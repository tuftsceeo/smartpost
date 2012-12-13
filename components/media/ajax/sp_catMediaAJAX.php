<?php
/*
 * AJAX-Related functions for all
 * sp_postMedia components. Functions are used
 * in dashboard/admin section of the site.
 */

if (!class_exists("sp_catMediaAJAX")) {
	class sp_catMediaAJAX{
		
		static function init(){
			add_action('wp_ajax_saveMediaSettingsAJAX', array('sp_catMediaAJAX', 'saveMediaSettingsAJAX'));			
		}
	
		static function saveMediaSettingsAJAX(){
			$nonce = $_POST['nonce'];
			if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
				header("HTTP/1.0 403 Security Check.");
				exit;
			}					
		
			if( empty($_POST['compID']) ){
				header("HTTP/1.0 403 Could not find componentID to update.");
				exit;			
			}
			
			$compID    = (int) $_POST['compID'];
			$isGallery = (bool) $_POST['galleryMode'];
			$mediaComp = new sp_catMedia($compID);
			
			if(is_wp_error($mediaComp->errors)){
				header( 'HTTP/1.0 403 ' . $mediaCom->errors->get_error_message() );
				exit;
			}
			
			$allowedExts = $_POST['exts'];
			$customExts  = explode(",", $_POST['customExts']);
			$galleryMode = (bool) $_POST['galleryMode'];
			
			//Cleanup extensions
			foreach($customExts as $index => $ext){

				$ext = trim($ext);
				if( empty($ext) ){
					unset(	$customExts[$index] );
				}else{
					//!To-do: use regex to remove wierd characters
					$customExts[$index] = $ext;				
				}
				
			}
			
			//Merge the arrays
			$customExts = array_flip($customExts);
			foreach($customExts as $ext => $included){
				$customExts[$ext] = 1;
			}
			
			$options->allowedExts = $allowedExts;
			$options->customExts  = $customExts;
			$options->isGallery   = $galleryMode;
			
			$success = $mediaComp->setOptions($options);
			if($success === false){
				header( 'HTTP/1.0 403 Could not update media component succesfully' );
				exit;
		 }

			echo json_encode(array('success' => true));
			exit;
		}
		
	}
}
?>