<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TForm.php');
	
	
	
	class contentExtensionFrontend_localisationEdit extends AdministrationPage {
		
		/**
		 * Translation form.
		 *
		 * @var TForm
		 */
		private $t_form = null;
		
		
		
		public function __construct($parent){
			parent::__construct($parent);
			
			$this->t_form = new TForm();
			$this->_errors = array();
			
			$this->addScriptToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.content.js', 203, false);
			$this->addStylesheetToHead(URL . '/extensions/frontend_localisation/assets/frontend_localisation.content.css', "screen");
		}
		
		
		
		/**
		 * Displays the form for a Translation.
		 *
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::view()
		 */
		public function view() {
			$this->setPageType('form');
			
			// If we're editing, make sure the item exists
			
			if( $this->_context[0] ){
				$translations = TManager::instance()->getFolder( FLang::instance()->referenceLanguage() )->getTranslations();
				
				if( !$handle = $this->_context[0] ) redirect(URL . '/symphony/extension/frontend_localisaion');
				
				if( !array_key_exists($handle, $translations) || empty($translations[$handle]) || !($translations[$handle] instanceof Translation) ){
					$this->_Parent->customError(
						__('Translation not found'),
						__('The translation file you requested to edit does not exist.'),
						'error',
						array(
							'header' => 'HTTP/1.0 404 Not Found'
						)
					);
				}
			}
			
			
			// sync file across languages. On fail, abort edit.
			
			if( !TManager::instance()->syncTranslation($handle) ){
				$this->pageAlert(
					__('Translations synchronisation failed. Please contact site administrator.'),
					Alert::NOTICE
				);
				
				return false;
			}
			
			
			// Status message
			
			if( isset($this->_context[1]) ){
				$this->pageAlert(
					__(
						'%1$s %2$s at %3$s. <a href="%4$s" accesskey="c">Create another?</a> <a href="%5$s" accesskey="a">%6$s</a>',
						array(
							__('Translation'),
							($this->_context[1] == 'saved' ? __('updated') : __('created')),
							DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__),
							URL . '/symphony/extension/frontend_localisation/new/',
							URL . '/symphony/extension/frontend_localisation/',
							__('View all Translations')
						)
					),
					Alert::SUCCESS
				);
			}
			
			
			// Find values
			
			$fields = array();
			
			if( isset($_POST['fields']) ){
				$fields = $_POST['fields'];
			}
			
			elseif( $this->_context[0] ){
				
				$fields['handle'] = $handle;
				$fields['translations'] = array();
				
				$t_linker = new TLinker();
				
				foreach( array_keys( $t_linker->getLinkedPages($handle) ) as $page_id ){
					$fields['pages'][] = $page_id;
				}
				
				foreach( TManager::instance()->getFolders() as $language_code => $t_folder ){
					/* @var $t_folder TFolder */
					$translation = $t_folder->getTranslation( $handle );
					
					$fields['name'][$language_code] = $translation->meta()->get('name');
					$fields['translations'][$language_code] = $translation->getParser()->asTArray($translation);
				}
			}
			
			
			// Start building the page
			
			$reference_language = FLang::instance()->referenceLanguage();
			
			$this->setTitle(__(
				($fields['name'] ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Translations'),
					$fields['name'][$reference_language]
				)
			));
			$this->appendSubheading(($fields['name'][$reference_language]? $fields['name'][$reference_language] : __('Untitled')));
			
			
			// Append form elements
			
			$this->Form->appendChild(
				$this->t_form->render($fields, $this->_errors)
			);
			
			
			// Form actions
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild( Widget::Input(
				'action[save]',
				__('Save changes'),
				'submit',
				array('accesskey' => 's'))
			);
			
			if( $this->_context[0] && Administration::instance()->Author->isDeveloper() ){
				$div->appendChild(Widget::Input(
					'action[delete]',
					__('Delete'),
					'submit',
					array('accesskey' => 'd','style' => 'float:left')
				));
			}
			
			$this->Form->appendChild($div);
		}
		
		/**
		 * Manages form submit.
		 *
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::action()
		 */
		public function action() {
			$fields = $_POST['fields'];
			$t_linker = new TLinker();
			
			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				$file_translations = array();
				
				// if there are any translations at all
				if( is_array($fields['translations']) ){
					$reference_language = FLang::instance()->referenceLanguage();
					
					foreach( $fields['translations'] as $language_code => $translations ){
						foreach( $translations as $context => $items ){
							
							foreach( $items as $handle => $item ){
								if( empty($item['handle']) && ($language_code == $reference_language) && Administration::instance()->Author->isDeveloper() ){
									$this->_errors['translations'][$language_code][$context][$handle]['handle'] = __('Handle is a required field.');
								}
								
								$valid_xml = true;
								
								// allow empty values
								if( $item['value'] !== ''){
									General::validateXML($item['value'], $valid_xml, false);
								}
								
								if( $valid_xml !== true ){
									$this->_errors['translations'][$language_code][$context][$handle]['value'] = __('Invalid XML.') . ' Error ' . $valid_xml['error'];
								}
								
								// store translation item
								$file_translations[$language_code][$context][$handle] = array(
									'handle' => $handle,
									'value' => $item['value']
								);
							}
						}
					}
				}
				// default to empty translations data
				else{
					foreach( FLang::instance()->ld()->languageCodes() as $language_code ){
						$file_translations[$language_code] = array();
					}
				}
				
				// store values
				if( empty($this->_errors) ){
					
					// if handle changed
					if( $fields['old_handle'] != $fields['handle'] ){
						TManager::instance()->changeTranslationHandle($fields['old_handle'], $fields['handle']);
					}
					
					// update linked pages
					$fields['old_pages'] = array_filter( explode('_', $fields['old_pages']) );
					
					$pages_plus =  array_diff($fields['pages'], $fields['old_pages']);
					$pages_minus = array_diff($fields['old_pages'], $fields['pages']);
					
					foreach( $pages_minus as $page_id ){
						$t_linker->unlinkFromPage($fields['handle'], $page_id);
					}

					foreach( $pages_plus as $page_id ){
						$t_linker->linkToPage($fields['handle'], $page_id);
					}
					
					// set translations
					foreach( $file_translations as $language_code => $translations ){
						$t_folder = TManager::instance()->getFolder($language_code);
						
						if( !empty($t_folder) ){
							$translation = $t_folder->getTranslation( $fields['old_handle'] );
							
							if( !empty($translation) ){
								// set name
								if( empty($fields['name'][$language_code]) ) $fields['name'][$language_code] = $fields['handle'];
								
								$translation->setName($fields['name'][$language_code]);
								
								// set tranlsation strings
								$content = $translation->getParser()->TArray2string($translations);
								
								$translation->data()->setContent($content);
							}
						}
					}
					
					redirect(URL . "/symphony/extension/frontend_localisation/edit/{$fields['handle']}/saved/");
				}
			}
			
			elseif( array_key_exists('delete', $_POST['action']) ){
				$t_folders = TManager::instance()->getFolders();
				$pages = FLPageManager::instance()->listAll(array('translations'));
						
				// remove translation files
				foreach( $t_folders as $t_folder ){
					$t_folder->deleteTranslation($fields['old_handle']);
				}
				
				// unlink from Pages
				foreach( $pages as $page_id => $page ){
					$page_translations = array_filter( explode(',', $page['translations']) );
					
					if( in_array($handle, $page_translations) ){
						$t_linker->unlinkFromPage($fields['old_handle'], $page_id);
					}
				}
				
				redirect(URL . "/symphony/extension/frontend_localisation");
			}
			
			if( is_array($this->_errors) && !empty($this->_errors) ){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}
		}
	
	}
