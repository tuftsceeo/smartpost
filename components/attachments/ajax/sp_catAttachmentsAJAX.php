<?php
/*
 * AJAX-Related functions for all
 * sp_postAttachments components. Functions are used
 * in dashboard/admin section of the site.
 */

if (!class_exists("sp_catAttachmentsAJAX")) {
	class sp_catAttachmentsAJAX{
		
		static function init(){
			add_action('wp_ajax_saveAttachmentsSettingsAJAX', array('sp_catAttachmentsAJAX', 'saveAttachmentsSettingsAJAX'));
		}

        /**
         * Saves extensions to the component.
         */
        static function saveAttachmentsSettingsAJAX(){

            $nonce = $_POST['nonce'];
			if( !wp_verify_nonce($nonce, 'sp_nonce') ){
				header("HTTP/1.0 403 Security Check.");
				exit;
			}

			if( empty($_POST['compID']) ){
				header("HTTP/1.0 403 Could not find componentID to update.");
				exit;			
			}
			
			$compID = (int) $_POST['compID'];
			$attachmentComp = new sp_catAttachments($compID);
			
			if(is_wp_error($attachmentComp->errors)){
				header( 'HTTP/1.0 403 ' . $attachmentComp->errors->get_error_message() );
				exit;
			}

            $allowedExts = trim( stripslashes_deep( $_POST['allowedExts'] ) );

            if( !empty($allowedExts) ){
                $allowedExts = explode(",", $_POST['allowedExts']);
                //Cleanup extensions
                foreach($allowedExts as $index => $ext){
                    $ext = trim($ext);
                    $allowedExts[$index] = $ext;
                }
            }else{
                $allowedExts = array();
            }
			$success = $attachmentComp->setOptions($allowedExts);
			if($success === false){
				header( 'HTTP/1.0 403 Could not update attachment component succesfully' );
				exit;
            }

            echo json_encode(array('success' => true));
			exit;
		}
		
	}
}