<?php
if (!class_exists("sp_catPhoto")) {
	
	/**
	 * Extends sp_catComponent
	 * The photo photo component allows users to upload pictures into
     * a photo. Photo uses thickbox to browse through the pictures.
	 *
	 * @see sp_catComponent
	 */
	class sp_catPhoto extends sp_catComponent{

		function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "order", "options", "default", "required");

            if($compID == 0){
                $this->options = $options;
            }

            $this->initComponent($compInfo);
		}
		
        /**
         * @see parent::installComponent()
         */
		function install(){
			self::installComponent('Photo', 'Upload a single photo.', __FILE__);
		}

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

		/**
		 * Adds CSS / JS to stylize and handle any UI actions
		 */		
		static function init(){}

		/**
		 * @see parent::componentOptions()
		 */		
		function componentOptions(){
        ?>
        <p>No options exist for this component</p>
        <?php
		}

		/**
		 * @see parent::getOptions()
		 */ 
		function getOptions(){ return $this->options; }
		
		/**
		 * Sets the option for the Photo component. The $data param should be a bool
		 * stating whether FancyBox should be used to view photos.
		 * 
		 * @param object $data
		 * @return bool True on success, null or false on failure 
		 */
		function setOptions($data = null){ return false; }

        /**
         * Renders the global options for this component, otherwise returns false.
         * @return bool|string
         */
        public static function globalOptions(){
            return false;
        }
	}
}
?>