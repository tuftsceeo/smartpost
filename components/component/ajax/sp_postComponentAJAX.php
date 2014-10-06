<?php
if (!class_exists("sp_postComponentAJAX")) {

/**
 * Handles all AJAX actions for the sp_postComponent class
 * 
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost
 */

    class sp_postComponentAJAX{
	
        static function init(){
            add_action('wp_ajax_newPostComponentAJAX', array('sp_postComponentAJAX', 'newPostComponentAJAX'));
            add_action('wp_ajax_deletePostComponentAJAX', array('sp_postComponentAJAX', 'deletePostComponentAJAX'));
            add_action('wp_ajax_setPostCompOrderAJAX', array('sp_postComponentAJAX', 'setPostCompOrderAJAX'));
            add_action('wp_ajax_saveCompTitleAJAX', array('sp_postComponentAJAX', 'saveCompTitleAJAX'));
        }
		
		function newPostComponentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security check.");
                die('Security Check');
            }

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could find post ID.");
                exit;
            }

            if( empty($_POST['catCompID']) ){
                header("HTTP/1.0 409 Could find component ID to add.");
                exit;
            }

            $postID     = (int) $_POST['postID'];
            $catCompID  = (int) $_POST['catCompID'];
            $sp_post    = new sp_post($postID, true);

            if(is_wp_error($sp_post->errors)){
                header("HTTP/1.0 409 " . $sp_post->errors->get_error_message());
                exit;
            }

            $postCompID = $sp_post->add_component($catCompID);

            if(is_wp_error($postCompID)){
                header("HTTP/1.0 409 " . $postCompID->get_error_message());
                exit;
            }

            $postComponent = $sp_post->getComponentByID($postCompID);

            /**
             * Set globals is_single and $_GET['edit_mode'] to true in order to render
             * the component in the proper "mode"
             */
            global $wp_query;
            $_GET['edit_mode'] = true;
            $wp_query->is_single = true;

            echo $postComponent->render();
            exit;
		}
		
		
		function deletePostComponentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security check.");
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to delete.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $type   = 'sp_post' . (string) sp_postComponent::getCompTypeFromID($compID);

            if( !class_exists( $type ) ){
                header( 'HTTP/1.0 409 Error: class of type: "' . $type . '" does not exist!' );
                exit;
            }

            $component = new $type($compID);

            if($component->isRequired() && $component->lastOne()){
                header("HTTP/1.0 409 Unable to delete component. This component is required.");
                echo json_encode(array('error' => 'This component is required.'));
                exit;
            }
            $postID  = $component->getPostID();
            $success = $component->delete();
            if( $success === false ){
                header("HTTP/1.0 409 Could not delete component");
                echo json_encode(array('error' => 'Could not delete component'));
            }else{
                sp_post::saveShortcodes($postID);
                echo json_encode(array('success' => true));
            }
            exit;
		}
			
		function updateSettingsAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }else if( empty($_POST['updateAction']) ){
                header("HTTP/1.0 409 No update action provided.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $updateAction = (string) $_POST['updateAction'];
            $value = $_POST['value'];
            $type = 'sp_cat' . sp_catComponent::get_comp_type_from_id($compID);

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
		
		function saveCompTitleAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to delete.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $title  = (string) stripslashes_deep($_POST['title']);
            $type   = sp_postComponent::getCompTypeFromID($compID);
            $class  = 'sp_post' . $type;

            $component = new $class($compID);
            if(is_wp_error($component->errors)){
                header("HTTP/1.0 409 " . $component->errors->get_error_message());
                exit;
            }

            if($component->getName() == $title){
                echo json_encode( array('success' => true) );
                exit;
            }

            $success = $component->setName($title);
            if($success === false){
                header("HTTP/1.0 409 Could find component ID to delete.");
                exit;
            }

            echo json_encode( array('success' => true) );
            exit;
		}
		
		function setPostCompOrderAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            //Cannot have an empty postID
            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could find postID to udpate.");
                exit;
            }

            //Cannot have an empty compOrder
            if( empty($_POST['compOrder']) ){
                header("HTTP/1.0 409 Could find component order info.");
                exit;
            }

            //Initialize all data
            $compOrder  = $_POST['compOrder'];
            $postID 				= (int) $_POST['postID'];
            $sp_post    = new sp_post($postID, true);

            //Check if the category loaded succesfully
            if(is_wp_error($sp_category->errors)){
                header("HTTP/1.0 409 Could not instantiate the category succesfully.");
                exit;
            }

            $success = $sp_post->setCompOrder($compOrder);

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