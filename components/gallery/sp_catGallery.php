<?php
if (!class_exists("sp_catGallery")) {
	
	/**
	 * Extends sp_catComponent
	 * The photo gallery component allows users to upload pictures into
     * a gallery. Gallery uses fancybox plugin to browse through the pictures.
	 *
	 * @see sp_catComponent
	 */
	class sp_catGallery extends sp_catComponent{

		private $useFancyBox = true; //Use fancybox by default
		
		function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "order", "options", "default", "required");

            if($compID == 0){
                //Set default Gallery options
                $options->useFancyBox = true;
                $this->options = $options;
            }

            $this->initComponent($compInfo);

            //Get updated gallery options after initializing the component
            $this->useFancyBox = $this->options->useFancyBox;
		}
		
        /**
         * @see parent::installComponent()
         */
		function install(){
			self::installComponent('Gallery', 'Upload pictures (or a web-cam snapshot) to a picture gallery.', __FILE__);
		}

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

		/**
		 * Adds CSS / JS to stylize and handle any UI actions
		 */		
		static function init(){
			require_once('ajax/sp_catGalleryAJAX.php');
			sp_catGalleryAJAX::init();
            /*
			wp_register_script( 'sp_catGalleryJS', plugins_url('js/sp_catGallery.js', __FILE__), array('sp_admin_globals') );
			wp_enqueue_script( 'sp_catGalleryJS' );
            */
		}

        /**
         * @see parent::renderSettings()
         */
        function getComponentSettings(){}

		/**
		 * @see parent::componentOptions()
		 */		
		function componentOptions(){
			$fancyBox = $this->useFancyBox;
            $checked = $fancyBox ? 'checked' : '';
            $html = '';
            $html .= '<input class="sp_gallery_fbox" id="fancybox-' . $this->ID . '" type="checkbox" ' . $checked . ' />';
            $html .= '<label for="fancybox-' . $this->ID . '" > Click to use FancyBox with the gallery. </label>';
			echo $html;
		}

		/**
		 * Gallery options are whether to use fancybox or not to view pictures.
		 */ 
		function getOptions(){
			return $this->options;
		}
		
		/**
		 * Sets the option for the Gallery component. The $data param should be a bool
		 * stating whether FancyBox should be used to view photos.
		 * 
		 * @param object $data
		 * @return bool True on success, null or false on failure 
		 */
		function setOptions($data = null){
			$options = maybe_serialize($data);
			return sp_core::updateVar('sp_catComponents', $this->ID, 'options', $options, '%s');		
		}
		
	}
}
?>