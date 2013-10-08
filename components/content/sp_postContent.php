<?php 
if (!class_exists("sp_postContent")) {
	/**
	 * Extends sp_postComponent
	 * 
	 * @see sp_postComponent
	 */	
	class sp_postContent extends sp_postComponent{
			
			function __construct($compID = 0, $catCompID = 0, $compOrder = 0, 
																								$name = '', $value = '', $postID = 0){
					$compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");
					
					//Set the default content options
					$contentOptions = sp_catComponent::getOptionsFromID($catCompID);
					$this->options = $contentOptions;
					
					$this->initComponent($compInfo);
			}

			/**
			 * @see parent::renderEditMode()
			 */ 
			function renderEditMode($value = ""){
				$contentOptions =  $this->options;
					$html .= $contentOptions->richtext ? '<div id="editorPanel-' . $this->ID . '" class="editorPanel"></div>' : '';
					$html .= '<div id="sp_content-' . $this->ID . '" class="' . ($contentOptions->richtext ? 'sp_richtext' : 'sp_plaintext') . ' editable" data-compid="' . $this->ID . '" data-richtext="' . ($contentOptions->richtext ? 'true' : '') . '">';
						if(empty($this->value)){
							$html .= 'Click to add content'; //may want to create a $this->defaultVal
						}else{
							$html .=  trim($this->value);
						}
					$html .= '</div>';
				return $html;
			}

			/**
			 * @see parent::renderViewMode()
			 */ 			
			function renderViewMode(){
				$html .= '<div id="sp_content">';
					$html .= $this->value;
				$html .= '</div>';
				return $html;
			}
			
			function renderPreview(){
				return self::renderViewMode();
			}
			
			static function init(){
				require_once('ajax/sp_postContentAJAX.php');
				sp_postContentAJAX::init();
				self::enqueueJS();
			}
			
			static function enqueueJS(){
				wp_register_script( 'sp_postContent', plugins_url('/js/sp_postContent.js', __FILE__));
				wp_enqueue_script( 'sp_tiny_mce',    null, array( 'jquery', 'sp_globals' )  );
				wp_enqueue_script( 'sp_postContent', null, array( 'jquery', 'sp_globals', 'sp_postComponentJS', 'sp_tiny_mce' ) );				
			}
			
			/**
			 * @see parent::addMenuOptions()
			 */
			protected function addMenuOptions(){
				return array();
			}
				
			/**
			 * @see parent::update();
			 */
			function update($content = null){
				$this->value = (string) $content;
				return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $content, '%s');					
			}
			
			/**
			 * @see parent::isEmpty();
			 */
			function isEmpty(){
				$strippedContent = trim(strip_tags($this->value));
				return empty($strippedContent);
			}			
			
	}
}
