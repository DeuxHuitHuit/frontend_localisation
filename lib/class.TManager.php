<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once ('class.TFolder.php');
	require_once ('class.TLinker.php');



	/**
	 * Manages Translation Folders and Translations.
	 *
	 * @package Frontend Localisation
	 *
	 * @author  Vlad Ghita
	 *
	 */
	final class TManager implements Singleton
	{

		/*------------------------------------------------------------------------------------------------*/
		/*  Properties  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * @var TManager
		 */
		private static $instance;

		/**
		 * Translation path
		 *
		 * @var string
		 */
		private $path = '';

		/**
		 * Reference language
		 *
		 * @var string
		 */
		private $ref_lang = '';

		/**
		 * Storage format
		 *
		 * @var string
		 */
		private $storage_format = '';

		/**
		 * Supported storage formats
		 *
		 * @var array
		 *
		 * Array(
		 *		 [format_XXX] => Array(
		 *			[description] - Description about the format.
		 *			[storage] - XXX - storage format. The same as the storage folder.
		 *		 )
		 * )
		 */
		private $supported_storage_formats = array(
			'xml' => array(
				'description' => 'XML',
			),
			// 				'po' => array(
			// 						'description' => 'GNU PO',
			// 				),
			// 				'i18n' => array(
			// 						'description' => 'JAVA properties',
			// 				)
		);

		/**
		 * Translation folders.
		 *
		 *		 Array [0-N](
		 *			 [$lang_code] => TFolder
		 *		 )
		 *
		 * @var TFolder[]
		 */
		private $t_folders = array();

		/**
		 * Translation Parsers. Caches them for easy access.
		 *
		 * @var TParser[]
		 */
		private $t_parsers = array();

		/**
		 * Switch to write config or not
		 *
		 * @var bool
		 */
		private $write_config = false;



		/*------------------------------------------------------------------------------------------------*/
		/*  Initialization  */
		/*------------------------------------------------------------------------------------------------*/

		private function __construct(){
			try{
				$this->_initRefLang();
				$this->_initPath();
				$this->_initStorageFormat();
				$this->_discoverFolders();
			}
			catch( Exception $e ){
				$message = $e->getMessage();

				Administration::instance()->Page->pageAlert($message, Alert::NOTICE);
				Symphony::Log()->pushToLog($message, E_NOTICE, true);
			}
		}

		public function __destruct(){
			if( $this->write_config === true ){
				Symphony::Configuration()->write();
			}
		}

		/**
		 * Initializes translation path
		 *
		 * @throws Exception
		 */
		private function _initPath(){
			$path = WORKSPACE.Symphony::Configuration()->get('translation_path', FL_GROUP);

			if( false === $this->setPath($path, false) ){
				throw new Exception(__('
					<code>%1$s</code>: Translation folder couldn\'t be created a <code>%2$s</code>. Please %3$s.',
					array(
						FL_NAME,
						$path,
						__('review settings')
					)
				));
			}
		}

		/**
		 * Initializes reference language
		 *
		 * @throws Exception
		 */
		private function _initRefLang(){
			$ref_lang = Symphony::Configuration()->get('ref_lang', FL_GROUP);

			if( false === $this->setRefLang($ref_lang, false) ){
				$langs = FLang::instance()->getLangs();

				if( !empty($langs) )
					throw new Exception(__('
					<code>%1$s</code>: Reference language code <code>%2$s</code> is not supported. Please %3$s.',
						array(
							FL_NAME,
							$ref_lang,
							'<a href="'.SYMPHONY_URL.'/system/preferences/#'.FL_GROUP.'_translation_path">'.__('review settings').'</a>'
						)
					));
			}
		}

		/**
		 * Initializes storage format
		 *
		 * @throws Exception
		 */
		private function _initStorageFormat(){
			$storage_format = Symphony::Configuration()->get('storage_format', FL_GROUP);

			if( fales === $this->setStorageFormat($storage_format, false) ){
				throw new Exception(__('
					<code>%1$s</code>: Storage directory <code>%2$s</code> for <code>%3$s</code> storage format doesn\'t exist. Please %4$s.',
					array(
						FL_NAME,
						$dir_storage,
						$this->storage_format,
						'<a href="'.SYMPHONY_URL.'/system/preferences/#'.FL_GROUP.'_translation_path">'.__('review settings').'</a>'
					)
				));
			}
		}

		/**
		 * Discover existing folders in translation folder
		 */
		private function _discoverFolders(){
			foreach( FLang::instance()->getLangs() as $lc ){
				if( is_dir($this->path.'/'.$lc) ){
					$this->addFolder($lc);
				}
			}
		}

		public static function instance(){
			if( !self::$instance instanceof TManager ){
				self::$instance = new TManager();
			}

			return self::$instance;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Getters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Get translation path
		 *
		 * @return string
		 */
		public function getPath(){
			return (string)$this->path;
		}

		/**
		 * Get reference language
		 *
		 * @return string
		 */
		public function getRefLang(){
			return (string)$this->ref_lang;
		}

		/**
		 * Get storage format
		 *
		 * @return string
		 */
		public function getStorageFormat(){
			return (string)$this->storage_format;
		}

		/**
		 * Get all Translation folders
		 *
		 * @return array
		 */
		public function getFolders(){
			return (array)$this->t_folders;
		}

		/**
		 * Get Translation folder identified by `$lang_code`
		 *
		 * @param string $lang_code (optional)
		 *
		 * @return TFolder|null
		 */
		public function getFolder($lang_code = null){
			General::ensureType(array(
				'lang_code' => array('var' => $lang_code, 'type' => 'string', 'optional' => true)
			));

			if( is_null($lang_code) ){
				$lang_code = $this->getRefLang();
			}

			if( array_key_exists($lang_code, $this->t_folders) && $this->t_folders[$lang_code] instanceof TFolder ){
				return $this->t_folders[$lang_code];
			}

			return null;
		}

		/**
		 * Get supported storage formats.
		 *
		 * @return array
		 */
		public function getSupportedStorageFormats(){
			return (array)$this->supported_storage_formats;
		}

		/**
		 * Get a TParser instance for Translation transform.
		 *
		 * @param string $storage_format (optional)
		 *
		 * @return TParser - Appropriate TParser for $storage_format
		 */
		public function getParser($storage_format = null){
			General::ensureType(array(
				'storage_format' => array('var' => $storage_format, 'type' => 'string', 'optional' => true)
			));

			if( is_null($storage_format) ){
				$storage_format = $this->getStorageFormat();
			}

			if( !array_key_exists($storage_format, $this->supported_storage_formats) )
				return null;

			if( !($this->t_parsers[$storage_format] instanceof TParser) ){
				$this->loadStorageClass($storage_format, 'TParser');

				$class_name = strtoupper($storage_format).'_TParser';

				$this->t_parsers[$storage_format] = new $class_name();
			}

			return $this->t_parsers[$storage_format];
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Setters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Set translation path
		 *
		 * @param string $path
		 * @param boolean $write
		 *
		 * @return boolean
		 */
		public function setPath($path, $write = true){

			// safe cheks
			if( !General::realiseDirectory($path) ){
				return false;
			}

			$this->path = $path;

			if( $write === true ){
				Symphony::Configuration()->set('translation_path', $this->path, FL_GROUP);
				$this->write_config = true;
			}

			return true;
		}

		/**
		 * Set reference language
		 *
		 * @param string $lang_code
		 * @param boolean $write
		 *
		 * @return boolean
		 */
		public function setRefLang($lang_code, $write = true){

			if( FLang::instance()->validateLangCode($lang_code) ){
				$this->ref_lang = $lang_code;
			}

			// defaults to main language
			else{
				$main_lang = FLang::instance()->getMainLang();

				if( !empty($main_lang) ){
					$this->ref_lang = $main_lang;
				}
				else{
					return false;
				}
			}

			if( $write === true ){
				Symphony::Configuration()->set('ref_lang', $this->ref_lang, FL_GROUP);
				$this->write_config = true;
			}

			return true;
		}

		/**
		 * Set storage format
		 *
		 * @param string $storage_format
		 * @param boolean $write
		 *
		 * @return boolean
		 */
		public function setStorageFormat($storage_format, $write = true){
			// make sure default storage format is supported
			if( !array_key_exists($storage_format, $this->supported_storage_formats) )
				$storage_format = 'xml';

			$dir_storage = EXTENSIONS.'/'.FL_GROUP.'/lib/'.$storage_format.'/';

			// if necessary storage files are missing, abort
			if( !is_dir($dir_storage) ){
				return false;
			}

			$this->storage_format = $storage_format;

			if( $write === true ){
				Symphony::Configuration()->set('storage_format', $this->storage_format, FL_GROUP);
				$this->write_config = true;
			}

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Translations  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Creates Translation for given Page.
		 *
		 * @param array $current_page - page info. Must include id, handle and parent.
		 */
		public function createTranslation(array $current_page){
			// if it has a parent, build entire ascending line
			if( !empty($current_page['parent']) ){
				$handle = $this->_createAncestorFilename($current_page['parent'], FLPageManager::instance()->listAll());
			}

			$handle = Symphony::Configuration()->get('page_name_prefix', FL_GROUP).$handle.$current_page['handle'];

			foreach( $this->t_folders as $t_folder ){
				/* @var $t_folder TFolder */
				$translation = $t_folder->addTranslation($handle, array('type' => 'page'));
				$translation->setName($handle);
			}
		}

		/**
		 * Edits Translation for given Page.
		 *
		 * @param array $current_page - page info. Must include id, old_handle, new_handle and parent.
		 *
		 * @return array - changed handles
		 */
		public function editTranslation(array $current_page){
			$pages = FLPageManager::instance()->listAll();

			// get ancestor handle part
			$old_ancestor_handle = '';
			$new_ancestor_handle = '';

			if( !empty($current_page['old_parent']) ){
				$old_ancestor_handle = $this->_createAncestorFilename($current_page['old_parent'], $pages);
			}

			if( !empty($current_page['new_parent']) ){
				$new_ancestor_handle = $this->_createAncestorFilename($current_page['new_parent'], $pages);
			}

			// get children of this page, including self
			$descendant_handles = array();
			$this->_createDescendantFilenames($current_page['id'], $pages, '', $descendant_handles);

			$page_prefix = Symphony::Configuration()->get('page_name_prefix', FL_GROUP);
			$handles = array();

			foreach( $descendant_handles as $desc_handle ){
				$desc_handle = trim($desc_handle, '_');

				$old_handle = $page_prefix.$old_ancestor_handle.$desc_handle;
				$new_handle = $page_prefix.$new_ancestor_handle.preg_replace("/{$current_page['old_handle']}/", $current_page['new_handle'], $desc_handle, 1);

				$handles[$old_handle] = $new_handle;
			}

			// update Translations whichs' name depend on this page
			foreach( $handles as $old_handle => $new_handle ){
				$this->changeTranslationHandle($old_handle, $new_handle);
			}

			return $handles;
		}

		/**
		 * Deletes Translation for given Pages.
		 *
		 * @param array $page_ids
		 */
		public function deleteTranslation(array $page_ids){
			$handles = array();
			$pages = FLPageManager::instance()->listAll();

			// build handles to be deleted
			foreach( $page_ids as $page_id ){
				if( FLPageManager::instance()->hasChildren($page_id) ) continue;

				$handle = '';
				if( !empty($pages[$page_id]['parent']) ){
					$handle = $this->_createAncestorFilename($pages[$page_id]['parent'], $pages);
				}
				$handles[] = Symphony::Configuration()->get('page_name_prefix', FL_GROUP).$handle.$pages[$page_id]['handle'];
			}

			foreach( $this->t_folders as $t_folder ){
				foreach( $handles as $handle ){
					$t_folder->deleteTranslation($handle);
				}
			}
		}

		/**
		 * Synchronize business data for all languages Translations from $handle Translation.
		 * It only inserts missing items without their value.
		 *
		 * @param string $handle - reference handle translation.
		 */
		public function syncTranslation($handle){
			if( !is_string($handle) || empty($handle) ) return false;

			$ref_lang = TManager::instance()->getRefLang();

			// make sure reference folder exists
			if( !$this->addFolder($ref_lang) ) return false;

			$ref_translation = $this->t_folders[$ref_lang]->getTranslation($handle);

			// make sure ref_file exists
			if( is_null($ref_translation) ) return false;

			$valid = true;

			foreach( FLang::instance()->getLangs() as $lc ){

				if( $lc === $ref_lang ) continue;

				if( !$this->addFolder($lc) ){
					$valid = false;
					continue;
				}

				$this->t_folders[$lc]->addTranslation($handle);
				$translation = $this->t_folders[$lc]->getTranslation($handle);

				if( is_null($translation) ){
					$valid = false;
					continue;
				}

				$translation->syncFrom($ref_translation);
			}

			return $valid;
		}

		/**
		 * Provides safe Translation handle change. It will first create $new_handle files
		 * and, if successfull, delete $old_handle files.
		 *
		 * @param string $old_handle
		 * @param string $new_handle
		 *
		 * return boolean - true if success, false otherwise
		 */
		public function changeTranslationHandle($old_handle, $new_handle){
			$valid = true;

			// keeps track of altered Translations
			$changed = array();

			// try to rename files
			foreach( $this->t_folders as $t_folder ){
				/* @var $t_folder TFolder */
				$valid = $t_folder->changeTranslationHandle($old_handle, $new_handle);

				$changed[] = $t_folder->getTranslation($new_handle);

				if( !$valid ) break;
			}

			// if renaming went well
			if( $valid ){
				// update relations with pages
				$t_linker = new TLinker();
				$pages = $t_linker->getLinkedPages($old_handle);

				foreach( array_keys($pages) as $page_id ){
					$t_linker->unlinkFromPage($old_handle, $page_id);
					$t_linker->linkToPage($new_handle, $page_id);
				}

				// remove old files
				foreach( $changed as $translation ){
					General::deleteFile($translation->getPath().'/'.$translation->meta()->getFilename($old_handle));
					General::deleteFile($translation->getPath().'/'.$translation->data()->getFilename($old_handle));
				}
			}

			// else try to rollback changes
			else{
				foreach( $changed as $translation ){
					/* @var $translation Translation */
					$translation->delete();
					$translation->setHandle($old_handle);
					$translation->setName($old_handle);
				}
			}

			return (boolean)$valid;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Translation Folders  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Updates Translation Folders:
		 *
		 *   1. creates missing folders;
		 *   2. updates exising ones;
		 *
		 * @param array $langs - desired languages to update
		 */
		public function updateFolders($langs = null){
			// if no languages desired, update all folders
			if( empty($langs) ){
				$langs = FLang::instance()->getLangs();
			}

			if( !empty($langs) ){

				// update folder for reference language
				$ref_lang = TManager::instance()->getRefLang();

				if( !empty($ref_lang) ){
					$this->addFolder($ref_lang);

					$this->t_folders[$ref_lang]->updateTranslationsForPages();


					// update remaining folders
					foreach( $langs as $lc ){
						if( $lc === $ref_lang ) continue;

						$t_folder = $this->addFolder($lc);
						$t_folder->updateTranslations($this->t_folders[$ref_lang]->getTranslations());
					}
				}
			}
		}

		/**
		 * Delete Translation Folders for given languages.
		 *
		 * @param array $langs
		 */
		public function deleteFolders(array $langs){
			// if no languages desired, delete all folders
			if( empty($langs) ){
				$langs = FLang::instance()->getLangs();
			}

			foreach( $langs as $lc ){
				if( General::deleteDirectory($this->path.'/'.$lc) ){
					unset($this->t_folders[$lc]);
				}
				else{
					Administration::instance()->Page->pageAlert(
						__(
							'<code>%1$s</code>: Failed to remove <code>%2$s</code> folder.',
							array(FL_NAME, $lc)
						),
						Alert::ERROR
					);
				}
			}
		}

		/**
		 * Translation Folder generator
		 *
		 * @param string $lang_code
		 *
		 * @return TFolder
		 */
		public function addFolder($lang_code){
			General::ensureType(array(
				'lang_code' => array('var' => $lang_code, 'type' => 'string')
			));

			if( !FLang::instance()->validateLangCode($lang_code) ) return false;

			if( empty($this->t_folders[$lang_code]) ){
				if( !General::realiseDirectory($this->path.'/'.$lang_code) ) return false;

				$this->t_folders[$lang_code] = new TFolder($this, $lang_code);
			}

			return $this->t_folders[$lang_code];
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Public Utilities  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Loads a Translation resource identified by `$class` abstract class name.
		 *
		 *		 - If class file doesn't exist, `Exception` is thrown.
		 *		 - If class doesn't exist in file, `Exception` is thrown.
		 *
		 * @param string $storage_format
		 * @param string $class - abstract base class name.
		 *                      e.g. $class=='TParser' and $storage_format=='xml' => required class is `XML_TParser`
		 *
		 * @throws Exception - will be catched by Symphony's default error handler.
		 *
		 * @return bool
		 */
		public function loadStorageClass($storage_format, $class){
			if( !array_key_exists($storage_format, $this->supported_storage_formats) ){
				throw new Exception("Storage format `{$storage_format}` is not supported.`");
			}

			$filename = EXTENSIONS.'/'.FL_GROUP.'/lib/'.$storage_format.'/class.'.$class.'.php';
			$class_name = strtoupper($storage_format).'_'.$class;

			return (boolean)$this->_loadClass($filename, $class_name);
		}

		/**
		 * Loads a Translation type identified by `$type`.
		 *
		 * @param string $type
		 *
		 * @throws Exception - will be catched by Symphony's default error handler.
		 *
		 * @return bool
		 */
		public function loadTranslationClass($type){
			$type = ($type == 'page') ? ucfirst($type) : '';

			$filename = EXTENSIONS.'/'.FL_GROUP.'/lib/class.Translation'.$type.'.php';
			$class_name = 'Translation'.$type;

			return (boolean)$this->_loadClass($filename, $class_name);
		}

		/**
		 * Creates Page handle from Page ID.
		 *
		 * @param integer - Page ID
		 *
		 * @return string - Page handle
		 */
		public function getPageHandle($page_id){
			$pages = FLPageManager::instance()->listAll();

			$parent_handle = $this->_createAncestorFilename($page_id, $pages);
			$prefix = Symphony::Configuration()->get('page_name_prefix', FL_GROUP);

			return (string)$prefix.trim($parent_handle, '_');
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Private utilities  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Attempts to load given class from desired filename.
		 *
		 * @param string $filename
		 * @param string $class_name
		 *
		 * @throws Exception
		 *
		 * @return boolean - true if success
		 */
		private function _loadClass($filename, $class_name){
			// $class file must exist
			if( !is_file($filename) ){
				throw new Exception("File '`{$filename}`' doesn't exist.`");
			}

			require_once ($filename);

			// $class must exist
			if( !class_exists($class_name) ){
				throw new Exception("Class `{$class_name}` could not be found in file `{$filename}`.`");
			}

			return true;
		}

		/**
		 * Creates the handle for given page, having all pages.
		 *
		 * @param integer $page_id - target page id
		 * @param array $pages     (optional) - all pages
		 *
		 * @return string - handle
		 */
		private function _createAncestorFilename($page_id, $pages = null){
			if( empty($pages) ) $pages = FLPageManager::instance()->listAll();

			$page = $pages[$page_id];

			$handle = $page['handle'].'_';
			while( !empty($page['parent']) ){
				$page = $pages[$page['parent']];
				$handle = $page['handle'].'_'.$handle;
			}

			return $handle;
		}

		/**
		 * Creates an array with descending pages of $page_id.
		 *
		 * @param integer $page_id - start page
		 * @param array $pages     - all Symphony pages
		 * @param string $handle   - internal used to store current handle
		 * @param array $handles   (pointer) - resulting array
		 */
		private function _createDescendantFilenames($page_id, $pages, $handle, &$handles){
			$handle .= '_'.$pages[$page_id]['handle'];
			$handles[] = $handle;

			foreach( $pages as $page ){
				if( $page['parent'] == $page_id ){
					$this->_createDescendantFilenames($page['id'], $pages, $handle, $handles);
				}
			}
		}

	}
