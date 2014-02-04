/*
 * JS for sp_catAttachments class
 * Used in dashboard/admin page
 */
(function($){
    sp_admin.sp_catAttachments = {
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_admin){
                var types = sp_admin.SP_TYPES;

                //!Important - the raw name of the type
                if(types['Attachments']){
                    this.typeID = types['Attachments']; // Get the type ID of our object
                    sp_admin.SP_TYPES['Attachments'] = this; // Overwrite it with this object
                }
            }else{
                return 0;
            }
        },
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
                var compID = $(this).data('compid');
                var attachmentsOptions = self.setAttachmentsOptions(compID, $(this));
                $('#sp-attachments-' + compID + '-form').ajaxSubmit( attachmentsOptions );
            });
        },

        /**
         * Initialize component if it was created dynamically
         */
        initComponent: function(componentElem){
            this.bindForm();
        },
        /**
         * Init method for the attachments components
         */
        init: function(){
            this.setTypeID();
            this.bindForm();
        }
    }

    $(document).ready(function(){
        sp_admin.sp_catAttachments.init();
    });
})(jQuery);