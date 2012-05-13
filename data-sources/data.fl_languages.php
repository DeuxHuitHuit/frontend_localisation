<?php

	require_once(TOOLKIT.'/class.datasource.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLang.php');

	Class datasourcefl_languages extends Datasource
	{

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

		public function grab(&$param_pool = NULL){
			$result = new XMLElement('fl-languages');

			$main_lang = FLang::instance()->getMainLang();
			$lang_code = FLang::instance()->getLangCode();
			$all_languages = FLang::instance()->getAllLangs();
			$langs = FLang::instance()->getLangs();

			$current_language_xml = new XMLElement('current-language', $all_languages[$lang_code] ? $all_languages[$lang_code] : $lang_code);
			$current_language_xml->setAttribute('handle', $lang_code);
			$result->appendChild($current_language_xml);

			$supported_languages_xml = new XMLElement('supported-languages');

			foreach( $langs as $lc ){
				$lang_xml = new XMLElement('item', $all_languages[$lc] ? $all_languages[$lc] : $lc);
				$lang_xml->setAttribute('handle', $lc);

				if( $lc === $main_lang ){
					$lang_xml->setAttribute('main', 'yes');
				}

				$supported_languages_xml->appendChild($lang_xml);
			}

			$result->appendChild($supported_languages_xml);

			return $result;
		}
	}
