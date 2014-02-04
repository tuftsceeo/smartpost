<?php
/**
 * Handles AJAX on SmartPost dashboard for the Video component.
 */

if (!class_exists("sp_catVideoAJAX")) {
    class sp_catVideoAJAX{

        static function init(){
            add_action('wp_ajax_enableHTML5VideoAJAX', array('sp_catVideoAJAX', 'enableHTML5VideoAJAX'));
            add_action('wp_ajax_check_for_ffmpeg', array('sp_catVideoAJAX', 'check_for_ffmpeg'));
        }

        static function check_for_ffmpeg(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }

            if( empty( $_POST[ 'ffmpeg_path' ] ) ){
                header("HTTP/1.0 403 Provided path is empty.");
                exit;
            }

            $sp_ffmpeg_path = (string) stripslashes_deep( $_POST[ 'ffmpeg_path' ] );

            exec('command -v ' . $sp_ffmpeg_path . 'ffmpeg', $cmd_output, $cmd_status);

            if( $cmd_status === 0 ){
                update_site_option( 'sp_ffmpeg_path', $sp_ffmpeg_path );
                echo json_encode( array( 'success' => true, 'status_code' => $cmd_status, 'output' => $cmd_output[0] ) );
            }else{
                echo json_encode( array( 'status_code' => $cmd_status, 'output' => $cmd_output[0] ) );
            }

            exit;
        }

        /**
         * If ffmpeg is detected, handles checkbox AJAX to enable/disable HTML5
         * video encoding.
         */
        static function enableHTML5VideoAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                exit;
            }

            $videoComp = new sp_catVideo($compID);

            if(is_wp_error($mediaComp->errors)){
                header( 'HTTP/1.0 403 ' . $mediaCom->errors->get_error_message() );
                exit;
            }

            $convertToHTML5 = (bool) $_POST['convertToHTML5'];

            $success = update_site_option( 'sp_html5_encoding', $convertToHTML5);

            echo json_encode( array('success' => $success) );

            exit();
        }
    }
}