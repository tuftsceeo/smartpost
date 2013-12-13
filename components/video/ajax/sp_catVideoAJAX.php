<?php
/**
 * Handles AJAX on SmartPost dashboard for the Video component.
 */

if (!class_exists("sp_catVideoAJAX")) {
    class sp_catVideoAJAX{

        static function init(){
            add_action('wp_ajax_enableHTML5VideoAJAX', array('sp_catVideoAJAX', 'enableHTML5VideoAJAX'));
        }

        static function enableHTML5VideoAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 403 Could not find componentID to update.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $videoComp = new sp_catVideo($compID);

            if(is_wp_error($mediaComp->errors)){
                header( 'HTTP/1.0 403 ' . $mediaCom->errors->get_error_message() );
                exit;
            }

            $options = $videoComp->getOptions();
            $options->convertToHTML5 = (bool) $_POST['convertToHTML5'];

            $success = $videoComp->setOptions($options);

            echo json_encode(array('success' => $success));

            exit();
        }
    }
}