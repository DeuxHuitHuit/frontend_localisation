<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once (EXTENSIONS . '/frontend_localisation/lib/class.TParser.php');
	
	
	
	/**
	 * Deals with data parsing for XML Translations.
	 *
	 * @package xml
	 *
	 * @author Vlad Ghita
	 */
	final class XML_TParser extends TParser {
		
		/**
		 * @see TParser::asTArray()
		 */
		public function asTArray(Translation $translation){
			$contents = $translation->data()->getContent();
			if( empty($contents) ) return array();
			
			$result = array();
			
			$doc = new DOMDocument();
			$doc->loadXML($contents);
			
			$xPath = new DOMXPath($doc);
			
			foreach( $xPath->query("//item[ @handle != '' ]") as $item ){
				/* @var $item DOMElement */
				
				// remove root and item
				$path = array_filter( explode('/', $item->getNodePath()) );
				array_shift($path);array_pop($path);
				
				$context = '/'.implode('/',$path);
				
				if( empty($result[$context]) ) $result[$context] = array();
				
				$result[$context][$item->getAttribute('handle')] = array(
					'handle' => $item->getAttribute('handle'),
					'value' => $this->_getValueFromNode($doc, $item)
				);
			}
			
			return (array) $result;
		}
		
		/**
		 * @see TParser::TArray2string()
		 */
		public function TArray2string(array $translations){
			$doc = new DOMDocument('1.0', 'utf-8');
			$doc->formatOutput = true;
			
			$doc_data = $doc->createElement('data');
			$doc->appendChild( $doc_data );
			
			$doc_xPath = new DOMXPath($doc);
			
			foreach( $translations as $xPath => $items ){
			
				// create nodes according to $xPath
				$this->_createNodes($doc, $doc_data, $xPath);
				
				// get the last node
				$node = $doc_xPath->query('/data'.($xPath=='/'?'':$xPath))->item(0);
			
				// solve translation items
				foreach( $items as $item ){
					$doc_item = $doc->createElement('item');
					$doc_item->setAttribute('handle', $item['handle']);
					
					$doc_item_cdata = $doc->createCDATASection( $item['value'] );
					
					$doc_item->appendChild($doc_item_cdata);
					$node->appendChild( $doc_item );
				}
			}
			
			return $doc->saveXML();
		}
		
		
		
		/**
		 * Retrieves the value of a translation as a string while preserving child XML elements.
		 *
		 * @param DOMDocument $doc - parent DOM
		 * @param DOMElement $node - target node
		 *
		 * @return string
		 */
		private function _getValueFromNode(DOMDocument $doc, DOMElement $node){
			$value = '';
		
			//we have childs
			foreach( $node->childNodes as $child ){
				
				if( $child instanceof DOMText ){
					$value .= $child->nodeValue;
				}

				elseif( $child instanceof DOMElement ){
					$value .= $doc->saveXML($child);
				}
			}
		
			return $value;
		}
	
		/**
		 * Attaches to $node all nodes indicated by $xPath expression
		 *
		 * @param DOMDocument $doc - parent dom
		 * @param DOMElement $node (pointer) - DOMElement to append $xPath to
		 * @param string $target_xPath - xPath expression
		 */
		private function _createNodes(DOMDocument $doc, DOMElement &$node, $target_xPath){
			$node_names = array_filter( explode('/', trim($target_xPath,'/')) );
		
			$xPath = new DOMXPath($doc);
			$query = '/data';
			$iterator = $node;
		
			foreach( $node_names as $node_name ){
				$query .= '/' . $node_name;
		
				if( $xPath->query($query)->length == 0 ){
					$iterator->appendChild(
						$doc->createElement($node_name)
					);
				}
		
				$iterator = $iterator->getElementsByTagName($node_name)->item(0);
			}
		}
	}
