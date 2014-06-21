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
            add_action('wp_ajax_downloadSamMov', array('sp_postSamAJAX', 'downloadSamMov'));
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
         * Create a .mp4 out of the saved image, then destroys the images?
         */
        function downloadSamMov(){
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

            $compID = (int) $_POST['compid'];
            $samComponent = new sp_postSam($compID);

            // Check to make sure there are images to stitch together.
            $imgs = $samComponent->imgs;
            if( !is_array( $imgs ) ){
                header("HTTP/1.0 409 Could not create movie. Please make sure you've saved images before attempting to download a movie.");
                exit;
            }else{

                // Check again to make sure there are images
                if( count( $samComponent->imgs ) === 0 ){
                    header("HTTP/1.0 409 Please make sure you've recorded a few images before attempting to create your SAM movie!");
                    exit;
                }

                // Delete old SAM movie if a previous exists
                if( file_exists( $samComponent->movie ) ){
                    unlink( $samComponent->movie );
                    $samComponent->update();
                }

                // Works only if ffmpeg is enabled
                $sp_ffmpeg_path = get_site_option('sp_ffmpeg_path');
                if( isset( $sp_ffmpeg_path ) && !is_wp_error( $sp_ffmpeg_path ) ){

                    $dl_url = plugins_url( 'download_sam_mov.php', dirname(__FILE__) );
                    $uploads  = wp_upload_dir();
                    $sam_mov_name = $uploads['path'] . DIRECTORY_SEPARATOR . 'my-sp-sam-mov-' . uniqid();

                    $ffmpeg_cmd = $sp_ffmpeg_path . 'ffmpeg -r 5 -i ' . $uploads['path'] . DIRECTORY_SEPARATOR . $samComponent->getID() . 'img%03d.png -c:v libx264 -r 30 -pix_fmt yuv420p ' . $sam_mov_name . '.mp4 2>&1';

                    exec( $ffmpeg_cmd, $ffmpeg_output, $ffmpeg_status );

                    if( $ffmpeg_status !== 0 ){
                        header("HTTP/1.0 409 Could not create movie. Please try again or contact your site administrator if the problem persists.");
                        exit;
                    }

                    if( file_exists( $sam_mov_name . '.mp4' ) ){

                        // Get rid of all the images once the video is made
                        foreach( $samComponent->imgs as $img ){
                            if( file_exists( $img ) ){
                                unlink($img);
                            }
                        }
                        $samComponent->imgs = array();
                        $samComponent->movie = $sam_mov_name . '.mp4';
                        //$sam_id = sp_core::create_attachment( $samComponent->movie, $samComponent->getPostID(), 'SAM Movie' );
                        //$samComponent->movie_id = $sam_id;
                        $samComponent->update();

                        echo json_encode( array( 'file_path' =>  $sam_mov_name . '.mp4', 'dl_url' => $dl_url )  );
                    }else{
                        header("HTTP/1.0 409 Could not create movie. Please try again or contact your site administrator if the problem persists.");
                        exit;
                    }
                }
            }
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
            array_push($imgs, $filename);
            
            $SamComp->fps = $fps;
            $SamComp->imgs = $imgs;
            $success = $SamComp->update();

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