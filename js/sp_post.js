/*
 * JS for sp_postComponent class
 * Used in front-end posts
 */
(function($){
    smartpost.sp_post = {

        /**
         * Save the title of a component
         */
        savePostTitle: function(title, postID){

            if( title.length < 5 ){
                if(sp_postComponent){
                    sp_postComponent.showError('Title field must be at least 5 characters long!');
                    return;
                }
            }

            if( postID == undefined ){
                if(sp_postComponent){
                    sp_postComponent.showError('Could not find the postID needed to update the title!');
                    return;
                }
            }

            $.ajax({
                url		 : ajaxurl,
                type     : 'POST',
                data	 : {action: 'savePostTitleAJAX', nonce: SP_NONCE, postID: postID, post_title: title},
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    if(sp_postComponent)
                        console.log(jqXHR);
                    console.log(statusText);
                    console.log(errorThrown);
                    sp_postComponent.showError(errorThrown + statusText + jqXHR);
                }
            })
        },

        /**
         * Make component titles editable
         */
        editablePostTitle: function(titleElems){
            var thisObj = this;

            if( titleElems == undefined){
                titleElems = $('.sp_postTitle');
            }

            titleElems.editable(function(value, settings){
                    var postID = $('#postID').val();
                    thisObj.savePostTitle(value, postID);
                    return value;
                },
                {
                    placeholder: 'Click to add a title',
                    onblur     : 'submit',
                    cssclass   : 'sp_compTitleEditable',
                    maxlength  : 65,
                    event      : 'click'
                })
        },

        /**
         * Initializes the post with any necessary init methods
         *
         * @uses editablePostTitle()
         */
        init: function(){
            this.editablePostTitle($('.sp_postTitle'));
        }
    }

    $(document).ready(function(){
        smartpost.sp_post.init();
    });

})(jQuery);