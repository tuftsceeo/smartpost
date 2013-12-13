<?php
if (!class_exists("sp_catVideo")) {

    /**
     * Extends sp_catComponent
     * HTML5 Video category component - used to control the video post component.
     * Adds features to the dashboard to control video post components.
     *
     * @see sp_catComponent
     */
    define("DEBUG_SP_VIDEO", FALSE); //set to true to debug the video component

    class sp_catVideo extends sp_catComponent{

        public $convertToHTML5 = false;

        function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                                "options",	"order", "default", "required");


            //Set default video options
            if($compID == 0){
                $sp_hcli_path   = get_site_option('sp_hcli_path');
                $sp_ffmpeg_path = get_site_option('sp_ffmpeg_path');
                if( $sp_ffmpeg_path && $sp_hcli_path ){
                    $options->convertToHTML5 = true;
                }else{
                    $options->convertToHTML5 = false;
                }
                $this->options = $options;
            }
            $this->initComponent($compInfo);

            //Get updated media options after initializing the component
            $this->convertToHTML5 = $this->options->convertToHTML5;
        }

        /**
         * @see parent::installComponent()
         */
        function install(){

            self::installComponent('Video', 'Basic HTML5 video player', __FILE__);

            $sp_hcli_path   = get_site_option( 'sp_hcli_path' );
            $sp_ffmpeg_path = get_site_option( 'sp_ffmpeg_path' );

            if( !$sp_hcli_path && !$sp_ffmpeg_path ){

                $vidTool = array( 'HandBrakeCLI', 'ffmpeg' );
                $paths   = array( '', '/usr/bin/', '/usr/local/bin/' );

                if(DEBUG_SP_VIDEO){
                    error_log( 'Installing Video component.. ' );
                }

                foreach($paths as $path){
                    exec($path . $vidTool[0] . ' 2>&1', $hb_output, $hb_status);
                    exec($path . $vidTool[1] . ' 2>&1', $ffmpeg_output, $ffmpeg_status);

                    if( $hb_status != 127 ){
                        $path = empty($path) ? 'empty' : $path;
                        update_site_option( 'sp_hcli_path', $path );
                    }

                    if( $ffmpeg_status != 127 ){
                        $path = empty($path) ? 'empty' : $path;
                        update_site_option( 'sp_ffmpeg_path', $path );
                    }

                    if(DEBUG_SP_VIDEO){
                        error_log( 'hb_status: ' . $hb_status );
                        error_log( 'ffmpeg_status: ' . $ffmpeg_status );
                        error_log( 'HandBrakeCLI path: ' . $path . $vidTool[0] );
                        error_log( 'ffmpeg path: ' . $path . $vidTool[1] );
                        error_log( 'HandBrakeCLI output: ' . print_r($hb_output, true) );
                        error_log( 'ffmpeg output: ' . print_r($ffmpeg_output, true) );
                    }

                    if( $hb_status != 127 && $ffmpeg_status != 127 ){
                        break;
                    }
                }
            }

        }

        /**
         * !TODO: Add as another abstract method to all components and call when plugin is deleted.
         */
        function uninstall(){
            delete_site_option( 'sp_hcli_path' );
            delete_site_option( 'sp_ffmpeg_path' );
        }

        /**
         * Adds CSS / JS to stylize and handle any UI actions
         */
        static function init(){
            require_once('ajax/sp_catVideoAJAX.php');
            sp_catVideoAJAX::init();
            wp_register_script( 'sp_catVideoJS', plugins_url('js/sp_catVideo.js', __FILE__), array('jquery', 'sp_admin_globals', 'sp_admin_js') );
            wp_enqueue_script( 'sp_catVideoJS' );
        }

        /**
         * @see parent::componentMenu()
         */
        function componentMenu(){
            ?>
            <ul class="simpleMenu">
                <li class="stuffbox"><a href="#"><img src="<?php echo IMAGE_PATH . '/downArrow.png'; ?>" /></a>
                    <ul class="stuffbox">
                        <li><a href="#" class="delete_component" data-compid="<?php echo $this->ID ?>">Delete Component</a></li>
                    </ul>
                </li>
            </ul>
        <?php
        }

        /**
         * @see parent::componentOptions()
         */
        function componentOptions(){

            $sp_hcli_path   = get_site_option( 'sp_hcli_path' );
            $sp_ffmpeg_path = get_site_option( 'sp_ffmpeg_path' );

            if(DEBUG_SP_VIDEO){

                ?>
                <div style="border: 1px solid red;">
                    <p>Debug Info:</p>
                    <p>HandBrakeCLI path: <?php echo $sp_hcli_path ?></p>
                    <p>ffmpeg path: <?php echo $sp_ffmpeg_path ?></p>
                </div>
                <?php
            }

            if( $sp_hcli_path && $sp_ffmpeg_path ){
                $checked = $this->convertToHTML5 ? 'checked' : '';
                ?>

                <input type="checkbox" class="enableHTML5Video" id="html5video-<?php echo $this->ID ?>" compID="<?php echo $this->ID ?>" <?php echo $checked ?> />
                <label for="html5video-<?php echo $this->ID ?>">
                    The video component has detected that your server is able to convert
                    videos to a HTML5 video format. It is recommended to check the box
                    to enable this option (why should I enable this option?).
                </label>

                <?php
            }else{
                ?>
                <p>
                    The video component has not detected ffmpeg2theora or HandBrakeCLI for
                    video conversion. To convert uploaded videos to HTML5 compatible video
                    format, install ffmpeg2theora and HandBrakeCLI on your server. If you are
                    unsure how to install these utilities, contact your server administrator
                    for help.
                </p>
                <?php
            }
        }

        /**
         * @see parent::getOptions()
         */
        function getOptions(){
            return $this->options;
        }

        /**
         * Sets the option of whether to attempt to convert to HTML5 compatible video formats
         * using ffmpeg2theora and HandBrakeCLI command line utilities.
         * @see parent::setOptions()
         * @param $options
         * @return bool|int
         */
        function setOptions($options){
            $options = maybe_serialize($options);
            return sp_core::updateVar('sp_catComponents', $this->ID, 'options', $options, '%s');
        }
    }
}
?>