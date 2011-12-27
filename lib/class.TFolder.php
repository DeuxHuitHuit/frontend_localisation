<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once ('class.Translation.php');
	
	
	
	/**
	 * Takes care of a Translation Folder.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 *
	 */
	final class TFolder
	{
		
		/**
		 * Language code identifier
		 *
		 * @var string
		 */
		private $language_code = '';
		
		/**
		 * TManager to which this folder belongs.
		 *
		 * @var TManager
		 */
		private $parent = null;
		
		/**
		 * Translations in this folder
		 *
		 * @var array
		 */
		private $translations = array();
		
		
		
		/**
		 * Creates a new Translation folder, discovering existing Translations.
		 *
		 * @param string $path - path to folder
		 * @param string $language_code - responsible for this language
		 */
		public function __construct(TManager $t_manager, $language_code){
			$this->parent = $t_manager;
			$this->language_code = $language_code;
			
 			$this->_discoverTranslations();
		}
		
		/**
		 * On exit, save all config files.
		 */
		public function __destruct(){
			foreach( $this->translations as $translation ){
				/* @var $translation Translation */
				$translation->meta()->saveSettings();
			}
		}
		
		
		
		/**
		 * Getter for language code this folder manages.
		 *
		 * @return string
		 */
		public function languageCode(){
			return (string) $this->language_code;
		}
		
		/**
		 * Return path to this Translation folder
		 *
		 * @return string
		 */
		public function getPath(){
			return (string) $this->parent->getPath() .'/'. $this->language_code;
		}
		
		/**
		 * Getter for Translation Manager parent.
		 * 
		 * @return TManager
		 */
		public function getManager(){
			return $this->parent;
		}
		
		/**
		 * Get all Translations in folder.
		 *
		 * @return array
		 */
		public function getTranslations(){
			return $this->translations;
		}
		
		/**
		 * Get requested Translation.
		 *
		 * @param string $handle
		 *
		 * @return Translation else null if $handle is not set
		 */
		public function getTranslation($handle){
			if( !empty($this->translations[$handle]) )
				return $this->translations[$handle];
				
			return null;
		}
		
		
		
		/**
		 * Deletes a Translation.
		 *
		 * @param string $handle
		 */
		public function deleteTranslation($handle){
			if( !empty($this->translations[$handle]) ){
				if( $this->translations[$handle]->delete() ){
					unset($this->translations[$handle]);
				}
			}
			
			return true;
		}
		
		/**
		 * Synchronize Translations with Symphony Pages.
		 */
		public function updateTranslationsForPages(){
			$pages = FLPageManager::instance()->listAll();
			
			foreach ($pages as $page) {
				$handle = $page['handle'];
				
				while( !empty($page['parent']) ){
					$page = $pages[$page['parent']];
					$handle = $page['handle'] . '_' . $handle;
				}
				
				$handle = Symphony::Configuration()->get('page_name_prefix','frontend_localisation'). $handle;
				
				if( empty($this->translations[$handle]) ) {
					$this->addTranslation($handle);
				}
			}
		}
		
		/**
		 * Update Translations againts reference language Translations.
		 *
		 * @param array $ref_files - array of Translations representing reference_languages' files.
		 */
		public function updateTranslations(array $ref_files){
			if( !empty($ref_files) && is_array($ref_files) ){
				
				foreach ($ref_files as $ref_handle => $ref_translation) {
					$this->addTranslation($ref_handle);
					
					$this->translations[$ref_handle]->syncFrom($ref_translation);
				}
			}
		}
		
		/**
		 * Translation generator. Creates new Translations and sets Meta content and Data content if given.
		 *
		 * @param string $handle - Handle of the file
		 * @param string $storage_format (optional) - storage format to use for this translations
		 * 
		 * @return Translation
		 */
		public function addTranslation($handle, $storage_format = null) {
			// make sure $storage_format is supported. Fallback to default otherwise.
			$storage_format = array_key_exists($storage_format, $this->parent->getSupportedStorageFormats()) ? $storage_format : $this->parent->getStorageFormat();
			
			if( empty($this->translations[$handle]) ){
				$this->translations[$handle] = new Translation($this, $handle, $storage_format);
			}
			
			return $this->translations[$handle];
		}
		
		
		
		/**
		 * Discover existing files in translation folder.
		 */
		private function _discoverTranslations(){
			$structure = (array) General::listStructure( $this->getPath() );
			
			if (!empty($structure['filelist'])) {
				foreach ($structure['filelist'] as $filename) {
					
					// get information from filename
					// [storage_format][meta/data][handle]
					$bits = array_map('strrev', explode('.', strrev($filename), 3));
					
					if( $bits[1] == 'meta' ){
						$this->addTranslation($bits[2], $bits[0]);
					}
				}
			}
		}
	}
	