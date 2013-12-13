/*
 * JS for sp_catMedia class
 * Used in dashboard/admin page
 */
(function($){
    spAdmin.sp_catMedia = {
        /**
         * Save media component settings
         * @param compID
         * @param indicatorElem
         * @returns {{url: *, type: string, data: {action: string, nonce: *, compID: *}, dataType: string, success: Function, error: Function}}
         */
        setMediaOptions: function(compID, indicatorElem){
            //Setup the media options
            var spMediaOptions = {
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveMediaSettingsAJAX',
                    nonce : SP_NONCE,
                    compID: compID
                },
                dataType : 'json',
                success	 : function(response, statusText, xhr, $form){
                    console.log(response);
                    if(response.success){
                        var success = $('<span id="successCatUpdate" style="color: green;"> Media Options saved! </span>');
                        indicatorElem.after(success); //To-do: rename indicatorElem as it's confusing where its placed
                        success.delay(3000).fadeOut().delay(3000, function(){ $(this).remove() });
                    }
                },
                error : function(data){
                    spAdmin.adminpage.showError(data.statusText, null);
                }
            };

            return spMediaOptions;
        },

        /**
         * Initializes the media form for jquery-form submissions
         */
        bindForm: function(){
            var self = this;
            $( '.update_sp_media' ).click(function(){
                var compID       = $(this).attr('data-compid');
                var mediaOptions = self.setMediaOptions(compID, $(this));
                $('#spMedia-' + compID + '-form').ajaxSubmit(mediaOptions);
            });
        },

        /**
         * Init method for the media components
         */
        init: function(){
            this.bindForm();
        }
    }

    $(document).ready(function(){
        spAdmin.sp_catMedia.init();
    });
})(jQuery);