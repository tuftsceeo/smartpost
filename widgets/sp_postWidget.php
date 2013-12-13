<?php
/**
 * sp_postWidget used to add new post components to posts
 */
class sp_postWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP Post Options');
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
        if(is_user_logged_in() && current_user_can('edit_posts')){
            $owner = ($current_user->ID == $post->post_author);
            $admin = current_user_can('administrator');

            require_once(ABSPATH . 'wp-admin/includes/post.php');
            $isLocked = (bool) wp_check_post_lock( $this->postID );

            extract( $args );
            $title = apply_filters('widget_title', $instance['title']);


            //Render the widget
            echo $before_widget;
            echo $before_title . $title . $after_title;
            echo '<div class="clear"></div>';

            //Load post components
            if( sp_post::isSPPost($post->ID) && ($owner || $admin) && !$isLocked && is_single()){
                self::setFeaturedImage();
                self::postStatusOptions();
                self::renderCompBlocks();
                echo '<div class="clear"></div>';
            }
            echo $after_widget;
        }
    }

    /**
     * Featured Image
     * !To-do: Allow for image uploads and fix workaround
     */
    function setFeaturedImage(){
        global $post;
        $featuredImgID = get_post_thumbnail_id($post->ID);

        echo '<p class="sp_widgetSubHeader">Set Featured Image</p>';
        echo '<div id="sp_featuredImg">';
        echo '<div id="thumb_results">';

        if($featuredImgID){
            /**
             * Bug: thumbs from other posts show up in incorrect posts
             */
            $featuredImg = get_post($featuredImgID);
            if( $featuredImg->post_parent != $post->ID ){
                set_post_thumbnail($post->ID, -1);
            }else{
                echo get_the_post_thumbnail( $post->ID, array(100, 100), array('data-id' => $featuredImgID, 'class' => 'featuredImg') );
            }
        }

        $args = array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'post__not_in' => array($featuredImgID), 'numberposts' => -1, 'post_parent' => $post->ID, 'post_status' => null );
        $attachments = get_posts($args);
        if( $attachments ){
            foreach($attachments as $attachment){
                $thumbs .= wp_get_attachment_image( $attachment->ID, array(100, 100), true, array('style' => 'display:none;', 'data-id' => $attachment->ID, 'post-parent' => $attachment->post_parent) );
            }
            echo $thumbs;
        }
        echo '</div><!-- end #thumb_results -->';

        if( !$attachments ){
            $html .= '<div id="customFeaturedImage">';
            $html .= '<p id="ftImgFileDropLbl">Drag n\' drop a custom image</p>';
            $html .= '<input type="file" id="sp_custom_ft_img_browse" name="sp_custom_ft_img_browse" />';
            $html .= '</div>';
            echo $html;
        }

        if( $attachments ){
            $thumbSelection .= '<div id="thumbSelection-' . $post->ID . '" class="thumbSelection">';
            $thumbSelection .= '<button type="button" id="prevThumb" class="sp_link_prev"></button>';
            $thumbSelection .= '<button type="button" id="nextThumb" class="sp_link_next"></button>';
            $thumbSelection .= '<small>Select a thumbnail</small>';
            $thumbSelection .= '<button type="button" id="selectImg" class="sp_link_select_thumb">Ok</button>';
            $thumbSelection .= '</div>';
            echo $thumbSelection;
        }
        echo '</div><!-- end sp_featuredImg -->';
    }


    /**
     * Publishes or Deletes a draft if we're in a draft post
     */
    function postStatusOptions(){
        global $post;
        if( $post->post_status == 'draft' ){
            echo '<p class="sp_widgetSubHeader"> Draft Options </p>';
            echo '<button type="button" id="sp_publish_post" name="sp_publish_post" class="sp_qp_button">Publish Draft</button>';
            echo '<button type="button" id="sp_cancel_draft" name="sp_cancel_draft" class="sp_qp_button">Delete Draft</button>';
            echo '<input type="hidden" id="sp_qpPostID" name="sp_qpPostID" value="' . $post->ID . '" />';
            echo '<input type="hidden" id="sp_spDeleteRedirect" name="sp_spDeleteRedirect" value="' . get_bloginfo('url') . '" />';
        }
    }

    /**
     * Renders Post Component blocks that can be added to the post
     */
    function renderCompBlocks(){
        $sp_category = sp_post::getSPCategory($post->ID);
        $components  = $sp_category->getComponents();

        if( !empty($components) ){
            echo '<p class="sp_widgetSubHeader"> Drag to your post </p>';
            echo '<div id="catCompList">';
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