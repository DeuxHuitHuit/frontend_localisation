<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	
	
	/**
	 * A container for basic file management.
	 *
	 * @package Frontend Localisation
	 *
	 * @author Vlad Ghita
	 */
	abstract class TFile
	{
		
		/**
		 * Translation to which belongs.
		 *
		 * @var Translation
		 */
		protected $parent = null;
		
		/**
		 * File type. Meta, Data etc.
		 *
		 * @var string
		 */
		protected $type = '';
		
		/**
		 * File extension.
		 *
		 * @var string
		 */
		protected $extension = '';
		
		
		public function __construct(Translation $translation){
			$this->parent = $translation;
		}
		
		
		/**
		 * Get content. If file not found, null returned.
		 *
		 * @return mixed - if file exists, contents returned, else null.
		 */
		public function getContent(){
			$filename = $this->parent->getPath() .'/'. $this->getFilename();
			
			if( !is_file($filename) ) return null;
			
			$contents = file_get_contents($filename);
			
			if( $contents === false) return null;
				
			return (string) $contents;
		}
		
		/**
		 * Set content from $content parameter.
		 *
		 * @param string $content - new content
		 *
		 * @return boolean - true on success, false otherwise
		 */
		public function setContent($content){
			return (boolean) General::writeFile($this->parent->getPath() .'/'. $this->getFilename(), $content);
		}
		
		/**
		 * Get file name. If $handle given, the filename is built with it.
		 *
		 * @param string $handle (optional)
		 *
		 * @return string
		 */
		public function getFilename($handle = null){
			$handle = (is_string($handle) && !empty($handle)) ? $handle : $this->parent->getHandle();
			
			return (string) $handle .'.'. $this->type .'.'. $this->extension;
		}
		
		/**
		 * Set file name by creating a new file.
		 * Old file must be deleted at a higher level to ensure consistency.
		 *
		 * @param string $handle - new handle
		 *
		 * @return boolean - true on success, false otherwise
		 */
		public function setFilename($handle){
			return (boolean) General::writeFile($this->parent->getPath() .'/'. $this->getFilename($handle), $this->getContent());
		}
	}
	