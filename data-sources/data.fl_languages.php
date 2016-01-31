<?php



	require_once(TOOLKIT . '/class.datasource.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLang.php');

	Class datasourcefl_languages extends Datasource {

		public function about() {
			return array(
				'name'         => 'FL: Languages',
				'author'       => array(
					'name'    => 'Xander Group',
					'email'   => 'symphonycms@xandergroup.ro',
					'website' => 'www.xanderadvertising.com'
				),
				'version'      => '1.0',
				'release-date' => '2011-12-15',
				'description'  => 'From Frontend Localisation extension. It supplies translation strings for current Page, as selected in Page settings.'
			);
		}

		public function allowEditorToParse() {
			return false;
		}

		public function execute(array &$param_pool = null) {
			$result = new XMLElement('fl-languages');

			$main_lang  = FLang::getMainLang();
			$crt_lc     = FLang::getLangCode();
			$lang_names = Languages::all()->listAll();
			$langs      = FLang::getLangs();

			$current_language_xml = new XMLElement('current-language', $lang_names[$crt_lc] ? $lang_names[$crt_lc]['name'] : $crt_lc);
			$current_language_xml->setAttribute('handle', $crt_lc);
			$current_language_xml->setAttribute('language', FLang::getLang());
			$current_language_xml->setAttribute('region', FLang::getReg());
			$result->appendChild($current_language_xml);

			$supported_languages_xml = new XMLElement('supported-languages');

			foreach ($langs as $lc) {
				$lang_xml = new XMLElement('item', $lang_names[$lc] ? $lang_names[$lc]['name'] : $lc);
				$lang_xml->setAttribute('handle', $lc);

				if ($lc === $main_lang) {
					$lang_xml->setAttribute('main', 'yes');
				}

				$supported_languages_xml->appendChild($lang_xml);
			}

			$result->appendChild($supported_languages_xml);

			return $result;
		}
	}
