<?php
if (!class_exists("sp_adminAJAX")) {
	class sp_adminAJAX{
		
		/**
		 * Called on plugin initialization. Adds necessary action hooks to handle
		 * AJAX requests.
		 */
		static function init(){
			add_action('wp_ajax_catFormAJAX', array('sp_adminAJAX', 'catFormAJAX'));
			add_action('wp_ajax_newSPCatAJAX', array('sp_adminAJAX', 'newSPCatAJAX'));
			add_action('wp_ajax_updateSPCatAJAX', array('sp_adminAJAX', 'updateSPCatAJAX'));
			add_action('wp_ajax_renderSPCatSettingsAJAX', array('sp_adminAJAX', 'renderSPCatSettingsAJAX'));
			add_action('wp_ajax_responseCatAJAX', array('sp_adminAJAX', 'responseCatAJAX'));
			add_action('wp_ajax_switchCategoryAJAX', array('sp_adminAJAX', 'switchCategoryAJAX'));			
		}
		
	/**********************************
	 * Category AJAX functions        *
	 **********************************/				
		
		/**
		 * "Enables" a wordpress category, or "disables" a SP category.
		 * If $_POST['isSPCat'] is true, it will remove $_POST['catID'] 
		 * from global WP option 'sp_cateogories', otherwise it will add it.
		 */
		function switchCategoryAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					header("HTTP/1.0 409 Security Check.");
					die('Security Check');
				}
				
				if( empty($_POST['catID']) ){
					header("HTTP/1.0 409 Could not find catID.");
					exit;
				}
				
				if( is_null($_POST['isSPCat']) ){
					header("HTTP/1.0 409 Could not find differentiate categories.");
					exit;
				}
				
				$isSPCat = (bool) $_POST['isSPCat'];
				$catID   = (int) $_POST['catID'];			
				$sp_categories = get_option('sp_categories');
				
				if(!$isSPCat){
					if( !in_array($catID, $sp_categories) ){
						array_push($sp_categories, $catID);
						update_option('sp_categories', $sp_categories);
					}
				}else{
					$key = array_search($catID, $sp_categories);
					if( $key !== false){
						unset( $sp_categories[$key] );
						update_option('sp_categories', $sp_categories);						
					}else{
						header("HTTP/1.0 409 Could not find the spCatID to disable.");
						exit;
					}
				}
				
				echo json_encode( array('success' => true) );
				exit;
		}
		
		/**
		 * Returns an HTML category form.
		 * @uses sp_admin::newCatForm()
		 * @uses sp_admin::catForm()
		 */ 
		function catFormAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				$newSPCat = $_POST['newSPCat'];
				$catID 			= $_POST['catID'];
				
				if( (bool) $newSPCat ){
					echo sp_admin::newCatForm();
				}else{
					echo sp_admin::catForm($catID);
				}
				exit;
		}
		
		/**
		 * Creates a new smartpost category via an AJAX request.
		 * Requires $_POST variables 'cat_name'
		 */ 
		function newSPCatAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
				
				if( empty($_POST['cat_name']) ){
						echo '<div class="errors">The title field is empty!</div>';
				}else{
						
						$name = stripslashes_deep($_POST['cat_name']);
						$desc = stripslashes_deep($_POST['category_description']);
						
						//Cannot have empty name
						if(empty($name)){
									header("HTTP/1.0 409 " .  $icon_error);
									echo json_encode($responseArray);
									exit;							
						}
						
						//Validate icon upload
						if($_FILES['category_icon']['size'] > 0){
								if(sp_core::validImageUpload($_FILES, 'category_icon') && sp_core::validateIcon($_FILES['category_icon']['tmp_name'])){
									$description = $name . ' icon';
									$iconID = sp_core::upload($_FILES, 'category_icon', null, array('post_title' => $description, 'post_content' => $description));
								}else{
									$icon_error = 'File uploaded does not meet icon requirements.' .
																				  	' Please make sure the file uploaded is ' .
																							' 16x16 pixels and is a .png or .jpg file';

									header("HTTP/1.0 409 " .  $icon_error);
									echo json_encode(array('error' => $icon_error));
									exit;
								}
						}
						
						//Create a new category
						$sp_category = new sp_category($name, $desc);
						$sp_category->setIconID($iconID);
						
						//Check for any creation errors
						if(is_wp_error($sp_category->errors)){
							
							//delete the icon since something went wrong
							if(!empty($iconID)){
								wp_delete_attachment( $iconID, true );
							}
							
							header("HTTP/1.0 409 " .  $sp_category->errors->get_error_message());
							echo json_encode(array('error' => $sp_category->errors->get_error_message()));
							exit;
						}else{
							
							//Otherwise if everything checks out, return the new catID
							echo json_encode(array('catID' => $sp_category->getID()));
						}
				}

				exit;
		}		
		
		/**
		 * Renders HTML category settings
		 * @uses sp_admin::renderSPCatSettings()
		 */ 
		function renderSPCatSettingsAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				if( empty($_POST['catID']) ){
						echo '<div class="errors">Could not load category settings</div>';
				}else{
						$sp_category = new sp_category(null, null, $_POST['catID']);
						if(is_wp_error($sp_category->errors)){
							header("HTTP/1.0 409 " .  $sp_category->errors->get_error_message());
						}else{
							echo sp_admin::renderSPCatSettings($sp_category);
						}
				}
				exit;
		}
		
		/**
		 * Handles updating a SP Category via AJAX request.
		 * Requires $_POST variables 'catID' - the ID of the category.
		 */
		function updateSPCatAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
				
				if(empty($_POST['catID'])){
					header("HTTP/1.0 409 Could not find the category ID");
				}else{
					
					$catID = $_POST['catID'];
					$name  = $_POST['cat_name'];
					$desc  = $_POST['category_description'];					
					
					//Cannot have catID be empty
					if(empty($_POST['catID'])){
						header("HTTP/1.0 409 Could not find catID");
							if(!$xhr){
								echo '<textarea>' . json_encode(array('error' => "Could not find catID")) . '</textarea>';
							}
							exit;
					}
					
					//Cannot have cat_name be empty
					if(empty($_POST['cat_name'])){
						header("HTTP/1.0 409 Please fill in the category name");
							if(!$xhr){
								echo '<textarea>' . json_encode(array('error' => "Please fill in the category name")) . '</textarea>';
							}
							exit;
					}
					
					//Validate icon upload
					if($_FILES['category_icon']['size'] > 0){
						if(sp_core::validImageUpload($_FILES, 'category_icon') && sp_core::validateIcon($_FILES['category_icon']['tmp_name'])){
							$description = $name . ' icon';
							$iconID = sp_core::upload($_FILES, 'category_icon', null, array('post_title' => $description, 'post_content' => $description));
						}else{
							$icon_error = 'File uploaded does not meet icon requirements.' .
																					' Please make sure the file uploaded is ' .
																					' 16x16 pixels and is a .png or .jpg file';
							if(!$xhr){
								echo '<textarea>' . json_encode(array('error' => $icon_error)) . '</textarea>';
							}
							exit;									
						}
					}
					
					//If everything checks out, update the cateogry
					$sp_category = new sp_category(null, null, $catID);
					$sp_category->setTitle($name);
					$sp_category->setDescription($desc);
					if(!empty($iconID)){
							$sp_category->setIconID($iconID);					
					}
					
					//Check for any update errors
					if(is_wp_error($sp_category->errors)){
							header("HTTP/1.0 409 " .  $sp_category->errors->get_error_message());
							if(!$xhr){
								echo '<textarea>' . json_encode(array('error' => $sp_category->errors->get_error_message())) . '</textarea>';
							}
							exit;							
					}					
					
					//Delete the icon if it's checked off
					if((bool) $_POST['deleteIcon']){
						$sp_category->deleteIcon();
					}
					
					//Return catID if everythign was succesfull!
					if(!$xhr){
								echo '<textarea>' . json_encode(array('catID' => $sp_category->getID())) . '</textarea>';
					}
				}
				exit;
		}

		/**
		 * Updates a SP Category's response categories via an AJAX request.
		 * Requires $_POST variables 'catID' - the category being updated.
		 */
		function responseCatAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				if(empty($_POST['catID'])){
					header("HTTP/1.0 409 Could not find the category ID");
					exit;
				}
					
				$catID = $_POST['catID'];
				
				//If everything checks out, update the cateogry
				$sp_category = new sp_category(null, null, $catID);
				
				//Check for any update errors
				if(is_wp_error($sp_category->errors)){
						header("HTTP/1.0 409 " .  $sp_category->errors->get_error_message());
						exit;							
				}					
				
				$success = $sp_category->setResponseCats($_POST['responseCats']);
				
				if($success === false){
					header("HTTP/1.0 409 Could not update response categories.");
				}
				
				//Return catID if everything was successful!
				if(!$xhr){
						echo json_encode(array('catID' => $sp_category->getID()));
				}
				exit;
		}
	
	}
}
?>