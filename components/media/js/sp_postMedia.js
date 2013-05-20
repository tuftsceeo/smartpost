/*
 * JS sp_postMedia Component class
 * Used alongside sp_postMedia for AJAX calls
 * Used in front-end posts
	*
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */
(function($){
	smartpost.sp_postMedia = {
		/**
		 * Required for all post component JS objects.
		 * Used in sp_globals.types to determine which 
		 * methods to call for different post component types
		 */	
		setTypeID: function(){
			if(sp_globals){
				var types = sp_globals.types;
	
				//!Important - the raw name of the type
				if(types['Media']){
					this.typeID = types['Media'];
					sp_globals.types[this.typeID] = this;
				}
			}else{
				return 0;
			}
		},
		
		/**
		 * Returns true if the media component is a gallery,
		 * otherwise false.
		 */
		isGallery: function(compID){
			return Boolean( $('#sp_media-' + compID).attr('data-isgallery') );
		},
		/**
		 * Returns true if media component is empty, otherwise false
		 *
		 * @param object component The component
		 * @return bool True if it's empty, otherwise false
		 */
		isEmpty: function(component){
			var compID = $(component).attr('data-compid');
			return $(component).find('#drop_indicator-' + compID).exists();
		}, 	
		
		/**
		 * Initializes the webcam plugin
		 * @see http://www.xarg.org/project/jquery-webcam-plugin/
		 */
		initWebcam: function(component, compID){
			var thisObj   = this;
			var compID    = component.attr('data-compid');
			var isGallery = this.isGallery(compID);
					
			//Use xarg code to get an image
			var pos = 0, ctx = null, saveCB, image = [];
	  var canvas = document.createElement("canvas");
	  canvas.setAttribute('width', 320);
	  canvas.setAttribute('height', 240);		
			
			if (canvas.toDataURL) {
				ctx    = canvas.getContext("2d");
	   image  = ctx.getImageData(0, 0, 320, 240);		
				saveCB = function(data) {	                 
	    var col = data.split(";");
	    var img = image;
	    for(var i = 0; i < 320; i++) {
	     var tmp = parseInt(col[i]);
	     img.data[pos + 0] = (tmp >> 16) & 0xff;
	     img.data[pos + 1] = (tmp >> 8) & 0xff;
	     img.data[pos + 2] = tmp & 0xff;
	     img.data[pos + 3] = 0xff;
	     pos+= 4;
	    }
	
	    if (pos >= 4 * 320 * 240) {
	     ctx.putImageData(img, 0, 0);
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {action: 'mediaUploadAJAX', 
															nonce: spNonce, 
															type: 'data', 
															compID: compID, 
															image: canvas.toDataURL("image/png")
														},
								dataType  : 'json',
								beforeSend: function (jqXHR, settings){
									thisObj.loadingGif(isGallery, compID);
								},
								success  : function(response, statusText, jqXHR){
									if(response){
										thisObj.insertThumb(response, isGallery, compID, $('#loadingGIF'));
									}									
								},
								error    : function(jqXHR, statusText, errorThrown){
										if(sp_postComponent)
											sp_postComponent.showError(errorThrown);
								}
							});
	     pos = 0;
	    }
	   };
	 	} else {
	   saveCB = function(data) {
	    image.push(data);
	    pos+= 4 * 320;
	    if (pos >= 4 * 320 * 240) {
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {action: 'mediaUploadAJAX', 
															nonce: spNonce, 
															type: 'pixel', 
															compID: compID,
															image:  image.join('|')
														},
								dataType  : 'json',
								beforeSend: function (jqXHR, settings){
									thisObj.loadingGif(isGallery, compID);
								},
								success  : function(response, statusText, jqXHR){
									if(response){
										thisObj.insertThumb(response, isGallery, compID, $('#loadingGIF'));
									}
								},
								error    : function(jqXHR, statusText, errorThrown){
										if(sp_postComponent)
											sp_postComponent.showError(errorThrown);
								}
							});      
	      pos = 0;     
	    }
	   };
	  }
			
			component.webcam({
				width: 320,
				height: 240,
				mode: 'callback',
				swffile: PLUGIN_PATH + 'components/media/js/jquery.webcam/jscam_canvas_only.swf',
				onSave: function(data){
		    saveCB(data);
				},
				onCapture: function(){
					webcam.save();
				}
			});
			
		},
		/**
		 * Show the webcam
		 */
		webcamClick: function(component){
			var thisObj = this;
			var compID = component.attr('data-compid');
	
			component.click(function(){
	
				currWebcam = $('#XwebcamXobjectX');
				if( currWebcam.exists() ){
					currWebcam.parent().hide();
					currWebcam.remove();
				}
				
				var compID = $(this).attr('data-compid');
				var webcam = $('#sp_media_webcam-' + compID);
	
				//!To-do: Disable sorting
				webcam.show();
				thisObj.initWebcam(webcam, compID);
				
				return false;
			})
		},
		
		/**
		 * Cancel camera upload
		 */
		cancelCam: function(compID){
				var webcam = $('#sp_media_webcam-' + compID);
				webcam.find('#XwebcamXobjectX').remove();
				webcam.hide();			
		},
		/**
		 * Initializes HTML5 filedrop for the media component
		 * @param object component The media compoennt
		 */	
		initFileDrop: function(component, postID){
			var thisObj     = this;
			var compID      = component.attr('data-compid');
			var isGallery   = this.isGallery(compID);
			var fallback_id = 'sp_upload-' + compID;
			var queueFiles  = this.isGallery(compID) ? 1 : 0;
			
			if(postID == undefined){
				postID = jQuery('#postID').val();
			}
			
			component.filedrop({
				fallback_id: fallback_id,
				url: ajaxurl,
				paramname: 'sp_media_files',
				data: {
				    action  : 'mediaUploadAJAX',         
				    nonce   : spNonce,
				    compID  : compID,
				    postID  : postID
				},
				error: function(err, file) {
		    switch(err) {
		      case 'BrowserNotSupported':
		         	$('.sp_browse').show();
		          break;
		      case 'TooManyFiles':
												sp_postComponent.showError('Too many files! Please upload less');
		          break;
		      case 'FileTooLarge':
												sp_postComponent.showError(file.name + ' is too large!');
		          break;
		      default:
		      				sp_postComponent.showError(err);
		          break;
		    }
						$('#loadingGIF').remove(); 				
				},
				maxfiles: 1,
				queuefiles: queueFiles,
				maxfilesize: 32, // max file size in MBs
				uploadStarted: function(i, file, len){
					thisObj.loadingGif(isGallery, compID);
				},
				uploadFinished: function(i, file, response, time) {
					if(response){
							thisObj.insertThumb(response, isGallery, compID, $('#loadingGIF'));
					}
				}
			});
			
	 },
	 /**
	  * Insert Description (used only in single-media mode)
	  * After the thumbnail div
	  */
	 insertDesc: function(attachmentID, compID, thumbDiv, placeHolder){
			var thisObj = this;
			var descDiv = '';
			
			if(placeHolder == undefined){
				placeHolder = 'Webcam Snapshot!';
			}
			
			if(thumbDiv == undefined){
				thumbDiv = $('#media_thumb-' + attachmentID);
			}
			
			var descExists = $('#sp_attachments-' + compID).find('.sp_media_desc');
			descDiv += '<div id="sp_media_desc-' + attachmentID + '" class="sp_media_desc editable" data-compid="' + compID + '" attach-id="' + attachmentID + '">';
				descDiv += placeHolder;
			descDiv += '</div>';				
			if( !$(descExists).exists() ){
				thumbDiv.after( $(descDiv) );
			}else{
				$(descExists).replaceWith( $(descDiv) );
			}
			
			thisObj.initDescEditor($('#sp_media_desc-' + attachmentID));
	 },
	 
	 /**
	  * Initialize the description editor
	  * @param descElem jQuery <div> object of the description
	  */
	 initDescEditor: function(descElem){
	 	var thisObj = this;
			if(sp_postComponent){
		 		var elementID = $(descElem).attr('id');
					sp_postComponent.addNicEditor(elementID, false, thisObj.saveDescription,'Click to add a description');
	 	}
	 },
	 
		/**
		 * Saves a media component's description to the database.
		 * 
		 * @param string    content   The content to be saved
		 * @param string    contentID The DOMElem id of the content's container
		 * @param nicEditor instance  The editor instance
		 */	
		saveDescription: function(content, contentID, instance){
			var compID  = $('#' + contentID).attr('data-compid');
			var attachmentID  = $('#' + contentID).attr('attach-id');
			$.ajax({
				url				   : ajaxurl,
				type      : 'POST',
				data			   : {action: 'saveMediaDescAJAX', 
																	nonce: spNonce, 
																	compID: compID,
																	attachmentID: attachmentID,
																	desc: content},
				dataType  : 'json',
				success  : function(response, statusText, jqXHR){
						console.log(response);
				},
				error    : function(jqXHR, statusText, errorThrown){
						if(sp_postComponent)
							sp_postComponent.showError(errorThrown);
				}
			})
		},
	 
	 /**
	  * Loads a loading GIF prior to upload
	  */
	 loadingGif: function(isGallery, compID){
			console.log('loadingGIF isGallery: ' + isGallery)
			console.log('loadingGIF compID: ' + compID)		
			
			var attachments = $('#sp_attachments-' + compID);
			var thumbClass = isGallery ? 'gallery_thumb' : 'sp_media_thumb';
			var loadingGIF = '<div id="loadingGIF" class="' + thumbClass + '"><img src="' + IMAGE_PATH + '/loading.gif" /></div>';
			
			if(isGallery || (attachments.children().length == 0) ){
				$('#sp_attachments-' + compID).append(loadingGIF);
			}else{
				$('#sp_attachments-' + compID).find('.sp_media_thumb').replaceWith(loadingGIF);
			}
	 },
		/**
		 * Inserts a thumbnail into the DOM based off of info in response
		 * reponse.id -> Attachment ID of the attachment
		 * response.thumbURL -> Array containting [0] URL of the thumb attachment, [1] width, and [2] height
		 * response.fileURL  -> String of the direct URL to the file attachments
		 * response.caption  -> String caption of the thumbnail
		 *
		 * @param object response Valid JSON object
		 * @gallery bool whether to render in gallery mode or not
		 * @compID int the component ID
		 * @replaceMe $(elem) jQuery element to replace the thumb with
		 */
		insertThumb: function(response, isGallery, compID, replaceMe){
				var thisObj      = this;
				var thumbDiv 		  = '';
				var thumbClass   = isGallery ? 'gallery_thumb' : 'sp_media_thumb';
				var deleteButton = '<img src="' + IMAGE_PATH + '/no.png" id="deleteThumb-' + response.id + '" name="deleteThumb-' + response.id + '" data-attachid="' + response.id + '" data-compid="' + compID + '" class="sp_mediaDelete" title="Delete Attachment" alt="Delete Attachment" />';
				var captionEl    = '<p id="media_caption-' + response.id +'" class="sp_mediaCaption">' + response.caption + '</p>';
				
				thumbDiv += '<div id="media_thumb-' + response.id + '" data-compid="704" class="' + thumbClass + '">';
					thumbDiv += '<a id="thumb-' + response.id +'" href="' + response.fileURL + '" rel="lightbox[' + compID + ']" title="' + response.caption + '">';
						thumbDiv += '<img width="' + response.thumbURL[1] + '" height="' + response.thumbURL[2] + '" src="' + response.thumbURL[0] + '" class="attachment-100x100">';
					thumbDiv += '</a>';
					thumbDiv += captionEl;
					thumbDiv += deleteButton;
				thumbDiv += '</div>';
				
				replaceMe.replaceWith($(thumbDiv));
				thisObj.bindDelete($('#deleteThumb-' + response.id));
				thisObj.bindDeleteHover($('#media_thumb-' + response.id));
				
				if(!isGallery)
					thisObj.insertDesc(response.id, compID);
				
				return $(thumbDiv);
		},
		/**
		 * Shows the delete button when over over deleteElems
		 *
		 * @param HTMLElement Can be a class or some HTMLElement
		 */	
		bindDelete: function(deleteElems){
			deleteElems.click(function(){
				var attachmentID = $(this).attr('data-attachid');
				var compID = $(this).attr('data-compid');
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {action: 'mediaDeleteAttachmentAJAX', nonce: spNonce, attachmentID: attachmentID, compID: compID },
					dataType  : 'json',
					success  : function(response, statusText, jqXHR){
						$('#media_thumb-' + attachmentID).remove();
					},
					error    : function(jqXHR, statusText, errorThrown){
							if(sp_postComponent)
								sp_postComponent.showError(errorThrown);
					}
				})
			});
		},
		
		/**
		 * Shows the delete button when over over deleteElems. Looks for 
		 * the 'sp_mediaDelete' class (i.e. the button to show).
		 *
		 * @param HTMLElement Can be a class or some HTMLElement
		 */
		bindDeleteHover: function(deleteElems){
			deleteElems.hover(function(){
				$(this).find('.sp_mediaDelete').css('display', 'block');
			}, function(){
				$(this).find('.sp_mediaDelete').css('display', 'none'); 
			});
		},
		/**
	 	* Initializes a single component with filedrop
	 	*/	
		initComponent: function(component, postID, autoFocus){
			var webcamLink = $(component).find('.sp_webcam_click');
			this.webcamClick($(webcamLink));
			this.initFileDrop($(component));
		},
	
		/**
	 	* Initializes all dropfile elements with the filedrop plugin
	 	*/	
		init: function(){
			this.setTypeID();
			var thisObj = this;
			
			$('.sp_webcam_click').each(function(){
				thisObj.webcamClick($(this));
			})
			
			$('.sp_media').each(function(){
				thisObj.initFileDrop($(this));	
			});
			
			this.bindDelete($('.sp_mediaDelete'));
			$('.gallery_thumb').each(function(){
				thisObj.bindDeleteHover($(this));
			})
			
			$('.sp_media_desc').each(function(){
				thisObj.initDescEditor($(this));
			});
	
			$(".fancybox").fancybox({
				openEffect	 : 'none',
				closeEffect	: 'none',
				helpers : {
					media : {}
				}			
			});
			
		}
	}
	
	$(document).ready(function(){
		smartpost.sp_postMedia.init();
	});
	
})(jQuery);