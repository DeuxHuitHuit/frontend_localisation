<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once('class.TranslationFile.php');
	require_once('class.TranslationFileWriter.php');
	
	
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
		 * @param string $handle
		 * 
		 * @return TranslationFile or null if it is not set
		 */
		public function getFile($handle){
			if( !empty($this->t_files[$handle]) )
				return $this->t_files[$handle];
				
			return null;
		}
		
		/**
		 * Deletes a Translation File. Hardcoded extension.
		 * 
		 * @param string $handle
		 */
		public function deleteFile($handle){
			if( General::deleteFile($this->path . '/' . $this->langauge_code . '/' . $handle . '.xml') ){
				unset($this->t_files[$handle]);
				return true;
			}
			
			return false;
		}
		
		/**
		 * Synchronize Translation Files with Symphony Pages.
		 */
		public function updateFilesForPages(){
			$pages = FLPageManager::instance()->listAll();
			
			foreach ($pages as $page) {
				$handle = $page['handle'];
				
				while( !empty($page['parent']) ){
					$page = $pages[$page['parent']];
					$handle = $page['handle'] . '_' . $handle;
				}
				
				$handle = Symphony::Configuration()->get('page_name_prefix','frontend_localisation'). $handle;
				
				if( empty($this->t_files[$handle]) ) {
					$this->addFile($handle, '');
				}
				else{
					$this->t_files[$handle]->ensureStructure();
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
				
				foreach ($ref_files as $ref_handle => $ref_t_file) {
					$this->addFile($ref_handle);
					
					// make sure source file has needed XML structure
					if( $ref_t_file->ensureStructure() ){
						$this->syncFilesData($ref_t_file, $this->t_files[$ref_handle]);
					}

					// else set default template content
					else{
						$this->t_files[$ref_handle]->setContent();
					}
				}
			}
		}
		
		
		/**
		 * Synchronize business data for $dest Translation from $source Translation.
		 * It only inserts missing items without their value.
		 * 
		 * @param TranslationFile $source
		 * @param TranslationFile $dest
		 * 
		 * @return boolean - true in succes, false otherwise
		 */
		public function syncFilesData(TranslationFile $source, TranslationFile $dest){
			$tf_writer = new TranslationFileWriter();
			
			$source_trans = $tf_writer->convertXMLtoArray($source->getContentXML());
			$dest_trans = $tf_writer->convertXMLtoArray($dest->getContentXML());
			
			$translations = array();
			
			foreach( $source_trans as $xPath => $items ){
				
				if( array_key_exists($xPath, $dest_trans) ){
					foreach( $items as $handle => $value ){
						
						if( array_key_exists($handle, $dest_trans[$xPath]) ){
							$translations[$xPath][$handle] = $dest_trans[$xPath][$handle];
						}
						else{
							$translations[$xPath][$handle] = '';
						}
					}
				}
				
				else{
					foreach( $items as $handle => $value ){
						$translations[$xPath][$handle] = '';
					}
				}
			}
			
			$data = $tf_writer->convertArraytoString($dest->getContentXML(), $translations);
			
			return (boolean) $dest->setData($data);
		}
		
		/**
		 * Translation File generator.
		 * 
		 * @param string $handle - Handle of the file
		 * @param string $content (optional) - content of file.
		 */
		public function addFile($handle, $content = null) {
			if( empty($this->t_files[$handle]) ){
				$this->t_files[$handle] = new TranslationFile($this, $handle);
			}
			
			if( $content != null ){
				$this->t_files[$handle]->setContent($content);
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
				$name = $t_file->getHandle();
				
				// make sure this is a Translation File for a Page (begins with prefix)
				if( strpos($name, $old_prefix) == 0 ){
					$name = preg_replace("/{$old_prefix}/", $new_prefix, $name, 1);
					
					// try to change this Filename
					if( !$t_file->setFilename($name) ){
						
						// if filename couldn't be changed, rollback changes (fingers crossed)
						foreach( $this->t_files as $b_t_file ){
							$b_name = $b_t_file->getHandle();
							
							if( $b_name != $t_file->getHandle() ){
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
					$this->addFile( substr($filename,0,-4) );
				}
			}
		}
	}
	