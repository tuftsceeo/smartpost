<?php
/**
 * sp_quickPostWidget used to add a new post quickly from the front end
 */
class sp_quickPostWidget extends WP_Widget {
    /** constructor */
    function __construct() {
        parent::__construct(false, $name = 'SP QuickPost');        
    }

				static function init(){
						require_once('ajax/sp_quickPostWidgetAJAX.php');
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
	    	wp_register_script('sp_quickPostWidgetJS', plugins_url('js/sp_quickPostWidget.js', __FILE__));
	    	wp_enqueue_script( 'sp_quickPostWidgetJS', array( 'jquery', 'sp_postComponentJS' ) );				
				}

    /** @see WP_Widget::widget */
   	function widget($args, $instance, $returnHTML = false, $responseQP = false) {
					global $current_user;
					extract( $args );
    	$title = apply_filters('widget_title', $instance['title']);
    	
					//Load post components
					if(current_user_can('edit_posts')){
							
							//Get the selected cats
							if( !empty($instance['displayCats']) ){
								$selectBox = '<select id="sp_selectCat">';
								$selectBox .= '<option> Select category... </option>';
								foreach($instance['displayCats'] as $catID){
									$sp_cat = new sp_category(null, null, $catID);
									$selectBox .= '<option value="' . $catID . '">' . $sp_cat->getTitle() . '</option>';
								}
								$selectBox .= '</select>';
							}
							
		     //$html .= $before_widget;
							
							//Add an errors div if we're on the front-page or home								
							$html .= (is_home() || is_front_page()) ? '<div id="component_errors" style="display: none;"></div>' : '';
	     	
	     	if(!$responseQP){
	     		$html .= '<h4 class="sp_add_post"> Add a new ' . $selectBox . ' post</h4>';
	     	}else{
		     	$html .= '<h4 class="sp_add_post"> Post a ' . $selectBox . ' response</h4>';
	     	}

	     	$html .= '<div id="sp_quickpost_form" class="sp_quickpost">';
								$html .= '<p> Title <font style="color: red;">*</font></p>';
	     		$html .=  '<input type="text" id="new_sp_post_title" id="new_sp_post_title" class="sp_new_title" />';
	     		
	     		//Component Stack
	     		$html .= '<div id="sp_qp_stack">';
	     		$html .= '</div>';
	     		
	     		//New component dialog
								$html .= '<p>';
		     		$html .= '<button type="button" id="sp_publish_post" class="sp_qp_button">Publish ' . (!$responseQP ? 'Post' : 'Response') . '</button> or ';
		     		$html .= '<button type="button" id="sp_cancel_draft" class="sp_qp_button">Cancel ' . (!$responseQP ? 'Post' : 'Response') . '</button> ';		     		
		     		$html .= !$responseQP ? 'Responding to a post? <span id="sp_qp_response">Publish as a response</span>.' : '';
	     		$html .= '</p>';
								
								//Response Post dialog
	     		$html .= '<div id="sp_qp_responseDialog">';
	     			$html .= '<div id="sp_qp_responsePosts"></div>';
	     			$html .= '<div class="clear"></div>';
	     		$html .= '</div>';
	     		
	     	$html .= '</div>';
	     	$html .= $after_widget;
	     	
	     	if($returnHTML){
	     		return $html;
	     	}else{
		     	echo $html;
	     	}
     }
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {															
						$instance['displayCats'] = $new_instance['displayCats'];
      return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {
					$sp_cats =  get_option('sp_categories');
					
					//If there are no SP Cats, indicate so
					if( empty($sp_cats) ){
						$html = "<p> No SP Categories exist! Go to the SmartPost settings and add new categories! </p>";
						echo $html;
						return;
					}
					
					//Otherwise set displayCats
					if ( !is_array($instance['displayCats']) ){
							
							//Transform $sp_cats so that all categories are included by default
							$displayCats = array_flip($sp_cats);
							foreach($displayCats as $catID => $display){
								$displayCats[$catID] = true;
							}
							$instance['displayCats'] = $displayCats;
					}
					

					foreach ($sp_cats as $catID) {
						$checked = in_array($catID, $instance['displayCats']) ? 'checked="checked"' : '';
						$id      = $this->get_field_id( 'displayCats' );
						$name    = $this->get_field_name( 'displayCats' );
						$cat     = get_category($catID);
						
						$html   .= '<input type="checkbox" id="' . $id . '[]" name="'. $name .'[]" value="' . $catID . '" ' . $checked . ' /> ';
						$html   .= '<label for="' . $id . '[]">' . $cat->cat_name . '</label>';						
      $html   .= '<br />';
	    }
	    
	    echo $html;
    }

} 
?>