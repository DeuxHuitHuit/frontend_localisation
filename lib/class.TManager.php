<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	

	require_once ('class.TFolder.php');
	require_once ('class.TLinker.php');
	
	
	
	/**
	 * Manages Translation Folders and Translations.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 *
	 */
	final class TManager implements Singleton
	{
		private static $instance;
		
		/**
		 * Supported storage formats.
		 *
		 * @var array
		 *
		 * Array(
		 * 		[format_XXX] => Array(
		 *			[description] - Description about the format.
		 *			[storage] - XXX - storage format. The same as the storage folder.
		 * 		)
		 * )
		 */
		private $supported_storage_formats = array(
				'xml' => array(
						'description' => 'XML',
				),
				'po' => array(
						'description' => 'GNU PO',
				),
				'i18n' => array(
						'description' => 'JAVA properties',
				)
		);
		
		/**
		 * Default storage format.
		 *
		 * @var string
		 */
		private $storage_format = null;
		
		/**
		 * Translation path.
		 *
		 * @var string
		 */
		private $path = null;
		
		/**
		 * Translation folders.
		 *
		 * 		Array [0-N](
		 * 			[$language_code] => TFolder
		 * 		)
		 *
		 * @var array
		 */
		private $t_folders = array();
		
		/**
		 * Translation Parsers. Caches them for easy access.
		 * 
		 * @var array
		 */
		private $t_parsers = array();
		
		
		
		/**
		 * Define the path for translations and auto-discover of tranlsation folders.
		 *
		 * @param string $translation_path - path to translations.
		 */
		private function __construct() {
			$this->path = WORKSPACE . Symphony::Configuration()->get('translation_path','frontend_localisation');
			
			// make sure translation folder exists
			General::realiseDirectory($this->path);
			
			$storage_format = Symphony::Configuration()->get('storage_format','frontend_localisation');
			
			// make sure default storage format is supported
			$this->storage_format = array_key_exists($storage_format, $this->supported_storage_formats) ? $storage_format : 'xml';
			
			$dir_storage = EXTENSIONS . '/frontend_localisation/lib/' . $this->storage_format . '/';
			
			// if necessary storage files are missing, abort
			if( !is_dir($dir_storage) ){
				throw new Exception("Storage directory `{$dir_storage}` doesn't exist. Translation Manager needs it there.`");
			}
			
			$this->_discoverFolders();
		}
		
		public static function instance(){
			if( !self::$instance instanceof TManager ){
				self::$instance = new TManager();
			}
			
			return self::$instance;
		}
		
		
		
		/**
		 * Getter for translation path.
		 *
		 * @return string
		 */
		public function getPath(){
			return (string) $this->path;
		}
		
		/**
		 * Getter for all Translation folders.
		 *
		 * @return array
		 */
		public function getFolders(){
			return (array) $this->t_folders;
		}
		
		/**
		 * Getter for Translation folder identified by `$language_code`.
		 *
		 * @param string $language_code - desired language.
		 *
		 * @return TFolder else null if $language_code is not set.
		 */
		public function getFolder($language_code){
			if( isset($this->t_folders[$language_code]) )
				return $this->t_folders[$language_code];
			
			return null;
		}
		
		/**
		 * Getter for supported storage formats.
		 *
		 * @return array
		 */
		public function getSupportedStorageFormats(){
			return (array) $this->supported_storage_formats;
		}
		
		/**
		 * Getter for default storage format.
		 *
		 * @return string
		 */
		public function getStorageFormat(){
			return (string) $this->storage_format;
		}
		
		/**
		 * Get a TParser instance for Translation transform.
		 * 
		 * @param string $storage_format
		 * 
		 * @return TParser - Appropriate TParser for $storage_format
		 */
		public function getParser($storage_format){
			if( !($this->t_parsers[$storage_format] instanceof TParser) ){
				$this->loadStorageClass($storage_format, 'TParser');
				
				$class_name = strtoupper($storage_format).'_TParser';
				
				$this->t_parsers[$storage_format] = new $class_name();
			}
			
			return $this->t_parsers[$storage_format];
		}
		
		
		
		/**
		 * Creates Translation for given Page.
		 *
		 * @param array $current_page - page info. Must include id, handle and parent.
		 */
		public function createTranslation(array $current_page){
			// if it has a parent, build entire ascending line
			if( !empty($current_page['parent']) ){
				$handle = $this->_createAncestorFilename( $current_page['parent'], FLPageManager::instance()->listAll() );
			}
			
			$handle = Symphony::Configuration()->get('page_name_prefix','frontend_localisation') . $handle . $current_page['handle'];
			
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
			
			$page_prefix = Symphony::Configuration()->get('page_name_prefix','frontend_localisation');
			$handles = array();
			
			foreach( $descendant_handles as $desc_handle ){
				$desc_handle = trim($desc_handle, '_');
				
				$old_handle = $page_prefix . $old_ancestor_handle . $desc_handle;
				$new_handle = $page_prefix . $new_ancestor_handle . preg_replace("/{$current_page['old_handle']}/", $current_page['new_handle'], $desc_handle, 1);
				
				$handles[$old_handle] = $new_handle;
			}
			
			// update Translations whichs' name depend on this page
			foreach( $handles as $old_handle => $new_handle ){
				$this->changeTranslationHandle($old_handle, $new_handle);
			}
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
				$handles[] = Symphony::Configuration()->get('page_name_prefix','frontend_localisation') . $handle . $pages[$page_id]['handle'];
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
		
			$reference_language = FLang::instance()->referenceLanguage();
		
			// make sure reference folder exists
			if( !$this->addFolder($reference_language) ) return false;
		
			$ref_translation = $this->t_folders[$reference_language]->getTranslation($handle);
		
			// make sure ref_file exists
			if( empty($ref_translation) ) return false;
		
			$valid = true;
		
			foreach( FLang::instance()->ld()->languageCodes() as $language_code ){
		
				if( $language_code === $reference_language ) continue;
		
				if( !$this->addFolder($language_code) ){
					$valid = false; continue;
				}
		
				$translation = $this->t_folders[$language_code]->getTranslation($handle);
		
				if( empty($translation) ){
					$valid = false; continue;
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
					$t_linker->linkToPage($new_handle, $page_id);
					$t_linker->unlinkFromPage($old_handle, $page_id);
				}
		
				// remove old files
				foreach( $changed as $translation ){
					General::deleteFile($translation->getPath() .'/'. $translation->meta()->getFilename($old_handle));
					General::deleteFile($translation->getPath() .'/'. $translation->data()->getFilename($old_handle));
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
		
			return (boolean) $valid;
		}
		
		
		
		/**
		 * Updates Translation Folders:
		 *
		 *   1. creates missing folders;
		 *   2. updates exising ones;
		 *
		 * @param array $languages_codes - desired languages to update
		 */
		public function updateFolders(array $language_codes = null) {
			// if no languages desired, update all folders
			if( empty($language_codes) ){
				$language_codes = FLang::instance()->ld()->languageCodes();
			}
			
			if( !empty($language_codes) ){
			
				// update folder for reference language
				$reference_language = FLang::instance()->referenceLanguage();
				$this->addFolder($reference_language);
				
				$this->t_folders[$reference_language]->updateTranslationsForPages();
				
				
				// update remaining folders
				foreach ($language_codes as $language_code) {
					if( $language_code === $reference_language ) continue;
					
					$t_folder = $this->addFolder($language_code);
					$t_folder->updateTranslations( $this->t_folders[$reference_language]->getTranslations() );
				}
			}
		}
		
		/**
		 * Delete Translation Folders for given languages.
		 *
		 * @param array $language_codes
		 */
		public function deleteFolders(array $language_codes = null){
			// if no languages desired, delete all folders
			if( empty($language_codes) ){
				$language_codes = FLang::instance()->ld()->languageCodes();
			}
			
			foreach( $language_codes as $language_code ){
				if( self::deleteFolder($this->path . '/' . $language_code) ){
					unset($this->t_folders[$language_code]);
				}
				else{
					Administration::instance()->Page->pageAlert(
						__(
							'<code>%1$s</code>: Failed to remove <code>%2$s</code> folder.',
							array(FRONTEND_LOCALISATION_NAME, $language_code)
						),
						Alert::ERROR
					);
				}
			}
		}
		
		/**
		 * Translation Folder generator.
		 *
		 * @param string $language_code
		 * 
		 * @return TFolder
		 */
		public function addFolder($language_code){
			if( !is_string($language_code) || empty($language_code) || !in_array($language_code, FLang::instance()->ld()->languageCodes() )) return false;
			
			if( empty($this->t_folders[$language_code]) ){
				if( !General::realiseDirectory($this->path.'/'.$language_code) ) return false;
			
				$this->t_folders[$language_code] = new TFolder($this, $language_code);
			}
			
			return $this->t_folders[$language_code];
		}
		
		
		
		/**
		 * Loads a Translation resource identified by `$class` abstract class name.
		 *
		 * 		- If class file doesn't exist, `Exception` is thrown.
		 * 		- If class doesn't exist in file, `Exception` is thrown.
		 * 
		 * @param string $storage_format
		 * @param string $class - abstract base class name.
		 * 		e.g. $class=='TParser' and $storage_format=='xml' => required class is `XML_TParser`
		 *
		 * @throws Exception - will be catched by Symphony's default error handler.
		 */
		public function loadStorageClass($storage_format, $class){
			if( !array_key_exists($storage_format, $this->supported_storage_formats) ){
				throw new Exception("Storage format `{$storage_format}` is not supported.`");
			}
			
			$filename = EXTENSIONS . '/frontend_localisation/lib/'. $storage_format .'/class.'. $class .'.php';
			$class_name = strtoupper($storage_format).'_'.$class;
			
			return (boolean) $this->_loadClass($filename, $class_name);
		}
		
		/**
		 * Loads a Translation type identified by `$type`.
		 * 
		 * @param string $type
		 * 
		 * @throws Exception - will be catched by Symphony's default error handler.
		 */
		public function loadTranslationClass($type){
			$type = ($type == 'page') ? ucfirst($type) : '';
			
			$filename = EXTENSIONS . '/frontend_localisation/lib/class.Translation'. $type .'.php';
			$class_name = 'Translation'.$type;
			
			return (boolean) $this->_loadClass($filename, $class_name);
		}
		
		
		
		/**
		 * Attempts to load given class from desired filename.
		 * 
		 * @param string $filename
		 * @param string $class_name
		 * 
		 * @return boolean - true if success
		 * 
		 * @throws Exception
		 */
		private function _loadClass($filename, $class_name){
			// $class file must exist
			if( !is_file($filename) ){
				throw new Exception("File '`{$filename}`' doesn't exist.`");
			}
			
			require_once ($filename);
			
			// $class must exist
			if( !class_exists($class_name) ){
				throw new Exception("Class `{$class_name}` could not be found in file `{$file_name}`.`");
			}
			
			return true;
		}
		
		/**
		 * Creates the handle for given page, having all pages.
		 *
		 * @param integer $page_id - target page id
		 * @param array $pages - all pages
		 *
		 * @return string - handle
		 */
		private function _createAncestorFilename($page_id, $pages){
			$page = $pages[$page_id];
			
			$handle = $page['handle'] . '_';
			while( !empty($page['parent']) ){
				$page = $pages[$page['parent']];
				$handle = $page['handle'] . '_' . $handle;
			}
			
			return $handle;
		}
		
		/**
		 * Creates an array with descending pages of $page_id.
		 *
		 * @param integer $page_id - start page
		 * @param array $pages - all Symphony pages
		 * @param string $handle - internal used to store current handle
		 * @param array $handles (pointer) - resulting array
		 */
		private function _createDescendantFilenames($page_id, $pages, $handle, &$handles){
			$handle .= '_' . $pages[$page_id]['handle'];
			$handles[] = $handle;
			
			foreach( $pages as $page ){
				if( $page['parent'] == $page_id ){
					$this->_createDescendantFilenames($page['id'], $pages, $handle, $handles);
				}
			}
		}
		
		/**
		 * Discover existing folders in translation folder.
		 */
		private function _discoverFolders(){
			$structure = General::listStructure($this->path);
		
			if( !empty($structure['dirlist']) ){
				$language_codes = FLang::instance()->ld()->languageCodes();
		
				foreach( $structure['dirlist'] as $language_code ){
					if( in_array($language_code, $language_codes) ){
						$this->addFolder($language_code);
					}
				}
			}
		}
		
		
		
		/**
		 * @todo To be removed in Symphony 2.3 as General::deleteDirectory($dir) will be available.
		 *
		 * @param string $tmp_path
		 */
		public static function deleteFolder($tmp_path){
			if (!is_writeable($tmp_path) && is_dir($tmp_path)){
				chmod($tmp_path,0777);
			}
		
			$handle = opendir($tmp_path);
			while ($tmp=readdir($handle)){
				if ($tmp!='..' && $tmp!='.' && $tmp!=''){
					if (is_writeable($tmp_path.'/'.$tmp) && is_file($tmp_path.'/'.$tmp)){
						unlink($tmp_path.'/'.$tmp);
					}
					elseif (!is_writeable($tmp_path.'/'.$tmp) && is_file($tmp_path.'/'.$tmp)){
						chmod($tmp_path.'/'.$tmp,0666);
						unlink($tmp_path.'/'.$tmp);
					}
		
					if (is_writeable($tmp_path.'/'.$tmp) && is_dir($tmp_path.'/'.$tmp)){
						self::deleteFolder($tmp_path.'/'.$tmp);
					}
					elseif (!is_writeable($tmp_path.'/'.$tmp) && is_dir($tmp_path.'/'.$tmp)){
						chmod($tmp_path.'/'.$tmp,0777);
						self::deleteFolder($tmp_path.'/'.$tmp);
					}
				}
			}
			closedir($handle);
			rmdir($tmp_path);
		
			return !is_dir($tmp_path);
		}
	}
	