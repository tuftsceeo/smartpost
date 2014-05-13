<?php 
if (!class_exists("sp_postAttachments")) {
	/**
	 * Extends sp_postComponent
	 * @see sp_postComponent
	 */	
	class sp_postAttachments extends sp_postComponent{
			
        public $allowedExts = array();
        public $attachmentIDs = array(); //An array of attachment IDs
        public $description = ""; //Gallery description if the component is a gallery

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                            $name = '', $value = '', $postID = 0){

            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");

            if($compID == 0){
                // Set default options from category component
                $this->options = sp_catComponent::getOptionsFromID( $catCompID );
            }

            $this->initComponent($compInfo);

            // Get the "default" allowed extensions
            $this->allowedExts = empty( $this->options ) ? array() : $this->options;

            // Load instance vars
            if( !empty($this->value) ){
                $this->attachmentIDs = !empty( $this->value->attachmentIDs ) ? $this->value->attachmentIDs : array();
                $this->description = $this->value->description;
            }
        }
			
        /**
         * @see parent::init()
         */
        static function init(){
            require_once( dirname( __FILE__ ) . '/ajax/sp_postAttachmentsAJAX.php');
            sp_postAttachmentsAJAX::init();
            self::enqueueCSS();
            self::enqueueJS();
        }

        static function enqueueCSS(){
            wp_register_style( 'sp_postAttachmentsCSS', plugins_url('css/sp_postAttachments.css', __FILE__) );
            wp_enqueue_style( 'sp_postAttachmentsCSS' );
        }

        static function enqueueJS(){
            wp_register_script( 'sp_postAttachmentsJS', plugins_url('js/sp_postAttachments.js', __FILE__) );
            wp_enqueue_script( 'sp_postAttachmentsJS',  null, array( 'jquery', 'sp_globals', 'sp_postComponentJS' ) );
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode(){
            $html = '<div id="sp-attachments-' . $this->ID .'" class="sp-attachments" data-compid="' . $this->ID .'">';
                $html .= sp_core::sp_editor(
                    $this->description,
                    $this->ID,
                    false,
                    'Click here to add a description ...',
                    array( 'data-action' => 'saveAttachmentsDescAJAX', 'data-compid' => $this->ID )
                );
                $html .= '<div id="sp-attachments-table-container-' . $this->ID .'" class="sp-attachments-table-container">';
                    $html .= self::renderAttachmentsTable(true);
                $html .= '</div>';
                $html .= !empty($this->allowedExts) ? "Allowed file types: " . implode(', ', $this->allowedExts) : '';
                $html .= '<div class="clear"></div>';
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = '<div id="sp-attachments-' . $this->ID .'" class="sp-attachments">';
                $html .= '<div id="sp-attachments-desc-' . $this->ID .'" class="sp-attachments-desc">' . $this->description . '</div>';
                $html .= '<div id="sp-attachments-table-container-' . $this->ID .'" class="sp-attachments-table-container">';
                    $html .= self::renderAttachmentsTable();
                $html .= '</div>';
                $html .= '<div class="clear"></div>';
            $html .= '</div>';
            return $html;
        }

        /**
         * Renders the list of attachments
         * @param bool $edit_mode
         * @return string
         */
        function renderAttachmentsTable($edit_mode = false){
            $html = '<table id="sp-attachments-table-' . $this->ID .'" class="sp-attachments-table">';
            $html .= '<tr><th>Attachment</th><th>Type</th><th>Size</th></tr>';
            if( !empty($this->attachmentIDs) ){
                $row = 0;
                foreach($this->attachmentIDs as $attach_id){
                    $html .= $this->renderAttachmentRow( $attach_id, $edit_mode );
                    $row++;
                }
            }

            if( $edit_mode ){
                $addIcon = '<img src="' . plugins_url( '/images/add.png', __FILE__ ) . '" style="vertical-align: text-top;" />';
                $html .= '<tr id="sp-attachments-uploads-row-' . $this->ID . '" class="sp-attachments-uploads-row" data-compid="' . $this->ID . '">';
                    $html .= '<td><span class="sp-attachments-browse-img" data-compid="' . $this->ID . '">' . $addIcon . ' Attach more files</span> </td>';
                    $html .= '<td><input type="file" id="sp-attachments-upload-' . $this->ID . '" /></td></td>';
                    $html .= '<td>&nbsp;</td><td>&nbsp;</td>'; // Fill out size and delete cols
                $html .= '</tr>';
            }
            $html .= '</table><!-- end .sp-attachments-list -->';

            if( $edit_mode ){
                $html .= '<span id="sp-attachments-progress-msg-' . $this->ID . '"></span>';
                $html .= '<span id="sp-attachments-progress-' . $this->ID . '"></span>';
            }
            return $html;
        }

        /**
         * Renders a single row for attachment
         * @param $attach_id
         * @param $edit_mode
         * @return string
         */
        public function renderAttachmentRow($attach_id, $edit_mode){
            $attachment = get_post( $attach_id );
            $mime_type = get_post_mime_type( $attach_id );
            $attach_size = filesize( get_attached_file( $attach_id) );
            $url = wp_get_attachment_url( $attach_id );
            $icon = wp_get_attachment_image( $attach_id, array(30, 30), true, array( 'class' => 'sp-attachments-icon' ) );

            $html = '<tr id="sp-attachment-' . $attach_id . '" data-attachid="' . $attach_id . '" class="sp-attachment-row">';
            $html .= '<td><a href="' . $url . '" target="_new">' . $icon . $attachment->post_content . '</a></td>';
            $html .= '<td>' . $mime_type . '</td>';
            $html .= '<td>' . $this->formatSizeUnits( $attach_size ) . '</td>';
            if( $edit_mode ){
                $html .= '<td id="sp-attachments-delete-' . $attach_id . '" class="sp-attachments-delete">';
                    $html .= '<span id="sp-attachments-delete-button-' . $attach_id .'" class="sp-attachments-delete-button sp_xButton" data-attachid="' . $attach_id .'" data-compid="' . $this->ID . '" alt="Remove Attachment" title="Remove Attachment"></span>';
                $html .= '</td>';
            }
            $html .= '</tr>';
            return $html;
        }

        /**
         * @see http://stackoverflow.com/questions/5501427/php-filesize-mb-kb-conversion
         * @param $bytes
         * @return string
         */
        private function formatSizeUnits($bytes)
        {
            if ($bytes >= 1073741824)
            {
                $bytes = number_format($bytes / 1073741824, 2) . ' GB';
            }
            elseif ($bytes >= 1048576)
            {
                $bytes = number_format($bytes / 1048576, 2) . ' MB';
            }
            elseif ($bytes >= 1024)
            {
                $bytes = number_format($bytes / 1024, 2) . ' KB';
            }
            elseif ($bytes > 1)
            {
                $bytes = $bytes . ' bytes';
            }
            elseif ($bytes == 1)
            {
                $bytes = $bytes . ' byte';
            }
            else
            {
                $bytes = '0 bytes';
            }

            return $bytes;
        }

        function renderPreview(){
            return $this->description;
        }

        /**
         * Overload parent function since we need to delete all the attachments
         * As we delete all the attachments
         * @return bool|int false on failure, number of rows affected on success
         */
        function delete(){
            global $wpdb;

            if( !empty($this->attachmentIDs) ){
                foreach($this->attachmentIDs as $id){
                    wp_delete_attachment($id, true);
                }
            }

            $tableName = $wpdb->prefix . 'sp_postComponents';
            return $wpdb->query( $wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $this->ID ) );
        }

        /**
         * Saves the state of the component to the database.
         * @return bool|int
         */
        function update(){
            $attachmentComp = new stdClass();
            $attachmentComp->attachmentIDs = $this->attachmentIDs;
            $attachmentComp->description = $this->description;
            $attachmentComp->allowedExts = $this->allowedExts;
            $attachmentComp = maybe_serialize($attachmentComp);
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $attachmentComp, '%s');
        }
			
        function isEmpty(){
            return empty($this->attachmentIDs);
        }

        function getAttachmentDescription($id){
            $attachment = get_post($id);
            return $attachment->post_content;
        }

        /**
         * Sets the description of a particular attachment
         * @param $description string - The description of the attachment
         * @param $attachmentID int - The ID of the attachment
         * @return int|WP_Error
         */
        static function setAttachmentDescription($description, $attachmentID){
            $attachment = get_post($attachmentID);
            $attachment->post_content = $description;
            return wp_update_post($attachment);
        }
			
        function getAttachments(){
            return $this->attachmentIDs;
        }
	}
}
