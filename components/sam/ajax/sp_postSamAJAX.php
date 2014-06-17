<?php
/*
 * AJAX-Related functions for all
 * sp_postContent components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postSamAJAX")) {
	class sp_postSamAJAX{
	
		static function init(){
			add_action('wp_ajax_saveSamAJAX', array('sp_postSamAJAX', 'saveSamAJAX'));
			add_action('wp_ajax_saveSamDescAJAX', array('sp_postSamAJAX', 'saveSamDescAJAX'));
		}

        /**
         * AJAX function that saves the video caption/description.
         */
        static function saveSamDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( !class_exists( 'sp_postSam' ) ){
                header("HTTP/1.0 409 Could not instantiate sp_postMedia class.");
                exit;
            }

            if( empty( $_POST['compid'] ) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            // Update video description
            $compID = (int) $_POST['compid'];
            $samComponent = new sp_postSam($compID);

            if( !empty($samComponent->errors) ){
                header( "HTTP/1.0 409 Error: " . $samComponent->errors->get_error_message() );
                exit;
            }

            $samComponent->description = stripslashes_deep( $_POST['content'] );
            $samComponent->update();
            echo json_encode( array('success' => true) );
            exit;
        }

        /**
         * Saves the content of a Sam component.
         */
        function saveSamAJAX(){
		    
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could find post ID to udpate.");
                exit;
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }
            
            $postID  = (int) $_POST['postID'];
            $compID  = (int) $_POST['compID'];

            $SamComp = new sp_postSam( $compID );
            if( !empty( $SamComp->errors ) ){
                header("HTTP/1.0 409 Could not instantiate the content component.");
                exit;
            }

            if( $SamComp->getPostID() != $postID ){
                header("HTTP/1.0 409 Tsk. Tsk. Trying to update the wrong post are we?");
                exit;
            }
            
            // get data about this new image from $_POST
            $fps = (int) $_POST['fps'];
            $imgData = $_POST['img'];
            $frameNum = (int) $_POST['frameNum'];
            
            // imgData is encoded as 'data:image/png;base64,<ACTUAL IMAGE DATA>'
            // pull off actual image data, create from string
            $data = explode(',', $imgData);
            $data = base64_decode($data[1]);
            $im = imagecreatefromstring($data);
            
            // get path for upload
            $uploads  = wp_upload_dir();
            $frameString = sprintf( '%03d', $frameNum);
            $idString = sprintf( '%d', $compID);
            $filename = $uploads['path'] . '/' . $idString . 'img' . $frameString . '.png';

            // create a png image at location
            $conv = imagepng($im, $filename, 0);

            //Create an attachment!
            $wp_filetype = wp_check_filetype(basename($filename), null );
            $attachment = array(
                'guid'           => $uploads['baseurl'] . _wp_relative_upload_path( $filename ),
                'post_mime_type' => $wp_filetype['type'],
                'post_title'     => preg_replace('/\.[^.]+$/', '', basename($filename)),
                'post_content'   => 'SAM_Image',
                'post_status'    => 'inherit'
            );
            $img_id = wp_insert_attachment( $attachment, $filename,  $postID );
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $img_data = wp_generate_attachment_metadata( $img_id, $filename );
            wp_update_attachment_metadata( $img_id, $img_data );

            // get images this component already has
            $imgs = $SamComp->getImgs();
            
            // if it's not an array, it should be...
            if ( !is_array($imgs) )
                $imgs = array();
            
            // if this is supposed to be the first frame,
            //  but this object already has images, remove them
            //  used if the frames were cleared on the front end
            if ($frameNum == 0 ) {
                while ( !empty( $imgs ) ) {
                    wp_delete_attachment (array_shift( $imgs ) );
                }
            }
            // insert new img into the array
            array_push($imgs, $img_id);
            
            $SamComp->fps = $fps;
            $SamComp->imgs = $imgs;
            $success = $SamComp->update();

            /**
                    WE STILL NEED TO CONVERT IT INTO A VIDEO
                    which should happen once the post has been submitted
            **/

            if( $success === false ){
                header("HTTP/1.0 409 Could not save content.");
                echo json_encode(array('error' => 'Could not save content.'));
            }else{
                echo json_encode(array('success' =>  count($SamComp->imgs) ));
            }
            exit;
		}
	}
}