<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/**
	 * Borrowed from EDUI extension. Thanks @ekoes.
	 * 
	 * @package Frontend Localisation
	 * 
	 * @author Vlad Ghita
	 */
	class FrontendLocalisationPageManager {
		
		public function listAll(){
			$query = "SELECT `id`, `title`, `handle`, `parent`, `translations` FROM `tbl_pages` ORDER BY `title` ASC";
			
			$pages = Symphony::Database()->fetch($query, 'id');

			return $pages;
		}

		public function linkTranslation($t_handle, $page_id) {
			$this->_linkResource("translations", $t_handle, $page_id);
		}

		public function unlinkTranslation($t_handle, $page_id) {
			$this->_unlinkResource("translations", $t_handle, $page_id);
		}
		
		
		
		private function _linkResource($field, $r_handle, $page_id) {
			$query = "SELECT `$field`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				if (!in_array($r_handle, explode(',', $result))) {

					if (strlen($result) > 0) $result .= ",";
					$result .= $r_handle;

					$query = "UPDATE `tbl_pages`
					          SET `$field` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";

					Symphony::Database()->query($query);
				}
			}
		}

		private function _unlinkResource($field, $r_handle, $page_id) {
			$query = "SELECT `$field`
			          FROM `tbl_pages`
			          WHERE `id` = '$page_id'";

			$results = Symphony::Database()->fetch($query);

			if (is_array($results) && count($results) == 1) {
				$result = $results[0][$field];

				$values = explode(',', $result);
				$idx = array_search($r_handle, $values, false);

				if ($idx !== false) {
					array_splice($values, $idx, 1);
					$result = implode(',', $values);

					$query = "UPDATE `tbl_pages`
					          SET `$field` = '" . MySQL::cleanValue($result) . "'
					          WHERE `id` = '$page_id'";

					Symphony::Database()->query($query);
				}
			}
		}
	
	}
