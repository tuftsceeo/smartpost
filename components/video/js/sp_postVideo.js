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
    var sp_postVideo = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.types to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.types;

                //!Important - the raw name of the type
                if(types['Video']){
                    this.typeID = types['Video'];
                    sp_globals.types[this.typeID] = this;
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
            var compID    = component.attr('data-compid');
            var browse_id = 'sp_videoBrowse-' + compID;
            var prev_onbeforeunload = window.onbeforeunload

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
                file_data_name  : 'sp_videoUpload',
                multipart       : true,
                urlstream_upload: true,
                multipart_params: {
                    action  : 'videoUploadAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID,
                    postID  : postID
                },
                max_file_size : '1gb',
                filters : [
                        {title : "MOV files", extensions : "mov, MOV"}
                ],
                init: {

                    FilesAdded: function(up, files) {
                        up.start();
                        $('#sp_videoDropZone-' + compID).hide()
                        $('#videoProgBarContainer-' + compID).show();
                        $('#sp_simpleMenu-' + compID).remove();

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
                        $('#videoProgressBar-' + compID).css('width', file.percent + '%');
                        $('#videoProgressMsg-' + compID).html('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading Video… ' + file.percent + '%, ' + parseInt(up.total.bytesPerSec/1024) +'Kb/s</p>');
                    },

                    Error: function(up, err) {
                        var out = '';
                        for (var i in err) {
                            out += i + ": " + err[i] + "\n";
                        }
                       alert( out );
                    },

                    FileUploaded: function(up, files, response) {
                        if(response){
                            $('#videoProgressMsg-' + compID).html('');
                            $('#videoProgBarContainer-' + compID).hide();
                            $('#playerContainer-' + compID).html( response.response );
                        }
                        window.onbeforeunload = prev_onbeforeunload;
                    }
                }
            });
            uploader.init();
        }, //end initFileDrop

        /**
         * Dynamically initializes the video component
         */
        initComponent: function(component, postID, autoFocus){
            this.initFileDrop($(component));
        },

        /**
         * Statically initializes all media components on document.ready
         */
        init: function(){
            this.setTypeID();
            var thisObj = this;

            $('.sp_video').each(function(){
                thisObj.initFileDrop($(this));
            });

        }
    }

    $(document).ready(function(){
        sp_postVideo.init();
    });
})(jQuery);