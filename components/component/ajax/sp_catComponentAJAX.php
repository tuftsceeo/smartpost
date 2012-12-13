<?php
/*
 * AJAX-Related functions for all
 * Category components. Functions are used
 * in the Settings page of the SmartPost plugin
 */

if (!class_exists("sp_catComponentAJAX")) {
	class sp_catComponentAJAX{
	
			static function init(){	
				add_action('wp_ajax_newComponentAJAX', array('sp_catComponentAJAX', 'newComponentAJAX'));
				add_action('wp_ajax_loadCompOptionsAJAX', array('sp_catComponentAJAX', 'loadCompOptionsAJAX'));
				add_action('wp_ajax_loadCompSettingsAJAX', array('sp_catComponentAJAX', 'loadCompSettingsAJAX'));
				add_action('wp_ajax_updateSettingsAJAX', array('sp_catComponentAJAX', 'updateSettingsAJAX'));
				add_action('wp_ajax_deleteComponentAJAX', array('sp_catComponentAJAX', 'deleteComponentAJAX'));
				add_action('wp_ajax_setCompOrderAJAX', array('sp_catComponentAJAX', 'setCompOrderAJAX'));
			}
		
		function deleteComponentAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					header("HTTP/1.0 409 Security Check.");
					die('Security Check');
				}
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;					
				}

				$compID = (int) $_POST['compID'];
				$type   = 'sp_cat' . (string) sp_catComponent::getCompTypeFromID($compID);
				
				$component = new $type($compID);
				$success = $component->delete();
				if( $success === false ){
					header("HTTP/1.0 409 Could not delete component");
					echo json_encode(array('error' => 'Could not delete component'));
				}else{
					echo json_encode(array('success' => true));
				}
				
				exit;
		}
			
		function updateSettingsAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;					
				}else if( empty($_POST['updateAction']) ){
					header("HTTP/1.0 409 No update action provided.");
					exit;
				}

				$compID    			   = (int) $_POST['compID'];
				$updateAction    = (string) $_POST['updateAction'];
				$value     			   = $_POST['value'];
				$type      			   = 'sp_cat' . sp_catComponent::getCompTypeFromID($compID);
				
				if(!class_exists($type)){
					header("HTTP/1.0 409 Could not find class " . $type);
					exit;
				}
				
				$component = new $type($compID);
				$success = $component->$updateAction($value);
				if( $success === false ){
					header("HTTP/1.0 409 Could not update component");
					echo json_encode(array('error' => 'Could not update component'));
				}else{
					echo json_encode(array('success' => true));
				}
				exit;
		}
		
		//Adds a new $component object to $sp_category based off of the $catID
		function newComponentAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				$xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
				
				$catID 	     = (int) $_POST['catID'];
				$typeID      = (int) $_POST['sp_compTypes'];
				$name        = (string) stripslashes_deep($_POST['compName']);
				$description = (string) stripslashes_deep($_POST['compDescription']);
				$isDefault   = (bool) $_POST['isDefault'];
				$isRequired  = (bool) $_POST['isRequired'];
				
				//Cannot have a component that's required but not default
				if($isRequired && !$isDefault){
					$isDefault = true;
				}
				
				//Cannot have catID or typeID be empty
				if(empty($catID) || empty($typeID)){
					header("HTTP/1.0 409 Could not retrieve category ID and/or component typeID");
					echo json_encode(array('error' => 'Could not retrieve category ID and/or component typeID'));
					exit;
				}
				
				//If name not given, go with default component type
				if(empty($name)){
					$name = sp_core::getType($typeID);
				}				

				//Upload Component Icon
				if($_FILES['componentIcon']['size'] > 0){
					if(sp_core::validImageUpload($_FILES, 'componentIcon') && sp_core::validateIcon($_FILES['componentIcon']['tmp_name'])){
						$iconDescription = $name . ' Component icon';
						$iconID = sp_core::upload($_FILES, 'componentIcon', null, $iconDescription);
					}else{
						$icon_error = 'File uploaded does not meet icon requirements.' .
																																							' Please make sure the file uploaded is ' .
																																							' 16x16 pixels and is a .png or .jpg file';
						echo json_encode(array('error' => $icon_error));
						exit;
					}
			 }

				//Try and create new component
				$sp_category = new sp_category(null, null, $catID);
				$component = $sp_category->addCatComponent($name, $description, 
																																																	$typeID, $isDefault, $isRequired);

				if(is_wp_error($component->errors) || is_wp_error($component)){
					
					if(!is_wp_error($component->errors)){
						$errors = $component;
					}else{
						$errors = $components->errors;
					}
					
					//Delete attachment since something went wrong
					if(!empty($iconID)){
						wp_delete_attachment( $iconID, true );
					}
					
					header("HTTP/1.0 409 " .  $errors->get_error_message());
					echo json_encode(array('error' => $errors->get_error_message()));
					exit;
				}

				$component->setIcon($iconID);
				echo json_encode(array('success' => true));
				exit;
		}

		function loadCompOptionsAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				if(empty($_POST['compID']) || empty($_POST['typeID'])){
					header("HTTP/1.0 409 Could not retrieve category ID and/or component ID");					
				}else{
					$compID = (int) $_POST['compID'];
					$typeID = (int) $_POST['typeID'];
					$catID  = (int) $_POST['catID'];
					$type   = 'sp_cat' . sp_core::getType($typeID);
					$component = new $type($compID);
					$component->componentOptions();
				}
				exit;
		}

		function loadCompSettingsAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				if(empty($_POST['compID']) || empty($_POST['typeID'])){
					header("HTTP/1.0 409 Could not retrieve category ID and/or component ID");					
				}else{
					$compID = (int) $_POST['compID'];
					$typeID = (int) $_POST['typeID'];
					$catID  = (int) $_POST['catID'];
					$type   = 'sp_cat' . sp_core::getType($typeID);
					$component = new $type($compID);
					sp_admin::loadCompForm($component->getCatID(), $component);
				}
				exit;
		}
		
		function setCompOrderAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
					die('Security Check');
				}
				
				//Cannot have an empty catID
				if( empty($_POST['catID']) ){
					header("HTTP/1.0 409 Could find category ID to udpate.");
					exit;
				}

				//Cannot have an empty compOrder
				if( empty($_POST['compOrder']) ){
					header("HTTP/1.0 409 Could find component order info.");
					exit;				
				}
				
				//Initialize all data
				$compOrder   = $_POST['compOrder'];
				$catID 						= (int) $_POST['catID'];
				$sp_category =  new sp_category(null, null, $catID);
					
				//Check if the category loaded succesfully
				if(is_wp_error($sp_category->errors)){
					header("HTTP/1.0 409 Could not instantiate the category succesfully.");
					exit;
				}
				
				$success = $sp_category->setCompOrder($compOrder);
					
				if( is_wp_error($success) ){
					header("HTTP/1.0 409 " . $success->get_error_message());
					exit;
				}
				
				echo json_encode(array('success' => true));
				exit;
		}
					
	}
}
?>