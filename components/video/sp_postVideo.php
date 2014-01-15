<?php
if (!class_exists("sp_postVideo")) {
	/**
	 * Extends sp_postComponent.
	 * Upload and view videos on an HTML5 player.
	 * @see sp_postComponent
	 */

	class sp_postVideo extends sp_postComponent{

        public $beingConverted = false;
        public $videoAttachmentIDs = array(); // An array of video attachment IDs, format: array('{video format}' => {attachment ID})

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
            $this->beingConverted     = $this->value->beingConverted;
            $this->videoAttachmentIDs = $this->value->videoAttachmentIDs;
            $this->convertToHTML5  = get_site_option( 'sp_html5_encoding' );
        }

        static function init(){
            require_once('ajax/sp_postVideoAJAX.php');
            sp_postVideoAJAX::init();
            self::enqueueJS();
            self::enqueueCSS();
        }

        static function enqueueCSS(){
            wp_register_style( 'sp_postVideoCSS', plugins_url('css/sp_postVideo.css', __FILE__) );
            wp_enqueue_style( 'sp_postVideoCSS' );
            wp_enqueue_style( 'wp-mediaelement' );
        }

        static function enqueueJS(){
            wp_register_script( 'sp_postVideoJS', plugins_url('js/sp_postVideo.js', __FILE__), array( 'jquery', 'sp_globals', 'sp_postComponentJS', 'plupload-all', 'wp-mediaelement' ) );
            wp_enqueue_script( 'wp-mediaelement' );
            wp_enqueue_script( 'sp_postVideoJS' );
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode($value = ""){
            $html = '<div id="sp_video-' . $this->ID . '" class="sp_video" data-compid="' . $this->ID . '" style="text-align: center;">';
                $html .= $this->renderPlayer();
                if( !$this->beingConverted ) {
                    $html .= '<div id="videoUploader-' . $this->ID .'" class="videoUploader">';
                        $html .= '<p id="videoProgressMsg-' . $this->ID .'" class="videoProgressMsg"></p>';
                        $html .= '<div id="videoProgBarContainer-' . $this->ID . '" class="videoProgBarContainer">';
                            $html .= '<div id="videoProgressBar-' .$this->ID .'" class="videoProgressBar"></div>';
                        $html .= '</div>';
                        $html .= '<div class="clear"></div>';
                    $html .= '</div>';

                    $html .= '<p id="sp_videoDropZone-' . $this->ID . '" class="sp_videoDropZone">';
                        $html .= 'Drag and drop a video file here';
                        $html .= '<br /><br /> Or <br /><br />';
                        $html .= 'Browse for a video: <input id="sp_videoBrowse-' . $this->ID .'" data-compid="' . $this->ID . '" type="file">';
                        $html .= '<p>Note: We currently only support .mov and .avi video files.</p>';
                    $html .= '</p>';
                }
            $html .= '</div>';
            return $html;
        }

        /**
         * Renders an HTML5 video player for the video associated with the component instance.
         * If a video is not found, it will return an empty string;
         * @return string
         */
        function renderPlayer(){
            $html = '';

            if( $this->beingConverted ){
                $html .= '<p><img src="' . SP_IMAGE_PATH . '/loading.gif" /> This video is currently being processed. Please check back in a few minutes.</p>';
                return $html;
            }

            $width  = get_site_option( 'sp_player_width' );
            $height = get_site_option( 'sp_player_height' );
            $media_elem_path = includes_url('js/mediaelement');


            // If the video is done converting and we have both .mp4 and .webm files, render <video> elem with both
            if( !$this->beingConverted && isset( $this->videoAttachmentIDs['mp4'] ) && isset( $this->videoAttachmentIDs['webm'] ) && isset( $this->videoAttachmentIDs['ogv'] ) ){

                $mp4_vid  = wp_get_attachment_url( $this->videoAttachmentIDs['mp4'] );
                $webm_vid = wp_get_attachment_url( $this->videoAttachmentIDs['webm'] );
                $ogv_vid  = wp_get_attachment_url( $this->videoAttachmentIDs['ogv'] );
                $img_vid  = wp_get_attachment_url( $this->videoAttachmentIDs['img'] );

                $html .= '<div id="playerContainer-' . $this->ID . '" style="width: ' . $width . 'px; height: ' . $height . 'px; margin-right: auto; margin-left: auto;">';
                    $html .= '<video class="sp-video-player" width="' . $width . '" height="' . $height . '" preload="metadata" controls>';
                        $html .= '<source type="video/mp4" src="' . $mp4_vid . '">';
                        $html .= '<source type="video/webm" src="' . $webm_vid . '">';
                        $html .= '<source type="video/ogg" src="' . $ogv_vid . '">';
                        $html .= '<object width="' . $width . '" height="' . $height . '" type="application/x-shockwave-flash" data="' . $media_elem_path . '/flashmediaelement.swf">';
                            $html .= '<param name="movie" value="' . $media_elem_path . '/flashmediaelement.swf" />';
                            $html .= '<param name="flashvars" value="controls=true&file=' . $mp4_vid . '" />';
                            $html .= '<!-- Image as a last resort -->';
                            $html .= '<img src="' . $img_vid . '" width="' . $width . '" height="' . $height . '" title="No video playback capabilities" />';
                        $html .= '</object>';
                    $html .= '</video>';
                $html .= '</div>';
            }else if( $this->videoAttachmentIDs['mov'] ){ // Otherwise just render the original
                $html .= '<div id="playerContainer-' . $this->ID . '" style="width: ' . $width . 'px; height: ' . $height . 'px; margin-right: auto; margin-left: auto;">';
                    $html .= '<video class="sp-video-player" width="' . $width . '" height="' . $height . '" preload="metadata" controls>';
                        $html .= '<source src="' . wp_get_attachment_url( $this->videoAttachmentIDs['mov'] ) . '" type="video/mp4">';
                        $html .= 'Your browser does not support HTML5 video playback!';
                    $html .= '</video>';
                $html .= '</div>';
            }

            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html .= '<div id="sp_video-' . $this->ID . '" class="sp_video" data-compid="' . $this->ID . '">';
                $html .= $this->renderPlayer();
            $html .= '</div>';
            return $html;
        }

        /**
         * !TODO: Use ffmpeg to create video thumbs
         * @return string
         */
        function renderPreview(){
            return '';
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
         * @see parent::addMenuOptions()
         */
        protected function addMenuOptions(){
            return array();
        }


        /**
         * Writes member variables to the database:
         * - $this->beingConverted      - Whether the video is in the process of being converted
         * - $this->videoAttachmentIDs - array containing the formats and attachment IDs of video files
         *                                    in the format: array({format} => {attachment id});
         * @param $data - unnecessary
         * @return bool|int
         */
        function update($data = null){
            $videoData = new stdClass();
            $videoData->beingConverted     = (bool) $this->beingConverted;
            $videoData->videoAttachmentIDs = $this->videoAttachmentIDs;
            $videoData = maybe_serialize($videoData);
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $videoData, '%s');
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            return empty($this->value);
        }

	}
}
