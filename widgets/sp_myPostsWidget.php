<?php
/**
 * sp_myPostsWidget used to allow the current
 * users to navigate their published / draft posts
 */
class sp_myPostsWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP My Posts');
        self::init();
    }

    static function init(){
        require_once( dirname( __FILE__ ) . '/ajax/sp_myPostsWidgetAJAX.php');
        if(class_exists('sp_myPostsWidgetAJAX')){
            sp_myPostsWidgetAJAX::init();
        }
        self::enqueueCSS();
        self::enqueueJS();
    }

    static function enqueueCSS(){
        wp_register_style('sp_myPostsWidgetCSS', plugins_url('/css/sp_myPostsWidget.css', __FILE__));
        wp_enqueue_style( 'sp_myPostsWidgetCSS' );
    }

    static function enqueueJS(){
        wp_register_script('sp_myPostsWidgetJS', plugins_url('/js/sp_myPostsWidget.js', __FILE__), array( 'sp_globals', 'jquery-ui-tabs' ));
        wp_enqueue_script( 'sp_myPostsWidgetJS' );
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $post;
        global $current_user;
        if(is_user_logged_in() && current_user_can('edit_posts')){

            extract( $args );
            $title = apply_filters('widget_title', $instance['title']);

            //Render the widget
            $html .= $before_widget;
            $html .= $before_title . $title . $after_title;
            $html .= '<div class="clear"></div>';
            $html .= self::renderMyPosts();
            $html .= $after_widget;
            echo $html;

        }
    }

    /**
     * Renders Published/Draft posts
     */
    function renderMyPosts(){
        global $current_user;

        $args  = array( 'numberposts' => -1, 'author' => $current_user->ID, 'post_status' => 'publish|draft' );
        $posts = get_posts($args);

        $drafts = $published = "";
        $pCount = $dCount = 0;
        if($posts){
            $deleteImgSrc = SP_IMAGE_PATH . '/no.png';
            foreach($posts as $post){
                $deleteImg  = '<img src="' . $deleteImgSrc . '" id="delete-' . $post->ID .'" name="' . $post->ID . '" class="sp_deletePost" data-postid="' . $post->ID . '"" alt="Delete Post" title="Delete Post" />';
                if($post->post_status == 'publish'){
                    $published .= '<li class="sp_myPost" data-status="published"><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a>' . $deleteImg . '</li>';
                    $pCount++;
                }elseif($post->post_status == 'draft'){
                    $drafts .= '<li class="sp_myPost" data-status="draft"><a href="' . get_permalink($post->ID) . '">' . $post->post_title . '</a>' . $deleteImg . '</li>';
                    $dCount++;
                }
            }
        }

        $html .= '<div id="myPosts" class="sp_myPosts">';
        $html .= '<ul class="tab_header">';
        $html .= '<li><a href="#sp_published">Published (<span id="publishCount">' . $pCount . '</span>)</a></li>';
        $html .= '<li><a href="#sp_drafts">Drafts (<span id="draftCount">' . $dCount . '</span>)</a></li>';
        $html .= '</ul>';

        $html .= '<div id="sp_published">';
        $html .= '<ul>';
        $html .= $published;
        $html .= '</ul>';
        $html .= '<div class="clear"></div>';
        $html .= '</div>';


        $html .= '<div id="sp_drafts">';
        $html .= '<ul>';
        $html .= $drafts;
        $html .= '</ul>';
        $html .= '<div class="clear"></div>';
        $html .= '</div>';
        $html .= '<div class="clear"></div>';
        $html .= '</div><!-- end #myPosts -->';

        return $html;
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = strip_tags( $new_instance['title'] );

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
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
    <?php
    }

}
?>