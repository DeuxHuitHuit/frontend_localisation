<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(TOOLKIT.'/class.administrationpage.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.TForm.php');



	class contentExtensionFrontend_localisationIndex extends AdministrationPage
	{

		/**
		 * Displays all Translations on Index page.
		 *
		 * @see AdministrationPage::view()
		 */
		public function view(){

			/* Start building the page */

			$this->setPageType('table');
			$this->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Translations'))));


			/* Append heading */

			$create_new = null;

			if( Administration::instance()->Author->isDeveloper() ){
				$create_new = Widget::Anchor(
					__('Create New'), SYMPHONY_URL.'/extension/'.FL_GROUP.'/new/',
					__('Create a new translation file'), 'create button'
				);
			}

			$this->appendSubheading(__('Translations'), $create_new);


			/* Build the table */

			$thead = array(
				array(__('Title'), 'col'),
				array(__('Pages'), 'col')
			);

			$tbody = array();

			$t_folder = TManager::getFolder(TManager::getRefLang());
			if( is_null($t_folder) ){
				$this->Form->appendChild($t_folder);
				Administration::instance()->Page->pageAlert(__('<code>%1$s</code>: Translation folders not found.', array(FL_NAME)), Alert::NOTICE);
				return;
			}

			$translations = $t_folder->getTranslations();

			// If there are no records, display default message
			if( !is_array($translations) or empty($translations) ){


				$tbody = array(Widget::TableRow(array(
					Widget::TableData(
						__('No translations found. <a href="%s">Create new?</a>', array(SYMPHONY_URL.'/'.FL_GROUP.'/frontend_localisation/new/')),
						'inactive', null, count($thead)
					)
				), 'odd'));
			}

			// Otherwise, build table rows
			else{
				$bOdd = true;

				foreach( $translations as $handle => $translation ){
					$col_title = Widget::TableData(Widget::Anchor(
						$translation->meta()->get('name'), SYMPHONY_URL.'/extension/'.FL_GROUP.'/edit/'.$handle.'/'
					));
					$col_title->appendChild(Widget::Input("items[{$handle}]", null, 'checkbox'));

					$col_pages = Widget::TableData($this->_createPageList($translation));

					$tbody[] = Widget::TableRow(array($col_title, $col_pages), ($bOdd ? 'odd' : NULL));

					$bOdd = !$bOdd;
				}
			}

			$table = Widget::Table( Widget::TableHead($thead), null, Widget::TableBody($tbody), null);
			$table->setAttribute('class', 'selectable');

			$this->Form->appendChild($table);


			/* With-selected */

			if( Administration::instance()->Author->isDeveloper() ){
				$table_actions = new XMLElement('div', null, array('class' => 'actions'));

				$options = array(
					array('', true, __('With Selected...')),
					array('delete', false, __('Delete'), 'confirm', null, array(
						'data-message' => __('Are you sure you want to delete the selected translations?')
					))
				);

				$table_actions->appendChild(Widget::Apply($options));

				$this->Form->appendChild($table_actions);
			}
		}

		/**
		 * Implements actions on Index page.
		 *
		 * @see AdministrationPage::action()
		 */
		public function __actionIndex(){

			$checked = @array_keys($_POST['items']);

			if( is_array($checked) && !empty($checked) ){
				switch( $_POST['with-selected'] ){

					case 'delete':

						$t_folders = TManager::getFolders();
						$t_linker = new TLinker();

						$pages = FLPageManager::instance()->listAll(array('translations'));

						foreach( $checked as $handle ){

							// remove files from HDD
							foreach( $t_folders as $t_folder ){
								/* @var $t_folder TFolder */
								$t_folder->deleteTranslation($handle);
							}

							// unlink from Pages
							foreach( $pages as $page_id => $page ){
								$page_translations = explode(',', $page['translations']);

								if( in_array($handle, $page_translations) ){
									$t_linker->unlinkFromPage($handle, $page_id);
								}
							}

						}

						redirect($this->_Parent->getCurrentPageURL());
						break;
				}
			}
		}



		/**
		 * Creates the pagelist for a Translation
		 *
		 * @param Translation $translation
		 *
		 * @return string
		 */
		private function _createPageList($translation){
			$result = '';
			$t_handle = $translation->getHandle();

			$pages = FLPageManager::instance()->listAll(array('translations'));

			// enable links for developers
			if( Administration::instance()->Author->isDeveloper() ){
				foreach( $pages as $page_id => $page ){
					$page_translations = array_map('trim', explode(',', $page['translations']));

					if( in_array($t_handle, $page_translations) ){
						$result .= sprintf(
							'<a href="%1$s">%2$s</a>&#160;&#160;&#160;',
							SYMPHONY_URL.'/blueprints/pages/edit/'.$page_id,
							PageManager::resolvePageTitle($page_id)
						);
					}
				}
			}

			// disable links for other users
			else{
				foreach( $pages as $page_id => $page ){
					$page_translations = explode(',', $page['translations']);

					if( in_array($translation->getHandle(), $page_translations) ){
						$result .= '/'.PageManager::resolvePageTitle($page_id).'<br />';
					}
				}
			}

			if( empty($result) ){
				$result = __('No Pages Found');
			}

			return $result;
		}

	}
