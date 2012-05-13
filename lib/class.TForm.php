<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLPageManager.php');



	final class TForm
	{

		/**
		 * Render form elements for a Translation.
		 *
		 * @param       &$wrapper
		 * @param array $fields - form fields
		 * @param array $errors - form errors
		 *
		 * @return XMLElement - entire form
		 */
		public function render(XMLElement &$wrapper, array $fields, array $errors){
			$disabled = !Administration::instance()->Author->isDeveloper();

			/* Primary */
			$fieldset = new XMLElement('fieldset', null, array('class' => 'primary column'));
				$this->_appendHandle($fieldset, $fields, $disabled);
				$wrapper_meta = new XMLElement('div', null, array('class' => 'two columns'));
					$this->_appendStorageFormat($wrapper_meta, $fields, $disabled);
					$this->_appendType($wrapper_meta, $fields, $disabled);
				$fieldset->appendChild($wrapper_meta);
				$this->_appendContent($fieldset, $fields, $errors, $disabled);
			$wrapper->appendChild($fieldset);

			/* Sidebar */
			$fieldset = new XMLElement('fieldset', null, array('class' => 'secondary column'));
				$this->_appendPages($fieldset, $fields, $errors, $disabled);
			$wrapper->appendChild($fieldset);

			return $wrapper;
		}



		private function _appendHandle(XMLElement &$wrapper, $fields, $disabled){
			$label = Widget::Label(
				__('Handle'),
				Widget::Input('fields[handle]', General::sanitize($fields['handle'])),
				null, null, $disabled ? array('style' => 'display:none;') : array()
			);

			if( isset($errors['handle']) ){
				$label = Widget::Error($label, $errors['handle']);
			}

			$wrapper->appendChild($label);
			$wrapper->appendChild(Widget::Input('fields[old_handle]', General::sanitize($fields['handle']), 'hidden'));
		}

		private function _appendStorageFormat(XMLElement &$wrapper, $fields, $disabled){
			$options = array();

			foreach( TManager::instance()->getSupportedStorageFormats() as $storage_format => $info ){
				$options[] = array(
					$storage_format, $fields['storage_format'] === $storage_format, $info['description']
				);
			}

			$label = Widget::Label(
				__('Storage format'),
				Widget::Select('fields[storage_format]', $options),
				'column',
				null,
				$disabled ? array('style' => 'display:none;') : array()
			);

			$label->appendChild(Widget::Input('fields[old_storage_format]', General::sanitize($fields['storage_format']), 'hidden'));
			$wrapper->appendChild($label);
		}

		private function _appendType(XMLElement &$wrapper, $fields, $disabled){
			$options = array();

			foreach( array('' => __('Normal'), 'page' => __('Page')) as $type => $info ){
				$options[] = array(
					$type, $fields['type'] == $type, $info
				);
			}

			$label = Widget::Label(
				__('Type'),
				Widget::Select('fields[type]', $options),
				'column',
				null,
				$disabled ? array('style' => 'display:none;') : array()
			);

			$label->appendChild(new XMLElement('p', __('Set type of Translation. <b>%1$s</b> for Symphony Pages, <b>%2$s</b> otherwise.', array(__('Page'), __('Normal'))), array('class' => 'help')));

			$label->appendChild(Widget::Input('fields[old_type]', General::sanitize($fields['type']), 'hidden'));
			$wrapper->appendChild($label);
		}

		private function _appendContent(XMLElement &$wrapper, $fields, $errors, $disabled){
			if( is_array($fields['translations']) ){
				$attributes = $disabled ? array('disabled' => 'yes') : array();

				$all_langs = FLang::instance()->getAllLangs();
				$ref_lang = TManager::instance()->getRefLang();

				$content_wrapper = new XMLElement('div');
				$content_wrapper->setAttribute('class', 'field-multilingual');

				$ul = new XMLElement('ul');
				$ul->setAttribute('class', 'tabs');

				foreach( $fields['translations'] as $lc => $translations ){

					$lc_wrapper = new XMLElement('div', null, array('class' => "tab-panel tab-{$lc}"));


					/* Tabs */

					$li = new XMLElement(
						'li',
						$all_langs[$lc] ? $all_langs[$lc] : __('Unknown Lang').' : '.$lc,
						array('class' => $lc.($lc == $ref_lang ? ' active' : ''))
					);


					// Name - text input

					$label = Widget::Label(
						__('Name'),
						Widget::Input(
							"fields[name][{$lc}]",
							$fields['name'][$lc],
							'text',
							$attributes
						)
					);

					$lc_wrapper->appendChild($label);



					/* Translations */

					foreach( $translations as $context => $items ){
						$context_xml = new XMLElement('div', null, array('class' => 'context'));

						$context_xml->appendChild(new XMLElement('h2', __('Context: ').trim($context, '/')));

						foreach( $items as $handle => $item ){

							$item_xml = new XMLElement('div', null, array('class' => 'two columns'));


							/* Handle */

							$handle_xml = new XMLElement('div', null, array('class' => 'column'));

							$handle_label = Widget::Label(
								'Handle',
								Widget::Input(
									"fields[translations][{$lc}][{$context}][{$handle}][handle]",
									empty($item['handle']) ? '' : $item['handle'],
									'text',
									$ref_lang != $lc ? array('disabled' => 'disabled') : $attributes
								)
							);

							if( isset($errors['translations'][$lc][$context][$handle]['handle']) ){
								$handle_label = Widget::Error($handle_label, $errors['translations'][$lc][$context][$handle]['handle']);
							}

							$handle_xml->appendChild($handle_label);
							$item_xml->appendChild($handle_xml);


							/* Reference value */

							$value_xml = new XMLElement('div', null, array('class' => 'column'));

							if( $lc != $ref_lang ){
								$value_xml->appendChild(
									Widget::Label('<span class="fl_plus">(+)</span><span class="fl_minus">(-)</span> '.__('Reference value'), null, 'reference_value')
								);
								$value_xml->appendChild(
									Widget::Textarea('reference_value', 2, 50, $fields['translations'][$ref_lang][$context][$handle]['value'])
								);
							}


							/* Value */

							$value_label = Widget::Label(
								__('Value'),
								Widget::Textarea("fields[translations][{$lc}][{$context}][{$handle}][value]", 2, 50, $item['value'])
							);
							if( isset($errors['translations'][$lc][$context][$handle]['value']) ){
								$value_label = Widget::Error($value_label, $errors['translations'][$lc][$context][$handle]['value']);
							}

							$value_xml->appendChild($value_label);
							$item_xml->appendChild($value_xml);

							$context_xml->appendChild($item_xml);
						}

						$lc_wrapper->appendChild($context_xml);
					}


					if( $lc === $ref_lang ){
						$ul->prependChild($li);
						$content_wrapper->prependChild((isset($errors['content']) ? Widget::Error($lc_wrapper, $errors['content']) : $lc_wrapper));
					}
					else{
						$ul->appendChild($li);
						$content_wrapper->appendChild((isset($errors['content']) ? Widget::Error($lc_wrapper, $errors['content']) : $lc_wrapper));
					}
				}

				$content_wrapper->prependChild($ul);

				$wrapper->appendChild($content_wrapper);
			}
		}

		private function _appendPages(XMLElement &$wrapper, $fields, $errors, $disabled){
			$label = Widget::Label(__('Pages'), null, null, null, $disabled ? array('style' => 'display:none;') : array());

			if( !is_array($fields['pages']) ) $fields['pages'] = array();

			$options = array();
			$old_pages = '';

			foreach( FLPageManager::instance()->listAll() as $page_id => $page ){
				$options[] = array(
					$page_id, in_array($page_id, $fields['pages']), PageManager::resolvePageTitle($page_id)
				);
				$old_pages .= in_array($page_id, $fields['pages']) ? $page_id.'_' : '';
			}

			uasort($options, "fl_name_sort");

			$label->appendChild(Widget::Select('fields[pages][]', $options, array('multiple' => 'multiple', 'style' => 'height: 40em;')));

			if( isset($errors['pages']) ){
				$label = Widget::Error($label, $errors['pages']);
			}

			$wrapper->appendChild($label);
			$wrapper->appendChild(Widget::Input('fields[old_pages]', trim($old_pages, '_'), 'hidden'));
		}

	}

	function fl_name_sort($a, $b){
		return $a[2] > $b[2];
	}
