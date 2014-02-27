/**
 * JS for the sp_postWidget class. Used in
 * front-end posts for post manipulation and
 * options.
 */
(function($){
    sp_widgets.sp_postWidget = {

        /**
         * Makes the SP Widget draggable
         */
        makeWidgetDraggable: function(draggableContainer, sortableElemID){
            $('.catComponentWidget').draggable({
                addClasses: false,
                helper: 'clone',
                revert: 'invalid',
                connectToSortable: sortableElemID
            });
        },

        deleteButtonClick: function(deleteButton){
            deleteButton.click(function(){
                var deletePost = confirm('Are you sure you want to delete this post and all of it\'s content?');
                if(deletePost){
                    var postID = $(this).data('postid');
                    smartpost.ajaxcall(
                        {
                            action: 'deletePostAJAX',
                            postID: postID
                        },
                        function(response){
                            // console.log(response);
                            window.location = $(' #sp-delete-redirect ').val();
                        },
                        function(response){
                            smartpost.sp_postComponent.showError( response );
                        },
                        'json'
                    );
                }
            });
        },
        /**
         * Initializes the postWidget
         */
        init: function(){
            var self = this;

            self.deleteButtonClick( $('.sp-delete-post') );

            $( '.sp-widget-post-settings-draggable-comps' ).each(function(){
                self.makeWidgetDraggable( $(this), '.sortableSPComponents' );
            });
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_postWidget.init();
    });
})(jQuery);