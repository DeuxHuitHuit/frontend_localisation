<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once (EXTENSIONS . '/frontend_localisation/lib/class.TParser.php');
	
	
	
	/**
	 * Deals with data parsing for I18N Translations.
	 *
	 * @package i18n
	 *
	 * @author Vlad Ghita
	 */
	final class I18N_TParser extends TParser {
		
		/**
		 * @see TParser::asTArray()
		 */
		public function asTArray(Translation $translation){
			$contents = $translation->data()->getContent();
			if( empty($contents) ) return array();
			
			$result = array();
			
			// process here
			
			return (array) $result;
		}
		
		/**
		 * @see TParser::TArray2string()
		 */
		public function TArray2string(array $translations){
			$result = '';
			
			// process $translations
			
			return $result;
		}
	}
