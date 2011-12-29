<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(EXTENSIONS . '/language_redirect/lib/class.languageredirect.php');
	require_once 'class.FLDriver.php';
	
	
	
	final class FLDriverLanguageRedirect extends FLDriver
	{
		public function __construct(){
			$this->language_codes = (array) LanguageRedirect::instance()->getSupportedLanguageCodes();
			$this->all_languages = (array) LanguageRedirect::instance()->getAllLanguages();
		}
		
		
		
		public function getHandle(){
			return 'language_redirect';
		}
		
		public function languageCodes(){
			return (array) $this->language_codes;
		}
		
		public function referenceLanguage(){
			return (string) $this->language_codes[0];
		}
		
		public function languageCode(){
			return LanguageRedirect::instance()->getLanguageCode();
		}
		
		public function getSavedLanguages($context){
			$saved_languages = explode( ',', General::Sanitize($context['settings']['language_redirect']['language_codes']) );
			$saved_languages = LanguageRedirect::instance()->cleanLanguageCodes($saved_languages);
			
			return (array) $saved_languages;
		}
		
	}