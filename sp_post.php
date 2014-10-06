<?php
if (!class_exists("sp_post")) {

    class sp_post{

        private $wpPost; //wordpress post object
        private $sp_category;
        private $components;
        public $errors;

        /**
         * Instantiates or loads a new sp_post object
         *
         * @param int  $postID   The WP Post ID
         * @param bool $loadPost Whether to load an existing post. If true,
         *                       load() will attempt to load a sp post using
         *                       components from sp_postComponents, otherwise it
         *                       will instantiate a new sp post with any required/default
         *                       post components
         */
        function __construct($postID, $loadPost = false){
            if($loadPost){
                self::load($postID);
            }else{
                // Instantiate the post with any required/default components
                $wpPost = get_post($postID);
                if( !is_null($wpPost) ){

                    // Get the sp_category if there is one
                    $sp_category       = self::get_sp_category($postID);
                    $this->wpPost 	   = get_post($postID);
                    $this->sp_category = $sp_category;
                    $this->components  = array();

                    // Add any required or default components
                    if( !is_null( $this->sp_category ) ){
                        $catComponents = $this->sp_category->getComponents();

                        if( !empty($catComponents) ){
                            foreach($catComponents as $catComp){
                                if($catComp->getRequired() || $catComp->getDefault()){
                                    $catCompID = $catComp->getID();
                                    // TODO: this calls save() multiple times for each component, which is inefficient
                                    $this->add_component($catCompID);
                                }
                            }
                        }
                    }
                }else{
                    $this->errors = new WP_Error('broke', ('Could not load WP post properly'));
                }
            }
        }

        /**
         * Loads an existing SP Post based off of $postID
         *
         * @param  int    $postID The ID of the post
         * @return object Returns a sp_post object
         */
        function load($postID){
            $sp_category = self::get_sp_category($postID);

            //See if we succesfully loaded the sp category
            if(is_wp_error($sp_category->errors)){
                $this->errors = $sp_category->errors;
            }else if(is_object($sp_category)){
                //If yes, define member variables
                $this->wpPost      = get_post($postID);
                $this->sp_category = $sp_category;
                $this->components  = self::get_components_from_ID($postID);
            }else{

                //Otherwise something went wrong
                $this->errors = new WP_Error('broke', ('Could not load a valid SP Category.'));
            }
        }

        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postAJAX.php');
            sp_postAJAX::init();
            add_filter( 'the_content', array('sp_post', 'renderPost'));
            add_filter( 'category_save_pre', array('sp_post', 'validatePostCatSave'), 10);
            add_action( 'new_to_publish', array('sp_post', 'newSPPost'));
            add_action( 'new_to_draft', array('sp_post', 'newSPPost'));
            add_action( 'before_delete_post', array('sp_post', 'delete'));
            add_action( 'admin_notices', array('sp_post', 'cat_save_error'));
            add_action( 'pre_get_posts', array('sp_post', 'excludeDeletedPosts'));
            add_action( 'sp_updates', array('sp_post', 'update_sp_posts') );
            add_shortcode( 'sp-components', array('sp_post' , 'sp_components_shortcode') );
            self::enqueue_sp_post_js();
            self::enqueue_sp_post_css();
        }

        /**
         * Enqueue JS scripts
         */
        static function enqueue_sp_post_js(){
            wp_register_script( 'magnific-popup', plugins_url('/js/magnific-popup/jquery.magnific-popup.min.js', __FILE__), array( 'sp_postGalleryJS' ) );
            wp_enqueue_script( 'magnific-popup' );

            wp_register_script('sp_postJS', plugins_url('/js/sp_post.js', __FILE__));
            wp_enqueue_script('sp_postJS');
        }

        /**
         * Enqueue CSS scripts
         */
        static function enqueue_sp_post_css(){
            wp_register_style( 'magnific-popup-css', plugins_url('js/magnific-popup/magnific-popup.css', __FILE__) );
            wp_enqueue_style( 'magnific-popup-css' );

            wp_register_style( 'sp_postCSS', plugins_url('/css/sp_post.css', __FILE__) );
            wp_enqueue_style( 'sp_postCSS' );
        }

        /**
         * Hooks into the 'sp_update' action (see sp_updates.php).
         * @param $sp_site_version
         */
        static function update_sp_posts( $sp_site_version ){

            /**
             * Conversion from 2.x to 2.3+. Introduction of the [sp-components][/sp-components] shortcode.
             * This update wraps all [sp_component] shortcodes with the [sp-components] tag.
             * e.g. [sp_component id="1"][sp_component id="2"] becomes [sp-components][sp_component id="1"][sp_component id="2"][/sp-components].
             * The [sp-components] shortcode wraps the components in a sortable div, allowing users to add new components, and re-order them.
             */
            if( $sp_site_version == "2.2" || $sp_site_version === false ){
                $sp_cat_ids = get_option( 'sp_categories' );

                // exist if no sp_cat_ids exist
                if( !is_array( $sp_cat_ids ) || empty( $sp_cat_ids ) ){
                    return;
                }

                foreach($sp_cat_ids as $cat_id){
                    $sp_posts = get_posts( array( 'category' => $cat_id, 'numberposts' => -1, 'post_status' => 'publish|draft|trash' ) );
                    if( !empty( $sp_posts ) ){
                        foreach( $sp_posts as $post ){
                            $content = $post->post_content;
                            if( !has_shortcode( $content, 'sp-components') ){
                                $pattern = '/\[sp_component id=\"[0-9]+\"\]/';
                                preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE);

                                $first_match = $matches[0][0];
                                $first_match_start = $first_match[1]; // Start position of the first match

                                $last_match = $matches[0][ count($matches[0]) - 1 ];
                                $last_match_start = $last_match[1];
                                $last_match_end = $last_match_start + strlen($last_match[0]);

                                $before_html = substr($content, 0, $first_match_start); // Get all the content before the first match of [sp_component]
                                $component_shortcodes = substr($content, $first_match_start, $last_match_end - $first_match_start ); // Get everything in between the first [sp_component] and the last
                                $after_html = substr($content, $last_match_end ); // Get everything after the last [sp_component] match

                                $new_content = $before_html . '[sp-components]' . $component_shortcodes . '[/sp-components]' . $after_html;
                                $post->post_content = $new_content;
                                wp_update_post( $post );
                            }
                        }
                    }
                }
            }
        }

        /**
         * Filter for category_pre_save (runs before categories are saved to WP DB).
         * Checks for any changes to a SP post
         *
         * @uses global $post The current post that's being updated
         */
        function validatePostCatSave($newPostCats){
            global $post;
            $spCatCount = 0;
            $sp_categories = get_option('sp_categories');
            $currentPostCats = get_the_category($post->ID);

            //Check if the post is getting updated to more than 1 SP category
            if( !empty($newPostCats) ){
                foreach($newPostCats as $newCat){
                    if(in_array($newCat, $sp_categories)){
                        $spCatCount++;
                    }
                }
            }

            if($spCatCount > 1){
                //Show admin/dashboard  error
                update_option('sp_cat_save_error', true);

                //Don't update the post with multiple SP categories
                $currentPostCatIDs = array();
                foreach($currentPostCats as $currentCat){
                    array_push($currentPostCatIDs, $currentCat->term_id);
                }
                return $currentPostCatIDs;
            }elseif($spCatCount == 1){
                return $newPostCats;
            }
            return $newPostCats;
        }

        /**
         * Displays an error if more then 1 sp category was attempted to
         * be added to a post.
         */
        function cat_save_error(){
            $sp_cat_save_error = get_option('sp_cat_save_error');
            if($sp_cat_save_error){
                $html = "";
                $html .= '<div class="error">';
                $html .= '<p>';
                $html .= "Error: SmartPost currently cannot handle categorizing a post " .
                    "under two or more SmartPost categories, please limit the post to one (1) " .
                    "SmartPost categories when saving a post.";
                $html .= '</p>';
                $html .= '</div>';
                echo $html;
            }
            delete_option('sp_cat_save_error');
        }

        /**
         * Adds post component shortcodes to the post
         *
         * @param int $postID the ID of the post to save
         * @uses wp_update_post()
         */
        static function saveShortcodes($postID){
            $post = get_post($postID);
            if(self::is_sp_post($post->ID)){
                $sp_post = new sp_post($post->ID, true);
                $postComponents = $sp_post->getComponents();
                $shortCodes = '';
                foreach($postComponents as $postComponent){
                    $compID = $postComponent->getID();
                    $shortCodes .= '[sp_component id="' . $compID . '"]';
                }
                $updatedPost['ID'] = $post->ID;
                $new_sp_content = '[sp-components]' . $shortCodes . '[/sp-components]';
                if( has_shortcode($sp_post->wpPost->post_content, 'sp-components') ){
                    $updatedPost['post_content'] = sp_core::replace_shortcode('sp-components', $new_sp_content, $sp_post->wpPost->post_content);
                }else{
                    $updatedPost['post_content'] = $sp_post->wpPost->post_content . $new_sp_content;
                }
                wp_update_post($updatedPost);
            }
        }

        /**
         * Same as saveShortcodes() but dynamically bound
         * @see saveShortcodes()
         */
        function save(){
            $postComponents = $this->components;
            $shortCodes = '';
            foreach($postComponents as $postComponent){
                $compID = $postComponent->getID();
                $shortCodes .= '[sp_component id="' . $compID . '"]';
            }
            $updatedPost['ID'] = $this->wpPost->ID;
            $new_sp_content = '[sp-components]' . $shortCodes . '[/sp-components]';

            if( has_shortcode($this->wpPost->post_content, 'sp-components') ){
                $updatedPost['post_content'] = sp_core::replace_shortcode('sp-components', $new_sp_content, $this->wpPost->post_content);
            }else{
                $updatedPost['post_content'] = $this->wpPost->post_content . $new_sp_content;
            }
            wp_update_post($updatedPost);
        }

        /**
         * Action hook for wp_insert_post, called whenever a new post is created
         * @link http://codex.wordpress.org/Plugin_API/Action_Reference/save_post
         * @param $post
         * @return null|sp_post
         */
        function newSPPost($post){
            $sp_post = null;
            if(self::is_sp_post($post->ID) &&  !wp_is_post_revision( $post->ID )){
                $sp_post = new sp_post($post->ID);
            }
            return $sp_post;
        }

        /**
         * Action hook for 'before_delete_post'.
         * @todo Allow for administrators to designate anonymous author
         * @todo Figure out a way to cleanup deleted parents/children
         * @link http://codex.wordpress.org/Plugin_API/Action_Reference
         */
        function delete($postID){
            if(self::is_sp_post($postID)){
                $sp_post    = new sp_post($postID, true);
                $components = $sp_post->getComponents();
                $wpPost     = $sp_post->wpPost;

                if(!empty($components)){
                    foreach($components as $component){
                        $component->delete();
                    }
                }

                //Find the children of the post we're deleting
                $childArgs = array( 'numberposts' => -1, 'post_parent' => $postID, 'post_status' => 'publish' );
                $children  = get_posts( $childArgs );

                if( !empty($children) ){

                    //!To-do: lookup annonymous author ID via save option instead of hardcoding
                    $anonymousUser = 'siteuser';
                    $anonymousUser = get_user_by('login', $anonymousUser);
                    $anonymousUser = !$anonymousUser ? 1 : $anonymousUser;

                    //Remove post content/title
                    $wpPost->post_content = 'This post has been deleted.';
                    $wpPost->post_title   = '[deleted]';
                    $wpPost->post_author  = $anonymousUser;
                    $wpPost->comment_status = 'closed';
                    wp_update_post($wpPost);

                    //Add post_meta to indicate the post has been removed
                    add_post_meta($wpPost->ID, 'sp_deleted', true, true);
                    exit("Post deleted successfully.");
                }
            }
        }

        /**
         * Breadcrumb function that traverses the post tree. Starting at
         * the post with id $post_id all the way to the root, and outputs
         * a linkable breadcrumbs to ancestor posts. It omits a breadcrumb
         * if there are no ancestors for the current post.
         *
         * @param $post_id The ID of the post to start with
         * @param bool $traversing
         * @return string The HTML representations of the breadcrumbs
         */
        static function sp_breadcrumbs($post_id, $traversing = false){
            $post = get_post($post_id);
            if( !empty($post->post_parent) ){
                return self::sp_breadcrumbs($post->post_parent, true) . '&rsaquo;' . ' <a id="breadcrumb-' . $post->ID . '" href="' . get_permalink($post->ID) . '" >' . stripslashes($post->post_title) . '</a> ';
            }elseif( empty($post->post_parent) && $traversing){
                return ' <a "breadcrumb-' . $post->ID . '" href="' . get_permalink($post->ID) . '" >' . stripslashes($post->post_title) . '</a> ';
            }
        }

        /**
         * Action hook for 'pre_get_posts'. Excludes "deleted" posts from
         * the front page. Deleted posts will still be viewable as post responses
         * to maintain hierarchy.
         */
        function excludeDeletedPosts( $query ){
            if( !$query->is_single && !$query->is_admin ){
                $args = array( 'post_type'   => 'post',
                    'post_status' => 'publish',
                    'meta_key'    => 'sp_deleted',
                    'fields'      => 'ids',
                    'stop_propagation' => true
                );

                //Stop infinite loop
                remove_action('pre_get_posts', array('sp_post', 'excludeDeletedPosts'));
                $deletedPosts = new WP_Query($args);
                add_action('pre_get_posts', array('sp_post', 'excludeDeletedPosts'));

                $query->set('post__not_in', $deletedPosts->posts );
            }
        }

        /**
         * Shortcode that pads the components with a sortable div
         */
        function sp_components_shortcode($atts, $content = ""){
            global $post;
            if( sp_post::is_sp_post( $post->ID ) ){
                $edit_mode = $_GET['edit_mode'];
                $add_sortable_class = $edit_mode ? 'sp-component-stack' : '';

                // HTML padding before
                $html_before = '<div class="clear"></div>';
                $html_before .= '<div id="spComponents" class="sortableSPComponents ' . $add_sortable_class . '">';

                // HTML padding after
                $html_after = '</div><!-- end #spComponents -->';
                $html_after .= '<input type="hidden" id="postID" name="postID" value="' . $post->ID . '" />';
                $html_after .= '<div class="clear"></div>';

                return $html_before . do_shortcode( $content ) . $html_after;
            }
            return $content;
        }

        /**
         * Filter for the_content
         *
         * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/the_content)
         * @param string $content The content prior to filtering
         * @return string        The content after filtering
         */
        function renderPost($content){
            global $post;

            if( self::is_sp_post($post->ID) ){
                $sp_post = new sp_post($post->ID, true);

                if( current_user_can( 'edit_post', $post->ID ) && is_single() ){

                    // Check for post errors
                    $errors = '';
                    if( is_wp_error($sp_post->errors) ){
                        $errors = '<p>' . $sp_post->errors->get_error_message() . '</p>';
                    }

                    // Check if post is currently locked for editing
                    require_once(ABSPATH . 'wp-admin/includes/post.php');
                    $isLocked = wp_check_post_lock( $post->ID );
                    if( $isLocked > 0 ){
                        $lockUser = get_userdata($isLocked);
                        $user   = '<a href="' . get_author_posts_url($lockUser->ID) . ' ">' . $lockUser->first_name . ' ' . $lockUser->last_name . '</a>';
                        $errors = '<p>Warning: post is currently being edited by ' . $user . '.';
                    }else{
                        wp_set_post_lock($post->ID);
                    }

                    //Add an errors div and display errors if necessary
                    $content = '<div id="component_errors"' . (!empty($errors) ? ' style="display: block;"' : '') . '>' . $errors . '<span id="clearErrors" class="sp_xButton"></span></div>' . $content;
                }

                if( !is_singular() || is_home() ){
                    // load the components
                    $postComponents = $sp_post->getComponents();
                    $content .= '<div class="clear"></div>';
                    $content .= '<div id="spComponents" class="sortableSPComponents">';
                    foreach($postComponents as $postComponent){
                        $content .= $postComponent->render();
                    }
                    $content .= '</div><!-- end #spComponents -->';
                    $content .= '<input type="hidden" id="postID" name="postID" value="' . $post->ID . '" />';
                    $content .= '<div class="clear"></div>';
                }

            }
            return $content;
        }

        /**
         * Adds any required components if the count is < 1
         */
        function addRequiredComponents(){
            $reqdComps = $this->sp_category->getRequiredComps();
            if($this->countRequired() < 1 && count($reqdComps) > 0){
                foreach($reqdComps as $reqdComp){
                    $catCompID = $reqdComp->getID();
                    $this->add_component($catCompID);
                }
            }
        }

        /**
         * Adds any default components if the count is < 1
         */
        function addDefaultComponents(){
            $defaultCatComps = $this->sp_category->getDefaultComps();
            if($this->countDefault() < 1 && count($defaultCatComps) > 0){
                foreach($defaultCatComps as $defaultComp){
                    $catCompID = $defaultComp->getID();
                    $this->add_component($catCompID);
                }
            }
        }

        /**
         * Returns number of required components in a post. If it cannot find
         * a valid catCompID (i.e. a category component), then it will return 0.
         * This may occur if a the category component has been deleted.
         *
         * @return int Number of required components.
         */
        function countRequired(){
            global $wpdb;
            $postID        = $this->wpPost->ID;
            $reqdComps     = $this->sp_category->getRequiredComps();
            $requiredCount = 0;
            if( !empty($reqdComps)){
                $tableName = $wpdb->prefix . 'sp_postComponents';
                foreach($reqdComps as $reqdComp){
                    $catCompID = $reqdComp->getID();
                    $requiredCount += $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName WHERE postID = %d AND catCompID = %d;", $postID, $catCompID ) );
                }
                return $requiredCount;
            }else{
                return 0;
            }
        }

        /**
         * Returns number of default components in a post. If it cannot find
         * a valid catCompID (i.e. a category component), then it will return 0.
         * This may occur if a the category component has been deleted
         *
         * @return int Number of default components.
         */
        function countDefault(){
            global $wpdb;
            $tableName    = $wpdb->prefix . 'sp_postComponents';
            $postID       = $this->wpPost->ID;
            $defaultComps = $this->sp_category->getDefaultComps();
            $defaultCount = 0;
            if( !empty($defaultComps)){
                foreach($defaultComps as $defaultComp){
                    $catCompID = $defaultComp->getID();
                    $defaultCount += $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName WHERE postID = %d AND catCompID = %d;", $postID, $catCompID ) );
                }
                return $defaultCount;
            }else{
                return 0;
            }
        }

        function countByCatCompID($catCompID){
            global $wpdb;
            if(!empty($catCompID)){
                $postID = $this->wpPost->ID;
                $tableName = $wpdb->prefix . 'sp_postComponents';
                $compCount = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName	WHERE postID = %d AND catCompID = %d;", $postID, $catCompID ) );
                return $compCount;
            }else{
                return 0;
            }
        }

        /**
         * Returns the post's SP Category object if the post is a SP-enabled post, otherwise null.
         * If multiple SP categories exist, the function returns the first one it finds.
         *
         * @param $postID
         * @return null|sp_category sp_category object if it's a SP-post, otherwise null
         */
        public static function get_sp_category($postID){
            $sp_categories = get_option('sp_categories');
            if( !empty($sp_categories) ){
                $categories = get_the_category($postID);
                foreach($categories as $category){
                    if(in_array($category->term_id, $sp_categories)){
                        return new sp_category(null, null, $category->term_id);
                    }
                }
            }
            return null;
        }

        /**
         * Returns true if the post is a SP-enabled post, false otherwise
         *
         * @param int $postID the post's ID
         * @return bool true if the post is a SP, false otherwise
         */
        public static function is_sp_post($postID){
            $sp_categories = get_option('sp_categories');
            if( !empty($sp_categories) ){
                $categories = get_the_category($postID);
                foreach($categories as $category){
                    if(in_array($category->term_id, $sp_categories)){
                        return true;
                    }
                }
            }
            return false;
        }

        /**
         * @todo figure out "standalone/unrelated" components later
         * Same as add_component(), except that the component may not exist in a
         * parent template.
         * @param $comp_type
         * @param $cat_comp_id
         * @param string $name
         * @param string $value
         * @param null $post_id
        function add_unrelated_component($comp_type = '', $cat_comp_id = null, $name = '', $value = '', $post_id = null){

            // check if the category component exists in a template
            $type = sp_catComponent::get_comp_type_from_id( $cat_comp_id );
            if( empty( $type ) ){
                // if it doesn't exist, add it as a "standalone/unrelated" component


            }else{
                // if it does exist somewhere, use add_component
                self::add_component( $cat_comp_id, $name, $value, $post_id );
            }
        }
        */

        /**
         * Adds a post component to the sp post based off of it's category component ID.
         * Returns the components ID on success, otherwise a WP_Error object on failure.
         *
         * @param $catCompID int The post component's category component ID
         * @param string $name
         * @param string $value
         * @param int $postID
         * @return WP_Error|int The components ID on success, otherwise WP_Error object on failure
         */
        function add_component($catCompID, $name = '', $value = '', $postID = null){

            //We need the catCompID or else this won't flow
            if(empty($catCompID) || $catCompID <= 0 ){
                return new WP_Error('broke', ('Category component ID missing.'));
            }

            $post_comp_type = '';
            $compOrder = self::getNextOrder();
            $type      = sp_catComponent::get_comp_type_from_id($catCompID);
            if( !empty( $type ) ){
                $post_comp_type  = 'sp_post' . $type;
            }

            //In case the class doesn't exist or no constructor method was declared.
            if( !class_exists($post_comp_type) ){
                return new WP_Error('broke', ('Could not instantiate component. Please make sure a ' . $post_comp_type . ' class exists and has constructor.'));
            }

            if( empty($postID) ){
                $postID = $this->wpPost->ID;
            }

            $postComponent = new $post_comp_type(0, $catCompID, $compOrder, $name, $value, $postID);

            if(is_wp_error($postComponent->errors)){
                $errors = $postComponent->errors;
                $postComponent->delete(); //delete the component since something went wrong
                return $errors;
            }else{
                if(empty($this->components)){
                    $this->components = array();
                }
                array_push($this->components, $postComponent);
                $this->save();
                return $postComponent->getID();
            }
        }

        /**
         * Looks up the postID in sp_postComponents and returns an array
         * of sp_postComponent objects
         *
         * @param  int   $postID the ID of the post
         * @return array An array of sp_postComponent objects
         */
        static function get_components_from_ID($postID){
            global $wpdb;
            $postComponents = array();
            $tableName = $wpdb->prefix . 'sp_postComponents';
            $componentResults = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableName WHERE postID = %d order by compOrder ASC;", $postID ) );

            if( !empty($componentResults) ){
                $postComponents = array();
                $i = 0;
                foreach( $componentResults as $rawComponent ){
                    $post_comp_type = 'sp_post' . sp_core::get_type_name($rawComponent->typeID);
                    if( class_exists( $post_comp_type) ){
                        $sp_postComponent = new $post_comp_type($rawComponent->id);
                        $postComponents[$i++] = $sp_postComponent;
                    }else if( SP_DEBUG ){
                        error_log('SmartPost Error: "' . $post_comp_type . '" class does not found. In ' . __FILE__ . ', line: ' . __LINE__ );
                    }
                }
            }
            return $postComponents;
        }

        /**
         * Returns the post component with ID of $id
         *
         * @param  int   $id The post component ID we're searching for
         * @return object A sp_postComponent object with the right ID, otherwise
         *                a WP_Error object
         */
        function getComponentByID($id){
            foreach($this->components as $component){
                if($component->getID() == $id){
                    return $component;
                }
            }
            return new WP_Error ('notFound', __("Could not find a component by the ID: " .
            $id));
        }

        /**
         * Returns the next position for a component (autoincrements 'compOrder' in sp_postComponent table)
         *
         * @return int The next position for a component
         */
        private function getNextOrder(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_postComponents';
            $postID = $this->wpPost->ID;
            $sql = $wpdb->prepare( "SELECT MAX(compOrder) FROM $tableName where postID = %d", $postID );
            $nextOrder = (int) $wpdb->get_var( $sql ) + 1;
            return $nextOrder;
        }

        /**
         * Sets the new component order
         *
         * @param  array       $compOrder an array of the form [ 0 => compID1, 1 => compID2]
         * @return bool|object Returns a WP_Error object on failure, otherwise true on success
         */
        function setCompOrder($compOrder){
            $numOfComps = (int) $this->getCompCount();

            if(count($compOrder) !== $numOfComps){
                return new WP_Error('broke', ('Number of components do not match.'));
            }else{
                $newOrder = array_values($compOrder);

                foreach($newOrder as $order => $compID){
                    $component = $this->getComponentByID($compID);
                    if(!is_wp_error($component)){
                        $component->setCompOrder($order);
                        $newOrder[$order] = $component;
                    }
                }

                $this->components = $newOrder;
                $this->save();
            }
            return true;
        }

        /**
         * Returns the component count of the sp_post
         *
         * @return int The number of components based off of $this->wpPost->ID
         */
        function getCompCount(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_postComponents';
            $postID = $this->wpPost->ID;
            return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $tableName where postID = %d;", $postID ) );
        }

        function getwpPost(){
            return $this->wpPost;
        }

        function getsp_category(){
            return $this->sp_category;
        }

        function getComponents(){
            return $this->components;
        }


    }
}
?>