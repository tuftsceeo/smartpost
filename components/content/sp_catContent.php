<?php
if (!class_exists("sp_catContent")) {

    /**
     * Extends sp_catComponent
     * Content category component. Defines administrative features
     * for the content component. Also used alongside sp_postComponent
     * for front-end handling.
     *
     * @see sp_catComponent
     */
    class sp_catContent extends sp_catComponent{

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
            self::installComponent("Content", "Rich and plain text editor. Uses the <a href='http://nicedit.com/'>NicEdit</a> as its editor.", __FILE__);
        }

        /**
         * @see parent::uninstall()
         */
        function uninstall(){}

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
            wp_register_script( 'sp_catContentJS', plugins_url('/js/sp_catContent.js', __FILE__));
            //wp_enqueue_script( 'sp_catContentJS', array('jquery', 'sp_admin_globals', 'sp_admin_js', 'sp_catComponentJS') );
        }

        /**
         * Add content component CSS
         */
        static function enqueueCSS(){
            wp_register_style( 'sp_catContentCSS', plugins_url('/css/sp_catContent.css', __FILE__));
            wp_enqueue_style( 'sp_catContentCSS' );
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