<?php
if (!class_exists("sp_catLink")) {
	
	/**
	 * Extends sp_catComponent
	 * 
	 * Link category component. Defines administrative features for
	 * the link component. Also used alongside sp_postLink for front-end
	 * handling.
	 *
	 * @see sp_catComponent
	 */

	class sp_catLink extends sp_catComponent{

		function __construct($compID = 0, $catID = 0, $name = '', 
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

                $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                    "order", "options", "default", "required");
 				$this->initComponent($compInfo);
		}

        /**
         * @see parent::install()
         * @return mixed|void
         */
        function install(){
			self::installComponent('Link', 'Link to photos and other media.', __FILE__);			
		}

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

        /**
         * @see parent::init()
         * @return mixed|void
         */
        static function init(){}

        /**
         * @see parent::componentOptions()
         * @return mixed|void
         */
        function componentOptions(){ echo '<p>No options exist for this component</p>';	}

        /**
         * @see parent::getOptions()
         * @return mixed|null
         */
        function getOptions(){ return null; }

        /**
         * @see parent::setOptions
         * @param null $data
         * @return mixed|null
         */
        function setOptions($data = null){ return null; }

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