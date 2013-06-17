<?php
if (!class_exists("sp_catContent")) {

    /**
     * Extends sp_catComponent
     * Content category component. Defines administrative features
     * for the content component. Also used alongside sp_postComponent
     * for front-end handling.
     *
     * @see sp_catComponent
     */
    class sp_catContent extends sp_catComponent{

        function __construct($compID = 0, $catID = 0, $name = '',
                             $description = '', $typeID = 0, $order = 0,
                             $options = null, $default = false, $required = false){

            $compInfo = compact("compID", "catID", "name", "description", "typeID",
                "options",	"order", "default", "required");


            //Set default content options
            if($compID == 0){
                $this->options->plaintext = false;
                $this->options->richtext = true;
            }

            $this->initComponent($compInfo);
        }

        /**
         * @see parent::installComponent()
         */
        function install(){
            self::installComponent("Content Editor", "Rich and plain text editor. Uses the <a href='http://nicedit.com/'>NicEdit</a> as its editor.", __FILE__);
        }

        /**
         * Adds CSS / JS to stylize and handle any UI actions
         */
        static function init(){
            self::enqueueCSS();
            self::enqueueJS();
        }

        /**
         * Add content component JS
         */

        static function enqueueJS(){
            wp_register_script( 'sp_catContentJS', plugins_url('/js/sp_catContent.js', __FILE__));
            //wp_enqueue_script( 'sp_catContentJS', array('jquery', 'sp_admin_globals', 'sp_admin_js', 'sp_catComponentJS') );
        }

        /**
         * Add content component CSS
         */
        static function enqueueCSS(){
            wp_register_style( 'sp_catContentCSS', plugins_url('/css/sp_catContent.css', __FILE__));
            wp_enqueue_style( 'sp_catContentCSS' );
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
            $options = $this->options;
            ?>
            <p>Select editor mode:</p>
            <table>
                <tr>
                    <td>
                        <input type="radio" name="content-mode-<?php echo $this->ID ?>" id="plaintext-mode-<?php echo $this->ID ?>" value="plaintext" <?php echo $options->plaintext ? 'checked="checked"' : '' ?> class="content-comp" />
                        <label for="plaintext-mode-<?php echo $this->ID ?>">Plaintext mode - Users will <b>not</b> have access to a formatting toolbar.</label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="radio" name="content-mode-<?php echo $this->ID ?>" id="richtext-mode-<?php echo $this->ID ?>" value="richtext" <?php echo $options->richtext ? 'checked="checked"' : '' ?> />
                        <label for="richtext-mode-<?php echo $this->ID ?>">Richtext mode - Users will have access to a formatting toolbar.</label>
                    </td>
                </tr>
            </table>
        <?php
        }

        /**
         * @see parent::getOptions()
         */
        function getOptions(){
            return $this->options;
        }

        /**
         * Returns true whether the content component is plaintext, otherwise false
         *
         * @return bool
         */
        function isPlaintext(){
            return $this->options->plaintext;
        }

        /**
         * Returns true whether the content component is richttext, otherwise false
         *
         * @return bool
         */
        function isRichtext(){
            return $this->options->richtext;
        }

        /**
         * @see parent::setOptions()
         */
        function setOptions($data){

        }

        //Converts all instances from Richtext to Plaintext
        private function convertToPlaintext(){
        }



    }
}
?>