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
            
            $html .= '<span class="samClear">';
            $html .= '<table id="samContainer-'. $this->ID .'" class="samContainer" cellpadding="0" style="float:left">';
                $html .= '<tr id="samLabel-'. $this->ID .'" class="samLabel">';
                    $html .= '<td style="padding:0px"> <span style="height:30px">';
                        $html .= '<div id="samLogo-'. $this->ID .'" class="samLogo"></div>';
                        $html .= '<button id="samRedoButton-'. $this->ID .'" class="samRedoButton"/>';        
                        //$html .= '<button id="samSaveButton-'. $this->ID .'" class="samSaveButton"/>';
                    $html .= '</td>';
                $html .= '</tr>';
                $html .= '<tr id="samTimeline-'. $this->ID .'" class="samTimeline">';
                    $html .= '<td style="padding:0px"><span style="height:15px">';
                        $html .= '<div id="samFilledTime-'. $this->ID .'" class="samFilledTime"> </div>';
                        $html .= '<div id="samEmptyTime-'. $this->ID .'" class="samEmptyTime"> </div>';
                        $html .= '<div id="samFramesIndicator-'. $this->ID .'" class="samFramesIndicator"> 0/60 </div>';
                    $html .= '</span></td>';
                $html .= '</tr>';
                $html .= '<tr id="samVideoContainer-'. $this->ID .'" class="samVideoContainer">';
                    $html .= '<td style="padding:0px">';
                        $html .= '<div id="samEmptyVideo-'. $this->ID .'" class="samEmptyVideo"> <span>';
                            $html .= '<div> We need access to your webcam! </div>';
                            $html .= '<div> (should be in the top right) </div>';
                        $html .= '</span> </div>';
                        $html .= '<canvas id="samCanvas-'. $this->ID .'" width="440" height="330" style="display:none" class="samCanvas"> </canvas>';
                        $html .= '<canvas id="samOverlay-'. $this->ID .'" width="440" height="330" style="display:none" class="samOverlay"> </canvas>';
                        $html .= '<button id="samRecordFrame-'. $this->ID .'" class="samRecordFrame"/>';
                        $html .= '<button id="samPlayButton-'. $this->ID .'" class="samPlayButton"/>';
                    $html .= '</td>';
                $html.= '</tr>';
                $html .= '<tr id="samExtraControls">';
                    $html .= '<td>';
                        $html .= '<button id="samToggleOverlay-'. $this->ID .'" class="samToggleOverlay"/>';
                    $html .= '</td>';
                $html .= '</tr>';
            $html .='</table></span>';
            
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            //  is video empty??
            //    return "";
            //  else
            //    return video;
            $html = '<div id="sp_sam-'. $this->ID .'" style="margin: 20px">';
                $html .= '<div id="samPlaybackContainer-'. $this->ID .'" class="samPlaybackContainer">';
                    $html .= '<canvas id="samPlaybackCanvas-'. $this->ID .'" width="440" height="330" class="samPlaybackCanvas"';
                    $html .= 'style="border:1px solid black"> </canvas>';
                    $html .= '<button id="samPlayButton-'. $this->ID .'" class="samPlayButton"/>';
                $html.= '</div>';
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
            $samData->fps = $this->fps;
            $samData->imgs = $this->imgs;
            $samData = maybe_serialize( $samData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $samData, '%s');
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            // FOR TESTING CHANGE THIS
            return false;
            // END TESTING
            $strippedContent = trim(strip_tags($this->value));
            return empty($strippedContent);
        }
    }
}
