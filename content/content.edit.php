<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationForm.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationManager.php');
	
	class contentExtensionFrontend_localisationEdit extends AdministrationPage {
		
		/**
		 * Translation Manager
		 * 
		 * @var TranslationManager
		 */
		public $translation_manager = null;
		
		/**
		 * Translation form.
		 * 
		 * @var TranslationForm
		 */
		private $t_form = null;
		
		
		
		public function __construct($parent){
			parent::__construct($parent);
			
			$this->t_form = new TranslationForm($this);
			$this->_errors = array();
			
			$this->translation_manager = new TranslationManager();
		}
		
		
		
		/**
		 * Displays the form for a Translation.
		 * 
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::view()
		 */
		function view() {
			$this->setPageType('form');
			
			// If we're editing, make sure the item exists
			
			if( $this->_context[0] ){
				$t_files = $this->translation_manager->getFolder( FrontendLanguage::instance()->referenceLanguage() )->getFiles();
				
				if( !$handle = $this->_context[0] ) redirect(URL . '/symphony/extension/frontend_localisaion');
				
				if( !array_key_exists($handle, $t_files) || empty($t_files[$handle]) || !($t_files[$handle] instanceof TranslationFile) ){
					$this->_Parent->customError(
						__('Translation File not found'),
						__('The translation file you requested to edit does not exist.'),
						'error',
						array(
							'header' => 'HTTP/1.0 404 Not Found'
						)
					);
				}
			}
			
			
			// sync file across languages. On fail, abort edit.
			
			if( !$this->translation_manager->updateFile($handle) ){
				$this->pageAlert(
					__('Translation Files synchronisation failed. Please contact site administrator.'),
					Alert::NOTICE
				);
				
				return false;
			}
			
			
			// Status message
			
			if( isset($this->_context[1]) ){
				$this->pageAlert(
					__(
						'%1$s %2$s at %3$s. <a href="%4$s">Create another?</a> <a href="%5$s">%6$s</a>',
						array(
							__('Translation File'),
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
				$t_file = $t_files[$handle];
				
				$fields['name'] = $t_file->getName();
				$fields['handle'] = $handle;
				
				$tf_writer = new TranslationFileWriter();
				$linked_pages = $tf_writer->getLinkedPages($handle);
				
				foreach( $linked_pages as $page_id => $page ){
					$fields['pages'][] = $page_id;
				}
				
				$fields['translations'] = array();
				
				foreach( $this->translation_manager->getFolders() as $language_code => $t_folder ){
					$file = $t_folder->getFile($t_file->getHandle());
					
					$fields['translations'][$language_code] = $tf_writer->convertXMLtoArray($file->getContentXML());
				}
			}

			
			// Start building the page
			
			$this->setTitle(__(
				($fields['name'] ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s'),
				array(
					__('Symphony'),
					__('Translations'),
					$fields['name']
				)
			));
			$this->appendSubheading(($fields['name']? $fields['name'] : __('Untitled')));
			
			
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
		function action() {
			$fields = $this->t_form->cleanFields( $_POST['fields'] );
			
			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				if( empty($fields['name']) ) $this->_errors['name'] = __('Name is a required field');
				
				$file_translations = array();
				
				// if there are any translations at all
				if( is_array($fields['translations']) ){
					foreach( $fields['translations'] as $language_code => $translations ){
						
						foreach( $translations as $xPath => $items ){
							
							foreach( $items as $id => $item ){
								if( empty($item['handle']) ){
									$this->_errors['translations'][$language_code][$xPath][$id]['handle'] = __('Handle is a required field.');
								}
								
								$valid_xml = true;
								
								// allow empty values
								if( $item['value'] !== ''){
									General::validateXML($item['value'], $valid_xml, false);
								}
								
								if( $valid_xml !== true ){
									$this->_errors['translations'][$language_code][$xPath][$id]['value'] = __('Invalid XML.') . ' Error ' . $valid_xml['error'];
								}
								
								// store translation item
								$file_translations[$language_code][$xPath][ $item['handle'] ] = $item['value'];
							}
						}
					}
				}
				
				else{
					foreach( FrontendLanguage::instance()->languageCodes() as $language_code ){
						$file_translations[$language_code] = array();
					}
				}
				
				if( empty($this->_errors) ){
					$tf_writer = new TranslationFileWriter();
					
					// set linked pages
					
					// if handle changed, update old_handle and new_handle
					if( $fields['old_handle'] != $fields['handle'] ){
						$pages_old = explode('_', $fields['old_pages']);
						
						foreach( $fields['pages'] as $page_id ){
							$tf_writer->linkTranslationToPage($fields['handle'], $page_id);
							$tf_writer->unlinkTranslationFromPage($fields['old_handle'], $page_id);
						}
						
						foreach( $pages_old as $page_id ){
							$tf_writer->unlinkTranslationFromPage($fields['old_handle'], $page_id);
						}
					}
					// update differences
					else{
						$pages_old = explode('_', $fields['old_pages']);
						$pages_plus = array();
						$pages_minus = array();
	
						if( !empty($pages_old) ){
							$pages_plus = array_diff($fields['pages'], $pages_old);
							$pages_minus = array_diff($pages_old, $fields['pages']);
						}
	
						foreach( $pages_minus as $page_id ){
							$tf_writer->unlinkTranslationFromPage($fields['handle'], $page_id);
						}
	
						foreach( $pages_plus as $page_id ){
							$tf_writer->linkTranslationToPage($fields['handle'], $page_id);
						}
					}
					
					// set translation information
					
					foreach( $file_translations as $language_code => $translations ){
						$t_folder = $this->translation_manager->getFolder($language_code);
						
						if( !empty($t_folder) ){
							$t_file = $t_folder->getFile( $fields['old_handle'] );
							
							if( !empty($t_file) ){
								if( $this->_Parent->Author->isDeveloper() ){
									
									// set name
									$t_file->setName($fields['name']);
									
									// set handle
									if( $fields['old_handle'] != $fields['handle'] ){
										$t_file->setFilename($fields['handle']);
									}
								}
								
								// save translation items to file
								$data = $tf_writer->convertArraytoString($t_file->getContentXML(), $translations);
								
								$t_file->setData($data);
							}
						}
					}
					
					redirect(URL . "/symphony/extension/frontend_localisation/edit/{$fields['handle']}/saved/");
				}
			}
			
			elseif( @array_key_exists('delete', $_POST['action']) ){
				$fields['old_handle'] = General::sanitize($fields['handle']);
				
				$t_folders = $this->translation_manager->getFolders();
				$tf_writer = new TranslationFileWriter();
						
				$pages = FLPageManager::instance()->listAll(array('translations'));
						
				// remove translations from HDD
				foreach( $t_folders as $t_folder ){
					$t_folder->deleteFile($fields['old_handle']);
				}
							
				// unlink from Pages
				foreach( $pages as $page_id => $page ){
					$page_translations = explode(',', $page['translations']);
					
					if( in_array($handle, $page_translations) ){
						$tf_writer->unlinkTranslationFromPage($fields['old_handle'], $page_id);
					}
				}
				
				redirect(URL . "/symphony/extension/frontend_localisation");
			}
			
			if( is_array($this->_errors) && !empty($this->_errors) ){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}				
		}
	
	}
