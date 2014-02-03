/*
 * JS for sp_catAttachments class
 * Used in dashboard/admin page
 */
(function($){
    sp_admin.sp_catAttachments = {
        /**
         * Save media component settings
         * @param compID
         * @param indicatorElem
         * @returns {{url: (SP_AJAX_URL|*), type: string, data: {action: string, nonce: (SP_NONCE|*), compID: *}, dataType: string, success: Function, error: Function}}
         */
        setAttachmentsOptions: function(compID, indicatorElem){
            //Setup the media options
            var spAttachmentsOptions = {
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveAttachmentsSettingsAJAX',
                    nonce : SP_NONCE,
                    compID: compID
                },
                dataType : 'json',
                success	 : function( response ){
                    console.log(response);
                    if(response.success){
                        var successElem = $('<span id="sp-attachments-success-udate" style="color: green;"> Attachments Options saved! </span>');
                        indicatorElem.after(successElem);
                        successElem.delay(3000).fadeOut().delay(3000, function(){
                            $(this).remove()
                        });
                    }
                },
                error : function(data){
                    sp_admin.adminpage.showError(data.statusText, null);
                }
            };

            return spAttachmentsOptions;
        },

        /**
         * Initializes the attachments form for jquery-form submissions
         */
        bindForm: function(){
            var self = this;
            $( '.update-sp-cat-attachments' ).click(function(){
                var compID       = $(this).data('compid');
                var attachmentsOptions = self.setAttachmentsOptions(compID, $(this));
                $('#sp-attachments-' + compID + '-form').ajaxSubmit( attachmentsOptions );
            });
        },

        /**
         * Init method for the attachments components
         */
        init: function(){
            this.bindForm();
        }
    }

    $(document).ready(function(){
        sp_admin.sp_catAttachments.init();
    });
})(jQuery);