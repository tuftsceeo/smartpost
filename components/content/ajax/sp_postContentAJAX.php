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
		
		function saveContentAJAX(){
		
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;					
				}

				$compID  = (int) $_POST['compID'];
				$content = (string) stripslashes_deep($_POST['content']);
				$content = strip_tags($content, '<p><a><font><span><h1><h1><h2><h3><h4><h5><h6><strong><ul><li><ol><img><table><tr><td><div>');
				$content = trim($content);
				$type      = 'sp_post' . (string) sp_postComponent::getCompTypeFromID($compID);
				$component = new $type($compID);
				$success   = $component->update($content);					

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
?>