/*
 * JS sp_posVideo Component class
 * Used alongside sp_postVideo for AJAX calls
 * Used in front-end posts
 *
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */
(function($){
    smartpost.sp_postVideo = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Video']){
                    this.typeID = types['Video'];
                    sp_globals.SP_TYPES[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },


        /**
         * Initializes HTML5 filedrop for the media component
         * @param object component The video compoennt
         * @param int    postID    The PostID
         * @see $plupload_init array in /wp-admin/includes/media.php
         */
        initFileDrop: function(component, postID){
            var self = this;
            var compID    = component.attr('data-compid');
            var browse_id = 'sp_videoBrowse-' + compID;
            var prev_onbeforeunload = window.onbeforeunload;

            if(postID == undefined){
                postID = jQuery('#postID').val();
                if(postID == undefined){
                    postID = jQuery('#sp_qpPostID').val();
                }
            }

            var videoFilters;
            if( sp_postVideoJS.SP_VIDEO_HTML5 ){
                videoFilters = [
                    {title : "MOV files", extensions : "mov, MOV"},
                    {title : "AVI files", extensions : "avi, AVI"},
                    {title : "MP4 files", extensions : "mp4, MP4"}
                ]
            }else{
                videoFilters = [
                    {title : "MP4 files", extensions : "mp4, MP4"}
                ]
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
                file_data_name  : 'sp_videoUpload',
                multi_selection : false,
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'videoUploadAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID,
                    postID  : postID
                },
                max_file_size : '1gb',
                filters : videoFilters,
                init: {

                    FilesAdded: function(up, files) {
                        if(files.length > 1){
                            alert('Please upload one file at a time.')
                            while (up.files.length > 0) {
                                up.removeFile(up.files[0]);
                            }
                        }else{
                            up.start();
                            $('#sp_videoDropZone-' + compID).remove();
                            $('#playerContainer-' + compID).remove();
                            $('#videoProgBarContainer-' + compID).show();

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
                        $('#videoProgressBar-' + compID).css('width', file.percent + '%');
                        $('#videoProgressMsg-' + compID).html('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading Video… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
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
                            $('#videoProgBarContainer-' + compID).hide();
                            $('#videoProgressMsg-' + compID).html( response.response );
                            self.checkVideoStatus(compID);
                        }
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        }, //end initFileDrop

        /**
         * Makes an AJAX call every 5 seconds to check on the encoding status of an uploaded video.
         * @param compID - The component ID the video belongs to
         */
        checkVideoStatus: function(compID){
            var self = this;
            var messageElem = $('#videoProgressMsg-' + compID);
            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'checkVideoStatusAJAX',
                    nonce  : SP_NONCE,
                    compID : compID
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    if( response.converted ){
                        if( $('#sp_qp_stack').exists() ){
                            messageElem.html( 'The video processed successfully! Submit your post to view the video.' );
                        }else{
                            messageElem.html( 'The video processed successfully! Refresh the page to view it.' );
                        }
                    }else{
                        setTimeout( function(){ self.checkVideoStatus(compID) }, 5000 );
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    console.log(errorThrown);
                }
            });
        },

        /**
         * Saves video description.
         * @param description
         */
        saveVideoDesc: function(content, elemID, editor){
            var self = this;
            var compID = $('#' + elemID).attr('data-compid');
            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'saveVideoDescAJAX',
                    nonce  : SP_NONCE,
                    compID : compID,
                    content: content
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log( response );
                },
                error : function(jqXHR, statusText, errorThrown){
                    console.log(errorThrown);
                }
            });
        },

        /**
         * Dynamically initializes the video component
         */
        initComponent: function(component, postID, autoFocus){
            this.initFileDrop($(component));
            var editor = $( component ).find( '.sp-editor-content' );
            var browseButton = $(component).find( '.sp-video-browse' );
            browseButton.click(function(){
                var compId = $(this).data( 'compid' );
                $('#sp_videoBrowse-' + compId).click();
            });
            smartpost.sp_post.initCkEditors(editor);
        },

        /**
         * Statically initializes all media components on document.ready
         */
        init: function(){
            this.setTypeID();
            var self = this;

            // Trigger the browse button
            $( '.sp-video-browse' ).each(function(){
                $(this).click(function(){
                    var compId = $(this).data( 'compid' );
                    $( '#sp_videoBrowse-' + compId ).click();
                });
            });

            $('.sp_video').each(function(){
                self.initFileDrop($(this));
            });
        }
    }

    $(document).ready(function(){
        smartpost.sp_postVideo.init();
    });
})(jQuery);