/*
 * JS for sp_admin class
 * Used in dashboard/admin page
 */

(function($) {
    spAdmin.adminpage = {

        /**
         * Displays errors to the user.
         * @param errorDivID - The HTML DOM elm's ID where the error will be displayed. Default: #setting_errors
         * @param errorText  - The error message to display (can be HTML)
         */
        showError: function(errorText, errorDivID){
            if(errorDivID == undefined)
                errorDivID = '#setting_errors';

            $(errorDivID).show().html(
                '<h4> Error: ' +	errorText + '<h4>').attr("class", "error");
        },
        /**
         * Saves the component order for a category template on the admin page.
         * @param components - An array of the components
         */
        saveCompOrder: function(components){
            if(components.length > 0){
                var thisObj = this;
                var compOrder = new Array();
                var catID     = $('#catID').val();

                $(components).each(function(index, value){
                    compOrder[index] = value.split('-')[1];
                });

                $.ajax({
                    url	 : SP_AJAX_URL,
                    type : 'POST',
                    data : {
                        action    : 'setCompOrderAJAX',
                        nonce     : SP_NONCE,
                        compOrder : compOrder,
                        catID     : catID
                    },
                    dataType : 'json',
                    success  : function(response){
                        console.log(response);
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        thisObj.showError(errorThrown, null);
                    }
                })
            }
        },

        /**
         * Turns the elements in draggableDiv draggable and connects the
         * draggable elements with the sortable elements of sortableDiv.
         * @param dragHelper
         * @param draggableDiv
         * @param sortableDiv
         */
        makeCompDivsDraggable: function(dragHelper, draggableDiv, sortableDiv){
          if(draggableDiv == undefined)
            draggableDiv = $('.catCompDraggable');

          if(sortableDiv == undefined)
            sortableDiv = $('#normal-sortables');

          if(draggableDiv.exists()){
              draggableDiv.draggable({
                  addClasses: true,
                  helper: dragHelper,
                  revert: 'invalid',
                  connectToSortable: sortableDiv
              })
          }
        },

        /**
         * Calls the AJAX to delete a component and deletes the corresponding
         * HTML for the component.
         * @param deleteButton
         */
        handleDeleteComp: function(deleteButton){

            deleteButton.click(function(){
                var divID = $(this).attr("comp-id");
                var compID = divID.split('-')[1];
                var cl = function(){
                   $('#' + divID ).remove();
                };
                spAdmin.sp_catComponent.deleteComponent(compID, cl);
            })
        },

        /**
         * Reveals the delete button when hovering over hoverElem.
         * @param hoverElem
         */
        enableDeleteHover: function(hoverElem){
            hoverElem.hover(
                function(){$(this).find('.delComp').css('visibility', 'visible')},
                function(){$(this).find('.delComp').css('visibility', 'hidden')}
            )
        },

        /**
         * Category Response Update
         */
        submitResponseCatForm: function(){
            var thisObj = this;
            $('#responseCatsForm').submit(function(){
                $(this).ajaxSubmit({
                    url	     : SP_ADMIN_URL,
                    type     : 'POST',
                    data	 : {action: 'responseCatAJAX', nonce: SP_NONCE},
                    dataType : 'json',
                    success	 : function(response){
                        if(response.error){
                            thisObj.showError(response.error, null);
                        }else{
                            if( !$('#successCatUpdate').exists() ){
                                var success = $('<p id="successCatUpdate"> Response Categories saved! </p>');
                                $('#submitResponseCats').after(success);
                                success.delay(3000).fadeOut();
                                success.delay(3000, function(){ $(this).remove() });
                            }
                        }
                    },
                    error	 : function(data){
                        thisObj.showError(data.statusText, null);
                    }
                }); //end ajaxSubmit
                return false;
            }); //end submit
        },

        /**
         * Initializes a dynaTree for template management.
         * @param sp_catTree - The DOM element consisting to categories and components.
         */
        initComponentTree: function(sp_catTree){
            sp_catTree.dynatree({
                imagePath: "",
                generateIds: true,
                onActivate: function (node) {
                    if(node.data.isFolder)
                        window.open(node.data.href, node.data.target);
                },
                debugLevel: 0
            });
            sp_catTree.dynatree("getTree").renderInvisibleNodes();
        },

        /**
         * Used inside the sortable component div, replaces a dropped
         * item with its corresponding component(s).
         * @param e
         * @param ui
         */
        replaceDroppedItem: function(e, ui){
            var self  = this;
            var catID = $('#catID').val();
            var cl = function(newComponent){
                var component = $(newComponent)
                ui.item.replaceWith(component);

                //Enable delete event
                self.handleDeleteComp(component.find('.delComp'));
                self.enableDeleteHover(component);

                //Enable required and default checkboxes
                if(spAdmin.sp_catComponent)
                    spAdmin.sp_catComponent.disableDefault(component.find('.requiredAndDefault input'));

                //Enable postbox open/close
                //Enable component rename
                //Enable icon drag n' drop

                self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
            };

            if ( ui.item.hasClass('catCompDraggable') ){
                var typeID = ui.item.attr("type-id").split("-")[1];
                spAdmin.sp_catComponent.addComponent(catID, typeID, cl);
            }else if( ui.item.hasClass('dynatree-node') ){
                var node  = $.ui.dynatree.getNode(ui.item.context);
                var compID = node.data.compID;
                if(node.data.compID){
                    spAdmin.sp_catComponent.copyComponent(compID, catID, cl);
                }

                if(node.data.catID){
                    console.log('dropped category:' + catID);
                }
            }else{
                self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
            }
        },

        /**
         * Initializes the spAdmin object with click handlers and variables
         * necessary for initialization.
         */
        init: function(){
            var self = this;
            var sortableDiv = $( ".meta-box-sortables" );
            var sp_catTree  = $('#sp_catTree');

            //Initialize a dynatree instance for the SP category tree.
            this.initComponentTree(sp_catTree);

            //Make the component widgets draggable
            this.makeCompDivsDraggable('clone', $('.dynatree-node'), null);
            this.makeCompDivsDraggable('clone', null, null);

            //Re-define sortable behavior on the admin page.
            sortableDiv.sortable( "option", "axis", "y" );
            sortableDiv.sortable({
                axis: "y",
                stop: function(e, ui){
                    self.replaceDroppedItem(e, ui);
                },
                placeholder: {
                    element: function(currentItem) {
                        return $('<div class="sortable-placeholder" style="height:32px;"></div>')[0];
                    },
                    update: function(container, p) {}
                }
            });

            //Enable component deletion
            this.handleDeleteComp($('.delComp'));
            this.enableDeleteHover($('.postbox'));

            //Limit click event only to hndl class
            $('.postbox h3').unbind('click.postboxes');

        }
    };

    //Initialize admin page behavior.
    $(document).ready(function(){
        spAdmin.adminpage.init();
    });

})(jQuery);