<?php
if (!class_exists("sp_catComponent")) {

    /**
     * sp_catComponent is the abstract class that all
     * category component classes should extend. Uses
     * sp_catComponent table.
     *
     * @abstract
     * @version 2.0
     * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
     * @project SmartPost
     */

    abstract class sp_catComponent{

        protected $ID;
        protected $name;
        protected $description;
        protected $catID;
        protected $typeID;
        protected $options;
        protected $compOrder;
        protected $isDefault;
        protected $isRequired;
        protected $icon;
        public $errors;

        /**
         * Used in smartpost::requireFiles().
         * Installs itself into sp_compTypes and makes this class known to SmartPost that it exists
         *
         * @return mixed
         */
        abstract public function install();

        /**
         * Called when register_uninstall_hook() is called
         *
         * @see register_uninstall_hook()
         * @return mixed
         */
        abstract public function uninstall();

        /**
         * Any initialization the component needs, i.e. enqueue
         * JS/CSS files, action hooks, filters, etc..
         * @return mixed
         */
        abstract static public function init();

        /**
         * GUI Function that renders serialized options that are specific to the component.
         * Used inside globalOptions().
         * @return mixed
         */
        abstract public function componentOptions();

        /**
         * Return component options in the appropriate form
         * @return mixed return the component options
         */
        abstract public function getOptions();

        /**
         * Settings section that is added to the SmartPost settings sub-page in the
         * Admin section/WP Dashboard. Should include any settings that may affect
         * your component globally across all posts.
         * @return bool|string The XHTML (forms, tables, etc.) representing the settings of your component, otherwise false
         */
        abstract static public function globalOptions();

        /**
         * Sets the component options. For each component, the set operation is unique.
         * @param null $data
         * @return mixed
         */
        abstract public function setOptions($data = null);

        /**
         * Creates a new category component (inserts it into the DB) or loads an existing one if
         * $compID > 0.
         * @param array $compInfo An array with the component info. @see __construct() method of child class more info
         * @return int|object The component ID on success, otherwise a WP_Error object on failure
         */
        protected function initComponent($compInfo){
            global $wpdb;
            $wpdb->show_errors();
            extract($compInfo);

            if($compID > 0){
                $this->load($compID);
            }else{

                //define member variables
                $this->catID       = $catID;
                $this->name        = $name;
                $this->description = $description;
                $this->typeID      = $typeID;
                $this->compOrder   = $order;
                $this->isDefault   = $default;
                $this->isRequired  = $required;

                $tableName = $wpdb->prefix . 'sp_catComponents';
                $wpdb->insert($tableName,
                    array(
                        'catID'    	  => $this->catID,
                        'name'     	  => $this->name,
                        'description' => $this->description,
                        'typeID'      => $this->typeID,
                        'compOrder'   => $this->compOrder,
                        'options'     => maybe_serialize($this->options),
                        'is_default'  => $this->isDefault,
                        'is_required' => $this->isRequired
                    ),array('%d', '%s', '%s', '%d', '%d', '%s', '%d', '%d'));
                if($wpdb->insert_id === false){
                    $this->errors = new WP_Error ('broke', __("Could insert component into the database succesffully: " . $wpdb->print_error()));
                }else{
                    $this->ID = $wpdb->insert_id;
                }
            }
        }

        /**
         * Loads an existing category component into a sp_catComponent instance
         * @param $compID
         */
        protected function load($compID){
            global $wpdb;
            $wpdb->show_errors();
            if( empty( $compID ) ){
                $this-> errors = new WP_Error ('broke', __("Invalid Component ID"));
            }else{
                $sp_catComponentsTable = $wpdb->prefix . "sp_catComponents";
                $component = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $sp_catComponentsTable WHERE id = %d;", $compID ) );
                if( !empty($component) ){
                    $this->ID          = $component->id;
                    $this->catID       = $component->catID;
                    $this->name        = $component->name;
                    $this->typeID      = $component->typeID;
                    $this->compOrder   = $component->compOrder;
                    $this->description = $component->description;
                    $this->options	   = maybe_unserialize($component->options);
                    $this->isDefault   = (bool) $component->is_default;
                    $this->isRequired  = (bool) $component->is_required;
                    $this->icon        = $component->iconID;
                }else{
                    $this->errors = new WP_Error ('broke', __("Could not find component with ID: " . $compID));
                }
            }
        }

        /**
         * Includes base JS and CSS for all category component classes.
         * Called in the admin settings page.
         */
        static function init_cat_component(){
            if(is_admin()){
                require_once( dirname( __FILE__ ) . '/ajax/sp_catComponentAJAX.php');
                sp_catComponentAJAX::init();
                self::enqueueBaseJS();
            }
        }

        /**
         *Add JS common to all components
         */
        static function enqueueBaseJS(){
            wp_register_script( 'sp_catComponentJS', plugins_url('/js/sp_catComponent.js', __FILE__), array('sp_globals', 'sp_admin_js'));
            wp_enqueue_script( 'sp_catComponentJS' );
        }

        /**
         * Adds this component to sp_compTypes
         * precondition: must be called inside sub_class::install()
         * where sub_class is a component extending this one
         *
         * @param string $name        The name of the component (No spaces allowed!)
         * @param string $description Description of the component
         * @param string $filepath    Filepath of the component
         */
        function installComponent($name, $description, $filepath){
            global $wpdb;
            $wpdb->show_errors();

            if( empty($name) || empty($filepath) ){
                ?>
                <div class="error">
                    Error: Could not install new component to SmartPost. The name or filepath
                    of the component were not provided.
                </div>
                <?php
                exit;
            }

            //Try and find the icon
            $icon_rel_path = '';
            $icon_ext = array('.png', '.jpg', '.jpeg');
            $path = dirname($filepath);

            //look for the right extension
            foreach($icon_ext as $ext){
                if(is_readable($path . '/images/icon' . $ext)){
                    $icon_filename = 'icon' . $ext;
                    break;
                }
            }

            if( !empty($icon_filename) ){
                $icon_rel_path = strtolower($name) . '/images/' . $icon_filename;
            }

            $typeID = sp_core::get_type_id_by_name($name);

            // The component already exists, so update it if necessary
            if($typeID > 0){
                $tableName = $wpdb->prefix . 'sp_compTypes';
                $wpdb->update(
                    $tableName,
                    array(
                        'name' 		  => $name,
                        'description' => $description,
                        'icon'        => $icon_rel_path
                    ),
                    array( 'id' => $typeID ),
                    array(
                        '%s','%s','%s'
                    ),
                    array( '%d' )
                );
            }

            // Otherwise, add component to sp_compTypes
            if( is_null($typeID) || $typeID === 0 ){
                $tableName = $wpdb->prefix . 'sp_compTypes';
                $wpdb->insert(
                    $tableName,
                    array(
                        'name' 		  => $name,
                        'description' => $description,
                        'icon'		  => $icon_rel_path
                    ),
                    array('%s','%s','%s')
                );
            }
        }

        /**
         * Delete the component from sp_catComponents
         *
         * @return bool|int false on failure, number of rows affected on success
         */
        function delete(){
            // delete the icon
            if(!empty($this->icon)){
                wp_delete_attachment($this->icon, true);
            }

            // delete the category component from sp_catComponents table
            return sp_core::delete_component( $this->ID, 'cat');
        }

        /**
         * Returns number of instances of the category component in $postID
         * If $compID is not supplied, then it will default to $this->ID
         *
         * @param  int $postID The post ID
         * @param  int $compID The category component ID
         * @return int The number of component instances that exist under the post
         *             with postID of $postID
         */
        function numOfInstances($postID, $compID = 0){
            global $wpdb;
            if($compID == 0){
                $compID = $this->ID;
            }
            $tableName = $wpdb->prefix . 'sp_postComponents';
            $sql = $wpdb->prepare( "SELECT COUNT(*) FROM $tableName where postID = %d AND catCompID = %d;", $postID, $compID );
            return $wpdb->get_var( $sql );
        }

        /**************************************
         * GUI Functions					  *
         **************************************/

        /**
         * Builds a meta box with the component's settings in XHTML format.
         * @see sp_admin::render_component_meta_boxes
         * @return string
         */
        public function render(){
            $disabled  = $this->isRequired ? 'disabled="disabled"' : '';
            $isDefaultChecked  = $this->isDefault ? 'checked="checked"' : '';
            $isRequiredChecked = $this->isRequired ? 'checked="checked"' : '';

            $checkBoxes = '<span class="requiredAndDefault">';
                $checkBoxes .= '<label for="isDefault-' . $this->ID . '" class="tooltip" title="A \'default\' component will automatically be added to a new post.">Default </label>';
                $checkBoxes .= '<input type="checkbox" class="compRestrictions" id="isDefault-' . $this->ID . '" data-compid="' . $this->ID . '" name="isDefault-' . $this->ID  . '" value="1" ' . $disabled . $isDefaultChecked . '/> ';
                $checkBoxes .= '<label for="isRequired-' . $this->ID . '" class="tooltip" title="A \'required\' component will highlight an empty component that was not filled by a user prior to submission.">Required </label>';
                $checkBoxes .= '<input type="checkbox" class="compRestrictions" id="isRequired-' . $this->ID . '" data-compid="' . $this->ID  . '" name="isRequired-' . $this->ID . '" value="1" ' . $isRequiredChecked  . '/>';
            $checkBoxes .= '</span>';

            $id    = $this->getCompType() . '-' . $this->ID;
            $title = '<img class="catCompIcon" src="' . $this->getIcon() . '" /> ';
            $title .= '<span class="editableCatCompTitle tooltip" title="Click to edit the title of the component" comp-id="' . $this->ID . '">';
            $title .= $this->name;
            $title .= '</span>';
            $title .= '<span class="delComp sp_xButton" id="del-' . $this->ID . '" comp-id="' . $this->getCompType() . '-' . $this->ID . '" alt="Delete Component" title="Delete Component"></span>';
            $title .=  $checkBoxes;

            add_meta_box(
                $id,
                __( $title ),
                array( &$this, 'componentOptions' ),
                'toplevel_page_smartpost',
                'normal',
                'default'
            );
        }

        /**************************************
         * Statically Bound Getters/Setters	  *
         **************************************/

        /**
         * Returns true whether $name exists under a given $catID, otherwise false
         * @param $name
         * @param $catID
         * @return bool
         */
        static function componentExists($name, $catID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $nameExists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName WHERE catID = %d AND name = %s;", $catID, $name) );
            return ($nameExists > 0);
        }

        /**
         * Uses $name to find $id of a particular category component under
         * the given $catID
         *
         * @todo Return multiple rows in case $name is common among multiple components (which can happen)
         */
        static function getIDFromName($name, $catID){
            global $wpdb;
            return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM 'sp_catComponents' WHERE name = %s AND catID = %d;", $name, $catID ) );
        }

        /**
         * Gets the component name based off $compID
         *
         * @param int $compID The category component's ID
         * @return string The name of the category component
         */
        static function getNameFromID($compID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $name 	   = (string) $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $tableName where id = %d;", $compID ) );
            return $name;
        }

        /**
         * Gets the component Type based off $compID
         *
         * @param int compID The ID of the category component
         * @return string The Type of the category component
         */
        static function get_comp_type_from_id($compID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $typeID    = $wpdb->get_var( $wpdb->prepare( "SELECT typeID FROM $tableName where id = %d;", $compID ) );
            return sp_core::get_type_name( $typeID );
        }

        /**
         * Gets the component TypeID based off $compID
         *
         * @param int compID The ID of the category component
         * @return int The typeID of the category component
         */
        static function getCompTypeIDFromID($compID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $typeID    = $wpdb->get_var( $wpdb->prepare( "SELECT typeID FROM $tableName where id = %d;", $compID ) );
            return $typeID;
        }

        /**
         * True if the component with $compID is default, otherwise false
         *
         * @param int $compID The category component ID
         * @return bool true if the component with $compID is default, otherwise false
         */
        static function getDefaultFromID($compID){
            if( !empty($compID) ){
                global $wpdb;
                $tableName  = $wpdb->prefix . 'sp_catComponents';
                $isDefault = (int) $wpdb->get_var( $wpdb->prepare( "SELECT is_default FROM $tableName WHERE id = %d;", $compID ) );
                return (bool) $isDefault;
            }else{
                return false;
            }
        }

        /**
         * True if the component with $compID is required, otherwise false
         *
         * @param int $compID The category component ID
         * @return bool true if the component with $compID is required, otherwise false
         */
        static function getRequiredFromID($compID){
            if( !empty($compID) ){
                global $wpdb;
                $tableName  = $wpdb->prefix . 'sp_catComponents';
                $isRequired = (int) $wpdb->get_var( $wpdb->prepare( "SELECT is_required FROM $tableName WHERE id = %d;", $compID ) );
                return (bool) $isRequired;
            }else{
                return false;
            }
        }

        /**
         * Gets the options of a content category component by its ID
         *
         *  @param int $compID The ID of the component
         *  @return mixed The component options
         */
        static function getOptionsFromID($compID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_catComponents';
            $options   = $wpdb->get_var( $wpdb->prepare( "SELECT options FROM $tableName where id = %d;", $compID ) );
            return maybe_unserialize($options);
        }

        /**************************************
         * Dynamically Bound Getters/Setters  *
         **************************************/

        function getID(){
            return $this->ID;
        }

        function getName(){
            return $this->name;
        }

        function setName($name){
            $this->name = (string) $name;
            return sp_core::updateVar('sp_catComponents', $this->ID, 'name', $this->name, '%s');
        }

        function getDescription(){
            return $this->description;
        }

        function setDescription($desc){
            $this->description = (string) $desc;
            return sp_core::updateVar('sp_catComponents', $this->ID, 'description', $this->description, '%s');
        }

        function getCatID(){
            return $this->catID;
        }

        function getTypeID(){
            return $this->typeID;
        }

        function getCompOrder(){
            return $this->compOrder;
        }

        function setCompOrder($order){
            $this->compOrder = (int) $order;
            return sp_core::updateVar('sp_catComponents', $this->ID, 'compOrder', (int) $this->compOrder, '%d');
        }

        function getDefault(){
            return $this->isDefault;
        }

        /**
         * Sets the category component to default and also calls
         * sp_category::maybeDessimanteReqdOrDef which will add a post
         * component instance to all posts under its category if an instance
         * does not already exist
         * @param $isDefault Whether the component is default or not
         * @return bool|int The number of rows affected from updateVar(), otherwise false
         */
        function setIsDefault($isDefault){
            $this->isDefault = (bool) $isDefault;
            $result = sp_core::updateVar('sp_catComponents', $this->ID, 'is_default', (int) $isDefault, '%d');
            if(!$this->isRequired && $this->isDefault){
                $sp_category = new sp_category(null, null, $this->catID);
                $sp_category->maybeDessiminateReqdOrDef($this->ID);
            }
            return $result;
        }

        function getRequired(){
            return $this->isRequired;
        }

        /**
         * Sets the category component to required and also calls
         * sp_category::maybeDessimanteReqdOrDef which will add a post
         * component instance to all posts under its category if an instance
         * does not alread exist
         *
         * @see sp_category::maybeDessimanteReqdOrDef()
         * @param bool $isRequired Whether the component is default or not
         * @return int  $result The number of rows affected from updateVar(), otherwise false
         */
        function setIsRequired($isRequired){
            $this->isRequired = (bool) $isRequired;
            $result = sp_core::updateVar('sp_catComponents', $this->ID, 'is_required', (int) $isRequired, '%d');
            if($this->isRequired){
                $sp_category = new sp_category(null, null, $this->catID);
                $sp_category->maybeDessiminateReqdOrDef($this->ID);
            }
            return $result;
        }

        /**
         * Returns the icon URL.
         * @return string The URL of the icon
         */
        function getIcon(){


            //If icon hasn't been set, return the default icon
            $default_icon = sp_core::getIcon($this->typeID); //the default component type icon

            if( empty($this->icon) ){
                return $default_icon;
            }

            $icon_url = wp_get_attachment_url($this->icon);

            //in case $this->icon is broken (may have been deleted)
            if( empty($icon_url) ){
                return $default_icon;
            }

            //Otherwise return the icon url
            return $icon_url;
        }

        function getIconID(){
            return $this->icon;
        }

        function setIcon($iconID){
            $this->icon = (int) $iconID;
            return sp_core::updateVar('sp_catComponents', $this->ID, 'iconID', $this->icon, '%d');
        }

        function getCompType(){
            return sp_core::get_type_name($this->typeID);
        }

    }
}
?>