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
        self::enqueue_sp_qp_css();
        self::enqueue_sp_qp_js();
        add_shortcode( 'sp-quickpost', array('sp_quickPostWidget', 'sp_qp_shortcode' ) );
    }

    static function enqueue_sp_qp_css(){
        wp_register_style('sp_qp_widget_css', plugins_url('css/sp_quickPostWidget.css', __FILE__));
        wp_enqueue_style( 'sp_qp_widget_css' );
    }

    static function enqueue_sp_qp_js(){
        if( is_admin() ){
            wp_register_script('sp_qp_widget_admin_js', plugins_url('js/adminjs/sp_qpWidgetAdmin.js', __FILE__));
            wp_enqueue_script( 'sp_qp_widget_admin_js', array( 'jquery', 'sp_postComponentJS' ) );
        }else{
            wp_register_script('sp_qp_widget_js', plugins_url('js/sp_quickPostWidget.js', __FILE__));
            wp_enqueue_script( 'sp_qp_widget_js', array( 'jquery', 'sp_postComponentJS' ) );
        }
    }

    /**
     * Shortcode handler for [sp-quickpost template_ids="1,2,3"]
     */
    static function sp_qp_shortcode( $atts ){
        $template_ids = array();
        $button_txt = "";
        $redirect_url = "";
        $submit_msg = "";
        $hide_buttons = false;
        $title_placeholder = "";
        $title_html = "";
        $show_form = false;
        $load_post = false;
        extract( shortcode_atts( array(
            'template_ids' => array(),
            'button_txt' => '',
            'redirect_url' => '',
            'submit_msg' => '',
            'hide_buttons' => false,
            'title_placeholder' => '',
            'title_html' => '',
            'show_form' => false,
            'load_post' => false
        ), $atts ) );

        $template_ids = explode(',', $template_ids);

        // If nothing is left, don't render anything!
        if( empty( $template_ids ) ){
            return '';
        }else{
            global $sp_qp_shortcode_id;

            // Clean up any bad template ids
            foreach( $template_ids as $key => $id ){
                if( !term_exists( (int) $id )  ){
                    unset( $template_ids[$key] );
                }

                if( !sp_category::isSPCat($id) ){
                    unset( $template_ids[$key] );
                }
            }

            // Gives each shortcode a unique id.
            if( empty( $sp_qp_shortcode_id ) ){
                $sp_qp_shortcode_id = 1;
            }else{
                $sp_qp_shortcode_id++;
            }

            ob_start(); // Capture the echo output of the widget() method

            $sp_qp_widget = new self(); // Create a new quickpost instance
            $instance = array(
                'categoryMode' => false,
                'displayCats' => $template_ids,
                'button_txt' => $button_txt,
                'redirect_url' => $redirect_url,
                'submit_msg' => $submit_msg,
                'hide_buttons' => (bool) $hide_buttons,
                'title_placeholder' => $title_placeholder,
                'title_html' => $title_html,
                'show_form' => (bool) $show_form,
                'load_post' => (bool) $load_post
            );

            $sp_qp_widget->widget( array(), $instance, $sp_qp_shortcode_id );

            $output = ob_get_clean(); // Store the output in a variable
            return $output;
        }
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance, $shortcode_id = 0) {
        extract( $args );

        $category_mode = $instance['categoryMode'];
        $current_category = get_category( get_query_var('cat'), false);

        // Don't return anything if we're not in a category AND category mode is enabled
        if( $category_mode && !sp_category::isSPCat( $current_category->term_id ) ){
            return '';
        }

        // Give each widget a unique id so there are no conflicts
        if( empty( $shortcode_id ) ){
            $widget_id = $this->id;
        }else{
            $widget_id = $shortcode_id;
        }

        // replace %widget_id% with the widget id
        $rewritecode = array(
            '%widget_id%'
        );

        $rewritereplace = array(
            $widget_id
        );

        // Perform the replacement
        foreach( $instance as $instance_key => $instance_property){
            $instance[$instance_key] = str_replace( $rewritecode, $rewritereplace, $instance[$instance_key] );
        }

        // Load post components
        if( current_user_can('edit_posts') ){
            $html = '';
            if( !$category_mode && empty($instance['displayCats']) ){
                if( current_user_can( 'edit_dashboard' ) ){
                    $html = 'This SmartPost widget is not configured! Go to the <a href="' . admin_url('widgets.php') . '"> widgets page </a> to configure it!.';
                }
                echo $html;
                return '';
            }

            $render_button = false; // true if only one category needs to be displayed
            $post_button_open = '';
            $post_button_txt  = '';
            $post_button_close = '';
            $select_box = '';
            $html = '';

            if( $category_mode ){
                $instance['displayCats'][0] = $current_category->term_id; // used below in the if statement to render a single button
            }

            // Display a drop down if more than one categories are selected and we are not in 'category mode'
            if( count( $instance['displayCats'] ) > 1  && !$category_mode ){
                $select_box = '<select id="sp-qp-select-cat-' . $widget_id . '" class="sp-qp-select-cat" data-widgetid="' . $widget_id . '">';
                $select_box .= '<option> Select category... </option>';
                foreach ( $instance['displayCats'] as $catID ) {
                    $sp_cat = new sp_category(null, null, $catID);
                    $select_box .= '<option value="' . $catID . '">' . $sp_cat->getTitle() . '</option>';
                }
                $select_box .= '</select>';
            }

            // Display a button if it's only one category that's selected or we are in 'category mode'
            if ( $category_mode || count( $instance['displayCats'] ) === 1 ) {
                $render_button = true;
                $catID = array_values( $instance['displayCats'] );
                $catID = $catID[0];
                $sp_cat  = new sp_category(null, null, $catID);

                $show_form = $instance['show_form'] ? 'data-showform="true"' : '';
                $post_button_open  = '<button type="button" id="sp-qp-new-post-' . $widget_id . '" class="sp-qp-new-post" data-widgetid="' . $widget_id . '" data-catid="' . $catID . '" ' . $show_form . '>';
                $post_button_txt   = 'Submit a ' . $sp_cat->getTitle() . ' post';
                $post_button_close = '</button>';
            }

            // Add an errors div in case we get any errors.
            $html .= '<div id="component_errors" style="display: none;"><span id="clearErrors" class="sp_xButton"></span></div>';

            // Override button text if it's set via shortcode
            $post_button_txt = !empty( $instance['button_txt'] ) ? $instance['button_txt'] : $post_button_txt;

            if( $instance['load_post'] ){
                $html .= '<div id="sp-quickpost-form-' . $widget_id . '" class="sp-quickpost-form" style="display: inline;">';
            }else{
                $html .= $render_button ? $post_button_open . $post_button_txt . $post_button_close : '<h4 id="sp-add-post-' . $widget_id .'" class="sp-add-post"> Add a new ' . $select_box . ' post</h4>';
                $html .= '<div id="sp-quickpost-form-' . $widget_id . '" class="sp-quickpost-form">';
            }
                $html .= '<p> Title <span style="color: red;">*</span></p>';

                $title_placeholder = empty( $instance['title_placeholder'] ) ? 'Type in a post title here ...' : $instance['title_placeholder'];

                $sp_qp_title_input_html = empty( $instance['title_html'] ) ? '<input type="text" id="new-sp-post-title-' . $widget_id . '" class="new-sp-post-title" placeholder="' . $title_placeholder . '" />' : $instance[ 'title_html' ];
                $sp_qp_title_input = apply_filters( 'sp_qp_widget_title_input', $sp_qp_title_input_html, $widget_id, $title_placeholder );

                $html .= $sp_qp_title_input;

                // Component Stack
                $html .= '<div id="sp-qp-comp-stack-' . $widget_id . '" class="sp-qp-comp-stack">';
                    if( $instance['load_post'] ){
                        /*
                        // load the most recent post that originated from the page we're on
                        $sp_post_query_args = array(
                            'meta_key' => 'sp_origin_id',
                            'meta_value' => $post->ID,
                            'author' => get_current_user_id(),
                            'posts_per_page' => 1
                        );
                        $sp_post_query = new WP_Query( $sp_post_query_args );
                        print_r( $sp_post_query, true );


                        // Add any default/required components
                        $post_comps = sp_post::get_components_from_ID( $instance['load_post'] );
                        $html = '<div id="sp-components-' . $widget_id . '" class="sortableSPComponents quickPostComps">';
                        if( !empty( $post_comps ) ){
                            global $wp_query;
                            $wp_query->is_single = true;
                            $_GET['edit_mode'] = true;
                            foreach( $post_comps as $postComp ){
                                $html .= $postComp->render();
                            }
                            $wp_query->is_single = false;
                        }
                        $html .= '</div>';
                        */
                    }else{
                        $html .= '<img src="' . SP_IMAGE_PATH . '/loading.gif" />';
                    }
                $html .= '</div>';

                if( $instance['hide_buttons'] ){
                    $html .= '<div id="sp-quickpost-add-components-' . $widget_id . '" style="display: none">';
                }

                // Add component buttons
                $sp_cat = new sp_category(null, null, $catID);
                $sp_catComps = $sp_cat->getComponents();
                if( !empty($sp_catComps) ){
                    foreach($sp_catComps as $component){

                        $catCompID = $component->getID();
                        $typeID = $component->getTypeID();
                        $desc = $component->getDescription();
                        $icon = $component->getIcon();
                        $icon_img = "";
                        if( !empty($icon) ){
                            $icon_img = '<img src="' . $component->getIcon() . '" />';
                        }

                        $html .= '<span id="' . $catCompID . '" class="sp-qp-component" data-compid="' . $catCompID . '" data-typeid="' . $typeID . '" title="' . $desc . '" alt="' . $desc . '">';
                        $html .= 	$icon_img . ' Add ' . $component->getName();
                        $html .= '</span> ';
                    }
                    $html .= '<div class="clear"></div>';
                }

                if( $instance['hide_buttons'] ){
                    $html .= '</div><!-- end #sp-quickpost-add-components -->';
                }

                // New component publish/cancel buttons
                $redirect_url = !empty( $instance['redirect_url'] ) ? 'data-redirecturl="' . $instance['redirect_url'] . '"' : '';
                $submit_msg = !empty( $instance['submit_msg'] ) ? 'data-submitmsg="' . $instance['submit_msg'] . '"' : '';
                $html .= '<div id="sp-qp-post-buttons-' . $widget_id . '" class="sp-qp-post-buttons">';
                    $html .= '<button type="button" id="sp-qp-publish-post-' . $widget_id . '" class="sp-qp-button sp-qp-publish-post" data-widgetid="' . $widget_id . '"' . $redirect_url . ' ' . $submit_msg . '>Publish Post</button> or ';
                    $html .= '<button type="button" id="sp-qp-cancel-draft-' . $widget_id . '" class="sp-qp-button sp-qp-cancel-draft" data-widgetid="' . $widget_id . '">Cancel Post</button>';
                $html .= '</div>';
            $html .= '</div>';

            // Add origin of where we're coming from
            global $post;
            $html .= '<input type="hidden" id="sp-post-origin-id-' . $widget_id .'" name="sp-post-origin-id-' . $widget_id .'" value="' . $post->ID . '" />';

            $html = apply_filters( 'sp_qp_widget_form_after', $html );

            echo '<div class="clear">' . $html . '</div>';
        }
        return '';
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