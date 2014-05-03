<?php
/**
 * Uninstalls SmartPost
 * User: ryagudin
 * Date: 4/8/14
 * Time: 10:33 AM
 */

if ( !class_exists("sp_uninstall") ) {

    class sp_uninstall{
        /**
         * @link http://stackoverflow.com/questions/9440423/wordpress-strip-single-shortcode-from-posts
         * @param $code
         * @param $content
         * @return string
         */
        public static function strip_shortcode($code, $content)
        {
            global $shortcode_tags;
            $stack = $shortcode_tags;
            $shortcode_tags = array($code => 1);
            $content = strip_shortcodes($content);
            $shortcode_tags = $stack;
            return $content;
        }

        /**
         * Saves component data to posts
         */
        public static function uninstall_sp_data(){
            error_log( 'uninstall_sp_data' );

            global $wpdb;
            // Save all SmartPost posts into HTML format
            $sp_cat_ids = get_option( 'sp_categories' );
            foreach($sp_cat_ids as $cat_id){
                $sp_posts = get_posts( array( 'category' => $cat_id, 'numberposts' => -1 ) );
                if( !empty( $sp_posts ) ){
                    foreach( $sp_posts as $post ){
                        if(sp_post::is_sp_post( $post->ID) ){
                            $sp_post_comps = sp_post::get_components_from_ID( $post->ID );
                            $post_comp_html = '';
                            $wpdb->is_single = true;
                            $post->post_content = self::strip_shortcode('sp_component', $post->post_content);
                            foreach( $sp_post_comps as $sp_post_comp ){
                                $post_comp_html .= $sp_post_comp->render();
                            }
                            $post->post_content .= $post_comp_html;
                            wp_update_post( $post );
                            $wpdb->is_single = false;
                        }
                    }
                }
            }

            // Call uninstall() for each component type
            $component_types = sp_core::get_component_types();
            foreach( $component_types as $id => $comp_type ){
                $class_name = 'sp_cat' . $comp_type->name;
                if( class_exists( $class_name ) ){
                    $class_name::uninstall();
                }
            }

            // Remove misc global options
            delete_option( 'sp_categories' );
            delete_option( 'sp_db_version' );
            delete_option( 'sp_defaultCat' );
            delete_option( 'sp_cat_icons' );
            delete_option( 'sp_responseCats' );
            delete_option( 'sp_cat_save_error' );

            // Delete all SmartPost tables
            $sp_tables = array( 'sp_postComponents', 'sp_catComponents', 'sp_compTypes');
            foreach( $sp_tables as $sp_table ){
                $table_name = $wpdb->prefix . $sp_table;
                $wpdb->query( $wpdb->prepare( "DROP TABLE $table_name" ) );
            }
        }

        /**
         * Call this function to uninstall SmartPost
         */
        public static function uninstall_smartpost(){
            error_log( 'uninstall_smartpost' );

            if ( __FILE__ != WP_UNINSTALL_PLUGIN )
                return;

            // For Single site
            if ( !is_multisite() )
            {
                self::uninstall_sp_data();
            }else{
                // For regular options.
                global $wpdb;
                $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                $original_blog_id = get_current_blog_id();
                foreach ( $blog_ids as $blog_id )
                {
                    switch_to_blog( $blog_id );
                    self::uninstall_sp_data();
                }
                switch_to_blog( $original_blog_id );
            }
        }
    }
}