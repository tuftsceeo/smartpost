/* 
 * JS globals for various admin JS files
 */

if(jQuery){
	jQuery.fn.exists = function(){return this.length > 0;}
}

var spAdmin = spAdmin || {};

/* Declare SmartPost admin page JS Constants */
var SP_NONCE      = sp_admin.ADMIN_NONCE;
var SP_ADMIN_URL  = sp_admin.ADMIN_URL;
var SP_IMAGE_PATH = sp_admin.IMAGE_PATH;