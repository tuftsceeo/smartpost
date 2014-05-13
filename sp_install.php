<?php
global $sp_db_version;
$sp_db_version = "2.0";

if (!class_exists("sp_install")) {
	
	class sp_install{
		
		function smartpost_install(){
			global $sp_db_version;
			$db_version = get_option('sp_db_version');	

			if($db_version != $sp_db_version){
				self::create_sp_compTypes();
				update_option('sp_db_version', $sp_db_version);				
			}

			add_action('admin_notices', array($this, 'findComponents'));
			self::findComponents(dirname(__FILE__) . "/components/");
			self::addDefaultSPCategory();
		}

        /*
         * On a new install, we add the 'SP QuickPost' category
         * as a new default category that users can use immediately.
         */
		static function addDefaultSPCategory(){
			$category = get_term_by('name', 'SP QuickPost', 'category', OBJECT);
			if( empty($category) ){
				$defaultSPCat = new sp_category('SP QuickPost', 'Default category for QuickPosts');
				
				$types = sp_core::get_component_types();
				foreach($types as $type){
					if(class_exists('sp_cat' . $type->name)){
						$defaultSPCat->addCatComponent($type->name, null, $type->id, false, false);
					}
				}
                update_option('sp_defaultCat', $defaultSPCat->getID());
			}
		}
		
		/* Table: sp_compTypes
		 *	Description: contains the 'base-package' types for SmartPost.
		 *	Is extendable to future types.
		 */		
		function tableExists($tableName){
			global $wpdb;
			$sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$tableName'";
			$row = $wpdb->get_row($sql);
			return is_null($row);
		}
		
		function create_sp_compTypes(){
			global $wpdb;
			$table_name = $wpdb->prefix . "sp_compTypes";
			$sql = "CREATE TABLE $table_name (
					  id mediumint(9) NOT NULL AUTO_INCREMENT,
					  name tinytext NOT NULL,
					  description tinytext NULL,
					  icon tinytext NULL,
					  UNIQUE KEY id (id)
      					);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			self::create_sp_catComponents();
		}
		
		/* Dynamically install all Component classes
		 * Precondition: call create_sp_compTypes
		 */
		function findComponents($dir){
			if (is_dir($dir)) {	
                if ($dh = opendir($dir)) {
                    while (($file = readdir($dh)) !== false) {
                        if(is_dir($dir . $file) && ($file != "." && $file != ".." && $file != ".git" && $file != ".idea")){
                            self::installComponents($dir . $file);
                        }
                    }
                    closedir($dh);
                }
		    }
		}
		
		function installComponents($folder){
			foreach (glob($folder . "/sp_*.php") as $filename){
                require_once( $filename );
                $component = basename($filename, ".php");

                // ignore sp_catComponent as it's an abstract class
                if( method_exists($component, 'install') && $component != 'sp_catComponent' ){
                    call_user_func( array($component, 'install') );
                }
			}
		}

		/* sp_catComponents relationships
		 * ------------------------------------
		 * sp_catComponents.id = wp_terms.id
		 * sp_catComponents.type == sp_compTypes.id
		 * 
		 */
		function create_sp_catComponents(){
			global $wpdb;
			$table_name = $wpdb->prefix . "sp_catComponents";
			$sql = "CREATE TABLE $table_name (
					  id mediumint(9) NOT NULL AUTO_INCREMENT,
					  catID smallint NOT NULL,
					  name tinytext NOT NULL,
					  description tinytext NULL,
					  typeID tinyint NOT NULL,
					  compOrder smallint NOT NULL,
					  options longtext NULL,
					  is_default tinyint(1) NOT NULL,
					  is_required tinyint(1) NOT NULL,
					  iconID smallint NULL,
					  UNIQUE KEY id (id)
					    );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			self::create_sp_postComponents();
		}

		/* sp_postComponents relationships
		 * ------------------------------------
		 * sp_postComponents.compCatID == sp_catComponents.id
		 * sp_postComponents.type     == sp_catComponents.type
		 */
		function create_sp_postComponents(){
			global $wpdb;
			$table_name = $wpdb->prefix . "sp_postComponents";	
			$sql = "CREATE TABLE $table_name (
					  id mediumint(9) NOT NULL AUTO_INCREMENT,
					  catCompID smallint NOT NULL,
					  compOrder smallint NOT NULL,
					  name 	tinytext NULL,
					  value longtext NULL,
					  postID smallint NOT NULL,
					  options longtext NULL,
					  typeID tinyint NOT NULL,
					  UNIQUE KEY id (id)
					    );";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);		
		}
	
	}
}
?>