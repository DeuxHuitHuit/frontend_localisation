<?php

	require_once(TOOLKIT . '/class.datasource.php');
	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FrontendLanguage.php');

	Class datasourcefl_translations extends Datasource{
		
		public function about(){
			return array(
				'name' => 'FL: Translations',
				'author' => array(
					'name' => 'Xander Group',
					'email' => 'symphonycms@xandergroup.ro',
					'website' => 'www.xanderadvertising.com'
				),
				'version' => '1.0',
				'release-date' => '2011-10-24',
				'description' => 'From Frontend Localisation extension. It supplies translation strings for current Page, as selected in Page settings.');
		}

		public function allowEditorToParse(){
			return false;
		}

	    public function grab(&$param_pool=NULL){
	    	$result = new XMLElement('fl-translations');

// 	    	$page_id = $this->_env['param']['current-page-id'];
// 	    	$translation_path = Symphony::Configuration()->get('translation_path','frontend_localisation');
	    	
// 	    	$pages = FLPageManager::instance()->listAll(array('translations'));
	    	
// 	    	if( !empty($translation_path) ){
// 		    	$translations = preg_split('/,/i', $pages[$page_id]['translations'], -1, PREG_SPLIT_NO_EMPTY);
		    	
// 		    	if( !empty($translations) ){
// 		    		$result_value = '';
		    		
// 			    	foreach( TManager::instance()->getFolder( FrontendLanguage::instance()->getLangaugeCode() )->getTranslations() as $translation ){
			    		
// 			    		if( in_array($translation->getHandle(), $translations) ){
// 			    			$result_value .= $this->_addFile($translation);
// 			    		}
// 			    	}
					
// 			    	$result->setValue( $this->_formatXmlString($result_value) );
// 		    	}
// 	    	}
			
	        return $result;
	    }
	    
	    
	    
	    private function _addFile(Translation $translation){
	    	$result = '';
	    	$force_empty_result = false;
	    	
	    	$stylesheet = new XMLElement('xsl:stylesheet');
	    	$stylesheet->setAttributeArray(array('version' => '1.0', 'xmlns:xsl' => 'http://www.w3.org/1999/XSL/Transform'));

	    	$output = new XMLElement('xsl:output');
	    	$output->setAttributeArray(array('method' => 'xml', 'version' => '1.0', 'encoding' => 'utf-8', 'indent' => 'yes', 'omit-xml-declaration' => 'yes'));
	    	$stylesheet->appendChild($output);

	    	$template = new XMLElement('xsl:template');
	    	$template->setAttribute('match', '/');

	    	$instruction = new XMLElement('xsl:copy-of');
	    	$instruction->setAttribute('select', "/translation/data/*");

	    	$template->appendChild($instruction);
	    	$stylesheet->appendChild($template);

	    	$stylesheet->setIncludeHeader(true);

	    	$xsl = $stylesheet->generate(true);

	    	$xml = $translation->getContent();

	    	// Handle where there is `$xml` and the XML is valid
	    	if(strlen($xml) > 0 && !General::validateXML($xml, $errors, false, new XsltProcess)){
	    		$result .= '<error> XML returned is invalid.';
	    		$result .= '<elemet>';
	    		foreach($errors as $e) {
	    			if(strlen(trim($e['message'])) == 0) continue;
	    			 
	    			$result .= '<item>';
	    			$result .= General::sanitize($e['message']);
	    			$result .= '</item>';
	    		}
	    		$result .= '</elemet>';
	    		$result .= '</error>';
	    	}
	    	// If `$xml` is empty, set the `force_empty_result` to true.
	    	elseif(strlen($xml) == 0){
	    		$force_empty_result = true;
	    	}

	    	// If `force_empty_result` is false and `$result` is not an instance of
	    	// XMLElement, build the `$result`.
	    	if(!$force_empty_result) {
				
	    		$proc = new XsltProcess;
	    		$ret = $proc->process($xml, $xsl);

	    		if($proc->isErrors()){
	    			$result .= '<error> XML returned is invalid.';
	    			$result .= '<elemet>';
	    			foreach($proc->getError() as $e) {
	    				if(strlen(trim($e['message'])) == 0) continue;
	    				
	    				$result .= '<item>';
	    				$result .= General::sanitize($e['message']);
	    				$result .= '</item>';
	    			}
	    			$result .= '</elemet>';
	    			$result .= '</error>';
	    		}

	    		else if(strlen(trim($ret)) == 0){
	    			$force_empty_result = true;
	    		}

	    		else{
	    			$result .= self::CRLF . preg_replace('/([\r\n]+)/', '$1	', $ret);
	    		}
	    	}
	    	
	    	if($force_empty_result) $result = '<error>Nothing here</error>';
	    	
	    	return $result;
	    }
	    
	    /**
	     * Courtesy of <a href="http://forums.devnetwork.net/viewtopic.php?p=213989">TJ at devnet</a>
	     */
	    private function _formatXmlString($xml) {

	    	// add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
	    	$xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml);

	    	// now indent the tags
	    	$token      = strtok($xml, "\n");
	    	$result     = ''; // holds formatted version as it is built
	    	$pad        = 0; // initial indent
	    	$matches    = array(); // returns from preg_matches()

	    	// scan each line and adjust indent based on opening/closing tags
	    	while ($token !== false) :

		    	// test for the various tag states
	
		    	// 1. open and closing tags on same line - no change
		    	if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) :
		    	$indent=0;
		    	// 2. closing tag - outdent now
		    	elseif (preg_match('/^<\/\w/', $token, $matches)) :
		    	$pad--;
		    	// 3. opening tag - don't pad this one, only subsequent tags
		    	elseif (preg_match('/^<\w[^>]*[^\/]>.*$/', $token, $matches)) :
		    	$indent=1;
		    	// 4. no indentation needed
		    	else :
		    	$indent = 0;
		    	endif;
	
		    	// pad the line with the required number of leading spaces
		    	$line    = str_pad($token, strlen($token)+$pad, ' ', STR_PAD_LEFT);
		    	$result .= $line . "\n"; // add to the cumulative result, with linefeed
		    	$token   = strtok("\n"); // get the next token
		    	$pad    += $indent; // update the pad size for subsequent lines
	    	endwhile;

	    	return $result;
	    }

	}
