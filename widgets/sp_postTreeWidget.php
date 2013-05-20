<?php
/**
 * sp_postTreeWidget used to add a new post quickly from the front end
 */
class sp_postTreeWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP Post Tree');
    }

				function init(){
						require_once('ajax/sp_postTreeWidgetAJAX.php');
						sp_postTreeWidgetAJAX::init();				
						self::enqueueCSS();
						self::enqueueJS();
				}
				
				static function enqueueCSS(){
	    	wp_register_style('sp_postTreeWidgetCSS', plugins_url('css/sp_postTreeWidget.css', __FILE__));
	    	wp_register_style('jquery-dynatree-css', plugins_url('js/dynatree/src/skin-vista/ui.dynatree.css', __FILE__));
		    wp_enqueue_style( 'sp_postTreeWidgetCSS' );
		    wp_enqueue_style( 'jquery-dynatree-css' );
				}
				
				static function enqueueJS(){
	    	wp_register_script('sp_postTreeWidgetJS', plugins_url('js/sp_postTreeWidget.js', __FILE__));
	    	wp_register_script('jquery-dynatree', plugins_url('js/dynatree/src/jquery.dynatree.min.js', __FILE__));				
						wp_register_script('jquery-dynatree-custom', plugins_url('js/dynatree/jquery/jquery-ui.custom.min.js', __FILE__));
						wp_register_script('jquery-dynatree-cookie', plugins_url('js/dynatree/jquery/jquery.cookie.js', __FILE__));

						wp_enqueue_script( 'jquery-dynatree-custom', null, array( 'jquery' ) );
						wp_enqueue_script( 'jquery-dynatree-cookie', null, array( 'jquery-dynatree-custom' ) );								
						wp_enqueue_script( 'jquery-dynatree',        null, array( 'jquery-dynatree-cookie' ) );
		    wp_enqueue_script( 'sp_postTreeWidgetJS',    null, array( 'jquery-dynatree', 'sp_postComponentJS' ) );
				}

    /** @see WP_Widget::widget */
   	function widget($args, $instance) {
	  		global $current_user;
					if(is_user_logged_in() && current_user_can('edit_posts')){
	    	extract( $args );
	    	$title = apply_filters('widget_title', $instance['title']);
	    	
						$sp_categories = get_option('sp_categories');
						if(!empty($sp_categories)){
								$html .= '<div id="sp_postTree">';
									$html .= '<ul>';
									foreach($sp_categories as $categoryID){
										$sp_category = new sp_category(null, null, $categoryID);
										$post_filters = array('permalink' => true);
										$html .= $sp_category->renderPostTree('publish', 0, $post_filters);
									}
									$html .= '</ul>';
								$html .= '</div>';
						}
						
	     echo $before_widget;
	    	echo $before_title . $title . $after_title;
	    	echo $html;
	     echo $after_widget;
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