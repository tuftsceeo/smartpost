/*
 * JS for sp_catMedia class
 * Used in dashboard/admin page
 */
 
var sp_catMedia = {
	
	/**
	 * Display errors
	 * !To-do: turn sp_admin.js into object and inherit its methods
	 */ 
	showError: function(errorText){
			$('#setting_errors').show().html(
												'<h4>' +
													'Error: ' +
														errorText + 
												'<h4>').attr("class", "error");	
	},
	
	/**
	 * Save media component settings
	 *
	 * @param int compID The media component's ID
	 */
	setMediaOptions: function(compID, indicatorElem){
		var thisOb = this;
		//Setup the media options
		var spMediaOptions = {
				url									 : ajaxurl,
				type								 : 'POST',
				data    				 : {action: 'saveMediaSettingsAJAX', nonce: spNonce, compID: compID},
				dataType				 : 'json',
				success						: function(response, statusText, xhr, $form){
					if(response.success){
							var success = $('<span id="successCatUpdate" style="color: green;"> Media Options saved! </span>');
							indicatorElem.after(success); //To-do: rename indicatorElem as it's confusing where its placed
							success.delay(3000).fadeOut().delay(3000, function(){ $(this).remove() });
					}
				},
				error 							: function(data){
						thisObj.showError(data.statusText);
				},
		};
		
		return spMediaOptions;
	},
	
	/**
	 * Initializes the media form for jquery-form submissions
	 */
	bindForm: function(){
		var thisObj = this;
		$( '.update_sp_media' ).click(function(){
				var compID       = $(this).attr('data-compid');
				var mediaOptions = thisObj.setMediaOptions(compID, $(this));
				$('#componentOptions-' + compID + '-form').ajaxSubmit(mediaOptions);
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
	sp_catMedia.init();
});