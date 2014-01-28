<?php
/*
 * AJAX-Related functions for all
 * sp_postContent components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postContentAJAX")) {
	class sp_postContentAJAX{
	
		static function init(){
			add_action('wp_ajax_saveContentAJAX', array('sp_postContentAJAX', 'saveContentAJAX'));
		}

        /**
         * Saves the content of a content component.
         */
        function saveContentAJAX(){
		
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['postid']) ){
                header("HTTP/1.0 409 Could find post ID to udpate.");
                exit;
            }

            if( empty($_POST['compid']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $postID  = (int) $_POST['postid'];
            $compID  = (int) $_POST['compid'];

            $content = (string) stripslashes_deep($_POST['content']);
            $content = strip_tags($content, '<p><a><font><span><h1><h1><h2><h3><h4><h5><h6><strong><ul><li><ol><img><table><tr><td><br>');
            $content = trim($content);

            $contentComp = new sp_postContent( $compID );
            if( !empty( $contentComp->errors ) ){
                header("HTTP/1.0 409 Could not instantiate the content component.");
                exit;
            }

            if( $contentComp->getPostID() != $postID ){
                header("HTTP/1.0 409 Tsk. Tsk. Trying to update the wrong post are we?");
                exit;
            }

            $success = $contentComp->update($content);

            if( $success === false ){
                header("HTTP/1.0 409 Could not save content.");
                echo json_encode(array('error' => 'Could not save content.'));
            }else{
                echo json_encode(array('success' => true));
            }
            exit;
		}
	}
}