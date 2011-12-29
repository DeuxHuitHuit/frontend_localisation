<?php
	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	/**
	 * Borrowed from EDUI extension. Thanks @ekoes.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 */
	final class FLPageManager implements Singleton {
		
		private static $instance;
		
		/**
		 * This function returns an instance of the FLang class.
		 * It is the only way to create a new FLang, as
		 * it implements the Singleton interface
		 *
		 * @return FLang
		 */
		public static function instance(){
			if (!self::$instance instanceof FLPageManager) {
				self::$instance = new FLPageManager();
			}
			
			return self::$instance;
		}
		
		/**
		 * Get a list of Symphony Pages indexed by `id`. Default values returned are `id`, `title`, `handle` and `parent`.
		 *
		 * @param array $fields (optional) - extra fields
		 * @param string $index_by (optional) - element to index by. Defaults to `id`
		 *
		 * @return array  - found pages
		 */
		public function listAll(array $fields = array(), $index_by = 'id'){
			$fields = array_merge($fields, array('title', 'handle', 'parent', 'translations'));
			
			$query = "SELECT `id`";
			foreach( $fields as $field ){
				$query .= ", `{$field}`";
			}
			$query .= " FROM `tbl_pages` ORDER BY `sortorder` ASC";
			
			try {
				$pages = Symphony::Database()->fetch($query, $index_by);
			}
			catch (DatabaseException $dbe) {
				if( Symphony::Engine() instanceof Administration ){
					Symphony::Engine()->Page->pageAlert($dbe->getMessage(), Alert::ERROR);
				}
			}
			catch (Exception $e){
				if( Symphony::Engine() instanceof Administration ){
					throw new Exception(__('In FLPageManager it died trying to get a list of Pages from Database. Poor fellow.'));
				}
			}
			
			return (array) $pages;
		}

		/**
		 * Checks if given page has children.
		 *
		 * @param integer $page_id
		 */
		public function hasChildren($page_id) {
			return (boolean)Symphony::Database()->fetchVar('id', 0, " SELECT `id` FROM `tbl_pages` WHERE parent = '{$page_id}' LIMIT 1");
		}
		
	}
