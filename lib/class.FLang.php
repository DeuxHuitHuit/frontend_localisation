<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	/**
	 * Provides Frontend language information
	 */
	final class FLang implements Singleton
	{

		/*------------------------------------------------------------------------------------------------*/
		/*  Properties  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Singleton instance
		 *
		 * @var FLang
		 */
		private static $_instance;

		/**
		 * Current language
		 *
		 * @var string
		 */
		private $_lang = '';

		/**
		 * Current region
		 *
		 * @var string
		 */
		private $_reg = '';

		/**
		 * Main language
		 *
		 * @var string
		 */
		private $_main_lang = '';

		/**
		 * Stored language codes
		 *
		 * @var array
		 */
		private $_langs = array();

		// I don't know those languages, so if You know for sure that browser uses different code,
		// or that native name should be different, please let me know about that :).
		// It would also be great, if whole string could be in native form, including name of country.
		private $_all_langs = array( // [English name]
			'ab' => 'аҧсуа бызшәа', // Abkhazian
			'af' => 'Afrikaans', // Afrikaans
			'sq' => 'shqip', // Albanian
			'am' => 'አማርኛ', // Amharic
			'ar-dz' => 'العربية (Algeria)', // Arabic
			'ar-bh' => 'العربية (Bahrain)', // Arabic
			'ar-eg' => 'العربية (Egypt)', // Arabic
			'ar-iq' => 'العربية (Iraq)', // Arabic
			'ar-jo' => 'العربية (Jordan)', // Arabic
			'ar-kw' => 'العربية (Kuwait)', // Arabic
			'ar-lb' => 'العربية (Lebanon)', // Arabic
			'ar-ly' => 'العربية (Libya)', // Arabic
			'ar-ma' => 'العربية (Morocco)', // Arabic
			'ar-om' => 'العربية (Oman)', // Arabic
			'ar-qa' => 'العربية (Qatar)', // Arabic
			'ar-sa' => 'العربية (Saudi Arabia)', // Arabic
			'ar-sy' => 'العربية (Syria)', // Arabic
			'ar-tn' => 'العربية (Tunisia)', // Arabic
			'ar-ae' => 'العربية (U.A.E.)', // Arabic
			'ar-ye' => 'العربية (Yemen)', // Arabic
			'ar' => 'العربية', // Arabic
			'hy' => 'Հայերեն', // Armenian
			'as' => 'অসমীয়া', // Assamese
			'az' => 'azərbaycan', // Azeri
			'eu' => 'euskera', // Basque
			'be' => 'Беларуская', // Belarusian
			'bn' => 'বাংলা', // Bengali
			'bg' => 'Български', // Bulgarian
			'ca' => 'Català', // Catalan
			'zh-cn' => '简体中文 (China)', // Chinese simplified script
			'zh-hk' => '繁體中文 (Hong Kong SAR)', // Chinese traditional script
			'zh-mo' => '繁體中文 (Macau SAR)', // Chinese traditional script
			'zh-sg' => '简体中文 (Singapore)', // Chinese simplified script
			'zh-tw' => '繁體中文 (Taiwan)', // Chinese traditional script
			'zh' => '中文', // Chinese
			'hr' => 'Hrvatski', // Croatian
			'cs' => 'čeština', // Czech
			'da' => 'Dansk', // Danish
			'dv' => 'ދިވެހި', // Divehi
			'nl-be' => 'Nederlands (Belgium)', // Dutch
			'nl' => 'Nederlands (Netherlands)', // Dutch
			'en-au' => 'English (Australia)', // English
			'en-bz' => 'English (Belize)', // English
			'en-ca' => 'English (Canada)', // English
			'en-ie' => 'English (Ireland)', // English
			'en-jm' => 'English (Jamaica)', // English
			'en-nz' => 'English (New Zealand)', // English
			'en-ph' => 'English (Philippines)', // English
			'en-za' => 'English (South Africa)', // English
			'en-tt' => 'English (Trinidad)', // English
			'en-gb' => 'English (United Kingdom)', // English
			'en-us' => 'English (United States)', // English
			'en-zw' => 'English (Zimbabwe)', // English
			'en' => 'English', // English
			'ee' => 'Ɛʋɛ', // Ewe
			'et' => 'Eesti', // Estonian
			'fo' => 'føroyskt', // Faeroese
			'fa' => 'فارسی', // Farsi
			'fi' => 'suomi', // Finnish
			'fr-be' => 'Français (Belgium)', // French (Belgium)
			'fr-ca' => 'Français canadien', // French (Canada)
			'fr-lu' => 'Français (Luxembourg)', // French
			'fr-mc' => 'Français (Monaco)', // French
			'fr-ch' => 'Français (Switzerland)', // French
			'fr' => 'Français', // French
			'ff' => 'Fulfulde, Pulaar, Pular', // Fula, Fulah, Fulani
			'gl' => 'Galego', // Galician
			'gd' => 'Gàidhlig', // Gaelic (Scottish)
			'ga' => 'Gaeilge', // Gaelic (Irish)
			'gv' => 'Gaelg', // Gaelic (Manx) (Isle of Man)
			'ka' => 'ქართული ენა', // Georgian
			'de-at' => 'Deutsch (Austria)', // German
			'de-li' => 'Deutsch (Liechtenstein)', // German
			'de-lu' => 'Deutsch (Luxembourg)', // German
			'de-ch' => 'Deutsch (Switzerland)', // German
			'de' => 'Deutsch', // German
			'el' => 'Ελληνικά', // Greek
			'gu' => 'ગુજરાતી', // Gujarati
			'ha' => 'هَوْسَ', // Hausa
			'he' => 'עברית', // Hebrew
			'hi' => 'हिंदी', // Hindi
			'hu' => 'Magyar', // Hungarian
			'is' => 'Íslenska', // Icelandic
			'id' => 'Bahasa Indonesia', // Indonesian
			'it-ch' => 'italiano (Switzerland)', // Italian
			'it' => 'italiano', // Italian
			'ja' => '日本語', // Japanese
			'kn' => 'ಕನ್ನಡ', // Kannada
			'kk' => 'Қазақ', // Kazakh
			'rw' => 'Kinyarwanda', // Kinyarwanda
			'kok' => 'कोंकणी', // Konkani
			'ko' => '한국어/조선말', // Korean
			'kz' => 'Кыргыз', // Kyrgyz
			'lv' => 'Latviešu', // Latvian
			'lt' => 'Lietuviškai', // Lithuanian
			'luo' => 'Dholuo', // Luo
			'ms' => 'Bahasa Melayu', // Malay
			'mk' => 'Македонски', // Macedonian
			'ml' => 'മലയാളം', // Malayalam
			'mt' => 'Malti', // Maltese
			'mr' => 'मराठी', // Marathi
			'mn' => 'Монгол', // Mongolian  (Cyrillic)
			'ne' => 'नेपाली', // Nepali
			'nb-no' => 'Norsk bokmål', // Norwegian Bokmål
			'nb' => 'Norsk bokmål', // Norwegian Bokmål
			'nn-no' => 'Norsk nynorsk', // Norwegian Nynorsk
			'nn' => 'Norsk nynorsk', // Norwegian Nynorsk
			'no' => 'Norsk', // Norwegian
			'or' => 'ଓଡ଼ିଆ', // Oriya
			'ps' => 'پښتو', // Pashto
			'pl' => 'polski', // Polish
			'pt-br' => 'português brasileiro', // Portuguese (Brasil)
			'pt' => 'português', // Portuguese
			'pa' => 'پنجابی/ਪੰਜਾਬੀ', // Punjabi
			'qu' => 'Runa Simi/Kichwa', // Quechua
			'rm' => 'Romansch', // Rhaeto-Romanic
			'ro-md' => 'Română (Moldova)', // Romanian
			'ro' => 'Română', // Romanian
			'rn' => 'kiRundi', // Rundi
			'ru-md' => 'Pyccĸий (Moldova)', // Russian
			'ru' => 'Pyccĸий', // Russian
			'sg' => 'yângâ tî sängö', // Sango
			'sa' => 'संस्कृतम्', // Sanskrit
			'sc' => 'sardu', // Sardinian
			'sr' => 'Srpski/српски', // Serbian
			'sn' => 'chiShona', // Shona
			'ii' => 'ꆇꉙ', // Sichuan Yi
			'si' => 'සිංහල', // Sinhalese, Sinhala
			'sk' => 'Slovenčina', // Slovak
			'ls' => 'Slovenščina', // Slovenian
			'so' => 'Soomaaliga/af Soomaali', // Somali
			'st' => 'Sesotho', // Sotho, Sutu
			'es-ar' => 'Español (Argentina)', // Spanish
			'es-bo' => 'Español (Bolivia)', // Spanish
			'es-cl' => 'Español (Chile)', // Spanish
			'es-co' => 'Español (Colombia)', // Spanish
			'es-cr' => 'Español (Costa Rica)', // Spanish
			'es-do' => 'Español (Dominican Republic)', // Spanish
			'es-ec' => 'Español (Ecuador)', // Spanish
			'es-sv' => 'Español (El Salvador)', // Spanish
			'es-gt' => 'Español (Guatemala)', // Spanish
			'es-hn' => 'Español (Honduras)', // Spanish
			'es-mx' => 'Español (Mexico)', // Spanish
			'es-ni' => 'Español (Nicaragua)', // Spanish
			'es-pa' => 'Español (Panama)', // Spanish
			'es-py' => 'Español (Paraguay)', // Spanish
			'es-pe' => 'Español (Peru)', // Spanish
			'es-pr' => 'Español (Puerto Rico)', // Spanish
			'es-us' => 'Español (United States)', // Spanish
			'es-uy' => 'Español (Uruguay)', // Spanish
			'es-ve' => 'Español (Venezuela)', // Spanish
			'es' => 'Español', // Spanish
			'sw' => 'kiswahili', // Swahili
			'sv-fi' => 'svenska (Finland)', // Swedish
			'sv' => 'svenska', // Swedish
			'syr' => 'ܣܘܪܝܝܐ', // Syriac
			'ta' => 'தமிழ்', // Tamil
			'tt' => 'татарча/تاتارچا', // Tatar
			'te' => 'తెలుగు', // Telugu
			'th' => 'ภาษาไทย', // Thai
			'ti' => 'ትግርኛ', // Tigrinya
			'ts' => 'Xitsonga', // Tsonga
			'tn' => 'Setswana', // Tswana
			'tr' => 'Türkçe', // Turkish
			'tk' => 'Түркмен', // Turkmen
			'ug' => 'ئۇيغۇرچە‎/Uyƣurqə/Уйғурчә', // Uighur, Uyghur
			'uk' => 'Українська', // Ukrainian
			'ur' => 'اردو', // Urdu
			'uz' => 'o\'zbek', // Uzbek
			've' => 'Tshivenḓa', // Venda
			'vi' => 'Tiếng Việt', // Vietnamese
			'wa' => 'walon', // Waloon
			'cy' => 'Cymraeg', // Welsh
			'wo' => 'Wolof', // Wolof
			'xh' => 'isiXhosa', // Xhosa
			'yi' => 'ייִדיש', // Yiddish
			'yo' => 'Yorùbá', // Yoruba
			'zu' => 'isiZulu', // Zulu
		);

		/**
		 * Switch to write config or not
		 *
		 * @var bool
		 */
		private $_write_config = false;



		/*------------------------------------------------------------------------------------------------*/
		/*  Initialisation  */
		/*------------------------------------------------------------------------------------------------*/

		private function __construct(){
			// initialize Language codes
			$langs = Symphony::Configuration()->get('langs', FL_GROUP);
			$this->setLangs(is_null($langs) ? '' : $langs, false);

			// initialize Main language
			$main_lang = Symphony::Configuration()->get('main_lang', FL_GROUP);
			$this->setMainLang(is_null($main_lang) ? '' : $main_lang, false);

			// read current language
			$language = General::sanitize((string)$_REQUEST['fl-language']);
			$region = General::sanitize((string)$_REQUEST['fl-region']);

			// set language code
			if( false === $this->setLangCode($language, $region) ){

				// language code is not supported, fallback to main lang
				if( false === $this->setLangCode($this->_main_lang) ){
					// do something usefull here if no lang is set ...
				}
			}
		}

		public function __destruct(){
			if( $this->_write_config === true ){
				Symphony::Configuration()->write();
			}
		}

		public static function instance(){
			if( !self::$_instance instanceof FLang ){
				self::$_instance = new self();
			}

			return self::$_instance;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Getters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Get language code
		 *
		 * @return string
		 */
		public function getLangCode(){
			return $this->buildLanguageCode($this->_lang, $this->_reg);
		}

		/**
		 * Get language
		 *
		 * @return string
		 */
		public function getLang(){
			return $this->_lang;
		}

		/**
		 * Get region
		 *
		 * @return string
		 */
		public function getReg(){
			return $this->_reg;
		}

		/**
		 * Get main language. Defaults to first language
		 *
		 * @return string
		 */
		public function getMainLang(){
			return $this->_main_lang;
		}

		/**
		 * Get supported language codes.
		 *
		 * @return array
		 */
		public function getLangs(){
			return $this->_langs;
		}

		/**
		 * Get all languages
		 *
		 * @return array
		 */
		public function getAllLangs(){
			return $this->_all_langs;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Setters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Set language code
		 *
		 * @param string $language - if $language contains a dash '-', it will be treated as a lang_code
		 * @param string $region   (optional)
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function setLangCode($language, $region = ''){
			General::ensureType(array(
				'language' => array('var' => $language, 'type' => 'string'),
				'region' => array('var' => $region, 'type' => 'string')
			));

			// if language code
			if( strpos($language, '-') !== false ){
				$lang_code = $language;
				list($language, $region) = $this->extractLanguageBits($lang_code);
			}
			// if language
			else{
				$lang_code = $this->buildLanguageCode($language, $region);
			}

			// make sure language code exists in current setup
			if( in_array($lang_code, $this->_langs) ){
				$this->setLang($language);
				$this->setReg($region);

				return true;
			}

			return false;
		}

		/**
		 * Set language
		 *
		 * @param $language
		 */
		private function setLang($language){
			$this->_lang = $language;
		}

		/**
		 * Set region
		 *
		 * @param $region
		 */
		private function setReg($region){
			$this->_reg = $region;
		}

		/**
		 * Set main language code
		 *
		 * @param $lang_code
		 * @param boolean $write
		 *
		 * @return boolean
		 */
		public function setMainLang($lang_code, $write = true){

			// make sure it exists in language codes
			if( in_array($lang_code, $this->_langs) ){
				$this->_main_lang = $lang_code;
			}

			// defaults to first language
			elseif( isset($this->_langs[0]) ){
				$this->_main_lang = $this->_langs[0];
			}

			else{
				return false;
			}

			if( $write === true ){
				Symphony::Configuration()->set('main_lang', $this->_main_lang, FL_GROUP);
				$this->_write_config = true;
			}

			return true;
		}

		/**
		 * Set language codes
		 *
		 * @param $langs
		 * @param boolean $write
		 *
		 * @return boolean
		 */
		public function setLangs($langs, $write = true){
			$langs = explode(',', General::sanitize($langs));

			// if no language codes, return false
			if( $langs === false || !is_array($langs) ){
				return false;
			}

			$langs = $this->cleanLanguageCodes($langs);

			if( count($langs) === 0 ){
				return false;
			}

			$new_codes = array();

			// only valid language codes are preserved
			foreach( $langs as $lc ){
				if( array_key_exists($lc, $this->_all_langs) ){
					$new_codes[] = $lc;
				}
			}

			// if no valid language codes, return false
			if( empty($new_codes) ){
				return false;
			}

			// store the new language codes
			$this->_langs = $new_codes;

			if( $write === true ){
				Symphony::Configuration()->set('langs', implode(',', $this->_langs), FL_GROUP);
				$this->_write_config = true;
			}

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Utilities  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Checks that given language code is valid (exists between frontend languages)
		 *
		 * @param string $lang_code
		 *
		 * @return boolean true if language code is valid, false otherwise
		 */
		public function validateLangCode($lang_code){
			return in_array($lang_code, $this->_langs);
		}

		/**
		 * Sanitize language codes array
		 *
		 * @param $langs
		 *
		 * @return array
		 */
		public function cleanLanguageCodes($langs){
			$clean = array_map('trim', $langs);
			$clean = array_filter($clean);

			return $clean;
		}

		/**
		 * Helper to build the language code
		 *
		 * @param string $language
		 * @param string $region
		 *
		 * @return string - the language code
		 */
		public function buildLanguageCode($language, $region = ''){
			return $language.(($region !== '') ? '-'.$region : '');
		}

		/**
		 * Helper to extract language information from a language code
		 *
		 * @param $lang_code
		 *
		 * @return array - array(0 => string, 1 => string)
		 */
		public function extractLanguageBits($lang_code){
			$bits = explode('-', $lang_code);

			if( empty($bits[0]) )
				throw new Exception('Invalid language code.');

			return array($bits[0], isset($bits[1]) ? $bits[1] : '');
		}



		/**
		 * Deprecated API. Use new API instead
		 *
		 * @deprecated
		 */

		/**
		 * Deprecated. Use @see instance()
		 *
		 * @deprecated
		 *
		 * @return FLang
		 */
		public function ld(){
			return $this;
		}

		/**
		 * Deprecated. Use @see getMainLang() instead
		 *
		 * @deprecated
		 *
		 * @return string
		 */
		public function referenceLanguage(){
			return $this->getMainLang();
		}

		/**
		 * Deprecated. Use @see getAllLangs() instead
		 *
		 * @deprecated
		 *
		 * @return string
		 */
		public function allLanguages(){
			return $this->getAllLangs();
		}

		/**
		 * Deprecated. Use @see getLangCode() instead
		 *
		 * @deprecated
		 *
		 * @return string
		 */
		public function languageCode(){
			return $this->getLangCode();
		}

		/**
		 * Deprecated. Use @see getLangs() instead
		 *
		 * @deprecated
		 *
		 * @return string
		 */
		public function languageCodes(){
			return $this->getLangs();
		}


	}
