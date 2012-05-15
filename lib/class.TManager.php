<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once ('class.TFolder.php');
	require_once ('class.TLinker.php');



	/**
	 * Manages Translation Folders and Translations.
	 *
	 * @package Frontend Localisation
	 *
	 * @static
	 *
	 * @author  Vlad Ghita
	 */
	final class TManager
	{

		/*------------------------------------------------------------------------------------------------*/
		/*  Properties  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Translation path
		 *
		 * @var string
		 */
		private static $path = '';

		/**
		 * Page prefix
		 *
		 * @var string
		 */
		private static $page_prefix = '';

		/**
		 * Reference language
		 *
		 * @var string
		 */
		private static $ref_lang = '';

		/**
		 * Storage format
		 *
		 * @var string
		 */
		private static $storage_format = '';

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
		private static $supported_storage_formats = array(
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
		private static $t_folders = array();

		/**
		 * Translation Parsers. Caches them for easy access.
		 *
		 * @var TParser[]
		 */
		private static $t_parsers = array();



		/*------------------------------------------------------------------------------------------------*/
		/*  Getters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Get translation path
		 *
		 * @return string
		 */
		public static function getPath(){
			return (string)self::$path;
		}

		/**
		 * Get page prefix
		 *
		 * @return string
		 */
		public static function getPagePrefix(){
			return (string) self::$page_prefix;
		}

		/**
		 * Get reference language
		 *
		 * @return string
		 */
		public static function getRefLang(){
			return (string)self::$ref_lang;
		}

		/**
		 * Get storage format
		 *
		 * @return string
		 */
		public static function getStorageFormat(){
			return (string)self::$storage_format;
		}

		/**
		 * Get all Translation folders
		 *
		 * @return TFolder[]
		 */
		public static function getFolders(){
			return (array)self::$t_folders;
		}

		/**
		 * Get Translation folder identified by `$lang_code`
		 *
		 * @param string $lang_code
		 *
		 * @return TFolder|null
		 */
		public static function getFolder($lang_code){
			General::ensureType(array(
				'lang_code' => array('var' => $lang_code, 'type' => 'string')
			));

			if( !isset(self::$t_folders[$lang_code]) ) return null;

			if( !self::$t_folders[$lang_code] instanceof TFolder ) return null;

			return self::$t_folders[$lang_code];
		}

		/**
		 * Get supported storage formats.
		 *
		 * @return array
		 */
		public static function getSupportedStorageFormats(){
			return (array)self::$supported_storage_formats;
		}

		/**
		 * Get a TParser instance for Translation transform.
		 *
		 * @param string $storage_format (optional)
		 *
		 * @return TParser - Appropriate TParser for $storage_format
		 */
		public static function getParser($storage_format = null){
			General::ensureType(array(
				'storage_format' => array('var' => $storage_format, 'type' => 'string', 'optional' => true)
			));

			if( !self::validateStorageFormat($storage_format) ){
				$storage_format = self::getStorageFormat();
			}

			if( !self::validateStorageFormat($storage_format) )
				return null;

			if( !(self::$t_parsers[$storage_format] instanceof TParser) ){
				self::loadStorageClass($storage_format, 'TParser');

				$class_name = strtoupper($storage_format).'_TParser';

				self::$t_parsers[$storage_format] = new $class_name();
			}

			return self::$t_parsers[$storage_format];
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Setters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Set translation path
		 *
		 * @param string $path
		 *
		 * @return boolean
		 */
		public static function setPath($path){
			if( !General::realiseDirectory($path) ) return false;

			self::$path = $path;

			return true;
		}

		/**
		 * Set page prefix
		 *
		 * @param string $page_prefix
		 *
		 * @return boolean
		 */
		public static function setPagePrefix($page_prefix){
			General::ensureType(array(
				'page_prefix' => array('var' => $page_prefix, 'type' => 'string')
			));

			self::$page_prefix = $page_prefix;

			return true;
		}

		/**
		 * Set reference language
		 *
		 * @param string $lang_code
		 *
		 * @return boolean
		 */
		public static function setRefLang($lang_code){
			if( !FLang::validateLangCode($lang_code) )return false;

			self::$ref_lang = $lang_code;

			return true;
		}

		/**
		 * Set storage format
		 *
		 * @param string $storage_format
		 *
		 * @return boolean
		 */
		public static function setStorageFormat($storage_format){
			if( !self::validateStorageFormat($storage_format) ) return false;

			if( !is_dir(EXTENSIONS.'/'.FL_GROUP.'/lib/'.$storage_format.'/') ) return false;

			self::$storage_format = $storage_format;

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
		public static function createTranslation(array $current_page){
			// if it has a parent, build entire ascending line
			if( !empty($current_page['parent']) ){
				$handle = self::_createAncestorFilename($current_page['parent'], FLPageManager::instance()->listAll());
			}

			$handle = self::$page_prefix.$handle.$current_page['handle'];

			foreach( self::$t_folders as $t_folder ){
				$t_folder->addTranslation($handle, array('type' => 'page'));
				$translation = $t_folder->getTranslation($handle);
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
		public static function editTranslation(array $current_page){
			$pages = FLPageManager::instance()->listAll();

			// get ancestor handle part
			$old_ancestor_handle = '';
			$new_ancestor_handle = '';

			if( !empty($current_page['old_parent']) ){
				$old_ancestor_handle = self::_createAncestorFilename($current_page['old_parent'], $pages);
			}

			if( !empty($current_page['new_parent']) ){
				$new_ancestor_handle = self::_createAncestorFilename($current_page['new_parent'], $pages);
			}

			// get children of this page, including self
			$descendant_handles = array();
			self::_createDescendantFilenames($current_page['id'], $pages, '', $descendant_handles);

			$handles = array();

			foreach( $descendant_handles as $desc_handle ){
				$desc_handle = trim($desc_handle, '_');

				$old_handle = self::$page_prefix.$old_ancestor_handle.$desc_handle;
				$new_handle = self::$page_prefix.$new_ancestor_handle.preg_replace("/{$current_page['old_handle']}/", $current_page['new_handle'], $desc_handle, 1);

				$handles[$old_handle] = $new_handle;
			}

			// update Translations whichs' name depend on this page
			foreach( $handles as $old_handle => $new_handle ){
				self::changeTranslationHandle($old_handle, $new_handle);
			}

			return $handles;
		}

		/**
		 * Deletes Translation for given Pages.
		 *
		 * @param array $page_ids
		 */
		public static function deleteTranslation(array $page_ids){
			$handles = array();
			$pages = FLPageManager::instance()->listAll();

			// build handles to be deleted
			foreach( $page_ids as $page_id ){
				if( FLPageManager::instance()->hasChildren($page_id) ) continue;

				$handle = '';
				if( !empty($pages[$page_id]['parent']) ){
					$handle = self::_createAncestorFilename($pages[$page_id]['parent'], $pages);
				}
				$handles[] = self::$page_prefix.$handle.$pages[$page_id]['handle'];
			}

			foreach( self::$t_folders as $t_folder ){
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
		public static function syncTranslation($handle){
			if( !is_string($handle) || empty($handle) ) return false;

			$ref_lang = TManager::getRefLang();

			// make sure reference folder exists
			if( !self::addFolder($ref_lang) ) return false;

			$ref_translation = self::$t_folders[$ref_lang]->getTranslation($handle);

			// make sure ref_file exists
			if( is_null($ref_translation) ) return false;

			$valid = true;

			foreach( FLang::getLangs() as $lc ){

				if( $lc === $ref_lang ) continue;

				if( !self::addFolder($lc) ){
					$valid = false;
					continue;
				}

				self::$t_folders[$lc]->addTranslation($handle);
				$translation = self::$t_folders[$lc]->getTranslation($handle);

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
		public static function changeTranslationHandle($old_handle, $new_handle){
			$valid = true;

			// keeps track of altered Translations
			$changed = array();

			// try to rename files
			foreach( self::$t_folders as $t_folder ){
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
		public static function updateFolders($langs = null){
			// if no languages desired, update all folders
			if( empty($langs) ){
				$langs = FLang::getLangs();
			}

			if( !empty($langs) ){

				// update folder for reference language
				$ref_lang = TManager::getRefLang();

				if( !empty($ref_lang) ){
					self::addFolder($ref_lang);
					$ref_folder = self::getFOlder($ref_lang);

					if( is_null($ref_folder) ) return false;

					$ref_folder->updateTranslationsForPages();

					// update remaining folders
					foreach( $langs as $lc ){
						if( $lc === $ref_lang ) continue;

						self::addFolder($lc);
						self::getFolder($lc)->updateTranslations( $ref_folder->getTranslations() );
					}
				}
			}
		}

		/**
		 * Delete Translation Folders for given languages.
		 *
		 * @param array $langs
		 */
		public static function deleteFolders(array $langs){
			// if no languages desired, delete all folders
			if( empty($langs) ){
				$langs = FLang::getLangs();
			}

			foreach( $langs as $lc ){
				if( General::deleteDirectory(self::$path.'/'.$lc) ){
					unset(self::$t_folders[$lc]);
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
		 * @return boolean
		 */
		public static function addFolder($lang_code){
			General::ensureType(array(
				'lang_code' => array('var' => $lang_code, 'type' => 'string')
			));

			if( !FLang::validateLangCode($lang_code) ) return false;

			if( is_null(self::getFolder($lang_code)) ){
				if( !General::realiseDirectory(self::$path.'/'.$lang_code) ) return false;

				self::$t_folders[$lang_code] = new TFolder($lang_code);
			}

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Public Utilities  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Validate given storage_format.
		 *
		 * @param $storage_format
		 *
		 * @return bool
		 */
		public static function validateStorageFormat($storage_format){
			return array_key_exists($storage_format, self::$supported_storage_formats);
		}

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
		public static function loadStorageClass($storage_format, $class){
			if( !self::validateStorageFormat($storage_format) )
				throw new Exception("Storage format `{$storage_format}` is not supported.`");

			$filename = EXTENSIONS.'/'.FL_GROUP.'/lib/'.$storage_format.'/class.'.$class.'.php';
			$class_name = strtoupper($storage_format).'_'.$class;

			return (boolean)self::_loadClass($filename, $class_name);
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
		public static function loadTranslationClass($type){
			$type = ($type == 'page') ? ucfirst($type) : '';

			$filename = EXTENSIONS.'/'.FL_GROUP.'/lib/class.Translation'.$type.'.php';
			$class_name = 'Translation'.$type;

			return (boolean)self::_loadClass($filename, $class_name);
		}

		/**
		 * Creates Page handle from Page ID.
		 *
		 * @param integer - Page ID
		 *
		 * @return string - Page handle
		 */
		public static function getPageHandle($page_id){
			$pages = FLPageManager::instance()->listAll();

			$parent_handle = self::_createAncestorFilename($page_id, $pages);

			return (string) self::$page_prefix.trim($parent_handle, '_');
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
		private static function _loadClass($filename, $class_name){
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
		private static function _createAncestorFilename($page_id, $pages = null){
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
		private static function _createDescendantFilenames($page_id, $pages, $handle, &$handles){
			$handle .= '_'.$pages[$page_id]['handle'];
			$handles[] = $handle;

			foreach( $pages as $page ){
				if( $page['parent'] == $page_id ){
					self::_createDescendantFilenames($page['id'], $pages, $handle, $handles);
				}
			}
		}

	}
