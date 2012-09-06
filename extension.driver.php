<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once('lib/class.TManager.php');
	require_once('lib/class.FLang.php');



	define_safe(FL_NAME, 'Frontend Localisation');
	define_safe(FL_GROUP, 'frontend_localisation');



	final class Extension_Frontend_Localisation extends Extension
	{

		const CHECKBOX_YES = 'yes';

		/**
		 * Caches changed page handles when editing a page.
		 *
		 * @var array
		 */
		private $changed_handles = array();

		private static $assets_loaded = false;



		/*------------------------------------------------------------------------------------------------*/
		/*  Installation  */
		/*------------------------------------------------------------------------------------------------*/

		public function install(){

			/* Database */
			try{
				Symphony::Database()->query("ALTER TABLE `tbl_pages` ADD `translations` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `events`");
			}
			catch( DatabaseException $dbe ){
				// column already exists
				if( $dbe->getDatabaseErrorCode() == 1060 ){
					$message = __('<code>%1$s</code>: Column `translations` for `tbl_pages` already exists. Uninstall extension and re-install it after.', array(FL_NAME));
				}
				// other errors
				else{
					$message = __('<code>%1$s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.', array(FL_NAME, $dbe['_error']['num']));
				}

				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::Log()->pushToLog($message, E_NOTICE, true);
				return false;
			}
			catch( Exception $e ){
				Administration::instance()->Page->pageAlert($e->getMessage(), Alert::ERROR);
				Symphony::Log()->pushToLog($e->getMessage(), E_NOTICE, true);
				return false;
			}

			/* Configuration */
			Symphony::Configuration()->set('langs', '', FL_GROUP);
			Symphony::Configuration()->set('main_lang', '', FL_GROUP);
			Symphony::Configuration()->set('ref_lang', '', FL_GROUP);
			Symphony::Configuration()->set('translation_path', '/translations', FL_GROUP);
			Symphony::Configuration()->set('page_name_prefix', 'p_', FL_GROUP);
			Symphony::Configuration()->set('storage_format', 'xml', FL_GROUP);
			Symphony::Configuration()->set('consolidate', self::CHECKBOX_YES, FL_GROUP);

			Symphony::Configuration()->write();

			/* Translations */
			General::realiseDirectory(WORKSPACE.Symphony::Configuration()->get('translation_path', FL_GROUP));

			/* Update existing translations */
			TManager::updateFolders();

			return true;
		}

		public function update($previousVersion = false){
			if( version_compare($previousVersion, '1.4', '<') ){
				Symphony::Configuration()->remove('fl_driver', FL_GROUP);

				Symphony::Configuration()->set('lang_codes', '', FL_GROUP);
				Symphony::Configuration()->set('main_lang', '', FL_GROUP);

				$ref_lang = Symphony::Configuration()->get('reference_language', FL_GROUP);
				Symphony::Configuration()->set('ref_lang', $ref_lang, FL_GROUP);

				$consolidate = Symphony::Configuration()->get('consolidate_translations', FL_GROUP);
				Symphony::Configuration()->set('consolidate', $consolidate, FL_GROUP);
			}
		}

		public function uninstall(){
			/* Translations */
			if( Symphony::Configuration()->get('consolidate', FL_GROUP) != self::CHECKBOX_YES ){
				General::deleteDirectory(WORKSPACE.Symphony::Configuration()->get('translation_path', FL_GROUP));
			}

			/* Database */
			try{
				Symphony::Database()->query("ALTER TABLE `tbl_pages` DROP `translations`");
			} catch( Exception $e ){
				$message = __('<code>%1$s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.', array(FL_NAME));

				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::Log()->pushToLog($message, E_NOTICE, true);
			}

			/* Configuration */
			Symphony::Configuration()->remove(FL_GROUP);
			Symphony::Configuration()->write();

			return true;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Navigation  */
		/*------------------------------------------------------------------------------------------------*/

		public function fetchNavigation(){
			return array(
				array(
					'name' => __("Translations"),
					'type' => 'content',
					'children' => array(
						array(
							'name' => __('Translations'),
							'link' => '/'
						)
					),
				)
			);
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Delegates  */
		/*------------------------------------------------------------------------------------------------*/

		public function getSubscribedDelegates(){
			return array(

				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendInitialised',
					'callback' => 'dFrontendInitialised'
				),

				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'dInitialiseAdminPageHead'
				),



				array(
					'page' => '/frontend/',
					'delegate' => 'FrontendParamsPostResolve',
					'callback' => 'dFrontendParamsPostResolve'
				),



				array(
					'page' => '/backend/',
					'delegate' => 'AppendPageAlert',
					'callback' => 'dAppendPageAlert'
				),



				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'AppendPageContent',
					'callback' => 'dAppendPageContent'
				),

				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePreCreate',
					'callback' => 'dPagePreCreate'
				),

				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePostCreate',
					'callback' => 'dPagePostCreate'
				),

				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePreEdit',
					'callback' => 'dPagePreEdit'
				),

				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePostEdit',
					'callback' => 'dPagePostEdit'
				),

				array(
					'page' => '/blueprints/pages/',
					'delegate' => 'PagePreDelete',
					'callback' => 'dPagePreDelete'
				),



				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'dAddCustomPreferenceFieldsets'
				),

				array(
					'page' => '/system/preferences/',
					'delegate' => 'CustomActions',
					'callback' => 'dCustomActions'
				),

				array(
					'page' => '/system/preferences/',
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
		public function dFrontendInitialised(){
			$frontend_localisation = ExtensionManager::fetchStatus(array('handle' => FL_GROUP));

			if( $frontend_localisation[0] = EXTENSION_ENABLED ){
				$this->_initFLang();
				$this->_initTManager();
			}
		}

		public function dFrontendParamsPostResolve($context){
			if( isset($context['params']['url-fl-language']) )
				unset($context['params']['url-fl-language']);

			if( isset($context['params']['url-fl-region']) )
				unset($context['params']['url-fl-region']);
		}

		/**
		 * Backend
		 */
		public function dInitialiseAdminPageHead(){
			$frontend_localisation = ExtensionManager::fetchStatus(array('handle' => FL_GROUP));

			if( $frontend_localisation[0] = EXTENSION_ENABLED ){
				$this->_initFLang();
				$this->_initTManager();
			}
		}

		private function _initFLang(){
			// initialize Language codes
			$langs = Symphony::Configuration()->get('langs', FL_GROUP);
			FLang::setLangs($langs);

			// initialize Main language
			$main_lang = Symphony::Configuration()->get('main_lang', FL_GROUP);
			if( !FLang::setMainLang($main_lang) ){
				$langs = FLang::getLangs();

				if( isset($langs[0]) && !FLang::setLangCode($langs[0]) ){
					// do something usefull here if no lang is set ...
				}
			}

			// read current language
			$language = General::sanitize((string)$_REQUEST['fl-language']);
			$region = General::sanitize((string)$_REQUEST['fl-region']);

			// set language code
			if( false === FLang::setLangCode($language, $region) ){

				// language code is not supported, fallback to main lang
				if( false === FLang::setLangCode(FLang::getMainLang()) ){
					// do something usefull here if no lang is set ...
				}
			}
		}

		private function _initTManager(){
			$ref_lang = Symphony::Configuration()->get('ref_lang', FL_GROUP);
			$path = WORKSPACE.Symphony::Configuration()->get('translation_path', FL_GROUP);
			$page_prefix = Symphony::Configuration()->get('page_name_prefix', FL_GROUP);
			$storage_format = Symphony::Configuration()->get('storage_format', FL_GROUP);

			/**
			 * Allow config to be changed.
			 */
			Symphony::ExtensionManager()->notifyMembers('frontend_localisation_TManagerInitialization', '/extensions/frontend_localisation/', array(
				'ref_lang' => &$ref_lang,
				'path' => &$path,
				'page_prefix' => &$page_prefix,
				'storage_format' => &$storage_format
			));

			try{
				// initialize Reference language
				if( !TManager::setRefLang($ref_lang) ){
					$langs = FLang::getLangs();
					if( !empty($langs) )
						throw new Exception(
							__('<code>%1$s</code>: Reference language code <code>%2$s</code> is not supported.', array(FL_NAME, $ref_lang))
								.'<a href="'.SYMPHONY_URL.'/system/preferences/#'.FL_GROUP.'_translation_path">'.__('Please review settings').'</a>'
						);
				}


				// initialize Translation path
				if( !TManager::setPath($path) ){
					throw new Exception(
						__('<code>%1$s</code>: Translation folder couldn\'t be created at <code>%2$s</code>.', array(FL_NAME, $path))
							.__('Please review settings')
					);
				}


				// initialize Page prefix
				TManager::setPagePrefix($page_prefix);


				// initialize Storage format
				if( !TManager::setStorageFormat($storage_format) ){
					throw new Exception(
						__(
							'<code>%1$s</code>: Storage directory <code>%2$s</code> for <code>%3$s</code> storage format doesn\'t exist.',
							array(FL_NAME, EXTENSIONS.'/'.FL_GROUP.'/lib/'.$storage_format, $storage_format)
						)
							.'<a href="'.SYMPHONY_URL.'/system/preferences/#'.FL_GROUP.'_translation_path">'.__('Please review settings').'</a>'
					);
				}
			}
			catch( Exception $e ){
				$message = $e->getMessage();

				Administration::instance()->Page->pageAlert($message, Alert::NOTICE);
				Symphony::Log()->pushToLog($message, E_NOTICE, true);

				return false;
			}


			// discover Translation Folders
			$t_path = TManager::getPath();

			foreach( FLang::getLangs() as $lc )
				if( is_dir($t_path.'/'.$lc) ){
					TManager::addFolder($lc);
				}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Notifications  */
		/*------------------------------------------------------------------------------------------------*/

		public function dAppendPageAlert(){
			$langs = FLang::getLangs();

			if( empty($langs) ){
				Administration::instance()->Page->pageAlert(
					__('<code>%1$s</code>: No languages have been set on Preferences page. Please <a href="%2$s">review them</a>.',
						array(FL_NAME, SYMPHONY_URL.'/system/preferences/#'.FL_GROUP.'_langs')),
					Alert::NOTICE
				);
			}
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Pages integration  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Append Translations select to Page edit menu
		 *
		 * @param array $context - see delegate description
		 */
		public function dAppendPageContent(array $context){
			// prepare translations array
			if( is_string($context['fields']['translations']) ){
				$context['fields']['translations'] = preg_split('/,/i', $context['fields']['translations'], -1, PREG_SPLIT_NO_EMPTY);
			}

			// generate select
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Frontend Translations')));

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			$label = Widget::Label(__('Translations'));

			$options = array();

			$t_folder = TManager::getFolder(TManager::getRefLang());

			if( !is_null($t_folder) ){
				$translations = $t_folder->getTranslations();

				if( is_array($translations) && !empty($translations) ){
					if( !is_array($context['fields']['translations']) ){
						$context['fields']['translations'] = array();
					}

					foreach( $translations as $translation ){
						$handle = $translation->getHandle();

						$options[] = array(
							$handle, in_array($handle, $context['fields']['translations']), $translation->meta()->get('name')
						);
					}
				}
			}

			$label->appendChild(Widget::Select('fields[translations][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);

			$fieldset->appendChild($group);
			$context['form']->appendChild($fieldset);
		}

		/**
		 * Prepare Translations select data for DB insertion
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreCreate(array $context){
			// prepare translations data for DB insert
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : NULL;
		}

		/**
		 * On page creation, add a Translation in all Translation Folders
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePostCreate(array $context){
			TManager::createTranslation(
				array(
					'id' => $context['page_id'],
					'handle' => $context['fields']['handle'],
					'parent' => $context['fields']['parent']
				)
			);
		}

		/**
		 * On changing page handle, update corresponding Translations.
		 * Translation Name gets broken. Fix in @see dPagePostEdit
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreEdit(array $context){
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : '';

			$current_page = Symphony::Database()->fetchRow(0, "SELECT `handle`, `parent` FROM `tbl_pages` WHERE id = '{$context['page_id']}' LIMIT 1");

			// update translation filenames if needed
			if( ($context['fields']['handle'] != $current_page['handle']) || ($context['fields']['parent'] != $current_page['parent']) ){
				$this->changed_handles = TManager::editTranslation(array(
					'id' => $context['page_id'],
					'old_handle' => $current_page['handle'],
					'new_handle' => $context['fields']['handle'],
					'old_parent' => $current_page['parent'],
					'new_parent' => $context['fields']['parent']
				));

				// FL takes care of Translations
				unset($context['fields']['translations']);
			}
		}

		/**
		 * After changing page handle fix Translations' Names.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePostEdit($context){
			foreach( TManager::getFolders() as $t_folder ){
				// change all
				if( !empty($this->changed_handles) ){
					foreach( $this->changed_handles as $new_handle ){
						$translation = $t_folder->getTranslation($new_handle);
						if( !is_null($translation) ) $translation->setName();
					}
				}
				else{
					$translation = $t_folder->getTranslation(TManager::getPageHandle($context['page_id']));
					if( !is_null($translation) ) $translation->setName();
				}
			}
		}

		/**
		 * On deleting one or more pages, delete corresponding Translations.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreDelete(array $context){
			TManager::deleteTranslation((array) $context['page_ids']);
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  System preferences  */
		/*------------------------------------------------------------------------------------------------*/

		/**
		 * Display options on Preferences page.
		 *
		 * @param array $context
		 */
		public function dAddCustomPreferenceFieldsets(array $context){
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __(FL_NAME)));

			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->_appendLangs($div, $context);
			$this->_appendMainLang($div, $context);
			$group->appendChild($div);

			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->_appendStorageFormat($div, $context);
			$this->_appendRefLang($div, $context);
			$group->appendChild($div);

			$this->_appendConsolidate($group);
			$this->_appendUpdate($group);

			$context['wrapper']->appendChild($group);
		}

		/**
		 * Convenience method; builds language codes input
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendLangs(&$wrapper, $context){
			$label = Widget::Label(__('Language codes'), null, 'column', FL_GROUP.'_langs');
			$label->appendChild(Widget::Input('settings['.FL_GROUP.'][langs]', implode(',', FLang::getLangs())));
			$label->appendChild(new XMLElement('p', __('Comma separated list of supported language codes.'), array('class' => 'help')));

			if( isset($context['errors'][FL_GROUP]['langs']) ){
				$wrapper->appendChild(Widget::Error($label, $context['errors'][FL_GROUP]['langs']));
			}
			else{
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Convenience method; builds main language select
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendMainLang(&$wrapper, $context){
			$label = Widget::Label(__('Main language'), null, 'column', FL_GROUP.'_main_lang');
			$main_lang = FLang::getMainLang();
			$all_languages = FLang::getAllLangs();

			$options = array();
			foreach( FLang::getLangs() as $lang_code ){
				$options[] = array($lang_code, ($lang_code == $main_lang), $all_languages[$lang_code]);
			}

			$label->appendChild(Widget::Select('settings['.FL_GROUP.'][main_lang]', $options));
			$label->appendChild(new XMLElement('p', __('Select the main language of the site.'), array('class' => 'help')));

			if( isset($context['errors'][FL_GROUP]['main_lang']) ){
				$wrapper->appendChild(Widget::Error($label, $context['errors'][FL_GROUP]['main_lang']));
			}
			else{
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Convenience method; builds reference language select
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendRefLang(&$wrapper, $context){
			$label = Widget::Label(__('Reference language'), null, 'column', FL_GROUP.'_ref_lang');
			$ref_lang = TManager::getRefLang();
			$all_langs = FLang::getAllLangs();

			$options = array();
			foreach( FLang::getLangs() as $lang ){
				$options[] = array($lang, ($lang == $ref_lang), $all_langs[$lang]);
			}

			$label->appendChild(Widget::Select('settings['.FL_GROUP.'][ref_lang]', $options));
			$label->appendChild(new XMLElement('p', __('Language translations that will be used as reference when updating other languages\' translations.'), array('class' => 'help')));

			if( isset($context['errors'][FL_GROUP]['ref_lang']) ){
				$wrapper->appendChild(Widget::Error($label, $context['errors'][FL_GROUP]['ref_lang']));
			}
			else{
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Convenience method; builds storage format select
		 *
		 * @param XMLElement &$wrapper
		 * @param array      $context
		 */
		private function _appendStorageFormat(&$wrapper, $context){
			$label = Widget::Label(__('Default storage format'), null, 'column');

			$current_storage_format = Symphony::Configuration()->get('storage_format', FL_GROUP);

			$options = array();
			foreach( TManager::getSupportedStorageFormats() as $storage_format => $info ){
				$options[] = array(
					$storage_format,
					($storage_format == $current_storage_format),
					$info['description']
				);
			}

			$label->appendChild(Widget::Select('settings['.FL_GROUP.'][storage_format]', $options));
			$label->appendChild(new XMLElement('p', __('Storage format to use for translations.'), array('class' => 'help')));

			if( isset($context['errors'][FL_GROUP]['storage_format']) ){
				$wrapper->appendChild(Widget::Error($label, $context['errors'][FL_GROUP]['storage_format']));
			}
			else{
				$wrapper->appendChild($label);
			}
		}

		/**
		 * Convenience method; builds consolidate translations checkbox
		 *
		 * @param XMLElement (reference) $wrapper
		 */
		private function _appendConsolidate(&$wrapper){
			$checkbox = Widget::Input('settings['.FL_GROUP.'][consolidate]', self::CHECKBOX_YES, 'checkbox');
			if( Symphony::Configuration()->get('consolidate', FL_GROUP) == self::CHECKBOX_YES ){
				$checkbox->setAttribute('checked', 'checked');
			}
			$label = Widget::Label($checkbox->generate().' '.__('Consolidate translations'));
			$label->appendChild(new XMLElement('p', __('Check this to preserve Translations for removed languages.'), array('class' => 'help')));
			$wrapper->appendChild($label);
		}

		/**
		 * Convenience method; builds update folders button
		 *
		 * @param XMLElement (reference) $wrapper
		 */
		private function _appendUpdate(&$wrapper){
			$button = new XMLElement('span', NULL, array('class' => 'frame'));
			$button->appendChild(new XMLElement('button', __('Update Translations'), array('name' => 'action['.FL_GROUP.'][update_translations]', 'type' => 'submit')));
			$wrapper->appendChild($button);
		}

		/**
		 * Handle custom preferences actions
		 */
		public function dCustomActions(){
			if( isset($_POST['action'][FL_GROUP]['update_translations']) ){
				TManager::updateFolders();
			}
		}

		/**
		 * Save options from Preferences page
		 *
		 * @param array $context
		 *
		 * @return boolean
		 */
		public function dSave(array $context){

			$valid = true;

			/* Language codes */

			$old_langs = FLang::getLangs();
			if( !FLang::setLangs($context['settings'][FL_GROUP]['langs']) ){
				$context['errors'][FL_GROUP]['langs'] = __('Please fill at least one valid language code.');
				$valid = false;
			}
			$new_langs = FLang::getLangs();
			unset($context['settings'][FL_GROUP]['langs']);
			Symphony::Configuration()->set('langs', implode(',', $new_langs), FL_GROUP);


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
				'context' => $context,
				'old_lang' => $old_langs,
				'new_langs' => $new_langs
			));


			/* Main language */

			if( !FLang::setMainLang($context['settings'][FL_GROUP]['main_lang']) ){

				if( !empty($old_langs) || !(isset($new_langs[0]) && FLang::setMainLang($new_langs[0])) ){
					$context['errors'][FL_GROUP]['main_lang'] = __('Invalid language code.');
					$valid = false;
				}
			}
			$main_lang = FLang::getMainLang();
			unset($context['settings'][FL_GROUP]['main_lang']);
			Symphony::Configuration()->set('main_lang', $main_lang, FL_GROUP);


			/* Reference language */

			if( !TManager::setRefLang($context['settings'][FL_GROUP]['ref_lang']) ){

				if( !empty($old_langs) || !TManager::setRefLang($main_lang) ){
					$context['errors'][FL_GROUP]['main_lang'] = __('Invalid language code.');
					$valid = false;
				}
			}
			unset($context['settings'][FL_GROUP]['ref_lang']);
			Symphony::Configuration()->set('ref_lang', TManager::getRefLang(), FL_GROUP);


			/* Storage format */

			if( !TManager::setStorageFormat($context['settings'][FL_GROUP]['storage_format']) ){

				// make sure existing storage format is valid
				if( !TManager::validateStorageFormat( TManager::getStorageFormat() ) ){
					TManager::setStorageFormat('xml');
				}

				$context['errors'][FL_GROUP]['storage_format'] = __('Invalid storage format.');
				$valid = false;
			}
			unset($context['settings'][FL_GROUP]['storage_format']);
			Symphony::Configuration()->set('storage_format', TManager::getStorageFormat(), FL_GROUP);


			/* Consolidate */

			$old_consolidate = Symphony::Configuration()->get('consolidate', FL_GROUP);
			$new_consolidate = $context['settings'][FL_GROUP]['consolidate'];

			if( $old_consolidate != $new_consolidate ){
				unset($context['settings'][FL_GROUP]['consolidate']);
				Symphony::Configuration()->set('consolidate', $new_consolidate, FL_GROUP);
			}


			/* Manage Translation folders */

			// update translation folders for new languages
			$added_languages = array_diff($new_langs, $old_langs);
			if( !empty($added_languages) ){
				TManager::updateFolders($added_languages);
			}

			// delete translation folders for deleted languages
			$deleted_languages = array_diff($old_langs, $new_langs);
			if( !empty($deleted_languages) && ($new_consolidate != self::CHECKBOX_YES) ){
				TManager::deleteFolders($deleted_languages);
			}


			Symphony::Configuration()->write();

			return $valid;
		}



		/*------------------------------------------------------------------------------------------------*/
		/*  Public utilities  */
		/*------------------------------------------------------------------------------------------------*/

		public static function appendAssets(){
			if( self::$assets_loaded === false
				&& class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage ){

				self::$assets_loaded = true;

				$page = Administration::instance()->Page;

				$page->addStylesheetToHead(URL.'/extensions/'.FL_GROUP.'/assets/'.FL_GROUP.'.multilingual_tabs.css', 'screen', null, false);
				$page->addScriptToHead(URL.'/extensions/'.FL_GROUP.'/assets/'.FL_GROUP.'.multilingual_tabs.js', null, false);
				$page->addScriptToHead(URL.'/extensions/'.FL_GROUP.'/assets/'.FL_GROUP.'.multilingual_tabs_init.js', null, false);
			}
		}

	}
