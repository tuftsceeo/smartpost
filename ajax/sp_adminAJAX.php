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
            add_action('wp_ajax_setCompOrderAJAX', array('sp_adminAJAX', 'setCompOrderAJAX'));
            add_action('wp_ajax_getCategoryJSONTreeAJAX', array('sp_adminAJAX', 'getCategoryJSONTreeAJAX'));
        }

        /**
         * AJAX handler function that echos properly formatted JSON representing
         * SP Templates and their components.
         */
        function getCategoryJSONTreeAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_admin_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            $parent = 0;
            if( !empty( $_POST['parent'] ) )
                $parent = (int) $_POST['parent'];

            $includeParent = false;
            if( !empty( $_POST['includeParent'] ) )
                $includeParent = $_POST['includeParent'];

            $dynaTree = sp_admin::buildSPDynaTree( array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 ), $parent, $includeParent );

            echo json_encode($dynaTree);

            exit;
        }

        /**
         * "Enables" a wordpress category, or "disables" a SP category.
         * If the catID is that of a SP category, it will be removed from the
         * from global WP option 'sp_categories', otherwise it will add it.
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

            $catID   = (int) $_POST['catID'];
            $sp_categories = get_option('sp_categories');

            if( !in_array($catID, $sp_categories) ){
                array_push($sp_categories, $catID);
                update_option('sp_categories', $sp_categories);
            }else{
                $key = array_search($catID, $sp_categories);
                if( $key !== false){
                    unset( $sp_categories[$key] );
                    update_option('sp_categories', $sp_categories);
                }else{
                    header("HTTP/1.0 409 Could not find the category ID to disable.");
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

            $name = stripslashes_deep($_POST['template_name']);
            $desc = $_POST['template_desc'];

            //Cannot have empty name
            if(empty($name)){
                header("HTTP/1.0 409 Error: empty name template name.");
                exit;
            }

            //Create a new template
            $sp_category = new sp_category($name, $desc);

            //Check for any creation errors
            if(is_wp_error($sp_category->errors)){
                header("HTTP/1.0 409 " . $sp_category->errors->get_error_message());
                echo json_encode(array('error' => $sp_category->errors->get_error_message()));
                exit;
            }else{

                $add_to_menu = (bool) $_POST['add_to_menu'];

                if( $add_to_menu ){

                    $menu_id = (int) $_POST['wp_menus'];
                    if(!empty($menu_id)){
                        wp_update_nav_menu_item($menu_id, 0, array(
                            'menu-item-title'  =>  __( $sp_category->getTitle() ),
                            'menu-item-url'    => get_category_link( $sp_category->getID() ),
                            'menu-item-status' => 'publish')
                        );
                    }
                }

                $add_widget = (bool) $_POST['add_widget'];
                if( $add_widget ){

                    //Grab the sidebar ID
                    $sidebar_id = $_POST['widget_areas'];
                    error_log( $sidebar_id );

                    //Get active widgets and active SP quickpost widget instances
                    $sidebars_instances  = get_option( 'sidebars_widgets' );
                    $sp_widget_instances = get_option( 'widget_sp_quickpostwidget' );

                    //Get the new widget instance ID
                    $new_widget_id = max( array_keys($sp_widget_instances) ) + 1;
                    $new_widget_name = 'sp_quickpostwidget-' . $new_widget_id;

                    //Update WP widget instances
                    $sp_widget_instances[$new_widget_id]['categoryMode'] = "on";
                    update_option( 'widget_sp_quickpostwidget', $sp_widget_instances);

                    //Add the new widget to the selected side bar
                    array_push( $sidebars_instances[ $sidebar_id ], $new_widget_name );
                    update_option( 'sidebars_widgets', $sidebars_instances);
                }

                //Otherwise if everything checks out, return the new catID
                echo json_encode( array( 'catID' => $sp_category->getID() ) );
            }

            exit;
        }

        /*
         * Renders HTML category settings
         * @uses sp_admin::renderCatSettings()
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
                    echo sp_admin::renderCatSettings($sp_category);
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
            echo json_encode( array('catID' => $sp_category->getID()) );

            exit;
        }

        /**
         * Sets the component order for a category template in the admin page.
         */
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
            $catID 		 = (int) $_POST['catID'];
            $sp_category = new sp_category(null, null, $catID);

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