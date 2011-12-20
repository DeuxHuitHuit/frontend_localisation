<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FrontendLanguage.php');

	Class datasourcefl_languages extends Datasource{

		public function about(){
			return array(
				'name' => 'FL: Languages',
				'author' => array(
					'name' => 'Xander Group',
					'email' => 'symphonycms@xandergroup.ro',
					'website' => 'www.xanderadvertising.com'
				),
				'version' => '1.0',
				'release-date' => '2011-12-15',
				'description' => 'From Frontend Localisation extension. It supplies translation strings for current Page, as selected in Page settings.'
			);
		}

		public function allowEditorToParse(){
			return false;
		}

		public function grab(&$param_pool=NULL){
			$result = new XMLElement('fl-languages');
			
			$current_language_code = FrontendLanguage::instance()->getLanguageCode();
			$all_languages = FrontendLanguage::instance()->allLanguages();
			$supported_language_codes = FrontendLanguage::instance()->languageCodes();
			
			$current_language_xml = new XMLElement('current-language', $all_languages[$current_language_code] ? $all_languages[$current_language_code] : $current_language_code);
			$current_language_xml->setAttribute('handle', $current_language_code);
			$result->appendChild($current_language_xml);
			
			$supported_languages_xml = new XMLElement('supported-languages');
			foreach($supported_language_codes as $language) {
				$language_code = new XMLElement('item', $all_languages[$language] ? $all_languages[$language] : $language);
				$language_code->setAttribute('handle', $language);
				$supported_languages_xml->appendChild($language_code);
			}
			$result->appendChild($supported_languages_xml);
			
			return $result;
		}
	}
