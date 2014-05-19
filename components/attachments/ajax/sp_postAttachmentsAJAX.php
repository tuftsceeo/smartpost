<?php
/**
 * AJAX-Related functions for all
 * sp_postAttachments components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postAttachmentsAJAX")) {
    class sp_postAttachmentsAJAX{

        static function init(){
            add_action('wp_ajax_saveAttachmentsDescAJAX', array('sp_postAttachmentsAJAX', 'saveAttachmentsDescAJAX'));
            add_action('wp_ajax_attachmentsUploadAJAX', array('sp_postAttachmentsAJAX', 'attachmentsUploadAJAX'));
            add_action('wp_ajax_attachmentsDeleteAttachmentAJAX', array('sp_postAttachmentsAJAX', 'attachmentsDeleteAttachmentAJAX'));
        }

        function saveAttachmentsDescAJAX(){

            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postAttachments')){
                header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compid']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compid'];
            $desc = (string) stripslashes_deep( $_POST['content'] );
            $attachmentsComponent = new sp_postAttachments($compID);

            if( is_wp_error($attachmentsComponent->errors) ){
                header( "HTTP/1.0 409 " . $attachmentsComponent->errors->get_error_message() );
            }else{
                $attachmentsComponent->description = $desc;
                $success = $attachmentsComponent->update();
                if($success === false){
                    header("HTTP/1.0 409 Could not save link description.");
                }else{
                    echo json_encode(array('success' => true));
                }
            }
            exit;
        }

        /**
         * Deletes an attachment component
         */
        static function attachmentsDeleteAttachmentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postAttachments')){
                header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if( empty($_POST['attachmentID']) ){
                header("HTTP/1.0 409 Could find attachment ID to udpate.");
                exit;
            }

            $id = (int) $_POST['attachmentID'];
            $compID = (int) $_POST['compID'];
            $attachmentsComponent = new sp_postAttachments( $compID );

            if( is_wp_error( $attachmentsComponent->errors ) ){
                header( 'HTTP/1.0 409 Error: ' . $attachmentsComponent->errors->get_error_message() );
                exit;
            }

            // Delete the attachment
            wp_delete_attachment( $id, true );

            if( !empty( $attachmentsComponent->attachmentIDs ) ){
                $idKey = array_search( $id, $attachmentsComponent->attachmentIDs );
                if( $idKey !== false )
                    unset( $attachmentsComponent->attachmentIDs[$idKey] );

                $success = $attachmentsComponent->update();
                if( $success === false ){
                    header("HTTP/1.0 409 Could not successfully set attachment ID.");
                    exit;
                }
            }else{
                header( 'HTTP/1.0 409 Error: attachment ID "' . $id . '" could not be found!' );
                exit;
            }

            echo json_encode( array('sucess' => true) );
            exit;
        }

        /**
         * Handles attachment uploads
         */
        static function attachmentsUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postAttachments')){
                header("HTTP/1.0 409 Could not instantiate sp_postAttachments class.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if( empty($_FILES) ){
                header("HTTP/1.0 409 Files uploaded are empty!");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $attachmentsComponent = new sp_postAttachments($compID);

            if( is_wp_error( $attachmentsComponent->errors ) ){
                header( "HTTP/1.0 409 Error: " . $attachmentsComponent->errors->get_error_message() );
                exit;
            }
            // Upload the file
            $file = sp_core::chunked_plupload('sp-attachments-upload');

            if( file_exists($file) ){

                $allowedExts = $attachmentsComponent->allowedExts;

                if( !empty( $allowedExts ) ){
                    $allowed = sp_core::validateExtension($file, $allowedExts);
                }else{
                    $allowed = true;
                }

                if($allowed){

                    // Get a file name
                    if ( isset( $_REQUEST["name"] ) ) {
                        $name = $_REQUEST["name"];
                    } elseif ( !empty( $_FILES ) ) {
                        $name = $_FILES['sp-attachments-upload']["name"];
                    } else {
                        $name = uniqid("file_");
                    }

                    $attach_id = sp_core::create_attachment($file, $attachmentsComponent->getPostID(), $name );

                    array_push( $attachmentsComponent->attachmentIDs, $attach_id );
                    $success = $attachmentsComponent->update();

                    if( $success === false ){
                        header("HTTP/1.0 409 Could not successfully set attachment ID.");
                        exit;
                    }
                    echo $attachmentsComponent->renderAttachmentRow( $attach_id, true );
                }else{
                    // Delete the file
                    unlink($file);
                    header("HTTP/1.0 409 File type now allowed.");
                    echo 'File type now allowed.';
                    exit;
                }
            }else if( $file !== false && !file_exists( $file ) ){
                header( "HTTP/1.0 409 Could not successfully upload file!" );
                exit;
            }
            exit;
        }
    }
}
