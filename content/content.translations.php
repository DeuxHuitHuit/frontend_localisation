<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(TOOLKIT.'/class.administrationpage.php');
	require_once(EXTENSIONS.'/frontend_localisation/extension.driver.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.TForm.php');
	require_once(EXTENSIONS.'/frontend_localisation/lib/class.TManager.php');



	class contentExtensionFrontend_localisationTranslations extends AdministrationPage
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

			// Start building the page
			Extension_Frontend_Localisation::appendAssets();

			$this->addScriptToHead(URL.'/extensions/frontend_localisation/assets/frontend_localisation.translations.js', 203, false);
			$this->addStylesheetToHead(URL.'/extensions/frontend_localisation/assets/frontend_localisation.translations.css', "screen");

			$this->setPageType('form');

			$callback = Administration::instance()->getPageCallback();
			$template = $callback['context'][0];

			$this->setTitle(__(
				$template ? '%1$s &ndash; %2$s &ndash; %3$s' : '%1$s &ndash; %2$s',
				array(__('Symphony'), __('Translations'), $template)
			));

			$this->appendSubheading(($template ? $template : __('Untitled')));

			$this->insertBreadcrumbs(array(
				Widget::Anchor(__('Translations'), SYMPHONY_URL.'/extension/'.FL_GROUP.'/'),
			));

			// Form elements

			// Load the translations:
			$tableHeader = array(
				array(__('Handle'), 'col')
			);

			// Get the available languages:
			$languages = FLang::getLangs();
			$codes     = FLang::getAllLangs();
			$xml       = array();
			$mainlang  = FLang::getMainLang();

			foreach($languages as $code)
			{
				$tableHeader[] = array($codes[$code], 'col');
				$xmlFile  = WORKSPACE.'/translations/'.$code.'/'.$template.'.data.xml';
				$xml[$code]    = simplexml_load_file($xmlFile);
			}

			$tableHeader[] = array(__('Actions'), 'col');

			$tableBody = array();

			// Create a template:
			$tableData = array(Widget::TableData(Widget::Input('translations[handle][__HANDLE__]', '', 'text', array('class'=>'handle'))));
			foreach($languages as $code) {
				$tableData[] = Widget::TableData(Widget::Input('translations['.$code.'][__HANDLE__]', '', 'text'));
			}
			$tableBody[] = Widget::TableRow($tableData, 'template');

			// Build the table:
			foreach($xml[$mainlang]->children() as $xmlItem)
			{
				$handle = (string)$xmlItem->attributes()->handle;
				$tableData = array(
					Widget::TableData(
						Widget::Input('translations[handle]['.$handle.']', $handle, 'text', array('class'=>'handle'))
					)
				);
				foreach($languages as $code) {
					$value = $xml[$code]->xpath('/data/item[@handle=\''.$handle.'\']');
					$tableData[] = Widget::TableData(
						Widget::Input('translations['.$code.']['.$handle.']',
							(string)$value[0]
						)
					);
				}

				$tableData[] = Widget::TableData(
					Widget::Anchor(__('Delete'), '#', __('Delete'), 'delete')
				);

				$tableBody[] = Widget::TableRow($tableData, 'data');
			}

			// Create the table:
			$table = Widget::Table(
				Widget::TableHead($tableHeader),
				Widget::TableBody($tableBody),
				null,
				'translations'
			);

			$this->Form->appendChild($table);

			$this->Form->appendChild(new XMLElement('p', __('Pressing the tab-button after the last field will create a new translation row'),
				array('class'=>'help translation-help')));

			$div = new XMLElement('div', null, array('class' => 'actions'));
			$div->appendChild(Widget::Input('action[save]', __('Save changes'), 'submit', array('accesskey' => 's')));
			$this->Form->appendChild($div);
		}

		/**
		 * Manages form submit
		 *
		 * @see AdministrationPage::action()
		 */
		public function action(){
			if( @array_key_exists('save', $_POST['action']) ){
				$this->_errors = array();

				$translations = $_POST['translations'];

				$languages = FLang::getLangs();
				$xml       = array();

				// Create the elements:
				foreach($languages as $code)
				{
					$xml[$code] = new XMLElement('data');
				}

				// Store the translation:
				foreach($translations['handle'] as $handle)
				{
					// Ignore the template and empty rows:
					if($handle != '__HANDLE__' && !empty($handle))
					{
						foreach($languages as $code)
						{
							$xml[$code]->appendChild(new XMLElement('item',
								'<![CDATA['.$translations[$code][$handle].']]>',
								array('handle'=>$handle)));
						}
					}
				}

				// Dump them files:
				$callback = Administration::instance()->getPageCallback();
				$template = $callback['context'][0];

				foreach($languages as $code)
				{
					General::writeFile(WORKSPACE.'/translations/'.$code.'/'.$template.'.data.xml',
						'<?xml version="1.0"?>'."\n".$xml[$code]->generate(true));
				}

				redirect(SYMPHONY_URL."/extension/".FL_GROUP);
			}

			if( is_array($this->_errors) && !empty($this->_errors) ){
				$this->pageAlert(__('An error occurred while processing this form. <a href="#error">See below for details.</a>'), Alert::ERROR);
			}
		}

	}
