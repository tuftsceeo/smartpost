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
            $videoComponent->update(null);
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
         * @todo Abstract out chunk uploading to sp_core.php class
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

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could find post ID.");
                exit;
            }

            if(empty($_FILES)){
                header("HTTP/1.0 409 Files uploaded are empty!");
                exit;
            }

            // Get a file name
            if ( isset( $_REQUEST["name"] ) ) {
                $fileName = $_REQUEST["name"];
            } elseif ( !empty( $_FILES ) ) {
                $fileName = $_FILES["sp_videoUpload"]["name"];
            } else {
                $fileName = uniqid("file_");
            }

            $uploadsPath = wp_upload_dir();
            $filePath =  $uploadsPath['path'] . DIRECTORY_SEPARATOR . $fileName;

            // Chunking might be enabled
            $chunk  = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
            $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

            // Open temp file
            if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
                die('{ "jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."} }');
            }

            if ( !empty($_FILES) ) {
                if ($_FILES["sp_videoUpload"]["error"] || !is_uploaded_file($_FILES["sp_videoUpload"]["tmp_name"])) {
                    die('{ "jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."} }');
                }

                // Read binary input stream and append it to temp file
                if (!$in = @fopen($_FILES["sp_videoUpload"]["tmp_name"], "rb")) {
                    die('{ "jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."} }');
                }
            } else {
                if (!$in = @fopen("php://input", "rb")) {
                    die('{ "jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."} }');
                }
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            fclose($out);
            fclose($in);

            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks - 1) {

                // Format the file name
                $newFilePath = preg_replace( '/\s+/', '', $filePath ); // Remove all whitespace
                $newFilePath = str_replace( '.', '_' . uniqid() . '.', $newFilePath ); // Add a unique ID to keep file names unique
                rename( "{$filePath}.part", $newFilePath ); // Strip the temp .part suffix off

                // Create a new attachment
                $compID = (int) $_POST['compID'];
                $videoComponent = new sp_postVideo($compID);
                $postID = $videoComponent->getPostID();
                $vid_id = sp_core::create_attachment( $newFilePath, $postID, $fileName, get_current_user_id() );

                if( !$vid_id ){
                    header( "http/1.0 409 " . $vid_id->get_error_message() );
                    die();
                }

                //Delete previous attachments if they exist
                if( !empty($videoComponent->videoAttachmentIDs) ){
                    foreach($videoComponent->videoAttachmentIDs as $attach_id){
                        if( $attach_id )
                            wp_delete_attachment( $attach_id, true );
                    }
                }

                //Update the component with at least the .mov in case something is up with converting..
                $videoComponent->videoAttachmentIDs['mov'] = $vid_id;
                $videoComponent->update(null);

                $html5_encoding = (bool) get_site_option('sp_html5_encoding');

                if( $html5_encoding ){

                    $videoComponent->beingConverted = true;
                    $videoComponent->update(null);

                    $script_path = dirname(dirname(__FILE__)) . '/html5video.php';

                    $script_args = array(
                        'BASE_PATH' => ABSPATH,
                        'POST_ID'   => $postID,
                        'VID_FILE'  => $newFilePath,
                        'COMP_ID'   => $compID,
                        'AUTH_ID'   => get_current_user_id(),
                        'MOV_ID'    => $vid_id,
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
                }
                echo $videoComponent->renderPlayer();
            }

            exit;
        }

    }
}