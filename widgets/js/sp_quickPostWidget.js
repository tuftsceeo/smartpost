/**
 * JS sp_quickPostWidget object. Used for the
 * SP QuickPost widget in the front-end
 */
(function($){
    sp_widgets.sp_quickPostWidget = {

        /**
         * Toggles the quickpost form and focuses on the title input
         */
        toggleForm: function(){
            var self = this;

            var newPostHandler = function(){
                var clicked = $(this).data('clicked') || 0;

                if( !clicked ){
                    var catID = $('#sp_selectCat').exists() ? $('#sp_selectCat').val() : $('#sp_addPostButton').attr('data-catID');
                    self.newSPPost(catID);

                    //Add a dialogue in case window is closed
                    window.onbeforeunload = function (e) {
                      e = e || window.event;
                      if (e) {
                        e.returnValue = 'There is unsaved data, are you sure you want leave the page?';
                      }
                      return 'There is unsaved data, are you sure you want leave the page?';
                    };

                    $(this).data('clicked', true);
                }

                $('#new_sp_post_title').focus();
                var category = $('#sp_selectCat :selected').text();
                $(this).replaceWith(category);

            }//endNewPostHandler()

            if($('#sp_selectCat').exists()){
                $('#sp_selectCat').change(newPostHandler);
            }else{
                $('#sp_addPostButton').bind("click", newPostHandler);
            }
        },

        /**
         * Creates a draft post whenever "Add a new post" is clicked
         */
        newSPPost: function(catID){

            //If postID input exists, we're inside a post
            var parentID = $('#postID').exists() ? $('#postID').val() : 0;
            var sp_qp_stack = $('#sp_qp_stack');

            $.ajax({
                url: SP_AJAX_URL,
                type: 'POST',
                data: { action: 'newSPDraftAJAX', nonce: SP_NONCE, catID: catID, parentID: parentID },
                dataType: 'html',
                success: function( response, statusText, jqXHR ){
                    // Add the quickpost response to the DOM
                    sp_qp_stack.html( response );

                    // Get the new postID
                    var postID = sp_qp_stack.find('#sp_qpPostID').val();
                    var componentStack = sp_qp_stack.find('#spComponents');

                    // Add the new components and initialize them
                    if(smartpost.sp_postComponent){
                        var components = sp_qp_stack.find('#spComponents').children();
                        $(components).each(function() {
                            //Initialize each component
                            smartpost.sp_postComponent.initializeComponent($(this), undefined, postID, false);
                        });
                    }else{
                        smartpost.sp_postComponent.showError('Error: Could not find the smartpost.sp_postComponent object!');
                    }

                    // Initialize new component buttons
                    $('.sp_qp_component').click(function(){
                        var catCompID = $(this).attr("data-compid");
                        var typeID    = $(this).attr("data-typeid");
                        smartpost.sp_postComponent.addNewComponent(catCompID, typeID, postID, componentStack);
                    });

                    // Make them sortable
                    smartpost.sp_postComponent.makeSortable(componentStack, postID);

                    $('#sp_quickpost_form').slideDown();
                },
                error: function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                }
            });
        },

        /**
         * Publishes the quickpost
         */
        publishSPPost: function(parentID){
            var postID = $('#sp_qpPostID').val();

            if( $('#new_sp_post_title').exists() ){
                var title  = $('#new_sp_post_title').val();
            }else{
                var title = $('.sp_postTitle').text();
            }

            if(parentID == undefined){
                parentID = 0;
            }

            if( (title.length < 5) ){

                alert('Please type in a valid title! Titles has to be at least 5 characters long.');
                title.focus().click();

                if( $('#sp_qp_responseDialog').exists() ){
                    $('#sp_qp_responseDialog').dialog('close'); //Close the dialog if it's open
                }

            }else{
                window.onbeforeunload=null;
                if(postID){
                    $.ajax({
                        url: SP_AJAX_URL,
                        type: 'POST',
                        data: { action: 'publishPostAJAX', nonce: SP_NONCE, ID: postID, post_title: title, post_parent: parentID },
                        success: function(response, statusText, jqXHR){
                            //Refresh the page
                            window.location.reload(true);
                        },
                        error: function(jqXHR, statusText, errorThrown){
                            if(smartpost.sp_postComponent)
                                    smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                        }
                    });
                }

            }
        },

        /**
         * Cancels the quickpost
         */
        cancelSPPost: function(){
            var postID      = $('#sp_qpPostID').val();
            var redirectURL = $('#sp_spDeleteRedirect').val();
            window.onbeforeunload=null;
            var cancel = confirm("Are you sure you want to permanently delete this draft?");
            if(postID && cancel){
                $.ajax({
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: { action: 'deleteQPPostAJAX', nonce: SP_NONCE, ID: postID },
                    success: function(response, statusText, jqXHR){

                        //Refresh the page
                        if(redirectURL == undefined){
                            window.location.reload(true);
                        }else{
                            window.location.href = redirectURL;
                        }
                    },
                    error: function(jqXHR, statusText, errroThrown){
                        if(smartpost.sp_postComponent)
                                smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                    }
                });
            }
        },

        /**
         * Used in loop templates (recommended on front page) for
         * when the user selects the option to create a post as a response post. Loads
         * a jQuery UI dialog with a post tree that allows the user to select which post
         * to respond to.
         */
        loadResponsePosts: function(){
            var thisObj = this;
            var postID  = $('#sp_qpPostID').val();
            $.ajax({
                url: SP_AJAX_URL,
                type: 'POST',
                data: { action: 'loadResponsePostsAJAX', nonce: SP_NONCE, ID: postID },
                success: function(response, statusText, jqXHR){
                    $('#sp_qp_responsePosts').html(response);
                    var postResponseTree = $('#sp_qp_responsePosts').find('#sp_postTree');

                    if(sp_widgets.sp_postTreeWidget)
                        sp_widgets.sp_postTreeWidget.initDynaTree(postResponseTree, false);

                    $('#sp_qp_responseDialog').dialog({
                        modal: true,
                        minWidth: 600,
                        maxHeight: 400,
                        position: ['center', 'center'],
                        title: 'Select a post to respond to:',
                        buttons: {
                            Ok: function(){
                                var responseNode = $(postResponseTree).dynatree("getActiveNode");
                                if(responseNode.data.postID){
                                    thisObj.publishSPPost(responseNode.data.postID)
                                }else{
                                    alert('Please select a post!');
                                }
                            }
                        }
                    })
                },
                error: function(jqXHR, statusText, errroThrown){
                    if(smartpost.sp_postComponent)
                            smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                }
            });
        },
        init: function(){
            var thisObj = this;
            $('#sp_qp_response').click(function(){ thisObj.loadResponsePosts() });
            $('#sp_publish_post').click(function(){ thisObj.publishSPPost();	});
            $('#sp_cancel_draft').click(function(){	thisObj.cancelSPPost(); });
            this.toggleForm();
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_quickPostWidget.init();
    });
})(jQuery);