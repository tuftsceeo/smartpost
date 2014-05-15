<?php
/*
 * AJAX-Related functions for all
 * sp_postPhoto components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postPhotoAJAX")) {
	class sp_postPhotoAJAX{
		
		static function init(){
			add_action('wp_ajax_savePhotoDescAJAX', array('sp_postPhotoAJAX', 'savePhotoDescAJAX'));		
			add_action('wp_ajax_photoUploadAJAX', array('sp_postPhotoAJAX', 'photoUploadAJAX'));
			add_action('wp_ajax_photoDeletePicAJAX', array('sp_postPhotoAJAX', 'photoDeletePicAJAX'));
		}
		
		function savePhotoDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postPhoto')){
                    header("HTTP/1.0 409 Could not instantiate sp_postPhoto class.");
                    echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compid']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compid'];
            $desc = (string) stripslashes_deep( $_POST['content'] );
            $photoComponent = new sp_postPhoto( $compID );

            if( is_wp_error($photoComponent->errors) ){
                header("HTTP/1.0 409 " . $photoComponent->errors->get_error_message());
            }else{

                $photoComponent->caption = $desc;
                $success = $photoComponent->update();

                if($success === false){
                    header("HTTP/1.0 409 Could not save link description.");
                }else{
                    echo json_encode(array('success' => true));
                }
            }
            exit;
		}		
		
		static function photoDeletePicAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postPhoto')){
                    header("HTTP/1.0 409 Could not instantiate sp_postPhoto class.");
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

            $id	= (int) $_POST['attachmentID'];
            $compID = (int) $_POST['compID'];
            $photoComponent = new sp_postPhoto( $compID );

            $oldPhotoID = $photoComponent->photoID;

            // Delete the attachment
            wp_delete_attachment( $oldPhotoID, true );

            if( get_post_thumbnail_id( $photoComponent->getPostID() ) == $oldPhotoID ){
                delete_post_thumbnail( $photoComponent->getPostID() );
            }

            $photoComponent->photoID = null;
            $success = $photoComponent->update();

            if( $success === false ){
                header("HTTP/1.0 409 Could not successfully set attachment ID.");
                exit;
            }

            echo json_encode( array('sucess' => true) );
            exit;
		}

        /**
         * Handles uploading photo photos asynchronously.
         */
        static function photoUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postPhoto')){
                header("HTTP/1.0 409 Could not instantiate sp_postPhoto class.");
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

            $file = sp_core::chunked_plupload('sp-photo-upload');

            if( file_exists($file) ){

                $compID = (int) $_POST['compID'];
                $photoComponent = new sp_postPhoto($compID);

                if( is_wp_error($photoComponent->errors) ){
                    header( "HTTP/1.0 409 Error:" . $photoComponent->errors->get_error_message() );
                    exit;
                }

                $name = '';
                if ( isset( $_REQUEST["name"] ) ) {
                    $name = $_REQUEST["name"];
                } elseif ( !empty( $_FILES ) ) {
                    $name = $_FILES["sp-photo-upload"]["name"];
                }

                $postID = $photoComponent->getPostID();

                // Issue #38: Before creating the image attachment, account for orientation
                sp_core::fixImageOrientation( $file );

                $id = sp_core::create_attachment( $file, $postID, $name );

                $oldPhotoID = $photoComponent->photoID;
                wp_delete_attachment( $oldPhotoID ); // Replace old attachment

                $photoComponent->photoID = $id; // Update with new ID
                $success = $photoComponent->update();

                if( $success === false ){
                    header("HTTP/1.0 409 Could not successfully set attachment ID.");
                    exit;
                }

                if( get_post_thumbnail_id( $postID ) == $oldPhotoID ){
                    set_post_thumbnail($postID, $id); // Replace featured img id with new one
                }else if( !has_post_thumbnail($postID) ){
                    set_post_thumbnail($postID, $id); // Set new featured img if one isn't set
                }

                echo $photoComponent->renderThumb( $id, $compID );

            }else if( $file !== false && !file_exists( $file ) ){
                header( "HTTP/1.0 409 Could not successfully upload file!" );
                exit;
            }

            exit;
		}
	}
}