<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(TOOLKIT.'/class.administrationpage.php');
	require_once(EXTENSIONS.'/frontend_localisation/extension.driver.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.TForm.php');



	class contentExtensionFrontend_localisationEdit extends AdministrationPage
	{

		/**
		 * Translation form.
		 *
		 * @var TForm
		 */
		private $t_form = null;



		public function __construct(){
			parent::__construct();

			$this->t_form = new TForm();
			$this->_errors = array();
		}



		/**
		 * Displays the form for a Translation.
		 *
		 * @see AdministrationPage::view()
		 */
		public function view(){
			// If we're editing, make sure the item exists

			if( $this->_context[0] ){
				if( !$handle = $this->_context[0] ) redirect(SYMPHONY_URL.'/extension/frontend_localisaion');

				$valid = true;

				$t_folder = TManager::getFolder( TManager::getRefLang() );

				if( is_null($t_folder) ) $valid = false;

				if( !$valid || is_null($t_folder->getTranslation($handle)) ){
					Administration::instance()->customError(
						__('Translation not found'),
						__('The translation file you requested to edit does not exist.'),
						'generic',
						array(
							'header' => 'HTTP/1.0 404 Not Found'
						)
					);
				}
			}


			// sync file across languages. On fail, abort edit.

			if( !TManager::syncTranslation($handle) ){
				$this->pageAlert(__('Translations synchronisation failed. Please contact site administrator.'), Alert::NOTICE);
				return;
			}


			// Status message

			if( isset($this->_context[1]) ){

				$this->pageAlert(
					__(
						'%1$s %2$s at %3$s. <a href="%4$s" accesskey="c">Create another?</a> <a href="%5$s" accesskey="a">%6$s</a>',
						array(
							__('Translation'),
							$this->_context[1] == 'saved' ? __('updated') : __('created'),
							Widget::Time('', __SYM_TIME_FORMAT__)->generate(),
							SYMPHONY_URL.'/extension/'.FL_GROUP.'/new/',
							SYMPHONY_URL.'/extension/'.FL_GROUP.'/',
							__('View all Translations')
						)
					),
					Alert::SUCCESS
				);
			}


			// Find values

			$ref_lang = TManager::getRefLang();

			$fields = array();

			if( isset($_POST['fields']) ){
				$fields = $_POST['fields'];
			}

			elseif( $this->_context[0] ){

				$fields['handle'] = $handle;
				$fields['translations'] = array();

				$t_linker = new TLinker();

				foreach( array_keys($t_linker->getLinkedPages($handle)) as $page_id ){
					$fields['pages'][] = $page_id;
				}

				foreach( TManager::getFolders() as $lc => $t_folder ){
					$translation = $t_folder->getTranslation($handle);

					if( $lc === $ref_lang ){
						$fields['storage_format'] = $translation->meta()->get('storage_format');
						$fields['type'] = $translation->meta()->get('type');
					}

					$fields['name'][$lc] = $translation->meta()->get('name');
					$fields['translations'][$lc] = $translation->getParser()->asTArray($translation);
				}
			}


			// Start building the page

			Extension_Frontend_Localisation::appendAssets();

			$this->addScriptToHead(URL.'/extensions/frontend_localisation/assets/frontend_localisation.content.js', 203, false);
			$this->addStylesheetToHead(URL.'/extensions/frontend_localisation/assets/frontend_localisation.content.css', "screen");

			$this->setPageType('form');

			$this->setTitle(__(
				$fields['name'] ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s',
				array(__('Symphony'), __('Translations'), $fields['name'][$ref_lang])
			));

			$this->appendSubheading(($fields['name'][$ref_lang] ? $fields['name'][$ref_lang] : __('Untitled')));

			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Translations'), SYMPHONY_URL.'/extension/'.FL_GROUP.'/'),
			));


			// Form elements

			$this->Form->setAttribute('class', 'two columns');

			$this->t_form->render($this->Form, $fields, $this->_errors);


			// Form actions

			$div = new XMLElement('div', null, array('class' => 'actions'));
			$div->appendChild(Widget::Input('action[save]', __('Save changes'), 'submit', array('accesskey' => 's')));

			if( $this->_context[0] && Administration::instance()->Author->isDeveloper() ){
				$button = new XMLElement('button', __('Delete'));
				$button->setAttributeArray(array('name' => 'action[delete]', 'class' => 'button confirm delete', 'title' => __('Delete'), 'type' => 'submit', 'accesskey' => 'd', 'data-message' => __('Are you sure you want to delete this entry?')));
				$div->appendChild($button);
			}

			$this->Form->appendChild($div);
		}

		/**
		 * Manages form submit
		 *
		 * @see AdministrationPage::action()
		 */
		public function action(){
			$fields = $_POST['fields'];
            $contexts = $_POST['contexts'];
			$t_linker = new TLinker();

			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				$file_translations = array();

				// if there are any translations at all
				if( is_array($fields['translations']) ){
					$ref_lang = TManager::getRefLang();

					foreach( $fields['translations'] as $lc => $translations ){
						foreach( $translations as $old_context => $items ){
                            
                            //context is the context in the context field
                            $context = $old_context;
                            if($old_context != '/'.$contexts['translations'][$ref_lang][$old_context]){
                                $context='/'.$contexts['translations'][$ref_lang][$old_context];
                            }
							foreach( $items as $old_handle => $item ){
								if( empty($item['handle']) && ($lc === $ref_lang) && Administration::instance()->Author->isDeveloper() ){
									$this->_errors['translations'][$lc][$old_context][$old_handle]['handle'] = __('Handle is a required field.');
								}

								// mark for storage
								$file_translations[$lc][$context][$old_handle] = array(
									'handle' => $fields['translations'][$ref_lang][$old_context][$old_handle]['handle'],
									'value' => $item['value']
								);
							}
						}
					}
				}
				// default to empty translations data
				else{
					foreach( FLang::getLangs() as $lc ){
						$file_translations[$lc] = array();
					}
				}

				// store values
				if( empty($this->_errors) ){

					// if handle changed
					if( $fields['old_handle'] != $fields['handle'] ){
						TManager::changeTranslationHandle($fields['old_handle'], $fields['handle']);
					}

					// update linked pages
					if( empty($fields['old_pages']) ) $fields['old_pages'] = '';
					if( empty($fields['pages']) ) $fields['pages'] = array();

					$fields['old_pages'] = array_filter(explode('_', $fields['old_pages']));

					$pages_plus = array_diff($fields['pages'], $fields['old_pages']);
					$pages_minus = array_diff($fields['old_pages'], $fields['pages']);

					foreach( $pages_minus as $page_id ){
						$t_linker->unlinkFromPage($fields['handle'], $page_id);
					}

					foreach( $pages_plus as $page_id ){
						$t_linker->linkToPage($fields['handle'], $page_id);
					}

					// set translations
					foreach( $file_translations as $lc => $translations ){
						$t_folder = TManager::getFolder($lc);

						if( !is_null($t_folder) ){
							$translation = $t_folder->getTranslation($fields['handle']);

							if( !is_null($translation) ){
								// if storage_format changed
								if( $fields['old_storage_format'] != $fields['storage_format'] ){
									$translation->meta()->set('storage_format', $fields['storage_format']);
									$translation->setData();
								}


								// type changed to 'Page'
								if( ($fields['old_type'] != $fields['type']) ){
									$translation = $t_folder->setTranslationType($fields['handle'], $fields['type']);
								}


								// set name
								if( empty($fields['name'][$lc]) ) $fields['name'][$lc] = $fields['handle'];

								$translation->setName($fields['name'][$lc]);


								// set tranlsation strings
								$content = $translation->getParser()->TArray2string($translations);

								$translation->data()->setContent($content);
							}
						}
					}

					redirect(SYMPHONY_URL."/extension/".FL_GROUP."/edit/{$fields['handle']}/saved/");
				}
			}

			elseif( array_key_exists('delete', $_POST['action']) ){
				$t_folders = TManager::getFolders();
				$pages = FLPageManager::instance()->listAll(array('translations'));

				// remove translation files
				foreach( $t_folders as $t_folder ){
					$t_folder->deleteTranslation($fields['old_handle']);
				}

				// unlink from Pages
				foreach( $pages as $page_id => $page ){
					$page_translations = array_filter(explode(',', $page['translations']));

					if( in_array($fields['old_handle'], $page_translations) ){
						$t_linker->unlinkFromPage($fields['old_handle'], $page_id);
					}
				}

				redirect(SYMPHONY_URL."/extension/".FL_GROUP);
			}

			if( is_array($this->_errors) && !empty($this->_errors) ){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}
		}

	}
