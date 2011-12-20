<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	
	
	
	final class TForm {
		
		/**
		 * Render form elements for a Translation.
		 *
		 * @param array $fields - form fields
		 * @param array $errors - form errors
		 *
		 * @return XMLElement - entire form
		 */
		public function render(array $fields, array $errors) {
			
			$disabled = !Administration::instance()->Author->isDeveloper();
			$attributes = $disabled ? array('disabled' => 'yes') : array();
			
			
			
			// Wrapper
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'group');
			
			
			
			// Main
			
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'primary');
			
			
			
			// Handle - text input
			
			$label = Widget::Label(
				__('Handle'), 
				Widget::Input( 'fields[handle]', General::sanitize($fields['handle'])),
				null, null, $disabled ? array('style' => 'display:none;') : array()
			);
			
			if( isset($errors['handle']) ){
				$label = Widget::wrapFormElementWithError($label, $errors['handle']);
			}
			
			$fieldset->appendChild($label);
			$fieldset->appendChild(Widget::Input( 'fields[old_handle]', General::sanitize($fields['handle']), 'hidden' ));
			
			
			
			// Translations are available only on edit
			
			if( is_array($fields['translations']) ){
				
				$all_languages = FrontendLanguage::instance()->allLanguages();
				$reference_language = FrontendLanguage::instance()->referenceLanguage();
				
				$content_wrapper = new XMLElement('div');
				$content_wrapper->setAttribute('class', 'translations');
				
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'tabs');
				
				foreach( $fields['translations'] as $language_code => $translations ){
					
					$language_wrapper = new XMLElement('div',null,array('class' => "tab-panel tab-{$language_code}"));
					
					/* Tabs */
					
					$li = new XMLElement(
						'li',
						($all_languages[$language_code] ? $all_languages[$language_code] : __('Unknown Lang').' : '.$language_code),
						array( 'class' => $language_code . ($language_code == $reference_language ? ' active' : '') )
					);
					
					
					
					// Name - text input
					
					$label = Widget::Label(
						__('Name'),
						Widget::Input(
							"fields[name][{$language_code}]",
							$fields['name'][$language_code],
							'text',
							$attributes
						)
					);
					
					$language_wrapper->appendChild($label);
					
					
					
					/* Translations */
					
					foreach( $translations as $context => $items ){
						$context_wrapper = new XMLElement('div', null, array('class' => 'context'));
						
						$context_wrapper->appendChild( new XMLElement('h2', __('Context: ').$context) );
						
						foreach( $items as $handle => $item ){
							
							$item_wrapper = new XMLElement('div', null, array('class' => 'item_wrapper'));
							
							
							/* Handle */
							
							$handle_wrapper = new XMLElement('div', null, array('class' => 'handle_wrapper'));
							
							$handle_label = Widget::Label(
								'Handle',
								Widget::Input(
									"fields[translations][{$language_code}][{$context}][{$handle}][handle]",
									empty($item['handle'])? '' : $item['handle'],
									'text',
									$reference_language != $language_code ? array('disabled' => 'disabled') : $attributes
								)
							);
							
							if( isset($errors['translations'][$language_code][$context][$handle]['handle']) ){
								$handle_label = Widget::wrapFormElementWithError($handle_label, $errors['translations'][$language_code][$context][$handle]['handle']);
							}
							
							$handle_wrapper->appendChild($handle_label);
							$item_wrapper->appendChild($handle_wrapper);
							
							
							/* Reference value */
							
							$value_wrapper = new XMLElement('div', null, array('class' => 'value_wrapper'));
							
							if( $language_code != $reference_language ){
								$value_wrapper->appendChild(
									Widget::Label('<span class="fl_plus">(+)</span><span class="fl_minus">(-)</span> '.__('Reference value'), null, 'reference_value')
								);
								$value_wrapper->appendChild(
									Widget::Textarea('reference_value', 3, 50, $fields['translations'][$reference_language][$context][$handle]['value'])
								);
							}
							
							
							/* Value */
							
							$value_label = Widget::Label(
								__('Value'),
								Widget::Textarea("fields[translations][{$language_code}][{$context}][{$handle}][value]", 3, 50, $item['value'])
							);
							if( isset($errors['translations'][$language_code][$context][$handle]['value']) ){
								$value_label = Widget::wrapFormElementWithError($value_label, $errors['translations'][$language_code][$context][$handle]['value']);
							}
							
							$value_wrapper->appendChild($value_label);
							$item_wrapper->appendChild($value_wrapper);
							
							$context_wrapper->appendChild($item_wrapper);
						}
						
						$language_wrapper->appendChild($context_wrapper);
					}
					
					
					if( $language_code == $reference_language ){
						$ul->prependChild($li);
						$content_wrapper->prependChild(( isset($errors['content']) ? Widget::wrapFormElementWithError($language_wrapper, $errors['content']) : $language_wrapper ));
					}
					else{
						$ul->appendChild($li);
						$content_wrapper->appendChild(( isset($errors['content']) ? Widget::wrapFormElementWithError($language_wrapper, $errors['content']) : $language_wrapper ));
					}
				}
				
				$content_wrapper->prependChild($ul);
				
				$fieldset->appendChild($content_wrapper);
			}
			
			$div->appendChild($fieldset);
			
			
			
			// Sidebar
		
			$fieldset = new XMLElement('fieldset');
			$fieldset->setAttribute('class', 'secondary');
			
			
			
			// Pages - select
			
			$label = Widget::Label(__('Pages'), null, null, null, $disabled ? array('style' => 'display:none;') : array());
			
			if( !is_array($fields['pages']) ) $fields['pages'] = array();
			
			$options = array();
			$old_pages = '';
			
			foreach( FLPageManager::instance()->listAll() as $page_id => $page ){
				$options[] = array(
					$page_id, in_array($page_id, $fields['pages']), Administration::instance()->resolvePageTitle($page_id)
				);
				$old_pages .= in_array($page_id, $fields['pages']) ? $page_id.'_' : '';
			}
			
			$label->appendChild(Widget::Select('fields[pages][]', $options, array('multiple' => 'multiple', 'style' => 'height: 40em;')));
			
			if( isset($errors['pages']) ){
				$label = Widget::wrapFormElementWithError($label, $errors['pages']);
			}
			$fieldset->appendChild($label);
			$fieldset->appendChild(Widget::Input( 'fields[old_pages]', trim($old_pages, '_'), 'hidden' ));
			
			$div->appendChild($fieldset);
			
			return $div;
		}
	}
