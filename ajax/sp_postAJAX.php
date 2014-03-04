<?php
if (!class_exists("sp_postAJAX")) {
	/**
	 * Handles AJAX calls for sp_post class
	 */
	class sp_postAJAX{
		
        function init(){
            add_action('wp_ajax_savePostTitleAJAX', array('sp_postAJAX', 'savePostTitleAJAX'));
        }

        function savePostTitleAJAX(){
                $nonce = $_POST['nonce'];
                if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                    die('Security Check');
                }

                if( empty($_POST['postID']) ){
                    header("HTTP/1.0 409 Could not find postID.");
                    exit;
                }

                if( empty($_POST['post_title']) ){
                    header("HTTP/1.0 409 Post title is empty. Please fill in a post title!");
                    exit;
                }

                $postID    = (int) $_POST['postID'];
                $postTitle = (string) stripslashes_deep($_POST['post_title']);

                $post = get_post($postID);

                if(is_null($post)){
                    header("HTTP/1.0 409 Could not successfully load post.");
                    exit;
                }

                $post->post_title = $postTitle;
                $success = wp_update_post($post);
                if($success === 0){
                    header("HTTP/1.0 409 Could not successfully update the post title.");
                    exit;
                }

                echo json_encode(array('success' => true, 'details' => 'Post title successfully updated', 'postID' => $postID));
                exit;
        }
		
	}
}
?>