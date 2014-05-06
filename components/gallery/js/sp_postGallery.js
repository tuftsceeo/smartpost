/**
 * JS sp_postMedia Component class
 * Used alongside sp_postMedia for AJAX calls
 * Used in front-end posts
 *
 * @version 2.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost
 * @todo figure out captions!
 */
(function($){
    smartpost.sp_postGallery = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Gallery']){
                    this.typeID = types['Gallery'];
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
            return Boolean( $(component).find('.sp-gallery-thumb').length );
        },

        /**
         * Initializes HTML5 filedrop for the media component
         * @param object component The gallery component
         */
        initFileDrop: function(component, postID){
            var self = this;
            var compID = component.data('compid');
            var browse_id = 'sp-gallery-upload-' + compID;
            var prev_onbeforeunload = window.onbeforeunload;

            if(postID == undefined){
                postID = jQuery('#postID').val();
                if(postID == undefined){
                    postID = jQuery('#sp_qpPostID').val();
                }
            }

            var uploader = new plupload.Uploader({
                runtimes      : 'html5,silverlight,flash,html4',
                chunk_size    : '1mb',
                browse_button : browse_id, // you can pass in id...
                url           : SP_AJAX_URL,
                flash_swf_url : sp_globals.UPLOAD_SWF_URL,
                silverlight_xap_url : sp_globals.UPLOAD_SILVERLIGHT_URL,
                dragdrop        : true,
                drop_element    : component.attr('id'),
                file_data_name  : 'sp-gallery-upload',
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'galleryUploadAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID,
                    postID  : postID
                },
                max_file_size : '1gb',
                filters : [
                    {title : "png files", extensions : "png, PNG"},
                    {title : "jpg files", extensions : "jpg, jpeg, JPG, JPEG"},
                    {title : "gif files", extensions : "gif, GIF"}
                ],
                init: {
                    FilesAdded: function(up, files) {
                        up.start();
                        $('#sp-gallery-progress-' + compID).show();

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
                        $('#sp-gallery-progress-' + compID).css('width', file.percent + '%');
                        $('#sp-gallery-progress-msg-' + compID).html('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading "' + file.name + '"… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
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
                            $('#sp-gallery-progress-msg-' + compID).html('');
                            $('#sp-gallery-progress-' + compID).css('width', '0%');
                            self.renderPhoto( $('#sp-gallery-pics-' + compID), response.response );
                        }
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        },

        /**
         * Appends an uploaded photo to the gallery as well as bind
         * delete and edit events to the new photo.
         * @param galleryElem - The gallery container with all the pics
         * @param thumbElem - The single thumbnail
         */
        renderPhoto: function(galleryElem, thumbElem){
            var self = this;
            thumbElem = $(thumbElem);

            thumbElem.hide(); // hide the thumbnail initially for a nice fade-in
            galleryElem.append( thumbElem );
            thumbElem.fadeIn();

            var delElem = thumbElem.find('.sp-gallery-delete-thumb');
            self.bindDelete( $(delElem) );
        },

        /**
         * Triggers editing a caption of a thumbnail.
         * @param editElem
         */
        bindEditCaption: function(editElem){

            var captionInfo = editElem.data();
            var captionElem = $('#sp-gallery-caption-' + captionInfo.thumbid);
            captionElem.bind('blur', function(){
                console.log('blur');
                $(this).hide();
            })

            editElem.click(function(){
                captionElem.show();
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
                        action: 'galleryDeletePicAJAX',
                        nonce: SP_NONCE,
                        attachmentID: thumbID,
                        compID: compID
                    },
                    dataType  : 'json',
                    success  : function(response, statusText, jqXHR){
                        $('#sp-gallery-thumb-' + thumbID).remove();
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        smartpost.sp_postComponent.showError( errorThrown );
                    }
                })
            });
        },

        /**
         * Initializes a single component with filedrop
         */
        initComponent: function(component, postID, autoFocus){
            this.initFileDrop( $(component) );
            $(component).magnificPopup({
                delegate: 'a', // the selector for gallery item
                type: 'image',
                gallery: {
                    enabled:true
                }
            });
            var editor = $( component ).find( '.sp-editor-content' );
            var browseButton = $(component).find( '.sp-gallery-browse' );
            browseButton.click(function(){
                var compId = $(this).data( 'compid' );
                $('#sp-gallery-upload-' + compId).click();
            });
            smartpost.sp_post.initCkEditors(editor);
        },

        /**
         * Initializes all dropfile elements with the filedrop plugin
         */
        init: function(){
            var self = this;
            self.setTypeID();

            $('.sp-gallery').each(function(){
                self.initFileDrop( $(this) );
            });

            $('.sp-gallery-delete-thumb').each(function(){
                self.bindDelete( $(this) );
            });

            // Trigger the browse button
            $( '.sp-gallery-browse' ).each(function(){
                $(this).click(function(){
                    var compId = $(this).data( 'compid' );
                    $( '#sp-gallery-upload-' + compId ).click();
                });
            });

            // Initialize magnific-popup
            $('.sp-gallery-pics').each(function() { // the containers for all your galleries
                $(this).magnificPopup({
                    delegate: 'a', // the selector for gallery item
                    type: 'image',
                    gallery: {
                        enabled:true
                    }
                });
            });
        }
    }

    $(document).ready(function(){
        smartpost.sp_postGallery.init();
    });

})(jQuery);