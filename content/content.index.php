<?php
	
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/class.administrationpage.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationFileWriter.php');
	
	class contentExtensionFrontend_localisationIndex extends AdministrationPage {
		
		/**
		 * Translation Manager
		 * 
		 * @var TranslationManager
		 */
		private $translation_manager = null;
		
		
		
		public function __construct($parent){
			parent::__construct($parent);
			
			$this->translation_manager = new TranslationManager();
		}
		
		
		
		/**
		 * Displays all Translations on Index page.
		 * 
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::view()
		 */
		public function view() {
		
			/* Start building the page */
			
			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Translations'))));
			
			$create_new = null;
			
			if( $this->_Parent->Author->isDeveloper() ){
				$create_new = Widget::Anchor(
					__('Create New'), URL . '/symphony/extension/frontend_localisation/new/',
					__('Create a new translation file'), 'create button'
				);
			}
			
			$this->appendSubheading(__('Translations'), $create_new);
		

			/* Build the table */
			
			$t_files = $this->translation_manager->getFolder( Symphony::Configuration()->get('reference_language','frontend_localisation') )->getFiles();
			
			$thead = array(
				array(__('Title'), 'col'),
				array(__('Pages'), 'col')
			);
			
			$tbody = array();

			// If there are no records, display default message
			if (!is_array($t_files) or empty($t_files)) {
				$tbody = array(Widget::TableRow(array(
					Widget::TableData(__('None found.'), 'inactive', null, count($thead))
				), 'odd'));
			}
		
			// Otherwise, build table rows
			else{
				$bOdd = true;
				
				foreach ($t_files as $handle => $t_file) {
					$edit_url = URL . '/symphony/extension/frontend_localisation/edit/' . $handle . '/';
					
					$col_title = Widget::TableData(Widget::Anchor(
						$t_file->getName(), $edit_url
					));
					$col_title->appendChild(Widget::Input("items[{$t_file->getHandle()}]", null, 'checkbox'));
					
					$col_pages = Widget::TableData( $this->_createPageList($t_file) );
					
					$tbody[] = Widget::TableRow(array($col_title, $col_pages), ($bOdd ? 'odd' : NULL));
					
					$bOdd = !$bOdd;
				}
			}
			
			$table = Widget::Table(
				Widget::TableHead($thead), null,
				Widget::TableBody($tbody), null
			);
			$table->setAttribute('class','selectable');
			
			$this->Form->appendChild($table);
			
			
			/* With-selected */
			
			if( $this->_Parent->Author->isDeveloper() ){
				$tableActions = new XMLElement('div');
				$tableActions->setAttribute('class', 'actions');
				
				$options = array(
					array(null, false, __('With Selected...')),
					array('delete', false, __('Delete'))							
				);
				
				$tableActions->appendChild(Widget::Select('with-selected', $options));
				$tableActions->appendChild(Widget::Input('action[apply]', __('Apply'), 'submit'));
				
				$this->Form->appendChild($tableActions);
			}
		}
		
		/**
		 * Implements actions on Index page.
		 * 
		 * @see _dev/symphony/lib/toolkit/AdministrationPage::action()
		 */
		function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if(is_array($checked) && !empty($checked)){
				switch($_POST['with-selected']) {

					case 'delete':
						
						$t_folders = $this->translation_manager->getFolders();
						$tf_writer = new TranslationFileWriter();
						
						$pages = FLPageManager::instance()->listAll(array('translations'));
						
						foreach( $checked as $handle ){
							
							// remove files from HDD
							foreach( $t_folders as $t_folder ){
								$t_folder->deleteFile($handle);
							}
							
							// unlink from Pages
							foreach( $pages as $page_id => $page ){
								$page_translations = explode(',', $page['translations']);
								
								if( in_array($handle, $page_translations) ){
									$tf_writer->unlinkTranslationFromPage($handle, $page_id);
								}
							}
							
						}

						redirect($this->_Parent->getCurrentPageURL());
						break;  	
				}
			}
		}	
		
		
		
		/**
		 * Creates the pagelist for a Translation File
		 * 
		 * @param TranslationFile $t_file
		 */
		private function _createPageList($t_file) {
			$value = '';
			
			$pages = FLPageManager::instance()->listAll(array('translations'));
			
			// enable links for developers
			if( $this->_Parent->Author->isDeveloper() ){
				foreach( $pages as $page_id => $page ){
					$page_translations = explode(',', $page['translations']);
					
					if( in_array($t_file->getHandle(), $page_translations) ){
						$link = URL . '/symphony/blueprints/pages/edit/' . $page_id;
						
						$value .= "<a href=\"{$link}\">".Administration::instance()->resolvePageTitle($page_id)."</a><br />";
					}
				}
			}
			
			// disable links for other users
			else{
				foreach( $pages as $page_id => $page ){
					$page_translations = explode(',', $page['translations']);
					
					if( in_array($t_file->getHandle(), $page_translations) ){
						$value .= '/' . Administration::instance()->resolvePageTitle($page_id) . '<br />';
					}
				}
			}
			
			if( empty($value) ){
				$value = __('No Pages Found');
			}
			
			return $value;
		}
	
	}
