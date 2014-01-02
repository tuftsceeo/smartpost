/**
 * JS sp_postContent Component class
 * Used alongside sp_postContentAJAX for AJAX calls
 * Used in front-end posts
 *
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */

(function($){
    var sp_postContent = {

        /**
         * Required for all post component JS objects.
         * Used in sp_globals.types to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.types;

                //!Important - the raw name of the type
                if(types['Content']){
                    this.typeID = types['Content'];
                    sp_globals.types[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },

        /**
         * Returns true if content component is empty, otherwise false
         *
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).attr('data-compid');
            return $(component).find('#sp_content-' + compID).text() == "Click to add content";
        },

        /**
         * Saves a content component's content to the database.
         *
         * @param string    content   The content to be saved
         * @param string    contentID The DOMElem id of the content's container
         * @param nicEditor instance  The editor instance
         */
        saveContent: function(content, contentID, instance){
            var thisObj = this;
            var compID = $('#' + contentID).attr('data-compid');
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveContentAJAX',
                    nonce: SP_NONCE,
                    compID: compID,
                    content: content
                },
                dataType : 'json',
                error    : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                }
            });
        },

        /**
         * Initializes editors for a specific component. Used when created a new element.
         * @see smartpost.sp_postComponent.initComponent()
         * @param component
         * @param postID
         * @param autoFocus
         */
        initComponent: function(component, postID, autoFocus){
            var thisObj  = this;
            if(smartpost.sp_postComponent){
                var content  = $(component).find('.sp_richtext, .sp_plaintext');
                var panelID  = $(component).find('.editorPanel').attr('id');
                var richText = Boolean($(content).attr('data-richtext'));
                smartpost.sp_postComponent.addNicEditor(content.attr('id'), panelID, this.saveContent, "Click to add content");
            }else{
                console.log('Error: could not find the smartpost.sp_postComponent object!');
            }
            if(autoFocus){
                content.click().focus();
            }
        },

        /**
         * Initializes editors for the .sp_richtext and .sp_plaintext classes
         */
        init: function(){
            this.setTypeID();
            var thisObj = this;
            if(smartpost.sp_postComponent){
                $('.sp_richtext, .sp_plaintext').each(function(){
                    var elementID  = $(this).attr('id');
                    var compID     = $(this).attr('data-compid');
                    var panelID    = $('#editorPanel-' + compID).exists() ? 'editorPanel-' + compID : undefined;
                    smartpost.sp_postComponent.addNicEditor( elementID, panelID, thisObj.saveContent, "Click to add content" );
                });
            }
        }
    }

    $(document).ready(function(){
        sp_postContent.init();
    });
})(jQuery);