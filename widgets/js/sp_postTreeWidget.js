/**
 * JS for the sp_postTreeWidget class
 *
 * Used on the front end for the SP Post Tree widget
 */
(function($){
    sp_widgets.sp_postTreeWidget = {
        /**
         * Initializes a simple dynaTree instance.
         * @param spCatTree - Container for the tree
         */
        initDynaTree: function( sp_catTree ){
            var widgetId = sp_catTree.data('widgetid').split('-')[1];
            var activePost = $('#sp_postTreePostID').val();
            var activeCat = $('#sp_postTreeCatID').val();

            // AJAX handler to load in all the nodes dynamically
            sp_catTree.dynatree({
                initAjax: {
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        nonce  : SP_NONCE,
                        action : 'getCatTreeAJAX',
                        widgetId: widgetId
                    }
                },
                generateIds: true,
                persist: true,
                clickFolderMode: 1,
                onActivate: function (node) {
                    window.open(node.data.href, node.data.target);
                },
                onPostInit: function(){
                    if(activePost){
                        var node = this.getNodeByKey( "post-" + activePost )
                    }else{
                        var node = this.getNodeByKey( "cat-" + activeCat )
                    }

                    if(node){
                        node.activateSilently();
                        node.expand(true);
                    }
                },
                debugLevel: 0
            });
            sp_catTree.dynatree("getTree").renderInvisibleNodes();
        },

        init: function(){
            var self = this;
            $( '.sp_catTree' ).each(function(){
                self.initDynaTree( $(this) );
            });
        }
    };

    $(document).ready(function(){
        sp_widgets.sp_postTreeWidget.init();
    });
})(jQuery);