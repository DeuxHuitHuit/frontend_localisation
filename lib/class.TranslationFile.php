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
		private $handle = '';
		
		/**
		 * Hardcoded file extension.
		 * 
		 * @var string
		 */
		private $extension = 'xml';
		
		
		
		/**
		 * Creates a new Translation File that holds translation strings.
		 * 
		 * @param TranslationFolder $t_folder - parent Translation Folder
		 * @param string $handle - handle of the file
		 */
		public function __construct(TranslationFolder $t_folder, $handle){
			$this->t_folder = $t_folder;
			$this->handle = $handle;
			
			if( !file_exists($t_folder->getPath() . '/' . $t_folder->getLanguageCode() . '/' . $handle . '.' . $this->extension) ){
				$this->setContent();
			}
		}
		
		
		
		/**
		 * Get handle.
		 */
		public function getHandle(){
			return (string) $this->handle;
		}
		
		/**
		 * Set handle to given parameter.
		 *
		 * @param string $handle
		 */
		public function setFilename($handle){
			$content = $this->getContent();
			if( General::deleteFile($this->t_folder->getPath() . '/' . $this->t_folder->getLanguageCode() . '/' . $this->handle . '.' . $this->extension, false) ){
				$this->handle = $handle;
				return $this->setContent($content);
			}
			
			return false;
		}
		
		/**
		 * Get the name of the file.
		 * 
		 * @return string - name of the file stored in Meta if foud, else an empty string.
		 */
		public function getName(){
			$dom = $this->getContentXML();
			
			$xPath = new DOMXPath($dom);
			$dom_name = $xPath->query("/translation/meta/name")->item(0);
			
			if( !empty($dom_name) && ($dom_name instanceof DOMElement) ){
				return (string) $dom_name->nodeValue;
			}
			
			return (string) '';
		}
		
		/**
		 * @todo to implement this
		 * 
		 * Set "name" of the file.
		 * 
		 * @param string $name
		 */
		public function setName($name){
			$dom = $this->getContentXML();
			
			$dom->formatOutput = false;
			$dom->preserveWhiteSpace = false;
			
			$xPath = new DOMXPath($dom);
			
			$meta = $xPath->query('/translation/meta')->item(0);
			
			$meta_name = $meta->getElementsByTagName('name')->item(0);
			
			if( $meta_name instanceof DOMElement ){
				$meta_name->nodeValue = $name;
			}
			else{
				$meta->appendChild(
					$dom->createElement('name', $name)
				);
			}
			
			$content = $dom->saveXML();
			return $this->setContent($content);
		}
		
		/**
		 * Set translation business "data". 
		 * 
		 * @param string $data (optional) - DOMElement node to append.
		 * 
		 * @return boolean - true if data is set false otherwise
		 */
		public function setData($data = ''){
			$dom = $this->getContentXML();
			
			$translation = $dom->childNodes->item(0);
			$translation->getElementsByTagName('data')->item(0)->nodeValue = $data;
			
			$content = $dom->saveXML();
			
			return $this->setContent($content);
		}
		
		/**
		 * Get string content of file
		 * 
		 * @return string - file contents
		 */
		public function getContent(){
			return (string) file_get_contents($this->t_folder->getPath() . '/' . $this->t_folder->getLanguageCode() . '/' . $this->handle . '.' . $this->extension);
		}
		
		/**
		 * Get XML content of file
		 * 
		 * @return DOMDocument - file contents
		 */
		public function getContentXML(){
			$dom = new DOMDocument();
			$dom->loadXML( $this->getContent() );
			
			return $dom;
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
			if( empty($content) || !is_string($content) ){
				$dom = new DOMDocument('1.0', 'UTF-8');
				$dom->formatOutput = true;
				$dom->preserveWhiteSpace = true;
				
				$dom->appendChild(	$this->_createTranslation($dom) );
				$data = $dom->saveXML();
			}
			
			if( !empty($data) ){
				$data = $this->_sanitizeXML($data);
				
				return (boolean) General::writeFile($path . '/' . $langauge_code . '/' . $this->handle . '.' . $this->extension, $data);
			}
			
			return false;
		}
		
		/**
		 * Make sure a Translation File has standard structure, as defined in Readme.
		 * 
		 * @return (boolean) - true if succesfull, false otherwise
		 */
		public function ensureStructure(){
			$old_dom = $this->getContentXML();
			
			$xPath = new DOMXPath($old_dom);
			
			$new_dom = new DOMDocument('1.0', 'UTF-8');
			$new_dom->formatOutput = true;
			$new_dom->preserveWhiteSpace = true;
			
			$new_dom_translation = $new_dom->createElement('translation');
			
			
			// meta information
			$old_dom_name = $xPath->query('/translation/meta/name')->item(0);
			$name = null;
			
			if( !empty($old_dom_name) && ($old_dom_name instanceof DOMElement) ){
				$name = $old_dom_name->nodeValue;
			}
			
			$new_dom_translation->appendChild( $this->_createMeta($new_dom, $name) );
			
			
			// business data
			$old_dom_data = $xPath->query('/translation/data')->item(0);
			
			// make sure "data" node exists in /translation/data
			if( $old_dom_data instanceof DOMElement ){
				$new_dom_translation->appendChild(
					$new_dom->importNode($old_dom_data, true)
				);
			}
			
			// there is no "data" node
			else{
				$new_dom_translation->appendChild( $this->_createData($new_dom) );
			}
			
			$new_dom->appendChild( $new_dom_translation );

			return (boolean) $this->setContent( $new_dom->saveXML() );
		}
		
		
		
		/**
		 * Creates data Node.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $name (optional) - name of the file
		 * @param array $data_children (optional) - contains business data children to append.
		 * 
		 * @return DOMElement - resulting element
		 */
		private function _createTranslation(DOMDocument $dom, $name = null, $data_children = array()){
			$translation = $dom->createElement('translation');

			$translation->appendChild( $this->_createMeta($dom, $name) );
			$translation->appendChild( $this->_createData($dom, $data_children) );
			
			return $translation;
		}
		
		/**
		 * Creates meta information Node.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $name (optional) - name of the file
		 * 
		 * @return DOMElement - resulting element
		 */
		private function _createMeta(DOMDocument $dom, $name = null){
			$meta = $dom->createElement('meta');
			
			$meta->appendChild( $this->_createName($dom, $name) );
			$meta->appendChild( $this->_createLanguage($dom) );
			
			return $meta;
		}
		
		/**
		 * Creates a name Node for meta information.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param string $name (optional) - name of the file. Defaults to handle.
		 * 
		 * @return DOMElement
		 */
		private function _createName(DOMDocument $dom, $name = null){
			if( empty($name) ) {
				$name = $this->handle;
			}
				
			return $dom->createElement('name', $name);
		}
		
		/**
		 * Creates a language Node for meta information.
		 * 
		 * @param DOMDocument $dom - document node
		 * 
		 * @return DOMElement - new language node
		 */
		private function _createLanguage(DOMDocument $dom){
			$all_languages = (array) FrontendLanguage::instance()->allLanguages();
			$language_code = $this->t_folder->getLanguageCode();
			
			$language = $dom->createElement('language', $all_languages[$language_code]);
			
			$language->setAttribute('code', $language_code);
			$language->setAttribute('handle', Lang::createHandle($all_languages[$language_code]));
			
			return $language;
		}
		
		/**
		 * Creates business data Node.
		 * 
		 * @param DOMDocument $dom - document node
		 * @param array $data_children (optional) - contains business data children to append.
		 * 
		 * @return DOMElement - resulting element
		 */
		private function _createData(DOMDocument $dom, $data_children = array()){
			$data = $dom->createElement('data');
			
			// set default node
			if( empty($data_children) || !is_array($data_children) ){
				$item = $dom->createElement('item');
				$item->setAttribute('handle', '');
				
				$data_children[] = $item;
			}
			
			// add all children
			foreach( $data_children as $child ){
				try {
					$data->appendChild( $child );
				} catch (Exception $e) {
					$data->appendChild( $dom->importNode($child, true) );
				}
			}
			
			return $data;
		}
		
		/**
		 * Convert special chars to their HTML equivalents.
		 * 
		 * @param unknown_type $data
		 */
		private function _sanitizeXML($data){
			$data = htmlspecialchars_decode($data);
			
			// replace carrige return. For an unknown reason to me, DOMDocument makes \r => &#13;
			// I must escape it now.
			$data = preg_replace('/&#13;/i', '', $data);
			
			// keep ampersands
//			$data = preg_replace('/&|&amp;/i', '&#38;', trim($data));
				
			return $data;
		}
	}
	