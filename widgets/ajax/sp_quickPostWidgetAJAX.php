<?php
if (!class_exists("sp_quickPostWidgetAJAX")) {
	class sp_quickPostWidgetAJAX{
		
		static function init(){
				add_action('wp_ajax_newSPDraftAJAX', array('sp_quickPostWidgetAJAX', 'newSPDraftAJAX'));
				add_action('wp_ajax_publishPostAJAX', array('sp_quickPostWidgetAJAX', 'publishPostAJAX'));
				add_action('wp_ajax_deleteQPPostAJAX', array('sp_quickPostWidgetAJAX', 'deleteQPPostAJAX'));
				add_action('wp_ajax_loadResponsePostsAJAX', array('sp_quickPostWidgetAJAX', 'loadResponsePostsAJAX'));	
		}
		
		/**
		 * Create a new draft
		 */
		function newSPDraftAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['catID'] )){
                header("HTTP/1.0 409 Could not find catID.");
                exit;
            }

            if( empty( $_POST['widgetID'] ) ){
                header("HTTP/1.0 409 Could not find widgetID.");
                exit;
            }

            $widget_id = $_POST['widgetID'];

            if( !empty( $_POST['parentID'] ) ){
                $post['post_parent'] = (int) $_POST['parentID'];
            }

            global $current_user;

            // Create a new blank draft
            $post['post_status'] = 'draft';
            $post['post_title']  = 'New draft ' . date('d-m-y');
            $post['post_author'] = $current_user->ID;
            $post['comment_status'] = 'open';
            $post['post_content'] = '[sp-components][/sp-components]';

            // Set the category of the post
            $catID = (int) $_POST['catID'];
            $post['post_category'] = array( $catID );

           // Create the draft post
            $id = wp_insert_post($post, true);
            if( is_wp_error($id) ){
                header("HTTP/1.0 409 " . $id->get_error_message());
                exit;
            }

            // Add any default/required components
            $post_comps = sp_post::get_components_from_ID($id);
            $html = '<div id="sp-components-' . $widget_id . '" class="sortableSPComponents quickPostComps">';
            if( !empty( $post_comps ) ){
                global $wp_query;
                $wp_query->is_single = true;
                $_GET['edit_mode'] = true;
                foreach( $post_comps as $postComp ){
                    $html .= $postComp->render();
                }
                $wp_query->is_single = false;
            }
            $html .= '</div>';

            // Add component buttons
            $sp_cat = new sp_category(null, null, $catID);
            $sp_catComps = $sp_cat->getComponents();
            if( !empty($sp_catComps) ){
                foreach($sp_catComps as $component){

                    $catCompID = $component->getID();
                    $typeID = $component->getTypeID();
                    $desc = $component->getDescription();
                    $icon = $component->getIcon();
                    $icon_img = "";
                    if( !empty($icon) ){
                        $icon_img = '<img src="' . $component->getIcon() . '" />';
                    }

                    $html .= '<span id="' . $catCompID . '" class="sp-qp-component" data-compid="' . $catCompID . '" data-typeid="' . $typeID . '" title="' . $desc . '" alt="' . $desc . '">';
                    $html .= 	$icon_img . ' Add ' . $component->getName();
                    $html .= '</span> ';
                }
                $html .= '<div class="clear"></div>';
            }

            // Add the post ID
            $html .= '<input type="hidden" id="sp-qp-post-id-' . $widget_id . '" name="sp-qp-post-id-' . $widget_id . '" value="' . $id . '" />';
            echo $html;
            exit;
		}
		
		/**
		 * Delete the draft if "Cancel Draft" was clicked
		 */		
		function deleteQPPostAJAX(){
			$nonce = $_POST['nonce'];
			if( !wp_verify_nonce($nonce, 'sp_nonce') ){
				header("HTTP/1.0 409 Security Check.");
				die('Security Check');
			}
			
			if( empty($_POST['ID']) ){
				header("HTTP/1.0 409 could not find postID");
				exit;
			}
			
			$postID = (int) $_POST['ID'];

			$success =	wp_delete_post($postID, true);			
			if($success === false){
				header("HTTP/1.0 409 could not delete post.");
				exit;			
			}
			echo json_encode( array('success' => true ) );
			
			exit;
		}
		
		/**
		 * Publish the post
		 */
		function publishPostAJAX(){
			$nonce = $_POST['nonce'];
			if( !wp_verify_nonce($nonce, 'sp_nonce') ){
				header("HTTP/1.0 409 Security Check.");
				die('Security Check');
			}
			
			if( empty($_POST['ID']) ){
				header("HTTP/1.0 409 could not find postID");
				exit;
			}
			
			if( empty($_POST['post_title']) ){
				header("HTTP/1.0 409 please fill in the post title!");
				exit;
			}
			
			$post['ID'] = (int) $_POST['ID'];
			$post['post_title']  = (string) $_POST['post_title'];
			$post['post_status'] = 'publish';
			
			$success = wp_update_post($post);
			
			if($success === 0){
				header("HTTP/1.0 409 could update post successfully.");
				exit;
			}
			
			echo json_encode( array( 'success' => true, 'postID' => $post['ID'], 'permalink' => get_permalink( $post['ID'] ) ) );
			exit;
		}
		
	}
}
?>