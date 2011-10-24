<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FrontendLocalisationPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FrontendLanguage.php');

	Class datasourcefrontend_localisation extends Datasource{

		public function about(){
			return array(
				'name' => 'Frontend Localisation',
				'author' => array(
					'name' => 'Xander Group',
					'email' => 'symphonycms@xandergroup.ro',
					'website' => 'www.xandergroup.ro'
				),
				'version' => '1.0',
				'release-date' => '2011-10-22',
				'description' => 'From Frontend Localisation extension. It supplies translation strings for current Page, as selected in Page settings.');
		}

		public function allowEditorToParse(){
			return false;
		}

	    public function grab(&$param_pool=NULL){
	    	$result = new XMLElement('frontend-localisation');

	    	$page_id = $this->_env['param']['current-page-id'];
	    	
	    	$page_manager = new FrontendLocalisationPageManager();
	    	$pages = $page_manager->listAll();
	    	
	    	$translation_path = Symphony::Configuration()->get('translation_path','frontend_localisation');
	    	
	    	if( !empty($translation_path) ){
		    	$translation_manager = new TranslationManager(DOCROOT . $translation_path);
		    	$translations = preg_split('/,/i', $pages[$page_id]['translations'], -1, PREG_SPLIT_NO_EMPTY);
		    	
		    	if( !empty($translations) ){
			    	foreach( $translation_manager->getFolder( FrontendLanguage::instance()->getLangaugeCode() )->getFiles() as $t_file ){
			    		
			    		if( in_array($t_file->getFilename(), $translations) ){
			    			$result->appendChild( $this->_addFile($t_file) );
			    		}
			    	}
		    	}
	    	}
			
	        return $result;
	    }
	    
	    
	    
	    private function _addFile(TranslationFile $t_file){
	    	$result = new XMLElement(substr($t_file->getFilename(), 0, -4));
	    	$force_empty_result = false;
	    	
	    	$stylesheet = new XMLElement('xsl:stylesheet');
	    	$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

	    	$output = new XMLElement('xsl:output');
	    	$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
	    	$stylesheet->appendChild($output);

	    	$template = new XMLElement('xsl:template');
	    	$template->setAttribute('match', '/');

	    	$instruction = new XMLElement('xsl:copy-of');
	    	$instruction->setAttribute('select', "/data/*[name() != 'meta']");

	    	$template->appendChild($instruction);
	    	$stylesheet->appendChild($template);

	    	$stylesheet->setIncludeHeader(true);

	    	$xsl = $stylesheet->generate(true);

	    	$xml = $t_file->getContent();

	    	// Handle where there is `$xml` and the XML is valid
	    	if(strlen($xml) > 0 && !General::validateXML($xml, $errors, false, new XsltProcess)){
	    		$result->setAttribute('valid', 'false');
	    		$result->appendChild(new XMLElement('error', __('XML returned is invalid.')));
	    		$element = new XMLElement('errors');
	    		foreach($errors as $e) {
	    			if(strlen(trim($e['message'])) == 0) continue;
	    			$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
	    		}
	    		$result->appendChild($element);
	    	}
	    	// If `$xml` is empty, set the `force_empty_result` to true.
	    	elseif(strlen($xml) == 0){
	    		$force_empty_result = true;
	    	}

	    	// If `force_empty_result` is false and `$result` is not an instance of
	    	// XMLElement, build the `$result`.
	    	if(!$force_empty_result && is_object($result)) {

	    		$proc = new XsltProcess;
	    		$ret = $proc->process($xml, $xsl);

	    		if($proc->isErrors()){
	    			$result->setAttribute('valid', 'false');
	    			$error = new XMLElement('error', __('XML returned is invalid.'));
	    			$result->appendChild($error);
	    			$element = new XMLElement('errors');
	    			foreach($proc->getError() as $e) {
	    				if(strlen(trim($e['message'])) == 0) continue;
	    				$element->appendChild(new XMLElement('item', General::sanitize($e['message'])));
	    			}
	    			$result->appendChild($element);
	    		}

	    		else if(strlen(trim($ret)) == 0){
	    			$force_empty_result = true;
	    		}

	    		else{
	    			$result->setValue(self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret));
	    		}
	    	}
	    	
	    	if($force_empty_result) $result = $this->emptyXMLSet();
	    	
	    	return $result;
	    }

	}
