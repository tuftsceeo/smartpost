<?php
if (!class_exists("sp_postVideo")) {
	/**
	 * Extends sp_postComponent.
	 * Upload and view videos on an HTML5 player.
	 * @see sp_postComponent
	 */

	class sp_postVideo extends sp_postComponent{

        public $beingConverted = false;
        public $just_converted = false; // Flag signifying the conversion script just finished running
        public $videoAttachmentIDs = array(); // An array of video attachment IDs, format: array('{video format}' => {attachment ID})
        public $error = false;
        public $description;

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                               $name = '', $value = '', $postID = 0){

            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

            //Set default options from category component for new instances
            if($compID == 0){
                $this->options = sp_catComponent::getOptionsFromID($catCompID);
            }

            // Retrieves any stored options from the db
            $this->initComponent($compInfo);

            // Update any post component options
            $this->beingConverted = $this->value->beingConverted;
            $this->videoAttachmentIDs = $this->value->videoAttachmentIDs;
            $this->just_converted = $this->value->just_converted;
            $this->description = $this->value->description;
            $this->error = $this->value->error;
            $this->convertToHTML5  = get_site_option( 'sp_html5_encoding' );

            // Check first if the video was just converted
            $this->check_converted_video();
        }

        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postVideoAJAX.php');
            sp_postVideoAJAX::init();
            self::enqueue_sp_video_js();
            self::enqueue_sp_video_css();
        }

        static function enqueue_sp_video_css(){
            wp_register_style( 'sp_postVideoCSS', plugins_url('css/sp_postVideo.css', __FILE__) );
            wp_enqueue_style( 'sp_postVideoCSS' );
            wp_enqueue_style( 'wp-mediaelement' );
        }

        static function enqueue_sp_video_js(){
            wp_register_script( 'sp_postVideoJS', plugins_url('js/sp_postVideo.js', __FILE__), array( 'jquery', 'sp_globals', 'sp_postComponentJS', 'plupload-all', 'wp-mediaelement' ) );
            wp_enqueue_script( 'wp-mediaelement' );
            wp_enqueue_script( 'sp_postVideoJS' );
            wp_localize_script( 'sp_postVideoJS', 'sp_postVideoJS', array(
                    'SP_VIDEO_HTML5' => (bool) get_site_option( 'sp_html5_encoding' )
                )
            );
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode($value = ""){
            $html = '<div id="sp_video-' . $this->ID . '" class="sp_video" data-compid="' . $this->ID . '" style="text-align: center;">';

            // Create an editor area for a video description
            $html .= sp_core::sp_editor(
                $this->description,
                $this->ID,
                false,
                'Click here to add a video description ...',
                array('data-action' => 'saveVideoDescAJAX', 'data-compid' => $this->ID, 'data-postid' => $this->postID )
            );

            if( $this->error ){
                $html .= '<div class="sp-comp-errors" style="padding:10px; margin:20px; text-align: left; display: inherit;">';
                $html .= $this->error;
                $html .= '</div>';
            }

            if( !$this->beingConverted ) {

                    $html .= $this->renderPlayer();

                    $html .= '<div id="videoUploader-' . $this->ID .'" class="videoUploader">';
                        $html .= '<p id="videoProgressMsg-' . $this->ID .'" class="videoProgressMsg"></p>';
                        $html .= '<div id="videoProgBarContainer-' . $this->ID . '" class="videoProgBarContainer">';
                            $html .= '<div id="videoProgressBar-' .$this->ID .'" class="videoProgressBar"></div>';
                        $html .= '</div>';
                        $html .= '<div class="clear"></div>';
                    $html .= '</div>';

                    $html .= '<div id="sp_videoDropZone-' . $this->ID . '" class="sp_videoDropZone">';
                        $html .= '<button type="button" data-compid="' . $this-> ID .'" id="sp-video-browse-' . $this->ID . '" class="sp-video-browse sp-browse-button button">';
                            $html .= '<img src="' . sp_core::getIcon( $this->typeID ) . '" /> Upload a Video';
                        $html .= '</button>';
                        $html .= '<p>You can also drag and drop a video file here</p>';
                        $html .= !get_site_option( 'sp_html5_encoding' ) ? '<p>Note: only .mp4 files are allowed.</p>' : '';
                        $html .= '<input id="sp_videoBrowse-' . $this->ID .'" data-compid="' . $this->ID . '" type="file" style="display:none;">';
                    $html .= '</div>';
            }else{
                $html .= '<p><img src="' . SP_IMAGE_PATH . '/loading.gif" /> Your video is being processed, thank you for your patience!</p>';
            }
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = '';

            // Don't display anything if there is an error
            if( $this->error ){
                return $html;
            }

            $html = '<div id="sp_video-' . $this->ID . '" class="sp_video" data-compid="' . $this->ID . '">';
                $html .= '<div id="sp-video-desc-' . $this->ID . '" class="sp-video-desc">' . $this->description . '</div>';
                $html .= $this->renderPlayer();
            $html .= '</div>';

            return $html;
        }

        /**
         * @return string
         */
        function renderPreview(){
            return $this->description;
        }

        /**
         * Renders an HTML5 video player for the video associated with the component instance.
         * If a video is not found, it will return an empty string;
         * @return string
         */
        function renderPlayer(){
            $html = '';

            if( $this->beingConverted ){
                $html .= '<p><img src="' . SP_IMAGE_PATH . '/loading.gif" /> Your video is being processed - feel free to come back at a later time to see if the video is ready for viewing!</p>';
                return $html;
            }

            $width  = get_site_option( 'sp_player_width' );
            $height = get_site_option( 'sp_player_height' );

            // If the video is done converting and we have the .mp4 file, render the <video> elem
            if( !$this->beingConverted && isset( $this->videoAttachmentIDs['mp4'] ) ){
                $mp4_vid  = wp_get_attachment_url( $this->videoAttachmentIDs['mp4'] );
                $html .= '<div id="playerContainer-' . $this->ID . '" style="width: ' . $width . 'px; max-width: 100%; margin-right: auto; margin-left: auto;">';
                    $html .= '<video class="wp-video-shortcode sp-video-player" width="' . $width . '" height="' . $height . '" preload="metadata" controls>';
                    $html .= '<source type="video/mp4" src="' . $mp4_vid . '">';
                    $html .= '</video>';
                $html .= '</div>';
            }
            return $html;
        }

        /**
         * Overload parent function since we need to delete all the attachments
         * As we delete all the attachments
         *
         * @return bool|int false on failure, number of rows affected on success
         */
        function delete(){
            global $wpdb;
            if( !empty($this->videoAttachmentIDs) ){
               foreach($this->videoAttachmentIDs as $attach_id){
                    wp_delete_attachment($attach_id, true);
               }
            }
            $tableName = $wpdb->prefix . 'sp_postComponents';
            return $wpdb->query($wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $this->ID ) );
        }

        /**
         * Writes member variables to the database:
         * - $this->beingConverted     : Whether the video is in the process of being converted
         * - $this->videoAttachmentIDs : array containing the formats and attachment IDs of video files in the format: array({format} => {attachment id});
         * @return bool|int
         */
        function update(){
            $videoData = new stdClass();
            $videoData->beingConverted = (bool) $this->beingConverted;
            $videoData->videoAttachmentIDs = $this->videoAttachmentIDs;
            $videoData->description = $this->description;
            $videoData->just_converted = $this->just_converted;
            $videoData = maybe_serialize( $videoData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $videoData, '%s');
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            return !isset( $this->videoAttachmentIDs['mp4'] );
        }

        /**
         * Checks if an uploaded video was just converted and
         * 1) Adds the converted video as an attachment
         * 2) Adds the video thumb .png as an attachment
         * 3) Deletes the original file
         * 4) Sets the video thumb .png as the featured image if one was not already set
         */
        function check_converted_video(){

            if( $this->just_converted ){

                // Get rid of original uploaded file
                unlink( $this->videoAttachmentIDs['uploaded_video'] );
                $this->just_converted = false;
                $this->update();

                // Add the encoded video as a new attachment
                $encoded_video = $this->videoAttachmentIDs['encoded_video'];
                $video_path_info = pathinfo( $encoded_video );
                $this->videoAttachmentIDs['mp4'] = sp_core::create_attachment( $encoded_video, $this->postID, $video_path_info['filename'] . '.mp4' );

                // Add the png as a new attachment
                $png_filename = $this->videoAttachmentIDs['png_file'];
                if( file_exists( $png_filename ) ){
                    $this->videoAttachmentIDs['img'] = sp_core::create_attachment( $png_filename, $this->postID, $video_path_info['filename'] . '.png' );
                    if( !has_post_thumbnail( $this->postID ) ){
                        set_post_thumbnail( $this->postID, $this->videoAttachmentIDs['img']);
                    }
                }

                $this->update();
            }
        }
	}
}
