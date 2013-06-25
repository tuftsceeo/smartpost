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
                    console.log($('#' + divID ));
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
         * Make component titles editable
         */
        editableCatCompTitle: function(titleElems){

            if( titleElems == undefined){
                titleElems = $('.editableCatCompTitle');
            }

            titleElems.editable(function(value, settings){
                    var compID = $(this).attr('comp-id');
                    if(spAdmin.sp_catComponent)
                        spAdmin.sp_catComponent.saveCatCompTitleAJAX(compID, value, function(response){});
                    return value;
                },
                {
                    placeholder: 'Click to add a component title',
                    onblur     : 'submit',
                    cssclass   : 'editableCatCompTitle',
                    maxlength  : 35
                }
            )
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
                    if(node.data.isFolder){
                        node.expand(false)
                        window.open(node.data.href, node.data.target);
                    }
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
                var component = $(newComponent);
                ui.item.replaceWith(component);

                //Enable delete event
                self.handleDeleteComp(component.find('.delComp'));
                self.enableDeleteHover(component);

                //Enable required and default checkboxes
                if(spAdmin.sp_catComponent)
                    spAdmin.sp_catComponent.disableDefault(component.find('.requiredAndDefault input'));

                //Enable component rename
                self.editableCatCompTitle();

                //TODO: Enable postbox open/close
                //TODO: Enable icon drag n' drop

                self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
            };

            if ( ui.item.hasClass('catCompDraggable') ){
                var typeID = ui.item.attr("type-id").split("-")[1];
                spAdmin.sp_catComponent.addComponent(catID, typeID, cl);
            }else if( ui.item.hasClass('dynatree-node') ){
                var node   = $.ui.dynatree.getNode(ui.item.context);
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
         * Sends a AJAX request with new category information
         * based off the fields in the form represented by formID.
         * @param formElement
         */
        submitCategory: function(formElement){
            var self = this;
            var spCatOptions = {
                url	 : SP_AJAX_URL,
                type : 'POST',
                data : {action: 'newSPCatAJAX', nonce: SP_NONCE},
                dataType : 'json',
                success	: function(response){
                    if(response){
                        if(response.error){
                            self.showError(response.error, null);
                        }else{
                            window.location.href = SP_ADMIN_URL + '?page=smartpost&catID=' + response.catID;
                        }
                    }
                },
                beforeSubmit : function(formData, jqForm){ // form validation
                    var form = jqForm[0];
                    if(!form.cat_name.value){
                        self.showError('Please fill in the category name', null);
                        $('#cat_name').focus();
                        return false;
                    }
                },
                error: function(data){
                    self.showError(data.statusText, null);
                }
            };
            formElement.submit(function(){
                $(this).ajaxSubmit(spCatOptions);
                return false;
            });
        },

        /**
         * Initializes the spAdmin object with click handlers and variables
         * necessary for initialization.
         * TODO: define constants for element classes and IDs
         */
        SP_CAT_FORM: 'cat_form',
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
            sortableDiv.sortable({
                axis: "y",
                stop: function(e, ui){
                    self.replaceDroppedItem(e, ui);
                },
                placeholder: {
                    element: function(currentItem) {
                        var node = $.ui.dynatree.getNode(currentItem.context);
                        var placeholder = '';
                        if(node.data.catID > 0){
                            placeholder = '<div class="sortable-placeholder" style="height:32px; position: relative;"><div style="position: absolute; top: -5px; right: -5px; color: red;">' + node.childList.length + '</div></div>';
                        }else{
                            placeholder = '<div class="sortable-placeholder" style="height:32px;"></div>';
                        }

                        console.log($(placeholder))
                        return $(placeholder);
                    },
                    update: function(container, p) {}
                }
            });

            //Enable component deletion
            this.handleDeleteComp($('.delComp'));
            this.enableDeleteHover($('.postbox'));

            //Limit click event only to hndl class
            $('.postbox h3').unbind('click.postboxes');

            //Enable editable component titles
            this.editableCatCompTitle($('.editableCatCompTitle'));

            //Initialize the dialog
            $( "#newCategoryForm" ).dialog({
                resizable: false,
                draggable: false,
                autoOpen: false,
                title: "Create a new template:",
                width: "auto",
                modal: true
            });
            //Enable dialog for new category form
            $('#newCatButton').click(function(){
                $( '#newCategoryForm' ).dialog( 'open' );
            })

            //Enable new template submission
            this.submitCategory( $('#' + self.SP_CAT_FORM) )
        }
    };

    //Initialize admin page behavior.
    $(document).ready(function(){
        spAdmin.adminpage.init();
    });

})(jQuery);