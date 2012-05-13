<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once ('class.Translation.php');



	/**
	 * Management for Translation of a Symphony Page. It improves Translation's name management
	 *
	 * @package Frontend Localisation
	 *
	 * @author  Vlad Ghita
	 */
	class TranslationPage extends Translation
	{

		public function setName($name = ''){
			$name = 'P';
			$title_field = 'title';

			// support for Page LHandles
			$page_lhandles = ExtensionManager::fetchStatus(array('handle' => 'page_lhandles'));
			if( $page_lhandles[0] === EXTENSION_ENABLED ){
				$title_field = 'plh_t-'.$this->getFolder()->getLangCode();
			}

			$pages = FLPageManager::instance()->listAll(array($title_field));

			$bits = explode('_', $this->handle);
			array_shift($bits);

			$p_id = null;

			while( $bit = array_shift($bits) ){

				foreach( $pages as $page ){
					if( ($page['handle'] == $bit) && ($page['parent'] == $p_id) ){
						$p_id = $page['id'];

						$name .= ' : '.$pages[$p_id][$title_field];
						break;
					}
				}
			}

			return (boolean)parent::setName($name);
		}


	}
