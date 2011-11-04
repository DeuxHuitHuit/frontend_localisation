<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationForm.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationManager.php');
	
	class contentExtensionFrontend_localisationNew extends AdministrationPage {
		
		/**
		 * Translation Manager
		 * 
		 * @var TranslationManager
		 */
		private $translation_manager = null;
		
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
			
			
			// Find values
			
			$fields = array();
			
			if( isset($_POST['fields']) ){
				$fields = $_POST['fields'];
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
				__('Create Translation File'), 
				'submit', 
				array('accesskey' => 's')) 
			);
			
			$this->Form->appendChild($div);
		}
		
		/**
		 * Manages form submit.
		 * 
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::action()
		 */
		function action() {
			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				$fields = $this->t_form->cleanFields( $_POST['fields'] );
				
				if( empty($fields['name']) ) $this->_errors['name'] = __('Name is a required field');
				
				if( empty($this->_errors) ){
					if( $this->_Parent->Author->isDeveloper() ){
						
						$tf_writer = new TranslationFileWriter();
						
						// link to Pages
						foreach( $fields['pages'] as $page_id ){
							$tf_writer->linkTranslationToPage($fields['handle'], $page_id);
						}
						
						$t_folders = $this->translation_manager->getFolders();
						
						if( is_array($t_folders) && !empty($t_folders) ){
							foreach( $t_folders as $language_code => $t_folder ){
								
								// create Translation File
								$t_folder->addFile( $fields['handle'] );
								
								// set Name
								$t_file = $t_folder->getFile( $fields['handle'] );
								$t_file->setName($fields['name']);
							}
							
							redirect(URL . "/symphony/extension/frontend_localisation/edit/{$fields['handle']}/created/");
						}
						
						$this->_Parent->customError(
							__('Translation File could not be created.'),
							__('You asked to create a Translation File but there are not languages set by your Language Driver.'),
							'error',
							array(
								'header' => 'HTTP/1.0 404 Not Found'
							)
						);
					}
				}
			}
			
			if( is_array($this->_errors) && !empty($this->_errors) ){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}				
		}
	
	}
