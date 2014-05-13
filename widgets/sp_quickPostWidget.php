<?php
/**
 * sp_quickPostWidget used to add a new post quickly from the front end
 */
class sp_quickPostWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct( 'sp_quickpostwidget', 'SP QuickPost' );
        self::init();
    }

    static function init(){
        require_once( dirname( __FILE__ ) . '/ajax/sp_quickPostWidgetAJAX.php');
        sp_quickPostWidgetAJAX::init();
        self::enqueueCSS();
        self::enqueueJS();
    }

    static function enqueueCSS(){
        wp_register_style('jquery-ui-theme', plugins_url('css/jquery-ui-theme/jquery-ui-1.8.21.custom.css', dirname(__FILE__)));
        wp_register_style('sp_quickPostWidgetCSS', plugins_url('css/sp_quickPostWidget.css', __FILE__));
        wp_enqueue_style( 'jquery-ui-theme' );
        wp_enqueue_style( 'sp_quickPostWidgetCSS' );
    }

    static function enqueueJS(){
        if( is_admin() ){
            wp_register_script('sp_qpWidgetAdminJS', plugins_url('js/adminjs/sp_qpWidgetAdmin.js', __FILE__));
            wp_enqueue_script( 'sp_qpWidgetAdminJS', array( 'jquery', 'sp_postComponentJS' ) );
        }else{
            wp_register_script('sp_quickPostWidgetJS', plugins_url('js/sp_quickPostWidget.js', __FILE__));
            wp_enqueue_script( 'sp_quickPostWidgetJS', array( 'jquery', 'sp_postComponentJS' ) );
        }
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance, $returnHTML = false, $responseQP = false) {
        extract( $args );
        $catMode = $instance['categoryMode'];
        $thisCat = get_category( get_query_var('cat'), false);

        // Don't return anything if we're not in a category AND category mode is enabled
        if( $catMode && !sp_category::isSPCat($thisCat->term_id) ){
            return '';
        }

        //Load post components
        if( current_user_can('edit_posts') ){

            if( !$catMode && empty($instance['displayCats']) ){
                if( current_user_can( 'edit_dashboard' ) ){
                    $html = 'This SmartPost widget is not configured! Go to the <a href="' . admin_url('widgets.php') . '"> widgets page </a> to configure it!.';
                }
                return $html;
            }

            $renderButton = false; //true if only one category needs to be displayed
            $postButtonOpen = '';
            $postButtonTxt  = '';
            $postButtonClose = '';
            $selectBox = '';
            $html = '';

            if( $catMode ){
                $instance['displayCats'][0] = $thisCat->term_id; //used below in the if statement to render a single button
            }

            //Display a drop down if more than one categories are selected and we are not in 'category mode'
            if( count( $instance['displayCats'] ) > 1  && !$catMode ){

                $selectBox = '<select id="sp_selectCat">';
                $selectBox .= '<option> Select category... </option>';

                foreach ( $instance['displayCats'] as $catID ) {
                    $sp_cat = new sp_category(null, null, $catID);
                    $selectBox .= '<option value="' . $catID . '">' . $sp_cat->getTitle() . '</option>';
                }
                $selectBox .= '</select>';
            }

            //Display a button if it's only one category that's selected or we are in 'category mode'
            if ( $catMode || count( $instance['displayCats'] ) === 1 ) {

                $renderButton = true;
                $catID = array_values($instance['displayCats']);
                $catID = $catID[0];
                $sp_cat  = new sp_category(null, null, $catID);
                $catIcon = wp_get_attachment_image($sp_cat->getIconID());
                $postButtonOpen  = '<button type="button" id="sp_addPostButton" class="sp_qp_button" data-catID="' . $catID . '">';
                $postButtonTxt   = $catIcon . ' Submit a ' . ( $responseQP ? '' : $sp_cat->getTitle() );
                $postButtonClose = '</button>';
            }

            //Add an errors div in case we get any errors ..
            $html .= '<div id="component_errors" style="display: none;"><span id="clearErrors" class="sp_xButton"></span></div>';

            //Handle response categories
            if(!$responseQP){
                $html .= $renderButton ? $postButtonOpen . $postButtonTxt . ' post' . $postButtonClose : '<h4 class="sp_add_post"> Add a new ' . $selectBox . ' post</h4>';
            }else{
                $html .= $renderButton ? $postButtonOpen . $postButtonTxt . ' response' . $postButtonClose : '<h4 class="sp_add_post"> Post a ' . $selectBox . ' response</h4>';
            }

            $html .= '<div id="sp_quickpost_form" class="sp_quickpost">';
                $html .= '<p> Title <span style="color: red;">*</span></p>';
                $html .= '<input type="text" id="new_sp_post_title" id="new_sp_post_title" class="sp_new_title" placeholder="Type in a post title here ..." />';

                // Component Stack
                $html .= '<div id="sp_qp_stack">';
                    $html .= '<img src="' . SP_IMAGE_PATH . '/loading.gif" />';
                $html .= '</div>';

                // Post Tags
                $html .= '<div id="sp-tags-container">';
                    $html .= '<label for="sp-add-tags">Tag this post: </label>';
                    $html .= '<input type="text" id="sp-add-tags" placeholder="Type in a tag here ..." value="" />';
                    $html .= '<div id="sp-tags"></div>';
                $html .= '</div>';

                // New component publish/cancel buttons
                $html .= '<div id="sp-qp-post-buttons">';
                    $html .= '<button type="button" id="sp_publish_post" class="sp_qp_button">Publish ' . (!$responseQP ? 'Post' : 'Response') . '</button> or ';
                    $html .= '<button type="button" id="sp_cancel_draft" class="sp_qp_button">Cancel ' . (!$responseQP ? 'Post' : 'Response') . '</button> ';
                $html .= '</div>';
            $html .= '</div>';

            if($returnHTML){
                return $html;
            }else{
                echo $html;
            }
        }
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {
        $instance['displayCats']  = $new_instance['displayCats'];
        $instance['categoryMode'] = $new_instance['categoryMode'];
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
        $catMode = (bool) $instance[ 'categoryMode' ];
        $title   = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : __( 'New title', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <input type="checkbox" class="sp_catMode" data-widget-num="<?php echo $this->number ?>" id="<?php echo $this->get_field_id( 'categoryMode') ?>" name="<?php echo $this->get_field_name( 'categoryMode') ?>" <?php echo $catMode ? 'checked="checked"' : ''; ?> onchange="sp_widgets.sp_qpWidgetAdmin.toggleCatMode(this)" />
            <label for="<?php echo $this->get_field_id( 'categoryMode') ?>" class="tooltip" title="">Category Mode</label>
        </p>
        <?php
        $sp_cats = get_option( 'sp_categories' );

        //If there are no SP Cats, indicate so
        if( empty($sp_cats) ) {
            ?>
            <p> No SP Categories exist! Go to the SmartPost settings and add new categories! </p>
            <?php
            return;
        }
        $instance['displayCats'] = is_array($instance['displayCats']) ? $instance['displayCats'] : array();
        $id      = $this->get_field_id( 'displayCats' );
        $name    = $this->get_field_name( 'displayCats' );
        $display = $catMode ? 'style="display: none;"' : '';
        $counter = 0;
        ?>
        <div id="sp_qp_categories-<?php echo $this->number ?>" <?php echo $display ?>>
        <?php
        foreach ($sp_cats as $catID) {
            $cat = get_category( $catID );
            if( !is_null($cat) && !is_wp_error($cat) ){
                $checked = in_array($catID, $instance['displayCats']) ? 'checked="checked"' : '';
                ?>
                <input type="checkbox" id="<?php echo $id . '[' . $counter . ']' ?>" name="<?php echo $name . '[' . $counter .']' ?>" value="<?php echo $catID ?>" <?php echo $checked ?> />
                <label for="<?php echo $id . '[' . $counter . ']' ?>"><?php echo $cat->cat_name ?></label>
                <br />
                <?php
                $counter++;
            }
        }
        ?>
        </div>
        <?php
    }

}
?>