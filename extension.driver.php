<?php
	require_once('lib/class.TranslationManager.php');
	require_once('lib/class.FrontendLanguage.php');

	define_safe(FRONTEND_LOCALISATION_NAME, 'Frontend Localisation');
	define_safe(FRONTEND_LOCALISATION_GROUP, 'frontend_localisation');
	
	final class extension_frontend_localisation extends Extension {

		const CHECKBOX_YES = 'yes';
		
		/**
		 * Manages Translation Tolders and Files.
		 * 
		 * @var TranslationManager
		 */
		private $translation_manager = null;
		
		/**
		 * Cache current language codes for internal use.
		 * 
		 * @var array
		 */
		private $language_codes = array();
		
		/**
		 * Cache reference language for internal use.
		 * 
		 * @var string
		 */
		private $reference_language = '';
		
		/**
		 * Cache translation path for internal use.
		 * 
		 * @var string
		 */
		private $translation_path = '';
		
		
		
		public function about(){
			return array(
				'name' => FRONTEND_LOCALISATION_NAME,
				'version' => '0.2beta',
				'release-date' => '2011-10-24',
				'author' => array(
					array(
						'name' => 'Xander Group',
						'email' => 'symphonycms@xandergroup.ro',
						'website' => 'www.xandergroup.ro'
					),
					array(
						'name' => 'Vlad Ghita',
						'email' => 'vlad.ghita@xandergroup.ro',
					),
				),
				'description' => __('Offers a frontend localisation mechanism using XML files.')
			);
		}
		
		public function __construct($args) {
			if( Symphony::ExtensionManager()->fetchStatus(FRONTEND_LOCALISATION_GROUP) == EXTENSION_ENABLED
			    && FrontendLanguage::instance() == null ){
			    
				$this->language_codes = (array) FrontendLanguage::instance()->languageCodes();
				$this->reference_language = (string) FrontendLanguage::instance()->referenceLanguage();
				$this->translation_path = (string) Symphony::Configuration()->get('translation_path',FRONTEND_LOCALISATION_GROUP);
			
				$this->translation_manager = new TranslationManager(DOCROOT . $this->translation_path);
			}
		}
		
		public function install() {
			// depends on FrontendLanguage
			if( FrontendLanguage::instance() == null ){
				return false;
			}
			
			/* Database */
			try {
				Symphony::Database()->query("ALTER TABLE `tbl_pages` ADD `translations` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `events`");
			} catch (DatabaseException $dbe){
				// column already exists
				if( $dbe->getDatabaseErrorCode() == 1060 ){
					$message = __('<code>%s</code>: Column `translation` for `tbl_pages` already exists. Uninstall extension and then install it.', array(FRONTEND_LOCALISATION_NAME));
				}
				// other errors
				else{
					$message = __("<code>%s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.", array(FRONTEND_LOCALISATION_NAME, $dbe['_error']['num']));
				}
				
				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
				
				return false;
			} catch (Exception $e){}
			
			/* Configuration */
			Symphony::Configuration()->set('language_driver', FrontendLanguage::instance()->getDefaultLanguageDriverName(), FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('reference_language', FrontendLanguage::instance()->referenceLanguage(), FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('translation_path', '/workspace/translations', FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('page_name_prefix', 'pagina_', FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('consolidate_translations', self::CHECKBOX_YES, FRONTEND_LOCALISATION_GROUP);
			
			Administration::instance()->saveConfig();
			
			/* Translations */
			General::realiseDirectory(DOCROOT . Symphony::Configuration()->get('translation_path', FRONTEND_LOCALISATION_GROUP));
			
			$this->translation_manager = new TranslationManager(DOCROOT . '/workspace/translations');
			$this->translation_manager->updateFolders();
			
			return true;
		}
		
		public function uninstall() {
			/* Translations */
			if (Symphony::Configuration()->get('consolidate_translations',FRONTEND_LOCALISATION_GROUP) != self::CHECKBOX_YES) {
				/* @todo To be replaced with */
				// General::deleteDirectory(DOCROOT . $this->translation_path);
				/*  in Symphony 2.3 */
				
				if( !empty($this->translation_path) && is_dir(DOCROOT . $this->translation_path) ){
					TranslationManager::deleteFolder(DOCROOT . $this->translation_path);
				}
			}
			
			/* Database */
			try {
				Symphony::Database()->query("ALTER TABLE `tbl_pages` DROP `translations`");
			} catch (Exception $e){
				$message = __('<code>%s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.', array(FRONTEND_LOCALISATION_NAME));
				
				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
			}
			
			/* Configuration */
			Symphony::Configuration()->remove(FRONTEND_LOCALISATION_GROUP);
			Administration::instance()->saveConfig();
			
			return true;
		}
		
		
		
//		public function fetchNavigation() {
//			return array(
//				array(
//					'location'	=> __('Blueprints'),
//					'name'		=> __('Frontend Translations'),
//					'link'		=> '/frontendtranslations/'
//				),
//			);
//		}
		
		public function getSubscribedDelegates(){
			return array(
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
					'callback' => 'dSavePreferences' 
				),
			);
		}
		
		
		
		/**
		 * Append Translations select to Page edit menu.
		 * 
		 * @param array $context - see delegate description
		 */
		public function dAppendPageContent(array $context){
			// prepare translations array
			$context['fields']['translations'] = preg_split('/,/i', $context['fields']['translations'], -1, PREG_SPLIT_NO_EMPTY);
			
			// generate select
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'settings');
			$fieldset->appendChild(new XMLElement('legend', __('Frontend Translations')));
			
			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');
			
			$label = Widget::Label(__('Translations'));
			
			$t_files = $this->translation_manager->getFolder( Symphony::Configuration()->get('reference_language','frontend_localisation') )->getFiles();
			$options = array();

			if( is_array($t_files) && !empty($t_files) ){
				if( !is_array($context['fields']['translations']) ){
					$context['fields']['translations'] = array();
				}
				
				foreach( $t_files as $t_file ){
					$filename = $t_file->getFilename();
					
					$options[] = array(
						$filename, in_array($filename, $context['fields']['translations']), $filename
					);
				}
			}

			$label->appendChild(Widget::Select('fields[translations][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			$context['form']->appendChild($fieldset);
		}
		
		/**
		 * Triggered before page data is saved to DB.
		 * 
		 * @param array $context - see delegate description
		 */
		public function dPagePreCreate(array $context){
			// prepare translations data for DB insert
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : NULL;
		}
		
		/**
		 * On page creation, add a Translation File in all Translation Folders.
		 * 
		 * @param array $context - see delegate description
		 */
		public function dPagePostCreate(array $context){
			$this->translation_manager->createTranslationFile(
				array(
					'id' => $context['page_id'],
					'handle' => $context['fields']['handle'],
					'parent' => $context['fields']['parent']
				)
			);
		}
		
		/**
		 * On changing page handle, update corresponding Translation Files.
		 * 
		 * @param array $context - see delegate description
		 */
		public function dPagePreEdit(array $context){
			// prepare translations data for DB insert
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : NULL;
			
			// update translation filenames if needed
			$current_page = Symphony::Database()->fetchRow(0, "SELECT `handle` FROM `tbl_pages` WHERE id = '{$context['page_id']}' LIMIT 1");
			
			if ( $context['fields']['handle'] != $current_page['handle'] ) {
				$this->translation_manager->editTranslationFiles(array(
						'id' => $context['page_id'],
						'old_handle' => $current_page['handle'],
						'new_handle' => $context['fields']['handle'],
						'parent' => $context['fields']['parent']
				));
			}
		}
		
		/**
		 * On deleting one or more pages, deleting corresponding Translation Files.
		 * 
		 * @param array $context - see delegate description
		 */		
		public function dPagePreDelete(array $context){
			$this->translation_manager->deleteTranslationFiles($context['page_ids']);
		}
		
		/**
		 * On preferences page add extension settings.
		 * 
		 * @param array $context - see delegate description
		 */
		public function dAddCustomPreferenceFieldsets(array $context) {
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __(FRONTEND_LOCALISATION_NAME)));
			
			$group->appendChild( $this->_addLanguageDriver() );
			$group->appendChild(new XMLElement('p', __('The Symphony extension that drives your frontend langauge management.'), array('class' => 'help')));
			
			$group->appendChild( $this->_addReferenceLanguage() );
			$group->appendChild(new XMLElement('p', __('Language translations that will be used as reference when updating other languages translations.'), array('class' => 'help')));
			
			$group->appendChild( $this->_addTranslationPath() );
			$group->appendChild(new XMLElement('p', __('Path where you would like to store translations.'), array('class' => 'help')));
			
			$prefix_label = Widget::Label(__('Pages prefix'));
			$prefix_label->appendChild(Widget::Input('settings['.FRONTEND_LOCALISATION_GROUP.'][page_name_prefix]', Symphony::Configuration()->get('page_name_prefix',FRONTEND_LOCALISATION_GROUP)));
			$group->appendChild($prefix_label);
			$group->appendChild(new XMLElement('p', __('This prefix will be added for Symphony Pages translations for easier management. e.g.: <code>pagina_cotact.xml</code>'), array('class' => 'help')));
			
			$consolidate_checkbox = Widget::Input('settings['.FRONTEND_LOCALISATION_GROUP.'][consolidate_translations]', self::CHECKBOX_YES, 'checkbox');
			if(Symphony::Configuration()->get('consolidate_translations', FRONTEND_LOCALISATION_GROUP) == self::CHECKBOX_YES) { $consolidate_checkbox->setAttribute('checked', 'checked'); }
			$group->appendChild(Widget::Label($consolidate_checkbox->generate() . ' ' . __('Consolidate translations')));
			$group->appendChild(new XMLElement('p', __('Check this to preserve translation files for languages being removed by <code>Language driver</code>.'), array('class' => 'help')));
			
			$update_folders = new XMLElement('span', NULL, array('class' => 'frame'));
			$update_folders->appendChild(new XMLElement('button', __('Update translation files'), array('name' => 'action['.FRONTEND_LOCALISATION_GROUP.'][update_files]', 'type' => 'submit')));
			$group->appendChild($update_folders);
			
//			$convert = new XMLElement('span', NULL, array('class' => 'frame'));
//			$convert->appendChild(new XMLElement('button', __('Convert XML to Translations'), array('name' => 'action['.FRONTEND_LOCALISATION_GROUP.'][convert]', 'type' => 'submit')));
//			$group->appendChild($convert);
			
			$context['wrapper']->appendChild($group);
		}
		
		/**
		 * On preferences page, personalize custom form actions.
		 */
		public function dCustomActions(){
			if( isset($_POST['action'][FRONTEND_LOCALISATION_GROUP]['update_files']) ){
				$this->translation_manager->updateFolders();
			}
			
			// This is here to ensure compatibility with XS_XML_PAGES
			if( isset($_POST['action'][FRONTEND_LOCALISATION_GROUP]['convert']) ){
				$t_folders = $this->translation_manager->getFolders();
				
				$all_languages = FrontendLanguage::instance()->allLanguages();
				
				foreach( $t_folders as $t_folder ){
					$t_files = $t_folder->getFiles();
					
					$all_languages = (array) FrontendLanguage::instance()->allLanguages();
					$language_code = $t_folder->getLanguageCode();
					
					foreach( $t_files as $t_file ){
						$old_dom = $t_file->getContentXML();
			
						$old_dom_first_node = $old_dom->childNodes->item(0);
						$old_dom_language = $old_dom_first_node->getElementsByTagName('language')->item(0);
						
						$translated = 'no';
						
						if( !empty($old_dom_language) && $old_dom_language instanceof DOMElement ){
							$translated = ($old_dom_language->getAttribute('translated') === 'yes') ? 'yes' : 'no';
						}
						
						$new_dom = new DOMDocument('1.0', 'UTF-8');
						$new_dom->formatOutput = true;
						$new_dom->preserveWhiteSpace = false;
						
						$new_dom_data = $new_dom->createElement('data');
						
						$new_dom_meta = $new_dom->createElement('meta');
						
						$new_dom_translated = $new_dom->createElement('translated', $translated);
						$new_dom_meta->appendChild($new_dom_translated);
						
						$new_dom_language = $new_dom->createElement('language', $all_languages[$language_code]);
						$new_dom_language->setAttribute('code', $language_code);
						$new_dom_language->setAttribute('handle', Lang::createHandle($all_languages[$language_code]));
						$new_dom_meta->appendChild($new_dom_language);
						
						$new_dom_data->appendChild( $new_dom_meta );
							
						foreach( $old_dom->childNodes->item(0)->childNodes as $old_data_child ){
							if( $old_data_child instanceof DOMElement ){
								if( $old_data_child->nodeName != 'meta' && $old_data_child->nodeName != 'language' ) {
									$new_dom_data->appendChild( $new_dom->importNode($old_data_child, true) );
								}
							}
						}
						
						$new_dom->appendChild( $new_dom_data );
			
						$t_file->setContent( $new_dom->saveXML() );
					}
				}
			}
		}
		
		/**
		 * On preferences page, take custom actions when saving preferences.
		 * 
		 * @param $context - see delegate description
		 */
		public function dSavePreferences(array $context) {
			
			/* First check language driver. If it changed, other settings are irrelevant */
			
			$old_language_driver = Symphony::Configuration()->get('language_driver',FRONTEND_LOCALISATION_GROUP);
			$new_language_driver = $context['settings'][FRONTEND_LOCALISATION_GROUP]['language_driver'];
			
			if ( $old_language_driver != $new_language_driver ) {
				Symphony::Configuration()->set('language_driver', $new_language_driver, FRONTEND_LOCALISATION_GROUP);
				Symphony::Configuration()->set('reference_language', '', FRONTEND_LOCALISATION_GROUP);
				Administration::instance()->saveConfig();
				
				FrontendLanguage::instance()->setLanguageDriver($new_language_driver);
				
				return true;
			}
			
			
			/* Change Translations folder */
			
			$old_translation_path = Symphony::Configuration()->get('translation_path',FRONTEND_LOCALISATION_GROUP);
			$new_translation_path = $context['settings'][FRONTEND_LOCALISATION_GROUP]['translation_path'];
			
			if ( $old_translation_path != $new_translation_path ) {
				Symphony::Configuration()->set('translation_path', $new_translation_path, FRONTEND_LOCALISATION_GROUP);
				
				/* @todo
				 * change folders here from $old_translation_path to $new_translation_path
				 * on changing to a new folder, existing <<lang_code>> folders will be DELETED
				 * 
				 * This will do nothing atm. Perhaps Symphony will provide a general method to copy one folder to another.
				 */
				// $this->translation_manager->changeTranslationLocation($old_translation_path, $new_translation_path);
			}
			
			
			/* Change prefix for pages Translations */
			
			$old_prefix = Symphony::Configuration()->get('page_name_prefix',FRONTEND_LOCALISATION_GROUP);
			$new_prefix = $context['settings'][FRONTEND_LOCALISATION_GROUP]['page_name_prefix'];
			
			if ( $old_prefix != $new_prefix ) {
				if( $this->translation_manager->changeFilenamesPrefix($old_prefix, $new_prefix) ){
					Symphony::Configuration()->set('page_name_prefix', $new_prefix, FRONTEND_LOCALISATION_GROUP);
				}
				else{
					$message = __('<code>%s</code>: couldn\'t change prefix for pages\' Translation Files.', array(FRONTEND_LOCALISATION_NAME));
					Administration::instance()->Page->pageAlert($message, Alert::ERROR);
					$context['errors'][] = $message;
				}
			}
			
				
			/* Check if we should keep translations for removed langauges */
			
			$old_consolidate = Symphony::Configuration()->get('consolidate_translations',FRONTEND_LOCALISATION_GROUP);
			$new_consolidate = $context['settings'][FRONTEND_LOCALISATION_GROUP]['consolidate_translations'];
			
			if ( $old_consolidate != $new_consolidate ) {
				Symphony::Configuration()->set('consolidate_translations', $new_consolidate, FRONTEND_LOCALISATION_GROUP);
			}
			
			
			$new_languages = FrontendLanguage::instance()->savedLanguages($context);
			
			
			/* Update Reference Language */
			
			$reference_language = $context['settings'][FRONTEND_LOCALISATION_GROUP]['reference_language'];
			if( !in_array($reference_language, $new_languages) ){
				$reference_language = FrontendLanguage::instance()->referenceLanguage();
			}
			
			if( empty($reference_language) ) return true;
			
			Symphony::Configuration()->set('reference_language', $reference_language, FRONTEND_LOCALISATION_GROUP);
			
			
			/* Manage translation folders */
			
			// update translation folders for new languages
			$added_languages = array_diff($new_languages, $this->language_codes);
			if ( !empty($added_languages) ) {
				$this->translation_manager->updateFolders($added_languages);
			}
			
			// delete translation folders for deleted languages
			$deleted_languages = array_diff($this->language_codes, $new_languages);
			if ( !empty($deleted_languages) && ($new_consolidate != self::CHECKBOX_YES) ) {
				$this->translation_manager->deleteFolders($deleted_languages);
			}
			
			
			Administration::instance()->saveConfig();
			
			return true;
		}
		
		
		
		/**
		 * Convenience method; builds language_driver selectbox.
		 * 
		 * @return XMLElement - selectbox containing supported language drivers
		 */
		private function _addLanguageDriver() {
			$label = Widget::Label(__('Language driver'));
			
			$options = array();
			foreach (FrontendLanguage::instance()->getAvailableLanguageDrivers() as $driver_name) {
				$options[] = array($driver_name, ($driver_name == FrontendLanguage::instance()->driverName() ), $driver_name );
			}
			
			$label->appendChild( Widget::Select('settings['.FRONTEND_LOCALISATION_GROUP.'][language_driver]', $options) );
			
			return $label;
		}
		
		/**
		 * Convenience method; builds reference_language selectbox.
		 * 
		 * @return XMLElement - selectbox containing supported language codes
		 */
		private function _addReferenceLanguage() {
			$label = Widget::Label(__('Reference language'));
			
			$options = array();
			foreach ($this->language_codes as $language_code) {
				$options[] = array($language_code, ($language_code == $this->reference_language), $language_code );
			}
			
			$label->appendChild( Widget::Select('settings['.FRONTEND_LOCALISATION_GROUP.'][reference_language]', $options) );
			
			return $label;
		}
		
		/**
		 * Convenience method; builds translation_path selectbox.
		 * 
		 * @return XMLElement - selectbox containing allowed workspace directories
		 */
		private function _addTranslationPath() {
			$ignore = array(
				'/workspace/events',
				'/workspace/data-sources',
				'/workspace/text-formatters',
				'/workspace/pages',
				'/workspace/utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, null, true, DOCROOT, $ignore);

			$label = Widget::Label(__('Translation path'));

			$options = array();
			$options[] = array('/workspace', false, '/workspace');
			if(!empty($directories) && is_array($directories)){
				foreach($directories as $d) {
					$d = '/' . trim($d, '/');
					if(!in_array($d, $ignore)) $options[] = array($d, (Symphony::Configuration()->get('translation_path',FRONTEND_LOCALISATION_GROUP) == $d), $d);
				}
			}

			$label->appendChild( Widget::Select('settings['.FRONTEND_LOCALISATION_GROUP.'][translation_path]', $options) );
			
			return $label;
		}
		
	}
