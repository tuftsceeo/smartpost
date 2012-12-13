/*
 * source: www.webchief.co.uk/blog/simple-jquery-dropdown-menu/finishedMenu.html
 *
 * Simple menu used for components and other various SP web elements
 */

var simpleMenu = {
	openSubMenu: function(){
		$(this).find('ul').css('visibility', 'visible');
	},
	closeSubMenu: function(){
		$(this).find('ul').css('visibility', 'hidden');			
	},
	init: function(){
		$('.simpleMenu > li').bind('mouseover', this.openSubMenu);
		$('.simpleMenu > li').bind('mouseout', this.closeSubMenu);	
	},
	initComponent: function(component){
		var simpleMenu = $(component).find('.simpleMenu > li');
		$(simpleMenu).bind('mouseover', this.openSubMenu);
		$(simpleMenu).bind('mouseout', this.closeSubMenu);
	}
}

$(document).ready(function() {
   simpleMenu.init();
});