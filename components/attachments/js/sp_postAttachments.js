/*
 * JS sp_postAttachments Component class
 * Used alongside sp_postAttachments for AJAX calls
 * Used in front-end posts
 *
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */
(function($){
    smartpost.sp_postAttachments = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Attachments']){
                    this.typeID = types['Attachments'];
                    sp_globals.SP_TYPES[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },

        /**
         * Returns true if attachments component is empty, otherwise false
         *
         * @param component component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).data('compid');
            return $(component).find('#sp-attachments-' + compID).exists();
        },

        /**
         * Initializes HTML5 filedrop for the attachments component
         * @todo WP uses v 1.5.7 of plupload, v2.0+ will have access to xhr headers for error handling
         * @param component The attachments compoennt
         * @param postID
         */
        initFileDrop: function( component ){
            var self = this;
            var compID = component.data('compid');

            // Get a handle on the browse button
            var browse_id = 'sp-attachments-upload-' + compID;

            // Get a handle onto the previous unload event as our message will change throughout upload
            var prev_onbeforeunload = window.onbeforeunload;

            var uploader = new plupload.Uploader({
                runtimes      : 'html5,silverlight,flash,html4',
                chunk_size    : '1mb',
                browse_button : browse_id,
                url           : SP_AJAX_URL,
                flash_swf_url : sp_globals.UPLOAD_SWF_URL,
                silverlight_xap_url : sp_globals.UPLOAD_SILVERLIGHT_URL,
                dragdrop        : true,
                drop_element    : component.attr('id'),
                file_data_name  : 'sp-attachments-upload',
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'attachmentsUploadAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID
                },
                max_file_size : '1gb',
                init: {
                    FilesAdded: function(up, files) {
                        up.start();
                        $('#sp-attachments-progress-' + compID).show();

                        //Add a dialogue in case window is closed
                        window.onbeforeunload = function (e) {
                            e = e || window.event;
                            if (e) {
                                e.returnValue = 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                            }
                            return 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                        };
                    },

                    UploadProgress: function(up, file) {
                        $('#sp-attachments-progress-' + compID).css('width', file.percent + '%');
                        $('#sp-attachments-progress-msg-' + compID).html('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading "' + file.name + '"… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
                    },

                    Error: function(up, err) {
                        var out = '';
                        for (var i in err) {
                            out += i + ": " + err[i] + "\n";
                        }
                        if(err.status == '409'){
                            out = "File type not allowed for file " + err.file.name;
                        }
                        smartpost.sp_postComponent.showError( out );

                        $('#sp-attachments-progress-msg-' + compID).html('');
                        $('#sp-attachments-progress-' + compID).css('width', '0%');
                        window.onbeforeunload = prev_onbeforeunload;
                    },

                    FileUploaded: function(up, files, response) {
                        if(response){
                            self.renderAttachment( $('#sp-attachments-table-' + compID), response.response );
                        }

                        $('#sp-attachments-progress-msg-' + compID).html('');
                        $('#sp-attachments-progress-' + compID).css('width', '0%');
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        },

        /**
         * Adds a new attachment to the attachment table
         * @param attachmentTable
         * @param attachment
         */
        renderAttachment: function(attachmentTable, attachment){
            var self = this;
            var attach_id = $( attachment).data( 'attachid' );
            var beforeRow = attachmentTable.find( '.sp-attachments-uploads-row' );
            $( attachment ).insertBefore( beforeRow );
            self.bindDelete( $( '#sp-attachments-delete-button-' + attach_id ) );
        },

        /**
         * @param deleteButton DOM element representing the delete button
         */
        bindDelete: function(deleteButton){
            deleteButton.click(function(){
                var attachmentID = $(this).data( 'attachid' );
                var compID = $(this).data( 'compid' );
                $.ajax({
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        action: 'attachmentsDeleteAttachmentAJAX',
                        nonce: SP_NONCE,
                        attachmentID: attachmentID,
                        compID: compID
                    },
                    dataType  : 'json',
                    success  : function(response){
                        if(response){
                            $('#sp-attachment-' + attachmentID).remove();
                        }
                    },
                    error : function(jqXHR, statusText, errorThrown){
                        smartpost.sp_postComponent.showError(errorThrown);
                    }
                })
            });
        },

        /**
         * Dynamically initializes a attachments component
         */
        initComponent: function(component, postID, autoFocus){
            this.initFileDrop( $(component).find( '.sp-attachments-uploads-row' ), null );
            var browseButton = $(component).find( '.sp-attachments-browse-img' );
            browseButton.click(function(){
                var compId = $(this).data( 'compid' );
                $('#sp-photo-upload--' + compId).click();
            });
            var editor = $(component).find( '.sp-editor-content' );
            smartpost.sp_post.initCkEditors(editor);
        },

        /**
         * Statically initializes all attachments components on document.ready
         */
        init: function(){
            var self = this;
            self.setTypeID();

            // Init plupload file drop for the drop zones
            $( '.sp-attachments-uploads-row' ).each(function(){
                self.initFileDrop( $(this) );
            });

            // Click the browse button when clicking on the "+" image
            $( '.sp-attachments-browse-img' ).each(function(){
                var compId = $(this).data( 'compid' );
                $(this).click(function(){
                    $( '#sp-attachments-upload-' + compId ).click();
                });
            });

            // Bind delete event to delete elements
            $( '.sp-attachments-delete-button').each(function(){
                self.bindDelete( $(this) );
            });
        }
    };

    $(document).ready(function(){
        smartpost.sp_postAttachments.init();
    });
})(jQuery);