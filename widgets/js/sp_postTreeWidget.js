/**
 * JS for the sp_postTreeWidget class
 *
 * Used on the front end for the SP Post Tree widget
 */
 
var sp_postTreeWidget = {
	relocatePost: function(parentID, postID, catID){
		$.ajax({
			url				  : ajaxurl,
			type     : 'POST',
			data			  : {action: 'relocatePostAJAX', 
														 nonce: spNonce, 
														 parentID: parentID, 
														 postID: postID,
														 catID : catID
														},
			dataType : 'json',
			success  : function(response, statusText, jqXHR){
					console.log(response);
			},
			error    : function(jqXHR, statusText, errorThrown){
						sp_postComponent.showError(errorThrown);
			},
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
    dnd: {
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
	 * Initializes a simple dynaTree instance using listContainer as its 
	 * HTML Element. listContainer should be a <div> or some HTML
	 * container that contains a <ul> or <ol> list.
	 *
	 * @param DOMElem listContainer - A <ul> or <ol> container where the list exists
	 * @param bool linkToPosts  - Tree will link to posts if True, does nothing otherwise
	 * @param bool expandToPost - Expands tree to the current post being displayed
	 */
	initDynaTree: function(listContainer, linkToPosts, expandToPost){
		var thisObj = this;
		listContainer.dynatree({
			imagePath: "",
   onActivate: function(node) {
    // Use <a> href and target attributes to load the content:
    if( node.data.href && linkToPosts){
      window.open(node.data.href, node.data.target);
    }
   }
		});
	},
	
	init: function(){
		var tree = $('#sp_postTree');
		var activePost = $('#postID').val();
		this.initDynaTree(tree, true);
		if(activePost && tree.exists()){
			var node = tree.dynatree("getTree").getNodeByKey("treePost-" + activePost);
			if(node){
				node.activateSilently();
				node.expand(true);
			}
		}
	}
}

$(document).ready(function(){
		sp_postTreeWidget.init();
});