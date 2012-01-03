<?php
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TForm.php');
	
	
	
	class contentExtensionFrontend_localisationNew extends AdministrationPage {
		
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
		}
		
		
		
		/**
		 * Displays the form for a Translation.
		 *
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::view()
		 */
		public function view() {
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
				__('Create Translation'),
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
		public function action() {
			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				$fields = $_POST['fields'];
				
				if( empty($fields['handle']) ) $this->_errors['handle'] = __('Handle is a required field');
				
				if( empty($this->_errors) ){
					if( $this->_Parent->Author->isDeveloper() ){
						
						$t_linker = new TLinker();
						
						// link to Pages
						if( !empty($fields['pages']) ){
							
							foreach( $fields['pages'] as $page_id ){
								$t_linker->linkToPage($fields['handle'], $page_id);
							}
						}
						
						$t_folders = TManager::instance()->getFolders();
						
						if( is_array($t_folders) && !empty($t_folders) ){
							foreach( $t_folders as $language_code => $t_folder ){
								/* @var $t_folder TFolder */
								
								// create Translation
								$translation = $t_folder->addTranslation( $fields['handle'], array( 'storage_format' => $fields['storage_format'] ) );
								
								// set Name
								$translation->setName($fields['handle']);
								
								// set default Content
								$translation->data()->setContent();
							}
							
							redirect(URL . "/symphony/extension/frontend_localisation/edit/{$fields['handle']}/created/");
						}
						
						$this->_Parent->customError(
							__('Translation could not be created.'),
							__('You asked to create a Translation but there are not languages set by your Language Driver.'),
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
