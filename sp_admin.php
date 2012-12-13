<?php
require_once('ajax/sp_adminAJAX.php');
if (!class_exists("sp_admin")) {
	class sp_admin{
		
		/******************************
		 *  Administrative Functions  *
		 ******************************/
		
		static function init(){
				add_action('newSPCat', array('sp_admin', 'newSPCat'));
				add_action('admin_menu', array('sp_admin','sp_admin_add_page') );
				add_action('admin_notices', array('sp_admin', 'checkForBadPosts') );
				add_action( 'delete_category', array('sp_category', 'deleteCategory'));
				require_once('ajax/sp_adminAJAX.php');
				sp_adminAJAX::init();
		}
		
		function dashboardPostNotice(){
			$post = (int)$_GET['post'];
			$action = $_GET['action'];
			if(sp_post::isSPPost($post) && $action && is_admin()){
			?>
			 <div class="error">
			 		<p>Warning: You are attempting to edit a SmartPost post. The current version of SmartPost
			 		does not support editing posts from the dashboard.
			 		</p>
			 		<p>
			 		Any changes made here	will still be added to your post but you will not be able to add, 
			 		remove or update any post components.
			 		</p>
			 		<p>Click <a href="<?php echo get_permalink($post) ?>">here</a> to edit the post from the front end.</p>
			 	</h3>
			 </div>
			<?php
			}
		}
				
		//Checks all SP categories for "bad" posts
		//(i.e. posts that are part of > 1 categories)
		//and returns an array of distinct "bad" post IDs
		function checkAllSPCategories(){
			$sp_categories = get_option("sp_categories");
			$badPosts 					= array();
			if( !empty($sp_categories) ){
				foreach($sp_categories as $catID){
					$badPosts += self::checkCategory($catID);
				}
			}
			return $badPosts;
		}
		
		//takes in a catID and returns an array of "bad" post ID's
		//(i.e. posts that are part of > 1 categories)
		function checkCategory($catID){
					$category 	= get_category($catID);
					$badPosts 	= array();
					$posts 				= get_posts( array('category' => $category->term_id, 'post_status' => 'publish|draft') );
					foreach($posts as $post){
						$categories = get_the_category($post->ID);
						if( count($categories) > 1 ){
							if(!in_array($post->ID, $badPosts)){
								array_push($badPosts, $post->ID); 
							}
						}
					}
					return $badPosts;
		}
		
		function enableCategory($catID){
			$isBad = self::checkCategory($catID);
			if( count($isBad) > 0 ){
				return new WP_Error('broke', ("There are post(s) under this category" . 
																																	 " that belong to other categories." .
																																	 " Please make sure all SP Posts belong".
																																	 " to just one category!" ));
			}else{
				$sp_categories = get_option("sp_categories");
				array_push($sp_categories, $catID);
				return update_option("sp_categories", $sp_categories);
			}
		}
		
		function newSPCat($title, $description){
	 	return new sp_category($title, $description);
		}
		
		function updateSPCat($catID, $title, $description){
			$sp_category = new sp_category(null, null, $catID);
			if( !empty($title) ){
				$sp_category->setTitle($title);
			}
			$sp_category->setDescription($description);
			return $sp_category;
		}
		
		/*******************************
			*  Cat Component Functions    *
			*******************************/
		function newCompForm($catID){
		?>
				<button type="button" class="button-secondary" id="addNewComponent" name="addNewComponent">
					Add New Component
				</button>
				<?php self::componentForm($catID, null); ?>
				<button type="submit" class="button-primary" id="addComponent" name="addComponent">
					Add Component
				</button>
				<button type="button" class="button-secondary" id="cancelCompForm" name="cancelCompForm">
					Cancel
				</button>
				</form> <!-- end #componentForm -->
			</div><!-- end #compFormWrapper -->
			<?php
		}
		
		/* Takes in a $catID and $component and fills in the
		 * componentForm() wiwth $component's settings
		 */
		function loadCompForm($catID, $component){
		?>
				<?php self::componentForm($catID, $component); ?>
				<button type="submit" class="button-primary" id="addComponent" name="addComponent">
					Update <?php echo $component->getName(); ?>
				</button>
				</form> <!-- end #componentForm -->
			</div><!-- end #compFormWrapper -->							
		<?php
		}
		
		//Pre-condition: used inside newCompForm() or loadCompForm()
		function componentForm($catID, $component = null){
			?>
			<div id="compFormWrapper" <?php echo is_object($component) ? 'style="display:inline;"' : '' ?>>
				<form id="componentSettings<?php echo is_object($component) ? '-' . $component->getID() : '' ?>-form" name="componentSettings<?php echo is_object($component) ? '-' . $component->getID() : '' ?>-form" action="" method="post">
					<table>
						<?php if(is_null($component)){ ?>
						<tr>
							<td>Component Type</td>
							<td><?php self::listCompTypes() ?></td>
						</tr>
						<?php } ?>
						<tr>
							<td>Component Name </td>
							<td><input type="text" class="regular-text" id="compName" name="compName" value="<?php echo is_object($component) ? $component->getName() : '' ?>" /></td>
						</tr>
						<tr>
							<td>Component Description</td>
							<td><input type="text" class="regular-text" id="compDescription" name="compDescription" value="<?php echo is_object($component) ? $component->getDescription() : '' ?>" /></td>
						</tr>
						<tr>
							<td>Component Icon</td>
							<td>
								<input type="file" id="componentIcon" name="componentIcon">
								<p>16x16 .png or .jpg files only.</p>
								<p>Note: If no icon is uploaded, a default icon will be used.</p>
							</td>
						</tr>
						<?php 
							if(is_object($component)){
									if($component->getRequired()){
										$isRequired = true;
										$isDefault 	= true;
									}else if($component->getDefault()){
										$isRequired = false;
										$isDefault  = true;
									}else{
										$isRequired = false;
										$isDefault  = false;
									}
							}
						?>
						<tr>
							<td><label for="isDefault">Default</label></td>
							<td><input type="checkbox" class="compRestrictions" id="isDefault<?php echo is_object($component) ? '-' . $component->getID() : '' ?>" name="isDefault<?php echo is_object($component) ? '-' . $component->getID() : '' ?>" value="1" <?php echo $isDefault ? 'checked="checked"' : '' ?> <?php $isRequired ? 'disabled="disabled"' : ''?> /></td>
						</tr>
						<tr>
							<td><label for="isRequired">Required</label></td>
							<td><input type="checkbox" class="compRestrictions" id="isRequired<?php echo is_object($component) ? '-' . $component->getID() : '' ?>" name="isRequired<?php echo is_object($component) ? '-' . $component->getID() : '' ?>" value="1" <?php echo $isRequired ? 'checked="checked"' : '' ?> /></td>
						</tr>				
					</table>
				<input type="hidden" name="catID" id="catID" value="<?php echo $catID ?>" />
			<?php
		}
		
		
		function listCompTypes(){
			$types = sp_core::getTypes();
			?>
						<select id="sp_compTypes" name="sp_compTypes">
						<?php foreach($types as $compType){ ?>
								<option id="type-<?php echo $compType->id ?>" name="type-<?php echo $compType->id ?>" value="<?php echo $compType->id ?>">
									<?php echo trim($compType->name) ?>
								</option>
						<?php } ?>
						</select>
			<?php
		}
			
		function listCatComponents($sp_category){
			?>
				<div id="catComponentList">
						<?php 
							$catComponents = $sp_category->getComponents();
							//print_r($catComponents);
							if(!empty($catComponents)){
								foreach($catComponents as $component){
									$component->renderSettings();
								}
						 }
						?>
				</div>
			<?php		
		}
		
		function renderPostComponents($sp_category){
		?>
		<div id="postComponents">
			<?php 
							self::newCompForm($sp_category->getID());
							self::listCatComponents($sp_category);
			?>
		</div>
		<?php
		}
		
		/*******************************
			*			Category GUI Functions    *
			*******************************/
		
		function renderSPCatSettings($sp_category){
			if(is_wp_error($sp_category->errors)){
				?>
				<div class="error">
					<h3>An error occurred: <?php echo $sp_category->errors->get_error_message() ?></h3>
				</div>
				<?php
			}else{
			?>
				<?php echo wp_get_attachment_image($sp_category->getIconID(), null, null, array('class' => 'category_icon')); ?>
				<h2 class="category_title">
					<a href="<?php echo admin_url('admin.php?page=smartpost&catID=' . $sp_category->getID() . '&action=edit') ?>">
					<?php echo $sp_category->getTitle() ?></a>
				</h2>
				<?php $sp_category->categoryMenu() ?>
				<?php $catDesc = $sp_category->getDescription();
											echo empty($catDesc) ? '' : '<p>' . $catDesc . '</p>';
				?>
				<div class="clear"></div>
				<?php 
					if($_GET['action'] == 'edit'){
						self::loadCatForm($_GET['catID']);
					}else{
						if(isset($_GET['tab'])){
							$active_tab = $_GET['tab'];
						}else{
							$active_tab = 'postComps';
						}
				?>
				<h2 class="nav-tab-wrapper">  
				    <a href="?page=smartpost&catID=<?php echo $sp_category->getID() ?>&tab=postComps" class="nav-tab <?php echo $active_tab == "postComps" ? 'nav-tab-active' : '' ?>">Post Components</a>  
				    <a href="?page=smartpost&catID=<?php echo $sp_category->getID() ?>&tab=responseCats" class="nav-tab <?php echo $active_tab == "responseCats" ? 'nav-tab-active' : '' ?>" >Response Categories</a>		    
				</h2>
				<div id="settings_list">
					<?php 
					switch($active_tab){
						case 'postComps':
							self::renderPostComponents($sp_category);
							break;
						case 'responseCats':
							$sp_category->renderResponseCatForm();
							break;
						default:
							self::renderPostComponents($sp_category);
							break;
					}
					?>
				</div>
				<?php
					}
			}
		}		
		
		function newCatForm(){
			self::catForm(0, true);
			?>
				<button type="submit" id="newSPCat" name="newSPCat" class="button-primary">
					Add new Category
				</button>
				<?php wp_nonce_field('sp_admin_nonce'); ?>
				<input type="hidden" name="action" id="action" value="newSPCatAJAX" />
			</form>
			<?php
		}
		
		function loadCatForm($catID){
			self::catForm($catID, true);
			?>
				<button type="submit" id="updateSPCat" name="updateSPCat" class="button-primary">
					Update Category
				</button>
				<!-- <button type="button" id="updateSPCat" name="updateSPCat" class="button-secondary delete">
					<a>Delete Category</a>
				</button>	-->
				<?php wp_nonce_field('sp_admin_nonce'); ?>
				<input type="hidden" name="catID" id="catID" value="<?php echo $catID ?>"
				<input type="hidden" name="action" id="action" value="updateSPCat" />
			</form>
			<?php			
		}
		
		function catForm($catID){
			if($catID > 0){
				$sp_category = new sp_category(null, null, $catID);
			}else{
					?><h2>New Category</h2><?php
			}
			?>
				<form id="cat_form" method="post" action="">
				<div id="cat_info">
						<table>
							<tr>
								<td>
									<h4>Category Name <font style="color:red">*</font></h4>
								</td>
								<td>
									<input type="text" class="regular-text" id="cat_name" name="cat_name" value="<?php echo isset($sp_category) ? $sp_category->getTitle() : '' ?>" />
								</td>
							</tr>
							<tr>
								<td>
									<h4>Category Description</h4>
								</td>
								<td>
									<input type="text" class="regular-text" id="category_description" name="category_description" value="<?php echo isset($sp_category) ? $sp_category->getDescription() : '' ?>" />
								</td>
							</tr>
							<?php if(isset($sp_category)){ ?>
									<?php if( $sp_category->getIconID() > 0 ){ ?>
							<tr>
								<td><h4>Current Icon</h4></td>
								<td>
									<p>
										<?php echo wp_get_attachment_image($sp_category->getIconID()) ?>
										Upload a new icon below to replace the current icon.
									</p>
									<p>
										<input type="checkbox" id="deleteIcon" name="deleteIcon" value="deleteIcon" />
										<label for="deleteIcon">Delete Icon</label>
									</p>
								</td>
							</tr>
									<?php } ?>
							<?php } ?>
							<tr>
								<td><h4>Category Icon</h4></td>
								<td>
									<input type="file" id="category_icon" name="category_icon">
								</td>
							</tr>
						</table>
						<p style="color: red">* Required</p>
				</div>
			<?php
		}		
		
		/*******************************
		 *			Admin Page GUI Functions  *
		 *******************************/
				 
		//returns html list of bad posts in SP-Enabled categories
		function checkForBadPosts(){
			if($_GET['page'] == 'smartpost'){
				$badPosts = self::checkAllSPCategories();
				if( count($badPosts) > 0 ){?>
					<div class="error">
						<p>
							The following posts are part of multiple categories.
							Please limit each post to one (1) SmartPost category.
						</p>
						<ul>
					<?php
							foreach($badPosts as $postID){
								$post = get_post($postID);
					?>
							<li>
								<a href="<?php echo get_edit_post_link($post->ID); ?>">
									<?php echo empty($post->post_title) ? 'No title' : $post->post_title; ?>
								</a>
							</li>
					<?php }?>
						</ul>
					</div>
					<?php
				}
			}
		}
		
		function sp_admin_add_page() {
			add_menu_page( 'SmartPost', 'SmartPost', 'edit_users', 'smartpost', $function, $icon_url, $position );
			add_options_page('SmartPost', 'IEL Categories', 'manage_options', 'smartpost', array('sp_admin','smartpost_admin_page'));
			add_action('admin_init', array('sp_admin', 'register_sp_settings'));
		}		

		function register_sp_settings() {
			add_settings_section('sp_categories', NULL, NULL, 'smartpost');
		}
		
		function listSPCategories(){
			$nulls = false;
			$sp_categories = get_option("sp_categories");?>
			<h2>SmartPost Categories</h2>
			<button id="newSPCatForm" class="button">Add a new SP Category</button>
			<ul id="sp_cats" class="categories_list">			
			<?php
			if( !empty($sp_categories) ){
				?>
				<?php
				foreach($sp_categories as $key => $catID){
						$sp_category = new sp_category(null, null, $catID);
				?>
						<li class="stuffbox" spcat_id="<?php echo $sp_category->getID() ?>"> 
							<?php echo wp_get_attachment_image($sp_category->getIconID()); ?>
							<span id="cat-<?php echo $catID ?>" class="cat_title">
								<a href="<?php echo admin_url('admin.php?page=smartpost&catID=' . $catID) ?>">
								<?php echo $sp_category->getTitle() ?>
								</a>
							</span>
						</li>
				<?php 
				} //end for each
				?>
				<?php
			}
			?>
			</ul>	
			<?php
		}

		function listWPCategories(){
			$sp_categories = get_option("sp_categories");
			if(!empty($sp_categories)){
				$sp_categories = implode(",", $sp_categories);
			}
			$categories = get_categories(
				array('orderby' => 'name','order' => 'ASC', 
					  'exclude' => $sp_categories, 'hide_empty' => 0));
			?>
			<h2>WordPress Categories</h2>
			<ul id="non_sp_cats" class="categories_list">
			<?php foreach($categories as $category){?>
				<li class="stuffbox" wpcat_id="<?php echo $category->term_id ?>">
					<span class="wp_cat_title">
						<a href="<?php echo admin_url('edit-tags.php?action=edit&taxonomy=category&tag_ID=' . $category->cat_ID . '&post_type=post') ?>">
						<?php echo $category->name ?>
						</a>
					</span>
				</li>
			<?php } ?>
			</ul>
			<p>
				Drag a WP Category to the SmartPost Categories 
				list to enable it
			</p>					
			<?php		
		}
		
		function smartpost_admin_page(){
			if (!current_user_can('manage_options'))  {
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			$sp_category = new sp_category(null, null, $_GET['catID']);
			$sp_categories = get_option('sp_categories');
			
			?>
			<div class="wrap">
				<div id="sp_errors"></div>
				<h2>SmartPost</h2>
				<div id="categories_sidebar" class="postbox">
						<?php self::listSPCategories(); ?>
						<?php self::listWPCategories(); ?>
				</div><!-- end #categories_sidebar -->
				<div id="category_settings" class="postbox">
					<div id="setting_errors"></div>
					<div id="the_settings">
					<?php 
							if( empty($sp_categories) ){
								echo self::newCatForm();
							}else{
								if( empty($_GET['catID'])){
									$sp_category = new sp_category(null, null, $sp_categories[0]);
								}
								echo self::renderSPCatSettings($sp_category);
							}
					?>
					</div>
				</div><!-- end #category_settings -->
				<div class="clear"></div>
			</div>
			<?php
		}
		
	}
}
?>