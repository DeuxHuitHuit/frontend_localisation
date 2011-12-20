<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once ('class.TFileMeta.php');
	
	
	
	/**
	 * Management for a Translation. Provides access to meta information and business data.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 */
	class Translation
	{
		
		/**
		 * Instance of TFileMeta holding meta information for Translation.
		 *
		 * @var TFileMeta
		 */
		protected $tf_meta = null;
		
		/**
		 * Instance of TFileData holding business content for Translation.
		 *
		 * @var TFileData
		 */
		protected $tf_data = null;
		
		/**
		 * Instance of TFolder holding the Translation folder where this Translation resides.
		 *
		 * @var TFolder
		 */
		protected $parent = null;
		
		/**
		 * Unique handle identifier.
		 *
		 * @var string
		 */
		protected $handle = null;
		
		
		
		/**
		 * Creates a new Translation holding meta information and business data.
		 *
		 * @param TFolder $parent - parent Translation Folder
		 * @param string $handle - handle of the file
		 * @param string $storage_format - storage format to use when saving business data
		 */
		public function __construct(TFolder $t_folder, $handle, $storage_format){
			$this->parent = $t_folder;
			$this->handle = $handle;
			
			$this->_initialiseMeta($storage_format);
			$this->_initialiseData();
		}
		
		
		
		/**
		 * Getter for Translation parent folder.
		 *
		 * @see $parent
		 *
		 * @return TFolder
		 */
		public function getFolder(){
			return $this->parent;
		}
		
		/**
		 * Getter for Translation handle.
		 *
		 * @see $handle
		 *
		 * @return string
		 */
		public function getHandle(){
			return (string) $this->handle;
		}
		
		/**
		 * Setter for Translation handle. 
		 * It will only create a new file with old file contents.
		 * 
		 * @param string $handle - new handle
		 *
		 * @return boolean - true if success, false otherwise.
		 * 
		 * @see TFile::setFilename()
		 */
		public function setHandle($handle){
			if( !is_string($handle) && empty($handle) ) return false;
			
			// 1. Create new files
			$valid = $this->tf_meta->setFilename($handle);
			$valid = $this->tf_data->setFilename($handle) && $valid;
			
			if( !$valid ) return false;
			
			// 2. Change handle
			$this->handle = $handle;
			$this->_initialiseMeta($this->tf_meta->get('storage_format'));
			$this->_initialiseData();
			
			return true;
		}
		
		/**
		 * The recommended way to set the Name of translation.
		 * 
		 * @param string $name - new name
		 * 
		 * @return boolean - true if success, false otherwise.
		 */
		public function setName($name){
			return (boolean) $this->tf_meta->set('name', $name);
		}
		
		/**
		 * Return path to Translation.
		 *
		 * @return string
		 */
		public function getPath(){
			return (string) $this->parent->getPath();
		}
		
		
		
		/**
		 * Accessor for Translation meta information.
		 *
		 * @return TFileMeta
		 */
		public function meta(){
			return $this->tf_meta;
		}
		
		/**
		 * Accessor for Translation business data.
		 *
		 * @return TFileData
		 */
		public function data(){
			return $this->tf_data;
		}
		
		/**
		 * Delete Translation information.
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function delete(){
			$valid = General::deleteFile( $this->getPath() .'/'. $this->tf_meta->getFilename() );
			
			return (boolean) ( General::deleteFile( $this->getPath() .'/'. $this->tf_data->getFilename() ) && $valid );
		}
		
		/**
		 * Synchronize business data from $ref Translation.
		 * It only inserts missing items without their value.
		 *
		 * @param Translation $ref
		 *
		 * @return boolean - true if succes, false otherwise
		 */
		public function syncFrom(Translation $ref){
			$ref_trans = $ref->data()->getAsTArray();
			$this_trans = $this->tf_data->getAsTArray();
			
			$translations = array();
			
			foreach( $ref_trans as $context => $items ){
			
				if( array_key_exists($context, $this_trans) ){
					foreach( $items as $handle => $item ){
			
						if( array_key_exists($handle, $this_trans[$context]) ){
							$translations[$context][$handle] = array(
									'handle' => $item['handle'],
									'value' => $this_trans[$context][$handle]['value']
							);
						}
						else{
							$translations[$context][$handle] = array(
									'handle' => $handle,
									'value' => ''
							);
						}
					}
				}
			
				else{
					foreach( $items as $handle => $value ){
						$translations[$context][$handle] = array(
								'handle' => $handle,
								'value' => ''
						);
					}
				}
			}
			
			return (boolean) $this->tf_data->setFromTArray($translations);
		}
		
		
		
		/**
		 * Initialize Meta information.
		 *
		 * @param string $storage_format - desired storage format.
		 */
		private function _initialiseMeta($storage_format){
			$this->tf_meta = new TFileMeta($this, $storage_format);
		}
		
		/**
		 * Initialize business data.
		 */
		private function _initialiseData(){
			$this->_loadStorageClass('TFileData');
			
			$data_class_name = strtoupper( $this->tf_meta->get('storage_format') ).'_TFileData';
			$this->tf_data = new $data_class_name($this);
		}
		
		/**
		 * Loads a Translation resource identified by `$class` abstract class name.
		 *
		 * 		- If class file doesn't exist, `Exception` is thrown.
		 * 		- If class doesn't exist in file, `Exception` is thrown.
		 *
		 * @param string $class - abstract base class name.
		 * 		e.g. $class=='TParser' and $storage_format=='xml' => required class is `XML_TParser`
		 *
		 * @throws Exception - will be catched by Symphony's default error handler.
		 */
		private function _loadStorageClass($class){
			$file_name = EXTENSIONS . '/frontend_localisation/lib/'. $this->tf_meta->get('storage_format') .'/class.'. $class .'.php';
		
			// $class file must exist
			if( !is_file($file_name) ){
				throw new Exception("File `{$file_name}` doesn't exist. Translation needs it there.`");
			}
		
			require_once ($file_name);
			$class_name = strtoupper($this->tf_meta->get('storage_format')).'_'.$class;
		
			if( !class_exists($class_name) ){
				throw new Exception("Class `{$class_name}` could not be found in file {$file_name}.`");
			}
		
			return true;
		}
	}
	