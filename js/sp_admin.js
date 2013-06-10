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
         * @param draggableDiv
         * @param sortableDiv
         */
        makeCompDivsDraggable: function(draggableDiv, sortableDiv){
          if(draggableDiv == undefined)
            draggableDiv = $('.catCompDraggable');

          if(sortableDiv == undefined)
            sortableDiv = $('#normal-sortables');

          if(draggableDiv.exists()){
              draggableDiv.draggable({
                  addClasses: false,
                  helper: 'clone',
                  revert: 'invalid',
                  connectToSortable: sortableDiv
              })
          }
        },

        /**
         * Replaces the dropped component widget with a category
         * component interface.
         * @param sortableDiv

        handleDroppedComp: function(sortableDiv){
            var thisObj = this;
            sortableDiv.on(
                "sortstop",
                function(event, ui){
                    if ( ui.item.hasClass('catCompDraggable') ){
                        var typeID = ui.item.attr("type-id").split("-")[1];
                        var catID  = $('#catID').val();
                        var cl = function(newComponent){
                            sortableDiv.find('.catCompDraggable').replaceWith(newComponent);
                            var compOrder = sortableDiv.sortable('toArray');
                            thisObj.saveCompOrder( compOrder );
                        }
                        spAdmin.sp_catComponent.addComponent(catID, typeID, cl);
                    }else{
                        var compOrder = sortableDiv.sortable('toArray');
                        thisObj.saveCompOrder( compOrder );
                    }
                }
            );
        },
         */

        /**
         * Calls the AJAX to delete a component and deletes the corresponding
         * HTML for the component.
         * @param deleteButton
         * @param cl - callback after the delete operation has completed.
         */
        handleDeleteComp: function(deleteButton, cl){
            var thisObj = this;
            deleteButton.click(function(){
                var compDivID = $(this).attr("comp-id");
                var compid = compDivID.split("-")[1];
                $('#' + compDivID).remove();
                spAdmin.sp_catComponent.deleteComponent(compid, cl);
            })
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
         * Initializes the spAdmin object with click handlers and variables
         * necessary for initialization.
         */
        init: function(){
            var self = this;
            var sortableDiv = $( ".meta-box-sortables" );

            //Initialize a dynatree instance for the SP category tree.
            $('#sp_catTree').dynatree({
                imagePath: "",
                onActivate: function (node) {
                    window.open(node.data.href, node.data.target);
                },
                debugLevel: 0
            });

            //Make the component widgets draggable
            this.makeCompDivsDraggable(null, null);

            //Enable component deletion
            this.handleDeleteComp(
                $('.delComp'),
                function(){self.saveCompOrder( sortableDiv.sortable( 'toArray') )}
            );

            //Re-define sortable behavior on the admin page.

            sortableDiv.sortable( "option", "axis", "y" );
            sortableDiv.sortable({
                axis: "y",
                stop: function(e, ui){
                    if ( ui.item.hasClass('catCompDraggable') ){
                        var typeID = ui.item.attr("type-id").split("-")[1];
                        var catID  = $('#catID').val();
                        var cl = function(newComponent){
                            $(newComponent).unwrap();
                            ui.item.replaceWith(newComponent);
                            self.saveCompOrder( sortableDiv.sortable( 'toArray' ) );
                        }
                        spAdmin.sp_catComponent.addComponent(catID, typeID, cl);
                    }else{
                        self.saveCompOrder( sortableDiv.sortable( 'toArray' ) );
                    }
                }
            })

            //Reveal delete button
            $('.postbox').hover(function(){
                $(this).find('.delComp').css('visibility', 'visible');
            }, function(){
                $(this).find('.delComp').css('visibility', 'hidden');
            })
        }
    };

    //Initialize admin page behavior.
    $(document).ready(function(){
        spAdmin.adminpage.init();
    });

})(jQuery);