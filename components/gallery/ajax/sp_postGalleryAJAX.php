<?php
/*
 * AJAX-Related functions for all
 * sp_postGallery components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postGalleryAJAX")) {
	class sp_postGalleryAJAX{
		
		static function init(){
			add_action('wp_ajax_saveGalleryDescAJAX', array('sp_postGalleryAJAX', 'saveGalleryDescAJAX'));		
			add_action('wp_ajax_galleryUploadAJAX', array('sp_postGalleryAJAX', 'galleryUploadAJAX'));
			add_action('wp_ajax_galleryDeletePicAJAX', array('sp_postGalleryAJAX', 'galleryDeletePicAJAX'));
		}
		
		function saveGalleryDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postGallery')){
                    header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
                    echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compid']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compid'];
            $desc = (string) stripslashes_deep($_POST['content']);
            $galleryComponent = new sp_postGallery( $compID );

            if( is_wp_error($galleryComponent->errors) ){

                header("HTTP/1.0 409 " . $galleryComponent->errors->get_error_message());

            }else{
                $galleryComponent->description = $desc;
                $success = $galleryComponent->update();

                if($success === false){
                    header("HTTP/1.0 409 Could not save link description.");
                }else{
                    echo json_encode(array('success' => true));
                }
            }
            exit;
		}		
		
		static function galleryDeletePicAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postGallery')){
                    header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
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
            $galleryComponent = new sp_postGallery( $compID );
            $postThumbID = get_post_thumbnail_id( $galleryComponent->getPostID() );
            $attachmentIDs = $galleryComponent->getAttachments();

            // Delete the attachment
            wp_delete_attachment( $id, true );
            $idKey = array_search( $id, $attachmentIDs );

            if( !empty($idKey) )
                unset( $attachmentIDs[$idKey] );

            $galleryComponent->attachmentIDs = $attachmentIDs;
            $success = $galleryComponent->update();

            if( $success === false ){
                header("HTTP/1.0 409 Could not successfully set attachment ID.");
                exit;
            }

            // Set the post thumb if the one that's deleted is the featured image
            if($postThumbID == $id){
                $args  = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_parent' => $galleryComponent->getPostID(), 'post_status' => null );
                $attachments = get_posts($args);
                if($attachments){
                    set_post_thumbnail($galleryComponent->getPostID(), $attachments[0]->ID);
                }else{
                    // Otherwise remove the thumbnail
                    delete_post_thumbnail( $galleryComponent->getPostID() );
                }
            }

            echo json_encode( array('sucess' => true) );
            exit;
		}

        /**
         * Handles uploading gallery photos asynchronously.
         */
        static function galleryUploadAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postGallery')){
                header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
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

            $file = sp_core::chunked_plupload('sp-gallery-upload');

            if( file_exists($file) ){

                $compID = (int) $_POST['compID'];
                $galleryComponent = new sp_postGallery($compID);

                if( is_wp_error($galleryComponent->errors) ){
                    header( "HTTP/1.0 409 Error:" . $galleryComponent->errors->get_error_message() );
                    exit;
                }

                $postID = $galleryComponent->getPostID();

                $name = '';
                // Get the file name
                if ( isset( $_REQUEST["name"] ) ) {
                    $name = $_REQUEST["name"];
                } elseif ( !empty( $_FILES ) ) {
                    $name = $_FILES['sp-gallery-upload']["name"];
                }

                // Issue #38: Before creating the image attachment, account for orientation
                $path_parts = pathinfo( $file );
                $allowed_exts = array( 'jpeg', 'jpg', 'JPEG', 'JPG', 'tiff', 'tif', 'TIFF', 'TIF');
                if( in_array( $path_parts['extension'], $allowed_exts) ){
                    sp_core::fixImageOrientation( $file );
                }

                $id = sp_core::create_attachment( $file, $postID, $name );

                $attachmentIDs = $galleryComponent->getAttachments();
                array_push($attachmentIDs, $id);
                $galleryComponent->attachmentIDs = $attachmentIDs;
                $success = $galleryComponent->update();

                if( $success === false ){
                    header("HTTP/1.0 409 Could not successfully set attachment ID.");
                    exit;
                }

                //Set featured image if it's not already set
                if( !has_post_thumbnail($postID) ){
                    set_post_thumbnail($postID, $id);
                }

                echo $galleryComponent->renderThumb($id, $compID);

            }else if( $file !== false && !file_exists( $file ) ){
                header( "HTTP/1.0 409 Could not successfully upload file!" );
                exit;
            }

            exit;
		}
		
		/**
		 * Creates a png from the webcam image
		 *
		 * @see http://www.xarg.org/project/jquery-webcam-plugin/
		 */
		static function handleWebcamUpload(){
				
            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            if ($_POST['type'] == "pixel") {
                // input is in format 1,2,3...|1,2,3...|...
                $im = imagecreatetruecolor(320, 240);

                foreach (explode("|", $_POST['image']) as $y => $csv) {
                    foreach (explode(";", $csv) as $x => $color) {
                        imagesetpixel($im, $x, $y, $color);
                    }
                }
            } else {
                // input is in format: data:image/png;base64,...
                $im = imagecreatefrompng($_POST['image']);
            }

            $compID = (int) $_POST['compID'];

            //Fetch the Gallery component
            $GalleryComponent = new sp_postGallery($compID);
            $postID   = $GalleryComponent->getPostID();
            $uploads  = wp_upload_dir();
            $filename = $uploads['path'] . '/webcam_' . date('ymdhs') . '.png';

            imagepng($im, $filename, 0);

            //Create an attachment!
            $wp_filetype = wp_check_filetype(basename($filename), null );
            $attachment = array(
                'guid'           => $uploads['baseurl'] . _wp_relative_upload_path( $filename ),
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content'   => 'Webcam Snapshot!',
                'post_status'    => 'inherit'
            );
            $attach_id = wp_insert_attachment( $attachment, $filename,  $GalleryComponent->getPostID() );
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            wp_update_attachment_metadata( $attach_id, $attach_data );

            $attachmentIDs = $GalleryComponent->getAttachments();

            //Delete the previous attachment if it's not a gallery
            if( !$GalleryComponent->isGallery() ){
                if( !empty($attachmentIDs) ){
                    wp_delete_attachment($attachmentIDs[0]);
                }
                $attachmentIDs[0] = $attach_id;
            }else{ //Otherwise if it's a gallery add the new attachment to the attachmentIDs array
                array_push($attachmentIDs, $attach_id);
            }

            $GalleryComponent->attachmentIDs = $attachmentIDs;
            $success = $GalleryComponent->update();

            if( $success === false || $success === 0 ){
                header("HTTP/1.0 409 Could not successfully set attachment ID.");
                exit;
            }

            //Set featured image if it's not already set
            if( !has_post_thumbnail($postID) ){
                set_post_thumbnail($postID, $attach_id);
            }

            exit;
		}
	}
}