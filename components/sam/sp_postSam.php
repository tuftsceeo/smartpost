<?php 
if (!class_exists("sp_postSam")) {
   /**
    * Extends sp_postComponent
    *
    * @see sp_postComponent
    */
    
    class sp_postSam extends sp_postComponent{

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                             $name = '', $value = '', $postID = 0){
            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

            //Set the default content options
            $contentOptions = sp_catSam::getOptionsFromID($catCompID);
            $this->options = $contentOptions;

            $this->initComponent($compInfo);
            
            if( !empty($this->value) ){
                $this->description = $this->value->description;
                $this->fps = $this->value->fps;
                $this->imgs = $this->value->imgs;
                $this->movie = $this->value->movie;
                $this->movie_id = $this->value->movie_id;
            }
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode($value = ""){
            $html = '<div id="sp_sam-' . $this->ID . '"class="sp_sam" data-compid="' . $this->ID . '" data-postid="'.$this->postID.'">';
            
            // Create an editor area for a SAM description
            $html .= sp_core::sp_editor(
                $this->description,
                $this->ID,
                false,
                'Create a description for your stop action movie...',
                array('data-action' => 'saveSamDescAJAX', 'data-compid' => $this->ID, 'data-postid' => $this->postID )
            );

            if( file_exists( $this->movie ) ){
                $html .= '<p style="text-align: center;"><b>Note:</b> You have a previously saved SAM movie, <a href="' . get_permalink( $this->postID ) . '">click here</a> to view it!</p>';
            }

            $html .= '<table id="samContainer-'. $this->ID .'" class="samContainer" cellpadding="0" style="float:left">';
                $html .= '<tr id="samVideoContainer-'. $this->ID .'" class="samVideoContainer">';
                    $html .= '<td style="padding:0px">';
                            $html .= '<div id="samEmptyVideo-'. $this->ID .'" class="samEmptyVideo">';
                                $html .= '<span>';
                                $html .= '<div> We need access to your webcam! </div>';
                                $html .= '<div> (should be in the top right) </div>';
                            $html .= '</span>';
                        $html .= '</div>';
                        $html .= '<canvas id="samCanvas-'. $this->ID .'" width="440" height="330" style="display:none" class="samCanvas"> </canvas>';
                        $html .= '<canvas id="samOverlay-'. $this->ID .'" width="440" height="330" style="display:none" class="samOverlay"> </canvas>';
                        $html .= '<div id="samFrameControls">';
                            $html .= '<button id="samRecordFrame-'. $this->ID .'" class="samRecordFrame"/>';
                            $html .= '<button id="samPlayButton-'. $this->ID .'" class="samPlayButton"/>';
                        $html .= '</div>';
                    $html .= '</td>';
                $html.= '</tr>';
                $html .= '<tr id="samExtraControls">';
                    $html .= '<td>';
                        $html .= '<p id="samFramesIndicator-'. $this->ID .'" class="samFramesIndicator" style="text-align: center;"> 0/60 </p>';
                        $html .= '<button id="samToggleOverlay-'. $this->ID .'" class="samToggleOverlay"/>';
                        $html .= '<button id="samRedoButton-'. $this->ID .'" class="samRedoButton" data-compid="' . $this->ID . '">Redo</button>';
                        $html .= '<p id="download-sam-movie-' . $this->ID . '" class="download-sam-movie">';
                            $html .= '<button id="download-sam-movie-button-' . $this->ID . '" class="download-sam-movie-button" data-compid="' . $this->ID .'">Save SAM Movie</button>';
                        $html .= '</p>';
                    $html .= '</td>';
                $html .= '</tr>';
            $html .='</table>';
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = '<div id="sp_sam-'. $this->ID .'" style="margin: 20px">';
                $html .= '<p>' . $this->description . '</p>';

                if( file_exists( $this->movie ) ){
                    $html .= '<div id="samPlayerContainer-' . $this->ID . '" style="width: 440px; max-width: 100%; margin-right: auto; margin-left: auto;">';
                        $html .= '<video class="wp-video-shortcode sp-video-player" width="440" height="330" preload="metadata" controls>';
                            $html .= '<source type="video/mp4" src="' . wp_get_attachment_url( $this->movie_id )  . '">';
                        $html .= '</video>';
                    $html .= '</div>';
                }

            $html .= '</div>';
            
            return $html;
        }

        /**
         * @see parent::render()
         * @return string
         */
        function renderPreview(){
            return self::renderViewMode();
        }

        /**
         * Initializes the class with AJAX functions, JS and CSS.
         * @return mixed|void
         */
        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postSamAJAX.php');
            sp_postSamAJAX::init();
            self::enqueueJS();
            self::enqueueCSS();
        }

        /**
         * Enqueues the JS to the page.
         */
        static function enqueueJS(){
            wp_register_script( 'sp_postSam', plugins_url('/js/sp_postSam.js', __FILE__));
            wp_enqueue_script( 'sp_postSam', null, array( 'jquery', 'sp_globals', 'sp_postComponentJS', 'sp_postJS' ) );
        }
        
        /**
         * Enqueues the CSS to the page.
         */
        static function enqueueCSS(){
            wp_register_style( 'sp_postSamCSS', plugins_url('/css/sp_postSam.css', __FILE__));
            wp_enqueue_style( 'sp_postSamCSS' );
        }


        /**
         * Returns an array of all the images for this particular SAM instance.
         * @return array
         */
        function getImgs(){
            return $this->imgs;
        }
        
        
        /**
         * Write member variables to database
         * @see parent::update();
         */
        function update(){
            $samData = new stdClass();
            $samData->description = $this->description;
            $samData->fps = $this->fps;
            $samData->imgs = $this->imgs;
            $samData->movie = $this->movie;
            $samData->movie_id = $this->movie_id;
            $samData = maybe_serialize( $samData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $samData, '%s');
        }

        function delete(){
            global $wpdb;

            // Clean up movie
            if( file_exists($this->movie) ){
                wp_delete_attachment( $this->movie_id );
            }

            // Clean up images
            if( is_array( $this->imgs) && count( $this->imgs ) > 0 ){
                foreach( $this->imgs as $img ){
                    if( file_exists( $img ) ){
                        unlink( $img );
                    }
                }
            }

            $tableName = $wpdb->prefix . 'sp_postComponents';
            return $wpdb->query( $wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $this->ID ) );
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            return empty($this->movie);
        }
    }
}
