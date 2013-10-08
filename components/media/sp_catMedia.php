<?php
if (!class_exists("sp_catMedia")) {
	
	/**
	 * Extends sp_catComponent
	 *	Media category component. Defines administrative features for
	 * the link component. Also used alongside sp_postMedia for front-end
	 * handling.
	 *
	 * @see sp_catComponent
	 */

	class sp_catMedia extends sp_catComponent{
		
		private $defaultExts = array("jpg", "jpeg", "png", "gif", "pdf", "doc");
		private $customExts  = array();
		private $isGallery   = false;
		private $allowedExts = array();
		
		function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "order", "options", "default", "required");

            if($compID == 0){
                //Set default Media options
                $options->allowedExts = array("jpg" => 1, "jpeg" => 1, "png" => 1, "gif" => 1, "pdf" => 1, "doc" => 1);
                $options->isGallery = false;
                $this->options = $options;
            }

            $this->initComponent($compInfo);

            //Get updated media options after initializing the component
            $this->isGallery   = $this->options->isGallery;
            $this->allowedExts = $this->options->allowedExts;
            $this->customExts  = $this->options->customExts;
		}
		
        /**
         * @see parent::installComponent()
         */
		function install(){
			self::installComponent('Media', 'Upload pictures and other forms of media.', __FILE__);			
		}

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

        /**
		 * Adds CSS / JS to stylize and handle any UI actions
		 */		
		static function init(){
			require_once('ajax/sp_catMediaAJAX.php');
			sp_catMediaAJAX::init();
			wp_register_script( 'sp_catMediaJS', plugins_url('js/sp_catMedia.js', __FILE__), array('sp_admin_globals') );
			wp_enqueue_script( 'sp_catMediaJS' );
		}

		/**
		 * @see parent::componentOptions()
		 */		
		function componentOptions(){
			$allowedExts = $this->allowedExts;
			$customExts  = $this->customExts;

            $html = '<form id="spMedia-' . $this->ID . '-form">';
			$html .= '<p> Allowed extensions: </p>';
			foreach($this->defaultExts as $index => $ext){
				$checked = $allowedExts[$ext] ? 'checked="checked"' : '';
				$html .= '<span class="sp_extension_checkbox"><input type="checkbox" id="exts[' . $ext . ']" name="exts[' . $ext . ']" value="1" ' . $checked . ' /> <label for="exts[' . $ext . ']">' . $ext . '</label></span>';
			}
			
			if( !empty($customExts) ){
				$customExts = array_keys($customExts);
				$customExts = implode(",", $customExts);
			}else{
				$customExts = "";
			}
			
			$html .= '<p>'; 
				$html .= 'Add more extensions separated by commas: "zip, doc, mov"): ';
				$html .= '<input type="text" class="regular-text" id="customExts" name="customExts" value="' . $customExts . '" />';
			$html .= '</p>';
			
			$checked = $this->isGallery ? 'checked="checked"' : '';
			$html .= '<p><input type="checkbox" id="galleryMode" name="galleryMode" ' . $checked . ' value="1" /> Gallery - This will enable multiple file uploads and display them in grid-like gallery. </p>';
			$html .= '<button type="button" class="update_sp_media button-secondary" data-compid="' . $this->ID . '"> Update Media </button>';
            $html .= '</form>';
			echo $html;
		}

        /**
         * @see parent::renderSettings()
         */
        function renderSettings(){}

		/**
		 * Returns the allowed media extensions
		 */ 
		function getOptions(){
			return $this->options;
		}

        /**
         * Sets the allowed extensions for the media component
         * @param $exts n array of allowed exts: array("jpg" => 1, "jpeg" => 1, ... )
         */
        function setExtensions($exts){
			$this->options->allowedExts = $exts;
		}
		
		/**
		 * Sets the option for the media component. The $data param should be an object
		 * with a 'isGallery' boolean and 'extensions' array. @see this class definition
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