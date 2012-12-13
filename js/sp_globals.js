/* 
 * JS globals for various JS files
 */

if(jQuery){
	var $ = jQuery;
	$.fn.exists 	= function(){return this.length > 0;}
}
var ajaxurl  = sp_globals.ajaxurl;
var spNonce 	= sp_globals.spNonce;
var IMAGE_PATH = sp_globals.IMAGE_PATH;
var PLUGIN_PATH = sp_globals.PLUGIN_PATH;
