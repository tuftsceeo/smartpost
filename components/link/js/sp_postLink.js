/*
 * JS sp_postLink Component class
 * Used alongside sp_postLink for AJAX calls
 * Used in front-end posts
	*
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */

(function($){
    var sp_postLink = {
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Link']){
                    this.typeID = types['Link'];
                    sp_globals.SP_TYPES[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },
     currUrl: "",
        /**
         * Returns true if link component is empty, otherwise false
         *
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).attr('data-compid');
            return $(component).find('#sp_the_link-' + compID).text() == "Click to add link";
        },

        /**
         * Initiates HTML5 fileDrop for custom link thumbnails
         */
        customThumbFileDrop: function(linkThumbElem, postID){
            var thisObj     = this;
            var compID      = linkThumbElem.attr('data-compid');
            var fallback_id = 'sp_link_browse-' + compID;

            if(postID == undefined){
                postID = jQuery('#postID').val();
                if(postID == undefined){
                    postID = jQuery('#sp_qpPostID').val();
                }
            }

            linkThumbElem.filedrop({
                fallback_id: fallback_id,
                url: SP_AJAX_URL,
                paramname: 'sp_link_thumb',
                data: {
                    action  : 'saveCustomLinkThumbAJAX',
                    nonce   : SP_NONCE,
                    compID  : compID,
                    postID  : postID
                },
                error: function(err, file) {
            switch(err) {
              case 'BrowserNotSupported':
                    $('#' + fallback_id).show();
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
                    var firstChild = linkThumbElem.children()[0];
                    $(firstChild).replaceWith('<span id="uploadingLinkThumb-' + compID + '"><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Uploading image... </span>');
                },
                uploadFinished: function(i, file, response, time) {
                    if(response){
                        var removeButton = linkThumbElem.find('.removeLinkThumb');
                        $('#uploadingLinkThumb-' + compID).replaceWith('<a href="' + response.url + '">' + response.thumb + '</a>');
                        linkThumbElem.removeClass('emptyLinkThumb');
                        removeButton.attr('data-thumbid', response.id)
                        thisObj.removeButtonHover(linkThumbElem);
                        $('#thumbSelection-' + compID).remove();
                    }
                }
            });
        },

        /**
         * Saves the thumbnail of the link
         */
        saveThumb: function(thumb, compID){
            var thisObj  = this;
            var thumbURL = thumb.attr('src');
            $.ajax({
                url				   : SP_AJAX_URL,
                type      : 'POST',
                data			   :
                {
                    action: 'saveLinkThumbAJAX',
                    nonce : SP_NONCE,
                    compID: compID,
                    thumb : thumbURL
                },
                dataType  : 'json',
                success  : function(response, statusText, jqXHR){
                    $('#thumbSelection-' + compID).html('Thumbnail saved!');
                    $('#thumbSelection-' + compID).delay(3000).fadeOut();
                },
                error    : function(jqXHR, statusText, errorThrown){
                        if(smartpost.sp_postComponent)
                            smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Allows user to rotate thumbs in the thumb_results-{compID} div
         *
         * @param int compID The component ID
         */
        rotateThumbs: function(compID){
            var results  = $('#thumb_results-' + compID).find('img');

            if(results){ //we may not always find thumbnails
                var thisObj     = this;
                var currThumb 	= 0;
                var selectThumb = $('#selectThumb-' + compID);
                var prev    = $('#prevThumb-' + compID);
                var next    = $('#nextThumb-' + compID);

                //Next rotation
                next.click(function(){
                    if(currThumb < results.length-1){
                        $(results[currThumb]).hide();
                        $(results[++currThumb]).show();
                    }
                })

                //Prev rotation
                prev.click(function(){
                    if(currThumb > 0){
                        $(results[currThumb]).hide();
                        $(results[--currThumb]).show();
                    }
                })

                //Save thumbnail
                selectThumb.click(function(){
                    if($(results[currThumb]).attr('data-thumbid') == 0){
                        $('#thumbSelection-' + compID).html('Thumbnail saved!');
                        $('#thumbSelection-' + compID).delay(3000).fadeOut();
                    }else{
                        thisObj.saveThumb($(results[currThumb]), compID);
                    }
                })
            }

        },

        /**
         * Saves a link component's link to the database.
         *
         * @param string link the link to save
         * @param int    compID  the component ID
         */
        saveLink: function(link, compID){
            var thisObj = this;
            var loading =	$('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Loading link info</p>');
            $.ajax({
                url				   : SP_AJAX_URL,
                type      : 'POST',
                data			   : {action: 'saveLinkAJAX', nonce: SP_NONCE, compID: compID, link: link},
                dataType  : 'html',
                beforeSend: function(jqXHR, settings){
                        $('#sp_the_link-' + compID).after(loading);
                },
                success  : function(response, statusText, jqXHR){
                        $(loading).remove();
                        var thumbAndDesc = $('#thumbAndDesc-' + compID);
                        thumbAndDesc.html(response);

                        var thumbs = $('#sp_link_thumbs-' + compID);
                        var desc   = $('#sp_link_desc-' + compID);
                        var removeImg = $('#removeThumb-' + compID);

                        thisObj.initDescEditor(desc);

                        if(thumbs.exists()){
                            thisObj.rotateThumbs(compID);
                            thisObj.removeButtonHover(thumbs, removeImg);
                            thisObj.removeButtonClick(removeImg, thumbs);
                            thisObj.customThumbFileDrop(thumbs);
                        }else{
                            var emptyThumb = $('#sp_link_thumb-' + compID);
                            thisObj.customThumbFileDrop(emptyThumb);
                        }

                        //Remove required component if it's the last one
                        if(link != ""){
                                if(smartpost.sp_postComponent.isLast(compID) && smartpost.sp_postComponent.isRequired(compID))
                                    $('#comp-' + compID).removeClass('requiredComponent');
                        }else{
                                if(smartpost.sp_postComponent.isLast(compID) && smartpost.sp_postComponent.isRequired(compID))
                                    $('#comp-' + compID).addClass('requiredComponent');
                        }
                        thisObj.currUrl = link;
                },
                error    : function(jqXHR, statusText, errorThrown){
                        $(loading).remove();
                        if(smartpost.sp_postComponent)
                            smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Saves a link component's description to the database.
         *
         * @param string    content   The content to be saved
         * @param string    contentID The DOMElem id of the content's container
         * @param nicEditor instance  The editor instance
         */
        saveDescription: function(content, contentID, instance){
            var thisObj = this;
      var compID = $('#' + contentID).attr('data-compid');
            $.ajax({
                url				   : SP_AJAX_URL,
                type      : 'POST',
                data			   : {action: 'saveLinkDescAJAX', nonce: SP_NONCE, compID: compID, desc: content},
                dataType  : 'json',
                success  : function(response, statusText, jqXHR){
                        console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                        if(smartpost.sp_postComponent)
                            smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },
    /**
     * Initializes all .sp_the_link class textboxes
     *
     * @uses saveLink()
     */
        initLinkTextBoxes: function(){
                var thisObj = this;
                $('.sp_the_link').editable(function(value, settings){
                    var compID = $(this).attr('data-compid');
                    thisObj.saveLink(value, compID);

                    return value;
                },
                {
                    placeholder: 'Click to add a link',
                    onblur     : 'cancel',
                    cssclass   : 'sp_the_link_editable',
                    submit     : 'Ok',
                    cancel     : 'Cancel',
                });
        },

    /**
     * Initializes a DOM Element for link input
     *
     * @uses saveLink()
     */
        initLinkTextBox: function(linkDiv){
                var thisObj = this;
                $(linkDiv).editable(function(value, settings){
                    var compID = $(this).attr('data-compid');
                    thisObj.saveLink(value, compID);
                    return value;
                },
                {
                    placeholder: 'Click to add a link',
                    onblur     : 'cancel',
                    submit     : 'Ok',
                    cssclass   : 'sp_the_link_editable',
                    cancel     : 'Cancel',
                });
        },

    /**
     * Initializes the description editor for the link description
     *
     * @uses initLinkTextBox()
     */
        initDescEditor: function(descDiv){
            var thisObj = this;
            if(smartpost.sp_postComponent){
                var elementID = $(descDiv).attr('id');
                smartpost.initEditor(elementID, false, thisObj.saveDescription,'Click to add a description');
            }
        },

        /**
         * Removes a thumbnail from a link component
         */
        removeThumb: function(compID, thumbDiv){
            $.ajax({
                    url				   : SP_AJAX_URL,
                    type      : 'POST',
                    data			   : {action: 'removeLinkThumbAJAX', nonce: SP_NONCE, compID: compID},
                    dataType  : 'json',
                    success  : function(response, statusText, jqXHR){
                        if(response.success){
                            thumbDiv.html('<p>Drag and drop your own thumbnail!</p>');
                            thumbDiv.addClass('emptyLinkThumb');
                            $('#thumbSelection-' + compID).remove();
                        }
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                            if(smartpost.sp_postComponent)
                                smartpost.sp_postComponent.showError(errorThrown);
                    }
                });
        },
        /**
         * Click event for the thumbnail removeButton
         */
        removeButtonClick: function(removeButton){
            var thisObj = this;
            removeButton.click(function(){
                var thumbDiv = $(this).parent();
                var compID   = $(this).parent().attr('data-compid');
                thisObj.removeThumb(compID, $(thumbDiv));
                $(thumbDiv).unbind('mouseenter mouseleave');
                $(this).hide();
            });
        },

        /**
         * Shows removeButton for the thumbnail on hover
         *
         * @param DOMElem hoverElem The thumbnail HTML container
         * @param DOMElem removeButton The element representing the remove button
         */
        removeButtonHover: function(thumbElem){
            var thisObj = this;
            var compID = thumbElem.attr('data-compid');
            var removeButton = $('#removeThumb-' + compID);
            if(removeButton.attr('data-thumbid')){
                thumbElem.hover(
                    function(){ removeButton.show() },
                    function(){ removeButton.hide() }
                );
            }
        },

    /**
     * Initializes a link component
     *
     * @uses initLinkTextBox()
     */
     initComponent: function(component, postID, autoFocus){
        var linkDiv = $(component).find('.sp_the_link');
        this.initLinkTextBox(linkDiv);
        if(autoFocus)
            $(linkDiv).click();
     },

    /**
     * Initializes inline textboxes for the .sp_the_link elements
     */
        init: function(){
            var thisObj = this;
            this.setTypeID();
            this.initLinkTextBoxes();

            $('.sp_link_desc').each(function(){
                thisObj.initDescEditor($(this));
            });

            thisObj.customThumbFileDrop($('.sp_link_html, .sp_link_img, .sp_link_pdf'));

            $('.sp_link_html, .sp_link_img, .sp_link_pdf').each(function(){
                thisObj.removeButtonHover($(this));
            })
            this.removeButtonClick($('.removeLinkThumb'));
        }
    }

    $(document).ready(function(){
        sp_postLink.init();
    });
})(jQuery);