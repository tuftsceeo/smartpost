<?php
/*
 * AJAX-Related functions for all
 * sp_postLink components. Functions are used
 * in front end posts.
 * @version 2.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 */

if (!class_exists("sp_postLinkAJAX")) {
	class sp_postLinkAJAX{
	
		static function init(){				
			add_action('wp_ajax_saveLinkAJAX', array('sp_postLinkAJAX', 'saveLinkAJAX'));
			add_action('wp_ajax_saveLinkDescAJAX', array('sp_postLinkAJAX', 'saveLinkDescAJAX'));
			add_action('wp_ajax_saveLinkThumbAJAX', array('sp_postLinkAJAX', 'saveLinkThumbAJAX'));
			add_action('wp_ajax_removeLinkThumbAJAX', array('sp_postLinkAJAX', 'removeLinkThumbAJAX'));
			add_action('wp_ajax_saveCustomLinkThumbAJAX', array('sp_postLinkAJAX', 'saveCustomLinkThumbAJAX'));
		}

        function removeLinkThumbAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postLink')){
                header("HTTP/1.0 409 Could not instantiate sp_postLink class.");
                echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID  = (int) $_POST['compID'];

            $linkComponent = new sp_postLink($compID);
            $linkComponent->removeThumb();

            echo json_encode( array('success' => true) );
            exit;
		}
		
		function saveLinkAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( !class_exists('sp_postLink') ){
                    header("HTTP/1.0 409 Could not instantiate sp_postLink class.");
                    echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $link 	= (string) stripslashes_deep($_POST['link']);
            $linkComponent = new sp_postLink($compID);
            $success = $linkComponent->setLink($link);

            if( is_wp_error( $linkComponent->errors ) ){
                header( "HTTP/1.0 409 " . $success->get_error_message() );
                exit;
            }

            echo $linkComponent->renderEditMode();
            exit;
		}

        /**
         * Save link description
         */
        function saveLinkDescAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if(!class_exists('sp_postLink')){
                header("HTTP/1.0 409 Could not instantiate sp_postLink class.");
                echo json_encode(array('error' => 'Could not save link.'));
            }

            if( empty($_POST['compid']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID	= (int) $_POST['compid'];
            $desc	= (string) stripslashes_deep( $_POST['content'] );
            $linkComponent = new sp_postLink($compID);

            if( is_wp_error( $linkComponent->errors ) ){
                header( "HTTP/1.0 409 " . $linkComponent->errors->get_error_message() );
            }else{

                $linkComponent->setUrlDesc($desc);
                $success = $linkComponent->update();

                if($success === false){
                    header("HTTP/1.0 409 Could not save link description.");
                }else{
                    echo json_encode(array('success' => true));
                }
            }
            exit;
		}
		
		function saveCustomLinkThumbAJAX(){
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}
				
				if(!class_exists('sp_postLink')){
						header("HTTP/1.0 409 Could not instantiate sp_postLink class.");
						echo json_encode(array('error' => 'Could not save link.'));						
				}				
				
				if( empty($_POST['postID']) ){
					header("HTTP/1.0 409 Could not find post ID to udpate.");
					exit;
				}
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could not find component ID to udpate.");
					exit;					
				}
				
				$postID = (int) $_POST['postID'];
				$compID = (int) $_POST['compID'];
				$linkComponent = new sp_postLink($compID);
				
				// remove existing thumb if one exists
				$linkComponent->removeThumb();
				
				$id = sp_core::upload('sp_link_thumb', $postID, array('post_title' => $_FILE['sp_link_thumb']['name']));
				if(is_wp_error($id)){
					header("HTTP/1.0 409 Could not successfully upload file, " . $id->get_error_message());
					exit;		
				}else{
					$linkComponent->setUrlThumb($id);
					$linkComponent->update(0);
					echo json_encode( array('id' => $id, 'url' => $linkComponent->getUrl(), 'thumb' => wp_get_attachment_image($id, array(100, 100), false)) );
				}
				exit;
		}
		
		function saveLinkThumbAJAX(){
		
				$nonce = $_POST['nonce'];
				if( !wp_verify_nonce($nonce, 'sp_nonce') ){
					header("HTTP/1.0 403 Security Check.");
					die('Security Check');
				}
				
				if(!class_exists('sp_postLink')){
						header("HTTP/1.0 409 Could not instantiate sp_postLink class.");
						echo json_encode(array('error' => 'Could not save link.'));						
				}				
				
				if( empty($_POST['compID']) ){
					header("HTTP/1.0 409 Could find component ID to udpate.");
					exit;					
				}
	
				$compID = (int) $_POST['compID'];
				$thumb	= (string) stripslashes_deep($_POST['thumb']);
				$linkComponent = new sp_postLink($compID);

				if( is_wp_error($linkComponent->errors) ){
					header("HTTP/1.0 409 " . $linkComponent->errors->get_error_message());
				}else{
					
					$linkComponent->removeThumb();
					$attachmentID = $linkComponent->downloadUrlThumb($thumb);

					if($attachmentID === false){
						header("HTTP/1.0 409 Could not save link thumbnail.");
					}else{
						$linkComponent->setUrlThumb($attachmentID);
						$linkComponent->update(0);
						echo json_encode(array('success' => true));
					}
					
				}
				exit;
		}		

	}	
}
?>