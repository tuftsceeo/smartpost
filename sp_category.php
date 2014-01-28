<?php
if (!class_exists("sp_category")) {
    /**
     * SP Category. Augments a WordPress category with extra features.
     */
    class sp_category{

        private $ID;
        private $title;
        private $description;
        private $catComponents = array(); //array of sp_catComponent objects
        private $responseCats  = array(); //array of ids of other categories array( [ID] => 1 );
        private $iconID;
        public 	$errors; //!TO-DO: make this an array of errors

        function __construct($title, $description, $catID = null){
            if( !is_null($catID) ){
                self::load($catID);
            }else{
                if( !empty($title) ){
                    $catID = wp_insert_category(array('cat_name'             => $title,
                                                      'category_description' => $description),
                                                true);
                    if(is_wp_error($catID)){
                        $this->errors = $catID;
                    }else{
                        $this->ID 		   = $catID;
                        $this->title 	   = $title;
                        $this->description = $description;
                        $sp_categories 	   = get_option('sp_categories');
                        if( empty($sp_categories) ){
                            $sp_categories = array();
                        }
                        array_push($sp_categories, $this->ID);
                        update_option('sp_categories', $sp_categories);
                    }
                }else{
                    $this->errors = new WP_Error('empty', __("Empty Parameter: $catID"));
                }
            }
        }

        /* Loads an existing category from the db - "overloads" the constructor
         * as PHP does not allow "true" constructor overloading
         */
        private function load($catID){
            global $wpdb;

            if( $catID > 0 ){
                $category = get_category($catID);
                if( is_wp_error($category) ){
                    $this->errors = new WP_Error('invalid', __("Invalid cat ID " . $catID));
                }else{
                    $sp_responseCats     = get_option('sp_responseCats');
                    $sp_cat_icons        = get_option('sp_cat_icons');
                    $this->ID 		   	 = $category->cat_ID;
                    $this->title		 = $category->name;
                    $this->description   = $category->category_description;
                    $this->catComponents = array();
                    $this->iconID		 = $sp_cat_icons[$catID];

                    if( !empty($sp_responseCats) )
                        $this->responseCats  = $sp_responseCats[$this->ID];

                    //load cat components
                    $sp_catComponentsTable = $wpdb->prefix . "sp_catComponents";
                    $componentResults 	   = $wpdb->get_results(
                        "SELECT * FROM $sp_catComponentsTable where catID = $catID order by compOrder ASC;");

                    if( !empty($componentResults) ){
                        foreach($componentResults as $componentRow){
                            $type = 'sp_cat' . sp_core::getTypeName($componentRow->typeID);
                            //if(class_exists($type)){
                                $component = new $type($componentRow->id);
                                array_push($this->catComponents, $component);
                            //}
                        }
                    }
                }
            }else{
                $this->errors = new WP_Error('empty', __("Empty Parameter: catID"));
            }
        }

        /**
         * Initializes scripts, actions, hooks, variables for the sp_category class.
         */
        function init(){
            add_action('delete_category', array('sp_category', 'deleteCategory'));
        }

        /**
         * Returns an array representing the category hierarchy.
         *
         * @param $args
         * @param int $parent
         * @return array
         */
        public static function get_cat_hierarchy($args, $parent = 0){

            $args['parent'] = $parent;

            $cats    = get_categories($args);
            $catTree = array();

            foreach( $cats as $cat ) {
                $cat->children = sp_category::get_cat_hierarchy($args, $cat->term_id);
                array_push($catTree, $cat);
            }

            return $catTree;
        }

        /**
         * Renders HTML <ul> tree of all the posts under the category
         *
         * @param string $post_status What types of posts to include in the tree: publish|draft|trash
         * @param int $post_parent The post_parent to search for in the query, default is 0 (i.e. no parent)
         * @param array $post_filters Filter params to pass down to the posts of the category (@see sp_post::renderTree)
         * @return string The ul tree of all the posts and their children
         */
        function renderPostTree($post_status = 'publish', $post_parent = 0, $post_filters = null ){
            $html  = '';
            $args  = array('numberposts' => -1,
                           'category'    => $this->ID,
                           'post_status' => $post_status,
                           'post_parent' => $post_parent
                           );
            $posts = get_posts( $args );
            $catIcon = wp_get_attachment_url($this->iconID, null, null,
                                             array('style' => 'vertical-align: text-bottom;'));

            if( !empty($catIcon) )
                $catIcon = 'icon: \'' . $catIcon . '\', ';

            $responses = $post_parent > 0 ? ' responses' : '';
            $html .= '<li class="folder" data="' . $catIcon . 'cat: true, catID: ' . $this->ID . '">' . $this->title . $responses;

            if(is_null($posts) || empty($posts))
                return $html;

            $html .= '<ul>';

            if( !empty($post_filters) )
                extract($post_filters); //will overwrite local vars of $post_status, $post_parent, etc

            foreach($posts as $post){
                $sp_post = new sp_post($post->ID, true);
                $html .= $sp_post->renderTree($post_status, NULL, NULL);
            }
            $html .= '</ul>';

            return $html;
        }

        /**
         * Renders the response categories and automatically
         * checks off the categories that are response categories
         *
         * Used in the administrative back-end
         */
        function renderResponseCatForm(){
            if(is_admin()){
                ?>
                <p>Check off the Response Categories for <b><?php echo $this->title ?>:</b></p>
                <form id="responseCatsForm" name="responseCatsForm" method="post">
                    <table class="widefat responseCatTable">
                        <thead>
                        <tr>
                            <th class="row-title">Category</th>
                            <th>Description</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sp_categories = get_option('sp_categories');
                        foreach($sp_categories as $sp_catID){
                            $sp_category = new sp_category(null, null, $sp_catID);
                            $checked = !empty($this->responseCats[$sp_catID]) ? 'checked="checked"' : '';
                            ?>
                            <tr>
                                <td class="row-title">
                                    <input type="checkbox" id="responseCats[<?php echo $sp_category->getID() ?>]" name="responseCats[<?php echo $sp_category->getID() ?>]" value="1" <?php echo $checked ?> />
                                    <label for="responseCats[<?php echo $sp_category->getID() ?>]"><?php echo $sp_category->getTitle(); ?></label>
                                </td>
                                <td><?php echo $sp_category->getDescription() ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th class="row-title"></th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                    <button type="submit" id="submitResponseCats" class="button-primary">Update Category</button>
                    <input type="hidden" id="catID" name="catID" value="<?php echo $this->ID ?>" />
                </form>
            <?php
            }
        }

        /**
         * Pre-condition: called via the WP delete_category action. Function should not
         * be called from an instantiated sp_category object
         *
         * @param int catID The ID of the category being deleted
         * @todo remove templateID from any SP QP instances
         */
        function deleteCategory($catID){
            $sp_categories = get_option('sp_categories');

            if( !empty($sp_categories)){
                if( in_array($catID, $sp_categories) ){

                    //remove the catID from the $sp_categories option
                    $key = array_search($catID, $sp_categories);
                    unset($sp_categories[$key]);
                    update_option('sp_categories', array_values($sp_categories));

                    //remove catID from any response categories
                    $sp_responseCats = get_option('sp_responseCats');

                    if( !empty($sp_responseCats) ){
                        foreach( $sp_responseCats as $sp_catID => $inc ){
                            if( isset($sp_responseCats[$sp_catID][$catID]) );
                            unset($sp_responseCats[$sp_catID][$catID]);
                        }

                        update_option('sp_responseCats', $sp_responseCats);
                    }

                    $sp_category = new sp_category(null, null, $catID);

                    //Remove icon
                    $iconID = $sp_category->getIconID();
                    if( !empty($iconID) ){
                        wp_delete_attachment( $iconID, true );
                    }

                    //Remove all the components
                    $catComponents = $sp_category->getComponents();
                    if( count($catComponents) > 0 ){
                        foreach($catComponents as $component){
                            $component->delete();
                        }
                    }
                }
            }
            return $catID;
        }

        /*
         * Updates the iconID representing the corresponding icon for the current
         * category.
         */
        function setIconID($iconID){
            $sp_cat_icons = get_option('sp_cat_icons');
            if(empty($sp_cat_icons)){
                $sp_cat_icons = array();
            }

            if($sp_cat_icons[$this->ID] > 0){
                wp_delete_attachment( $sp_cat_icons[$this->ID], true );
            }

            $sp_cat_icons[$this->ID] = $iconID;
            update_option('sp_cat_icons', $sp_cat_icons);
        }

        /*
         * Delete the icon and unset the iconID of the category.
         * (Actually deletes icon file).
         */
        function deleteIcon(){
            $sp_cat_icons = get_option('sp_cat_icons');
            unset($sp_cat_icons[$this->ID]);
            update_option('sp_cat_icons', $sp_cat_icons);
            wp_delete_attachment( $this->iconID, true );
            $this->iconID = 0;
        }

        function setTitle($title){
            if( !empty($title) ){
                $this->title = $title;
                return wp_update_category(array('cat_ID' => $this->ID, 'cat_name' => $title));
            }else{
                $this->errors = new WP_Error('broke', ("Category title cannot be empty."));
                return $this;
            }
        }

        function setDescription($description){
            $this->description = $description;
            return wp_update_category(array('cat_ID' => $this->ID, 'category_description' => $description));
        }


        /**
         * Sets the response categories of the category
         *
         * @param array $responseCats An array of SP category IDs: array( ID1 => true, ID2 => true, etc... )
         * @return bool True if update succesful, false otherwise
         */
        function setResponseCats($responseCats){
            $sp_responseCats  = get_option('sp_responseCats');
            if($sp_responseCats[$this->ID] != $responseCats){
                if( empty($sp_responseCats) )
                    $sp_responseCats = array();

                $this->responseCats = $responseCats;
                $sp_responseCats[$this->ID] = $this->responseCats;
                return update_option('sp_responseCats', $sp_responseCats);
            }else{
                //If nothing is updated, return true
                return true;
            }
        }

        function getResponseCats(){
            return $this->responseCats;
        }

        function getID(){
            return $this->ID;
        }

        function getIconID(){
            return $this->iconID;
        }

        function getTitle(){
            return stripslashes($this->title);
        }

        function getDescription(){
            return stripslashes($this->description);
        }

        /****************************
         *  Post Component Methods  *
         ****************************/

        function getComponents(){
            return $this->catComponents;
        }

        /**
         * Returns an array of the default components of the sp category
         *
         * @return array An array of sp_catComponent objects
         */
        function getDefaultComps(){
            if(!empty($this->catComponents)){
                $defaultComps = array();
                $i = 0;
                foreach($this->catComponents as $component){
                    if($component->getDefault() && !$component->getRequired()){
                        $defaultComps[$i++] = $component;
                    }
                }
                return $defaultComps;
            }
            return null;
        }

        /**
         * Returns an array of the required components of the sp category
         *
         * @return array An array of sp_catComponent objects
         */
        function getRequiredComps(){
            if(!empty($this->catComponents)){
                $reqdComps = array();
                $i = 0;
                foreach($this->catComponents as $component){
                    if($component->getRequired()){
                        $reqdComps[$i++] = $component;
                    }
                }
                return $reqdComps;
            }
            return null;
        }

        /*
         * Creates a new Category Component under this category
         *
         * @param string $name The name of the category component
         * @param string $description The description of the cat component
         * @param bool   $isDefault If the component should be default
         * @param bool   $isRequired If the component should be required
         * @return sp_catComponent An sp_catComponent object on success, otherwise a WP_Error object
         */
        function addCatComponent($name, $description, $typeID, $isDefault, $isRequired){
            $compOrder = self::getNextOrder();
            $type = 'sp_cat' . sp_core::getTypeName($typeID);

            if(!class_exists($type)){
                return new WP_Error('broke', ('Could not find ' . $type . ' class!'));
            }

            $component = new $type(0, $this->ID, $name, $description, $typeID, $compOrder,
                null, $isDefault, $isRequired);

            if(is_wp_error($component->errors)){
                return $component->errors->get_error_message();
            }

            array_push($this->catComponents, $component);

            //adds this component to publishes, drafted, and trashed posts
            if($isRequired || $isDefault){
                $this->dessiminateComponent($component->getID());
            }

            return $component;
        }

        /*
         * Adds a component to all posts under this category
         */
        function dessiminateComponent($catCompID){
            $args = array( 'numberposts' => -1, 'category' => $this->ID, 'post_status' => 'publish|draft|trash');
            $posts = get_posts( $args );
            foreach($posts as $post){
                $sp_post = new sp_post($post->ID, true);
                $sp_post->addComponent($catCompID);
            }
        }

        /*
         * Adds a required or default component to all posts under this category
         * if necessary. Used if a post component transitions from 'optional' to 'required'
         * or 'default'.
         *
         * @return bool false if
         */
        function maybeDessiminateReqdOrDef($catCompID){
            $type      = 'sp_cat' . sp_catComponent::getCompTypeFromID($catCompID);
            $component = new $type($catCompID);

            if($component->getRequired() || $component->getDefault()){
                $args      = array( 'numberposts' => -1, 'category' => $this->ID, 'post_status' => 'publish|draft|trash');
                $posts     = get_posts( $args );
                foreach($posts as $post){
                    $sp_post = new sp_post($post->ID, true);

                    if($sp_post->countByCatCompID($catCompID) < 1){
                        $sp_post->addComponent($catCompID);
                    }
                }
            }else{
                return false;
            }
        }

        function getComponentByName($name){
            foreach($this->catComponents as $component){
                if($component->name == $name){
                    return $component;
                }
            }
            return new WP_Error ('notFound', __("Could not find a component by the name: " . $name));
        }

        function getComponentByID($id){
            foreach($this->catComponents as $component){
                if($component->getID() == $id){
                    return $component;
                }
            }
            return new WP_Error ('notFound', __("Could not find a component by the ID: " .
                $id));
        }

        private function getNextOrder(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $sql = "SELECT MAX(compOrder) FROM $tableName where catID = " . $this->ID;
            $nextOrder = (int) $wpdb->get_var($wpdb->prepare($sql)) + 1;
            return $nextOrder;
        }

        private function getCompCount(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $sql = "SELECT COUNT(*) FROM $tableName where catID = $this->ID";
            return $wpdb->get_var($wpdb->prepare($sql));
        }

        /**
         * Sets the new component order
         *
         * @param  array       $compOrder an array of the form [ 0 => compID1, 1 => compID2]
         * @return bool|object Returns a WP_Error object on failure, otherwise true on success
         */
        public function setCompOrder($compOrder){
            $numOfComps = (int) self::getCompCount();
            if(count($compOrder) !== $numOfComps){
                return new WP_Error('broke', ('Number of components do not match.'));
            }else{
                $newOrder = array_values($compOrder);
                foreach($newOrder as $order => $compID){
                    $component = $this->getComponentByID($compID);
                    if(is_wp_error($component)){
                        return new WP_Error('broke', ('Could not instantiate component with component ID: "' . $compID . '".'));
                    }
                    $component->setCompOrder($order);
                }
            }
            return true;
        }
    }
}
?>