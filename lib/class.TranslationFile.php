<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once('class.TranslationFolder.php');
	
	/**
	 * Takes care of a Translation File.
	 * 
	 * @package Frontend Localisation
	 * 
	 * @author Vlad Ghita
	 *
	 */
	final class TranslationFile
	{
		
		/**
		 * Translation folder where this file resides.
		 * 
		 * @var TranslationFolder
		 */
		private $t_folder = null;
		
		/**
		 * File name identifier.
		 * 
		 * @var string
		 */
		private $filename = '';
		
		
		
		/**
		 * Creates a new Translation File that holds translation strings.
		 * 
		 * @param TranslationFolder $t_folder - parent Translation Folder
		 * @param string $filename - name of the file
		 */
		public function __construct(TranslationFolder $t_folder, $filename){
			$this->t_folder = $t_folder;
			$this->filename = $filename;
			
			if( !file_exists($t_folder->getPath() . '/' . $t_folder->getLanguageCode() . '/' . $filename) ){
				$this->setContent();
			}
		}
		
		
		
		/**
		 * Get filename.
		 */
		public function getFilename(){
			return (string) $this->filename;
		}
		
		/**
		 * Set filename to given parameter.
		 *
		 * @param string $filename
		 */
		public function setFilename($filename){
			$content = $this->getContent();
			if( General::deleteFile($this->t_folder->getPath() . '/' . $this->t_folder->getLanguageCode() . '/' . $this->filename, false) ){
				$this->filename = $filename;
				return $this->setContent($content);
			}
			
			return false;
		}
		
		/**
		 * Get string content of file
		 * 
		 * @return string - file contents
		 */
		public function getContent(){
			return (string) file_get_contents($this->t_folder->getPath() . '/' . $this->t_folder->getLanguageCode() . '/' . $this->filename);
		}
		
		/**
		 * Get XML content of file
		 * 
		 * @return DOMDocument - file contents
		 */
		public function getContentXML(){
			$content = new DOMDocument();
			$content->load($this->t_folder->getPath() . '/' . $this->t_folder->getLanguageCode() . '/' . $this->filename);
			
			return $content;
		}
		
		/**
		 * Set file content.
		 * 
		 * @param string $content (optional) - new content of file, defaults to translation_template.xml.
		 * 
		 * @return boolean - true if successful, false otherwise
		 */
		public function setContent($content = null){
			$path = $this->t_folder->getPath();
			$langauge_code = $this->t_folder->getLanguageCode();
			
			$data = $content;
			
			// set default content
			if( empty($content) || ($content === '') || !is_string($content) ){
				$dom = new DOMDocument('1.0', 'UTF-8');
				$dom->formatOutput = true;
				$dom->preserveWhiteSpace = true;
				
				$dom->appendChild(	$this->_createData($dom) );
				$data = $dom->saveXML();
			}
			
			if( !empty($data) ){
				return (boolean) General::writeFile($path . '/' . $langauge_code . '/' . $this->filename, $data);
			}
			
			return false;
		}
		
		/**
		 * Set content from a reference Translation File.
		 * 
		 * @param TranslationFile $ref_file - the reference file. Client code <b>must</b> make sure $ref_file
		 * has a valid XML structure using <i>ensureStructure()</i> before calling this method.
		 * 
		 * @return boolean - true if content was set, false otherwise.
		 */
		public function setRefContent(TranslationFile $ref_file){
			$dom = $ref_file->getContentXML();
			
			$xPath = new DOMXPath($dom);
			$dom_meta = $xPath->query("/data/meta")->item(0);

			$dom_translated = $dom_meta->getElementsByTagName('translated')->item(0);
			$dom_translated->nodeValue = 'no';
			
			$all_languages = (array) FrontendLanguage::instance()->allLanguages();
			$language_code = $this->t_folder->getLanguageCode();
			
			$dom_language = $dom_meta->getElementsByTagName('language')->item(0);
			$dom_language->setAttribute('code', $language_code);
			$dom_language->setAttribute('handle', Lang::createHandle($all_languages[$language_code]));
			$dom_language->nodeValue = $all_languages[$language_code];

			return (boolean) $this->setContent($dom->saveXML());
		}
		
		/**
		 * Make sure a Translation File has standard structure, as defined in Readme
		 * 
		 * @return (boolean) - true if succesfull, false otherwise
		 */
		public function ensureStructure(){
			$old_dom = $this->getContentXML();
			
			$xPath = new DOMXPath($old_dom);
			$old_dom_translated = $xPath->query('/data/meta/translated')->item(0);
			
			$translated = null;
			
			if( !empty($old_dom_translated) && $old_dom_translated instanceof DOMNode ){
				$translated = ($old_dom_translated->nodeValue === 'yes') ? 'yes' : 'no';
			}
			
			$new_dom = new DOMDocument('1.0', 'UTF-8');
			$new_dom->formatOutput = true;
			
			$new_dom_data = $new_dom->createElement('data');
			
			$new_dom_data->appendChild( $this->_createMeta($new_dom, $translated) );
				
			foreach( $old_dom->childNodes->item(0)->childNodes as $old_data_child ){
				if( $old_data_child instanceof DOMElement ){
					if( $old_data_child->nodeName != 'meta' ) {
						$new_dom_data->appendChild( $new_dom->importNode($old_data_child, true) );
					}
				}
			}
			
			$new_dom->appendChild( $new_dom_data );

			return (boolean) $this->setContent( $new_dom->saveXML() );
		}
		
		/**
		 * Checks if the file is translated.
		 * 
		 * @return boolean - true if yes, false otherwise
		 */
		public function isTranslated(){
			$dom = $this->getContentXML();
			
			$xPath = new DOMXPath($dom);
			$dom_translated = $xPath->query("/data/meta/translated")->item(0);
			
			if( !empty($dom_translated) && ($dom_translated instanceof DOMNode) ){
				return (boolean) ($dom_translated->nodeValue === 'yes');
			}
			
			return false;
		}		
		
		
		
		/**
		 * Creates data Node.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $translated (optional) - 'yes' or 'no'
		 * 
		 * @return DOMNode - resulting element
		 */
		private function _createData(DOMDocument $dom, $translated = null){
			$dom_data = $dom->createElement('data');

			$dom_data->appendChild(
				$this->_createMeta($dom, $translated)
			);

			$dom_item = $dom->createElement('item');
			$dom_item->setAttribute('handle', '');

			$dom_data->appendChild( $dom_item );
			
			return $dom_data;
		}
		
		/**
		 * Creates meta information Node.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $translated (optional) - 'yes' or 'no'
		 * 
		 * @return DOMNode - resulting element
		 */
		private function _createMeta(DOMDocument $dom, $translated = null){
			$dom_meta = $dom->createElement('meta');
			
			$dom_meta->appendChild( $this->_createTranslated($dom, $translated) );
			$dom_meta->appendChild( $this->_createLanguage($dom) );
			
			return $dom_meta;
		}
		
		/**
		 * Creates a translated Node for meta information.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $value (optional) - node value: 'yes' or 'no'
		 * 
		 * @return DOMNode
		 */
		private function _createTranslated(DOMDocument $dom, $translated = null){
			if( empty($translated) ) {
				$translated = ( FrontendLanguage::instance()->referenceLanguage() == $this->t_folder->getLanguageCode() ) ? 'yes' : 'no';
			}
				
			return $dom->createElement('translated', $translated);
		}
		
		/**
		 * Creates a language Node for meta information.
		 * 
		 * @param DOMDocument $dom - document node
		 * 
		 * @return DOMNode - new language node
		 */
		private function _createLanguage(DOMDocument $dom){
			$all_languages = (array) FrontendLanguage::instance()->allLanguages();
			$language_code = $this->t_folder->getLanguageCode();
			
			$dom_language = $dom->createElement('language', $all_languages[$language_code]);
			
			$dom_language->setAttribute('code', $language_code);
			$dom_language->setAttribute('handle', Lang::createHandle($all_languages[$language_code]));
			
			return $dom_language;
		}
		
	}
	