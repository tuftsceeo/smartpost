/**
 * SmartPost global variables go here.
 * Please keep global variables within the "sp_globals" namespace as to not pollute the global namespace.
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 */

var smartpost  = smartpost  || {};
var sp_widgets = sp_widgets || {};
var sp_globals = sp_globals || {};
var sp_admin   = sp_admin   || {};
var sp_update  = sp_update  || {};

//To-do: remove these global variables to minimize namespace pollution
var SP_AJAX_URL    = sp_globals.SP_AJAX_URL;
var SP_NONCE       = sp_globals.SP_NONCE;
var SP_IMAGE_PATH  = sp_globals.SP_IMAGE_PATH;
var SP_PLUGIN_PATH = sp_globals.SP_PLUGIN_PATH;
var SP_ADMIN_URL   = sp_globals.SP_ADMIN_URL;
var SP_MAX_UPLOAD_SIZE = sp_globals.MAX_UPLOAD_SIZE;

if(jQuery){
    jQuery.fn.exists = function(){ return this.length > 0; };

    (function($){

        /**
         * Generic ajax call prefilled with url and nonce data.
         * @param data
         * @param onSuccess
         * @param onError
         * @param dataType
         */
        smartpost.ajaxcall = function(data, onSuccess, onError, dataType){

            if(dataType == undefined || dataType == '')
                dataType = 'json';

            data.nonce = SP_NONCE;

            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data: data,
                dataType : dataType,
                success  : function(response, statusText, jqXHR){
                    onSuccess(response, statusText, jqXHR);
                },
                error : function(jqXHR, statusText, errorThrown){
                    onError(jqXHR, statusText, errorThrown);
                }
            });
        }

    })(jQuery);
}

