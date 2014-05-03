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
            add_action('wp_ajax_copyComponentAJAX', array('sp_catComponentAJAX', 'copyComponentAJAX'));
            add_action('wp_ajax_saveCatCompTitleAJAX', array('sp_catComponentAJAX', 'saveCatCompTitleAJAX'));
            add_action('wp_ajax_copyTemplateAJAX', array('sp_catComponentAJAX', 'copyTemplateAJAX'));
        }

        /**
         * Given a catID and a compID, copies the component represented by compID
         * to the category represented by catID.
         */
        function copyComponentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could not find component ID.");
                exit;
            }

            if( empty($_POST['catID']) ){
                header("HTTP/1.0 409 Could not find category ID.");
                exit;
            }

            $catID  = (int) $_POST['catID'];
            $compID = (int) $_POST['compID'];
            $type   = 'sp_cat' . (string) sp_catComponent::getCompTypeFromID($compID);
            $comp = new $type($compID);
            $sp_category = new sp_category(null, null, $catID);

            $copy = $sp_category->addCatComponent(
                        $comp->getName(), $comp->getDescription(), $comp->getTypeID(), $comp->getDefault(), $comp->getRequired());

            $copy->setOptions($comp->getOptions());
            $copy->setIcon($comp->getIconID());

            if( $copy === false || is_wp_error($copy) ){
                header("HTTP/1.0 409 Could not copy component.");
                echo 'Could not copy component successfully.';
            }else{
                $copy->render();
                do_meta_boxes('toplevel_page_smartpost', 'normal', null);
            }
            exit;
        }

        function copyTemplateAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['srcCatID']) ){
                header("HTTP/1.0 409 Could not find component ID.");
                exit;
            }

            if( empty($_POST['destCatID']) ){
                header("HTTP/1.0 409 Could not find category ID.");
                exit;
            }

            $srcCatID  = (int) $_POST['srcCatID'];
            $destCatID = (int) $_POST['destCatID'];

            $srcCategory  = new sp_category(null, null, $srcCatID);
            $destCategory = new sp_category(null, null, $destCatID);

            $srcComps = $srcCategory->getComponents();
            foreach($srcComps as $comp){
                $copy = $destCategory->addCatComponent(
                            $comp->getName(),
                            $comp->getDescription(),
                            $comp->getTypeID(),
                            $comp->getDefault(),
                            $comp->getRequired()
                        );

                $copy->setOptions($comp->getOptions());
                $copy->setIcon($comp->getIconID());

                if( $copy === false || is_wp_error($copy) ){
                    header("HTTP/1.0 409 Could not copy component.");
                    echo 'Could not copy component ' . $copy->getName() . ' successfully.';
                    exit;
                }else{
                    $copy->render();
               }
            }
            do_meta_boxes('toplevel_page_smartpost', 'normal', null);
            exit;
        }

        /**
         * AJAX handler for deleting category components. Given a component ID
         * (retrieved via $_POST['compID']), deletes that component.
         */
        function deleteComponentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID to udpate.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $type   = (string) sp_catComponent::getCompTypeFromID($compID);
            if(empty($type)){
                header("HTTP/1.0 409 Could not find correct component type.");
                echo json_encode(array('error' => 'Could not find correct component type.'));
                exit;
            }
            $class  = 'sp_cat' . $type;
            $component = new $class($compID);

            //remove meta box toggle tracking
            $closed_meta_boxes = get_user_option( 'closedpostboxes_toplevel_page_smartpost' );
            $compElemID = $component->getCompType() . '-' . $component->getID();
            $key = array_search($compElemID, $closed_meta_boxes);
            if($key !== false && is_array( $closed_meta_boxes ) ){
                unset($closed_meta_boxes[$key]);
            }
            update_user_option( get_current_user_id(), 'closedpostboxes_toplevel_page_smartpost', $closed_meta_boxes, true);

            $success = $component->delete();
            if( $success === false ){
                header("HTTP/1.0 409 Could not delete component");
                echo json_encode(array('error' => 'Could not delete component'));
            }else{

                echo json_encode(array('success' => true));
            }

            exit;
        }

        /**
         * AJAX handler for updating component options. Given a compID
         * representing a component, updates any changes made to the
         * component's options.
         */
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

            $compID		  = (int) $_POST['compID'];
            $updateAction = (string) $_POST['updateAction'];
            $value     	  = $_POST['value'];
            $type      	  = 'sp_cat' . sp_catComponent::getCompTypeFromID($compID);

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



        /**
         * Adds a new $component object to $sp_category based off of the $catID
         */
        function newComponentAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            $catID 	     = (int) $_POST['catID'];
            $typeID      = (int) $_POST['typeID'];
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
                $name = sp_core::getTypeName($typeID);
            }

            //Upload Component Icon
            if($_FILES['componentIcon']['size'] > 0){
                if(sp_core::validImageUpload($_FILES, 'componentIcon') && sp_core::validateIcon($_FILES['componentIcon']['tmp_name'])){
                    $iconDescription = $name . ' Component icon';
                    $iconID = sp_core::upload('componentIcon', null, $iconDescription);
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
            $component   = $sp_category->addCatComponent($name, $description,
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
            $component->render();
            do_meta_boxes('toplevel_page_smartpost', 'normal', null);
            exit;
        }

        function loadCompOptionsAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if(empty($_POST['compID']) || empty($_POST['typeID'])){
                header("HTTP/1.0 409 Could not retrieve category ID and/or component ID");
            }else{
                $compID = (int) $_POST['compID'];
                $typeID = (int) $_POST['typeID'];
                $catID  = (int) $_POST['catID'];
                $type   = 'sp_cat' . sp_core::getTypeName($typeID);
                $component = new $type($compID);
                $component->componentOptions();
            }
            exit;
        }

        function loadCompSettingsAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if(empty($_POST['compID']) || empty($_POST['typeID'])){
                header("HTTP/1.0 409 Could not retrieve category ID and/or component ID");
            }else{
                $compID = (int) $_POST['compID'];
                $typeID = (int) $_POST['typeID'];
                $type   = 'sp_cat' . sp_core::getTypeName($typeID);
                $component = new $type($compID);
                sp_admin::loadCompForm($component->getCatID(), $component);
            }
            exit;
        }

        /**
         * AJAX handler for saving the title of a component represented by $_POST['compID'].
         */
        function saveCatCompTitleAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if( empty($_POST['compID']) ){
                header("HTTP/1.0 409 Could find component ID.");
                exit;
            }

            $compID = (int) $_POST['compID'];
            $title  = (string) stripslashes_deep($_POST['title']);
            $type   = sp_catComponent::getCompTypeFromID($compID);
            $class  = 'sp_cat' . $type;

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

    }
}