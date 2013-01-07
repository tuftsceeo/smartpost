<?php
/*
Plugin Name: SmartPost
Plugin URI: http://www.rafisoft.com/smartpost.php
Description: 
Version: 2.0
Author: Tufts CEEO
Author URI: http://www.rafisoft.com 
*/

require_once(ABSPATH . 'wp-includes/pluggable.php');
define("PLUGIN_NAME", "SmartPost");
define("IMAGE_PATH", plugins_url('/images', __FILE__));
define("PLUGIN_PATH", plugins_url('/', __FILE__));

if (!class_exists("smartpost")){
	
	class smartpost{
		
		function __construct(){
		 require_once( 'core/sp_core.php' );
		 require_once( 'sp_install.php' );

			$this->sp_init();
			
			require_once('components/component/sp_catComponent.php');
			require_once('components/component/sp_postComponent.php');
			sp_catComponent::initCatComponent();
			sp_postComponent::initPostComponent();
			self::findClasses(dirname(__FILE__) . "/components/");
		 
		 require_once( 'sp_category.php' );
		 require_once( 'sp_admin.php' );
		 require_once( 'sp_post.php' );
			
			if(is_admin()){
				sp_admin::init();
			}
			
			sp_post::init();

			self::initClasses(dirname(__FILE__) . "/widgets");
		}
	
		/**
		 * Hook for WP init action
		 */
		function sp_init(){
			get_currentuserinfo();
			self::enqueueCSS();
			self::enqueueJS();
		}	
	
		/**
		 * Given $dir, recursively iterates over all directories and 
		 * calls initClasses() on each directory.
		 *
		 * @param string $dir The directory path
		 * @uses initClasses()
		 */
		static function findClasses($dir){
			if (is_dir($dir)) {	
    if ($dh = opendir($dir)) {
	     while (($file = readdir($dh)) !== false) {
	   				//filters ".", "..", and ".svn" files
								if(is_dir($dir . $file) && ($file != "." && $file != ".." && $file != ".svn")){
									self::initClasses($dir . $file);
								}
	     }
	     closedir($dh);
    }
			}
		}
		
		/**
		 * Searches for php files in a given directory
		 * and calls requires_once() and attempts to initialize
		 * a class based off the file's base name. If the class registers
		 * then it searches for an init() method and calls it.
		 *
		 * @param string $folder The path/directory
		 */
		static function initClasses($folder){
			foreach (glob($folder . "/*.php") as $filename){
					$class = basename($filename, ".php");
					if(!class_exists($class)){
						require_once($filename);
					}
					$ignoreClasses = array('sp_postComponent', 'sp_catComponent');
					if(class_exists($class) && (!in_array($class, $ignoreClasses)) ){
						if(method_exists($class, 'init')){
							call_user_func(array($class, 'init'));
						}
					}
			}
		}
		
		static function enqueueCSS(){
				wp_register_style( 'simple-menu-css', plugins_url('/css/simple_menu.css', __FILE__));
				wp_enqueue_style('simple-menu-css');
		}
		
		static function enqueueJS(){
			wp_register_script( 'sp_globals', plugins_url('/js/sp_globals.js', __FILE__), array( 'jquery' ));
			wp_register_script( 'simple-menu', plugins_url('/js/simple_menu.js', __FILE__), array( 'jquery' ));
			wp_register_script( 'strip_tags', plugins_url('/js/strip_tags.js', __FILE__), array( 'jquery' ));
			wp_register_script( 'jquery-editable', plugins_url('/js/jquery.jeditable.mini.js', __FILE__), array( 'jquery' ));		
			wp_register_script( 'nicEditor', plugins_url('/js/nicEdit/nicEdit.js', __FILE__));

			//Enqueue misc SP scripts
			wp_enqueue_script( 'sp_globals' );
			//wp_enqueue_script( 'simple-menu' );
			//wp_enqueue_script( 'strip_tags' );
			//wp_enqueue_script( 'jquery-editable' );

			/*Enqueue default WP scripts
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'jquery-ui-mouse' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-droppable' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-tabs' );*/

			$typesAndIDs = sp_core::getTypesAndIDs();
			wp_localize_script( 'sp_globals', 'sp_globals', array( 
					'ajaxurl' 			=> admin_url( 'admin-ajax.php' ),
					'spNonce'			 => wp_create_nonce( 'sp_nonce'),
					'types'						=> $typesAndIDs,
					'PLUGIN_PATH'=> PLUGIN_PATH,
					'IMAGE_PATH' => IMAGE_PATH )
			);
		}
		
	}
}

if(class_exists(smartpost)){
	$new_smartpost = new smartpost();
}
		
if(isset($new_smartpost)){
	
	register_activation_hook(__FILE__, array('sp_install','smartpost_install') );
	
	//Add the widgets
	add_action('widgets_init', create_function('', 'return register_widget("sp_postTreeWidget");'));
	add_action('widgets_init', create_function('', 'return register_widget("sp_postWidget");'));
	add_action('widgets_init', create_function('', 'return register_widget("sp_quickPostWidget");'));
	add_action('widgets_init', create_function('', 'return register_widget("sp_myPostsWidget");'));
		
}
?>