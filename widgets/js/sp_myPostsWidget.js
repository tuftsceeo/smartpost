/**
 * JS for the sp_myPostsWidget class. Used in
 * front-end posts for handling widget events.
 */
(function($){
    sp_widgets.sp_myPostsWidget = {
        deletePost: function(postID, postLbl){
            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data  : {
                    action: 'deletePostAJAX',
                    nonce: SP_NONCE,
                    postID: postID
                },
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    if(postLbl.attr('data-status') == 'published'){
                        var pCount = parseInt( $('#publishCount').text() );
                        pCount = pCount > 0 ? pCount - 1 : 0;
                        $('#publishCount').html(pCount);
                    }

                    if(postLbl.attr('data-status') == 'draft'){
                        var dCount = parseInt( $('#draftCount').text() );
                        dCount = dCount > 0 ? dCount - 1 : 0;
                        $('#draftCount').html(dCount);
                    }

                    postLbl.remove();

                    var articlePost = $('#post-' + postID);
                    if(articlePost){
                        articlePost.remove();
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },
        deleteButtonClick: function(deleteButton){
            var self = this;
            deleteButton.click(function(){
                var deletePost = confirm('Are you sure you want to delete this post and all of it\'s content?');
                if(deletePost){
                    var postID = $(this).attr('data-postid');
                    var postLbl = $(this).parent();
                    $(this).parent().html('<img src="' + SP_IMAGE_PATH + '/loading.gif" /> Deleting post...');
                    self.deletePost(postID, postLbl);
                }
            })
        },
        /**
         * Enables hover function for myPost post
         * elements (in this case <li> elems) to show
         * delete <img> / button.
         */
        initMyPostHover: function(postElem){
            postElem.hover(
                function(){	$(this).find('img').show();	},
                function(){	$(this).find('img').hide();	}
            );
        },
        /**
         * Takes in jQuery object and calls jQuery-UI tabs() function on it.
         * @see http://jqueryui.com/demos/tabs/
         */
        initTabs: function(tabElems){
            tabElems.tabs();
        },
        init: function(){
            this.initTabs($('#myPosts'));
            this.initMyPostHover($('.sp_myPost'));
            this.deleteButtonClick($('.sp_deletePost'));
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_myPostsWidget.init();
    });
})(jQuery);