<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	/**
	 * Deals with business data of XML Translations.
	 *
	 * @package Frontend Localisation
	 *
	 * @author  Vlad Ghita
	 */
	abstract class TFileData extends TFile
	{

		public function __construct(Translation $translation){
			parent::__construct($translation);

			$this->type = 'data';

			if( !file_exists($this->parent->getPath().'/'.$this->getFilename()) ){
				$this->setContent();
			}
		}
	}
