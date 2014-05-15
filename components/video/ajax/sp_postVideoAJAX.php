<?php
/*
 * AJAX-Related functions for all
 * sp_postVideo components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postVideoAJAX")) {
    class sp_postVideoAJAX{

        static function init(){
            add_action('wp_ajax_videoUploadAJAX', array('sp_postVideoAJAX', 'videoUploadAJAX'));
            add_action('wp_ajax_checkVideoStatusAJAX', array('sp_postVideoAJAX', 'checkVideoStatusAJAX'));
            add_action('wp_ajax_saveVideoDescAJAX', array('sp_postVideoAJAX', 'saveVideoDescAJAX'));
        }

        /**
         * AJAX function that saves the video caption/description.
         */
        static function saveVideoDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( !class_exists( 'sp_postVideo' ) ){
                header("HTTP/1.0 409 Could not instantiate sp_postMedia class.");
                exit;
            }

            if( empty( $_POST['compid'] ) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            // Update video description
            $compID = (int) $_POST['compid'];
            $videoComponent = new sp_postVideo($compID);

            if( !empty($videoComponent->errors) ){
                header( "HTTP/1.0 409 Error: " . $videoComponent->errors->get_error_message() );
                exit;
            }

            $videoComponent->description = stripslashes_deep( $_POST['content'] );
            $videoComponent->update();
            echo json_encode( array('success' => true) );
            exit;
        }

        /**
         * Checks on the status of the video.
         */
        static function checkVideoStatusAJAX(){
            $nonce = $_POST['nonce'];

            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postVideo')){
                header("HTTP/1.0 409 Could not instantiate sp_postMedia class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $videoComponent = new sp_postVideo($compID);
            if( $videoComponent->beingConverted ){
                echo json_encode( array( 'converted' => false ) );
            }else{
                echo json_encode( array( 'converted' => true ) );
            }
            exit;
        }

        /**
         * Handles video uploads using chunking.
         * @todo Do the conversion to mp4 and get rid of the original mov (?)
         */
        static function videoUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postVideo')){
                header("HTTP/1.0 409 Could not instantiate sp_postMedia class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if(empty($_FILES)){
                header("HTTP/1.0 409 Files uploaded are empty!");
                exit;
            }

            $videoFilePath = sp_core::chunked_plupload( "sp_videoUpload" );

            if( file_exists( $videoFilePath ) ){

                $compID = (int) $_POST['compID'];
                $videoComponent = new sp_postVideo($compID);
                $postID = $videoComponent->getPostID();

                // Delete previous attachments if they exist
                if( !empty($videoComponent->videoAttachmentIDs) ){
                    foreach($videoComponent->videoAttachmentIDs as $attach_id){
                        if( $attach_id ){
                            wp_delete_attachment( $attach_id, true );
                        }
                    }
                }

                $html5_encoding = (bool) get_site_option( 'sp_html5_encoding' );
                $sp_ffmpeg_path = get_site_option( 'sp_ffmpeg_path' );

                if( $html5_encoding && !is_wp_error( $sp_ffmpeg_path ) ){

                    $videoComponent->beingConverted = true;
                    $videoComponent->update();

                    $script_path = dirname(dirname(__FILE__)) . '/html5video.php';

                    $script_args = array(
                        'BASE_PATH' => ABSPATH,
                        'POST_ID'   => $postID,
                        'VID_FILE'  => $videoFilePath,
                        'COMP_ID'   => $compID,
                        'AUTH_ID'   => get_current_user_id(),
                        'WIDTH'     => get_site_option('sp_player_width'),
                        'HEIGHT'    => get_site_option('sp_player_height'),
                        'HTTP_HOST' => $_SERVER['HTTP_HOST'],
                        'BLOG_ID'   => get_current_blog_id(),
                        'IS_WPMU'   => is_multisite()
                    );

                    if(DEBUG_SP_VIDEO){
                        error_log( 'SCRIPT ARGS: ' . print_r($script_args, true) );
                        exec('php ' . $script_path . ' ' . implode(' ', $script_args) . ' 2>&1', $output, $status);
                        error_log( print_r($output, true) );
                        error_log( print_r($status, true) );
                    }else{
                        shell_exec('php ' . $script_path . ' ' . implode(' ', $script_args) . ' &> /dev/null &');
                    }
                }else{

                    // Check to see that it's mp4 format
                    $ext = pathinfo($videoFilePath, PATHINFO_EXTENSION);
                    if( $ext !== 'mp4' ){
                        unlink($videoFilePath);
                        header( "HTTP/1.0 409 Error: only mp4 files are allowed to be uploaded when HTML5 encoding is not enabled!" );
                        exit;
                    }else{
                        // Create the attachment
                        $videoComponent->videoAttachmentIDs['mp4'] = sp_core::create_attachment( $videoFilePath, $postID, '', get_current_user_id() );
                        $videoComponent->update();
                    }

                }
                echo $videoComponent->renderPlayer();

            }else if( $videoFilePath !== false && !file_exists( $videoFilePath )  ){
                header( "HTTP/1.0 409 There was an error with uploading the video. The video file could not be found." );
            }
            exit;
        }

    }
}