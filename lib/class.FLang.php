<?php

	if (!defined('__IN_SYMPHONY__')) {
		die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	}

	require_once EXTENSIONS . '/languages/lib/class.languages.php';

	/**
	 * Provides Frontend language information
	 *
	 * @static
	 */
	Final Class FLang {

		/*------------------------------------------------------------------------------------------------*/
		/*  Properties  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Current language
		 *
		 * @var string
		 */
		private static $_lang = '';

		/**
		 * Current region
		 *
		 * @var string
		 */
		private static $_reg = '';

		/**
		 * Main language
		 *
		 * @var string
		 */
		private static $_main_lang = '';

		/**
		 * Stored language codes
		 *
		 * @var array
		 */
		private static $_langs = array();



		/*------------------------------------------------------------------------------------------------*/
		/*  Getters  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Get language code
		 *
		 * @return string
		 */
		public static function getLangCode() {
			return self::buildLanguageCode(self::$_lang, self::$_reg);
		}

		/**
		 * Get language
		 *
		 * @return string
		 */
		public static function getLang() {
			return self::$_lang;
		}

		/**
		 * Get region
		 *
		 * @return string
		 */
		public static function getReg() {
			return self::$_reg;
		}

		/**
		 * Get main language. Defaults to first language
		 *
		 * @return string
		 */
		public static function getMainLang() {
			return self::$_main_lang;
		}

		/**
		 * Get supported language codes.
		 *
		 * @return array
		 */
		public static function getLangs() {
			return self::$_langs;
		}

		public static function getAllLangs($lc = null) {
			if ($lc === null) {
				$lc = self::getLangCode();
			}

			if (!($languages = Languages::local()->listAll($lc))) {
				$languages = Languages::local()->listAll();
			}

			return $languages;
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
		public static function setLangCode($language, $region = '') {
			if (empty($language)) {
				return false;
			}
			if ($region == null) {
				$region = '';
			}

			General::ensureType(array(
				'language' => array('var' => $language, 'type' => 'string'),
				'region'   => array('var' => $region, 'type' => 'string')
			));

			$language = strtolower($language);
			$region   = strtolower($region);

			// if language code
			if (strpos($language, '-') !== false) {
				$lang_code = $language;
				list($language, $region) = self::extractLanguageBits($lang_code);
			}
			// if language
			else {
				$lang_code = self::buildLanguageCode($language, $region);
			}

			// make sure language code exists in current setup
			if (in_array($lang_code, self::$_langs)) {
				self::setLang($language);
				self::setReg($region);

				return true;
			}

			return false;
		}

		/**
		 * Set language
		 *
		 * @param $language
		 */
		private static function setLang($language) {
			self::$_lang = $language;
		}

		/**
		 * Set region
		 *
		 * @param $region
		 */
		private static function setReg($region) {
			self::$_reg = $region;
		}

		/**
		 * Set main language code
		 *
		 * @param $lang_code
		 *
		 * @return boolean
		 */
		public static function setMainLang($lang_code) {
			if (!self::validateLangCode($lang_code)) {
				return false;
			}

			self::$_main_lang = $lang_code;

			return true;
		}

		/**
		 * Set language codes
		 *
		 * @param $langs
		 *
		 * @return boolean
		 */
		public static function setLangs($langs) {
			$langs = explode(',', General::sanitize($langs));

			// if no language codes, return false
			if ($langs === false || !is_array($langs)) {
				return false;
			}

			$langs = self::cleanLanguageCodes($langs);

			if (count($langs) === 0) {
				return false;
			}

			$new_codes = array();
			$all_codes = array_keys(Languages::all()->listAll());

			// only valid language codes are preserved
			foreach ($langs as $lc) {
				if (in_array($lc, $all_codes)) {
					$new_codes[] = $lc;
				}
			}

			// if no valid language codes, return false
			if (empty($new_codes)) {
				return false;
			}

			// store the new language codes
			self::$_langs = $new_codes;

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
		public static function validateLangCode($lang_code) {
			return in_array($lang_code, self::$_langs);
		}

		/**
		 * Sanitize language codes array
		 *
		 * @param $langs
		 *
		 * @return array
		 */
		public static function cleanLanguageCodes($langs) {
			$clean = array_map('trim', $langs);
			$clean = array_map('strtolower', $clean);
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
		public static function buildLanguageCode($language, $region = '') {
			return $language . (($region !== '') ? '-' . $region : '');
		}

		/**
		 * Helper to extract language information from a language code
		 *
		 * @param $lang_code
		 *
		 * @throws Exception
		 *
		 * @return array - array(0 => string, 1 => string)
		 */
		public static function extractLanguageBits($lang_code) {
			$bits = explode('-', $lang_code);

			if (empty($bits[0])) {
				throw new Exception('Invalid language code.');
			}

			return array($bits[0], isset($bits[1]) ? $bits[1] : '');
		}
	}
