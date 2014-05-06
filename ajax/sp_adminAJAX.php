<?php
if (!class_exists("sp_adminAJAX")) {
    class sp_adminAJAX{

        /**
         * Called on plugin initialization. Adds necessary action hooks to handle
         * AJAX requests.
         */
        static function init(){
            add_action('wp_ajax_new_sp_cat_ajax', array('sp_adminAJAX', 'new_sp_cat_ajax'));
            add_action('wp_ajax_switch_category_ajax', array('sp_adminAJAX', 'switch_category_ajax'));
            add_action('wp_ajax_set_comp_order_ajax', array('sp_adminAJAX', 'set_comp_order_ajax'));
            add_action('wp_ajax_get_category_json_tree_ajax', array('sp_adminAJAX', 'get_category_json_tree_ajax'));
            add_action('wp_ajax_delete_template_ajax', array('sp_adminAJAX', 'delete_template_ajax'));
        }

        /**
         * AJAX handler for deleting SmartPost templates
         */
        function delete_template_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['catID']) ){
                header("HTTP/1.0 409 Could not find catID.");
                exit;
            }

            $catID = (int) $_POST['catID'];

            $success = wp_delete_category($catID);

            if($success !== false || !is_wp_error($success) || $success !== 0){
                echo json_encode( array('success' => true) );
            }else if( is_wp_error($success) ){
                header( "HTTP/1.0 409 Error: " . $success->get_error_message() );
            }else if( $success === false ){
                header( "HTTP/1.0 409 Error: A category with an ID of " . $catID . " does not exist!" );
            }else if( $success === 0){
                header( "HTTP/1.0 409 Error: attempted to delete the default category!" );
            }else{
                header( "HTTP/1.0 409 A very bad error occurred. Data dump:" . print_r($success, true) );
            }
            exit;
        }

        /**
         * AJAX handler function that echos properly formatted JSON representing
         * SP Templates and their components.
         */
        function get_category_json_tree_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            $parent = 0;
            if( !empty( $_POST['parent'] ) )
                $parent = (int) $_POST['parent'];

            $includeParent = false;
            if( !empty( $_POST['includeParent'] ) )
                $includeParent = $_POST['includeParent'];

            $dynaTree = sp_admin::build_sp_dynatree( array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 ), $parent, $includeParent );

            echo json_encode($dynaTree);

            exit;
        }

        /**
         * "Enables" a wordpress category, or "disables" a SP category.
         * If the catID is that of a SP category, it will be removed from the
         * from global WP option 'sp_categories', otherwise it will add it.
         */
        function switch_category_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
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
         * Creates a new smartpost category via an AJAX request.
         * Requires $_POST variables 'cat_name'
         */
        function new_sp_cat_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
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

                // Add the category to the provided menu
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

                // Check if we should add the SP-QP widget
                $add_widget = (bool) $_POST['add_widget'];
                if( $add_widget ){

                    //Grab the sidebar ID
                    $sidebar_id = $_POST['widget_areas'];

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

        /**
         * Sets the component order for a category template in the admin page.
         */
        function set_comp_order_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
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
        } //end set_comp_order_ajax()
    }
}