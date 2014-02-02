/**
 * JS for the sp_postTreeWidget class
 *
 * Used on the front end for the SP Post Tree widget
 */
(function($){
    sp_widgets.sp_postTreeWidget = {
        relocatePost: function(parentID, postID, catID){
            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {
                    action: 'relocatePostAJAX',
                    nonce: SP_NONCE,
                    parentID: parentID,
                    postID: postID,
                    catID : catID
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    smartpost.sp_postComponent.showError(errorThrown);
                }
            });
        },
        /**
         * Initializes a dynaTree allowing for re-categorization of posts
         * via drag n' drop functionality.
         */
        initRelocationTree: function(listContainer){
            var thisObj = this;
            listContainer.dynatree({
                imagePath: "",
                dnd:
                {
                    onDragStart: function(node) {
                        if(node.data.cat || !node.data.owner){
                            return false;
                        }else{
                            return true;
                        }
                    },
                    autoExpandMS: 100,
                    preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
                    onDragEnter: function(node, sourceNode) {
                        return true;
                    },
                    onDragOver: function(node, sourceNode, hitMode) {
                        // Prevent dropping a parent below it's own child
                        if(node.isDescendantOf(sourceNode) ){
                            return false;
                        }
                    },
                    onDrop: function(node, sourceNode, hitMode, ui, draggable) {

                        if( (node.data.catID != undefined) && (sourceNode.parent.data.catID != node.data.catID) ){
                            var convert = confirm("Are you sure you want to convert your post's category from '"
                                + sourceNode.parent.data.title + "' to '" + node.data.title + "'?");
                            if( !convert )
                                return false;

                            var catID = node.data.catID;
                        }else{
                            var catID = 0;
                        }

                        //If catID is defined, that skip the category node and get the post parent node's ID, otherwise
                        //If a catID isn't defined, then we know we are just dropping the node under a post.
                        var parentID = (catID > 0) ? node.parent.data.postID : node.data.postID;

                        //Otherwise we are making the post a root post
                        var parentID = (parentID > 0) ? parentID : 0;


                        //Relocate the post
                        var postID   = sourceNode.data.postID;
                        thisObj.relocatePost(parentID, postID, catID);

                        sourceNode.move(node, hitMode);
                        sourceNode.expand(true); // expand the drop target
                    }
                },
                onActivate: function(node) {
                    // Use <a> href and target attributes to load the content:
                    if( node.data.href ){
                        window.open(node.data.href, node.data.target);
                    }
                }
            });
        },
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

        /**
         * Expands/collapses all the nodes of a given dynatree.
         */
        dynaTreeExpandAll: function(){
            /* Click handler for expand/collapse all
             $( '#expandAll' ).click(function(){
             sp_catTree.dynatree("getRoot").visit(function(node){
             if( !sp_catTree.expanded ){
             node.expand(true);
             }else{
             node.expand(false);
             }
             });
             sp_catTree.expanded = !sp_catTree.expanded;
             });
             */
        },

        init: function(){
            var self = this;
            $( '.sp_catTree').each(function(){
                self.initDynaTree( $(this) );
            });
        }
    };

    $(document).ready(function(){
        sp_widgets.sp_postTreeWidget.init();
    });
})(jQuery);