<?php
/**
 * Core functions used in various classes in SmartPost:
 * -Upload files
 * -Access table data
 */
if ( !class_exists("sp_core") ) {

    class sp_core{
        /**
         * Returns all types in sp_compTypes as a $wpdb object
         */
        static function get_component_types(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_compTypes';
            return $wpdb->get_results("SELECT * FROM $tableName;");
        }

        /**
         * returns a formatted array of array('typeName' => 'typeID')
         */
        static function get_types_and_ids(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_compTypes';
            $types = $wpdb->get_results("SELECT * FROM $tableName;");

            $typesAndIDs = array();
            foreach( $types as $type ){
                $typesAndIDs[$type->name] = $type->id;
            }
            return $typesAndIDs;
        }

        /**
         * Returns the "component type name" of a post component or category component.
         * Can be used to instantiate a component object via its class. If a post component,
         * then the name prefixed with a 'sp_post' can be used to access the post component's class:
         *
         *   'sp_cat<Component Type Name>' for category components
         *   'sp_post<Component Type Name>' for post components
         *
         * @param int $typeID The typeID of the component
         * @return string The component type name, otherwise an empty string
         */
        static function get_type_name($typeID){
            if( !empty($typeID) ){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_compTypes';
                return $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $tableName where id = %d;", $typeID ) );
            }else{
                return "";
            }
        }

        /**
         * Given a type ID, returns the DB object representing that object, otherwise a WP_Error object.
         * @param $typeID
         * @return WP_Error|Object
         */
        static function getType($typeID){
            if( !empty( $typeID ) ){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_compTypes';
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableName where id = %d;", $typeID ) );
                return $results[0];
            }else{
                return new WP_Error('broke', ('ID not given.'));
            }
        }

        /**
         * Get the component type based off the given $name parameter.
         * @param $name
         * @return WP_Error|Object DB Object representing the component type
         */
        static function get_type_id_by_name($name){
            if(!empty($name)){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_compTypes';
                return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $tableName where name = %s;", $name ) );
            }else{
                return new WP_Error('broke', ('Name not supplied.'));
            }
        }

        /**
         * Returns all the post componetns in the sp_postComponents table where catCompID = $cat_comp_id
         * @param $cat_comp_id
         * @return WP_Error|array
         */
        static function get_post_components_by_cat_comp_id( $cat_comp_id ){
            if( !empty( $cat_comp_id ) ){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_postComponents';
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableName where catCompID = %d;", $cat_comp_id) );
                return $results;
            }else{
                return new WP_Error('broke', ('Category Component ID not given.'));
            }
        }

        /**
         * Returns all the category components in the sp_catComponents table where typeID = $component_type_id
         * @param $component_type_id
         * @return WP_Error|array
         */
        static function get_cat_components_by_type( $component_type_id ){
            if( !empty( $component_type_id ) ){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_catComponents';
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableName where typeID = %d;", $component_type_id) );
                return $results;
            }else{
                return new WP_Error('broke', ('Component type ID not given.'));
            }
        }

        /**
         * Returns all the post components in the sp_postComponents table organized by by component type
         * @param $component_type_id
         * @return WP_Error|array
         */
        static function get_post_components_by_type( $component_type_id ){
            if( !empty( $component_type_id ) ){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_postComponents';
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $tableName where typeID = %d;", $component_type_id ) );
                return $results;
            }else{
                return new WP_Error('broke', ('Component type ID not given.'));
            }
        }

        /**
         * Deletes a SmartPost component from either the sp_postComponents or sp_catComponents tables
         * @param $component_id
         * @param $comp_type - post or category table
         * @return bool
         */
        static function delete_component( $component_id, $comp_type  ){
            if( !empty( $component_id) ){
                global $wpdb;
                if( $comp_type == 'post' ){
                    $tableName = $wpdb->prefix . 'sp_postComponents';
                }else if( $comp_type == 'cat' ){
                    $tableName = $wpdb->prefix . 'sp_catComponents';
                }else{
                    return false;
                }
                return $wpdb->query( $wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $component_id) );
            }else{
                return false;
            }
        }

        /**
         * Updates a cell in $table based off $id
         * @param  string      $table      SmartPost table name (sp_compTypes, sp_postComponents, or sp_catComponents)
         * @param  int         $id         Unique ID of row to update
         * @param  string      $columnName The name of the column
         * @param  string|int  $value      The value to update with
         * @param  string 					$valueType	 '%s' if $value is a string, '%d' if it's an int
         * @return int|bool    number of rows affected, otherwise false if update failed
         *
         */
        static function updateVar($table, $id, $columnName, $value, $valueType){
            global $wpdb;
            $tableName = $wpdb->prefix . $table;
            return $wpdb->update(
                $tableName,
                array(
                    $columnName => $value
                ),
                array( 'id' => $id ),
                array(
                    $valueType
                ),
                array( '%d' )
            );
        }

        /**
         * @param $input_id - ID of html file <input> tag
         * @param $post_id - ID of post to attach (i.e. associate) file to
         * @param $postInfo array - Attachment post info (i.e. Title, description, etc)
         * @return int
         */
        static function upload($input_id, $post_id, $postInfo){
            if ( !empty( $FILES ) ) {
                require_once(ABSPATH . 'wp-admin/includes/admin.php');
                $id = media_handle_upload($input_id, $post_id, $postInfo);
            }
            return $id;
        }

        /**
         * Handles chunked AJAX uploads (using plupload plugin).
         * @param $file_data_id
         * @return bool|mixed - The file path of the whole file (when chunks = 0), otherwise false (when chunks > 0).
         */
        static function chunked_plupload($file_data_id){

            // Get a file name
            if ( isset( $_REQUEST["name"] ) ) {
                $fileName = $_REQUEST["name"];
            } elseif ( !empty( $_FILES ) ) {
                $fileName = $_FILES[$file_data_id]["name"];
            } else {
                $fileName = uniqid("file_");
            }

            $uploadsPath = wp_upload_dir();
            $filePath =  $uploadsPath['path'] . DIRECTORY_SEPARATOR . $fileName;

            // Chunking might be enabled
            $chunk  = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
            $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;

            // Open temp file
            if (!$out = @fopen("{$filePath}.part", $chunks ? "ab" : "wb")) {
                header( "HTTP/1.0 409 Error Code 102: Failed to open output stream." );
                exit;
            }

            if ( !empty($_FILES) ) {
                if ($_FILES[$file_data_id]["error"] || !is_uploaded_file($_FILES[$file_data_id]["tmp_name"])) {
                    header( "HTTP/1.0 409 Error Code 103: Failed to move uploaded file." );
                    exit;
                }

                // Read binary input stream and append it to temp file
                if (!$in = @fopen($_FILES[$file_data_id]["tmp_name"], "rb")) {
                    header( "HTTP/1.0 409 Error Code 101: Failed to open input stream." );
                    exit;
                }
            } else {
                if (!$in = @fopen("php://input", "rb")) {
                    header( "HTTP/1.0 409 Error Code 101: Failed to open input stream." );
                    exit;
                }
            }

            while ($buff = fread($in, 4096)) {
                fwrite($out, $buff);
            }

            fclose($out);
            fclose($in);

            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks - 1) {
                // Format the file name
                $newFilePath = preg_replace( '/\s+/', '', $filePath ); // Remove all whitespace
                $path_parts  = pathinfo($newFilePath);

                /**
                 * - Adds a unique ID to keep file names unique
                 * - Makes all file extensions lowercase (cases like .JPG or .MOV => .jpg/.mov)
                 * - sanitize_title slugifies the filename, so names like "John's Movie.mov" => "johnsmovie.mov"
                 */
                $newFilePath = $path_parts['dirname'] . '/' . sanitize_title( $path_parts['filename'] ) . '_' . uniqid() . '.' . strtolower( $path_parts['extension'] );
                rename( "{$filePath}.part", $newFilePath ); // Strip the temp .part suffix off
                return $newFilePath;
            }else{
                return false;
            }
        }

        /**
         * Replaces $code with $replacement inside $content
         * @param $code
         * @param $replacement
         * @param $content
         * @return mixed
         */
        public static function replace_shortcode($code, $replacement, $content){
            global $shortcode_tags;
            $stack = $shortcode_tags;
            $shortcode_tags = array($code => 1);

            // replace the shortcode with replacement
            $pattern = get_shortcode_regex();
            $content = preg_replace_callback( "/$pattern/s", function($m) use($replacement) {
                // error_log( "made it " );
                // error_log( print_r($m, true) );
                return $replacement;
            }, $content );

            $shortcode_tags = $stack;
            return $content;
        }

        /**
         * @link http://stackoverflow.com/questions/9440423/wordpress-strip-single-shortcode-from-posts
         * @param $code
         * @param $content
         * @return string
         */
        public static function strip_shortcode($code, $content)
        {
            global $shortcode_tags;
            $stack = $shortcode_tags;
            $shortcode_tags = array($code => 1);
            $content = strip_shortcodes($content);
            $shortcode_tags = $stack;
            return $content;
        }

        /**
         * Returns true if the extension of $filename is in
         * $allowedExtensions
         * @param $filename
         * @param $allowedExtensions
         * @return bool
         */
        static function validateExtension($filename, $allowedExtensions){
            $path_parts = pathinfo($filename);
            return in_array($path_parts['extension'], $allowedExtensions);
        }

        /**
         * Returns true if filename is a jpeg, png, or gif, otherwise false.
         * @param $filename - the full path to the file
         * @return bool
         */
        static function validImageUpload($filename){
            if( !empty( $filename) && file_exists( $filename ) ){
                $valid_extensions = array('jpg', 'jpeg', 'png', 'gif');
                $validExt = self::validateExtension($filename, $valid_extensions);
                $isImage  = getimagesize($filename);
                return is_array($isImage) && $validExt;
            }else{
                return false;
            }
        }

        /**
         * @see http://www.mertyazicioglu.com/image-rotation-fixer/
         * @param $img_path - Full path to the image
         * @return array|WP_Error
         */
        static function fixImageOrientation( $img_path ) {

            if( !is_callable( 'exif_read_data' ) ){
                return new WP_Error( 'broke', ( 'exif_read_data() is not callable.' ) );
            }

            if( !is_callable( 'wp_get_image_editor' ) ){
                return new WP_Error( 'broke', ( 'wp_get_image_editor() is not callable.' ) );
            }

            $img_info = pathinfo( $img_path );
            if( $img_info['extension'] != 'jpg' || $img_info['extension'] != 'JPG' || $img_info['extension'] != 'jpeg' || $img_info['extension'] != 'JEPG' ){
                return new WP_Error( 'broke', ( 'Could not find any EXIF data.' ) );
            }

            // Get a handle on the image file
            $img = wp_get_image_editor( $img_path );

            if( is_wp_error( $img ) ){
                return $img;
            }

            // Read in EXIF data
            $exif = exif_read_data( $img_path );

            if ( isset( $exif ) && isset( $exif['Orientation'] ) && $exif['Orientation'] > 1 ) {

                // Get the correct orientation
                switch ( $exif['Orientation'] ) {
                    case 3:
                        $orientation = -180;
                        break;
                    case 6:
                        $orientation = -90;
                        break;
                    case 8:
                    case 9:
                        $orientation = -270;
                        break;
                    default:
                        $orientation = 0;
                        break;
                }

                $img->rotate( $orientation );
                return $img->save( $img_path );
            }else{
                return new WP_Error( 'broke', ( 'Could not find any EXIF data.' ) );
            }
        }

        /* Returns true if image size is 16x16 pixels
         * Otherwise returns false.
         *
         * @param  string $icon path of icon image
         * @return bool   whether icon image is 16x16
         */
        static function validateIcon($icon){
            $size = getimagesize($icon);
            return ($size[0] == 16 && $size[1] == 16);
        }

        /*
         * Returns the default icon of a component type based off $typeID
         * Looks for icon.{png|jpg|jpeg} in the images/ folder -- this no longer relies on the
         * hardcoded value in the "icon" column of sp_compTypes. This column will get removed in future versions.
         * @param  integer $typeID the component type ID
         * @return string  url of the icon image
         */
        static function getIcon($typeID){
            $component_name = strtolower( self::get_type_name( $typeID ) );
            //look for the right extension
            $img_url = '';
            $icon_ext = array('.png', '.jpg', '.jpeg');
            foreach($icon_ext as $ext){
                $img_path = plugin_dir_path( dirname( __FILE__ ) ) . 'components/' . $component_name . '/images/icon' . $ext;
                if( is_readable( $img_path ) ){
                    $img_url = plugin_dir_url( $img_path ) .  'icon' . $ext;
                    break;
                }
            }
            return $img_url;
        }

        /**
         * Creates a general-purpose editor area.
         * @param $content
         * @param $editor_id - if an ID isn't provided, will pick a random one between 0 - 99
         * @param $remove_toolbar - Whether to hide or display a toolbar
         * @param $placeholder - Placeholder if content is empty
         * @param $contentAttrs - Attributes of the content element. Attributes of the form 'data-{ajax-data-lbl}="{ajax-data-val}"' will be parsed
         *                        as the AJAX data object i.e. {ajax-data-lbl : ajax-data val}.
         * @param $containerAttrs
         * @return string
         */
        public static function sp_editor($content, $editor_id = null, $remove_toolbar = true,
                                         $placeholder = 'Click to edit', $contentAttrs = array(), $containerAttrs = array()){
            $html = '';
            $editor_id = empty( $editor_id ) ? rand(0, 99) : $editor_id; // Pick a unique id between 0 and 9

            // id and class attributes can be overwritten if params are non-empty
            $defaultContainerAttrs = array(
                'id' => 'sp-editor-container-' . $editor_id,
                'class' => 'sp-editor-container'
            );
            $defaultContentAttrs   = array(
                'id' => 'sp-editor-content-' . $editor_id,
                'class' => 'sp-editor-content'
            );
            $containerAttrs = array_merge( $defaultContainerAttrs, $containerAttrs );
            $contentAttrs = array_merge( $defaultContentAttrs, $contentAttrs );

            // Stringify the container attributes
            $formattedContainerAttrs = '';
            foreach($containerAttrs as $attr => $attr_val){
                $formattedContainerAttrs .= $attr . '="' . $attr_val . '" ';
            }

            // Stringify the content attributes
            $formattedContentAttrs = '';
            foreach($contentAttrs as $attr => $attr_val){
                $formattedContentAttrs .= $attr . '="' . $attr_val . '" ';
            }

            // Create the editor area
            $remove_toolbar = $remove_toolbar ? 'data-toolbar="toolbar"' : '';
            $html .= '<div ' . $formattedContainerAttrs . '>';
                $html .= '<span class="sp-editor-identifier sp_textIcon" title="Click to edit"></span>'; // Icon element
                $html .= '<div ' . $formattedContentAttrs . ' ' . $remove_toolbar . ' placeholder="' . $placeholder . '" contenteditable="true">' . $content . '</div>';
            $html .= '</div>';

            return $html;
        }

        /**
         * Creates an new wp_attachment based off of an existing file. The file
         * has to be located in wp-content/uploads in order for the function to work.
         * @param $filename - path and name of the file to attach
         * @param $postID - PostID to attach the attachment to
         * @param $content
         * @param $authID - Author of attachment
         * @return int - The attachment ID
         */
        public static function create_attachment($filename, $postID, $content = '', $authID = null){

            $authID = empty( $authID ) ? get_current_user_id() : $authID;

            $wp_filetype = wp_check_filetype(basename($filename), null );
            $wp_upload_dir = wp_upload_dir();
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ),
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => $content,
                'post_status' => 'inherit',
                'post_author' => $authID
            );
            $attach_id = wp_insert_attachment( $attachment, $filename, $postID );
            // you must first include the image.php file
            // for the function wp_generate_attachment_metadata() to work
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            return $attach_id;
        }

    }
}