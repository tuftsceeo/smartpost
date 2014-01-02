<?php 
if (!class_exists("sp_postGallery")) {
	/**
	 * Extends sp_postComponent.
	 * Front end of the gallery component (used in posts).
     *
	 * @see sp_postComponent
	 */	
	class sp_postGallery extends sp_postComponent{
        private $attachmentIDs = array(); //An array of attachment IDs

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                             $name = '', $value = '', $postID = 0){

                $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

                if($compID == 0){
                    //Set default options from category component
                    $this->options = sp_catComponent::getOptionsFromID($catCompID);
                }

                $this->initComponent($compInfo);

                //Get image IDs (in WP land they're called attachments)
                if( !empty($this->value) ){
                    $this->attachmentIDs = $this->value;
                }
        }
			
        /**
         * @see parent::init()
         */
        static function init(){
            require_once('ajax/sp_postGalleryAJAX.php');
            sp_postGalleryAJAX::init();
            self::enqueueCSS();
            self::enqueueJS();
        }
			
			static function enqueueCSS(){
                /*
					wp_register_style( 'sp_postGalleryCSS', plugins_url('css/sp_postGallery.css', __FILE__) );
					wp_enqueue_style( 'sp_postGalleryCSS' );
                */
			}
			
			static function enqueueJS(){
                //enqueue photo gallery related JS files
            }

			/**
			 * @see parent::renderEditMode()
			 */			
			function renderEditMode(){
				
				$html .= '<div id="sp_Gallery-' . $this->ID .'" class="sp_Gallery" data-compid="' . $this->ID .'" data-isgallery="' . (string) $this->isGallery . '">';
					$html .= '<div id="sp_uploads-' . $this->ID . '" class="sp_uploads">';

						$html .= '<p>';
							$html .= 'Drag and drop files here! ';
							$html .= $this->imagesAllowed ? 'Uploading photo(s)? Use your <a href="#" data-compid="' . $this->ID . '" class="sp_webcam_click"> webcam </a>!' : '';
						$html .= '</p>';
						
						if($this->imagesAllowed){
								$html .= '<p></p>';
								$html .= '<div id="sp_Gallery_webcam-' . $this->ID .'" data-compid="' . $this->ID . '" class="sp_Gallery_webcam">';
									$html .= '<div class="clear"></div>';								
									$html .= '<a href="javascript:webcam.capture();">Take photo!</a> | <a href="javascript:sp_postGallery.cancelCam(' . $this-> ID . ');"> Cancel </a>';
								$html .= '</div>';
						}
						
						//Fallback upload browser
						$html .= '<div id="sp_browse-' . $this->ID . '" class="sp_browse">';
							$html .= '<form id="sp_browse_upload-' . $this->ID . '" method="post" action="">';
								$html .= '<input type="file" id="sp_upload-' . $this->ID . '" />';
								$html .= '<button type="submit">Upload</button>';
							$html .= '</form>';
						$html .= '</div>';

						$html .= '<div class="clear"></div>';
					$html .= '</div><!-- end #sp_uploads-' . $this->ID . ' -->';
					
					$html .= '<div id="sp_attachments-' . $this->ID . '" class="sp_attachments">';					
						if( !empty($this->attachmentIDs) ){
								foreach($this->attachmentIDs as $id){
									$html .= self::renderSingleThumb($id, $this->ID, $this->isGallery);
								}
						}					
						
						if( !$this->isGallery && !empty($this->attachmentIDs) ){
							$html .= self::renderSingleDesc($this->attachmentIDs[0], $this->ID);
						}
					$html .= '</div>';
					
					$html .= '<div class="clear"></div>';
				$html .= '</div>';
				return $html;
			}
			
			/**
			 * @see parent::renderViewMode()
			 */			
			function renderViewMode(){
				$html .= '<div id="sp_Gallery-' . $this->ID .'" class="sp_Gallery" data-isgallery="' . (string) $this->isGallery . '">';
					$html .= '<div id="sp_attachments-' . $this->ID . '" class="sp_attachments">';					
						if( !empty($this->attachmentIDs) ){
								foreach($this->attachmentIDs as $id){
									$html .= self::renderSingleThumb($id, $this->ID, $this->isGallery, false);
								}
						}
						
						if( !$this->isGallery && !empty($this->attachmentIDs) ){
							$html .= self::renderSingleDesc($this->attachmentIDs[0], $this->ID, false);
						}
					$html .= '</div>';
						
					$html .= '<div class="clear"></div>';
					$html .= '</div>';
				return $html;		
			}			

			/**
			 * Renders single attachment description
			 */
			static function renderSingleDesc($id, $compID, $editMode = true){
				$html = "";
				if ( !empty($id) )
						$attachment = get_post($id);
				
				if( !empty($attachment) ){
					$descPlaceholder = $editMode ? 'Click to add a description' : '';
					$editable        = $editMode ? 'editable' : '';
					$html .= '<div id="sp_Gallery_desc-' . $id .'" class="sp_Gallery_desc ' . $editable . '" data-compid="' . $compID . '" attach-id="' . $attachment->ID . '">';
						$html .= empty( $attachment->post_content ) ? $descPlaceholder : $attachment->post_content;
					$html .= '</div>';
				}
				
				return $html;
			}

			/**
			 * Renders a single attachment thumbnail
			 */
			static function renderSingleThumb($id, $compID, $gallery = false, $editMode = true){
				$attachment = get_post($id);
				$html = "";
				if( !is_null($attachment) ){
					$class = $gallery ? 'gallery_thumb' : 'sp_Gallery_thumb';
					$html .= '<div id="Gallery_thumb-' . $attachment->ID . '" data-compid="' . $compID . '" class="' . $class . '">';
						
						if( strpos($attachment->post_mime_type, 'image') !== false && $gallery ){
							$hrefAttrs = 'class="fancybox" rel="gallery-' . $compID .'" title="' .$attachment->post_title . '" ';
						}
						
						$html .= '<a href="' . wp_get_attachment_url($id) . '"' . $hrefAttrs .'>';
							$html .= wp_get_attachment_image($id, array(100, 60), true);
						$html .= '</a>';
						
						$html .= '<p id="Gallery_caption-' . $attachment->ID . '" class="sp_GalleryCaption">' . $attachment->post_title . '</p>';
						$html .= $editMode ? '<img src="' . IMAGE_PATH . '/no.png" id="deleteThumb-' . $id .'" name="deleteThumb-' . $id .'" data-attachid="' . $id . '" data-compid="' . $compID . '" class="sp_GalleryDelete" alt="Delete Attachment" title="Delete Attachment">' : '';
					$html .= '</div>';
				}
				return $html;
			}
			
			function renderPreview(){
				//!To-do: include Gallery description
				if(!$this->isGallery){
					$attachmentIDs = $this->attachmentIDs;
					$attachment    = get_post($attachmentIDs[0]);
					$html = $attachment->post_content;
				}else{
					$html = $this->galleryDesc;
				}
				
				return $html;
			}

			/**
			 * @see parent::addMenuOptions()
			 */			
			function addMenuOptions(){
				return array();
			}
			
			/**
			 * Overload parent function since we need to delete all the attachments
			 * As we delete all the attachments
			 * 
			 * @return bool|int false on failure, number of rows affected on success
			 */
			function delete(){
				global $wpdb;
				
				if( !empty($this->attachmentIDs) ){
					foreach($this->attachmentIDs as $id){
						if(get_post_thumbnail_id($this->postID) == $id){
							
						}
						wp_delete_attachment($id, true);
					}
				}
				
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
			 * Changes the acceptable extensions of the 
			 * 
			 * @param array $extensions Extensions of the form array( ".jpg" => 0|1 ), 
			 *																										where 1 is enabled, and 0 is disabled
			 */
			function update($extensions = null){ return null; }
			
			function isEmpty(){
				return empty($this->attachmentIDs);
			}
			
			function getExtensions(){
				return $this->allowedExts;
			}
			
			function getCustomExts(){
				return $this->customExts;
			}
			
			function isGallery(){
				return $this->isGallery;
			}
			
			function getDescription($id){
				$attachment = get_post($id);
				return $attachment->post_content;
			}
			
			/**
			 * Sets the description of a particular attachment 
			 *
			 * @param string $description The description of the attachment
			 * @param int $attachmentID The ID of the attachment
			 */
			static function setDescription($description, $attachmentID){
				$attachment = get_post($attachmentID);
				$attachment->post_content = $description;
				return wp_update_post($attachment);
			}
			
			function getAttachments(){
				return $this->attachmentIDs;
			}
			
			/**
			 * Sets the attachmentIDs. $idArray should be of the form array( 0 => id1, 1 => id2, etc.. )
			 *
			 * @param array $idArray An array contain attachment IDs
			 * @return bool True if update was succesful, otherwise false
			 */
			function setAttachmentIDs($idArray){
				
				if( !empty($idArray) ){
					$this->attachmentIDs = $idArray;
				}else{
					$this->attachmentIDs = array();
				}
				$attachmentIDs = maybe_serialize($this->attachmentIDs);
				return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $attachmentIDs, '%s');			
			}
	}
}
			
?>