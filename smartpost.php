<?php
/*
Plugin Name: SmartPost
Plugin URI: http://sptemplates.org
Description: SmartPost is a templating and authoring tool that brings a lot of the features of the WordPress dashboard to the front end. SmartPost allows you to create category specific post templates that are then used by users on the front end to generates posts and content. Templates are broken down by post components such as pictures galleries, videos, and content blocks.
Version: 2.3.6
Author: RafiLabs
Author URI: http://www.rafilabs.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
require_once( ABSPATH . 'wp-includes/pluggable.php' );

define("SP_PLUGIN_NAME", "SmartPost");
define("SP_IMAGE_PATH", plugins_url('/images', __FILE__));
define("SP_PLUGIN_PATH", plugins_url('/', __FILE__));
define("SP_DEBUG", false); // Turns on useful errors that are dumped into the php error log for debugging
define("SP_VERSION", "2.3.6");

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

            // Install/update the plugin on activation
            require_once( dirname( __FILE__ ) . '/sp_install.php' );
            require_once( dirname( __FILE__ ) . '/sp_updates.php' );
            require_once( dirname( __FILE__ ) . '/core/sp_core.php' );

            register_activation_hook( __FILE__, array( 'sp_install','smartpost_install' ) );

            // Initialize the plugin
            $this->sp_init();

            require_once( dirname( __FILE__ ) . '/components/component/sp_catComponent.php' );
            require_once( dirname( __FILE__ ) . '/components/component/sp_postComponent.php' );
            sp_catComponent::init_cat_component();
            sp_postComponent::init_post_component();

            self::find_sp_classes( dirname(__FILE__) . "/components/" );

            require_once( dirname( __FILE__ ) . '/sp_category.php' );
            require_once( dirname( __FILE__ ) . '/sp_admin.php' );
            require_once( dirname( __FILE__ ) . '/sp_post.php' );

            self::init_sp_classes( dirname(__FILE__) . "/widgets" );

            sp_post::init();
            sp_category::init();
            if( is_admin() ){
                sp_admin::init();
            }

            sp_updates::check_for_updates();
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
            wp_register_style( 'jquery-dynatree-css', plugins_url('js/dynatree/skin/ui.dynatree.css', __FILE__) );
            wp_register_style( 'tooltipster-css', plugins_url('js/tooltipster/css/tooltipster.css', __FILE__) );
            wp_register_style( 'smartpost-css', plugins_url('css/smartpost.css', __FILE__) );

            wp_enqueue_style( 'dashicons' );
            wp_enqueue_style( 'jquery-dynatree-css' );
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
            wp_enqueue_script( 'thickbox' );

            //Enqueue SmartPost scripts
            wp_enqueue_script( 'sp_globals' );
            wp_enqueue_script( 'ckeditor' );
            wp_enqueue_script( 'strip_tags' );
            wp_enqueue_script( 'jquery-dynatree-cookie' );
            wp_enqueue_script( 'jquery-dynatree' );
            wp_enqueue_script( 'tooltipster' );

            // Check is required for first-time activation b/c sp_compTypes table hasn't been made yet!
            if( get_option( 'sp_db_version' ) ){
                $types_and_ids = sp_core::get_types_and_ids();
                wp_localize_script( 'sp_globals', 'sp_globals', array(
                    'SP_TYPES'               => $types_and_ids,
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
        }
    } // End class smartpost
}


if( class_exists('smartpost') ){
    $new_smartpost = new smartpost();
}

/**
 * Register SmartPost widgets.
 */
if( isset($new_smartpost) ){
    //Register SmartPost Widgets
    add_action('widgets_init', create_function('', 'return register_widget("sp_postTreeWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_postWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_quickPostWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_myPostsWidget");'));
}
