/*
 * JS for sp_admin class
 * Used in dashboard/admin page
 */ 

$(function() {
	
	//Make all the category divs sortable
	$( "#sp_cats, #non_sp_cats" ).sortable({
			connectWith: ".categories_list",
			axis: 'y',
			receive: function(event, ui){
				var isSPCat = 0;
				var catID   = ui.item.attr('spcat_id');
				if(!catID){
					catID = ui.item.attr('wpcat_id');
				}else{
					isSPCat = 1;
				}
				
				if(!catID){
					alert("Error: Could not find catID!");
					return;
				}

				$.ajax({
					url: ajaxurl,
					type    : 'POST',
					data    : {action: 'switchCategoryAJAX', catID: catID, isSPCat: isSPCat, nonce: spNonce},
					dataType: 'json',
					success : function(response, statusText, jqXHR){
					 if(response){
								document.location.reload(true);
					 }
					},
					error   : function(jqXHR, statusText, errorThrow){
						showError(statusText);
					}
				})
			}
		});
	
	/* Changes rightArrow to downArrow on toggle and
	 * displays DOM Element based off of divID
	 */
	$('.expandArrow').toggle(
		function(){
			var divID = $(this).attr('data-divID');
			$(this).attr('src', IMAGE_PATH + '/downArrow.png');
			$('#' + divID).show();
		}, 
		function(){
			var divID = $(this).attr('data-divID');
			$(this).attr('src', IMAGE_PATH + '/rightArrow.png');
			$('#' + divID).hide();
	});

	function showError(errorText){
			$('#setting_errors').show().html(
												'<h4>' +
													'Error: ' +
														errorText + 
												'<h4>').attr("class", "error");	
	}

	/*********************************
	 * Load Existing SP Categories   *
	 *********************************/
	
	var loadCatSettings = function(catID){
		$.ajax({
			url				  : ajaxurl,
			type     : 'POST',
			data			  : {action: 'renderSPCatSettingsAJAX', nonce: spNonce, catID: catID},
			dataType : 'html',
			success  : function(response, statusText, jqXHR){
					$('#the_settings').html(response);
			},
			error    : function(jqXHR, statusText, errorThrown){
						showError(statusText);
			}
		})
	}
	
	/**********************************
	 * New SP Category Form Functions *
	 **********************************/
	
	//adds a list item to the #sp_cats list
	var addSPCatToList = function(catTitle, catID){
		var category = $('<li><span id="cat-' + catID + '" class="cat_title">' + catTitle + '</span></li>');
		$('#sp_cats').prepend(category);
	}
	
	//Loads a new or existing category form based off of action
	function spCatOptions(action){
		var spCatOptions = {
				url									 : ajaxurl,
				type								 : 'POST',
				data    				 : {action: action, nonce: spNonce},			
				dataType				 : 'json',
				success						: function(response, statusText, xhr, $form){
					if(response){
						if(response.error){
								showError(response.error);
						}else{
								window.location.href = adminurl + '?page=smartpost&catID=' + response.catID;
						}
					}
				},
				beforeSubmit : function(formData, jqForm, options){ // form validation
																				var form = jqForm[0];
																				if(!form.cat_name.value){
																					showError('Please fill in the category name');
																					$('#cat_name').focus();
																					return false;
																				}
																		},
				error 							: function(data){
						console.log(data);
						showError(data.statusText);
				},
		};
		return spCatOptions;
	}
	
	//Loads a new category form via AJAX
	var getNewCatForm = function(){
				$.post(
					ajaxurl,
					{nonce: spNonce, action: 'catFormAJAX', newSPCat: 1},
					function(data){
						$('#the_settings').html(data);
						$('#cat_name').focus();
						$('#cat_form').submit(function(){
									var newSPCatOptions = spCatOptions('newSPCatAJAX');
									$('#cat_form').ajaxSubmit(newSPCatOptions);
									return false;
						});
					},
					'html'
				);
	}
	
	$('#newSPCatForm').click(function(){
				getNewCatForm();
	});	
	
	if($('#cat_form').exists()){
			$('#cat_form').submit(function(){
						var catOptions = spCatOptions('updateSPCatAJAX');
						$(this).ajaxSubmit(catOptions);
						return false;
			});
	}
	
	/**********************************
	 * Category Response Update       *
	 **********************************/
			$('#responseCatsForm').submit(function(){
					$(this).ajaxSubmit({
							url									 : ajaxurl,
							type								 : 'POST',
							data    				 : {action: 'responseCatAJAX', nonce: spNonce},			
							dataType				 : 'json',
							success						: function(response, statusText, xhr, $form){
								if(response.error){
										showError(response.error);
								}else{
									if( !$('#successCatUpdate').exists() ){
										var success = $('<p id="successCatUpdate"> Response Categories saved! </p>');
										$('#submitResponseCats').after(success);
										success.delay(3000).fadeOut();
										success.delay(3000, function(){ $(this).remove() });
									}
								}
							},
							error 							: function(data){
									showError(data.statusText);
							},						
					}); //end ajaxSubmit
					return false;
		}); //end submit
		
});