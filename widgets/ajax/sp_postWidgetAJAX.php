<?php
if (!class_exists("sp_postWidgetAJAX")) {
	class sp_postWidgetAJAX{
		
		static function init(){
				add_action('wp_ajax_setFeaturedImgAJAX', array('sp_postWidgetAJAX', 'setFeaturedImgAJAX'));
		}
		
		static function setFeaturedImgAJAX(){
			$nonce = $_POST['nonce'];
			if( !wp_verify_nonce($nonce, 'sp_nonce') ){
				header("HTTP/1.0 409 Security Check.");
				die('Security Check');
			}
			
			if( empty($_POST['postID']) ){
				header("HTTP/1.0 409 Could not locate post ID.");
				exit;
			}
			
			if( empty($_POST['attachmentID']) ){
				header("HTTP/1.0 409 Could not locate post ID.");
				exit;
			}
			
			$postID       = (int) $_POST['postID'];
			$attachmentID = (int) $_POST['attachmentID'];
			
			$post       = get_post($postID);
			$attachment = get_post($attachmentID);
			
			if( is_null($post) ){
				header("HTTP/1.0 409 Could not locate post!");
				exit;			
			}
			
			if( is_null($attachment) ){
				header("HTTP/1.0 409 Could not locate attachment!");
				exit;
			}
			
			//If it's the same thumbnail, pretend we succeeded but in
			//reality we should never get to this point
			$currThumbID = get_post_thumbnail_id($postID);
			if($currThumbID == $attachmentID){
				echo json_encode( array('success' => true) );
				exit;
			}
			
			$success = set_post_thumbnail($postID, $attachmentID);
			if($success === false){
				header("HTTP/1.0 409 Could not set featured image!");
				exit;			
			}
			
			echo json_encode( array('success' => true) );
			
			exit;			
		}
		
	}
}
?>