/*
 * JS for sp_catComponent class
 * Used in dashboard/admin page
 */ 

$(function() {

	//Make all the component divs sortable
	$( "#catComponentList" ).sortable({
		axis       : 'y',
		handle     : 'h3',
		stop       : function(event, ui){
				setCompOrder($(this).sortable('toArray'));
		}
	});

	function setCompOrder(components){
			if(components.length > 0){
				var compOrder = new Array();
				var catID     = $('#catID').val();
				
				$(components).each(function(index, value){
					var compID = value.split('-')[1];
					compOrder[index] = compID;
				});
				
				$.ajax({
					url				  : ajaxurl,
					type     : 'POST',
					data			  : {action      : 'setCompOrderAJAX', 
																	nonce       : spNonce,  
																	compOrder   : compOrder,
																	catID       : catID
																	},
					dataType : 'json',
					success  : function(response, statusText, jqXHR){
						console.log(response);
					},
					error    : function(jqXHR, statusText, errorThrown){
							showError(errorThrown);
					}
				})
			}
	}

	function showError(errorText){
			$('#setting_errors').show().html(
												'<h4>' +
													'Error: ' +
														errorText + 
												'<h4>').attr("class", "error");	
	}

	//Display the new component form
	$('#addNewComponent').click(function(){
		$('#compFormWrapper').show();
		$('#compName').focus();
		$(this).hide();
	})
	
	//Hide the new component form
	$('#cancelCompForm').click(function(){
		$('#compFormWrapper').hide();
		$('#addNewComponent').show();
	})

	var updateSettings = function(updateAction, compID, value){
		console.log('updateAction: ' + updateAction + ', value:' + value);
		$.ajax({
			url				  : ajaxurl,
			type     : 'POST',
			data			  : {action      : 'updateSettingsAJAX', 
															nonce       : spNonce, 
															compID      : compID, 
															updateAction: updateAction,
															value       : value
															},
			dataType : 'json',
			success  : function(response, statusText, jqXHR){
				console.log(response);
			},
			error    : function(jqXHR, statusText, errorThrown){
					showError(errorThrown);
			}
		})
	}
	
	//delete a component
	$('.delete_component').click(function(){
			var compID  = $(this).attr('data-compid');
			$.ajax({
					url				  : ajaxurl,
					type     : 'POST',
					data			  : {action      : 'deleteComponentAJAX', 
																	nonce       : spNonce, 
																	compID      : compID
																	},
					dataType : 'json',
					success  : function(response, statusText, jqXHR){
							$('#comp-' + compID).remove();
							setCompOrder($('#catComponentList').sortable('toArray'));
					},
					error    : function(jqXHR, statusText, errorThrown){
							showError(errorThrown);
					}
				})
	});

	//isRequired/isDefault checkbox handler
	var disableDefault = function(checkBoxes){
		var isDefault   = checkBoxes.get(0); //index 0 -> #isDefault
		var isRequired  = checkBoxes.get(1); //index 1 -> #isRequired
		var compID      = $(isDefault).attr('data-compid');
		
		//Update isDefault and isRequired
		checkBoxes.click(function(){
			if($(this).is($(isRequired))){
					if($(isRequired).attr('checked')){
					 $(isDefault).attr('checked', 'checked').attr('disabled', 'disabled');
						if(compID > 0 ){
							updateSettings('SetIsDefault', compID, 1);
							updateSettings('SetIsRequired', compID, 1);
						}
					}else{
						if(compID > 0 ){
						updateSettings('SetIsRequired', compID, 0);
						}
						$(isDefault).removeAttr('disabled');
					}
			}
			
			if($(this).is($(isDefault)) && (compID > 0)){
					if($(isDefault).attr('checked')){
						updateSettings('SetIsDefault', compID, 1);
					}else{
						updateSettings('SetIsDefault', compID, 0);
					}
			}
		});
	}
	
	//Add isRequired/isDefault checkbox restraints to all components
 $('.requiredAndDefault').each(function(){
 					var checkBoxes = $(this).find('.compRestrictions'); 
 					disableDefault(checkBoxes);
 });
	
	//Component form options
	var newCompOptions = {
			url									 : ajaxurl,
			type								 : 'POST',
			data    				 : {action: 'newComponentAJAX', nonce: spNonce},			
			dataType				 : 'json',
			success						: function(response, statusText, xhr, $form){
					if(response.success){
						location.reload(true);
					}else if(response.error){
						showError(response.error);
					}
			},				
			error 							: function(data){
					showError(data.statusText);
			},
	};

	var initializeCompSettingsForm = function(formID){
			if($(formID).exists()){
					//Form submission
					$(formID).submit(function(){
								$(this).ajaxSubmit(newCompOptions);
								return false;
					});
					var checkBoxes = $(formID).find('.compRestrictions');
					disableDefault(checkBoxes);
			}
	}
	
	initializeCompSettingsForm('#componentSettings-form');	
	
})