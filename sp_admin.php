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
            wp_register_script( 'sp_admin_js', plugins_url('/js/sp_admin.js', __FILE__), array('sp_admin_globals', 'post', 'postbox'));
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
            add_submenu_page( 'smartpost', 'SP Category Settings', 'SP Category Settings', 'edit_users', 'sp-cat-page', array('sp_admin', 'sp_category_page') );
        }

        /**
         * Renders all the component types as a HTML-draggable blocks.
         */
        public static function listCompTypes(){
            $types = sp_core::getTypes();
            ?>
            <div id="sp_compTypes">
                <?php foreach($types as $compType){ ?>
                    <div type-id="type-<?php echo $compType->id ?>"  alt="<?php echo $compType->description ?>" class="catCompDraggable">
                        <h3><?php echo '<img src="' . $compType->icon . '" />' ?> <?php echo trim($compType->name) ?></h3>
                    </div>
                <?php } ?>
            </div>
        <?php
        }

        /**
         * Renders all the components of a given SmartPost-enabled category.
         * @param sp_category $sp_category
         */
        function listCatComponents($sp_category){
            $catComponents = $sp_category->getComponents();
            if(!empty($catComponents)){
                foreach($catComponents as $component){
                    $component->render();
                }
                do_meta_boxes('smartpost', 'normal', null);
            }else{
                echo "<div id='normal-sortables' class='meta-box-sortables ui-sortable'></div>";
            }
        }

        /**
         * Renders a new category form that users can fill out
         */
        function renderCategoryForm(){
            ?>
            <div id="newCategoryForm">
                <form id="cat_form" method="post" action="">
                    <table>
                        <tr>
                            <td>
                                <h4>Category Name <span style="color:red">*</span></h4>
                            </td>
                            <td>
                                <input type="text" class="regular-text" id="cat_name" name="cat_name" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h4>Category Description</h4>
                            </td>
                            <td>
                                <input type="text" class="regular-text" id="category_description" name="category_description" value="" />
                            </td>
                        </tr>
                        <tr>
                            <td><h4>Category Icon</h4></td>
                            <td>
                                <input type="file" id="category_icon" name="category_icon">
                            </td>
                        </tr>
                    </table>
                    <p style="color: red">* Required</p>
                    <button class="button button-large" id="">Save Template</button>
                </form>
            </div>
            <?php
        }

        /**
         * Given a category, renders the settings for that category.
         * @param object $sp_category The sp_category object instance.
         */
        static function renderCatSettings($sp_category){
            if(is_wp_error($sp_category->errors)){
                ?>
                <div class="error">
                    <h3>An error occurred: <?php echo $sp_category->errors->get_error_message() ?></h3>
                </div>
            <?php
            }else{
                ?>
                <?php echo wp_get_attachment_image($sp_category->getIconID(), null, null, array('class' => 'category_icon')); ?>
                <h2 class="category_title">
                    <a href="<?php echo admin_url('admin.php?page=sp-cat-page&catID=' . $sp_category->getID()) ?>">
                        <?php echo $sp_category->getTitle() ?>
                    </a>
                </h2>
                <?php
                $catDesc = $sp_category->getDescription();
                echo empty($catDesc) ? '' : '<p>' . $catDesc . '</p>';
                ?>
                <input type="hidden" name="catID" id="catID" value="<?php echo $sp_category->getID() ?>" />
            <?php
            }
        }

        /**
         * HTML <ul> that lists all the categories.
         * Used with DynaTree JS to visualize the categories in a tree view.
         */
        function renderCatTree(){
            $sp_categories = get_option("sp_categories");
            $categories    = get_categories(array('orderby' => 'name','order' => 'ASC', 'hide_empty' => 0));
            ?>
            <div id="sp_catTree">
                <ul id="sp_catList">
                    <?php
                    foreach($categories as $category){
                        $sp_category  = null;
                        $spcat        = null;
                        $adminUrl     = null;
                        $catIcon      = null;
                        $liCatData    = null;

                        if(in_array($category->term_id, $sp_categories)){
                            $sp_category = new sp_category(null, null, $category->term_id);
                            $catIcon     = wp_get_attachment_url($sp_category->getIconID());
                            $adminUrl    = admin_url('admin.php?page=smartpost&catID=' . $category->term_id);
                            if( !empty($catIcon) )
                                $catIcon = 'icon: ' . $catIcon . ', ';
                        }else{
                            $adminUrl = admin_url('edit-tags.php?action=edit&taxonomy=category&tag_ID=' . $category->term_id . '&post_type=post');
                        }

                        $liCatData = 'isFolder: true';
                        $liCatData .= empty($catIcon) ? ', catID: ' . $category->term_id : ', catID: ' . $category->term_id . ', icon: ' . $catIcon;
                        $liCatData .= empty($sp_category) ? ", addClass: 'disableSPSortable'" : '';
                    ?>
                        <li id="cat-<?php echo $category->term_id ?>" data="<?php echo $liCatData ?>">
                            <a href="<?php echo $adminUrl ?>" target="_self"><?php echo $category->name ?></a>
                    <?php

                        if(!is_null($sp_category)){
                            $components = $sp_category->getComponents();
                            if(!empty($components)){
                                echo '<ul>';
                                foreach($components as $comp){
                                    $compIcon = $comp->getIcon();
                                    $liData = !empty($compIcon) ? 'icon: \'' . $compIcon . '\', compID: ' . $comp->getID() : 'compID: ' . $comp->getID();

                                    echo '<li id="comp-' . $comp->getID() .'" data="' . $liData . '">' . $comp->getName() . '</li>';
                                }
                                echo '</ul>';
                            }
                        }
                    }
                    ?>
                </ul>
            </div>
        <?php
        }

        /**
         * Renders the dashboard admin page for the SmartPost plugin.
         * @see sp_admin::sp_admin_add_page()
         */
        function sp_template_page(){
            if (!current_user_can('manage_options'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
            $catID         = (int) $_GET['catID'];
            $sp_category   = new sp_category(null, null, $catID);
            $sp_categories = get_option('sp_categories');
            ?>

            <div class="wrap">
            <div id="sp_errors"></div>
            <h2><?php echo PLUGIN_NAME . ' Templates' ?></h2>

            <button id="newCatButton" class="button button-primary button-large" title="Create a new category template">New Template</button>
            <?php self::renderCategoryForm(); ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content" style="margin-bottom: 0px;">
                        <div id="category_settings" class="postbox">
                            <div id="setting_errors"></div>
                            <div id="the_settings">
                                <?php
                                if( empty($sp_categories) ){
                                    self::renderCategoryForm();
                                }else{
                                    if( empty($_GET['catID']))
                                        $sp_category = new sp_category(null, null, $sp_categories[0]);

                                    self::renderCatSettings($sp_category);
                                }
                                ?>
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
                                <?php self::renderCatTree(); ?>
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

                    <div id="postbox-container-2" class="postbox-container">
                        <?php self::listCatComponents($sp_category) ?>
                        <?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
                    </div><!-- end #postbox-container-1 -->

                </div><!-- end #post-body -->
            </div><!-- end #poststuff -->
        <?php
        }

        function sp_category_page(){
            ?>
            <div class="wrap">
            <h2><?php echo PLUGIN_NAME ?> Category Settings</h2>
        <?php
        }
    }
}
?>