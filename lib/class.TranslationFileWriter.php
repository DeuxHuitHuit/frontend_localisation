<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	
	/**
	 * Deals with validation, write / set operations for a Translation File in Symphony admin.
	 * 
	 * @package Frontend Localisation
	 * 
	 * @author Vlad Ghita
	 *
	 */
	final class TranslationFileWriter
	{
		
		
		
		public function __construct(){
			
		}
		
		
		
		/**
		 * Link a Translation File to a Page.
		 * 
		 * @param string $handle - Translation File handle
		 * @param integer $page_id - Page ID
		 */
		public function linkTranslationToPage($handle, $page_id) {
			$query = "SELECT `translations`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";
			
			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0]['translations'];

				if (!in_array($handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ",";
					$result .= $handle;

					$query = "UPDATE `tbl_pages`
					          SET `translations` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";
					
					return Symphony::Database()->query($query);
				}
			}
		}
		
		/**
		 * Unlink a Translation File from a Page.
		 * 
		 * @param string $handle - Translation File handle
		 * @param integer $page_id - Page ID
		 */
		public function unlinkTranslationFromPage($handle, $page_id) {
			$query = "SELECT `translations`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0]['translations'];

				$values = explode(',', $result);
				$idx = array_search($handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

					$query = "UPDATE `tbl_pages`
					          SET `translations` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";

					Symphony::Database()->query($query);
				}
			}
		}
		
		/**
		 * Get all pages that are linked with this Translation.
		 * 
		 * @param string $handle - handle of the Translation to search for.
		 * 
		 * @return array - linked pages.
		 */
		public function getLinkedPages($handle){
			$pages = FLPageManager::instance()->listAll(array('translations'));
			
			$linked_pages = array();
			
			foreach( $pages as $page_id => $page ){
				$page_translations = explode(',', $page['translations']);
				
				if( in_array($handle, $page_translations) ){
					$linked_pages[$page_id] = $page;
				}
			}
			
			return (array) $linked_pages;
		}
		
		/**
		 * Converts business data of a Translation File from XML to array.
		 * 
		 * @param DOMDocument $dom
		 * 
		 * @return array - $result[xPath][handle_of_translation] => value_of_translation
		 */
		public function convertXMLtoArray(DOMDocument $dom){
			$result = array();
			
			$xPath = new DOMXPath($dom);
			$data = $xPath->query("/translation/data")->item(0);
			
			if( !empty($data) && ($data instanceof DOMElement) ){
				$this->_dataXMLwalkRecursive($dom, $data, '', $result);
			}
			
			return (array) $result;
		}
		
		/**
		 * Converts business data of a Translation File from array to XML.
		 * 
		 * @param TranslationFile $t_file
		 * 
		 * @return string - a "data" node containing all elements.
		 */
		public function convertArraytoString(DOMDocument $dom, array $translations){
			$dom->formatOutput = false;
			$dom->preserveWhiteSpace = false;
			
			$dom_xPath = new DOMXPath($dom);
			
			$data = $dom_xPath->query('/translation/data')->item(0);
			
			if( !($data instanceof DOMElement) ){
				$data = $dom->createElement('data');
				$dom->appendChild($data);
			}
			
			foreach( $translations as $xPath => $items ){
				
				// create nodes according to $xPath
				$this->_createNodes($dom, $data, $xPath);
				
				// get the last node
				$node = $dom_xPath->query('/translation'.$xPath)->item(0);
				
				// sovle translation items
				foreach( $items as $handle => $value ){
					
					$item = $dom_xPath->query("/translation{$xPath}/item[ @handle='{$handle}' ]")->item(0);
					
					if( $item instanceof DOMElement ){
						$item->nodeValue = $value;
					}
					else{
						$item = $dom->createElement('item', $value);
						$item->setAttribute('handle', $handle);
					}
					
					$node->appendChild( $item );
				}
			}
			
			// dump $data node without <data></data>
			$result = '';
			
			foreach( $data->childNodes as $child ){
				if( $child instanceof DOMElement )
					$result .= $dom->saveXML($child);
			}
			
			return (string) $result;
		}
		
		
		
		/**
		 * Walks a "data" node XML and adds translation "items" to result
		 * 
		 * @param DOMDocument $dom - parent DOM
		 * @param DOMElement $node - start node
		 * @param string $xPath - full xPath to $node
		 * @param array $result - resulting array
		 */		
		private function _dataXMLwalkRecursive(DOMDocument $dom, DOMElement $node, $xPath, array &$result){
			$xPath .= '/' . $node->nodeName;
			
			foreach( $node->childNodes as $child ){
				if( $child instanceof DOMElement ){

					// if an "item" was found, store it's value
					if( $child->nodeName === 'item' ){
						$handle = $child->getAttribute('handle');
						
						if( !empty($handle) ){
							$result[$xPath][$handle] = $this->_getValueFromNode($dom, $child);
						}
					}

					// else keep searching deeper
					else{
						$this->_dataXMLwalkRecursive($dom, $child, $xPath, $result);
					}
				}
			}
		}
		
		/**
		 * Retrieves the value of a translation as a string while preserving child XML elements.
		 * 
		 * @param DOMDocument $dom - parent DOM
		 * @param DOMElement $child - target node
		 * 
		 * @return string
		 */
		private function _getValueFromNode(DOMDocument $dom, DOMElement $node){
			$value = '';
			
			//we have childs
			if( $node->childNodes->length > 0 ){
				
				foreach( $node->childNodes as $child ){
					
					if( $child instanceof DOMText ){
						$value .= $child->nodeValue;
					}
					
					elseif( $child instanceof DOMElement ){
						$value .= $dom->saveXML($child);
					}
				}
			}
			
			return $value;
		}
		
		/**
		 * Attaches to $data node all nodes indicated by $xPath expression
		 * 
		 * @param DOMDocument $dom - parent dom
		 * @param DOMElement $node - DOMElement to append $xPath to
		 * @param string $target_xPath - xPath expression
		 */
		private function _createNodes(DOMDocument $dom, DOMElement &$node, $target_xPath){
			$node_names = explode('/', trim($target_xPath,'/') );
			
			// pop "data" node as it is irrelelvant.
			unset($node_names[0]);
			
			$xPath = new DOMXPath($dom);
			$query = '/data';
			$iterator = $node;
			
			foreach( $node_names as $node_name){
				$query .= '/' . $node_name;
				
				if( $xPath->query($query)->length == 0 ){
					$iterator->appendChild(
						$dom->createElement($node_name)
					);
				}
				
				$iterator = $iterator->getElementsByTagName($node_name)->item(0);
			}
		}
		
	}
	