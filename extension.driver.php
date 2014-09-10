<?php

	if (!defined('__IN_SYMPHONY__')) {
		die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	}

	final class Extension_Frontend_Localisation extends Extension {

		private static $assets_loaded = false;



		/*------------------------------------------------------------------------------------------------*/
		/*  Installation  */
		/*------------------------------------------------------------------------------------------------*/

		public function install() {
			try {
				$this->meetDependencies();
			} catch (Exception $e) {
				Administration::instance()->Page->pageAlert($e->getMessage(), Alert::ERROR);
			}

			/* Configuration */
			Symphony::Configuration()->set('langs', '', 'frontend_localisation');
			Symphony::Configuration()->set('main_lang', '', 'frontend_localisation');
			Symphony::Configuration()->set('ref_lang', '', 'frontend_localisation');

			Symphony::Configuration()->write();

			return true;
		}

		public function uninstall() {

			/* Configuration */
			Symphony::Configuration()->remove('frontend_localisation');
			Symphony::Configuration()->write();

			return true;
		}

		public function update($previousVersion = false) {
			try {
				$this->meetDependencies();
			} catch (Exception $e) {
				Administration::instance()->Page->pageAlert($e->getMessage(), Alert::ERROR);
			}

			if (version_compare($previousVersion, '1.4', '<')) {
				Symphony::Configuration()->remove('fl_driver', 'frontend_localisation');

				Symphony::Configuration()->set('lang_codes', '', 'frontend_localisation');
				Symphony::Configuration()->set('main_lang', '', 'frontend_localisation');

				$ref_lang = Symphony::Configuration()->get('reference_language', 'frontend_localisation');
				Symphony::Configuration()->set('ref_lang', $ref_lang, 'frontend_localisation');

				$consolidate = Symphony::Configuration()->get('consolidate_translations', 'frontend_localisation');
				Symphony::Configuration()->set('consolidate', $consolidate, 'frontend_localisation');
			}

			if (version_compare($previousVersion, '2.0', '<')) {
				General::deleteDirectory(WORKSPACE . Symphony::Configuration()->get('translation_path', 'frontend_localisation'));

				try {
					Symphony::Database()->query("ALTER TABLE `tbl_pages` DROP `translations`");
				} catch (Exception $e) {
				}
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Delegates  */
		/*------------------------------------------------------------------------------------------------*/

		public function getSubscribedDelegates() {
			return array(

				array(
					'page'     => '/frontend/',
					'delegate' => 'FrontendInitialised',
					'callback' => 'dFrontendInitialised'
				),
				array(
					'page'     => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'dInitialiseAdminPageHead'
				),
				array(
					'page'     => '/frontend/',
					'delegate' => 'FrontendParamsPostResolve',
					'callback' => 'dFrontendParamsPostResolve'
				),
				array(
					'page'     => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'dAddCustomPreferenceFieldsets'
				),
				array(
					'page'     => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => 'dSave'
				),
			);
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Initialisation */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Frontend
		 */
		public function dFrontendInitialised() {
			if (!$this->meetDependencies(true)) {
				return;
			}

			$this->_initFLang();

			Lang::set(FLang::getLangCode());
		}

		public function dFrontendParamsPostResolve($context) {
			if (!$this->meetDependencies(true)) {
				return;
			}

			if (isset($context['params']['url-fl-language'])) {
				unset($context['params']['url-fl-language']);
			}

			if (isset($context['params']['url-fl-region'])) {
				unset($context['params']['url-fl-region']);
			}
		}

		/**
		 * Backend
		 */
		public function dInitialiseAdminPageHead() {
			try {
				$this->meetDependencies();
			} catch (Exception $e) {
				Administration::instance()->Page->pageAlert($e->getMessage(), Alert::ERROR);
			}

			$this->_initFLang();
		}

		private function _initFLang() {
			require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLang.php');

			// initialize Language codes
			$langs = Symphony::Configuration()->get('langs', 'frontend_localisation');
			FLang::setLangs($langs);

			// initialize Main language
			$main_lang = Symphony::Configuration()->get('main_lang', 'frontend_localisation');
			if (!FLang::setMainLang($main_lang)) {
				$langs = FLang::getLangs();

				if (isset($langs[0]) && !FLang::setLangCode($langs[0])) {
					// do something useful here if no lang is set ...
				}
			}

			// read current language
			$language = isset($_REQUEST['fl-language']) ? General::sanitize($_REQUEST['fl-language']) : null;
			$region   = isset($_REQUEST['fl-region'])   ? General::sanitize($_REQUEST['fl-region'])   : null;

			// set language code
			if (false === FLang::setLangCode($language, $region)) {

				// try to set language from Admin
				if (Symphony::Engine() instanceof Administration) {

					// author language is not supported
					if (false === FLang::setLangCode(Lang::get())) {
						FLang::setLangCode(FLang::getMainLang());
					}
				}

				// set main lang
				else {
					FLang::setLangCode(FLang::getMainLang());
				}
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  System preferences  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Display options on Preferences page.
		 *
		 * @param array $context
		 */
		public function dAddCustomPreferenceFieldsets(array $context) {
			if (!$this->meetDependencies(true)) {
				return;
			}

			Administration::instance()->Page->addScriptToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.preferences.js', null, false);

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Frontend Localisation')));

			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->_appendLangs($div, $context);
			$this->_appendMainLang($div, $context);
			$group->appendChild($div);

			$context['wrapper']->appendChild($group);
		}

		/**
		 * Convenience method; builds language codes input
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendLangs(&$wrapper, $context) {
			$label = Widget::Label(__('Site languages'), null, 'column', 'frontend_localisation' . '_langs');

			require_once EXTENSIONS . '/languages/extension.driver.php';

			$options = Extension_Languages::findOptions(FLang::getLangs());

			$label->appendChild(Widget::Select('settings[' . 'frontend_localisation' . '][langs]', $options, array('multiple' => 'multiple')));
			$label->appendChild(new XMLElement('p', __('Select languages of the site.'), array('class' => 'help')));

			if (isset($context['errors']['frontend_localisation']['langs'])) {
				$wrapper->appendChild(Widget::Error($label, $context['errors']['frontend_localisation']['langs']));
			}
			else {
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Convenience method; builds main language select
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendMainLang(&$wrapper, $context) {
			$label = Widget::Label(__('Main language'), null, 'column', 'frontend_localisation' . '_main_lang');

			$options = Extension_Languages::findOptions(FLang::getMainLang(), FLang::getLangs());

			$label->appendChild(Widget::Select('settings[' . 'frontend_localisation' . '][main_lang]', $options));
			$label->appendChild(new XMLElement('p', __('Select the main language of the site.'), array('class' => 'help')));

			if (isset($context['errors']['frontend_localisation']['main_lang'])) {
				$wrapper->appendChild(Widget::Error($label, $context['errors']['frontend_localisation']['main_lang']));
			}
			else {
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Save options from Preferences page
		 *
		 * @param array $context
		 *
		 * @return boolean
		 */
		public function dSave(array $context) {
			$valid = true;

			$data   = & $context['settings']['frontend_localisation'];
			$errors = array();

			if (!$this->meetDependencies(true)) {
				return $valid;
			}

			/* Language codes */

			$old_langs = FLang::getLangs();
			if (!FLang::setLangs($data['langs'])) {
				$valid           = false;
				$errors['langs'] = __('Please fill at least one valid language code.');
			}
			$new_langs = FLang::getLangs();
			unset($data['langs']);
			Symphony::Configuration()->set('langs', implode(',', $new_langs), 'frontend_localisation');

			/**
			 * When saving Preferences, supplies the old_languages and new_languages arrays.
			 *
			 * @delegate FLSavePreferences
			 * @since    1.4
			 *
			 * @param string $context   - '/extensions/frontend_localisation/'
			 * @param array  $context   - the original context from @delegate Save
			 * @param array  $old_langs - old language codes
			 * @param array  $new_langs - new language codes
			 */
			Symphony::ExtensionManager()->notifyMembers('FLSavePreferences', '/extensions/frontend_localisation/', array(
				'context'   => $context,
				'old_lang'  => $old_langs,
				'new_langs' => $new_langs
			));

			/* Main language */

			if (!FLang::setMainLang($data['main_lang'])) {

				if (!empty($old_langs) || !(isset($new_langs[0]) && FLang::setMainLang($new_langs[0]))) {
					$valid               = false;
					$errors['main_lang'] = __('Invalid language code.');
				}
			}
			$main_lang = FLang::getMainLang();
			unset($data['main_lang']);
			Symphony::Configuration()->set('main_lang', $main_lang, 'frontend_localisation');

			Symphony::Configuration()->write();

			if (!empty($errors)) {
				$context['errors']['frontend_localisation'] = $errors;
			}

			return $valid;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Public utilities  */
		/*------------------------------------------------------------------------------------------------*/

		public static function appendAssets() {
			if (self::$assets_loaded === false
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				self::$assets_loaded = true;

				$page = Administration::instance()->Page;

				$page->addStylesheetToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.multilingual_tabs.css', 'screen', null, false);
				$page->addScriptToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.multilingual_tabs.js', null, false);
				$page->addScriptToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.multilingual_tabs_init.js', null, false);
			}
		}

		/**
		 * Returns true or false if dependencies are met.
		 *
		 * @param bool $return_status - if this is set, it will return true or false if dependencies are met. if it is not set, error is thrown
		 *
		 * @throws Exception
		 *
		 * @return bool
		 */
		public function meetDependencies($return_status = false) {

			// depends on "Languages"
			$languages_status = ExtensionManager::fetchStatus(array('handle' => 'languages'));
			$languages_status = current($languages_status);

			if ($languages_status != EXTENSION_ENABLED) {
				if ($return_status) {
					return false;
				}
				else {
					throw new Exception('Frontend Localisation depends on Languages extension.');
				}
			}

			return true;
		}
	}
