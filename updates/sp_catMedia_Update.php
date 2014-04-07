<?php
/**
 * Created by PhpStorm.
 * User: ryagudin
 * Date: 4/2/14
 * Time: 12:08 PM
 */
if ( !class_exists("sp_catMedia_Update") ){
    class sp_catMedia_Update{

        /**
         * Initializes the update class
         */
        public static function init(){
            add_action( 'admin_enqueue_scripts', array('sp_catMedia_Update', 'enqueue_upgrade_scripts') );
            add_action( 'wp_ajax_sp_cat_media_update_ajax', array('sp_catMedia_Update', 'sp_cat_media_update_ajax') );
        }

        /**
         * Loads the JS scripts handling the update
         */
        public static function enqueue_upgrade_scripts($hook){
            if( $hook == 'smartpost_page_sp-cat-page' && $_GET['update'] ){
                wp_register_script( 'sp_catMedia_UpdateJS', plugins_url('/js/sp_catMedia_Update.js', __FILE__));
                wp_enqueue_script( 'sp_catMedia_UpdateJS', null, array( 'jquery', 'sp_globals' ) );
            }
        }

        /**
         * Renders the update page in the dashboard. Contains information regarding what will get updated/changed.
         */
        public static function render_update_settings(){
            ?>
            <p>
                The "Media" component in SmartPost 1.x is now split into two components in SmartPost 2.x: (1) the "Gallery" component and
                (2) the "Attachments" component.
            </p>
            <p>
                This update will convert all posts using the "Media" component to either (1) an "Gallery" component if all the attachments are
                images, or (2) an "Attachments" component if the attachments are a mixture of images and files. It will then completely remove
                the "Media" type from SmartPost Templates.
            </p>
            <p>
                Click the button below to run the update.
            </p>
            <button id="sp-update-media-button" type="button" class="button">Update Media</button>

            <?php
            $sp_media_type_id = sp_core::get_type_id_by_name( "Media" );
            $media_components = sp_core::get_post_components_by_type( $sp_media_type_id );
            ?>
            <div id="sp_media_update_results">
                <p><b><?php echo count($media_components) ?></b> posts are currently using the Media component and should be updated:</p>
                <?php
                if( !empty( $media_components ) ){
                    echo '<ul>';
                    foreach($media_components as $media_comp){
                        $post = get_post( $media_comp->postID );
                        echo '<li><a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a></li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>
        <?php
        }

        /**
         * AJAX function that performs the actual update
         */
        public static function sp_cat_media_update_ajax(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                die('Security Check');
            }

            $results = self::update();

            // Check the results before deleting the type entirely
            $delete_media_type = true;
            foreach( $results as $result ){
                if( !$result ){
                    $delete_media_type = false;
                    break;
                }
            }

            if( $delete_media_type ){
                global $wpdb;
                $sp_media_type_id = sp_core::get_type_id_by_name( "Media" );
                $tableName = $wpdb->prefix . 'sp_compTypes';
                $wpdb->query( $wpdb->prepare( "DELETE FROM $tableName WHERE id = %d", $sp_media_type_id ) );
            }

            echo json_encode( $results );
            exit;
        }

        /**
         * The actual update function that will update the obsolete component.
         * @return array
         */
        private static function update(){

            // Keeps track of what failed and what succeeded
            $update_results = array();

            // Grab all the media components
            $sp_media_type_id = sp_core::get_type_id_by_name( "Media" );
            $media_post_comps = sp_core::get_post_components_by_type( $sp_media_type_id );

            if( !empty( $media_post_comps ) ){
                foreach( $media_post_comps as $media_comp ){

                    // Base conversion decision based off attachment ids
                    $attachment_ids = maybe_unserialize($media_comp->value);

                    if( !empty( $attachment_ids ) ){

                        // Case 1: If the number of attachment IDs is 1 AND it's an image, convert it to a photo component, otherwise convert it to an attachment
                        if( count( $attachment_ids ) === 1 ){
                            $filename = get_attached_file( $attachment_ids[0] );

                            if( file_exists( $filename ) ){
                                $is_image = sp_core::validImageUpload( get_attached_file( $attachment_ids[0] ) );
                                if( $is_image ){
                                    $update_results[$media_comp->id] = self::convert_to_photo( $media_comp, $attachment_ids[0] );
                                }else{
                                    $update_results[$media_comp->id] = self::convert_to_attachments( $media_comp, $attachment_ids );
                                }
                            }else{
                                // If it's one "attachment", and the file doesn't even exist, get rid of the component...
                                wp_delete_attachment( $attachment_ids[0] );
                                sp_core::delete_component( $media_comp->id, 'post');
                            }
                        }

                        // Cases 2 and 3: If number of attachment IDs is > 1, convert to either gallery or attachments components
                        if( count( $attachment_ids ) > 1 ){
                            $convert_to_attachments = false;

                            foreach( $attachment_ids as $key => $attach_id ){
                                $filename = get_attached_file( $attach_id );
                                if( file_exists( $filename ) ){
                                    $mime_type = get_post_mime_type( $attach_id );
                                    if( !in_array( $mime_type, array('image/jpeg', 'image/png', 'image/gif') ) ){
                                        $convert_to_attachments = true;
                                        break;
                                    }
                                }else{
                                    // If the attachment has no associated file.. get rid of it
                                    wp_delete_attachment( $attach_id );
                                    unset( $attachment_ids[$key] );
                                }
                            }

                            if( $convert_to_attachments ){
                                $update_results[$media_comp->id] = self::convert_to_attachments( $media_comp, $attachment_ids );
                            }else{
                                $update_results[$media_comp->id] = self::convert_to_gallery( $media_comp, $attachment_ids );
                            }
                        }
                    }else{
                        // Case 4: If there are no attachment IDs, remove the component entirely
                        sp_core::delete_component( $media_comp->id, 'post');
                    } // end check for attachment IDs
                } // end for loop for media components

                // Convert remaining media category components to attachment category components with no upload restrictions
                $media_cat_comps = sp_core::get_cat_components_by_type( $sp_media_type_id );
                if( !empty( $media_cat_comps ) ){
                    foreach( $media_cat_comps as $media_cat_comp ){
                        $attachments_comp_type_id = sp_core::get_type_id_by_name( "Attachments" );
                        sp_core::updateVar( 'sp_catComponents', $media_cat_comp->id, 'typeID', $attachments_comp_type_id, '%d' );
                        sp_core::updateVar( 'sp_catComponents', $media_cat_comp->id, 'options', '', '%d' );
                    }
                }

            } // end check for media components

            return $update_results;
        } //end update function

        /**
         * Case 1: If the number of attachment IDs is 1 AND it's an image, convert it to a photo component
         * @param $media_comp
         * @param $attachment_id
         * @return bool
         */
        private static function convert_to_photo( $media_comp, $attachment_id ){

            // Get the type ID of a photo component
            $photo_comp_type_id = sp_core::get_type_id_by_name( "Photo" );

            // Get the attachment
            $attachment = get_post( $attachment_id );

            // Change the options to that of a Photo component
            $update_1 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'options', '', '%s' );

            // Change the value column to that of a Photo component
            $photoData = new stdClass();
            $photoData->photoID = $attachment_id;
            $photoData->caption = $attachment->post_content;
            $photoData = maybe_serialize( $photoData );
            $update_2 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'value', $photoData, '%s' );

            // Change the type ID to that of a photo component
            $update_3 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'typeID', $photo_comp_type_id, '%d' );

            // Change the type ID of the parent category component
            $update_4 = sp_core::updateVar( 'sp_catComponents', $media_comp->catCompID, 'typeID', $photo_comp_type_id, '%d' );

            return $update_1 && $update_2 && $update_3 && $update_4;
        }

        /**
         * Case 2: If the number of attachment IDs is > 1 AND they mixed files, convert it to an "Attachments" component
         * @param $media_comp
         * @param $attachment_ids
         * @return bool
         */
        private static function convert_to_attachments( $media_comp, $attachment_ids ){
            $attachments_comp_type_id = sp_core::get_type_id_by_name( "Attachments" );

            // Change the options column to that of an Attachments component
            $update_1 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'options', '', '%s' );

            // Set attachment post_content the same as title if empty
            foreach( $attachment_ids as $attach_id ){
                $attachment = get_post( $attach_id );
                if( empty( $attachment->post_content ) ){
                    $attachment->post_content = $attachment->post_title;
                    wp_update_post( $attachment );
                }
            }

            // Change the value column to that of an Attachments component
            $options = maybe_unserialize($media_comp->options);
            $attachments_data = new stdClass();
            $attachments_data->attachmentIDs = $attachment_ids;
            $attachments_data->description = "";
            $attachments_data->allowedExts = array_merge( $options->allowedExts, $options->customExts );
            $attachments_data = maybe_serialize($attachments_data);
            $update_2 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'value', $attachments_data, '%s' );

            // Change the type ID to that of an Attachment component
            $update_3 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'typeID', $attachments_comp_type_id, '%d' );

            // Change the type ID of the parent category component
            $update_4 = sp_core::updateVar( 'sp_catComponents', $media_comp->catCompID, 'typeID', $attachments_comp_type_id, '%d' );

            return $update_1 && $update_2 && $update_3 && $update_4;
        }

        /**
         * Case 3: If the number of attachment IDs is > 1 AND they are all images, convert it to a gallery component
         * @param $media_comp
         * @param $attachment_ids
         * @return bool
         */
        private static function convert_to_gallery( $media_comp, $attachment_ids ){
            $gallery_comp_type_id = sp_core::get_type_id_by_name( "Gallery" );

            // Change the options column to that of an Attachments component
            $update_1 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'options', '', '%s' );

            // Change the value column to that of an Attachments component
            $galleryData = new stdClass();
            $galleryData->attachmentIDs = $attachment_ids;
            $galleryData->description   = '';
            $galleryData = maybe_serialize( $galleryData );
            $update_2 = sp_core::updateVar('sp_postComponents', $media_comp->id, 'value', $galleryData, '%s');

            // Change the type ID to that of an Attachment component
            $update_3 = sp_core::updateVar( 'sp_postComponents', $media_comp->id, 'typeID', $gallery_comp_type_id, '%d' );

            // Change the type ID of the parent category component
            $update_4 = sp_core::updateVar( 'sp_catComponents', $media_comp->catCompID, 'typeID', $gallery_comp_type_id, '%d' );

            return $update_1 && $update_2 && $update_3 && $update_4;
        }
    }
}