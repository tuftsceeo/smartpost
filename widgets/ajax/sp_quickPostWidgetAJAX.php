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

            global $current_user;

            if( !empty($_POST['parentID']) ){
                $post['post_parent'] = (int) $_POST['parentID'];
            }

            //Create a new blank draft
            $post['post_status'] = 'draft';
            $post['post_title']  = 'New draft ' . date('d-m-y');
            $post['post_author'] = $current_user->ID;
            $post['comment_status'] = 'open';

            // Set the category of the post
            $catID = (int) $_POST['catID'];
            $post['post_category'] = array($catID);

            $id = wp_insert_post($post, true);

            if( is_wp_error($id) ){
                header("HTTP/1.0 409 " . $id->get_error_message());
                exit;
            }

            // Add any default/required components
            $postComps = sp_post::getComponentsFromID($id);
            $html = '';
            $html .= '<div id="spComponents" class="sortableSPComponents quickPostComps">';
            if( !empty( $postComps ) ){
                global $wp_query;
                $wp_query->is_single = true;
                $_GET['edit_mode'] = true;
                foreach( $postComps as $postComp ){
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
                    $desc   = $component->getDescription();
                    $icon   = $component->getIcon();

                    if( !empty($icon) ){
                        $icon_img = '<img src="' . $component->getIcon() . '" />';
                    }

                    $html .= '<span id="' . $$catCompID . '" data-compid="' . $catCompID . '" data-typeid="' . $typeID . '" title="' . $desc . '" alt="' . $desc . '" class="sp_qp_component">';
                    $html .= 	$icon_img . ' Add ' . $component->getName();
                    $html .= '</span> ';
                }
                $html .= '<div class="clear"></div>';
            }else{
                global $current_user;
                $admin = current_user_can('administrator');
                $html .= '<p> No components exist for this category! </p>';
                $html .= $admin ? '<p> Add new components <a href="' . admin_url() . '?page=smartpost">here</a>.</p>' : '<p> Please contact your site admins for new components. </p>';
            }

            //Add the post ID
            $html .= '<input type="hidden" id="sp_qpPostID" name="sp_qpPostID" value="' . $id . '" />';
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
		 * Load response posts
		 */
		function loadResponsePostsAJAX(){
			
			if( empty($_POST['ID']) ){
				header("HTTP/1.0 409 could not find postID");
				exit;
			}	

			$postID = (int) $_POST['ID'];
			
			//Get the category of the response post
			$sp_postCatID = sp_post::getSPCategory($postID);
			
			//Search all SP categories that have the response posts's category as one
			//of their response categories. i.e. find all SP categoriese we can respond to..
			$sp_categories = get_option('sp_categories');
			$responseCats = array();
			
			foreach($sp_categories as $catID){
				$sp_category  = new sp_category(null, null, $catID);
				$responseCatIDs = $sp_category->getResponseCats();
				
				if( !empty($responseCatIDs) ){
					if( (bool) $responseCatIDs[$sp_postCatID] );
					  array_push($responseCats, $sp_category);
				}
			}
			
			$post_filters['post_status']   = 'publish';

			foreach($responseCats as $responseCat){
				$post_filters['categories']  = array($responseCat->getID());
				$html .= $responseCat->renderPostTree('publish', 0, $post_filters);
			}
			
			if(class_exists('sp_postTreeWidget')){
				$postTree = new sp_postTreeWidget();
				$postTree->widget();
			}

			//echo $html;
			exit;
		}
		
		/**
		 * Publish the post (as a response if necessary)
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
			
			$post['ID'] 								 = (int) $_POST['ID'];
			$post['post_title']  = (string) $_POST['post_title'];
			$post['post_status'] = 'publish';
			
			$parentID = (int) $_POST['post_parent'];
			if( $parentID > 0 ){
				$post['post_parent'] = $parentID;
			}
			
			$success = wp_update_post($post);
			
			if($success === 0){
				header("HTTP/1.0 409 could update post successfully.");
				exit;
			}
			
			echo json_encode( array('success' => true, 'postID' => $post['ID']));
			exit;
		}
		
	}
}
?>