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
                  revert: 'valid',
                  connectToSortable: sortableDiv,
                  cancel: ".disableSPSortable"
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
                    var node = $("#sp_catTree").dynatree("getTree").getNodeByKey('comp-' + compID);
                    if(node)
                        node.remove();
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
                    if(spAdmin.sp_catComponent){
                        spAdmin.sp_catComponent.saveCatCompTitleAJAX(compID, value, function(response){});
                        var node = $("#sp_catTree").dynatree("getTree").getNodeByKey('comp-' + compID);
                        if(node){
                            node.data.title = value;
                            node.render();
                        }
                    }
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
                persist: true,
                clickFolderMode: 1,
                onActivate: function (node) {
                    if(node.data.isFolder){
                        window.open(node.data.href, node.data.target);
                    }
                },
                debugLevel: 0
            });
            sp_catTree.dynatree("getTree").renderInvisibleNodes();
        },

        /**
         * Given a SmartPost category component container (@see sp_CatComponent.php),
         * binds the appropriate JS events/listeners to the container
         * @param component - jQuery object representing the component
         * @param catID - The ID of the category the component belongs to
         */
        initializeComponent: function(component, catID){
            var self = this;

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

            /*Add a new node to the tree
            var compTitle = $(component).find('.editableCatCompTitle').html();
            var compIcon  = $(component).find('.catCompIcon').attr('src');
            var compID    = $(component).attr('id').split('-')[1];
            var node = $("#sp_catTree").dynatree("getTree").getNodeByKey('cat-' + catID);

            if(node){
                node.addChild({
                    title: compTitle,
                    key  : 'comp-' + compID,
                    icon : compIcon
                });
            }
            */
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

            var newCompHndlr = function(newComponent){
                var component = $(newComponent);
                ui.item.replaceWith(component);
                self.initializeComponent(component, catID);
                self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
            };

            //Handle drag n' drop via component draggables
            if ( ui.item.hasClass('catCompDraggable') ){

                var typeID = ui.item.attr("type-id").split("-")[1];
                spAdmin.sp_catComponent.addComponent(catID, typeID, newCompHndlr);

            //Handle drag n' drop via dynaTree
            }else if( ui.item.hasClass('dynatree-node') ){

                var node   = $.ui.dynatree.getNode(ui.item.context);
                var compID = node.data.compID;

                if(node.data.compID){
                    spAdmin.sp_catComponent.copyComponent(compID, catID, newCompHndlr);
                }

                if(node.data.catID){
                    if((node.childList instanceof Array) && node.childList.length > 0){
                        var copyTemplateHndlr = function(response, statusText, jqXHR){
                            var components = $(response).children('div');
                            ui.item.replaceWith(components);

                            $.each(components, function(key, val){
                                self.initializeComponent($(val), catID);
                            });
                            self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
                        }
                        spAdmin.sp_catComponent.copyTemplate(node.data.catID, catID, copyTemplateHndlr);
                    }else{
                        self.showError('The template you are trying to copy has no components!', null);
                    }
                }

                if(!node.data.catID && !node.data.compID){
                    self.showError('Invalid dropped item!', null);
                    ui.remove();
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
         * "Enables" a wordpress category, or "disables" a SP category.
         * @param caID - The category
         */
        switchCategory: function(catID){
            var self = this;
            if(catID){
                $.ajax({
                    url	 : SP_AJAX_URL,
                    type : 'POST',
                    data : {
                        action : 'switchCategoryAJAX',
                        nonce  : SP_NONCE,
                        catID  : catID
                    },
                    dataType : 'json',
                    success  : function(response){
                        window.location.reload();
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        self.showError(errorThrown, null);
                    }
                })
            }else{
                self.showError('Empty category ID!');
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
            sortableDiv.sortable({
                axis: "y",
                stop: function(e, ui){
                    self.replaceDroppedItem(e, ui);
                },
                start: function(e, ui){
                    var node = $.ui.dynatree.getNode(ui.item.context);
                    if(node){
                        if(node.data.catID > 0){
                            ui.placeholder.html('<div id="catCompIndicator" style="position: relative;"><div class="catDragCompCount">' + node.childList.length + '</div></div>');
                        }
                    }
                },
                placeholder: "sortable-placeholder"
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
            $('#newCatButton').click(function () {
                $('#newCategoryForm').dialog('open');
            });

            //Enable new template submission
            this.submitCategory( $('#cat_form') );

            //Click handler for enable/disable checkboxes
            $('#sp_enabled').click(function(){
                self.switchCategory($('#catID').val());
            });

        }
    };

    //Initialize admin page behavior.
    $(document).ready(function(){
        spAdmin.adminpage.init();
    });

})(jQuery);