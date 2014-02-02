/**
 * Handles dashboard/admin JS in the widget area.
 * Created by ryagudin on 2/1/14.
 */
/**
 * Initializes all the DynaTree on the back-end
 */
(function($){
    sp_widgets.sp_postTreeWidgetAdmin = {

        /**
         * AJAX call to set (hide/show) tree items
         * @param items - comma separated list of category IDs to show
         * @param widgetid -
         */
        setTreeDisplay: function(items, widgetid){
            //console.log(items);
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'setTreeDisplayAJAX',
                    nonce : SP_NONCE,
                    displayItems: items,
                    widgetid: widgetid
                },
                dataType  : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error : function(jqXHR, statusText, errorThrown){
                    console.log( errorThrown );
                }
            });
        },

        /**
         * Initializes the tree in the dashboard widget area
         * @param sp_catTree
         * @param widgetId
         */
        initWidgetAdminTree: function(sp_catTree, widgetId){
            var self = this;
            console.log(widgetId);
            // AJAX handler to load in all the nodes dynamically
            sp_catTree.dynatree({
                checkbox: true,
                selectMode: 2,
                initAjax: {
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        nonce  : SP_NONCE,
                        action : 'getCatAdminTreeAJAX',
                        widgetId: widgetId
                    }
                },
                onSelect: function(select, node) {
                    // Get a list of all selected nodes, and convert to a key array:
                    var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
                        return node.data.key;
                    });
                    //console.log(selKeys);
                    //self.setTreeDisplay(selKeys.join(", "))
                    self.setTreeDisplay(selKeys, widgetId);
                },
                generateIds: true,
                persist: true,
                clickFolderMode: 1,
                debugLevel: 0
            });
            sp_catTree.dynatree("getTree").renderInvisibleNodes();
        },
        init: function(){
            var self = this;
            $( '.sp-widget-cat-tree' ).each(function(){

                // Get the right widgetId;
                var widgetId = $(this).data( 'widgetid').split('-')[1];
                if( widgetId !== '__i__' )
                    self.initWidgetAdminTree( $(this), widgetId );

                // Remove the save button, everything is done asynchronously ...
                $('#widget-' + $(this).data( 'widgetid') + '-savewidget').remove();
            });
        }
    }
    $(document).ready(function(){
        sp_widgets.sp_postTreeWidgetAdmin.init();
    });
})(jQuery);
