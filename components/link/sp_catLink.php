<?php
if (!class_exists("sp_catLink")) {
	
	/**
	 * Extends sp_catComponent
	 * 
	 *	Link category component. Defines administrative features for
	 * the link component. Also used alongside sp_postLink for front-end
	 * handling.
	 *
	 * @see sp_catComponent
	 */

	class sp_catLink extends sp_catComponent{

		function __construct($compID = 0, $catID = 0, $name = '', 
																							$description = '', $typeID = 0, $order = 0,
																							$options = null, $default = false, $required = false){

 				$compInfo = compact("compID", "catID", "name", "description", "typeID",
																										"order", "options", "default", "required");
 				$this->initComponent($compInfo);
		}
		
		function install(){
			self::installComponent('Link', 'Link to photos and other media.', __FILE__);			
		}
		
		function componentMenu(){
			
			$html .= '<ul class="simpleMenu">';
    $html .= '<li class="stuffbox"><a href="#"><img src="' . IMAGE_PATH . '/downArrow.png" /></a>';
    	$html .= '<ul class="stuffbox">';
      	$html .= '<li><a href="#" class="delete_component" data-compid="' . $this->ID . '">Delete Component</a></li>';
     $html .= '</ul>';
    $html .= '</li>';
			$html .= '</ul>';
			echo $html;	
		}
		
		static function init(){}
		function componentOptions(){ echo '<p>No options exist for this component</p>';	}
		function getOptions(){ return null;}
		function setOptions($data){ return null; }
	}
}
?>