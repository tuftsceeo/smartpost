<?php
if (!class_exists("sp_myPostsWidgetAJAX")) {
	class sp_myPostsWidgetAJAX{
		
		static function init(){
				add_action('wp_ajax_deletePostAJAX', array('sp_myPostsWidgetAJAX', 'deletePostAJAX'));
		}
		
		/**
		 * Deletes
		 */
		
		function deletePostAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 409 Security Check.");
					die('Security Check');
				}
				
				if( empty($_POST['postID']) ){
					header("HTTP/1.0 409 Could not locate post ID.");
					exit;
				}
				
				$postID = (int) $_POST['postID'];
				$post   = get_post($postID);
				
				if(is_null($post)){
					header("HTTP/1.0 409 Could not find the post to delete!");
					exit;
				}
				
				global $current_user;
				
				if($post->post_author != $current_user->ID){
					header("HTTP/1.0 409 You can only delete your own posts!");
					exit;					
				}
				
				wp_delete_post($postID, true);
				
				exit;
			}
		}
}
?>