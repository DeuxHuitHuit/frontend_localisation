<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once('class.TranslationFolder.php');
	require_once('class.FrontendLanguage.php');
	require_once('class.FLPageManager.php');
	
	/**
	 * Manages Translation Folders and Files.
	 * 
	 * @package Frontend Localisation
	 * 
	 * @author Vlad Ghita
	 *
	 */
	final class TranslationManager
	{
		
		/**
		 * Path to translation folder
		 * 
		 * @var string
		 */
		private $path = '';
		
		/**
		 * Translation folders
		 * 
		 * @var array
		 */
		private $t_folders = array();
		
		
		
		/**
		 * Constructor. On initialization, discover existing translation files.
		 * 
		 * @param string $translation_path (optional) - path to translations. Defaults to `/workspace/translations/`
		 */
		public function __construct($translation_path = null) {
			if( is_string($translation_path) && !empty($translation_path) ){
				$this->path = $translation_path;
			}
			else{
				$this->path = DOCROOT . Symphony::Configuration()->get('translation_path','frontend_localisation');
			}
			
			$this->_discoverFolders();
		}
		
		
		
		/**
		 * Access all Translation Folders.
		 * 
		 * @return array
		 */
		public function getFolders(){
			return $this->t_folders;
		}
		
		/**
		 * Access a Translation Folder.
		 * 
		 * @param string $name
		 * 
		 * @return TranslationFolder or null if it is not set.
		 */
		public function getFolder($language_code){
			if( isset($this->t_folders[$language_code]) )
				return $this->t_folders[$language_code];
			
			return null;
		}
		
		/**
		 * Creates Translation File for given Page.
		 * 
		 * @param array $current_page - page info. Must include id, handle and parent.
		 */
		public function createTranslationFile(array $current_page){
			
			// if it has a parent, build entire ascending line
			if( !empty($current_page['parent']) ){
				$handle = $this->_createAncestorFilename( $current_page['parent'], FLPageManager::instance()->listAll() );
			}
			
			$handle = Symphony::Configuration()->get('page_name_prefix','frontend_localisation') . $handle . $current_page['handle'];
			
			foreach( $this->t_folders as $t_folder ){
				$t_folder->addFile($handle);
			}
		}
		
		/**
		 * Edits Translation File for given Page.
		 * 
		 * @param array $current_page - page info. Must include id, old_handle, new_handle and parent.
		 */
		public function editTranslationFiles(array $current_page){
			$pages = FLPageManager::instance()->listAll();
			
			// get ancestor handle part
			$ancestor_handle = '';
			if( !empty($current_page['parent']) ){
				$ancestor_handle = $this->_createAncestorFilename($current_page['parent'], $pages);
			}
			
			// get children of this page, including self
			$descendant_handles = array();
			$this->_createDescendantFilenames($current_page['id'], $pages, '', $descendant_handles);
			
			$page_prefix = Symphony::Configuration()->get('page_name_prefix','frontend_localisation');
			$handles = array();
			
			foreach( $descendant_handles as $desc_handle ){
				$desc_handle = trim($desc_handle, '_');
				
				$old_handle = $page_prefix . $ancestor_handle . $desc_handle;
				$new_handle = $page_prefix . $ancestor_handle . preg_replace("/{$current_page['old_handle']}/", $current_page['new_handle'], $desc_handle, 1);
				
				$handles[$old_handle] = $new_handle;
			}
			
			// update files
			foreach( $this->t_folders as $t_folder ){
				foreach( $handles as $old_handle => $new_handle ){
					$t_folder->getFile($old_handle)->setFilename($new_handle);
				}
			}
		}
		
		/**
		 * Deletes Translation Files for given Pages.
		 * 
		 * @param array $page_ids
		 */
		public function deleteTranslationFiles(array $page_ids){
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
					$t_folder->deleteFile($handle);
				}
			}
		}
		
		/**
		 * Change prefix for pages' Translation Files; if something went wrong, rollback changes
		 * 
		 * @param string $old_prefix
		 * @param string $new_prefix
		 */
		public function changeFilenamesPrefix($old_prefix, $new_prefix){
			foreach ($this->t_folders as $t_folder) {
				
				// try to change all Files in this Folder
				if( !$t_folder->changeFilenamesPrefix($old_prefix, $new_prefix) ){
					
					// if a handle couldn't be changed, rollback changes (fingers crossed)
					foreach ($this->t_folders as $b_t_folder) {
						if( $b_t_folder->getLanguageCode() != $t_folder->getLanguageCode() ){
							$b_t_folder->changeFilenamesPrefix($new_prefix, $old_prefix);
						}
						else break;
					}
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * Updates translation folders:
		 *   1. creates missing folders;
		 *   2. updates exising ones;
		 * 
		 * @param array $languages_codes - desired languages to update
		 */
		public function updateFolders(array $language_codes = null) {
			
			// if no languages desired, update all folders
			if( empty($language_codes) ){
				$language_codes = FrontendLanguage::instance()->languageCodes();
			}
			
			if( !empty($language_codes) ){
			
				// update folder for reference language
				$reference_language = FrontendLanguage::instance()->referenceLanguage();
				if (empty($this->t_folders[$reference_language])) {
					$this->addFolder($reference_language);
				}
				$this->t_folders[$reference_language]->updateFilesForPages();
				
				
				// update remaining folders
				foreach ($language_codes as $language_code) {
					if( $language_code === $reference_language ) continue;
					
					$this->addFolder($language_code);
					$this->t_folders[$language_code]->updateFiles( $this->t_folders[$reference_language]->getFiles() );
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
			if (empty($language_codes)) {
				$language_codes = (array) FrontendLanguage::instance()->languageCodes();
			}
			
			foreach ($language_codes as $language_code) {
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
		 */
		public function addFolder($language_code){
			if( !is_string($language_code) || empty($language_code) ) return false;
			
			$folder = $this->path.'/'.$language_code;
			
			if( !is_dir($folder) ){
				if( !General::realiseDirectory($folder) ) return false;
			}
			
			if( empty($this->t_folders[$language_code]) ){
				$this->t_folders[$language_code] = new TranslationFolder($this->path, $language_code);
			}
			
			return true;
		}
		
		/**
		 * Synchronize business data for $dest Translation from $source Translation.
		 * It only inserts missing items without their value.
		 * 
		 * @param string $handle
		 */
		public function updateFile($handle){
			if( !is_string($handle) || empty($handle) ) return false;
			
			$reference_language = FrontendLanguage::instance()->referenceLanguage();
			
			// make sure reference folder exists
			if( !$this->addFolder($reference_language) ) return false;
			
			$ref_t_file = $this->t_folders[$reference_language]->getFile($handle);
			
			// make sure ref_file exists
			if( empty($ref_t_file) ) return false;
			
			$ref_t_file->ensureStructure();
			$valid = true;
			
			foreach( FrontendLanguage::instance()->languageCodes() as $language_code ){
				
				if( $language_code === $reference_language ) continue;
				
				if( !$this->addFolder($language_code) ){
					$valid = false;
					continue;
				}
				
				$t_folder = $this->t_folders[$language_code];
				
				$t_file = $t_folder->getFile($handle);
				if( empty($t_file) || !$t_file->ensureStructure() ){
					$valid = false;
					continue;
				}
				
				$t_folder->syncFilesData($ref_t_file, $t_file);
			}
			
			return $valid;
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
		
		
		
		/**
		 * Discover existing folders in translation folder.
		 */
		private function _discoverFolders() {
			$structure = General::listStructure($this->path);
			
			if ( !empty($structure['dirlist']) ) {
				$language_codes = FrontendLanguage::instance()->languageCodes();
			
				foreach ($structure['dirlist'] as $language_code) {
					if (in_array($language_code, $language_codes)) {
						$this->addFolder($language_code);
					}
				}
			}
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
		 * @param array &$handles - resulting array
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
	
	}
	