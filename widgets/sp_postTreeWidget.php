<?php
/**
 * sp_postTreeWidget used to add a new post quickly from the front end
 */
class sp_postTreeWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP Post Tree');
        self::init();
    }

    function init(){
        require_once('ajax/sp_postTreeWidgetAJAX.php');
        sp_postTreeWidgetAJAX::init();
        self::enqueueCSS();
        self::enqueueJS();
    }

    static function enqueueCSS(){
        wp_register_style( 'sp_postTreeWidgetCSS', plugins_url('css/sp_postTreeWidget.css', __FILE__) );
        wp_enqueue_style( 'sp_postTreeWidgetCSS' );
    }

    static function enqueueJS(){
        wp_register_script( 'sp_postTreeWidgetJS', plugins_url('js/sp_postTreeWidget.js', __FILE__) );
        wp_enqueue_script( 'sp_postTreeWidgetJS',    null, array( 'jquery-dynatree', 'sp_globals', 'sp_postComponentJS' ) );
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        $html = '<div id="sp_catTree-' . $this->id . '" data-widgetid="' . $this->id . '" class="sp_catTree"></div>';

        if ( is_category() ) {
            $html .= '<input type="hidden" id="sp_postTreeCatID" value="' . get_query_var('cat') . '" />';
        }else if( is_single() ){
            global $post;
            $html .= '<input type="hidden" id="sp_postTreePostID" value="' . $post->ID . '" />';
        }

        $title = apply_filters('widget_title', $instance['title']);
        echo $title;
        echo $html;
    }

    /**
     * @param $postArgs
     * @return array
     */
    static function buildPostTree( $postArgs ){

        $posts = get_posts( $postArgs );

        // Collect the post nodes
        $postNodes = array();

        if( !empty($posts) ){

            foreach($posts as $post){
                $postNode = new stdClass();
                $postNode->title = $post->post_title;
                $postNode->key   = 'post-' . $post->ID;
                $postNode->postID = $post->ID;
                $postNode->href   = get_permalink( $post->ID );
                $postNode->target = '_self';

                // Get post responses
                $args['post_parent'] = $post->ID;
                $postNode->children = self::buildPostTree( $args );

                array_push($postNodes, $postNode);
            }
        }
        return $postNodes;
    }

    /**
     * Renders HTML ul list of representing the category hierarchy (i.e. subcategories and their posts)
     * @param $catArgs - Query args used for displaying the categories
     * @param $postArgs - Query args used for displaying posts. If this is false, posts will not be shown.
     * @param int $parent - The category parent to start the search with, 0 will query all 'top-level' categories
     * @return array - array representation of category hierarchy and category posts
     */
    static function buildCatDynaTree( $catArgs, $postArgs = array(), $parent = 0 ){

        $catArgs['parent'] = $parent;
        $categories = get_categories( $catArgs );

        // Build category tree
        $catTree = array();

        foreach( $categories as $category ) {

            // Build the category node
            $catNode = new stdClass();
            $catNode->title    = $category->name;
            $catNode->key      = 'cat-' . $category->term_id;
            $catNode->isFolder = true;
            $catNode->catID    = $category->term_id;
            $catNode->href     = get_category_link( $category->term_id );
            $catNode->target   = '_self';

            // Get the posts of the category
            if( $postArgs !== false ){
                $postArgs = array_merge( $postArgs, array( 'category' => $category->term_id ) );
                $catPosts = self::buildPostTree( $postArgs );
            }

            $catNode->children = self::buildCatDynaTree( $catArgs, $postArgs, $category->term_id );

            if( !empty( $catPosts ) && $postArgs !== false ){
                $catNode->children = array_merge_recursive( $catPosts, $catNode->children );
            }

            array_push( $catTree, $catNode );
        }
        return $catTree;
    }

    /**
     * Helper function to render the hierarchy category tree
     * @param $args
     * @param int $parent
     * @param array $displayCats
     * @return string
     */
    private function renderWidgetTree( $args,  $displayCats, $parent = 0 ){
        $args['parent'] = $parent;
        $categories = get_categories( $args );

        $id      = $this->get_field_id( 'displayCats' );
        $name    = $this->get_field_name( 'displayCats' );

        $html = '';
        if( !empty($categories) && is_array( $displayCats ) ){

            $html = '<ul>';
            foreach( $categories as $category ) {
                $checked = in_array($category->term_id, $displayCats) ? 'checked="checked"' : '';
                $html .= '<li>';
                    $html .= '<input type="checkbox" id="' . $id . '[]" name="' . $name .'[]" value="' . $category->term_id . '" ' . $checked . ' />';
                    $html .= $category->name;
                    $html .= self::renderWidgetTree( $args, $displayCats, $category->term_id );
                $html .= '</li>';
            }
            $html .= '</ul>';
        }
        return $html;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = strip_tags( $new_instance['title'] );
        $instance['displayCats'] = $new_instance['displayCats'];
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'text_domain' );
        }

        if( !isset( $instance['displayCats'] ) ){
            $instance['displayCats'] = array();
        }

        $args = array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            Check off the categories you'd like to <span style="font-size:16px; font-weight: bold; text-decoration: underline;">HIDE</span> on the front-end.
        </p>
        <p><b><u>Note:</u></b> checking off a parent category will hide all of its children.</p>
        <div id="sp-cat-tree-container-<?php echo $this->id ?>" data-widgetid="<?php echo $this->id ?>" class="sp-widget-cat-tree-container">
            <?php echo $this->renderWidgetTree( $args, $instance['displayCats'] ); ?>
        </div>
    <?php
    }

}
?>