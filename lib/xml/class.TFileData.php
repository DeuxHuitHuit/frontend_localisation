<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once (EXTENSIONS.'/frontend_localisation/lib/class.TFileData.php');



	/**
	 * Deals with business data access for XML Translations.
	 *
	 * @package XML
	 *
	 * @author  Vlad Ghita
	 */
	final class XML_TFileData extends TFileData
	{

		public function __construct(Translation $translation){
			// set extension first
			$this->extension = 'xml';

			// call constructor after. Need extension to create default content.
			parent::__construct($translation);
		}



		/**
		 * If $content is missing, force file structure.
		 *
		 * @param string $content (optional) - content to be written.
		 *
		 * @see TFile::setContent()
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function setContent($content = null){
			// set default content
			if( empty($content) || !is_string($content) ){
				$doc = new DOMDocument('1.0', 'UTF-8');
				$doc->formatOutput = true;
				$doc->preserveWhiteSpace = true;

				$doc_data = $doc->createElement('data');

				$doc_item = $doc->createElement('item');
				$doc_item->setAttribute('handle', '');

				$doc_item_cdata = $doc->createCDATASection('');

				$doc_item->appendChild($doc_item_cdata);
				$doc_data->appendChild($doc_item);
				$doc->appendChild($doc_data);

				$content = $doc->saveXML();
			}

			return (boolean)parent::setContent($content);
		}
	}
