<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	abstract class TParser {
		
		/**
		 * Get translation data as array of translations. This is the common interface 
		 * for various storage formats.
		 *
		 * @param Translation $translation;
		 *
		 * 	Array(
		 *		[$context] => Array(
		 *			[$handle] => Array(
		 *				['handle'] => handle for translation string
		 *				['value'] => value for translation string
		 *			)
		 *		)
		 * 	)
		 *
		 *  $context = the context for a group of translation items.
		 *  $handle = identifier. Usually equals to handle of translation string
		 *
		 *
		 * E.g. for an XML based translation:
		 *
		 *  <data>
		 *  	<news>
		 *  		<item handle="item1">value behind item 1</item>
		 *  		<left-panel>
		 *  			<item handle="hallo-welt">Hello world!</item>
		 *  			<item handle="hello-symphony">Hello Symphony!</item>
		 *  		</left-panel>
		 *  	</news>
		 *  </data>
		 *
		 *  Array(
		 *  		[/news] => Array(
		 *  				[item1] => Array(
		 *  						['handle'] => item1
		 *  						['value'] => value behind item 1
		 *  				)
		 *  		)
		 *  		[/news/left-panel] => Array(
		 *  				[hallo-welt] => Array(
		 *  						['handle'] => hallo-welt
		 *  						['value'] => Hello world!
		 *  				)
		 *  				[hello-symphony] => Array(
		 *  						['handle'] => hello-symphony
		 *  						['value'] => Hello Symphony!
		 *  				)
		 *  		)
		 *  )
		 *
		 * @return array
		 */
		abstract public function asTArray(Translation $translation);
		
		/**
		 * Set translation data from array of translations.
		 *
		 * @param array $translations - @see TFileData::asTArray() for information about $translations structure.
		 *
		 * @return string - resulting string
		 *
		 * @see TFileData::asTArray()
		 */
		abstract public function TArray2string(array $translations);
	}
