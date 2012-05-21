<?php

	if( !defined('__IN_SYMPHONY__') ) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');



	require_once(EXTENSIONS.'/frontend_localisation/lib/class.FLPageManager.php');



	/**
	 * Deals with relationships between Translations and Symphony Pages.
	 *
	 * @package Frontend Localisation
	 *
	 * @author  Vlad Ghita
	 */
	final class TLinker
	{

		/**
		 * Link a Translation to a Page.
		 *
		 * @param string $handle   - Translation handle
		 * @param integer $page_id - Page ID
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function linkToPage($handle, $page_id){
			$result = Symphony::Database()->fetchVar('translations', 0, "SELECT `translations` FROM `tbl_pages` WHERE `id` = '{$page_id}'");

			if( !in_array($handle, explode(',', $result)) ){

				if( strlen($result) > 0 ) $result .= ",";
				$result .= $handle;

				$query = "UPDATE `tbl_pages` SET `translations` = '".MySQL::cleanValue($result)."' WHERE `id` = '{$page_id}'";

				return (boolean)Symphony::Database()->query($query);
			}

			return true;
		}

		/**
		 * Unlink a Translation from a Page.
		 *
		 * @param string $handle   - Translation handle
		 * @param integer $page_id - Page ID
		 *
		 * @return boolean - true if success, false otherwise
		 */
		public function unlinkFromPage($handle, $page_id){
			$result = Symphony::Database()->fetchVar('translations', 0, "SELECT `translations` FROM `tbl_pages` WHERE `id` = '{$page_id}'");

			$values = explode(',', $result);
			$idx = array_search($handle, $values, false);

			if( $idx !== false ){
				array_splice($values, $idx, 1);
				$result = implode(',', $values);

				$query = "UPDATE `tbl_pages` SET `translations` = '".MySQL::cleanValue($result)."' WHERE `id` = '{$page_id}'";

				return (boolean)Symphony::Database()->query($query);
			}

			return true;
		}

		/**
		 * Get all pages that are linked to this Translation.
		 *
		 * @param string $handle - handle of Translation to search for.
		 *
		 * @return array - [$page_id] => $page_data
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

			return (array)$linked_pages;
		}
	}
