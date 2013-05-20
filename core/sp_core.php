<?php
if (!class_exists("sp_core")) {
    /* Core (mostly static) functions used in various classes in SmartPost:
     * -Upload files
     * -Access table data
     */
    class sp_core{
        /**
         * Returns all types in sp_compTypes as a $wpdb object
         */
        function getTypes(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_compTypes';
            return $wpdb->get_results("SELECT * FROM $tableName;");
        }

        /**
         * returns a formatted array of array('typeName' => 'typeID)
         */
        function getTypesAndIDs(){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_compTypes';
            $types = $wpdb->get_results("SELECT * FROM $tableName;");

            $typesAndIDs = array();
            foreach($types as $type){
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
        static function getType($typeID){
            if(!empty($typeID)){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_compTypes';
                return $wpdb->get_var("SELECT name FROM $tableName where id = $typeID;");
            }else{
                return "";
            }
        }

        function getTypeIDByName($name){
            if(!empty($name)){
                global $wpdb;
                $tableName = $wpdb->prefix . 'sp_compTypes';
                return $wpdb->get_var("SELECT id FROM $tableName where name = '$name';");
            }else{
                return new WP_Error('broke', ('Name not supplied.'));
            }
        }

        /**
         * Updates a cell in $table based off $id
         *
         * @param  string      $table      SmartPost table name (sp_compTypes, sp_postComponents, or sp_catComponents)
         * @param  int         $id         Unique ID of row to update
         * @param  string      $columnName The name of the column
         * @param  string|int  $value      The value to update with
         * @param  string 					$valueType	 '%s' if $value is a string, '%d' if it's an int
         * @return int|bool    number of rows affected, otherwise false if update failed
         *
         */
        function updateVar($table, $id, $columnName, $value, $valueType){
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

        /*
         * Uses WordPress media_handle_upload to upload files
         *
         * @param array   $_FILES       PHP $_FILES array
         * @param int     $input_id     ID of html file <input> tag
         * @param int     $postID       ID of post to attach (i.e. associate) file to
         * @param array  	$post  							Attachment post info (i.e. Title, description, etc)
         */
        function upload(&$_FILES, $input_id, $post_id, $postInfo){
            if (!empty( $_FILES ) ) {
                require_once(ABSPATH . 'wp-admin/includes/admin.php');
                $id = media_handle_upload($input_id, $post_id, $postInfo);
            }
            return $id;
        }

        /* Returns true if the extension of $filename is in
         * $allowedExntesions
         *
         * @param $filename - string of filename
         * @param $allowedExtension - array of extension strings
         */
        function validateExtension($filename, $allowedExtensions){
            $ext = strtolower(substr(strrchr($filename, "."), 1));
            return in_array($ext, $allowedExtensions);
        }

        /* Returns true if uploaded file is a jpg, png, or jpeg,
         * otherwise false.
         *
         * @param &$_FILES - the uploaded file
         * @param $input_id - id of <input> field
         */

        function validImageUpload(&$_FILES, $input_id){
            $valid_extensions = array('jpg', 'jpeg', 'png');
            $filename = $_FILES[$input_id]['name'];
            $validExt = self::validateExtension($filename, $valid_extensions);
            $isImage  = getimagesize($_FILES[$input_id]['tmp_name']);

            return is_array($isImage) && $validExt;
        }

        /* Returns true if image size is 16x16 pixels
         * Otherwise returns false.
         *
         * @param  string $icon path of icon image
         * @return bool   whether icon image is 16x16
         */

        function validateIcon($icon){
            $size = getimagesize($icon);
            return ($size[0] == 16 && $size[1] == 16);
        }

        /*
         * Returns the default icon of a component type based off $typeID
         *
         * @param  integer $typeID the component type ID
         * @return string  url of the icon image
         */
        function getIcon($typeID){
            global $wpdb;
            $tableName = $wpdb->prefix . 'sp_compTypes';
            $sql = "SELECT icon FROM $tableName where id = $typeID;";
            return $wpdb->get_var($sql);
        }

    }
}