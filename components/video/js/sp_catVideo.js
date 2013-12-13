/**
 * Handles dashboard JS for the video component.
 *
 * @version 1.2
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu
 * @project SmartPost
 */

(function($){
    var sp_catVideo = {
        /**
         * Enables HTML5Video conversion if available via the use
         * of a checkbox.
         * @param checkBoxElem - jQuery object representing a checkbox element
         */
        enableHTML5Video: function(checkBoxElem){
            var convertToHTML5 = checkBoxElem.is(':checked') ? 1 : 0;
            var compID = checkBoxElem.attr('compID');

            console.log(convertToHTML5);
            console.log(compID);

            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'enableHTML5VideoAJAX',
                    nonce  : SP_ADMIN_NONCE,
                    convertToHTML5 : convertToHTML5,
                    compID: compID
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    if(sp_catComponent !== undefined){
                        sp_catComponent.showError(errorThrown);
                    }else{
                        console.log(errorThrown);
                    }
                }
            });
        },
        init: function(){
            var self = this;
            $('.enableHTML5Video').click(function(){
                self.enableHTML5Video($(this));
            });
        }
    }

    $(document).ready(function(){
       sp_catVideo.init();
    });

})(jQuery);