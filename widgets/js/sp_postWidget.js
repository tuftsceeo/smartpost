/**
 * JS for the sp_postWidget class. Used in
 * front-end posts for post manipulation and
 * options.
 */
(function($){
    sp_widgets.sp_postWidget = {

        rotateImgs: function(){
            var thisObj = this;
            var results   		= $('#thumb_results');
            var currThumb 		= $('#thumb_results').children().first();
            var selectThumb = $('#selectImg');
            var prev    = $('#prevThumb');
            var next    = $('#nextThumb');

            //Show the first thumbnail in case it's hidden
            currThumb.show();

            //Next rotation
            next.click(function(){
                if(currThumb.next().length > 0){
                    currThumb.hide();
                    currThumb = currThumb.next().show();
                }
            })

            //Prev rotation
            prev.click(function(){
                if(currThumb.prev().length > 0){
                    currThumb.hide();
                    currThumb = currThumb.prev().show();
                }
            })

            //Save thumbnail
            selectThumb.click(function(){
                thisObj.setFeaturedImg(currThumb.attr('data-id'));
            })
        },

        setFeaturedImg: function(attachmentID, postID){
            if(postID == undefined){
                var postID = $('#postID').val();
            }

            //If it's the same image, do nothing
            var featuredID = $('.featuredImg').attr('data-id');
            if(featuredID == attachmentID){
                return false;
            }

            $.ajax({
                url				  : SP_AJAX_URL,
                type     : 'POST',
                data			  : {action: 'setFeaturedImgAJAX', nonce: SP_NONCE, postID: postID, attachmentID: attachmentID},
                dataType : 'json',
                success  : function(response, statusText, jqXHR){

                        if(response.success){

                            //Set the new thumb to the featured img
                            $('.featuredImg').removeClass('featuredImg');
                            $('#thumb_results').find('img[data-id="' + attachmentID + '"]').addClass('featuredImg');

                            var thumbSaved = $('<p>Featured Image saved!</p>');
                            $('#thumbSelection-' + postID).after(thumbSaved);
                            thumbSaved.delay(3000).fadeOut().queue(function(){ $(this).remove(); });
                        }
                },
                error : function(jqXHR, statusText, errorThrown){
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Initializes HTML5 filedrop for the featured image of a post
         * @param DOMElem elementID The ID of the DOMElem to bind the filedrop to
         * @param int     postID    The postID to set the featured img to
         */
        initFtImgFileDrop: function(elementID, postID){
            var thisObj     = this;
            var fallback_id = 'sp_custom_ft_img_browse';

            if(postID == undefined){
                postID = jQuery('#postID').val();
            }

            elementID.filedrop({
                fallback_id: fallback_id,
                url: SP_AJAX_URL,
                paramname: 'sp_custom_ft_img',
                data: {
                    action  : 'ftImgFileDrop',
                    nonce   : SP_NONCE,
                    postID  : postID
                },
                error: function(err, file) {
            switch(err) {
              case 'BrowserNotSupported':
                    $('.sp_browse').show();
                  break;
              case 'TooManyFiles':
                  smartpost.sp_postComponent.showError('Too many files! Please upload less');
                  break;
              case 'FileTooLarge':
                  smartpost.sp_postComponent.showError(file.name + ' is too large!');
                  break;
              default:
                  smartpost.sp_postComponent.showError(err);
                  break;
            }
                        $('#loadingGIF').remove();
                },
                maxfiles: 1,
                maxfilesize: 32, // max file size in MBs
                uploadStarted: function(i, file, len){
                    console.log('uploading');
                },
                uploadFinished: function(i, file, response, time) {
                    console.log('uploadFinished');
                }
            });

        },

        /**
         * Initializes the postWidget
         */
        init: function(){
            //this.initFtImgFileDrop($('#customFeaturedImage'));
            this.rotateImgs();
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_postWidget.init();
    });
})(jQuery);