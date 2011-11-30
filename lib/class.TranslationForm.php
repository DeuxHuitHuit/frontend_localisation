<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.FLPageManager.php');
	require_once(EXTENSIONS . '/frontend_localisation/lib/class.TranslationFileWriter.php');
	
	
	
	final class TranslationForm {
		
		
		
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
			
			
			
			// Name - text input
			
			$label = Widget::Label(__('Name'));
			$label->appendChild(Widget::Input(
				'fields[name]', General::sanitize($fields['name']), 'text', $attributes
			));
			
			if( isset($errors['name']) ){
				$label = Widget::wrapFormElementWithError($label, $errors['name']);
			}
			$fieldset->appendChild($label);
			
			
			
			// Handle - text input
			
			$label = Widget::Label(__('Handle'));
			$label->appendChild(Widget::Input(
				'fields[handle]', General::sanitize($fields['handle']), 'text', $attributes
			));
			
			if( isset($errors['handle']) ){
				$label = Widget::wrapFormElementWithError($label, $errors['handle']);
			}
			$fieldset->appendChild($label);
			
			$fieldset->appendChild(Widget::Input(
				'fields[old_handle]', General::sanitize($fields['handle']), 'hidden'
			));
			
			
			
			// Translations are available only on edit
			
			if( is_array($fields['translations']) ){
				
				$all_languages = FrontendLanguage::instance()->allLanguages();
				$reference_language = FrontendLanguage::instance()->referenceLanguage();
				
				$content_wrapper = new XMLElement('div');
				$content_wrapper->setAttribute('class', 'translations');
				
				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'tabs');
				
				foreach( $fields['translations'] as $language_code => $translations ){
					
					/* Tabs */
					
					$li = new XMLElement(
						'li',
						($all_languages[$language_code] ? $all_languages[$language_code] : __('Unknown Lang').' : '.$language_code),
						array( 'class' => $language_code . ($language_code == $reference_language ? ' active' : '') )
					);
					
					
					/* Translations */
					
					$language_wrapper = new XMLElement('div',null,array('class' => "tab-panel tab-{$language_code}"));
					
					foreach( $translations as $xPath => $items ){
						$xPath_wrapper = new XMLElement('div', null, array('class' => 'xPath'));
						$xPath_wrapper->appendChild(
							new XMLElement('h3',$xPath)
						);
						
						$id = 0;
						
						foreach( $items as $handle => $value ){
							
							// if render from $_POST values, $value will be an array. Convert it
							if( is_array($value) ){
								$handle = $value['handle'];
								$value = $value['value'];
							}
							
							// remove extra-items not matching in reference_language
							else{
								if( !isset($fields['translations'][$reference_language][$xPath][$handle]) ) continue;
							}
							
							$item_wrapper = new XMLElement('div', null, array('class' => 'item_wrapper'));
							
							
							/* Handle */
							
							$handle_wrapper = new XMLElement('div', null, array('class' => 'handle_wrapper'));
							
							$handle_label = Widget::Label(
								'Handle',
								Widget::Input(
									"fields[translations][{$language_code}][{$xPath}][{$id}][handle]", empty($handle)? '' : $handle,
									'text',
									$reference_language != $language_code ? array('disabled' => 'disabled') : $attributes
								)
							);
							if( isset($errors['translations'][$language_code][$xPath][$id]['handle']) ){
								$handle_label = Widget::wrapFormElementWithError($handle_label, $errors['translations'][$language_code][$xPath][$id]['handle']);
							}
							
							$handle_wrapper->appendChild($handle_label);
							$item_wrapper->appendChild($handle_wrapper);
							
							
							/* Reference value */
							
							$value_wrapper = new XMLElement('div', null, array('class' => 'value_wrapper'));
							
							if( $language_code != $reference_language ){
								$value_wrapper->appendChild(
									Widget::Label(__('Reference value (click to show/hide)'), null, 'reference_value')
								);
								$value_wrapper->appendChild(
									Widget::Textarea('reference_value', 5, 50, $fields['translations'][$reference_language][$xPath][$handle])
								);
							}
							
							/* Value */
							
							$value_label = Widget::Label(
								__('Value'),
								Widget::Textarea("fields[translations][{$language_code}][{$xPath}][{$id}][value]", 5, 50, $value)
							);
							if( isset($errors['translations'][$language_code][$xPath][$id]['value']) ){
								$value_label = Widget::wrapFormElementWithError($value_label, $errors['translations'][$language_code][$xPath][$id]['value']);
							}
							
							$value_wrapper->appendChild($value_label);
							$item_wrapper->appendChild($value_wrapper);
							
							$xPath_wrapper->appendChild($item_wrapper);
							
							$id++;
						}
						
						$language_wrapper->appendChild($xPath_wrapper);
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
			
			$label = Widget::Label(__('Pages'));
			
			if( !is_array($fields['pages']) ) $fields['pages'] = array();
			
			$options = array();
			$old_pages = '';
			
			foreach( FLPageManager::instance()->listAll() as $page_id => $page ){
				$options[] = array(
					$page_id, in_array($page_id, $fields['pages']), Administration::instance()->resolvePageTitle($page_id)
				);
				$old_pages .= in_array($page_id, $fields['pages']) ? $page_id.'_' : '';
			}
			
			$pages_attributes = array('multiple' => 'multiple', 'style' => 'height: 40em;');
			if( $disabled ) $pages_attributes['disabled'] = $disabled;
			
			$label->appendChild(Widget::Select('fields[pages][]', $options, $pages_attributes));
			
			if( isset($errors['pages']) ){
				$label = Widget::wrapFormElementWithError($label, $errors['pages']);
			}
			$fieldset->appendChild($label);
			
			$fieldset->appendChild(Widget::Input(
				'fields[old_pages]', trim($old_pages, '_'), 'hidden'
			));
			
			$div->appendChild($fieldset);
			
			return $div;
			
			
			
			// Form actions
			
			$div = new XMLElement('div');
			$div->setAttribute('class', 'actions');
			$div->appendChild(Widget::Input(
				'action[save]', ($this->page->_context[0] ? __('Save Changes') : __('Create Translation File')),
				'submit', array('accesskey' => 's')
			));
			
			if( $this->page->_context[0] && !$disabled ){
				$div->appendChild(Widget::Input(
					'action[delete]',
					__('Delete'),
					'submit',
					array('accesskey' => 'd','style' => 'float:left')
				));
			}
			
			$this->page->Form->appendChild($div);
		}
		
		/**
		 * Sanitize given fields, except $fields['translations'].
		 *
		 * @param array $fields - form fields.
		 *
		 * @return array - sanitized fields.
		 */
		public function cleanFields(array $fields){
			$clean = $fields;
			
			$clean['name'] = $this->_replaceAmpersands( General::sanitize($fields['name']) );
			
			if( is_array($fields['pages']) ){
				foreach( $fields['pages'] as $key => $page_id ){
					$clean['pages'][$key] = General::sanitize($page_id);
				}
			}
			else{
				$clean['pages'] = array();
			}
			
			$clean['old_pages'] = General::sanitize($fields['old_pages']);
			$clean['handle'] = General::sanitize($fields['handle']);
			
			if( empty($fields['handle']) ){
				$clean['handle'] = General::createFilename($fields['name'], '_');
			}
			else{
				$clean['handle'] = General::createFilename($fields['handle'], '_');
			}
			
			$clean['old_handle'] = General::sanitize($fields['old_handle']);
			
			if( isset($fields['translations']) && is_array($fields['translations']) ){
				foreach( $fields['translations'] as $language_code => $translations ){
					foreach( $translations as $xPath => $items ){
						foreach( $items as $id => $item ){
							$item['value'] = $this->_replaceAmpersands( General::sanitize($item['value']) );
						}
					}
				}
			}
			
			return (array) $clean;
		}
		
		
		
		private function _replaceAmpersands($value, $replacement = '') {
			return preg_replace('/&(amp|#38);/i', $replacement, trim($value));
		}
		
	}
