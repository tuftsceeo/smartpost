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
                //instantiate the post with any required/default components
                $wpPost = get_post($postID);
                if( !is_null($wpPost) ){

                    //Get the sp_category if there is one
                    $sp_category       = self::getSPCategory($postID);
                    $this->wpPost 	   = get_post($postID);
                    $this->sp_category = $sp_category;
                    $this->components  = array();

                    //Add any required or default components
                    if( !is_null($this->sp_category) ){
                        $catComponents = $this->sp_category->getComponents();

                        if( !empty($catComponents) ){
                            foreach($catComponents as $catComp){
                                if($catComp->getRequired() || $catComp->getDefault()){
                                    $catCompID = $catComp->getID();
                                    $this->addComponent($catCompID);
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
            $sp_category = self::getSPCategory($postID);

            //See if we succesfully loaded the sp category
            if(is_wp_error($sp_category->errors)){
                $this->errors = $sp_category->errors;
            }else if(is_object($sp_category)){
                //If yes, define member variables
                $this->wpPost      = get_post($postID);
                $this->sp_category = $sp_category;
                $this->components  = self::getComponentsFromID($postID);
            }else{

                //Otherwise something went wrong
                $this->errors = new WP_Error('broke', ('Could not load a valid SP Category.'));
            }
        }

        static function init(){
            require_once('ajax/sp_postAJAX.php');
            sp_postAJAX::init();
            add_filter( 'the_content', array('sp_post', 'renderPost'));
            add_filter( 'category_save_pre', array('sp_post', 'validatePostCatSave'), 10);
            add_action( 'new_to_publish', array('sp_post', 'newSPPost'));
            add_action( 'new_to_draft', array('sp_post', 'newSPPost'));
            add_action( 'before_delete_post', array('sp_post', 'delete'));
            add_action( 'admin_notices', array('sp_post', 'cat_save_error'));
            add_action( 'pre_get_posts', array('sp_post', 'excludeDeletedPosts'));
            self::enqueueJS();
            self::enqueueCSS();
        }

        static function enqueueJS(){
            wp_register_script('sp_postJS', plugins_url('/js/sp_post.js', __FILE__));
            wp_register_script('fancyBoxJS', plugins_url('/js/fancybox/source/jquery.fancybox.pack.js', __FILE__));
            wp_register_script('fancyBox_thumbs', plugins_url('/js/fancybox/source/helpers/jquery.fancybox-thumbs.js', __FILE__));
            wp_register_script('fancyBox_media', plugins_url('/js/fancybox/source/helpers/jquery.fancybox-media.js', __FILE__));
            wp_enqueue_script('sp_postJS');
            wp_enqueue_script('fancyBoxJS');
            wp_enqueue_script('fancyBox_thumbs');
            wp_enqueue_script('fancyBox_media');
        }

        static function enqueueCSS(){
            wp_register_style('sp_postCSS', plugins_url('/css/sp_post.css', __FILE__));
            wp_register_style('fancyBoxCSS', plugins_url('/js/fancybox/source/jquery.fancybox.css', __FILE__));
            wp_register_style('fancyBox_buttons', plugins_url('/js/fancybox/source/helpers/jquery.fancybox-buttons.css', __FILE__));
            wp_register_style('fancyBox_thumbs', plugins_url('/js/fancybox/source/helpers/jquery.fancybox-thumbs.css', __FILE__));
            wp_enqueue_style('sp_postCSS');
            wp_enqueue_style('fancyBoxCSS');
            wp_enqueue_style('fancyBox_buttons');
            wp_enqueue_style('fancyBox_thumbs');
        }

        /**
         * Filter for category_pre_save (runs before categories are saved to WP DB).
         * Checks for any changes to a SP post
         *
         * @uses global $post The current post that's being updated
         */
        function validatePostCatSave($newPostCats){
            global $post;
            $spCatCount        = 0;
            $sp_categories     = get_option('sp_categories');
            $currentPostCats   = get_the_category($post->ID);

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
                //if($newPostCats[1] != $currentPostCats[0]->term_id)
                //update_option('sp_convert_' . $post->ID, true);
            }
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
            if(self::isSPPost($post->ID)){
                $sp_post = new sp_post($post->ID, true);
                $postComponents = $sp_post->getComponents();

                foreach($postComponents as $postComponent){
                    $compID = $postComponent->getID();
                    $shortCodes = '[sp_component id="' . $compID . '"]';
                }
                $updatedPost['ID'] = $post->ID;
                $updatedPost['post_content'] = strip_shortcodes($post->post_content) . $shortCodes;
                wp_update_post($updatedPost);
            }
        }

        /**
         * Same as saveShortcodes but dynamically bound
         *
         * @see saveShortcodes()
         */
        function save(){
            $postComponents = $this->components;
            foreach($postComponents as $postComponent){
                $compID = $postComponent->getID();
                $shortCodes .= '[sp_component id="' . $compID . '"]';
            }
            $updatedPost['ID'] = $this->wpPost->ID;
            $updatedPost['post_content'] = strip_shortcodes($this->wpPost->post_content) . $shortCodes;
            wp_update_post($updatedPost);
        }

        /**
         * Action hook for wp_insert_post, called whenever a new post is created
         *
         * @link http://codex.wordpress.org/Plugin_API/Action_Reference/save_post
         * @param $post
         */
        function newSPPost($post){
            if(self::isSPPost($post->ID) &&  !wp_is_post_revision( $post->ID )){
                $sp_post = new sp_post($post->ID);
            }
        }

        /**
         * Action hook for 'before_delete_post'.
         * @todo Allow for administrators to designate anonymous author
         * @todo Figure out a way to cleanup deleted parents/children
         * @link http://codex.wordpress.org/Plugin_API/Action_Reference
         */
        function delete($postID){
            if(self::isSPPost($postID)){
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
                    $anonymousUser = !$anyonymousUser ? 1 : $anonymousUser;

                    //Remove post content/title
                    $wpPost->post_content = 'This post has been deleted.';
                    $wpPost->post_title   = '[deleted]';
                    $wpPost->post_author  = $anonymousUser;
                    $wpPost->comment_status = 'closed';
                    $success = wp_update_post($wpPost);

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
         * Filter for the_content
         *
         * @link http://codex.wordpress.org/Plugin_API/Filter_Reference/the_content)
         * @param string $content The content prior to filtering
         * @return string        The content after filtering
         */
        function renderPost($content){
            global $post;
            global $current_user;

            if(self::isSPPost($post->ID)){
                $owner = ($current_user->ID == $post->post_author);
                $admin = current_user_can('administrator');
                $sp_post = new sp_post($post->ID, true);

                if( ($owner || $admin) && is_single()){

                    //remove shortcodes since we don't want double rendering happening
                    $content = strip_shortcodes($content);

                    //Check for post errors
                    if( is_wp_error($sp_post->errors) ){
                        $errors = '<p>' . $sp_post->errors->get_error_message() . '</p>';
                    }

                    //Check if post is currently locked for editing
                    require_once(ABSPATH . 'wp-admin/includes/post.php');
                    $isLocked = wp_check_post_lock( $post->ID );

                    if( $isLocked > 0 ){
                        $lockUser = get_userdata($isLocked);
                        $user     = '<a href="' . get_author_posts_url($lockUser->ID) . ' ">' . $lockUser->first_name . ' ' . $lockUser->last_name . '</a>';
                        $errors  .= '<p>Warning: post is currently being edited by ' . $user . '.';
                    }else{
                        wp_set_post_lock($post->ID);
                    }

                    //Add an errors div and display errors if necessary
                    $content = '<div id="component_errors"' . (!empty($errors) ? ' style="display: block;"' : '') . '>' . $errors . '</div>' . $content;

                    //load the components
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

                if( ($post->post_status != 'draft') && is_single()){
                    $content .= $sp_post->renderResponsePosts();
                    $content = do_shortcode($content);
                }

                if( !is_singular() || is_home() || is_search() ){
                    //load the components
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


        function renderResponsePosts(){
            $responses = $this->sp_category->getResponseCats();

            $args = array( 'numberposts' => -1, 'post_parent' => $this->wpPost->ID, 'post_status' => $post_status );
            $my_query = new WP_Query( $args );

            $html = "";

            if(class_exists('sp_quickPostWidget')){
                $spQuickPost = new sp_quickPostWidget();
                $catResponseIDs = $this->sp_category->getResponseCats();

                if( !empty($catResponseIDs) ){
                    $responseCats['displayCats'] = array_keys($catResponseIDs);
                }
            }

            if( !empty( $responseCats['displayCats'] ) || $my_query->have_posts() ){

                $html .= '<div id="sp_responses-' . $this->wpPost->ID . '">';
                $html .= '<h3 class="sp_response_header"> Post Responses </h3>';

                if( !empty( $responseCats['displayCats'] )){
                    $html .= $spQuickPost->widget(array(), $responseCats, true, true);
                }

                if( $my_query->have_posts() ) {
                    global $wp_query;
                    $wp_query->is_single = false;
                    $wp_query->is_search = true;

                    $html .= '<div id="the_responses-' . $this->wpPost->ID . '" class="sp_post_responses">';
                    while ( $my_query->have_posts() ) {
                        $my_query->the_post();

                        $html .= '<article id="post-' . get_the_ID() . '" class="post type-post status-publish pw">';
                        if(has_post_thumbnail()){
                            $html .= get_the_post_thumbnail( get_the_ID(), array(100, 100), array('class' => 'alignleft'));
                            $divClass = 'class="content-col"';
                        }

                        $html .= '<div ' . $divClass . '>';
                        $html .= '<header>';

                        $html .= '<hgroup>';
                        $html .= '<h3 class="posttitle"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h1>';
                        $html .= '<h2 class="meta">';
                        $html .=	'by <a href="' . get_author_link(false, $post->post_author) . '">' . get_the_author() . '</a> • ' . get_the_time('M d, Y');

                        $category = get_the_category();
                        $html .= ' • <a href="' . get_category_link($category[0]->term_id) . '">' . $category[0]->name . '</a>';
                        $html .= '</h2>';
                        $html .= '</hgroup>';

                        $html .= '</header>';
                        $html .= '<div class="storycontent">';
                        $html .= get_the_excerpt();
                        $html .= '</div>';

                        $html .= '</div>';
                        $html .= '</article>';
                    }
                }

                $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end #sp_responses-' . $this->wpPost->ID . ' -->';
            }

            $wp_query->is_search = false;
            $wp_query->is_single = true;
            wp_reset_postdata();

            return $html;
        }


        /**
         * Adds any required components if the count is < 1
         */
        function addRequiredComponents(){
            $reqdComps = $this->sp_category->getRequiredComps();
            if($this->countRequired() < 1 && count($reqdComps) > 0){
                foreach($reqdComps as $reqdComp){
                    $catCompID = $reqdComp->getID();
                    $this->addComponent($catCompID);
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
                    $this->addComponent($catCompID);
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
                    $requiredCount += $wpdb->get_var( "SELECT COUNT(*) FROM $tableName WHERE postID = $postID AND catCompID = $catCompID;" );
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
                    $defaultCount += $wpdb->get_var( "SELECT COUNT(*) FROM $tableName WHERE postID = $postID AND catCompID = $catCompID;" );
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
                $compCount = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $tableName	WHERE postID = $postID AND catCompID = $catCompID;" );
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
        public static function getSPCategory($postID){
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
         * Finds the SP category from an array of category IDs (i.e. array( 0 => catID1, 1 => catID2, ...)
         * Otherwise returns null.
         *
         * @param array $categories Array of catIDs array(0 => catID1, 1 => catID2, ...)
         * @returns int the
         */
        function findSPCategories($categories){

        }

        /**
         * Returns true if the post is a SP-enabled post, false otherwise
         *
         * @para int $postID the post's ID
         * @return bool true if the post is a SP, false otherwise
         */
        function isSPPost($postID){
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
         * Adds a post component to the sp post based off of it's category component ID.
         * Returns the components ID on success, otherwise a WP_Error object on failure.
         *
         * @param $catCompID int The post component's category component ID
         * @param string $name
         * @param string $value
         * @param int $postID
         * @return WP_Error|int The components ID on success, otherwise WP_Error object on failure
         */
        function addComponent($catCompID, $name = '', $value = '', $postID = null){

            //We need the catCompID or else this won't flow
            if(empty($catCompID) || $catCompID <= 0 ){
                return new WP_Error('broke', ('Category component ID missing.'));
            }

            $compOrder = self::getNextOrder();
            $type      = sp_catComponent::getCompTypeFromID($catCompID);
            if( !empty($type) ){
                $postCompType  = 'sp_post' . $type;
            }

            //In case the class doesn't exist or no constructor method was declared.
            if( !class_exists($postCompType) ){
                return new WP_Error('broke', ('Could not instantiate component. Please make sure a ' . $postCompType . ' class exists and has constructor.'));
            }

            if( empty($postID) ){
                $postID = $this->wpPost->ID;
            }

            $postComponent = new $postCompType(0, $catCompID, $compOrder, $name, $value, $postID);

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
        static function getComponentsFromID($postID){
            global $wpdb;
            $postComponents = array();
            $tableName = $wpdb->prefix . 'sp_postComponents';
            $componentResults = $wpdb->get_results( "SELECT * FROM $tableName WHERE postID = $postID order by compOrder ASC;" );

            if( !empty($componentResults) ){
                $postComponents = array();
                $i = 0;
                foreach( $componentResults as $rawComponent ){
                    $postCompType         = 'sp_post' . sp_core::getTypeName($rawComponent->typeID);
                    $sp_postComponent     = new $postCompType($rawComponent->id);
                    $postComponents[$i++] = $sp_postComponent;
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
            $sql = "SELECT MAX(compOrder) FROM $tableName where postID = $postID";
            $nextOrder = (int) $wpdb->get_var($sql) + 1;
            return $nextOrder;
        }

        /**
         * Sets the new component order
         *
         * @param  array       $compOrder an array of the form [ 0 => compID1, 1 => compID2]
         * @return bool|object Returns a WP_Error object on failure, otherwise true on success
         */
        function setCompOrder($compOrder){
            global $wpdb;
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
            return $wpdb->get_var( "SELECT COUNT(*) FROM $tableName where postID = $postID" );
        }

        /**************************************
         * Getters/Setters																				*
         **************************************/

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