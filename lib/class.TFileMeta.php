<?php
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once ('class.TFile.php');
	
	
	
	/**
	 * Deals with meta information for a Translation.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 */
	final class TFileMeta extends TFile
	{
		
		/**
		 * Settings of translation.
		 *
		 * @var array
		 */
		private $meta = array();
		
		public function __construct(Translation $translation, $storage_format){
			parent::__construct($translation);
	
			$this->type = 'meta';
			$this->extension = 'xml';
			
			if( !$this->_loadSettings() ){
				// somehow flag that contents from file were scrambled
			}
			$this->set('storage_format', $storage_format);
			$this->meetRequirements();
		}
		
		
		
		/**
		 * Return requested setting. If 'meta' is requested all settings are returned.
		 *
		 * @param string $setting (optional)
		 * @param string $child (optional)
		 *
		 * @return mixed
		 * 		- string if single value
		 * 		- array if there are nested children
		 * 		- null if setting not found or other errors
		 */
		public function get($setting = null, $child = null){
			if(!$child && !$setting) return $this->meta;
			
			if($child){
				return (isset($this->meta[$setting][$child]) ? $this->meta[$setting][$child] : null);
			}
			
			return (isset($this->meta[$setting]) ? $this->meta[$setting] : null);
		}
		
		/**
		 * Sets the value for requested setting.
		 *
		 * @param string $setting - setting name
		 * @param mixed $value - value of setting.
		 * @param string $child (optional) - child setting. If set, $value will be saved here.
		 *
		 * @return boolean - true if succesfull, false otherwise
		 */
		public function set($setting, $value, $child = null){
			if( !is_string($setting) || empty($setting) ) return false;
			if( ($child != null) && !is_string($child) ) return false;
			
			$value = General::sanitize($value);
			
			if( !empty($child) ){
				$this->meta[$setting][$child] = $value;
			}
			else{
				$this->meta[$setting] = $value;
			}
			
			return true;
		}
		
		/**
		 * Make sure the meta file has the minimum information required (name, storage_format, language).
		 *
		 * @return (boolean) - true if succesfull, false otherwise
		 */
		public function meetRequirements(){
			if( !isset($this->meta['name']) ){
				$this->set('name', $this->parent->getHandle());
			}
			
			if( !isset($this->meta['language']) ){
				$all_languages = FLang::instance()->ld()->allLanguages();
				$language_code = $this->parent->getFolder()->languageCode();
				
				$this->set('language', array(
						'name' => $all_languages[$language_code],
						'language_code' => $language_code,
						'handle' => General::createHandle( $all_languages[$language_code] )
				));
			}
			
			if( !isset($this->meta['storage_format']) ){
				$this->set('storage_format', $this->parent->getFolder()->getManager()->getStorageFormat());
			}
			
			if( !isset($this->meta['type']) ){
				$this->set('type', 'normal');
			}
			
			// filter duplicates
			$this->meta = array_unique($this->meta);
		}
		
		/**
		 * Set settings from parameter.
		 *
		 * @param mixed $content - new content
		 * 		- array = an array of $key => $value pairs to be stored.
		 * 		If it contains only one element called 'meta' => array(...), it's children will be stored.
		 *
		 * 		- string = containing new information
		 *
		 * @return boolean - true on success, false otherwise
		 */
		public function setContent($content){
			if( is_array($content) ){
				if( (count($content) == 1) && array_key_exists('meta', $content) && is_array($content['meta']) ){
					$this->meta = $content['meta'];
				}
				else{
					foreach( $content as $key => $value ){
						$this->set($key, $value);
					}
				}
		
				$this->meetRequirements();
		
				return (boolean) $this->saveSettings();
			}
		
			$errors = array();
			General::validateXML($content, $errors, false);
		
			if( !empty($errors) ){
				// log the error somewhere
				echo 'Tried to save contents in META file.<br />';
				die(var_dump($errors));
		
				return false;
			}
		
			return (boolean) parent::setContent($content);
		}
		
		/**
		 * Save settings to file.
		 */
		public function saveSettings(){
			$this->meetRequirements();
			
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;
			$doc->substituteEntities = true;
		
			$meta = $doc->createElement('meta');
		
			foreach( $this->meta as $setting => $value ){
				$meta->appendChild( $this->_insertSetting($doc, $setting, $value) );
			}
		
			$doc->appendChild($meta);
		
			return (boolean) $this->setContent( $doc->saveXML() );
		}
		
		
		
		/**
		 * Load settings from file.
		 */
		private function _loadSettings(){
			$file_contents = $this->getContent();
			
			if( $file_contents == null) return false;
		
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->loadXML( $file_contents );
		
			$xPath = new DOMXPath($doc);
		
			$this->meta = array();
		
			foreach( $xPath->query('/meta')->item(0)->childNodes as $setting ){
			
				if( $setting instanceof DOMElement ){
					if( !array_key_exists($setting->nodeName, $this->meta) )
						$this->meta[$setting->nodeName] = $this->_extractSetting($setting);
				}
			}
		
			return true;
		}
		
		/**
		 * Extract data for requested setting.
		 *
		 * @param DOMElement $setting - setting name
		 *
		 * @return mixed
		 * 		- string with node value if $dom_value has no child settings
		 * 		- array with child settings otherwise
		 */
		private function _extractSetting($setting){
			$result = array();
			
			foreach( $setting->childNodes as $child ){
				
				if( $child instanceof DOMElement ){
					$result[$child->nodeName] = $this->_extractSetting($child);
				}
			}
			
			// return flat value if no child settings
			if( empty($result) ) return (string) $setting->nodeValue;
			
			// return all settings
			return (array) $result;
		}
		
		/**
		 * Create a DOMElement from setting name and value. If value is an array, it will be stored appropriately.
		 *
		 * @param DOMDocument $doc - parent DOMDocument
		 * @param string $name - setting name
		 * @param mixed $value - value of setting. String or array of settings.
		 *
		 * @return DOMElement - the resulting node;
		 */
		private function _insertSetting($doc, $name, $value){
			$setting = $doc->createElement($name);
			
			if( is_array($value) ){
				foreach( $value as $k => $v ){
					$setting->appendChild( $this->_insertSetting($doc, $k, $v) );
				}
			}
			else{
				$setting->nodeValue = htmlspecialchars($value);
			}
			
			return $setting;
		}
	}
	