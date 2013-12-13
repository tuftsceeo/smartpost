<?php
/*
 * AJAX-Related functions for all
 * sp_postGallery components. Functions are used
 * in front end posts.
 */

if (!class_exists("sp_postGalleryAJAX")) {
	class sp_postGalleryAJAX{
		
		static function init(){
			add_action('wp_ajax_saveGalleryDescAJAX', array('sp_postGalleryAJAX', 'saveGalleryDescAJAX'));		
			add_action('wp_ajax_GalleryUploadAJAX', array('sp_postGalleryAJAX', 'GalleryUploadAJAX'));
			add_action('wp_ajax_GalleryDeleteAttachmentAJAX', array('sp_postGalleryAJAX', 'GalleryDeleteAttachmentAJAX'));			
		}
		
		function saveGalleryDescAJAX(){
		
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}

				if(!class_exists('sp_postGallery')){
						header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
						echo json_encode(array('error' => 'Could not save link.'));						
				}
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;					
				}
					
				$compID		       = (int) $_POST['compID'];
				$attachmentID   = (int)  $_POST['attachmentID'];
				$desc				       = (string) stripslashes_deep($_POST['desc']);	
				$GalleryComponent = new sp_postGallery($compID);
	
				if( is_wp_error($linkComponent->errors) ){
					header("HTTP/1.0 409 " . $success->get_error_message());
				}else{
					$success = $GalleryComponent->setDescription($desc, $attachmentID);
					if($success === false){
						header("HTTP/1.0 409 Could not save link description.");
					}else{
						echo json_encode(array('success' => true));
					}
					
				}
				exit;
		}		
		
		static function GalleryDeleteAttachmentAJAX(){
				$nonce = $_POST['nonce'];
			 if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}
				
				if(!class_exists('sp_postGallery')){
						header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
						exit;
				}	

				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;
				}
				
				if( empty($_POST['attachmentID']) ){
					header("HTTP/1.0 409 Could find attachment ID to udpate.");
					exit;
				}
				
				$id										 = (int) $_POST['attachmentID'];
				$compID       = (int) $_POST['compID'];
				$GalleryComponent = new sp_postGallery($compID);
				$postThumbID    = get_post_thumbnail_id($GalleryComponent->getPostID());				
				$attachmentIDs  = $GalleryComponent->getAttachments();
				
				//Delete the attachment
				wp_delete_attachment( $id, true );
				$idKey = array_search($id, $attachmentIDs);
				
				if( !empty($idKey) )
						unset( $attachmentIDs[$idKey] );
				
				$success = $GalleryComponent->setAttachmentIDs( $attachmentIDs );
				if( $success === false ){
					header("HTTP/1.0 409 Could not successfully set attachment ID.");
					exit;					
				}
				
				//Set the post thumb if the one that's deleted is the featured image
				if($postThumbID == $id){
					$args  = array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_parent' => $post->ID, 'post_status' => null );
					$attachments = get_posts($args);
					if($attachments){
						set_post_thumbnail($GalleryComponent->getPostID(), $attachments[0]->ID);
					}
				}
				
				echo json_encode( array('sucess' => true) );
				exit;
		}
		
		static function GalleryUploadAJAX(){		
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}
				
				if(!class_exists('sp_postGallery')){
						header("HTTP/1.0 409 Could not instantiate sp_postGallery class.");
						exit;
				}	

				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;
				}
				
				//Webcam uploads
				if( !empty($_POST['type']) ){
					self::handleWebcamUpload();
					exit;
				}
				
				if(empty($_FILES)){
						header("HTTP/1.0 409 Files uploaded are empty!");	
						exit;
				}
				
				$compID = (int) $_POST['compID'];
				$GalleryComponent = new sp_postGallery($compID);
				$postID = $GalleryComponent->getPostID();
				
				$defaultExts = $GalleryComponent->getExtensions();
				$customExts  = $GalleryComponent->getCustomExts();
				$defaultExts = !empty($defaultExts) ? array_keys($GalleryComponent->getExtensions()) : array();
				$customExts  = !empty($customExts)  ? array_keys($GalleryComponent->getCustomExts()) : array();
	
				$allowedExts = array_merge($defaultExts, $customExts);
				$allowed = sp_core::validateExtension($_FILES['sp_Gallery_files']['name'], $allowedExts);
				
				//$description = $GalleryComponent->getDescription();
				if($allowed){
					$caption = $_FILES['sp_Gallery_files']['name'];
					$id = sp_core::upload($_FILES, 'sp_Gallery_files', $postID, array('post_title' => $caption));
				}else{
					header("HTTP/1.0 409 File type not allowed.");
					exit;
				}
				
				if(is_wp_error($id)){
					header("HTTP/1.0 409 Could not successfully upload file, " . $id->get_error_message());
					exit;
				}

				$attachmentIDs = $GalleryComponent->getAttachments();

				//Delete the previous attachment if it's not a gallery
				if( !$GalleryComponent->isGallery() ){
					if( !empty($attachmentIDs) ){
						wp_delete_attachment($attachmentIDs[0]);
					}
					$attachmentIDs[0] = $id;					
				}else{ //Otherwise if it's a gallery add the new attachment to the attachmentIDs array
					array_push($attachmentIDs, $id);				
				}
				
				$success = $GalleryComponent->setAttachmentIDs($attachmentIDs);
				if( $success === false ){
					header("HTTP/1.0 409 Could not successfully set attachment ID.");
					exit;					
				}
				
				//Set featured image if it's not already set
				if( !has_post_thumbnail($postID) ){
					set_post_thumbnail($postID, $id);
				}
				
				self::attachmentJSON($id);
				
				exit;
		}
		
		/**
		 * Creates a png from the webcam image
		 *
		 * @see http://www.xarg.org/project/jquery-webcam-plugin/
		 */
		static function handleWebcamUpload(){
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;
				}				
				
				if ($_POST['type'] == "pixel") {
     // input is in format 1,2,3...|1,2,3...|...
     $im = imagecreatetruecolor(320, 240);

     foreach (explode("|", $_POST['image']) as $y => $csv) {
        foreach (explode(";", $csv) as $x => $color) {
         imagesetpixel($im, $x, $y, $color);
        }
     }
				} else {
      // input is in format: data:image/png;base64,...
      $im = imagecreatefrompng($_POST['image']);
				}

				$compID = (int) $_POST['compID'];
				
				//Fetch the Gallery component
				$GalleryComponent = new sp_postGallery($compID);
				$postID   = $GalleryComponent->getPostID();
				$uploads  = wp_upload_dir();
				$filename = $uploads['path'] . '/webcam_' . date('ymdhs') . '.png';
								
				imagepng($im, $filename, 0);
				
				//Create an attachment!
		  $wp_filetype = wp_check_filetype(basename($filename), null );
		  $attachment = array(
		     'guid' => $uploads['baseurl'] . _wp_relative_upload_path( $filename ), 
		     'post_mime_type' => $wp_filetype['type'],
		     'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		     'post_content' => 'Webcam Snapshot!',
		     'post_status' => 'inherit'
		  );
		  $attach_id = wp_insert_attachment( $attachment, $filename,  $GalleryComponent->getPostID() );
		  require_once(ABSPATH . 'wp-admin/includes/image.php');
		  $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		  wp_update_attachment_metadata( $attach_id, $attach_data );				

				$attachmentIDs = $GalleryComponent->getAttachments();

				//Delete the previous attachment if it's not a gallery
				if( !$GalleryComponent->isGallery() ){
					if( !empty($attachmentIDs) ){
						wp_delete_attachment($attachmentIDs[0]);
					}
					$attachmentIDs[0] = $attach_id;
				}else{ //Otherwise if it's a gallery add the new attachment to the attachmentIDs array
					array_push($attachmentIDs, $attach_id);				
				}
				
				$success = $GalleryComponent->setAttachmentIDs($attachmentIDs);
				if( $success === false || $success === 0 ){
					header("HTTP/1.0 409 Could not successfully set attachment ID.");
					exit;					
				}
				
				//Set featured image if it's not already set
				if( !has_post_thumbnail($postID) ){
					set_post_thumbnail($postID, $attach_id);
				}				
				
				self::attachmentJSON($attach_id);
				exit;
		}
		
		/**
		 * Echo out JSON info about an attachment
		 */
		static function attachmentJSON($id){
			$attachment = get_post($id);
			$fileURL = wp_get_attachment_url($id);
			$thumb   = wp_get_attachment_image_src( $id, array(100, 60), true );
			$caption = $attachment->post_title;
			echo json_encode( array('id' => $id, 'fileURL' => $fileURL, 'thumbURL' => $thumb, 'caption' => $caption) );		
		}
		
	}
}
?>