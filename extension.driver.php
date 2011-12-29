<?php
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once('lib/class.TManager.php');
	require_once('lib/class.FLang.php');
	
	
	
	define_safe(FRONTEND_LOCALISATION_NAME, 'Frontend Localisation');
	define_safe(FRONTEND_LOCALISATION_GROUP, 'frontend_localisation');
	
	
	
	final class extension_frontend_localisation extends Extension {

		public function about(){
			return array(
					'name' => FRONTEND_LOCALISATION_NAME,
					'version' => '1.1',
					'release-date' => '2011-12-29',
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
					'description' => __('Offers a frontend localisation mechanism using XML, GNU PO or JAVA Style formats.')
			);
		}
		
		
		
		const CHECKBOX_YES = 'yes';
		
		
		
		public function install() {
			// depends on a Language Driver
			if( FLang::instance()->ld() == null ){
				return false;
			}
			
			/* Database */
			try {
				Symphony::Database()->query("ALTER TABLE `tbl_pages` ADD `translations` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `events`");
			} catch (DatabaseException $dbe){
				// column already exists
				if( $dbe->getDatabaseErrorCode() == 1060 ){
					$message = __('<code>%1$s</code>: Column `translation` for `tbl_pages` already exists. Uninstall extension and re-install it after.', array(FRONTEND_LOCALISATION_NAME));
				}
				// other errors
				else{
					$message = __("<code>%1$s</code>: MySQL error %d occured when adding column `translation` to `tbl_pages`. Installation aborted.", array(FRONTEND_LOCALISATION_NAME, $dbe['_error']['num']));
				}
				
				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
				
				return false;
			} catch (Exception $e){}
			
			/* Configuration */
			Symphony::Configuration()->set('language_driver', FLang::instance()->getFLDriverClass(), FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('reference_language', FLang::instance()->referenceLanguage(), FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('translation_path', '/translations', FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('page_name_prefix', 'p_', FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('storage_format', 'xml', FRONTEND_LOCALISATION_GROUP);
			Symphony::Configuration()->set('consolidate_translations', self::CHECKBOX_YES, FRONTEND_LOCALISATION_GROUP);
			
			Administration::instance()->saveConfig();
			
			/* Translations */
			General::realiseDirectory(WORKSPACE . Symphony::Configuration()->get('translation_path', FRONTEND_LOCALISATION_GROUP));
			
			/* Update existing translations */
			TManager::instance()->updateFolders();
			
			return true;
		}
		
		public function uninstall() {
			/* Translations */
			if (Symphony::Configuration()->get('consolidate_translations',FRONTEND_LOCALISATION_GROUP) != self::CHECKBOX_YES) {
				/* @todo To be replaced with */
				// General::deleteDirectory(DOCROOT . $this->translation_path);
				/*  in Symphony 2.3 */
				
				$translation_path = Symphony::Configuration()->get('translation_path',FRONTEND_LOCALISATION_GROUP);
				
				if( is_dir(WORKSPACE . $translation_path) && !empty($translation_path) ){
					TManager::deleteFolder(WORKSPACE . $translation_path);
				}
			}
			
			/* Database */
			try {
				Symphony::Database()->query("ALTER TABLE `tbl_pages` DROP `translations`");
			} catch (Exception $e){
				$message = __('<code>%1$s</code>: Failed to remove `translation` column from `tbl_pages`. Perhaps it didn\'t existed at all.', array(FRONTEND_LOCALISATION_NAME));
				
				Administration::instance()->Page->pageAlert($message, Alert::ERROR);
				Symphony::$Log->pushToLog($message, E_NOTICE, true);
			}
			
			/* Configuration */
			Symphony::Configuration()->remove(FRONTEND_LOCALISATION_GROUP);
			Administration::instance()->saveConfig();
			
			return true;
		}
		
		
		
		public function fetchNavigation() {
			return array(
				array(
					'location'	=> __('Translations'),
					'name'		=> __('Translations'),
					'link'		=> '/'
				),
			);
		}
		
		
		
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
			
			$translations = TManager::instance()->getFolder( Symphony::Configuration()->get('reference_language','frontend_localisation') )->getTranslations();
			$options = array();

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

			$label->appendChild(Widget::Select('fields[translations][]', $options, array('multiple' => 'multiple')));
			$group->appendChild($label);
			
			$fieldset->appendChild($group);
			$context['form']->appendChild($fieldset);
		}
		
		/**
		 * Prepare Translations select data for DB insert.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreCreate(array $context){
			// prepare translations data for DB insert
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : NULL;
		}
		
		/**
		 * On page creation, add a Translation in all Translation Folders.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePostCreate(array $context){
			TManager::instance()->createTranslation(
				array(
					'id' => $context['page_id'],
					'handle' => $context['fields']['handle'],
					'parent' => $context['fields']['parent']
				)
			);
		}
		
		/**
		 * On changing page handle, update corresponding Translations.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreEdit(array $context){
			$context['fields']['translations'] = is_array($context['fields']['translations']) ? implode(',', $context['fields']['translations']) : '';
			
			$current_page = Symphony::Database()->fetchRow(0, "SELECT `handle`, `parent` FROM `tbl_pages` WHERE id = '{$context['page_id']}' LIMIT 1");
			
			// update translation filenames if needed
			if( ($context['fields']['handle'] != $current_page['handle']) || ($context['fields']['parent'] != $current_page['parent']) ){
				TManager::instance()->editTranslation(array(
						'id' => $context['page_id'],
						'old_handle' => $current_page['handle'],
						'new_handle' => $context['fields']['handle'],
						'old_parent' => $current_page['parent'],
						'new_parent' => $context['fields']['parent']
				));
			}
		}
		
		/**
		 * On deleting one or more pages, delete corresponding Translations.
		 *
		 * @param array $context - see delegate description
		 */
		public function dPagePreDelete(array $context){
			TManager::instance()->deleteTranslation($context['page_ids']);
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
			
			$group->appendChild( $this->_addFLDriver() );
			$group->appendChild(new XMLElement('p', __('The Symphony extension that drives your frontend language management.'), array('class' => 'help')));
			
			$group->appendChild( $this->_addReferenceLanguage() );
			$group->appendChild(new XMLElement('p', __('Language translations that will be used as reference when updating other languages translations.'), array('class' => 'help')));
			
			$group->appendChild( $this->_addStorageFormat() );
			$group->appendChild(new XMLElement('p', __('Storage format to use for translations.'), array('class' => 'help')));
			
			$consolidate_checkbox = Widget::Input('settings['.FRONTEND_LOCALISATION_GROUP.'][consolidate_translations]', self::CHECKBOX_YES, 'checkbox');
			if(Symphony::Configuration()->get('consolidate_translations', FRONTEND_LOCALISATION_GROUP) == self::CHECKBOX_YES) { $consolidate_checkbox->setAttribute('checked', 'checked'); }
			$group->appendChild(Widget::Label($consolidate_checkbox->generate() . ' ' . __('Consolidate translations')));
			$group->appendChild(new XMLElement('p', __('Check this to preserve Translations for languages being removed by <code>Language driver</code>.'), array('class' => 'help')));
			
			$update_folders = new XMLElement('span', NULL, array('class' => 'frame'));
			$update_folders->appendChild(new XMLElement('button', __('Update Translations'), array('name' => 'action['.FRONTEND_LOCALISATION_GROUP.'][update_translations]', 'type' => 'submit')));
			$group->appendChild($update_folders);
			
// 			$convert = new XMLElement('span', NULL, array('class' => 'frame'));
// 			$convert->appendChild(new XMLElement('button', __('Convert XML to Translations'), array('name' => 'action['.FRONTEND_LOCALISATION_GROUP.'][convert]', 'type' => 'submit')));
// 			$group->appendChild($convert);
			
// 			$convert = new XMLElement('span', NULL, array('class' => 'frame'));
// 			$convert->appendChild(new XMLElement('button', __('Convert Translations to 1.0'), array('name' => 'action['.FRONTEND_LOCALISATION_GROUP.'][convert_to_1.0]', 'type' => 'submit')));
// 			$group->appendChild($convert);
			
			$context['wrapper']->appendChild($group);
		}
		
		/**
		 * On preferences page, personalize custom form actions.
		 */
		public function dCustomActions(){
			if( isset($_POST['action'][FRONTEND_LOCALISATION_GROUP]['update_translations']) ){
				TManager::instance()->updateFolders();
			}
			
			// This is here to ensure compatibility with XS_XML_PAGES (another extension, precursor of this extension)
			if( isset($_POST['action'][FRONTEND_LOCALISATION_GROUP]['convert']) ){
				$t_folders = TManager::instance()->getFolders();
				
				$all_languages = FLang::instance()->ld()->allLanguages();
				
				foreach( $t_folders as $language_code => $t_folder ){
					$translations = $t_folder->getTranslations();
					
					foreach( $translations as $translation ){
						$old_dom = $translation->getContentXML();
						
						$new_dom = new DOMDocument('1.0', 'UTF-8');
						$new_dom->formatOutput = true;
						$new_dom->preserveWhiteSpace = false;
						
						$new_dom_translation = $new_dom->createElement('translation');
						$new_dom_meta = $new_dom->createElement('meta');
						
						$new_dom_meta->appendChild(
							$new_dom->createElement('name', $translation->getHandle())
						);
						
						$new_dom_language = $new_dom->createElement('language', $all_languages[$language_code]);
						$new_dom_language->setAttribute('code', $language_code);
						$new_dom_language->setAttribute('handle', Lang::createHandle($all_languages[$language_code]));
						$new_dom_meta->appendChild($new_dom_language);
						
						$new_dom_translation->appendChild( $new_dom_meta );
						
						$new_dom_data = $new_dom->createElement('data');
						
						foreach( $old_dom->childNodes->item(0)->childNodes as $old_data_child ){
							if( $old_data_child instanceof DOMElement ){
								if( ($old_data_child->nodeName != 'meta') && ($old_data_child->nodeName != 'language') ) {
									$new_dom_data->appendChild( $new_dom->importNode($old_data_child, true) );
								}
							}
						}
						
						$new_dom_translation->appendChild( $new_dom_data );
						$new_dom->appendChild( $new_dom_translation );
			
						$translation->setContent( $new_dom->saveXML() );
					}
				}
			}
			
			if( isset($_POST['action'][FRONTEND_LOCALISATION_GROUP]['convert_to_1.0']) ){
				$all_languages = FLang::instance()->ld()->allLanguages();
				$langauge_codes = FLang::instance()->ld()->languageCodes();
				
				foreach( $langauge_codes as $language_code ){

					$path = WORKSPACE . '/translations/' . $language_code;
					
					$structure = (array) General::listStructure($path);
					
					if (!empty($structure['filelist'])) {
						foreach ($structure['filelist'] as $filename) {
							$handle = substr($filename,0,-4);
							
							$old_doc = new DOMDocument('1.0', 'UTF-8');
							$old_doc->load($path . '/' . $filename);
							
							$xPath = new DOMXPath($old_doc);
							
							
							// Translation meta
							
							$dom_meta = new DOMDocument('1.0', 'UTF-8');
							$dom_meta->formatOutput = true;
							
							$dom_meta_info = $dom_meta->createElement('meta');
							
							// name
							$dom_meta_info->appendChild(
								$dom_meta->createElement('name', General::sanitize($xPath->query('/translation/meta/name')->item(0)->nodeValue) )
							);
							
							
							// language
							$dom_meta_language = $dom_meta->createElement('language');
							$dom_meta_language->appendChild( $dom_meta->createElement('name', General::sanitize($all_languages[$language_code])) );
							$dom_meta_language->appendChild( $dom_meta->createElement('code', $language_code) );
							$dom_meta_language->appendChild( $dom_meta->createElement('handle', Lang::createHandle($all_languages[$language_code])) );
							$dom_meta_info->appendChild($dom_meta_language);
							
							// storage format
							$dom_meta_info->appendChild(
								$dom_meta->createElement('storage_format', 'xml' )
							);
							
							// type
							$dom_meta_info->appendChild(
								$dom_meta->createElement('type', '' )
							);
							
							$dom_meta->appendChild( $dom_meta_info );
							
							General::writeFile($path.'/'.$handle.'.meta.xml', $dom_meta->saveXML());
							
							
							// Translation data
							
							// 1. convert
							
							$dom_data = new DOMDocument('1.0', 'UTF-8');
							$dom_data->formatOutput = true;
							
							$dom_data_info = $dom_data->createElement('data');
							
							foreach( $xPath->query('/translation/data')->item(0)->childNodes as $old_data_child ){
								if( $old_data_child instanceof DOMElement ){
									$dom_data_info->appendChild( $dom_data->importNode($old_data_child, true) );
								}
							}
							
							$dom_data->appendChild( $dom_data_info );
							
							
							// 2. create CDATAs
							
							unset($xPath);
							$xPath = new DOMXPath($dom_data);
							
							foreach( $xPath->query("//item[ @handle != '' ]") as $item ){
								/* @var $item DOMElement */
								$value = $dom_data->saveXML($item);
								
								// suppress opening element
								$value = substr($value, strpos($value, '>') + 1);
								
								// suppress closing element
								$value = substr($value, 0, strrpos($value, '<'));
								
								$item->nodeValue = '';
								
								/* @var $doc_item_cdata DOMCDATASection */
								$doc_item_cdata = $dom_data->createCDATASection( $value );
								
								$item->appendChild($doc_item_cdata);
							}
							
							General::writeFile($path.'/'.$handle.'.data.xml', $dom_data->saveXML());
							
							
							General::deleteFile($path . '/' . $filename);
						}
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
				
				FLang::instance()->setFLDriver($new_language_driver);
				
				return true;
			}
			
			
			
			/* Check if we should keep translations for removed langauges */
			
			$old_consolidate = Symphony::Configuration()->get('consolidate_translations',FRONTEND_LOCALISATION_GROUP);
			$new_consolidate = $context['settings'][FRONTEND_LOCALISATION_GROUP]['consolidate_translations'];
			
			if ( $old_consolidate != $new_consolidate ) {
				Symphony::Configuration()->set('consolidate_translations', $new_consolidate, FRONTEND_LOCALISATION_GROUP);
			}
			
			
			
			$new_languages = FLang::instance()->ld()->getSavedLanguages($context);
			
			FLang::instance()->ld()->setLanguageCodes($new_languages);
			
			
			
			/* Update Reference Language */
			
			$reference_language = $context['settings'][FRONTEND_LOCALISATION_GROUP]['reference_language'];
			if( !in_array($reference_language, $new_languages) ){
				$reference_language = FLang::instance()->referenceLanguage();
				
				if( !in_array($reference_language, $new_languages) ){
					$reference_language = '';
				}
			}
			
			if( empty($reference_language) ) return true;
			
			Symphony::Configuration()->set('reference_language', $reference_language, FRONTEND_LOCALISATION_GROUP);
			
			
			
			/* Manage translation folders */
			
			$old_languages = FLang::instance()->ld()->languageCodes();
			
			// update translation folders for new languages
			$added_languages = array_diff($new_languages, $old_languages);
			if ( !empty($added_languages) ) {
				TManager::instance()->updateFolders($added_languages);
			}
			
			// delete translation folders for deleted languages
			$deleted_languages = array_diff($old_languages, $new_languages);
			if ( !empty($deleted_languages) && ($new_consolidate != self::CHECKBOX_YES) ) {
				TManager::instance()->deleteFolders($deleted_languages);
			}
			
			
			
			Administration::instance()->saveConfig();
			
			return true;
		}
		
		
		
		/**
		 * Convenience method; builds language_driver selectbox.
		 *
		 * @return XMLElement - selectbox containing supported language drivers
		 */
		private function _addFLDriver() {
			$label = Widget::Label(__('Language driver'));
			
			$options = array();
			foreach( FLang::instance()->getAvailableDrivers() as $handle => $name ){
				$options[] = array($handle, ($handle == FLang::instance()->ld()->getHandle()), $name );
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
			$reference_language = FLang::instance()->referenceLanguage();
			$all_languages = FLang::instance()->ld()->allLanguages();
			
			$options = array();
			foreach (FLang::instance()->ld()->languageCodes() as $language_code) {
				$options[] = array($language_code, ($language_code == $reference_language), $all_languages[$language_code] );
			}
			
			$label->appendChild( Widget::Select('settings['.FRONTEND_LOCALISATION_GROUP.'][reference_language]', $options) );
			
			return $label;
		}

		/**
		 * Convenience method; builds reference_language selectbox.
		 *
		 * @return XMLElement - selectbox containing supported language codes
		 */
		private function _addStorageFormat() {
			$label = Widget::Label(__('Default storage format'));
			
			$current_storage_format = Symphony::Configuration()->get('storage_format', FRONTEND_LOCALISATION_GROUP);
		
			$options = array();
			foreach( TManager::instance()->getSupportedStorageFormats() as $storage_format => $info ){
				$options[] = array(
					$storage_format,
					($storage_format == $current_storage_format),
					$info['description']
				);
			}
		
			$label->appendChild( Widget::Select('settings['.FRONTEND_LOCALISATION_GROUP.'][storage_format]', $options) );
		
			return $label;
		}
	}
