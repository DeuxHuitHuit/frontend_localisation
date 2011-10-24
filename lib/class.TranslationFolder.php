<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once('class.TranslationFile.php');
	
	/**
	 * Takes care of a Translation Folder.
	 * 
	 * @package Frontend Localisation
	 * 
	 * @author Vlad Ghita
	 *
	 */
	final class TranslationFolder
	{
		
		/**
		 * Language code identifier
		 * 
		 * @var string
		 */
		private $langauge_code = '';
		
		/**
		 * Path to this folder
		 * 
		 * @var string
		 */
		private $path = '';
		
		/**
		 * Translation files in this folder
		 * 
		 * @var array
		 */
		private $t_files = array();
		
		
		
		/**
		 * Creates a new Translation folder, discovering existing Translation Files.
		 * 
		 * @param string $path - path to folder
		 * @param string $language_code - responsible for this language
		 */
		public function __construct($path, $language_code){
			$this->path = $path;
			$this->langauge_code = $language_code;
			
			$this->_discoverFiles();
		}
		
		
		
		/**
		 * Get folder language.
		 * 
		 * @return string
		 */
		public function getLanguageCode(){
			return (string) $this->langauge_code;
		}
		
		/**
		 * Get folder path.
		 * 
		 * @return string
		 */
		public function getPath(){
			return (string) $this->path;
		}
		
		/**
		 * Get all Translation Files in folder.
		 * 
		 * @return array
		 */
		public function getFiles(){
			return $this->t_files;
		}
		
		/**
		 * Get requested Translation File.
		 * 
		 * @param string $filename
		 * 
		 * @return TranslationFile
		 */
		public function getFile($filename){
			return $this->t_files[$filename];
		}
		
		/**
		 * Deletes a Translation File
		 * 
		 * @param string $filename
		 */
		public function deleteFile($filename){
			General::deleteFile($this->path . '/' . $this->langauge_code . '/' . $filename);
			unset($this->t_files[$filename]);
		}
		
		/**
		 * Synchronize Translation Files with Symphony Pages.
		 */
		public function updateFilesForPages(){
			// get all pages
			$query = "SELECT `id`, `handle`, `parent` FROM `tbl_pages` ORDER BY `handle`";
			
			try {
				$pages = Symphony::Database()->fetch($query, 'id');
				
			} catch (DatabaseException $e) {
				Symphony::$Log->pushToLog('It died in TranslationFolder class trying to fetch all pages... Poor fellow.', E_NOTICE, true);
			}
			
			foreach ($pages as $page) {
				$filename = $page['handle'];
				while( !empty($page['parent']) ){
					$page = $pages[$page['parent']];
					$filename = $page['handle'] . '_' . $filename;
				}
				
				$filename = Symphony::Configuration()->get('page_name_prefix','frontend_localisation'). $filename . '.xml';
				
				if( empty($this->t_files[$filename]) ) {
					$this->addFile($filename, '');
				}
			}
		}
		
		/**
		 * Update Translation Files againts reference language Translation Files.
		 * 
		 * @param array $ref_files - array of Translation Files representing reference_languages' files.
		 */
		public function updateFiles(array $ref_files){
			if( !empty($ref_files) && is_array($ref_files) ){
				
				foreach ($ref_files as $ref_filename => $ref_t_file) {
					$this->addFile($ref_filename);
					
					if( !$this->t_files[$ref_filename]->isTranslated() ){
						
						// make sure source file has needed XML structure
						if( $ref_t_file->ensureStructure() ){
							$this->t_files[$ref_filename]->setRefContent($ref_t_file);
						}
						
						// else set default template content
						else{
							$this->t_files[$ref_filename]->setContent();
						}
					}
					else{
						$this->t_files[$ref_filename]->ensureStructure();
					}
				}
			}
		}
		
		/**
		 * Translation File generator.
		 * 
		 * @param string $filename - Name of the file
		 * @param string $content (optional) - content of file.
		 */
		public function addFile($filename, $content = null) {
			if( empty($this->t_files[$filename]) ){
				$this->t_files[$filename] = new TranslationFile($this, $filename);
			}
			
			if( $content != null ){
				$this->t_files[$filename]->setContent($content);
			}
		}
		
		/**
		 * Change prefix for pages' Translation Files; if something went wrong, rollback changes
		 * 
		 * @param string $old_prefix
		 * @param string $new_prefix
		 * 
		 * @return boolean - true if all filenames have been changed, false otherwise
		 */
		public function changeFilenamesPrefix($old_prefix, $new_prefix){
			foreach( $this->t_files as $t_file ){
				$name = $t_file->getFilename();
				
				// make sure this is a Translation File for a Page (begins with prefix)
				if( strpos($name, $old_prefix) == 0 ){
					$name = preg_replace("/{$old_prefix}/", $new_prefix, $name, 1);
					
					// try to change this Filename
					if( !$t_file->setFilename($name) ){
						
						// if filename couldn't be changed, rollback changes (fingers crossed)
						foreach( $this->t_files as $b_t_file ){
							$b_name = $b_t_file->getFilename();
							
							if( $b_name != $t_file->getFilename() ){
								$b_name = preg_replace("/{$new_prefix}/", $old_prefix, $b_name, 1);
								$b_t_file->setFilename($b_name);
							}
							else break;
						}
						return false;
					}
				}
			}
			
			return true;
		}
		
		
		
		/**
		 * Discover existing files in translation folder.
		 */
		private function _discoverFiles(){
			$path = $this->path.'/'.$this->langauge_code;
			$structure = (array) General::listStructure($this->path.'/'.$this->langauge_code);
			
			if (!empty($structure['filelist'])) {
				foreach ($structure['filelist'] as $filename) {
					$this->addFile($filename);
				}
			}
		}
	}
	