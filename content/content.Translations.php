<?php

	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FrontendLocalisationPageManager.php');
	
	class contentExtensionFrontend_localisationFrontendtranslations extends AdministrationPage {
		
		/**
		 * Translation Manager
		 * 
		 * @var TranslationManager
		 */
		private $translation_manager = null;
		
		
		
		public function __construct($parent){
			parent::__construct($parent);
			
			$this->translation_manager = new TranslationManager(DOCROOT . Symphony::Configuration()->get('translation_path','frontend_localisation'));
			$this->translation_manager->updateFolders();
		}
		
		
		
		public function __viewIndex() {
			$this->setPageType('form');
			$this->setTitle('Symphony &ndash; '.FRONTEND_LOCALISATION_NAME);
			
			$this->appendSubheading(__('Frontend Translations'));
			
			$page_manager = new FrontendLocalisationPageManager();
			$pages = $page_manager->listAll();
			
			$t_files = $this->translation_manager->getFolder( Symphony::Configuration()->get('reference_language','frontend_localisation') )->getFiles();
			
			if( count($pages) > 0 && count($t_files) > 0 ){
			
				foreach($t_files as $t_file) {
					$group = new XMLElement('div');
					$group->setAttribute('class', 'group');
					
					$total_selected = $this->__createPageList($group, $t_file);
					
					$container = new XMLElement('fieldset');
					$container->setAttribute('class', 'settings');
					$container->appendChild(
						new XMLElement('legend', $t_file->getFilename() . ' (' . $total_selected . ')')
					);
					$container->appendChild($group);
					$this->Form->appendChild($container);
				}
			}
			elseif( count($pages) == 0 ){
				$container = new XMLElement('fieldset');
				$container->setAttribute('class', 'settings error');
				$container->appendChild(new XMLElement('legend', __('No Pages Found')));
				
				$group = new XMLElement('div');
				$group->setAttribute('class', 'group');
				$group->appendChild(Widget::Label(__('Please <a href="%s">create</a> some pages before using Translations.', array(URL . '/symphony/blueprints/pages/new/'))));
				
				$container->appendChild($group);
				$this->Form->appendChild($container);
				
				return;
			}
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$attr = array('accesskey' => 's');
			$div->appendChild(Widget::Input('action[save]', 'Save Changes', 'submit', $attr));
			$this->Form->appendChild($div);
		}
		
		public function __getPages() {
			return Symphony::Database()->fetch("
				SELECT
					p.*
				FROM
					tbl_pages AS p
				ORDER BY
					p.sortorder ASC
			");
		}
		
		public function __getDataSources() {
			$DSManager = new DatasourceManager($this->_Parent);
			return $DSManager->listAll();
		}
		
		/**
		 * Create the pagelist for a Translation File
		 * 
		 * @param XMLElement $context
		 * @param TranslationFile $t_file
		 */
		public function __createPageList(&$context, $t_file) {
			$options = array();
			$total_selected = 0;
			$page_translations = explode(',', $page['translations']);
			
			$page_manager = new FrontendLocalisationPageManager();
			$pages = $page_manager->listAll();
			
			foreach( $pages as $page ){
				$selected = in_array($t_file->getFilename(), $page_translations);
				if($selected) $total_selected++;
				
				$options[] = array( $page['id'], $selected, $page['name'] );
			}
			
			$section = Widget::Label('Pages');
			$section->appendChild(Widget::Select(
				'settings['.$t_file->getFilename().'][]', $options, array('multiple' => 'multiple')
			));
			
			$context->appendChild($section);
			return $total_selected;
		}
		
		public function __actionIndex() {
			
			if( @isset($_POST['action']['save']) ){
			
				$page_manager = new FrontendLocalisationPageManager();
				$pages = $page_manager->listAll();
				
				// extract the settings
				$settings  = @$_POST['settings'];
				
				// create an empty translation files array for each page
				$page_t_files = array();
				foreach($pages as $page) $page_t_files[$page['id']] = array();
				
				// loop through translation files and add to each page
				foreach($settings as $filename => $pages) {
					foreach($pages as $page) $page_t_files[$page][] = $filename;
				}
				
				// loop through the final translation files and add to the database
				$error = false;
				foreach($page_t_files as $page => $filenames) {
					
					// create the fields to be updated
					$fields = array('translations' => @implode(',', $filenames));
					
					// update the fields
					if (!Symphony::Database()->update($fields, 'tbl_pages', "`id` = '$page'")) {
						$error = true;
						break;
					}
				}
				
				// show the success message
				if(!$error) {
					$this->pageAlert(
						__(
							'Translation Files updated at %1$s.', 
							array(DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__))
						), 
						Alert::SUCCESS);
					return;
				}
				
				// show the error message
				$this->pageAlert(
					__(
						'Unknown errors occurred while attempting to save. Please check your <a href="%s">activity log</a>.',
						array(URL . '/symphony/system/log/')
					),
					Alert::ERROR);
			}
		}
	
	}
