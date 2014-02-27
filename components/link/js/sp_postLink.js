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
    smartpost.sp_postLink = {
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
        /**
         * Returns true if link component is empty, otherwise false
         *
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).data( 'compid' );
            return $(component).find('#sp_the_link-' + compID).text() == "http://... paste a link here";
        },

        /**
         * Saves the thumbnail of the link
         */
        saveThumb: function(thumb, compID){
            var thumbURL = thumb.attr('src');
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveLinkThumbAJAX',
                    nonce : SP_NONCE,
                    compID: compID,
                    thumb : thumbURL
                },
                dataType  : 'json',
                success  : function(response, statusText, jqXHR){
                    var thumb = $('#thumbSelection-' + compID);
                    thumb.html('Thumbnail saved!');
                    thumb.delay(3000).fadeOut();
                },
                error : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Allows user to rotate thumbs in the thumb_results-{compID} div
         * @param int compID The component ID
         */
        rotateThumbs: function(compID){
            var results  = $('#thumb_results-' + compID).find( 'img' );

            if(results){ //we may not always find thumbnails
                var self = this;
                var currThumb = 0;
                var selectThumb = $('#selectThumb-' + compID);
                var prev = $('#prevThumb-' + compID);
                var next = $('#nextThumb-' + compID);

                // Next rotation
                next.click(function(){
                    console.log( currThumb );
                    if( currThumb < results.length - 1 ){
                        $( results[currThumb] ).hide();
                        $( results[++currThumb] ).show();
                    }
                });

                // Prev rotation
                prev.click(function(){
                    console.log( currThumb );
                    if( currThumb > 0 ){
                        $( results[currThumb] ).hide();
                        $( results[--currThumb] ).show();
                    }
                });

                // Save thumbnail
                selectThumb.click(function(){
                    console.log( currThumb );
                    if( $( results[currThumb] ).data( 'thumbid' ) == 0 ){
                        var thumb = $('#thumbSelection-' + compID);
                        thumb.html('Thumbnail saved!');
                        thumb.delay(3000).fadeOut();
                    }else{
                        self.saveThumb( $(results[currThumb]), compID );
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
            var self = this;
            var loading = $('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Loading link info</p>');
            $.ajax({
                url : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveLinkAJAX',
                    nonce: SP_NONCE,
                    compID: compID,
                    link: link
                },
                dataType  : 'html',
                beforeSend: function(jqXHR, settings){
                    $('#sp_the_link-' + compID).after(loading);
                },
                success  : function(response, statusText, jqXHR){
                    $(loading).remove();

                    var spLinkContent = $('#sp-link-content-' + compID);
                    spLinkContent.replaceWith(response);

                    self.initLinkTextBox( $( '#sp_the_link-' + compID ) );
                    smartpost.sp_post.initCkEditors( $( '#sp-editor-content-' + compID ) );

                    var thumbs = $('#sp_link_thumbs-' + compID);
                    if( thumbs.exists() ){
                        self.rotateThumbs(compID);
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    $(loading).remove();
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Initializes a DOM Element for link input
         *
         * @uses saveLink()
         */
        initLinkTextBox: function(linkElem){
            var self = this;

            $(linkElem).editable(function(value, settings){
                var compID = $(this).data('compid');
                var currUrl = $('#sp-link-url-' + compID).val();
                if( value != currUrl ){
                    self.saveLink( value, compID );
                }
                return value;
            },
            {
                placeholder: 'http://... paste a link here',
                onblur     : 'submit',
                cssclass   : 'sp_the_link_editable'
            });
        },

        /**
         * Removes a thumbnail from a link component
         */
        removeThumb: function(compID, thumbDiv){
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'removeLinkThumbAJAX',
                    nonce: SP_NONCE,
                    compID: compID
                },
                dataType : 'json',
                success : function(response, statusText, jqXHR){
                    if(response.success){
                        thumbDiv.html('<p>Drag and drop your own thumbnail!</p>');
                        thumbDiv.addClass('emptyLinkThumb');
                        $('#thumbSelection-' + compID).remove();
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            });
        },
        /**
         * Click event for the thumbnail removeButton
         */
        removeThumbButton: function(removeButton){
            var self = this;
            removeButton.click(function(){
                var thumbDiv = $(this).parent();
                var compID   = $(this).parent().data('compid');
                self.removeThumb( compID, $(thumbDiv) );
            });
        },

        /**
         * Initializes a link component
         *
         * @uses initLinkTextBox()
         */
         initComponent: function(component, postID, autoFocus){
            var linkElem = $(component).find( '.sp_the_link' );
            this.initLinkTextBox( linkElem );
         },

        /**
         * Initializes inline textboxes for the .sp_the_link elements
         */
        init: function(){
            var self = this;
            self.setTypeID();
            $( '.sp_the_link' ).each(function(){
                self.initLinkTextBox( $(this) );
            });
        }
    }

    $(document).ready(function(){
        smartpost.sp_postLink.init();
    });
})(jQuery);