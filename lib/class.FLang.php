<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	/**
	 * Deals with management of multiple implementations for frontend language switchers.
	 */
	final class FLang implements Singleton
	{
		private static $instance;
		
		/**
		 * Supported language drivers.
		 * 
		 * @var array
		 *   - pairs of $driver_name => $handle.
		 *   - $driver_name comes from driver class namem "FLDriver{$driver_name}"
		 *   - $handle is name of extension folder in "/extensions"
		 */
		private $supported_drivers = array(
			'language_redirect' => 'LanguageRedirect'
		);
		
		/**
		 * Available language drivers. A language driver is available if the extension is enabled and LDriver$class exists.
		 * 
		 * @var array
		 */
		private $available_drivers = array();
		
		/**
		 * Current language driver
		 *
		 * @var FLDriver
		 */
		private $fl_driver;
		
		
		
		private function __construct(){
			foreach( $this->supported_drivers as $handle => $class ){
				if( Symphony::ExtensionManager()->fetchStatus($handle) == EXTENSION_ENABLED && $this->_validateDriver($class) ){
					
					$driver_info = Symphony::ExtensionManager()->about($handle);
					
					$this->available_drivers[$handle] = $driver_info['name'] .' v.'. $driver_info['version'];
				}
			}
			
			if( empty($this->available_drivers) ){
				$message = '<code>%1$s</code>: At least one language driver must be installed. Supported language drivers are:';
				$message_params = array(FRONTEND_LOCALISATION_NAME);
				
				$i = 2;
				
				foreach( $this->supported_drivers as $class ){
					$message .= ' <code>%'.$i++.'$s</code>,';
					$message_params[] = $class;
				}
				$message = trim($message, ',').'.';
				
				Administration::instance()->Page->pageAlert(__($message, $message_params), Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
				
				return null;
			}
			else{
				$handle = Symphony::Configuration()->get('language_driver','frontend_localisation');
				
				if( empty($this->available_drivers[$handle]) ){
					$driver_name = null;
				}
				else{
					$driver_name = $this->supported_drivers[$handle];
				}
				
				$this->_newFLDriver( $driver_name );
			}
		}
		
		/**
		 * This function returns an instance of the FLang class.
		 * It is the only way to create a new FLang, as
		 * it implements the Singleton interface
		 *
		 * @return FLang
		 */
		public static function instance(){
			if (!self::$instance instanceof FLang) {
				self::$instance = new FLang();
			}
			
			return self::$instance;
		}
		
		
		
		/**
		 * Returns supported frontend language drivers
		 *
		 * @return array
		 */
		public function getSupportedFLDrivers(){
			return $this->supported_drivers;
		}
		
		/**
		 * Returns available frontend language drivers
		 *
		 * @return array
		 */
		public function getAvailableDrivers(){
			return $this->available_drivers;
		}
		
		/**
		 * Returns language driver class from given $handle.
		 * 
		 * @param string $handle (optional) - driver handle
		 * 
		 * @return string - first element of $supported_drivers array
		 */
		public function getFLDriverClass($handle = null){
			if( empty($this->available_drivers[$handle]) ){
				reset($this->available_drivers);
				$handle = key($this->available_drivers);
			}
			
			return (string) $this->supported_drivers[$handle];
		}
		
		/**
		 * Set FL Driver from $handle.
		 *
		 * @param string $handle - Handle of new language driver
		 */
		public function setFLDriver($handle){
			if( empty($this->available_drivers[$handle]) ) return false;
			
			$this->_newFLDriver( $this->supported_fl_drivers[$handle] );
			
			return true;
		}
		
		/**
		 * Accessor for current Language Driver.
		 *
		 * @return FLDriver
		 */
		public function ld(){
			return $this->fl_driver;
		}
		
		/**
		 * Returns reference language code of current driver.
		 *
		 * @return string
		 */
		public function referenceLanguage(){
			$reference_language = Symphony::Configuration()->get('reference_language', 'frontend_localisation');
			
			return (string) (empty($reference_language) ? $this->fl_driver->referenceLanguage() : $reference_language);
		}
		
		
		
		/**
		 * Gets a new instance of FLDriver
		 *
		 * @param string $driver_name (optional) - Contains driver name
		 */
		private function _newFLDriver($driver_name = null){
			if ( empty($driver_name) ) {
				$driver_name = $this->getFLDriverClass();
			}
			
			$driver_class = 'FLDriver' . $driver_name;
			
			$this->fl_driver = new $driver_class();
		}
		
		/**
		 * Validate a fl_driver; driver file exists and driver class exists in that file
		 *
		 * @param string $driver_name
		 *
		 * @return boolean - true if succesfull, false otherwise
		 */
		private function _validateDriver($driver_name){
			$class = 'FLDriver'.$driver_name;
			$filename = EXTENSIONS . '/frontend_localisation/lib/class.'.$class.'.php';
			
			// Driver file must exist
			if( !is_file($filename) ) return false;
			
			require_once $filename;
				
			// Driver class must exist
			if( !class_exists($class) ) return false;
			
			return true;
		}
		
	}