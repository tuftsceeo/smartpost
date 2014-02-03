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
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).data('compid');
            return $(component).find('#sp-attachments-' + compID).exists();
        },

        /**
         * Initializes HTML5 filedrop for the attachments component
         * @param object component The attachments compoennt
         */
        initFileDrop: function(component, postID){
            var self = this;

            console.log('got here');

            var compID = component.data('compid');
            var browse_id = 'sp-attachments-upload-' + compID;

            console.log(compID);
            console.log(browse_id);

            var prev_onbeforeunload = window.onbeforeunload;

            if(postID == undefined){
                postID = jQuery('#postID').val(); // Single post
                if(postID == undefined){
                    postID = jQuery('#sp_qpPostID').val(); // Using the quickpost widget
                }
            }

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
                    compID  : compID,
                    postID  : postID
                },
                filters : [
                    /*
                    {title : "png files", extensions : "png, PNG"},
                    {title : "jpg files", extensions : "jpg, jpeg, JPG, JPEG"},
                    {title : "gif files", extensions : "gif, GIF"}
                    */
                ],
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
                        smartpost.sp_postComponent.showError( out );
                        window.onbeforeunload = prev_onbeforeunload;
                    },

                    FileUploaded: function(up, files, response) {
                        console.log(response);
                        if(response){
                            $('#sp-attachments-progress-msg-' + compID).html('');
                            $('#sp-attachments-progress-' + compID).css('width', '0%');
                            self.renderAttachment( $('#sp-attachments-table-' + compID), response.response );
                        }
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
            var beforeRow = attachmentTable.find('.sp-attachments-uploads-row')
            $( attachment ).insertBefore( beforeRow );
        },

        /**
         * Initialize the description editor
         * @param descElem jQuery <div> object of the description
         */
        initDescEditor: function(descElem){
            var thisObj = this;
            if(smartpost.sp_postComponent){
                var elementID = $(descElem).attr('id');
                smartpost.initEditor(elementID, false, thisObj.saveDescription,'Click to add a description');
            }
        },

        /**
         * Saves a attachments component's description to the database.
         * @param string    content   The content to be saved
         * @param string    contentID The DOMElem id of the content's container
         * @param nicEditor instance  The editor instance
         */
        saveDescription: function(content, contentID, instance){
            var compID  = $('#' + contentID).attr('data-compid');
            var attachmentID  = $('#' + contentID).attr('attach-id');
            $.ajax({
                url	: SP_AJAX_URL,
                type : 'POST',
                data : {action: 'saveAttachmentsDescAJAX',
                    nonce : SP_NONCE,
                    compID : compID,
                    attachmentID : attachmentID,
                    desc : content},
                dataType : 'json',
                success : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Shows the delete button when over over deleteElems
         *
         * @param HTMLElement Can be a class or some HTMLElement
         */
        bindDelete: function(deleteElems){
            deleteElems.click(function(){
                var attachmentID = $(this).attr('data-attachid');
                var compID = $(this).attr('data-compid');
                $.ajax({
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {action: 'attachmentsDeleteAttachmentAJAX', nonce: SP_NONCE, attachmentID: attachmentID, compID: compID },
                    dataType  : 'json',
                    success  : function(response, statusText, jqXHR){
                        $('#attachments_thumb-' + attachmentID).remove();
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        if(smartpost.sp_postComponent)
                            smartpost.sp_postComponent.showError(errorThrown);
                    }
                })
            });
        },

        /**
         * Dynamically initializes a attachments component
         */
        initComponent: function(component, postID, autoFocus){
            this.initFileDrop( $(component) );
        },

        /**
         * Statically initializes all attachments components on document.ready
         */
        init: function(){
            var self = this;
            self.setTypeID();

            $( '.sp-attachments-uploads-row' ).each(function(){
                self.initFileDrop( $(this) );
            });

            $( '.sp-attachments-browse-img' ).each(function(){
                var compId = $(this).data( 'compid' );
                $(this).click(function(){
                    $('#sp-attachments-upload-' + compId).click();
                });
            });

            /*
            this.bindDelete($('.sp_attachmentsDelete'));

            $('.sp_attachments_desc').each(function(){
                self.initDescEditor($(this));
            });
            */
        }
    };

    $(document).ready(function(){
        smartpost.sp_postAttachments.init();
    });
})(jQuery);