<?php
/**
 * Handles all updates
 * User: ryagudin
 * Date: 5/7/14
 * Time: 11:27 PM
 */
if ( !class_exists("sp_updates") ){
    class sp_updates {

        /**
         * Checks for any updates to run.
         */
        public static function check_for_updates(){
            $site_sp_version = get_site_option('sp_plugin_version');
            if( $site_sp_version != SP_VERSION ){
                do_action('sp_updates', $site_sp_version); // Calls all upgrade functions hooked into this action
                update_site_option('sp_plugin_version', SP_VERSION);
            }
        }

        /**
         * Runs component upgrades. Each component should have its own hardcoded version
         * and a stored db version to compare against. If they are different, then the upgrade_component()
         * function will get called.
         */
        protected static function run_component_updates( $site_sp_version ){
            $types = sp_core::get_component_types();

            // Calls update_component for each category component
            foreach( $types as $type ){
                $class_name = 'sp_cat' . $type;
                if( class_exists( $class_name ) ){
                    if( method_exists( $class_name, 'update_component') ){
                        call_user_func( array($class_name, 'update_component'), $site_sp_version );
                    }
                }
            }
        }
    }
}