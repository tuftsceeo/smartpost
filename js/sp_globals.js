/* 
 * JS globals for various JS files
 */

if(jQuery){
	jQuery.fn.exists = function(){return this.length > 0;}
}

var smartpost  = smartpost  || {};
var sp_widgets = sp_widgets || {};

/* Declare SmartPost JS Constants */
var SP_NONCE       = sp_globals.spNonce;
var SP_AJAX_URL    = sp_globals.ajaxurl;
var SP_IMAGE_PATH  = sp_globals.IMAGE_PATH;
var SP_PLUGIN_PATH = sp_globals.PLUGIN_PATH;