<?php 
if (!class_exists("sp_postContent")) {
   /**
    * Extends sp_postComponent
    *
    * @see sp_postComponent
    */
    class sp_postContent extends sp_postComponent{

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                             $name = '', $value = '', $postID = 0){
            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

            //Set the default content options
            $contentOptions = sp_catComponent::getOptionsFromID($catCompID);
            $this->options = $contentOptions;

            $this->initComponent($compInfo);
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode($value = ""){

            // Create an editor area for a video description
            return sp_core::sp_editor(
                $this->value,
                $this->ID,
                false,
                'Type something here ...',
                array('data-action' => 'saveContentAJAX', 'data-compid' => $this->ID, 'data-postid' => $this->postID )
            );
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = '<div id="sp_content" style="margin: 20px">';
                $html .= $this->value;
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::render()
         * @return string
         */
        function renderPreview(){
            return self::renderViewMode();
        }

        /**
         * Initializes the class with AJAX functions, JS and CSS.
         * @return mixed|void
         */
        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postContentAJAX.php');
            sp_postContentAJAX::init();
            self::enqueueJS();
        }

        /**
         * Enqueues the JS to the page.
         */
        static function enqueueJS(){
            wp_register_script( 'sp_postContent', plugins_url('/js/sp_postContent.js', __FILE__));
            wp_enqueue_script( 'sp_postContent', null, array( 'jquery', 'sp_globals', 'sp_postComponentJS', 'sp_postJS' ) );
        }

        /**
         * @see parent::update();
         */
        function update($content = ''){
            $this->value = (string) $content;
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $content, '%s');
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            $strippedContent = trim(strip_tags($this->value));
            return empty($strippedContent);
        }
    }
}
