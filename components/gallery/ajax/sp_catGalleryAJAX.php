<?php
/*
 * AJAX-Related functions for all
 * sp_postGallery components. Functions are used
 * in dashboard/admin section of the site.
 */

if (!class_exists("sp_catGalleryAJAX")) {
	class sp_catGalleryAJAX{
		
		static function init(){
			add_action('wp_ajax_saveGallerySettingsAJAX', array('sp_catGalleryAJAX', 'saveGallerySettingsAJAX'));
		}

		static function saveGallerySettingsAJAX(){
			error_log('test');
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
			$GalleryComp = new sp_catGallery($compID);
			
			if(is_wp_error($GalleryComp->errors)){
				header( 'HTTP/1.0 403 ' . $GalleryCom->errors->get_error_message() );
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
			
			$success = $GalleryComp->setOptions($options);
			if($success === false){
				header( 'HTTP/1.0 403 Could not update Gallery component succesfully' );
				exit;
		 }

			echo json_encode(array('success' => true));
			exit;
		}
		
	}
}
?>