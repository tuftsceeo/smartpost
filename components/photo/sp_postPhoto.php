<?php 
if (!class_exists("sp_postPhoto")) {
	/**
	 * Extends sp_postComponent.
	 * Front end of the photo component (used in posts).
     *
	 * @see sp_postComponent
	 */
	class sp_postPhoto extends sp_postComponent{
        public $photoID = null; // The Photo ID
        public $caption = ''; // Photo Caption

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
                $this->photoID = $this->value->photoID;
                $this->caption = $this->value->caption;
            }
        }
			
        /**
         * @see parent::init()
         */
        static function init(){
            require_once('ajax/sp_postPhotoAJAX.php');
            sp_postPhotoAJAX::init();
            self::enqueueCSS();
            self::enqueueJS();
        }
			
        static function enqueueCSS(){
            wp_register_style( 'sp_postPhotoCSS', plugins_url('css/sp_postPhoto.css', __FILE__) );
            wp_enqueue_style( 'sp_postPhotoCSS' );
        }
			
        static function enqueueJS(){
            wp_register_script( 'sp_postPhotoJS', plugins_url('/js/sp_postPhoto.js', __FILE__) );
            wp_enqueue_script( 'sp_postPhotoJS', null, array( 'jquery', 'sp_globals', 'sp_postComponentJS' ) );
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode(){

            $html = '<div id="sp-photo-' . $this->ID .'" class="sp-photo" data-compid="' . $this->ID .'">';
                // Create an editor area for a video description
                $html .= sp_core::sp_editor(
                    $this->caption,
                    $this->ID,
                    false,
                    'Click here to add a photo caption ...',
                    array('data-action' => 'savePhotoDescAJAX', 'data-compid' => $this->ID, 'data-postid' => $this->postID )
                );

                $html .= '<div id="sp-photo-container-' . $this->ID .'" class="sp-photo-container">';
                        if( !empty($this->photoID) ){
                            $html .= self::renderThumb($this->photoID, $this->ID);
                        }
                    $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end #sp-photo-container -->';

                // Render drop-zone/uploads area
                $html .= '<div id="sp-photo-uploads-' . $this->ID . '" class="sp-photo-uploads">';
                    $html .= '<span id="sp-photo-progress-msg-' . $this->ID . '"></span>';
                    $html .= '<span id="sp-photo-progress-' . $this->ID . '"></span>';
                    $html .= '<div id="sp-photo-dropzone-' . $this->ID . '" class="sp-photo-dropzone">';
                        $html .= '<button type="button" data-compid="' . $this-> ID .'" id="sp-photo-browse-' . $this->ID . '" class="sp-photo-browse sp-browse-button button">';
                            $html .= '<img src="' . sp_core::getIcon( $this->typeID ) . '" /> Upload a Photo';
                        $html .= '</button>';
                        $html .= '<p>You can also drag and drop a photo here</p>';
                        $html .= '<input type="file" id="sp-photo-upload-' . $this->ID . '" style="display: none;" />';
                    $html .= '</div>';
                    $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end .sp-photo-uploads -->';

                $html .= '<div class="clear"></div>';
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         * @return string
         */
        function renderViewMode(){
            $html = '<div id="sp-photo-caption-' . $this->ID . '" class="sp-photo-caption">' . $this->caption . '</div>';
            $html .= '<div id="sp-photo-container-' . $this->ID .'" class="sp-photo-container">';
            if( !empty($this->photoID) ){
                $html .= self::renderThumb($this->photoID, $this->ID, false);
            }
                $html .= '<div class="clear"></div>';
            $html .= '</div><!-- end #sp-photo-container -->';
            return $html;
        }

        /**
         * Renders a single attachment thumbnail
         * @param $id
         * @param $compID
         * @param bool $editMode
         * @return string
         */
        static function renderThumb($id, $compID, $editMode = true){
            $attachment = get_post($id);
            $html = "";
            if( !is_null($attachment) ){
                $img_attrs = wp_get_attachment_image_src( $id, 'large' );
                $html .= '<div id="sp-photo-thumb-' . $id . '" data-thumbid="' . $id . '" data-compid="' . $compID . '" class="sp-photo-thumb">';
                    $html .= '<a href="' . wp_get_attachment_url($id) . '" class="sp-photo-link" rel="photo-' . $compID .'" title="' . $attachment->post_content . '">';
                        $html .= '<img src="' . $img_attrs[0] . '">';
                    $html .= '</a>';

                    if($editMode){
                        $html .= '<span id="sp-photo-delete-thumb-' . $id .'" name="sp-photo-delete-thumb-' . $id .'" data-thumbid="' . $id . '" data-compid="' . $compID . '" class="sp-photo-delete-thumb sp_xButton" alt="Delete Photo" title="Delete Photo"></span>';
                    }
                $html .= '<div class="clear"></div>';
                $html .= '</div>';
            }
            return $html;
        }

        /**
         * @see parent:;renderPreview()
         * @return string
         */
        function renderPreview(){
            return $this->caption;
        }

        /**
         * @see parent::delete()
         * @return bool|int false on failure, number of rows affected on success
         */
        function delete(){
            global $wpdb;

            if( !empty($this->photoID) ){
                wp_delete_attachment($this->photoID, true);
            }

            $tableName = $wpdb->prefix . 'sp_postComponents';
            return $wpdb->query( $wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $this->ID ) );
        }

        /**
         * @see parent::isEmpty()
         * @return bool
         */
        function isEmpty(){
            return empty($this->photoID);
        }

        /**
         * Write member variables to database - i.e. equivalent to 'saving' the component instance in the
         * state that it's currently in.
         * @see parent::update();
         * @return bool|null
         */
        function update(){
            $photoData = new stdClass();
            $photoData->photoID = $this->photoID;
            $photoData->caption = $this->caption;
            $photoData = maybe_serialize( $photoData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $photoData, '%s');
        }
	}
}