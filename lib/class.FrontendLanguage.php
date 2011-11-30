<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/**
	 * Manager that deals with existing multiple implementations of frontend language switchers;
	 * It provides some neat convenince methods to operate with frontend languages.
	 */
	final class FrontendLanguage implements Singleton
	{
		private static $instance;
		
		/**
		 * Supported language drivers.
		 * @var array
		 *   - pairs of $driver_name => $driver_handle.
		 *   - $driver_name comes from driver class namem "LanguageDriver{$driver_name}"
		 *   - $driver_handle is name of extension folder in "/extensions"
		 */
		private $supported_language_drivers = array(
			'LanguageRedirect' => 'language_redirect',
		);
		
		/**
		 * Available language drivers. A language driver is available if his extension is enabled and 
		 * @var array
		 */
		private $available_language_drivers = array();
		
		/**
		 * Current language driver
		 * 
		 * @var LanguageDriver
		 */
		private $language_driver;
		
		
		
		private function __construct(){
			foreach( $this->supported_language_drivers as $name => $handle ){
				if( Symphony::ExtensionManager()->fetchStatus($handle) == EXTENSION_ENABLED 
				    && $this->_validateDriver($name) ){
					$this->available_language_drivers[] = $name;
				}
			}
			
			if( empty($this->available_language_drivers) ){
				$message = '<code>%1$s</code>: At least one language driver must be installed. Supported language drivers are:';
				$message_params = array(FRONTEND_LOCALISATION_NAME);
				
				foreach( $this->supported_language_drivers as $name => $handle ){
					$message .= ' <code>%s</code>,';
					$message_params[] = $name;
				}
				$message = trim($message, ',').'.'.
				
				Administration::instance()->Page->pageAlert(__($message, $message_params), Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
				
				return null;
			}
			else{
				$this->_newLanguageDriver( Symphony::Configuration()->get('language_driver','frontend_localisation') );
			}
		}
		
		/**
		 * This function returns an instance of the FrontendLanguage class.
		 * It is the only way to create a new FrontendLanguage, as
		 * it implements the Singleton interface
		 *
		 * @return FrontendLanguage
		 */
		public static function instance(){
			if (!self::$instance instanceof FrontendLanguage) {
				self::$instance = new FrontendLanguage();
			}
			
			return self::$instance;
		}
		
		
		
		/**
		 * Returns supported frontend language drivers
		 * 
		 * @return array
		 */
		public function getSupportedLanguageDrivers(){
			return $this->supported_language_drivers;
		}
		
		/**
		 * Returns available frontend language drivers
		 * 
		 * @return array
		 */
		public function getAvailableLanguageDrivers(){
			return $this->available_language_drivers;
		}
		
		/**
		 * Returns default language driver.
		 * 
		 * @return string - first element of $supported_language_drivers array
		 */
		public function getDefaultLanguageDriverName(){
			return (string) $this->available_language_drivers[0];
		}
		
		/**
		 * Returns current language driver
		 * 
		 * @return LanguageDriver
		 */
		public function getLanguageDriver(){
			return $this->language_driver;
		}
		
		/**
		 * Changes Language Driver to give parameter
		 * 
		 * @param string $driver_name - Name of new language driver
		 */
		public function setLanguageDriver($driver_name){
			if( Symphony::ExtensionManager()->fetchStatus( $this->supported_language_drivers[$driver_name] ) == EXTENSION_ENABLED ){
				if( $this->_validateDriver($driver_name) ){
					$this->_newLanguageDriver($driver_name);
				}
			}
		}
		
		/**
		 * Returns current frontend language code.
		 * 
		 * @return string
		 */
		public function getLangaugeCode(){
			return (string) $this->language_driver->getLanguageCode();
		}
		
		/**
		 * Returns supported language codes.
		 * 
		 * @return array
		 */
		public function languageCodes(){
			return (array) $this->language_driver->getLanguageCodes();
		}
		
		/**
		 * Returns all languages
		 * 
		 * @return array - Defaults to inner $allLanguages
		 */
		public function allLanguages(){
			return (array) $this->language_driver->getAllLanguages();
		}
		
		/**
		 * Returns all current driver details.
		 * 
		 * @return array
		 */
		public function driverDetails(){
			return (array) $this->language_driver->getDriverDetails();
		}

		/**
		 * Returns name of current driver.
		 * 
		 * @return string
		 */
		public function driverName(){
			return (string) $this->language_driver->getName();
		}
		
		/**
		 * Returns handle of current driver.
		 * 
		 * @return string
		 */
		public function driverHandle(){
			return (string) $this->language_driver->getHandle();
		}
		
		/**
		 * Returns if current driver is enabled or not.
		 * 
		 * @return boolean - true if EXTENSION_ENABLED, false otherwise
		 */
		public function driverStatus(){
			return (boolean) $this->language_driver->getDriverStatus();
		}
		
		/**
		 * Returns reference language code of current driver.
		 * 
		 * @return string
		 */
		public function referenceLanguage(){
			$reference_language = (string) Symphony::Configuration()->get('reference_language', 'frontend_localisation');
			
			return (string) (empty($reference_language) ? $this->language_driver->getReferenceLanguage() : $reference_language);
		}
		
		/**
		 * Returns newly entered language codes on preferences page set by current driver.
		 * 
		 * @param array $context - entire form data from Preferences page
		 * @return array - new laguage codes
		 */
		public function savedLanguages($context){
			return (array) $this->language_driver->getSavedLanguages($context);
		}		

		
		
		/**
		 * Gets a new instance of a LanguageDriver
		 * 
		 * @param string $driver_name (optional) - Contains driver name
		 */
		private function _newLanguageDriver($driver_name = null){
			if ( empty($driver_name) ) {
				$driver_name = $this->getDefaultLanguageDriverName();
			}
			
			$driver_class = 'LanguageDriver' . $driver_name;
			require_once EXTENSIONS . '/frontend_localisation/lib/class.'.$driver_class.'.php';
			
			$this->language_driver = new $driver_class();
		}
		
		/**
		 * Validate a language_driver; driver file exists and driver class exists in that file
		 * 
		 * @param string $driver_name
		 * 
		 * @return boolean - true if succesfull, false otherwise
		 */
		private function _validateDriver($driver_name){
			$driver_class = 'LanguageDriver' . $driver_name;
			$driver_file = EXTENSIONS . '/frontend_localisation/lib/class.'.$driver_class.'.php';
			
			// Driver file must exist
			if (file_exists($driver_file)) {
				require_once $driver_file;
				
				// Driver class must exist
				if (!class_exists($driver_class)) {
					$message = __('<code>%1$s</code>: Driver class <code>%2$s</code> doesn\'t exist in file <code>%3$s</code>.', array(FRONTEND_LOCALISATION_NAME, $driver_class, $driver_file));
				
					Administration::instance()->Page->pageAlert($message, Alert::ERROR);
					Symphony::$Log->pushToLog($message, E_NOTICE, true);
					
					return false;
				}
			}
			else {
				$message = __('<code>%1$s</code>: File <code>%2$s</code> doesn\'t exist.', array(FRONTEND_LOCALISATION_NAME, $driver_file));
				
				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
				
				return false;
			}
			
			return true;
		}
		
	}