/**
 * JS sp_postPhoto Component class
 * Used alongside sp_postPhoto for AJAX calls
 * Used in front-end posts
 *
 * @version 2.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost
 */
(function($){
    smartpost.sp_postPhoto = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Photo']){
                    this.typeID = types['Photo'];
                    sp_globals.SP_TYPES[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },

        /**
         * Returns true if media component is empty, otherwise false
         *
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            return Boolean( $(component).find('.sp-photo-thumb').length );
        },

        /**
         * Initializes HTML5 filedrop for the media component
         * @param object component The photo component
         */
        initFileDrop: function( component ){
            var self = this;
            var compID = component.data('compid');
            var browse_id = 'sp-photo-upload-' + compID;
            var prev_onbeforeunload = window.onbeforeunload;

            var uploader = new plupload.Uploader({
                runtimes      : 'html5,silverlight,flash,html4',
                chunk_size    : '1mb',
                browse_button : browse_id, // you can pass in id...
                url           : SP_AJAX_URL,
                flash_swf_url : sp_globals.UPLOAD_SWF_URL,
                silverlight_xap_url : sp_globals.UPLOAD_SILVERLIGHT_URL,
                dragdrop        : true,
                drop_element    : component.attr('id'),
                file_data_name  : 'sp-photo-upload',
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'photoUploadAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID
                },
                max_file_size : '1gb',
                filters : [
                    {title : "png files", extensions : "png, PNG"},
                    {title : "jpg files", extensions : "jpg, jpeg, JPG, JPEG"},
                    {title : "gif files", extensions : "gif, GIF"}
                ],
                init: {

                    FilesAdded: function(up, files) {
                        if(files.length > 1){
                            alert('Please upload one file at a time.')
                            while (up.files.length > 0) {
                                up.removeFile(up.files[0]);
                            }
                        }else{
                            up.start();
                            $('#sp-photo-progress-' + compID).show();

                            //Add a dialogue in case window is closed
                            window.onbeforeunload = function (e) {
                                e = e || window.event;
                                if (e) {
                                    e.returnValue = 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                                }
                                return 'Warning: A file is being uploaded. If you interrupt file upload you will have to restart the upload.';
                            };
                        }
                    },

                    UploadProgress: function(up, file) {
                        $('#sp-photo-progress-' + compID).css('width', file.percent + '%');
                        $('#sp-photo-progress-msg-' + compID).html('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading "' + file.name + '"… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
                    },

                    Error: function(up, err) {
                        var filetext = '';
                        if( err.file.name ){
                            filetext =  'File name: "' + err.file.name + '"';
                        }
                        smartpost.sp_postComponent.showError( err.message + ' ' + filetext );
                    },

                    FileUploaded: function(up, files, response) {
                        if(response){
                            $('#sp-photo-progress-msg-' + compID).html('');
                            $('#sp-photo-progress-' + compID).css('width', '0%');
                            self.renderPhoto( $('#sp-photo-container-' + compID), response.response );
                        }
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        },

        /**
         * Appends an uploaded photo to the photo as well as bind
         * delete and edit events to the new photo.
         * @param photoElem - The photo container with all the pics
         * @param thumbElem - The single thumbnail
         */
        renderPhoto: function(photoElem, thumbElem){
            var self = this;

            photoElem.html( thumbElem );

            // Get a handle on the DOM element
            var thumbInfo = $( thumbElem ).data();
            thumbElem = photoElem.find('#sp-photo-thumb-' + thumbInfo.thumbid);

            // Apply delete event to the delete elem
            var delElem = $(thumbElem).find('.sp-photo-delete-thumb');
            self.bindDelete( $(delElem) );

            // Enable photo pop-ups
            $(photoElem).find( '.sp-photo-link' ).magnificPopup({
                type:'image'
            });
        },

        /**
         * Shows the delete button when over over deleteElems
         *
         * @param HTMLElement Can be a class or some HTMLElement
         */
        bindDelete: function(deleteElems){
            deleteElems.click(function(){
                var attachment_info = $(this).data();
                var thumbID = attachment_info.thumbid;
                var compID = attachment_info.compid;
                $.ajax({
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        action: 'photoDeletePicAJAX',
                        nonce: SP_NONCE,
                        attachmentID: thumbID,
                        compID: compID
                    },
                    dataType  : 'json',
                    success  : function(response, statusText, jqXHR){
                        $('#sp-photo-thumb-' + thumbID).remove();
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        smartpost.sp_postComponent.showError( errorThrown );
                    }
                })
            });
        },

        /**
         * Initializes a single new component with filedrop
         */
        initComponent: function(component, postID, autoFocus){
            // Enable file drop
            this.initFileDrop( $(component) );
            var editor = $( component ).find( '.sp-editor-content' );

            // Enable browse button
            var browseButton = $(component).find( '.sp-photo-browse' );
            browseButton.click(function(){
                var compId = $(this).data( 'compid' );
                $('#sp-photo-upload-' + compId).click();
            });

            // Enable any CK editors
            smartpost.sp_post.initCkEditors(editor);
        },

        /**
         * Initializes all dropfile elements with the filedrop plugin
         */
        init: function(){
            var self = this;
            self.setTypeID();

            // Bind filedrop to component div
            $( '.sp-photo' ).each(function(){
                self.initFileDrop( $(this) );
            });

            // Bind delete event to delete button
            $( '.sp-photo-delete-thumb' ).each(function(){
                self.bindDelete( $(this) );
            });

            // Trigger the browse button
            $( '.sp-photo-browse' ).each(function(){
                $(this).click(function(){
                    var compId = $(this).data( 'compid' );
                    $( '#sp-photo-upload-' + compId ).click();
                });
            });

            // Enable photo pop-ups
            $( '.sp-photo-link' ).magnificPopup({
                type:'image'
            });

            // jQuery UI resizable
            //$('.sp-photo-image').resizable();
        }
    };

    $(document).ready(function(){
        smartpost.sp_postPhoto.init();
    });

})(jQuery);