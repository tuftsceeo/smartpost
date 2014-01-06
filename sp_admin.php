<?php
if (!class_exists("sp_admin")) {

    /**
     * sp_admin class
     *
     * Handles many of the features and functions in the WordPress administrative
     * dashboard on the SmartPost settings page.
     */
    class sp_admin{

        /**
         * Includes CSS, JS, and AJAX classes to initialize the admin page.
         * Registers actions used by the admin page.
         *
         * @params none
         */
        static function init(){
            require_once('ajax/sp_adminAJAX.php');
            sp_adminAJAX::init();
            add_action( 'admin_menu', array('sp_admin', 'sp_admin_add_template_page') );
            add_action( 'admin_menu', array('sp_admin', 'sp_admin_add_category_page') );
            add_action( 'admin_enqueue_scripts', array('sp_admin', 'enqueueScripts') );

            //Load relevant classes for the admin page.
            $spTypes = sp_core::getTypesAndIDs();
            foreach($spTypes as $typeName => $typeID){
                $class = 'sp_cat' . $typeName;
                if(class_exists($class)){
                    $class::init();
                }
            }
        }

        /**
         * CSS for the admin pages
         */
        function enqueueCSS(){
            wp_register_style( 'sp_admin_css', plugins_url('/css/sp_admin.css', __FILE__) );
            wp_enqueue_style( 'sp_admin_css' );

            //Default WP styles
            wp_enqueue_style( 'buttons' );
            wp_enqueue_style( 'wp-admin' );
        }

        /**
         * JS/CSS for the admin pages
         */
        function enqueueScripts($hook){
            if('toplevel_page_smartpost' != $hook){
                return;
            }
            self::enqueueCSS();

            wp_register_script( 'sp_admin_globals', plugins_url('/js/sp_admin_globals.js', __FILE__), array( 'jquery') );
            wp_register_script( 'sp_admin_js', plugins_url('/js/sp_admin.js', __FILE__), array('sp_admin_globals', 'post', 'postbox', 'jquery-dynatree'));
            wp_enqueue_script( 'post' );
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'sp_admin_globals' );
            wp_localize_script( 'sp_admin_globals', 'sp_admin', array(
                    'ADMIN_NONCE' => wp_create_nonce( 'sp_admin_nonce'),
                    'ADMIN_URL'	  => admin_url( 'admin.php'),
                    'PLUGIN_PATH' => PLUGIN_PATH,
                    'IMAGE_PATH'  => IMAGE_PATH )
            );
            wp_enqueue_script( 'sp_admin_js' );
        }

        /**
         * Used in the WordPress action hook 'add_menu'.
         * Adds a top-level menu item to the Dashboard called SmartPost
         */
        function sp_admin_add_template_page() {
            add_menu_page( PLUGIN_NAME, 'SmartPost', 'edit_users', 'smartpost', array('sp_admin', 'sp_template_page'), null, null );
        }

        function sp_admin_add_category_page(){
            add_submenu_page( 'smartpost', 'Settings', 'Settings', 'edit_users', 'sp-cat-page', array('sp_admin', 'sp_component_page') );
        }

        /**
         * Renders all the component types as a HTML-draggable blocks.
         */
        public static function listCompTypes(){
            $types = sp_core::getTypes();
            ?>
            <div id="sp_compTypes">
                <?php foreach($types as $compType){ ?>
                    <div type-id="type-<?php echo $compType->id ?>"  title="<?php echo $compType->description ?>" class="catCompDraggable tooltip">
                        <h3><?php echo '<img src="' . $compType->icon . '" />' ?> <?php echo trim($compType->name) ?></h3>
                    </div>
                <?php } ?>
            </div>
        <?php
        }

        /**
         * Admin notifying the user that the SmartPost QuickPost widget has not yet been added to any sidebar
         */
        function check_for_spqp_widget(){

        }

        /**
         * Renders all the components of a given SmartPost-enabled category.
         * @param sp_category $sp_category
         */
        function listCatComponents($sp_category){
            $closed_meta_boxes = get_user_option( 'closedpostboxes_toplevel_page_smartpost' );
            $catComponents     = $sp_category->getComponents();
            if(!empty($catComponents)){
                foreach($catComponents as $component){
                    $component->render();

                    //handle meta box toggling
                    $compElemID = $component->getCompType() . '-' . $component->getID();
                    $key        = array_search($compElemID, $closed_meta_boxes);
                    if($key !== false){
                        unset($closed_meta_boxes[$key]);
                    }
                }
                do_meta_boxes('toplevel_page_smartpost', 'normal', null);

                foreach($closed_meta_boxes as $box_id){
                    echo '<input type="text" class="postbox closed hide" id="' . $box_id . '" />';
                }
            }else{
                echo "<div id='normal-sortables' class='meta-box-sortables ui-sortable'></div>";
            }
        }

        /**
         * Renders a new category form that users can fill out
         */
        function renderNewTemplateForm(){
            ?>
            <div id="newTemplateForm">
                <form id="template_form" method="post" action="">
                    <table>
                        <tr>
                            <td>
                                <h4>Template Name <span style="color:red">*</span></h4>
                            </td>
                            <td>
                                <input type="text" class="regular-text" id="template_name" name="template_name" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h4>Template Description</h4>
                            </td>
                            <td>
                                <textarea class="regular-text" id="template_desc" name="template_desc" style="width: 100%; background: white;" rows="10" ></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="checkbox" checked="checked" id="add_to_menu" name="add_to_menu" /><label for="add_to_menu" id="add_to_menu_label" class="tooltip" title="Select the menu you'd like to add the template to. <br/> The template will then be accessible on the front-end via a menu item.">Add to menu</label>
                            </td>
                            <td>
                                <select id="wp_menus" name="wp_menus">
                                    <?php
                                    $nav_menus = wp_get_nav_menus();
                                    foreach($nav_menus as $menu){
                                        ?>
                                        <option id="menu-<?php echo $menu->term_id ?>" name="menu-<?php echo $menu->term_id ?>" value="<?php echo $menu->term_id ?>"><?php echo $menu->name ?></option>
                                        <?
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="checkbox" checked="checked" id="add_widget" name="add_widget" /><label for="add_widget" id="add_widget_label" class="tooltip" title="Select the sidebar you'd like to add the SP QuickPost widget to.<br />This widget makes this template accessible to users on the front-end of the site.">Add SP widget</label>
                            </td>
                            <td>
                                <select id="widget_areas" name="widget_areas">
                                    <?php
                                    $sidebars = $GLOBALS['wp_registered_sidebars'];
                                    $recommended = "";
                                    $selected    = "";
                                    foreach($sidebars as $sidebar){
                                        if( defined('SP_CAT_SIDEBAR') ){
                                            if( $sidebar['id'] == SP_CAT_SIDEBAR ){
                                                $selected = 'selected="selected"';
                                                $recommended = ' (recommended)';
                                            }
                                        }
                                    ?>
                                    <option id="<?php echo $sidebar['id'] ?>" value="<?php echo $sidebar['id'] ?>" <?php echo $selected ?>><?php echo $sidebar['name'] . $recommended ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p style="color: red">* Required</p>
                    <button type="submit" class="button button-large" id="save_template">Save Template</button>
                </form>
            </div>
            <?php
        }

        /**
         * Renders the appropriate settings for a given category.
         * @param int $catID - the category ID
         * @param array $sp_categories - Array of category IDs that are SP enabled.
         */
        static function renderCatSettings($catID, $sp_categories){
            $sp_category = null;
            $category    = null;
            $cat_desc = null;
            $title = null;
            $icon  = null;
            if( in_array($catID, $sp_categories) ){
                $sp_category = new sp_category(null, null, $catID);
                $title = $sp_category->getTitle();
                $icon  = wp_get_attachment_image($sp_category->getIconID(), null, null, array('class' => 'category_icon'));
                $cat_desc = $sp_category->getDescription();
            }else{
                $category = get_category($catID);
                $title = $category->cat_name;
            }
            ?>
            <h2 class="category_title">
                <a href="<?php echo admin_url('edit-tags.php?action=edit&taxonomy=category&tag_ID=' . $catID . '&post_type=post') ?>">
                    <?php echo $icon . ' ' . $title ?>
                </a>
            </h2>
            <?php echo '<p>' . $cat_desc . '</p>'; ?>
            <?php
                if(!is_null($sp_category)){
                ?>
                    <input type="checkbox" id="sp_enabled" checked /> <label for="sp_enabled">Uncheck to disable SmartPost for this category.</label>
                <?php
                } else {
                ?>
                    <input type="checkbox" id="sp_enabled" /> <label for="sp_enabled">Check to enable SmartPost for this category.</label>
                <?php
                }
            ?>
            <input type="hidden" name="catID" id="catID" value="<?php echo $catID ?>" />
            <?php
        }

        /**
         * Build an object that represents the category hierarchy
         * with added smartpost components.
         * @param $args - $args used in get_category query
         * @param int $parent - The "root" parent node of where to start the query
         * @param bool $include_parent - Whether to include the parent in the resulting array
         * @return array
         */
        public static function buildSPDynaTree($args, $parent = 0, $include_parent = false){

            if($include_parent){
                $parentCat  = get_category( $parent );
                $categories = array( $parentCat );
            } else {
                $args['parent'] = $parent;
                $categories     = get_categories($args);
            }

            $sp_categories = get_option( "sp_categories" );
            $catTree =  array();

            foreach( $categories as $category ) {

                $node = new stdClass();

                $node->title    = $category->name;
                $node->key      = 'cat-' . $category->term_id;
                $node->isFolder = true;
                $node->catID    = $category->term_id;
                $node->href     = admin_url('admin.php?page=smartpost&catID=' . $category->term_id);
                $node->target   = '_self';

                if( in_array( $category->term_id, $sp_categories ) ){

                    $sp_category = new sp_category( null, null, $category->term_id );

                    $icon = wp_get_attachment_url( $sp_category->getIconID() );
                    $node->icon = $icon ? $icon : null;

                    $components = $sp_category->getComponents();

                    if( !empty($components) ){
                        $node->compCount = count($components);

                        $compNodes = array();
                        foreach( $components as $comp ) {
                            $compNode = new stdClass();
                            $compNode->title  = $comp->getName();
                            $compNode->key    = 'comp-' .  $comp->getID();
                            $compNode->icon   = $comp->getIcon() ? $comp->getIcon() : null;
                            $compNode->compID = $comp->getID();
                            array_push($compNodes, $compNode);
                        }
                    }
                }else{
                    $node->addClass = 'disableSPSortable';
                }

                $node->children = sp_admin::buildSPDynaTree($args, $category->term_id);

                if( !empty($compNodes) ){
                    $node->children = array_merge_recursive($compNodes, $node->children);
                    $compNodes = null;
                }

                array_push( $catTree, $node );
            }

            return $catTree;
        }

        /**
         * Handles notifications on the SmartPost template page
         * @param null $message_type
         * @param null $message
         * @return string
         */
        function sp_admin_notification($message_type = null, $message = null){
            switch( $message_type ){
                case 'new_cat':
                    return 'Your new template was successfully created. <a href="' . admin_url('nav-menus.php') . '">Click here </a> to change the way it appears on the menu. Click here to view it.';
                case 'custom':
                    return $message;
                default:
                    return '';
            }
        }

        /**
         * Renders the dashboard admin page for the SmartPost plugin.
         * @see sp_admin::sp_admin_add_page()
         * @todo Use add_meta_box() instead of hard-coding meta boxes.
         */
        function sp_template_page(){
            if (!current_user_can('manage_options'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
            $categories    = get_categories( array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 ) );
            $sp_categories = get_option('sp_categories');
            $catID         = empty($_GET['catID']) ? $categories[0]->term_id : (int) $_GET['catID'];
            $sp_category   = new sp_category(null, null, $catID);
            ?>

            <div class="wrap">
                <div id="sp_errors" class="error"></div>
                <div id="sp_success" class="updated"><?php echo self::sp_admin_notification( $_POST['msg_type'], $_POST['msg'] ) ?></div>
                <h2><?php echo PLUGIN_NAME . ' Templates' ?> <button id="newTemplateButton" class="button button-primary button-large" title="Create a new category template">New Template</button></h2>
                <?php self::renderNewTemplateForm(); ?>
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content" style="margin-bottom: 0;">
                            <div id="category_settings" class="postbox">
                                <div id="the_settings">
                                    <span id="delete-<?php echo $catID ?>" class="deleteCat xButton" data-cat-id="<?php echo $catID ?>" title="Delete template"></span>
                                    <?php self::renderCatSettings($catID, $sp_categories); ?>
                                    <div class="clear"></div>
                                </div><!-- end #the_settings -->
                                <div class="clear"></div>
                            </div><!-- end #category_settings -->
                        </div>

                        <div id="postbox-container-1" class="postbox-container">
                            <div id="sp_cat_list" class="postbox" style="display: block;">
                                <div class="handlediv" title="Click to toggle"><br></div>
                                <h3 class="hndle" style="cursor: default"><span>SmartPost Templates</span></h3>
                                <div class="inside">
                                    <div id="sp_catTreeSettings">
                                        <p id="expandAll">Expand/Collapse All</p>
                                    </div>
                                    <div id="sp_catTree"></div>
                                </div>
                            </div><!-- end sp_cat_list -->

                            <div id="sp_components" class="postbox" style="display: block;">
                                <div class="handlediv" title="Click to toggle"><br></div>
                                <h3 class="hndle" style="cursor: default;"><span>SmartPost Components</span></h3>
                                <div class="inside">
                                    <p>Drag the below components to the template on the left:</p>
                                    <?php self::listCompTypes() ?>
                                </div>
                            </div><!-- end sp_components -->

                        </div><!-- end #postbox-container-1 -->
                        <?php
                        if( in_array($catID, $sp_categories) ){
                        ?>
                            <div id="postbox-container-2" class="postbox-container">
                                <?php self::listCatComponents($sp_category) ?>
                            </div><!-- end #postbox-container-1 -->
                        <?php
                            //handle toggling for meta boxes
                            wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
                        }
                        ?>
                    </div><!-- end #post-body -->
                </div><!-- end #poststuff -->
        <?php
        }

        function sp_component_page(){
            ?>
            <div class="wrap">
            <h2><?php echo PLUGIN_NAME ?> Component Settings</h2>
            <?php
                $components = sp_core::getTypes();
                foreach($components as $comp){
                    echo $comp->name . '<br />';
                }
            ?>
        <?php
        }
    }
}
?>