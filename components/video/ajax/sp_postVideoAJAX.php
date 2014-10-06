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

            // Display error if there is one
            if( !empty($videoComponent->error) ){
                header( "HTTP/1.0 409 " . $videoComponent->error );
                exit;
            }

            // Check to if video conversion is over
            if( !$videoComponent->beingConverted ){

                $video_info = $videoComponent->videoAttachmentIDs;
                if( empty( $video_info ) ){
                    header("HTTP/1.0 409 Could find uploaded video!");
                    exit;
                }

                if( file_exists( $video_info['encoded_video'] ) ){
                    echo json_encode( array( 'converted' => true ) );
                }
            }else{
                echo json_encode( array( 'converted' => false ) );
            }
            exit;
        }

        /**
         * Handles video uploads using chunking.
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

            $uploaded_video = sp_core::chunked_plupload( "sp_videoUpload" );

            if( file_exists( $uploaded_video ) ){

                $compID = (int) $_POST['compID'];
                $videoComponent = new sp_postVideo($compID);

                // Delete previous attachments if they exist
                if( !empty( $videoComponent->videoAttachmentIDs ) ){
                    foreach( $videoComponent->videoAttachmentIDs as $attach_id ){
                        if( $attach_id ){
                            wp_delete_attachment( $attach_id, true );
                        }
                    }
                    $videoComponent->videoAttachmentIDs = array(); // reset attachment IDs
                }

                // Attempt to encode the video
                sp_postVideo::encode_via_ffmpeg( $videoComponent, $uploaded_video );
                echo $videoComponent->renderPlayer();

            }else if( $uploaded_video !== false && !file_exists( $uploaded_video )  ){
                header( "HTTP/1.0 409 There was an error with uploading the video. The video file could not be found." );
            }
            exit;
        }

    }
}