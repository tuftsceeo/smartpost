/*
 * JS for sp_catComponent class.
 * Used in dashboard/admin page.
 */

(function($){
    sp_admin.sp_catComponent = {

        /**
         * Saves the title of a component given a title and component ID.
         * @param compID
         * @param title
         * @param cl - closure that gets passed the server response.
         */
        saveCatCompTitleAJAX: function(compID, title, cl){
            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {
                    action : 'saveCatCompTitleAJAX',
                    nonce  : SP_NONCE,
                    compID : compID,
                    title  : title
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    cl(response, statusText, jqXHR);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown);
                }
            })
        },

        /**
         * Given a componentID, makes the appropriate AJAX call
         * to delete the component server side. On a successful
         * response, remove the HTML associated with that component.
         * @param componentID
         * @param cl - closure that gets passed the response from the server
         */
        deleteComponent: function(compID, cl){
            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {
                    action : 'deleteComponentAJAX',
                    nonce  : SP_NONCE,
                    compID : compID
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    cl(response, statusText, jqXHR);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown);
                }
            })
        },

        /**
         * Given a component typeID and a category ID, adds that component
         * to a category template represented by catID
         * @param catID
         * @param typeID
         * @param cl - closure that gets passed the server response
         */
        addComponent: function(catID, typeID, cl){
            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {
                    action : 'newComponentAJAX',
                    nonce  : SP_NONCE,
                    catID  : catID,
                    typeID : typeID
                },
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    //Remove the outer parent: <div id="advanced-sortables">
                    cl($(response).html(), statusText, jqXHR);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown, null);
                }
            })
        },

        /**
         * Initializes a component elements by binding relevant JS events to it.
         * @param componentElem
         * @param typeID
         * @param cl - closure
         */
        initializeComponent: function(componentElem, typeID, cl){
            if(sp_admin.types)
                var componentJS = sp_admin.types[typeID];

            //Bind component-specific events
            if(componentJS)
                componentJS.initComponent(componentElem);
        },

        /**
         * A generic function that updates category component options.
         * @param theAction
         * @param compID
         * @param value
         */
        updateCompOptions: function(theAction, compID, value){
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action : 'updateSettingsAJAX',
                    nonce  : SP_NONCE,
                    compID : compID,
                    updateAction : theAction,
                    value  : value
                },
                dataType : 'html',
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown);
                }
            })
        },

        /**
         * Handles the required/default checkbox behavior on the admin page.
         * Invariance: If the required checkboxed is checked off, then the
         * default checkox should be disabled AND checked off.
         * @param checkBoxes
         */
        disableDefault: function(checkBoxes){
            var self = this;
            var isDefault   = checkBoxes.get(0); //index 0 -> #isDefault
            var isRequired  = checkBoxes.get(1); //index 1 -> #isRequired
            var compID      = $(isDefault).attr('data-compid');

            //Update isDefault and isRequired
            checkBoxes.click(function(){
                if($(this).is($(isRequired))){
                    if($(isRequired).attr('checked')){
                        $(isDefault).attr('checked', 'checked').attr('disabled', 'disabled');
                        if(compID > 0 ){
                            self.updateCompOptions('SetIsDefault', compID, 1);
                            self.updateCompOptions('SetIsRequired', compID, 1);
                        }
                    }else{
                        if(compID > 0 ){
                            self.updateCompOptions('SetIsRequired', compID, 0);
                        }
                        $(isDefault).removeAttr('disabled');
                    }
                }

                if($(this).is($(isDefault)) && (compID > 0)){
                    if($(isDefault).attr('checked')){
                        self.updateCompOptions('SetIsDefault', compID, 1);
                    }else{
                        self.updateCompOptions('SetIsDefault', compID, 0);
                    }
                }
            });
        },

        /**
         * Initializes component functions/behavior required for
         * interaction the admin page.
         */
        init: function(){
            var self = this;
            //Add isRequired/isDefault checkbox restraints to all components
            $('.requiredAndDefault').each(function(){
                var checkBoxes = $(this).find('.compRestrictions');
                self.disableDefault(checkBoxes)
            });
        }

    }

    $(document).ready(function(){
        sp_admin.sp_catComponent.init();
    })
})(jQuery);