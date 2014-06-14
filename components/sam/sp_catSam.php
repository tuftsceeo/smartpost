<?php
if (!class_exists("sp_catSam")) {

    /**
     * Extends sp_catComponent
     * SAM category component. Defines administrative features
     * for the SAM component. Also used alongside sp_postComponent
     * for front-end handling.
     *
     * @see sp_catComponent
     */
    define( 'SP_DEFAULT_PLAYER_WIDTH', 560) ; // Default player width
    define( 'SP_DEFAULT_PLAYER_HEIGHT', 320 ); // Default player height
     
    class sp_catSam extends sp_catComponent{

        function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                "options",	"order", "default", "required");

            $this->initComponent($compInfo);
        }

        /**
         * @see parent::installComponent()
         */
        function install(){
            self::installComponent("SAM", "Stop Action Movies.", __FILE__);
            
            $sp_player_width = get_site_option( 'sp_player_width' );
            if( empty( $sp_player_width ) ){
                update_site_option( 'sp_player_width', SP_DEFAULT_PLAYER_WIDTH );
            }

            $sp_player_height = get_site_option( 'sp_player_height' );
            if( empty( $sp_player_height ) ){
                update_site_option( 'sp_player_height', SP_DEFAULT_PLAYER_HEIGHT );
            }
        
        }

        /**
         * @see parent::uninstall()
         */
        function uninstall(){
            delete_site_option( 'sp_player_width' );
            delete_site_option( 'sp_player_height' );
        }

        /**
         * Adds CSS / JS to stylize and handle any UI actions
         */
        static function init(){
            self::enqueueCSS();
            self::enqueueJS();
        }

        /**
         * Add content component JS
         */

        static function enqueueJS(){
            //wp_register_script( 'sp_catSamJS', plugins_url('/js/sp_catSam.js', __FILE__));
            //wp_enqueue_script( 'sp_catSamJS' );
        }

        /**
         * Add content component CSS
         */
        static function enqueueCSS(){
            //wp_register_style( 'sp_catSamCSS', plugins_url('/css/sp_catSam.css', __FILE__));
            //wp_enqueue_style( 'sp_catSamCSS' );
        }

        /**
         * @see parent::componentOptions()
         */
        function componentOptions(){
            $options = $this->options;
            ?>
            <p>No options exist for this component.</p>
        <?php
        }

        /**
         * @see parent::getOptions()
         */
        function getOptions(){
            return null;
        }

          /**
         * @see parent::setOptions()
         */
        function setOptions($data = null){
            return $data;
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