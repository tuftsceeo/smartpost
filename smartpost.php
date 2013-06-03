<?php
/*
Plugin Name: SmartPost
Plugin URI: http://ceeo.tufts.edu/
Description: SmartPost as a dynamic templating and authoring tool that brings a lot of the features
             of the WordPress dashboard to the front end. SmartPost allows you to create category
             specific post templates that are then used by users on the front end to generates posts
             and content. Templates are broken down by post components such as pictures galleries,
             videos, and content blocks.
Version: 2.0
Author: Tufts CEEO
Author URI: http://www.rafilabs.com/smartpost
*/

define("PLUGIN_NAME", "SmartPost");
define("IMAGE_PATH", plugins_url('/images', __FILE__));
define("PLUGIN_PATH", plugins_url('/', __FILE__));

require_once( ABSPATH . 'wp-includes/pluggable.php' );
require_once( 'core/sp_core.php' );
require_once( 'sp_install.php' );
register_activation_hook(__FILE__, array('sp_install','smartpost_install') );
require_once( 'components/component/sp_catComponent.php' );
require_once( 'components/component/sp_postComponent.php' );
require_once( 'sp_category.php' );
require_once( 'sp_admin.php' );
require_once( 'sp_post.php' );

if (!class_exists("smartpost")){

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
            self::sp_init();
            sp_postComponent::initPostComponent();
            sp_catComponent::initCatComponent();
            self::requireClasses(dirname(__FILE__) . '/components');
        }

        /**
         * Hook for WP init action
         */
        private static function sp_init(){
            get_currentuserinfo();
            self::enqueueCSS();
            self::enqueueJS();
        }

        /**
         * Given $dir, recursively iterates over all directories and
         * and require_once()'s them.
         * @param string $dir The directory path
         */
        static function requireClasses($dir){
            if (is_dir($dir)) {
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        $folder = $dir . '/' . $file;
                        //filters ".", "..", and ".svn" files
                        if(is_dir($folder) && ($file != "." && $file != "..")){
                            foreach (glob($folder . "/*.php") as $filepath){
                                $class = basename($filepath, ".php");
                                if(!class_exists($class)){
                                    require_once($filepath);
                                }
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        }

        /**
         * Places globally used SmartPost CSS on the page.
         */
        static function enqueueCSS(){
            wp_register_style( 'jquery-ui-theme', plugins_url('/css/jquery-ui-theme/jquery-ui-1.8.21.custom.css', __FILE__));
            wp_register_style( 'simple-menu-css', plugins_url('/css/simple_menu.css', __FILE__) );
            wp_register_style( 'jquery-dynatree-css', plugins_url('js/dynatree/skin-vista/ui.dynatree.css', __FILE__) );
            wp_enqueue_style( 'simple-menu-css' );
            wp_enqueue_style( 'jquery-dynatree-css' );
            wp_enqueue_style( 'jquery-ui-theme' );
        }

        /**
         * Places globally used JS on the page.
         */
        static function enqueueJS(){
            //Register scripts
            wp_register_script( 'sp_globals'     , plugins_url('/js/sp_globals.js'            , __FILE__), array( 'jquery' ) );
            wp_register_script( 'simple-menu'    , plugins_url('/js/simple_menu.js'           , __FILE__), array( 'jquery' ) );
            wp_register_script( 'strip_tags'     , plugins_url('/js/strip_tags.js'            , __FILE__), array( 'jquery' ) );
            wp_register_script( 'jquery-editable', plugins_url('/js/jquery.jeditable.mini.js' , __FILE__), array( 'jquery' ) );
            wp_register_script( 'nicEditor'      , plugins_url('/js/nicEdit/nicEdit.js'       , __FILE__) );
            wp_register_script( 'jquery-dynatree', plugins_url('js/dynatree/jquery.dynatree.min.js', __FILE__), array( 'jquery-ui-core', 'jquery-ui-widget' ) );
            wp_register_script( 'jquery-dynatree-cookie', plugins_url('js/dynatree/jquery.cookie.js', __FILE__) );


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
            wp_enqueue_script( 'jquery-editable' );

            //Enqueue SmartPost scripts
            wp_enqueue_script( 'sp_globals' );
            //wp_enqueue_script( 'simple-menu' );
            wp_enqueue_script( 'strip_tags' );
            wp_enqueue_script( 'jquery-dynatree-cookie' );
            wp_enqueue_script( 'jquery-dynatree' );

            $typesAndIDs = sp_core::getTypesAndIDs();
            wp_localize_script( 'sp_globals', 'sp_globals', array(
                    'ajaxurl' 	 => admin_url( 'admin-ajax.php' ),
                    'spNonce'	 => wp_create_nonce( 'sp_nonce'),
                    'types'	     => $typesAndIDs,
                    'PLUGIN_PATH'=> PLUGIN_PATH,
                    'IMAGE_PATH' => IMAGE_PATH )
            );
        }

    }//end class smartpost
}

if(class_exists('smartpost')){
    $new_smartpost = new smartpost();
    is_admin() ? sp_admin::init() : sp_post::init();
}

/**
 * Register actions and hooks.
 * Register SmartPost widgets.
 */
if(isset($new_smartpost)){

    //Register SmartPost Widgets
    /*
    add_action('widgets_init', create_function('', 'return register_widget("sp_postTreeWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_postWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_quickPostWidget");'));
    add_action('widgets_init', create_function('', 'return register_widget("sp_myPostsWidget");'));
    */
}
