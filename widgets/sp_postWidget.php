<?php
/**
 * sp_postWidget used to add new post components to posts
 */
class sp_postWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP Post Settings');
        self::init();
    }

    static function init(){
        require_once('ajax/sp_postWidgetAJAX.php');
        sp_postWidgetAJAX::init();
        self::enqueueCSS();
        self::enqueueJS();
    }

    static function enqueueCSS(){
        wp_register_style('sp_postWidgetCSS', plugins_url('/css/sp_postWidget.css', __FILE__));
        wp_enqueue_style( 'sp_postWidgetCSS' );
    }

    static function enqueueJS(){
        wp_register_script('sp_postWidgetJS', plugins_url('/js/sp_postWidget.js', __FILE__), array( 'sp_globals', 'jquery-ui-tabs' ));
        wp_enqueue_script( 'sp_postWidgetJS' );
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        global $post;
        global $current_user;

        if( is_user_logged_in() && current_user_can('edit_posts') ){

            // initialize vars
            $before_widget = ''; $before_title = ''; $after_title = ''; $after_widget = '';
            extract( $args );

            require_once(ABSPATH . 'wp-admin/includes/post.php');

            /**
             * Go through appropriate checks to see if we can edit the post
             * @var $owner - Is the current user the owner of the post?
             * @var $admin - is the current user an administrator?
             * @var $isLocked - is the post being edited by someone else?
             * @var $editMode - does the edit_mode global var exist?
             */
            $owner = ($current_user->ID == $post->post_author);
            $admin = current_user_can( 'administrator' );
            $isLocked = (bool) wp_check_post_lock( $this->postID );

            if( sp_post::is_sp_post($post->ID) && ($owner || $admin) && !$isLocked && is_single()  ){

                $editMode = (bool) $_GET['edit_mode'];
                $title = apply_filters('widget_title', $instance['title']);
                ?>

                <?php echo $before_widget ?>
                <div id="sp-widget-post-settings-<? echo $this->number ?>" class="sp-widget-post-settings sp-widget">
                    <?php echo $before_title . $title . $after_title ?>
                    <?php if( $editMode ): ?>
                        <p><a href="<?php echo get_permalink( $post->ID ) ?>">Go back to viewing mode</a></p>
                        <?php self::postStatusOptions(); ?>
                        <?php self::renderCompBlocks(); ?>
                    <?php else: ?>
                        <p><a href="<?php echo get_permalink( $post->ID ) ?>/?edit_mode=true">Edit this post</a></p>
                    <?php endif; ?>
                </div><!-- end .sp-widget-post-settings -->
                <?php echo $after_widget ?>

                <?php
            }else{

            }
        }
    }

    /**
     * Publishes or Deletes a draft if we're in a draft post
     */
    function postStatusOptions(){
        global $post;
        ?>
        <?php if( $post->post_status == 'draft' ): ?>
            <p> Draft Options: </p>
            <button type="button" id="sp_publish_post" name="sp_publish_post" class="sp_qp_button">Publish Draft</button>
            <button type="button" id="sp_cancel_draft" name="sp_cancel_draft" class="sp_qp_button">Delete Draft</button>
        <?php endif; ?>
        <?php
    }

    /**
     * Renders Post Component blocks that can be added to the post
     */
    function renderCompBlocks(){
        global $post;
        $sp_category = sp_post::getSPCategory($post->ID);
        $components  = $sp_category->getComponents();

        if( !empty($components) ){
            echo '<p>← Drag the below widgets into your post </p>';
            echo '<div class="sp-widget-post-settings-draggable-comps">';
            if ( !empty($components)){
                foreach($components as $component){
                    echo '<div id="catComp-' . $component->getID() . '" data-compid="' . $component->getID() . '" data-typeid="' . $component->getTypeID() . '" title="' . $component->getDescription() . '" alt="' . $component->getDescription() . '" class="catComponentWidget">';

                    $icon = $component->getIcon();
                    if(!empty($icon)){
                        $icon_img = '<img src="' . $component->getIcon() . '" />';
                    }

                    echo '<h4>' . $icon_img . ' ' . $component->getName() . '</h4>';
                    echo '<div class="clear"></div>';
                    echo '</div>';
                }
            }
            echo '</div><!-- end #catCompList -->';
        }
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
            $title = __( 'Post Settings', 'text_domain' );
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