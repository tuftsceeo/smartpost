<?php
/**
 * Created by PhpStorm.
 * User: ryagudin
 * Date: 3/4/14
 * Time: 3:10 PM
 */

if ( !class_exists("SP_Category_AJAX") ) {
    /**
     * Handles AJAX calls for sp_category class
     */
    class SP_Category_AJAX{

        function init(){
            add_action('wp_ajax_sp_save_cat_desc_ajax', array('SP_Category_AJAX', 'sp_save_cat_desc_ajax'));
        }

        /**
         * Saves a category description. Averts WordPress html filters for category descriptions.
         */
        function sp_save_cat_desc_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty( $_POST['catid'] ) ){
                header("HTTP/1.0 409 Could not find category ID.");
                exit;
            }

            if( current_user_can( 'manage_categories' ) ){
                $cat_id = (int) $_POST['catid'];
                $filters = array('term_description','category_description','pre_term_description');
                foreach ( $filters as $filter ) {
                    remove_filter($filter, 'wptexturize');
                    remove_filter($filter, 'convert_chars');
                    remove_filter($filter, 'wpautop');
                    remove_filter($filter, 'wp_filter_kses');
                    remove_filter($filter, 'strip_tags');
                }

                $description = stripslashes_deep( $_POST['content'] );
                $success = wp_update_term($cat_id, 'category', array( 'description' => $description ) );

                if( is_wp_error( $success ) ){
                    header( "HTTP/1.0 409 Error:" . $success->get_error_message() );
                }else{
                    echo json_encode( array('success' => $success) );
                }
            }
            exit;
        }
    }
}