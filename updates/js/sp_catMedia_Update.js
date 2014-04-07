/**
 * Created by ryagudin on 4/3/14.
 */

(function($){
    sp_update.sp_catMedia_Update = {

        /**
         * Handles "update" button click
         * @param buttonElem
         */
        update_button_handler: function(buttonElem){
            var self = this;
            buttonElem.click(function(){
                self.run_media_update();
            });
        },

        /**
         * AJAX call to perform the actual update procedure
         */
        run_media_update: function(){
            smartpost.ajaxcall(
                {
                    nonce: SP_NONCE,
                    action: 'sp_cat_media_update_ajax'
                },
                function(response, statusText, jqXHR){
                    console.log(response);
                    console.log(statusText);
                    console.log(jqXHR);
                    $('#sp_media_update_results').html('<p><b>' + response + '</b> Media components successfully updated.</p>');
                },
                function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError( errorThrown, '.error' );
                }
            )
        },

        init: function(){
            this.update_button_handler( $('#sp-update-media-button') );
        }
    }

    $(document).ready(function(){
        sp_update.sp_catMedia_Update.init();
    })
})(jQuery);
