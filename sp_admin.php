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

            add_action( 'admin_menu', array('sp_admin','sp_admin_add_page') );
            add_action( 'admin_enqueue_scripts', array('sp_admin', 'enqueueScripts') );
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
            wp_register_script( 'sp_admin_js', plugins_url('/js/sp_admin.js', __FILE__), array('jquery-ui-tabs'));
            wp_enqueue_script( 'postbox' );
            wp_enqueue_script( 'post' );
            wp_enqueue_script( 'sp_admin_globals' );
            wp_enqueue_script( 'sp_admin_js' );
            wp_localize_script( 'sp_admin_js', 'sp_admin', array(
                    'ADMIN_NONCE' => wp_create_nonce( 'sp_admin_nonce'),
                    'ADMIN_URL'	  => admin_url( 'admin.php'),
                    'PLUGIN_PATH' => PLUGIN_PATH,
                    'IMAGE_PATH'  => IMAGE_PATH )
            );
        }



        /**
         * Renders all the component types as a HTML drop-down menu.
         */
        public static function listCompTypes(){
            $types = sp_core::getTypes();
            ?>
            <select id="sp_compTypes" name="sp_compTypes">
                <?php foreach($types as $compType){ ?>
                    <option id="type-<?php echo $compType->id ?>" name="type-<?php echo $compType->id ?>" value="<?php echo $compType->id ?>">
                        <?php echo trim($compType->name) ?>
                    </option>
                <?php } ?>
            </select>
        <?php
        }

        /**
         * Renders all the components of a given SmartPost-enabled category.
         * @param $sp_category
         */
        function listCatComponents($sp_category){
            ?>
            <div id="catComponentList">
                <?php
                $catComponents = $sp_category->getComponents();
                if(!empty($catComponents)){
                    foreach($catComponents as $component){
                        $component->renderSettings();
                    }
                }
                ?>
            </div>
        <?php
        }

        /**
         * Renders a jQuery UI vertical Tabs. Each tab described a SmartPost component
         * and gives a user an option to add the component to the category template.
         */
        private static function renderComponents($sp_category){
            $compTypes = sp_core::getTypes();
            ?>
            <div id="sp_compTabs" class="ui-tabs ui-widget ui-widget-content ui-corner-all ui-tabs-vertical ui-helper-clearfix">
                <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
                    <?php foreach($compTypes as $cType){ ?>
                        <li class="ui-corner-left ui-state-default">
                            <a href="#compType-<?php echo $cType->id ?>" style="text-decoration: none;">
                                <img src="<?php echo $cType->icon ?>" style="vertical-align: text-bottom;"/>
                                <?php echo $cType->name ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
                <?php foreach($compTypes as $cType){ ?>
                <div id="compType-<?php echo $cType->id ?>" class="ui-tabs-panel ui-widget-content ui-corner-bottom">
                    <h2 style="margin-top:5px;"> <img src="<?php echo $cType->icon ?>" /> <?php echo $cType->name ?></h2>
                    <p><?php echo $cType->description ?></p>
                    <button id="addComponent" data="<?php echo $cType->id ?>" class="button button-primary button-large">Add to template</button>
                    <div class="clear"></div>
                </div>
                <?php } ?>
            </div>
            <?
        }

        /**
         * Given a category, renders the settings for that category.
         * @param object $sp_category The sp_category object instance.
         */
        static function renderSPCatSettings($sp_category){
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
                    <a href="<?php echo admin_url('admin.php?page=smartpost&catID=' . $sp_category->getID() . '&action=edit') ?>">
                        <?php echo $sp_category->getTitle() ?></a>
                </h2>
                <?php $sp_category->categoryMenu() ?>
                <?php $catDesc = $sp_category->getDescription();
                echo empty($catDesc) ? '' : '<p>' . $catDesc . '</p>';
                ?>
                <div class="clear"></div>
                <?php
                if($_GET['action'] == 'edit'){
                    self::loadCatForm($_GET['catID']);
                }else{
                    $active_tab = empty($_GET['tab']) ? "postComps" : $_GET['tab'];

                    ?>
                    <h2 class="nav-tab-wrapper" style="padding-bottom: 0px;">
                        <a href="?page=smartpost&catID=<?php echo $sp_category->getID() ?>&tab=postComps" class="nav-tab <?php echo $active_tab == "postComps" ? 'nav-tab-active' : '' ?>">Post Components</a>
                        <a href="?page=smartpost&catID=<?php echo $sp_category->getID() ?>&tab=responseCats" class="nav-tab <?php echo $active_tab == "responseCats" ? 'nav-tab-active' : '' ?>" >Response Categories</a>
                    </h2>
                    <div id="settings_list">
                        <?php
                        switch($_GET['tab']){
                            case 'postComps':
                                self::renderComponents($sp_category);
                                echo '<br />';
                                self::listCatComponents($sp_category );
                                break;
                            case 'responseCats':
                                $sp_category->renderResponseCatForm();
                                break;
                            default:
                                self::renderComponents($sp_category);
                                break;
                        }
                        ?>
                        <input type="hidden" name="catID" id="catID" value="<?php echo $sp_category->getID() ?>" />
                    </div>
                <?php
                }
            }
        }

        /*
         * Given a $catID, renders a HTML <form> with fields corresponding to properties of a category
         * represented by $catID. If $catID is <= 0, then the rendered <form> will be used for creating
         * a new category.
         *
         * @param $catID
         * @return string
         */
        static function catForm($catID){
            if($catID > 0){
                $sp_category = new sp_category(null, null, $catID);
            }else{
                ?><h2>New Category</h2><?php
            }
            ?>
            <form id="cat_form" method="post" action="">
                <div id="cat_info">
                    <table>
                        <tr>
                            <td>
                                <h4>Category Name <span style="color:#ff0000">*</span></h4>
                            </td>
                            <td>
                                <input type="text" class="regular-text" id="cat_name" name="cat_name" value="<?php echo isset($sp_category) ? $sp_category->getTitle() : '' ?>" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <h4>Category Description</h4>
                            </td>
                            <td>
                                <input type="text" class="regular-text" id="category_description" name="category_description" value="<?php echo isset($sp_category) ? $sp_category->getDescription() : '' ?>" />
                            </td>
                        </tr>
                        <?php if(isset($sp_category)){ ?>
                            <?php if( $sp_category->getIconID() > 0 ){ ?>
                                <tr>
                                    <td><h4>Current Icon</h4></td>
                                    <td>
                                        <p>
                                            <?php echo wp_get_attachment_image($sp_category->getIconID()) ?>
                                            Upload a new icon below to replace the current icon.
                                        </p>
                                        <p>
                                            <input type="checkbox" id="deleteIcon" name="deleteIcon" value="deleteIcon" />
                                            <label for="deleteIcon">Delete Icon</label>
                                        </p>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <tr>
                            <td><h4>Category Icon</h4></td>
                            <td>
                                <input type="file" id="category_icon" name="category_icon">
                            </td>
                        </tr>
                    </table>
                    <p style="color: red">* Required</p>
                </div>
        <?php
        }

        /**
         * Used in the WordPress action hook 'add_menu'.
         * Adds a top-level menu item to the Dashboard called SmartPost
         */
        function sp_admin_add_page() {
            add_menu_page( PLUGIN_NAME, 'SmartPost', 'edit_users', 'smartpost', array('sp_admin','smartpost_admin_page'), null, null );
        }

        /**
         * HTML <ul> that lists all the categories.
         * Used with DynaTree JS to visualize the categories in a tree view.
         */
        function listCategories(){
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

                        if(in_array($category->term_id, $sp_categories)){
                            $spCat       = 'spCat: true';
                            $sp_category = new sp_category(null, null, $category->term_id);
                            $catIcon     = wp_get_attachment_url($sp_category->getIconID());
                            $adminUrl    = admin_url('admin.php?page=smartpost&catID=' . $category->term_id);

                            if( !empty($catIcon) )
                                $catIcon = 'icon: ' . $catIcon . ', ';
                        }else{
                            $adminUrl = admin_url('edit-tags.php?action=edit&taxonomy=category&tag_ID=' . $category->term_id . '&post_type=post');
                        }
                        ?>
                        <li data="<?php echo empty($catIcon) ? 'isFolder: true' : 'icon: ' . $catIcon ?>">
                            <a href="<?php echo $adminUrl ?>">
                                <?php echo $category->name ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php
        }

        /**
         * Renders the dashboard admin page for the SmartPost plugin.
         * @see sp_admin::sp_admin_add_page()
         */
        function smartpost_admin_page(){
            if (!current_user_can('manage_options'))  {
                wp_die( __('You do not have sufficient permissions to access this page.') );
            }
            $catID         = (int) $_GET['catID'];
            $sp_category   = new sp_category(null, null, $catID);
            $sp_categories = get_option('sp_categories');
            ?>

            <div class="wrap">
                <div id="sp_errors"></div>
                <h2><?php echo PLUGIN_NAME . ' Settings' ?></h2>

                <button id="newSPCatForm" class="button button-primary button-large">Add a new SP Category</button>

                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <div id="category_settings" class="postbox">
                                <div id="setting_errors"></div>
                                <div id="the_settings">
                                    <?php
                                    if( empty($sp_categories) ){
                                        //self::newCatForm();
                                    }else{
                                        if( empty($_GET['catID']))
                                            $sp_category = new sp_category(null, null, $sp_categories[0]);

                                        self::renderSPCatSettings($sp_category);
                                    }
                                    ?>
                                    <div class="clear"></div>
                                </div><!-- end #the_settings -->
                                <div class="clear"></div>
                            </div><!-- end #category_settings -->
                        </div>

                        <div id="postbox-container-1" class="postbox-container">
                            <div id="side-sortables" class="meta-box-sortables ui-sortable">
                                <div id="sp_cat_list" class="postbox" style="display: block;">
                                    <div class="handlediv" title="Click to toggle"><br></div>
                                    <h3 class="hndle"><span>SmartPost Categories</span></h3>
                                    <div class="inside">
                                        <?php self::listCategories(); ?>
                                    </div>
                                </div><!-- end sp_cat_list -->
                            </div><!-- end side-portables -->
                        </div><!-- end #postbox-container-1 -->
                    </div><!-- end #post-body -->
                </div><!-- end #poststuff -->
        <?php
        }
    }
}
?>