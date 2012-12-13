/* 
 * JS globals for various admin JS files
 */

var $ = jQuery; 
$.fn.exists 	= function(){return this.length > 0;}

var spNonce 	= sp_admin.adminNonce;
var adminurl = sp_admin.adminurl;
var IMAGE_PATH = sp_admin.IMAGE_PATH;