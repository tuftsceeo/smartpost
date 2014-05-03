<?php
/*
Plugin Name: SmartPost
Plugin URI: https://sptemplates.org
Description: SmartPost is a dynamic templating and authoring tool that brings a lot of the features of the WordPress dashboard to the front end. SmartPost allows you to create category specific post templates that are then used by users on the front end to generates posts and content. Templates are broken down by post components such as pictures galleries, videos, and content blocks.
Version: 2.2
Author: Rafi Yagudin
Author URI: http://www.rafilabs.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
require_once( ABSPATH . 'wp-includes/pluggable.php' );

define("SP_PLUGIN_NAME", "SmartPost");
define("SP_IMAGE_PATH", plugins_url('/images', __FILE__));
define("SP_PLUGIN_PATH", plugins_url('/', __FILE__));
define("SP_DEBUG", true); // Turns on useful errors that are dumped into the php error log for debugging
define("SP_VERSION", "2.2");

if ( !class_exists("smartpost") ){

    /**
     * Class smartpost
     * Initializes the plugin and includes all the necessary component
     * classes.
     */
    class smartpost{

        /**
         * Calls necessary initialization methods to initialize the plugin.
         */
        function __construct(){
            require_once( 'core/sp_core.php' );
            require_once( 'sp_install.php' );
            self::init_sp_classes( dirname(__FILE__) . "/updates" );

            $this->sp_init();

            require_once( 'components/component/sp_catComponent.php' );
            require_once( 'components/component/sp_postComponent.php' );
            sp_catComponent::init_cat_component();
            sp_postComponent::init_post_component();

            self::find_sp_classes( dirname(__FILE__) . "/components/" );

            require_once( 'sp_category.php' );
            require_once( 'sp_admin.php' );
            require_once( 'sp_post.php' );

            self::init_sp_classes( dirname(__FILE__) . "/widgets" );

            sp_post::init();
            sp_category::init();
            if( is_admin() ){
                sp_admin::init();
            }

            require_once( 'sp_uninstall.php' );
        }

        /**
         * Hook for WP init action
         */
        private static function sp_init(){
            get_currentuserinfo();
            self::enqueue_sp_css();
            self::enqueue_sp_js();
        }

        /**
         * Given $dir, recursively iterates over all directories and
         * calls init_sp_classes() on each directory. Used for extending SmartPost with
         * future SmartPost components.
         *
         * @param string $dir The directory path
         */
        static function find_sp_classes($dir){
            if ( is_dir($dir) ) {
                if ( $dh = opendir($dir) ) {
                    while ( ($file = readdir($dh)) !== false ) {
                        if( is_dir($dir . $file) && ($file != "." && $file != "..") ){
                            self::init_sp_classes( $dir . $file );
                        }
                    }
                    closedir($dh);
                }
            }
        }

        /**
         * Searches for php files with a an "sp_" prefix in a given directory
         * and calls requires_once() and attempts to initialize
         * a class based off the file's base name. If the class registers
         * then it searches for an init() method and calls it.
         *
         * @param string $folder The path/directory
         */
        static function init_sp_classes($folder){
            foreach ( glob( $folder . "/sp_*.php" ) as $filename ){
                $class = basename($filename, ".php");

                if( !class_exists($class) && file_exists( $filename )){
                    require_once( $filename );
                }

                //Initialize the class if possible
                $ignoreClasses = array('sp_postComponent', 'sp_catComponent');
                if( class_exists( $class ) && ( !in_array( $class, $ignoreClasses ) ) ){
                    if( method_exists( $class, 'init' ) ){
                        call_user_func( array($class, 'init') );
                    }
                }
            }
        }

        /**
         * Places globally used SmartPost CSS on the page.
         */
        static function enqueue_sp_css(){
            wp_register_style( 'jquery-ui-theme', plugins_url('/css/jquery-ui-theme/jquery-ui-1.10.3.custom.css', __FILE__));
            wp_register_style( 'jquery-dynatree-css', plugins_url('js/dynatree/skin/ui.dynatree.css', __FILE__) );
            wp_register_style( 'tooltipster-css', plugins_url('js/tooltipster/css/tooltipster.css', __FILE__) );
            wp_register_style( 'smartpost-css', plugins_url('css/smartpost.css', __FILE__) );

            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'jquery-dynatree-css' );
            wp_enqueue_style( 'jquery-ui-theme' );
            wp_enqueue_style( 'tooltipster-css' );
            wp_enqueue_style( 'smartpost-css' );
        }

        /**
         * Places globally used JS on the page.
         * @todo Use wp_script_is() to avoid conflicts
         */
        static function enqueue_sp_js(){
            //Register scripts
            wp_register_script( 'sp_globals'     , plugins_url('js/sp_globals.js', __FILE__), array( 'jquery' ) );
            wp_register_script( 'strip_tags'     , plugins_url('js/strip_tags.js', __FILE__), array( 'jquery' ) );
            wp_register_script( 'jquery-editable', plugins_url('js/jquery.jeditable.mini.js', __FILE__), array( 'jquery' ) );
            wp_register_script( 'ckeditor'       , plugins_url('js/ckeditor/ckeditor.js', __FILE__), array( 'jquery' ) );
            wp_register_script( 'tooltipster'    , plugins_url('js/tooltipster/jquery.tooltipster.min.js', __FILE__), array( 'jquery' ) );
            wp_register_script( 'jquery-dynatree', plugins_url('js/dynatree/jquery.dynatree.min.js', __FILE__), array( 'jquery-ui-core', 'jquery-ui-widget' ) );
            wp_register_script( 'jquery-dynatree-cookie', plugins_url('js/dynatree/jquery.cookie.js', __FILE__), array( 'jquery-dynatree' ) );
            wp_register_script( 'jquery-ui-touch-punch', plugins_url('js/jquery.ui.touch-punch.min.js', __FILE__), array( 'jquery-ui-core' ) );

            //Enqueue default WP scripts
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'jquery-form' );
            wp_enqueue_script( 'jquery-ui-core' );
            wp_enqueue_script( 'jquery-ui-widget' );
            wp_enqueue_script( 'jquery-ui-mouse' );
            wp_enqueue_script( 'jquery-ui-draggable' );
            wp_enqueue_script( 'jquery-ui-droppable' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-dialog' );
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-ui-widget' );
            wp_enqueue_script( 'jquery-ui-position' );
            wp_enqueue_script( 'jquery-ui-autocomplete' );
            wp_enqueue_script( 'jquery-ui-touch-punch' );
            wp_enqueue_script( 'jquery-editable' );
            wp_enqueue_script( 'plupload' );
            wp_enqueue_script( 'plupload-all' );

            //Enqueue SmartPost scripts
            wp_enqueue_script( 'sp_globals' );
            wp_enqueue_script( 'ckeditor' );
            wp_enqueue_script( 'strip_tags' );
            wp_enqueue_script( 'jquery-dynatree-cookie' );
            wp_enqueue_script( 'jquery-dynatree' );
            wp_enqueue_script( 'tooltipster' );

            $typesAndIDs = sp_core::getTypesAndIDs();
            wp_localize_script( 'sp_globals', 'sp_globals', array(
                'SP_TYPES'               => $typesAndIDs,
                'SP_ADMIN_URL'           => admin_url( 'admin.php' ),
                'SP_AJAX_URL'            => admin_url( 'admin-ajax.php' ),
                'SP_NONCE'	             => wp_create_nonce( 'sp_nonce' ),
                'SP_PLUGIN_PATH'         => SP_PLUGIN_PATH,
                'SP_IMAGE_PATH'          => SP_IMAGE_PATH,
                'MAX_UPLOAD_SIZE'        => WP_MEMORY_LIMIT,
                'UPLOAD_SWF_URL'         => includes_url( 'js/plupload/plupload.flash.swf' ),
                'UPLOAD_SILVERLIGHT_URL' => includes_url( 'js/plupload/plupload.silverlight.xap' )
                )
            );
        }

    } // End class smartpost
}

if(class_exists('smartpost')){
    $new_smartpost = new smartpost();
}

/**
 * Register actions and hooks.
 * Register SmartPost widgets.
 */
if(isset($new_smartpost)){

    //Register SmartPost Widgets
    add_action('widgets_init', create_function('', 'return register_widget("sp_postTreeWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_postWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_quickPostWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_myPostsWidget");'));

    register_activation_hook( __FILE__, array('sp_install','smartpost_install') );
}
