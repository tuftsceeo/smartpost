<?php 
if (!class_exists("sp_postComponent")) {

/**
 * sp_postComponent is the abstract class that all
 * post component classes should extend. Uses
 * sp_postComponent table.
 *
 * @abstract
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost
 */
	abstract class sp_postComponent{
		
		protected $ID;
		protected $catCompID;
		protected $compOrder;
		protected $name;
		protected $value;
		protected $postID;
		protected $options;
		protected $typeID;
		protected $menuOptions;
		protected $defaultMenuOptions = array('Delete' => 'sp_delete');		
		public $errors;

		/* Any initialization the component needs, i.e. enqueuing 
		 * JS/CSS files, action hooks, filters, etc..
		 */
	
		abstract static public function init();
		
		/**
		 * Returns HTML rendering of the post component in Edit Mode
		 * 
		 * @return string HTML string of the post component
		 */
		abstract protected function renderEditMode();

		/**
		 * Returns HTML rendering of the post component in View Mode (i.e. read-only mode)
		 * 
		 * @return string HTML string of the post component
		 */
		abstract protected function renderViewMode();
		
		/**
		 * Returns HTML rendering of the post component in preview mode used in listing post
		 * and search results. Should contain minimal amount of information.
		 * 
		 * @return string HTML string of the post component
		 */
		abstract protected function renderPreview();

		/**
		 * Adds extra menu options to the component menu
		 *
		 * @return array Array containing array("MenuItem" => "functionName") where
		 *               MenuItem is the item to be displayed and functionName is the function
		 *               to call when MenuItem is clicked on. Current menu items can only be 1
		 *               level deep.
		 */
		abstract protected function addMenuOptions();
		
		/**
		 * Update the component with data
		 * 
		 * @param mixed The data to update with
		 * @return bool Whether the update operation succeeded
		 */
		abstract public function update($data); 


		/**
		 * Checks whether the component is empty
		 *
		 * @return bool true if it's empty, false otherwise
		 */
		abstract public function isEmpty(); 
		
		protected function initComponent($compInfo){
				global $wpdb;
				$wpdb->show_errors();				

				extract($compInfo);

				if($compID > 0){
					$this->load($compID);
				}else{
						
						if( empty($postID) ){
							$this->errors = new WP_Error ('broke', __("Post ID not provided."));
						}
				
 					$this->catCompID = $catCompID;
						$this->compOrder = $compOrder;
						$this->value     = $value;
						$this->postID    = $postID;
						
						//Get the default name (if necessary) and typeID from the category component
						$this->name    = empty($name) ? sp_catComponent::getNameFromID($this->catCompID) : $name;
						$this->typeID  = sp_catComponent::getCompTypeIDFromID($this->catCompID);
						
						if( $this->typeID <= 0 ){
							$this->errors = new WP_Error ('broke', __("Invalid typeID"));
						}
						
						if( !is_wp_error($this->errors) ){
							$tableName = $wpdb->prefix . 'sp_postComponents';
							$wpdb->insert($tableName,
						  array(
						  	'catCompID'   => $this->catCompID,
						  	'compOrder'   => $this->compOrder,
						  	'name'     			=> $this->name,
						  	'value'						 => maybe_serialize($this->value),
						  	'postID'      => $this->postID,
						  	'options'     => maybe_serialize($this->options),
						  	'typeID'      => $this->typeID
						  ),array('%d', '%d', '%s', '%s', '%d', '%s', '%d'));
					 }
					 
					 if($wpdb->insert_id === false || $wpdb->insert_id === null){
							$this->errors = new WP_Error ('broke', __("Could insert component into the database succesfully: " . $wpdb->print_error()));
					 }else{
					  	$this->ID = $wpdb->insert_id;
  						$extraMenuOptions  = $this->addMenuOptions();
  						$this->menuOptions = array_merge($this->defaultMenuOptions, $extraMenuOptions);
					 }
				}		
		}
			
		/**
		 * Load a component from sp_postComponents based off of $compID.
		 * Called from __construct as a means of overloading the constructor.
		 *
		 * @param  int     $compID The component ID
		 */
		protected function load($compID){
			global $wpdb;
			$wpdb->show_errors();
			if( empty($compID) ){
				$this-> errors = new WP_Error ('broke', __("Invalid Component ID"));
			}else{
			 $sp_postComponentsTable = $wpdb->prefix . "sp_postComponents";
				$component = $wpdb->get_row($wpdb->prepare("SELECT * FROM $sp_postComponentsTable " . 
									  		"WHERE id = $compID;"));
				if( !empty($component) ){
					$this->ID        = $component->id;
					$this->catCompID = $component->catCompID;
					$this->compOrder = $component->compOrder;
					$this->name      = $component->name;
					$this->value     = maybe_unserialize($component->value);
					$this->postID    = $component->postID;
					$this->options   = maybe_unserialize($component->options);
					$this->typeID    = $component->typeID;

					$extraMenuOptions       = $this->addMenuOptions();
					$this->menuOptions      = array_merge($this->defaultMenuOptions, $extraMenuOptions);
				}else{
					$this->errors = new WP_Error ('broke', __("Could not find component with ID: " . $compID));					
				}
			}
		}
		
		static function initPostComponent(){
			require_once('ajax/sp_postComponentAJAX.php');
			sp_postComponentAJAX::init();
			self::enqueueBaseJS();
			self::enqueueBaseCSS();
			add_shortcode('sp_component', array('sp_postComponent' ,'spShortCodeComponent'));
		}
		
		function enqueueBaseJS(){
			wp_register_script( 'sp_postComponentJS', plugins_url('/js/sp_postComponent.js', __FILE__));
			wp_enqueue_script( 'sp_postComponentJS', null, array( 'jquery', 'sp_globals' ) );
		}
		
		function enqueueBaseCSS(){
			wp_register_style( 'sp_postComponentCSS', plugins_url('/css/sp_postComponent.css', __FILE__));
			wp_enqueue_style( 'sp_postComponentCSS' );
		}
		
		/**
		 * Hook for [sp_component id=$id typeid=$typeid] shortcode where $id is the post component ID
		 */
		function spShortCodeComponent($atts, $content = "", $tag){
			global $post;			
			if(sp_post::isSPPost($post->ID)){
				extract( shortcode_atts( array(
					'id' => 0,
				), $atts ) );
				if($id > 0){
					$type = self::getCompTypeFromID($id);
					if(!empty($type)){
						$class = 'sp_post' . $type;
						$postComponent = new $class($id);
						$content =	$postComponent->render();
					}else{
						$content = "";
					}
				}
				
			}
			return $content;
		}
		
		/**
		 * Delete the component from sp_postComponents
		 * 
		 * @return bool|int false on failure, number of rows affected on success
		 */
		function delete(){
			global $wpdb;
			$tableName = $wpdb->prefix . 'sp_postComponents';
			return $wpdb->query( 
				$wpdb->prepare( 
					"DELETE FROM $tableName
					 WHERE id = %d",
				  $this->ID
			 )
			);
		}
		
		/**
		 * Renders the component menu based off of $this->menuOptions
			*
			* @return string HTML representation of the menu
			*
		 */
 	protected function componentMenu(){
			$html = '';
			if(!empty($this->menuOptions)){
			$html .= '<ul class="simpleMenu">';
    $html .= '<li class="menuItem"><a href="#"><img src="' . IMAGE_PATH . '/downArrow.png" /></a>';
	    	$html .= '<ul class="menuItem">';
	    	foreach($this->menuOptions as $menuItem => $menuFunction){	    	
	      	$html .= '<li><a href="#" class="' . $menuFunction . '" data-compid="' . $this->ID .'">' . $menuItem . '</a></li>';
		    }
	     $html .= '</ul>';
    $html .= '</li>';
			$html .= '</ul>';
			}
			return $html;
		}
		
		/**
		 * Renders the component in HTML. If the component is empty and renderViewMode is called,
		 * then the component will not render.
		 * @return string HTML representation of the component
		 */
		function render(){
			global $current_user;
			global $wp_query;
			$post    = get_post($this->postID);
			$canEdit = current_user_can('edit_posts');
			$owner = ($current_user->ID == $post->post_author);
			$admin = current_user_can('administrator');
				
	 	require_once(ABSPATH . 'wp-admin/includes/post.php');
			$isLocked = (bool) wp_check_post_lock( $this->postID );				
			
			//Return preview mode if we're listing posts
			if( !is_single() && !$ajax ){
				if( !$this->isEmpty() ){
						$title = $this->renderCompTitle();
						$html .= '<div id="comp-' .  $this->ID . '" class="sp_component">';
									$html .= empty($title) ? '' : $title . ' - ';
								 $html .= $this->renderPreview() . ' ';
									$html .= '<div class="clear"></div>';
			 		$html .= '</div><!-- end #comp-' . $this->ID .' -->';
			 }
			 return $html;
			}
			
			//Return edit mode component if we're an admin or an owner
		 if( (( $canEdit &&  $owner) ||  $admin) && !$isLocked ){
				$html .= '<div id="comp-' . $this->ID . '" data-compid="' . $this->ID . '" data-required="' . $this->isRequired() . '" data-catcompid="' . $this->catCompID . '" data-typeid="' . $this->typeID . '" class="sp_component' . (($this->isRequired() && $this->lastOne() && $this->isEmpty()) ?  ' requiredComponent' : '') . '">';
					$html .= '<div class="componentHandle"></div>';
					$html .= $this->renderCompTitle(true);
					$html .= $this->componentMenu();
					$html .= $this->renderEditMode();
					$html .= '<div class="clear"></div>';
				$html .= '</div><!-- end #comp-' . $this->ID .' -->';
				return $html;
			
			}else{ //Otherwise return viewMode

				if( !$this->isEmpty() ){
						$html .= '<div id="comp-' .  $this->ID . '" class="sp_component">';
									$html .= $this->renderCompTitle();
								 $html .= $this->renderViewMode();
									$html .= '<div class="clear"></div>';
			 		$html .= '</div><!-- end #comp-' . $this->ID .' -->';
			 }
			 return $html;					
			
			}
		}
		
		function renderCompTitle($owner = false){
			global $current_user;
			
			$post = get_post($this->postID);
			$editable = 'componentTitle';
			if($owner){
				$editable = 'editableCompTitle editable';
				$html .= '<div id="comp-' . $this->ID .'-title" data-compid="' . $this->ID . '" class="' . $editable .'">';
					$html .= trim($this->name);
				$html .= '</div>';				
			}else{
				
				if( empty($this->name) ){
					return "";
				}else{
					$html .= trim($this->name);
				}
				
			}
			

			return $html;
		}
		
		/**
		 * Returns true if this component is last of its kind in the post
		 *
		 * @return bool true if it's the last one, false otherwise
		 */
		function lastOne(){
			$numOfInstances = (int) sp_catComponent::numOfInstances($this->postID, $this->catCompID);
			return ($numOfInstances == 1);
		}
		
		/** 
		 * Returns true is the category component assocated with this instance
		 * is required.
		 *
		 * @return bool True is it's required, otherwise false
		 */
		function isRequired(){
				return sp_catComponent::getRequiredFromID($this->catCompID);
		}

		/** 
		 * Returns true if the category component assocated with this instance
		 * is default (i.e. not required, but default).
		 *
		 * @return bool True is it's default, otherwise false
		 */		
		function isDefault(){
			return (sp_catComponent::getDefaultFromID($this->catCompID) && !$this->isRequired());
		}
		
		/**
		 * Gets the post component's typeID via it's ID.
		 */
		function getCompTypeFromID($compID){
			global $wpdb;
			$tableName = $wpdb->prefix . 'sp_postComponents';
			$typeID 			= $wpdb->get_var($wpdb->prepare("SELECT typeID FROM $tableName where id = $compID;"));
			return sp_core::getType($typeID);
		}		
		
		/**************************************
			* Getters/Setters																				*
			**************************************/
		function getCompType(){
			return sp_core::getType($this->typeID);
		}

		function getCompTypeID(){
			return $this->typeID;
		}
		
		function getID(){
			return $this->ID;
		}
		
		function getCatCompID(){
			return $this->catCompID;
		}
		
		function getCompOrder(){
			return $this->compOrder;
		}
		
		function setCompOrder($order){
			$this->compOrder = (int) $order;
			return sp_core::updateVar('sp_postComponents', $this->ID, 'compOrder', (int) $this->compOrder, '%d');
		}
		
		/**
		 * If the name has been modified. Looks at the name column in sp_postComponents to see if it's in use,
		 * otherwise the default category component name gets assigned to the component.
		 */
		function nameModified(){
			global $wpdb;
			$tableName = $wpdb->prefix . 'sp_postComponents';
			$modified = $wpdb->get_var($wpdb->prepare("SELECT name FROM $tableName where id = $this->ID;"));
			return !empty($modified);
		}
		
		function getName(){
			return $this->name;
		}
		
		function setName($name){
			$this->name = (string) $name;
			return sp_core::updateVar('sp_postComponents', $this->ID, 'name', $this->name, '%s');		
		}
		
		function getValue(){
			return $this->value;
		}
		
		function getPostID(){
			return $this->postID;
		}
		
	}
}
?>