<?php
if (!class_exists("sp_catAttachments")) {
	
	/**
	 * Extends sp_catComponent
	 *	Media category component. Defines administrative features for
	 * the link component. Also used alongside sp_postMedia for front-end
	 * handling.
	 *
	 * @see sp_catComponent
	 */

	class sp_catAttachments extends sp_catComponent{
		
		public $allowedExts = array();
		
		function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "order", "options", "default", "required");

            if($compID == 0){
                //Set default attachment options if any exist
                $this->options = $options;
            }

            $this->initComponent($compInfo);

            //Get updated media options after initializing the component
            $this->allowedExts = $this->options;
		}
		
        /**
         * @see parent::installComponent()
         */
		function install(){
			self::installComponent('Attachments', 'Attach files to your posts.', __FILE__);
		}

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

        /**
		 * Adds CSS / JS to stylize and handle any UI actions
		 */		
		static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_catAttachmentsAJAX.php');
			sp_catAttachmentsAJAX::init();
			wp_register_script( 'sp_catAttachmentsJS', plugins_url('js/sp_catAttachments.js', __FILE__), array( 'jquery', 'sp_globals', 'sp_catComponentJS' ) );
			wp_enqueue_script( 'sp_catAttachmentsJS' );
		}

		/**
		 * @see parent::componentOptions()
		 */		
		function componentOptions(){
            if( !empty($allowedExts) ){
                $allowedExts = implode(', ', $this->allowedExts);
            }else{
                $allowedExts = '';
            }
            $html = '<form id="sp-attachments-' . $this->ID . '-form">';
			$html .= '<p> Allowed extensions: </p>';
			$html .= '<p>';
				$html .= 'Add extensions separated by commas: "zip, doc, mov"). If left blank, any file extension is permitted (subject to server and and WordPress settings).';
				$html .= '<input type="text" class="regular-text" id="allowedExts" name="allowedExts" value="' . $allowedExts . '" />';
			$html .= '</p>';
			$html .= '<button type="button" class="update-sp-cat-attachments button-secondary" data-compid="' . $this->ID . '"> Save </button>';
            $html .= '</form>';
			echo $html;
		}

        /**
		 * Returns the allowed media extensions
		 */
		function getOptions(){
			return $this->options;
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