<?php 
if (!class_exists("sp_postGallery")) {
	/**
	 * Extends sp_postComponent.
	 * Front end of the gallery component (used in posts).
     *
	 * @see sp_postComponent
	 */
	class sp_postGallery extends sp_postComponent{
        public $attachmentIDs = array(); // An array of attachment IDs of the form array( 0 => id1, 1 => id2, etc.. )
        public $description   = ''; // Gallery Description

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                             $name = '', $value = '', $postID = 0){

            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

            if($compID == 0){
                // Set default options from category component
                $this->options = sp_catComponent::getOptionsFromID($catCompID);
            }

            $this->initComponent($compInfo);

            // Get attachments IDs
            if( !empty($this->value) ){
                $this->attachmentIDs = $this->value->attachmentIDs;
                $this->description   = $this->value->description;
            }
        }
			
        /**
         * @see parent::init()
         */
        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postGalleryAJAX.php');
            sp_postGalleryAJAX::init();
            self::enqueue_gallery_css();
            self::enqueue_gallery_js();
        }
			
        static function enqueue_gallery_css(){
            wp_register_style( 'sp_postGalleryCSS', plugins_url('css/sp_postGallery.css', __FILE__) );
            wp_enqueue_style( 'sp_postGalleryCSS' );

        }

        // Enqueue photo gallery related JS files
        static function enqueue_gallery_js(){
            wp_register_script( 'sp_postGalleryJS', plugins_url('/js/sp_postGallery.js', __FILE__), array( 'jquery', 'sp_globals', 'sp_postComponentJS' ) );
            wp_enqueue_script( 'sp_postGalleryJS' );
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode(){

            $html = '<div id="sp-gallery-' . $this->ID .'" class="sp-gallery" data-compid="' . $this->ID .'">';
                // Create an editor area for a video description
                $html .= sp_core::sp_editor(
                    $this->description,
                    $this->ID,
                    false,
                    'Click here to add a gallery description ...',
                    array('data-action' => 'saveGalleryDescAJAX', 'data-compid' => $this->ID, 'data-postid' => $this->postID )
                );

                $html .= '<div id="sp-gallery-container">';
                    $html .= '<div id="sp-gallery-pics-' . $this->ID . '" class="sp-gallery-pics">';
                        if( !empty($this->attachmentIDs) ){
                            foreach($this->attachmentIDs as $id){
                                $html .= self::renderThumb($id, $this->ID);
                            }
                        }
                    $html .= '</div><!-- end .sp-gallery-pic -->';
                    $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end #sp-gallery-container -->';

                // Render drop-zone/uploads area
                $html .= '<div id="sp-gallery-uploads-' . $this->ID . '" class="sp-gallery-uploads">';
                    $html .= '<span id="sp-gallery-progress-msg-' . $this->ID . '"></span>';
                    $html .= '<span id="sp-gallery-progress-' . $this->ID . '"></span>';
                    $html .= '<div class="sp-gallery-dropzone">';
                        $html .= '<button type="button" data-compid="' . $this-> ID .'" id="sp-gallery-browse-' . $this->ID . '" class="sp-gallery-browse sp-browse-button button">';
                            $html .= '<img src="' . sp_core::getIcon( $this->typeID ) . '" /> Upload Photos';
                        $html .= '</button>';
                        $html .= '<p>You can also drag and drop photos here</p>';
                        $html .= '<input type="file" id="sp-gallery-upload-' . $this->ID . '" style="display: none;" />';
                    $html .= '</div>';
                    $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end .sp-gallery-uploads -->';

                $html .= '<div class="clear"></div>';
            $html .= '</div>';
            return $html;
        }
			
        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = !empty($this->description) ? '<div id="sp-gallery-desc-' . $this->ID . '" class="sp-gallery-desc">' . $this->description . '</div>' : '';
            $html .= '<div id="sp-gallery-pics-' . $this->ID . '" class="sp-gallery-pics">';
            if( !empty($this->attachmentIDs) ){
                foreach($this->attachmentIDs as $id){
                    $html .= self::renderThumb($id, $this->ID, false);
                }
            }
            $html .= '</div><!-- end .sp-gallery-pic -->';
            return $html;
        }


        /**
         * Renders a single attachment thumbnail
         */
        static function renderThumb($id, $compID, $editMode = true){
            $attachment = get_post($id);
            $html = "";
            if( !is_null($attachment) ){
                $html .= '<div id="sp-gallery-thumb-' . $id . '" data-thumbid="' . $id . '" data-compid="' . $compID . '" class="sp-gallery-thumb">';
                    $html .= '<a href="' . wp_get_attachment_url($id) . '" rel="gallery-' . $compID .'" title="' . $attachment->post_content . '">';
                        $html .= wp_get_attachment_image($id, array(125, 125), true);
                    $html .= '</a>';

                    if($editMode){
                        /*
                        $captionEditor = sp_core::sp_editor( $attachment->post_content, $id, true, 'Add a caption ...', array( 'data-action' => 'gallerySetThumbCaptionAJAX', 'data-thumbid' => $id, 'title' => 'Photo caption' ) );
                        $html .= '<div id="sp-gallery-caption-' . $id . '" class="sp-gallery-caption">' . $captionEditor . '</div>';
                        $html .= '<span id="sp-gallery-edit-caption-' . $id .'" name="sp-gallery-edit-caption-' . $id .'" data-thumbid="' . $id . '" data-compid="' . $compID . '" class="sp-gallery-edit-caption sp_textIcon" alt="Edit Caption" title="Edit Caption"></span>';
                        */
                        $html .= '<span id="sp-gallery-delete-thumb-' . $id .'" name="sp-gallery-delete-thumb-' . $id .'" data-thumbid="' . $id . '" data-compid="' . $compID . '" class="sp-gallery-delete-thumb sp_xButton" alt="Delete Photo" title="Delete Photo"></span>';
                    }else{
                        // $html .= '<div id="sp-gallery-caption-' . $id . '" class="sp-gallery-caption" data-compid="' . $compID . '">' . $attachment->post_content . '</div>';
                    }

                $html .= '</div>';
            }
            return $html;
        }
			
        function renderPreview(){
            return $this->description;
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
                    wp_delete_attachment($id, true);
                }
            }

            $tableName = $wpdb->prefix . 'sp_postComponents';
            return $wpdb->query( $wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $this->ID ) );
        }
			
        function isEmpty(){
            return empty($this->attachmentIDs);
        }

        /**
         * Sets the description of a particular thumbnnail
         * @param $description The description of the attachment
         * @param $attachmentID The ID of the attachment
         * @return int|WP_Error
         */
        static function setThumbDescription($description, $attachmentID){
				$attachment = get_post($attachmentID);
				$attachment->post_content = $description;
				return wp_update_post($attachment);
        }

        /**
         * Returns an array of all the image attachments of this particular gallery instance.
         * @return array
         */
        function getAttachments(){
            return $this->attachmentIDs;
        }

        /**
         * Write member variables to database - i.e. equivalent to 'saving' the component instance in the
         * state that it's currently in.
         * @see parent::update();
         * @return bool|null
         */
        function update(){
            $galleryData = new stdClass();
            $galleryData->attachmentIDs = $this->attachmentIDs;
            $galleryData->description   = $this->description;
            $galleryData = maybe_serialize( $galleryData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $galleryData, '%s');
        }
	}
}