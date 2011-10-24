<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once('class.TranslationFolder.php');
	require_once('class.FrontendLocalisationPageManager.php');
	
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
		
		
		
		public function __construct($translation_path) {
			$this->path = $translation_path;
			
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
		 * @return TranslationFolder
		 */
		public function getFolder($language_code){
			return $this->t_folders[$language_code];
		}
		
		/**
		 * Creates Translation File for given Page.
		 * 
		 * @param array $current_page - page info. Must include id, handle and parent.
		 */
		public function createTranslationFile(array $current_page){
			$page_manager = new FrontendLocalisationPageManager();
			
			// if it has a parent, build entire ascending line
			if( !empty($current_page['parent']) ){
				$filename = $this->_createAncestorFilename( $current_page['parent'], $page_manager->listAll() );
			}
			
			$filename = Symphony::Configuration()->get('page_name_prefix','frontend_localisation') . $filename . $current_page['handle'] . '.xml';
			
			foreach( $this->t_folders as $t_folder ){
				$t_folder->addFile($filename);
			}
		}
		
		/**
		 * Edits Translation File for given Page.
		 * 
		 * @param array $current_page - page info. Must include id, old_handle, new_handle and parent.
		 */
		public function editTranslationFiles(array $current_page){
			$page_manager = new FrontendLocalisationPageManager();
			$pages = $page_manager->listAll();
			
			// get ancestor filename part
			$ancestor_filename = '';
			if( !empty($current_page['parent']) ){
				$ancestor_filename = $this->_createAncestorFilename($current_page['parent'], $pages);
			}
			
			// get children of this page, including self
			$descendant_filenames = array();
			$this->_createDescendantFilenames($current_page['id'], $pages, '', $descendant_filenames);
			
			$page_prefix = Symphony::Configuration()->get('page_name_prefix','frontend_localisation');
			$filenames = array();
			
			foreach( $descendant_filenames as $desc_filename ){
				$desc_filename = trim($desc_filename, '_');
				
				$old_filename = $page_prefix . $ancestor_filename . $desc_filename . '.xml';
				$new_filename = $page_prefix . $ancestor_filename . preg_replace("/{$current_page['old_handle']}/", $current_page['new_handle'], $desc_filename, 1) . '.xml';
				
				$filenames[$old_filename] = $new_filename;
			}
			
			// update files
			foreach( $this->t_folders as $t_folder ){
				foreach( $filenames as $old_filename => $new_filename ){
					$t_folder->getFile($old_filename)->setFilename($new_filename);
				}
			}
		}
		
		/**
		 * Deletes Translation Files for given Pages.
		 * 
		 * @param array $page_ids
		 */
		public function deleteTranslationFiles(array $page_ids){
			$filenames = array();
			$page_manager = new FrontendLocalisationPageManager();
			$pages = $page_manager->listAll();
			
			// build filenames to be deleted
			foreach( $page_ids as $page_id ){
				if( $this->_hasChildren($page_id) ) continue;
				
				$filename = '';
				if( !empty($pages[$page_id]['parent']) ){
					$filename = $this->_createAncestorFilename($pages[$page_id]['parent'], $pages);
				}
				$filenames[] = Symphony::Configuration()->get('page_name_prefix','frontend_localisation') . $filename . $pages[$page_id]['handle'] . '.xml';
			}
			
			foreach( $this->t_folders as $t_folder ){
				foreach( $filenames as $filename ){
					$t_folder->deleteFile($filename);
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
					
					// if a filename couldn't be changed, rollback changes (fingers crossed)
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
			if (empty($language_codes)) {
				$language_codes = FrontendLanguage::instance()->languageCodes();
			}
			
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
					Administration::instance()->Page->Alert = new Alert(
						__(
							'<code>%s</code>: Failed to remove <code>%s</code> folder.', 
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
			$folder = $this->path.'/'.$language_code;
			
			if( !is_dir($folder) ){
				General::realiseDirectory($folder);
			}
			
			if( empty($this->t_folders[$language_code]) ){
				$this->t_folders[$language_code] = new TranslationFolder($this->path, $language_code);
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
		 * Creates the filename for given page, having all pages.
		 * 
		 * @param integer $page_id - target page id
		 * @param array $pages - all pages
		 * 
		 * @return string - filename
		 */
		private function _createAncestorFilename($page_id, $pages){
			$page = $pages[$page_id];
			
			$filename = $page['handle'] . '_';
			while( !empty($page['parent']) ){
				$page = $pages[$page['parent']];
				$filename = $page['handle'] . '_' . $filename;
			}
			
			return $filename;
		}
		
		/**
		 * Checks if given page has children.
		 * 
		 * @param integer $page_id
		 */
		private function _hasChildren($page_id) {
			return (boolean)Symphony::Database()->fetchVar('id', 0, " SELECT `id` FROM `tbl_pages` WHERE parent = '{$page_id}' LIMIT 1");
		}
		
		/**
		 * Creates an array with descending pages of $page_id.
		 * 
		 * @param integer $page_id - start page
		 * @param array $pages - all Symphony pages
		 * @param string $filename - internal used to store current filename
		 * @param array &$filenames - resulting array
		 */
		private function _createDescendantFilenames($page_id, $pages, $filename, &$filenames){
			$filename .= '_' . $pages[$page_id]['handle'];
			$filenames[] = $filename;
			
			foreach( $pages as $page ){
				if( $page['parent'] == $page_id ){
					$this->_createDescendantFilenames($page['id'], $pages, $filename, $filenames);
				}
			}
		}
	
	}
	