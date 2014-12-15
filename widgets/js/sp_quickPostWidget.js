/**
 * JS sp_quickPostWidget object. Used for the
 * SP QuickPost widget in the front-end
 */
(function($){
    sp_widgets.sp_quickPostWidget = {

        /**
         * Creates a draft post whenever "Add a new post" is clicked
         * @param catID - the category ID
         * @param widgetID - the widget ID
         * @param slideDown - whether to slide the form down or to just show it
         */
        loadSmartPostTemplate: function(catID, widgetID, slideDown){

            var sp_qp_stack = $( '#sp-qp-comp-stack-' + widgetID );
            var sp_origin_id = $( '#sp-post-origin-id-' + widgetID).val();

            $.ajax({
                url: SP_AJAX_URL,
                type: 'POST',
                data: {
                    action: 'newSPDraftAJAX',
                    nonce: SP_NONCE,
                    catID: catID,
                    widgetID: widgetID,
                    sp_origin_id: sp_origin_id
                },
                dataType: 'html',
                success: function( response, statusText, jqXHR ){
                    // Add the quickpost response to the DOM
                    sp_qp_stack.html( response );

                    // Get the new postID
                    var postID = sp_qp_stack.find('#sp-qp-post-id-' + widgetID).val();
                    var componentStack = sp_qp_stack.find('#sp-components-' + widgetID);

                    // Add the new components and initialize them
                    if(smartpost.sp_postComponent){
                        var components = sp_qp_stack.find('#sp-components-' + widgetID).children();
                        $(components).each(function() {
                            //Initialize each component
                            smartpost.sp_postComponent.initializeComponent($(this), undefined, postID, false);
                        });
                    }else{
                        smartpost.sp_postComponent.showError('Error: Could not find the smartpost.sp_postComponent object!');
                    }

                    // Initialize new component buttons
                    $('.sp-qp-component').click(function(){
                        var catCompID = $(this).attr("data-compid");
                        var typeID    = $(this).attr("data-typeid");
                        smartpost.sp_postComponent.addNewComponent(catCompID, typeID, postID, componentStack, null);
                    });

                    // Make them sortable
                    smartpost.sp_postComponent.makeSortable(componentStack, postID);

                    if( slideDown == undefined ){
                       slideDown = true;
                    }

                    if( slideDown ){
                        $('#sp-quickpost-form-' + widgetID).slideDown();
                    }else{
                        $('#sp-quickpost-form-' + widgetID).show();
                    }
                },
                error: function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent){
                        smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                    }
                }
            });
        },

        /**
         * Publishes the quickpost
         */
        publishSPPost: function( widgetID, redirect_url, submit_msg ){
            var postID = $( '#sp-qp-post-id-' + widgetID ).val();
            var title = $( '#new-sp-post-title-' + widgetID ).val();

            if( (title.length < 2) ){
                alert('Please type in a valid title! Titles has to be at least 2 characters long.');
                title.focus().click();
            }else{
                window.onbeforeunload = null;
                if(postID){
                    $.ajax({
                        url: SP_AJAX_URL,
                        type: 'POST',
                        data: {
                            action: 'publishPostAJAX',
                            nonce: SP_NONCE,
                            ID: postID,
                            post_title: title
                        },
                        dataType: 'json',
                        success: function(response, statusText, jqXHR){
                            console.log( submit_msg );
                            if( submit_msg ){
                                $('#sp-quickpost-form-' + widgetID).slideUp(600, function(){
                                    $(this).replaceWith( submit_msg );
                                });
                            }

                            if( redirect_url ){
                                if( redirect_url == '#' ){
                                    return;
                                }else if( redirect_url == 'reload' ){
                                    location.reload( true );
                                }else{
                                    window.location.replace( redirect_url );
                                }

                            }else if( response.permalink ){
                                window.location.replace( response.permalink );
                            }else{
                                window.location.reload( true );
                            }
                        },
                        error: function(jqXHR, statusText, errorThrown){
                            if(smartpost.sp_postComponent){
                                smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                            }
                        }
                    });
                }

            }
        },

        /**
         * Cancels the quickpost
         */
        cancelSPPost: function( widgetID ){
            var postID = $('#sp-qp-post-id-' + widgetID).val();
            window.onbeforeunload = null;
            var cancel = confirm("Are you sure you want to permanently delete this draft?");
            if(postID && cancel){
                $.ajax({
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        action: 'deleteQPPostAJAX',
                        nonce: SP_NONCE,
                        ID: postID
                    },
                    success: function(response, statusText, jqXHR){
                        $( '#sp-quickpost-form-' + widgetID).slideUp( 600, function(){
                            $( '#sp-qp-comp-stack-' + widgetID).html(''); // reset components
                            $( '#new-sp-post-title-' + widgetID ).val(''); // reset post title
                            $( '#sp-add-post-' + widgetID ).show(); // show drop-down if that's where we came from
                            $( '#sp-qp-new-post-' + widgetID).show(); // show button if that's where we came from
                        });
                    },
                    error: function(jqXHR, statusText, errroThrown){
                        if(smartpost.sp_postComponent){
                            smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                        }
                    }
                });
            }
        },
        init: function(){
            var self = this;

            // Submit button click handler
            $('.sp-qp-new-post').each(function(){
                var thisButton = this;
                var showForm = Boolean( $(this).data( 'showform' ) );

                var initForm = function(){
                    var catID = $(thisButton).data('catid');
                    var widgetID = $(thisButton).data('widgetid');
                    self.loadSmartPostTemplate( catID, widgetID, showForm );
                    $(thisButton).hide();
                };

                // If showform shortcode is enabled, show the form by default
                if( showForm ){
                    initForm();
                }else{
                    $(this).click(function(){
                        initForm();
                    });
                }
            });

            // Drop drown change handler
            $('.sp-qp-select-cat').change(function(){
                var widgetID = $(this).data('widgetid');
                var catID = $(this).val();
                self.loadSmartPostTemplate( catID, widgetID );
                $('#sp-add-post-' + widgetID).hide();
            });

            // Publish post click handler
            $( '.sp-qp-publish-post' ).click(function(){
                var widgetID = $(this).data('widgetid');
                var redirect_url = $(this).data('redirecturl');
                var submit_msg = $(this).data('submitmsg');
                self.publishSPPost( widgetID, redirect_url, submit_msg );
            });

            // Cancel post click handler
            $( '.sp-qp-cancel-draft' ).click(function(){
                var widgetID = $(this).data('widgetid');
                self.cancelSPPost( widgetID );
            });
        }
    };

    $(document).ready(function(){
        sp_widgets.sp_quickPostWidget.init();
    });
})(jQuery);