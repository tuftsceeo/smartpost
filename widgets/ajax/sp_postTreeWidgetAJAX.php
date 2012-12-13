<?php
if (!class_exists("sp_postTreeWidgetAJAX")) {
	class sp_postTreeWidgetAJAX{
		
		static function init(){
				add_action('wp_ajax_relocatePostAJAX', array('sp_postTreeWidgetAJAX', 'relocatePostAJAX'));
		}
		
		function relocatePostAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 409 Security Check.");
					die('Security Check');
				}
				
				if( empty($_POST['postID']) ){
					header("HTTP/1.0 409 Could not locate post ID.");
					exit;
				}
				
				if( !isset( $_POST['parentID'] ) ){
					header("HTTP/1.0 409 Could not locate parent ID.");
					exit;
				}
				
				$postID      = (int) $_POST['postID'];
				$newParentID = (int) $_POST['parentID'];
				$post = get_post($postID);

				if( !isset($post) ){
						header("HTTP/1.0 409 Missing post parent and/or post.");
						exit;
				}
				
				//New array that holds update data
				$updatePost = array();
				$updatePost['ID'] = $post->ID;
				$updatePost['post_parent'] = $newParentID;
				
				//If we are provided a new catID, then assume we are converting the post
				if( !empty( $_POST['catID'] ) ){
							$catID = (int) $_POST['catID'];
							$catIDs = array();
							
							$curr_sp_category = sp_post::getSPCategory($postID);
			
							//Get the current categories
							$catObjs = get_the_category($postID);								
							if( !empty($catObjs) ){
								foreach($catObjs as $cat){
									array_push($catIDs, $cat->term_id);
								}
							}
							
							//Find the current SP category's key and replace it with the new one
							$currKey = array_search($curr_sp_category->getID(), $catIDs);
							$catIDs[$currKey] = $catID;	
							$updatePost['post_category'] = $catIDs;						
				}

				$success = wp_update_post( $updatePost );
				
				if($success === 0){
						header("HTTP/1.0 409 Could not relocate post.");
						exit;					
				}
				
				echo json_encode( array('success' => true, 'postUpdated' => $success) );
				exit;	
		}
		
	}
}
?>