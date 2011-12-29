<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once ('class.Translation.php');
	
	
	
	/**
	 * Management for Translation of a Symphony Page. It improves Translation's name management
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 */
	class TranslationPage extends Translation
	{
		
		public function setName($name = ''){
			$name = 'P';
			$title_field = 'title';
			
			// support for Page LHandles
			if( Symphony::ExtensionManager()->fetchStatus('page_lhandles') == EXTENSION_ENABLED ){
				$title_field = 'plh_t-'.$this->getFolder()->languageCode();
			}
			
			$pages = FLPageManager::instance()->listAll( array($title_field), 'handle' );
			
			$bits = explode('_', $this->handle);
			array_shift($bits);
			
			while( $bit = array_shift($bits) ){
				$name .= ' : '.$pages[$bit][$title_field];
			}
			
			return (boolean) parent::setName($name);
		}
		

	}
