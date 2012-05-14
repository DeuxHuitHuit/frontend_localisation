<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once ('class.TFileMeta.php');
	require_once ('class.Translation.php');



	/**
	 * Takes care of a Translation Folder.
	 *
	 * @package Frontend Localisation
	 *
	 * @author  Vlad Ghita
	 *
	 */
	final class TFolder
	{

		/**
		 * Language code identifier
		 *
		 * @var string
		 */
		private $lang_code = '';

		/**
		 * Translations in this folder
		 *
		 * @var Translation[]
		 */
		private $translations = array();



		/**
		 * Creates a new Translation folder, discovering existing Translations.
		 *
		 * @param string $lang_code
		 */
		public function __construct($lang_code){
			$this->lang_code = $lang_code;

			$this->_discoverTranslations();
		}

		/**
		 * Discover existing files in translation folder.
		 * It loads the already existing Translations.
		 */
		private function _discoverTranslations(){
			$structure = (array)General::listStructure($this->getPath());

			if( !empty($structure['filelist']) ){
				foreach( $structure['filelist'] as $filepath ){

					$filename = basename($filepath);

					// get information from filename
					$bits = array_map('strrev', explode('.', strrev($filename), 3));

					if( $bits[1] == 'meta' ){
						// preload meta information
						$meta = new TFileMeta(null, $this->getPath().'/'.$filename);

						$this->addTranslation($bits[2], $meta);
					}
				}
			}
		}

		/**
		 * On exit, save all config files.
		 */
		public function __destruct(){
			foreach( $this->translations as $translation ){
				$translation->meta()->saveSettings();
			}
		}



		/**
		 * Get the language code
		 *
		 * @return string
		 */
		public function getLangCode(){
			return (string) $this->lang_code;
		}

		/**
		 * Return path to this Translation folder
		 *
		 * @return string
		 */
		public function getPath(){
			return (string) TManager::getPath().'/'.$this->lang_code;
		}

		/**
		 * Get all Translations in folder.
		 *
		 * @return array
		 */
		public function getTranslations(){
			return (array) $this->translations;
		}

		/**
		 * Get requested Translation.
		 *
		 * @param string $handle
		 *
		 * @return Translation|null
		 */
		public function getTranslation($handle){
			General::ensureType(array(
				'handle' => array('var' => $handle, 'type' => 'string')
			));

			if( !isset($this->translations[$handle]) ) return null;

			if( !$this->translations[$handle] instanceof Translation ) return null;

			return $this->translations[$handle];
		}



		/**
		 * Deletes a Translation.
		 *
		 * @param string $handle
		 *
		 * @return boolean
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

			foreach( $pages as $page ){
				$handle = $page['handle'];

				while( !empty($page['parent']) ){
					$page = $pages[$page['parent']];
					$handle = $page['handle'].'_'.$handle;
				}

				$handle = TManager::getPagePrefix().$handle;

				if( is_null($this->getTranslation($handle)) ){
					$this->addTranslation($handle, array('type' => 'page'));
				}

				$this->translations[$handle]->setName($handle);
			}
		}

		/**
		 * Update Translations against reference language Translations.
		 *
		 * @param array $ref_files - array of Translations representing reference_languages' files.
		 */
		public function updateTranslations(array $ref_files){
			if( !empty($ref_files) && is_array($ref_files) ){

				foreach( $ref_files as $ref_handle => $ref_translation ){
					$this->addTranslation($ref_handle);

					$this->translations[$ref_handle]->syncFrom($ref_translation);
				}
			}
		}

		/**
		 * Translation generator. Creates new Translations and sets Meta content if given.
		 *
		 * @param string $handle - Handle of the file
		 * @param mixed $meta    (optional) - supplies meta info about Translation
		 *		 - TFileMeta - an object containing the information
		 *          OR
		 *		 - Array(
		 *			 [storage_format] => '' (optional)
		 *			 [type] => '' (optional)
		 *		 )
		 *
		 * @throws Exception
		 *
		 * @return Translation
		 */
		public function addTranslation($handle, $meta = array()){
			if( is_null($this->getTranslation($handle)) ){

				// if Array, ensure some settings
				if( is_array($meta) ){
					if( !TManager::validateStorageFormat($meta['storage_format']) ){
						$meta['storage_format'] = TManager::getStorageFormat();
					}

					$meta['type'] = ($meta['type'] == 'page') ? $meta['type'] : '';

					$type = $meta['type'];
				}
				elseif( $meta instanceof TFileMeta ){
					$type = $meta->get('type');
				}
				else{
					throw new Exception('Meta information not valid. Supply an array or a TFileMeta object.');
				}

				TManager::loadTranslationClass($type);
				$class_name = 'Translation'.ucfirst($type);

				$this->translations[$handle] = new $class_name($this, $handle, $meta);
			}

			return $this->translations[$handle];
		}

		/**
		 * Change Translation handle to new value.
		 *
		 * @param string $old_handle
		 * @param string $new_handle
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function changeTranslationHandle($old_handle, $new_handle){
			$translation = $this->translations[$old_handle];

			if( !$translation->setHandle($new_handle) ){
				return false;
			}

			if( $translation instanceof TranslationPage ){
				$translation->setName();
			}

			$this->translations[$new_handle] = $translation;
			unset($this->translations[$old_handle]);

			return true;
		}

		/**
		 * Change Translation type to new $type.
		 *
		 * @param string $handle
		 * @param string $type
		 *
		 * @return Translation
		 */
		public function setTranslationType($handle, $type){
			$meta = $this->translations[$handle]->meta();
			$meta->set('type', $type);

			unset($this->translations[$handle]);

			return $this->addTranslation($handle, $meta);
		}
	}
