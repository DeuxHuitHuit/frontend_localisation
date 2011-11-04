<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/**
	 * Contains mandatory data a Language Driver must provide 
	 */
	abstract class LanguageDriver
	{
		/**
		 * Array containing lang_code->lang_name pairs of Languages.
		 * 
		 * @var array
		 */
		protected $all_languages = array(			// [English name]
			'ab' => 'Đ°Ň§Ń�Ń�Đ° Đ±Ń‹Đ·Ń�Ó™Đ°',					// Abkhazian
			'af' => 'Afrikaans',					// Afrikaans
			'sq' => 'shqip',						// Albanian
			'am' => 'áŠ á�›á�­áŠ›',							// Amharic
			'ar-dz' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Algeria)',			// Arabic
			'ar-bh' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Bahrain)',			// Arabic
			'ar-eg' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Egypt)',			// Arabic
			'ar-iq' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Iraq)',			// Arabic
			'ar-jo' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Jordan)',			// Arabic
			'ar-kw' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Kuwait)',			// Arabic
			'ar-lb' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Lebanon)',			// Arabic
			'ar-ly' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Libya)',			// Arabic
			'ar-ma' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Morocco)',			// Arabic
			'ar-om' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Oman)',			// Arabic
			'ar-qa' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Qatar)',			// Arabic
			'ar-sa' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Saudi Arabia)',	// Arabic
			'ar-sy' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Syria)',			// Arabic
			'ar-tn' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Tunisia)',			// Arabic
			'ar-ae' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (U.A.E.)',			// Arabic
			'ar-ye' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ© (Yemen)',			// Arabic
			'ar' => 'Ř§Ů„ŘąŘ±Ř¨ŮŠŘ©',						// Arabic
			'hy' => 'Ő€ŐˇŐµŐĄÖ€ŐĄŐ¶',							// Armenian
			'as' => 'ŕ¦…ŕ¦¸ŕ¦®ŕ§€ŕ¦Żŕ¦Ľŕ¦ľ',								// Assamese
			'az' => 'azÉ™rbaycan',					// Azeri
			'eu' => 'euskera',						// Basque
			'be' => 'Đ‘ĐµĐ»Đ°Ń€Ń�Ń�ĐşĐ°ŃŹ',					// Belarusian
			'bn' => 'ŕ¦¬ŕ¦ľŕ¦‚ŕ¦˛ŕ¦ľ',								// Bengali
			'bg' => 'Đ‘ŃŠĐ»ĐłĐ°Ń€Ń�ĐşĐ¸',					// Bulgarian
			'ca' => 'CatalĂ ',						// Catalan
			'zh-cn' => 'ç®€ä˝“ä¸­ć–‡ (China)',					// Chinese simplified script
			'zh-hk' => 'çą�é«”ä¸­ć–‡ (Hong Kong SAR)',			// Chinese traditional script
			'zh-mo' => 'çą�é«”ä¸­ć–‡ (Macau SAR)',				// Chinese traditional script
			'zh-sg' => 'ç®€ä˝“ä¸­ć–‡ (Singapore)',				// Chinese simplified script
			'zh-tw' => 'çą�é«”ä¸­ć–‡ (Taiwan)',				// Chinese traditional script
			'zh' => 'ä¸­ć–‡',							// Chinese
			'hr' => 'Hrvatski',						// Croatian
			'cs' => 'ÄŤeĹˇtina',						// Czech
			'da' => 'Dansk',						// Danish
			'dv' => 'Ţ‹Ţ¨Ţ�Ţ¬Ţ€Ţ¨',							// Divehi
			'nl-be' => 'Nederlands (Belgium)',		// Dutch
			'nl' => 'Nederlands (Netherlands)',		// Dutch
			'en-au' => 'English (Australia)',		// English
			'en-bz' => 'English (Belize)',			// English
			'en-ca' => 'English (Canada)',			// English
			'en-ie' => 'English (Ireland)',			// English
			'en-jm' => 'English (Jamaica)',			// English
			'en-nz' => 'English (New Zealand)',		// English
			'en-ph' => 'English (Philippines)',		// English
			'en-za' => 'English (South Africa)',	// English
			'en-tt' => 'English (Trinidad)',		// English
			'en-gb' => 'English (United Kingdom)',	// English
			'en-us' => 'English (United States)',	// English
			'en-zw' => 'English (Zimbabwe)',		// English
			'en' => 'English',						// English
			'ee' => 'Ć�Ę‹É›',							// Ewe
			'et' => 'Eesti',						// Estonian
			'fo' => 'fĂ¸royskt',						// Faeroese
			'fa' => 'Ů�Ř§Ř±ŘłŰŚ',						// Farsi
			'fi' => 'suomi',						// Finnish
			'fr-be' => 'Francais (Belgium)',		// French (Belgium)
			'fr-ca' => 'Francais canadien',			// French (Canada)
			'fr-lu' => 'Francais (Luxembourg)',		// French
			'fr-mc' => 'Francais (Monaco)',			// French
			'fr-ch' => 'Francais (Switzerland)',	// French
			'fr' => 'Francais',						// French
			'ff' => 'Fulfulde, Pulaar, Pular',		// Fula, Fulah, Fulani
			'gl' => 'Galego',						// Galician
			'gd' => 'GĂ idhlig',						// Gaelic (Scottish)
			'ga' => 'Gaeilge',						// Gaelic (Irish)
			'gv' => 'Gaelg',						// Gaelic (Manx) (Isle of Man)
			'ka' => 'á�Ąá��á� á�—á�Łá�šá�� á�”á�śá��',						// Georgian
			'de-at' => 'Deutsch (Austria)',			// German
			'de-li' => 'Deutsch (Liechtenstein)',	// German
			'de-lu' => 'Deutsch (Luxembourg)',		// German
			'de-ch' => 'Deutsch (Switzerland)',		// German
			'de' => 'Deutsch',						// German
			'el' => 'Î•Î»Î»Î·Î˝ÎąÎşÎ¬',						// Greek
			'gu' => 'ŕŞ—ŕ«�ŕŞśŕŞ°ŕŞľŕŞ¤ŕ«€',							// Gujarati
			'ha' => 'Ů‡ŮŽŮ�Ů’ŘłŮŽ',							// Hausa
			'he' => '×˘×‘×¨×™×Ş',						// Hebrew
			'hi' => 'ŕ¤ąŕ¤żŕ¤‚ŕ¤¦ŕĄ€',							// Hindi
			'hu' => 'Magyar',						// Hungarian
			'is' => 'ĂŤslenska',						// Icelandic
			'id' => 'Bahasa Indonesia',				// Indonesian
			'it-ch' => 'italiano (Switzerland)',	// Italian
			'it' => 'italiano',						// Italian
			'ja' => 'ć—Ąćś¬čŞž',							// Japanese
			'kn' => 'ŕ˛•ŕ˛¨ŕłŤŕ˛¨ŕ˛ˇ',							// Kannada
			'kk' => 'ŇšĐ°Đ·Đ°Ň›',						// Kazakh
			'rw' => 'Kinyarwanda',					// Kinyarwanda
			'kok' => 'ŕ¤•ŕĄ‹ŕ¤‚ŕ¤•ŕ¤ŁŕĄ€',							// Konkani
			'ko' => 'í•śęµ­ě–´/ěˇ°ě„ ë§�',							// Korean
			'kz' => 'ĐšŃ‹Ń€ĐłŃ‹Đ·',						// Kyrgyz
			'lv' => 'LatvieĹˇu',						// Latvian
			'lt' => 'LietuviĹˇkai',					// Lithuanian
			'luo'=> 'Dholuo',						// Luo
			'ms' => 'Bahasa Melayu',				// Malay
			'mk' => 'ĐśĐ°ĐşĐµĐ´ĐľĐ˝Ń�ĐşĐ¸',					// Macedonian
			'ml' => 'ŕ´®ŕ´˛ŕ´Żŕ´ľŕ´łŕ´‚',								// Malayalam
			'mt' => 'Malti',						// Maltese
			'mr' => 'ŕ¤®ŕ¤°ŕ¤ľŕ¤ ŕĄ€',							// Marathi
			'mn' => 'ĐśĐľĐ˝ĐłĐľĐ»',						// Mongolian  (Cyrillic)
			'ne' => 'ŕ¤¨ŕĄ‡ŕ¤Şŕ¤ľŕ¤˛ŕĄ€',							// Nepali
			'nb-no' => 'Norsk bokmĂĄl',				// Norwegian BokmĂĄl
			'nb' => 'Norsk bokmĂĄl',					// Norwegian BokmĂĄl
			'nn-no' => 'Norsk nynorsk',				// Norwegian Nynorsk
			'nn' => 'Norsk nynorsk',				// Norwegian Nynorsk
			'no' => 'Norsk',						// Norwegian
			'or' => 'ŕ¬“ŕ¬ˇŕ¬Ľŕ¬żŕ¬†',								// Oriya
			'ps' => 'ŮľÚšŘŞŮ�',							// Pashto
			'pl' => 'polski',						// Polish
			'pt-br' => 'portuguĂŞs brasileiro',		// Portuguese (Brasil)
			'pt' => 'portuguĂŞs',					// Portuguese
			'pa' => 'ŮľŮ†Ř¬Ř§Ř¨ŰŚ/ŕ¨Şŕ©°ŕ¨śŕ¨ľŕ¨¬ŕ©€',					// Punjabi
			'qu' => 'Runa Simi/Kichwa',				// Quechua
			'rm' => 'Romansch',						// Rhaeto-Romanic
			'ro-md' => 'Română (Moldova)',			// Romanian
			'ro' => 'Română',						// Romanian
			'rn' => 'kiRundi', 						// Rundi
			'ru-md' => 'PyccÄ¸Đ¸Đą (Moldova)',			// Russian
			'ru' => 'PyccÄ¸Đ¸Đą',						// Russian
			'sg' => 'yĂ˘ngĂ˘ tĂ® sĂ¤ngĂ¶',				// Sango
			'sa' => 'ŕ¤¸ŕ¤‚ŕ¤¸ŕĄŤŕ¤•ŕĄ�ŕ¤¤ŕ¤®ŕĄŤ',							// Sanskrit
			'sc' => 'sardu',						// Sardinian
			'sr' => 'Srpski/Ń�Ń€ĐżŃ�ĐşĐ¸',				// Serbian
			'sn' => 'chiShona',						// Shona
			'ii' => 'ę†‡ę‰™',							// Sichuan Yi
			'si' => 'ŕ·�ŕ·’ŕ¶‚ŕ·„ŕ¶˝',						// Sinhalese, Sinhala
			'sk' => 'SlovenÄŤina',					// Slovak
			'ls' => 'SlovenĹˇÄŤina',					// Slovenian
			'so' => 'Soomaaliga/af Soomaali',		// Somali
			'st' => 'Sesotho',						// Sotho, Sutu
			'es-ar' => 'EspaĂ±ol (Argentina)',		// Spanish
			'es-bo' => 'EspaĂ±ol (Bolivia)',			// Spanish
			'es-cl' => 'EspaĂ±ol (Chile)',			// Spanish
			'es-co' => 'EspaĂ±ol (Colombia)',		// Spanish
			'es-cr' => 'EspaĂ±ol (Costa Rica)',		// Spanish
			'es-do' => 'EspaĂ±ol (Dominican Republic)',// Spanish
			'es-ec' => 'EspaĂ±ol (Ecuador)',			// Spanish
			'es-sv' => 'EspaĂ±ol (El Salvador)',		// Spanish
			'es-gt' => 'EspaĂ±ol (Guatemala)',		// Spanish
			'es-hn' => 'EspaĂ±ol (Honduras)',		// Spanish
			'es-mx' => 'EspaĂ±ol (Mexico)',			// Spanish
			'es-ni' => 'EspaĂ±ol (Nicaragua)',		// Spanish
			'es-pa' => 'EspaĂ±ol (Panama)',			// Spanish
			'es-py' => 'EspaĂ±ol (Paraguay)',		// Spanish
			'es-pe' => 'EspaĂ±ol (Peru)',			// Spanish
			'es-pr' => 'EspaĂ±ol (Puerto Rico)',		// Spanish
			'es-us' => 'EspaĂ±ol (United States)',	// Spanish
			'es-uy' => 'EspaĂ±ol (Uruguay)',			// Spanish
			'es-ve' => 'EspaĂ±ol (Venezuela)',		// Spanish
			'es' => 'EspaĂ±ol',						// Spanish
			'sw' => 'kiswahili',					// Swahili
			'sv-fi' => 'svenska (Finland)',			// Swedish
			'sv' => 'svenska',						// Swedish
			'syr' => 'ÜŁÜ�ÜŞÜťÜťÜ�',						// Syriac
			'ta' => 'ŕ®¤ŕ®®ŕ®żŕ®´ŕŻŤ',							// Tamil
			'tt' => 'Ń‚Đ°Ń‚Đ°Ń€Ń‡Đ°/ŘŞŘ§ŘŞŘ§Ř±Ú†Ř§',				// Tatar
			'te' => 'ŕ°¤ŕ±†ŕ°˛ŕ±�ŕ°—ŕ±�',							// Telugu
			'th' => 'ŕ¸ ŕ¸˛ŕ¸©ŕ¸˛ŕą„ŕ¸—ŕ¸˘',						// Thai
			'ti' => 'á‰µáŚŤá�­áŠ›',							// Tigrinya
			'ts' => 'Xitsonga',						// Tsonga
			'tn' => 'Setswana',						// Tswana
			'tr' => 'TĂĽrkĂ§e',						// Turkish
			'tk' => 'Đ˘ŇŻŃ€ĐşĐĽĐµĐ˝',						// Turkmen
			'ug' => 'Ř¦Ű‡ŮŠŘşŰ‡Ř±Ú†Ű•â€Ž/UyĆŁurqÉ™/ĐŁĐąŇ“Ń�Ń€Ń‡Ó™',		// Uighur, Uyghur
			'uk' => 'ĐŁĐşŃ€Đ°Ń—Đ˝Ń�ŃŚĐşĐ°',					// Ukrainian
			'ur' => 'Ř§Ř±ŘŻŮ�',							// Urdu
			'uz' => 'o\'zbek',						// Uzbek
			've' => 'Tshivená¸“a',					// Venda
			'vi' => 'Tiáşżng Viá»‡t',					// Vietnamese
			'wa' => 'walon',						// Waloon
			'cy' => 'Cymraeg',						// Welsh
			'wo' => 'Wolof',						// Wolof
			'xh' => 'isiXhosa',						// Xhosa
			'yi' => '×™×™Ö´×“×™×©',						// Yiddish
			'yo' => 'YorĂąbĂˇ',						// Yoruba
			'zu' => 'isiZulu',						// Zulu
		);
		
		/**
		 * Returns all languages
		 * 
		 * @return array - Defaults to inner $all_languages
		 */
		public function getAllLanguages(){
			return (array) $this->all_languages;
		}
		
		/**
		 * Returns all driver details.
		 * 
		 * @return array
		 */
		public function getDriverDetails(){
			return (array) Symphony::ExtensionManager()->about($this->getHandle());
		}
		
		/**
		 * Returns if current driver is enabled or not.
		 * 
		 * @return boolean - true if EXTENSION_ENABLED, false otherwise
		 */
		public function getDriverStatus(){
			return (boolean) (Symphony::ExtensionManager()->fetchStatus($this->getHandle()) == EXTENSION_ENABLED);
		}
		
		/**
		 * Returns name of driver.
		 * 
		 * @return string
		 */
		abstract public function getName();
		
		/**
		 * Returns handle of driver.
		 * 
		 * @return string
		 */
		abstract public function getHandle();
		
		/**
		 * Returns current language code.
		 * 
		 * @return string
		 */
		abstract public function getLanguageCode();
		
		/**
		 * Returns reference language code.
		 * 
		 * @return string
		 */
		abstract public function getReferenceLanguage();

		/**
		 * Returns supported languages codes.
		 * 
		 * @return array
		 */
		abstract public function getLanguageCodes();

		/**
		 * Returns newly entered language codes
		 * 
		 * @param array $context - entire form data from Preferences page
		 * @return array - new laguage codes
		 */
		abstract public function getSavedLanguages($context);
		
	}