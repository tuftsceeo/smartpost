/*
 * JS for sp_admin class
 * Used in dashboard/admin page
 */

(function($) {
    sp_admin.adminpage = {

        /**
         * Displays errors to the user.
         * @param errorElem - The HTML DOM elm's where the error will be displayed. Default: #setting_errors
         * @param errorText  - The error message to display (can be HTML)
         */
        showError: function(errorText, errorElem){
            if(errorElem == undefined)
                errorElem = '#sp_errors';

            $(errorElem).html('<p>Error: ' + errorText + '</p>');
            $(errorElem).parent().show();
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
                        action    : 'set_comp_order_ajax',
                        nonce     : SP_NONCE,
                        compOrder : compOrder,
                        catID     : catID
                    },
                    dataType : 'json',
                    success  : function(response){
                        // console.log(response);
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
          if( draggableDiv == undefined )
            draggableDiv = $('.catCompDraggable');

          if( sortableDiv == undefined )
            sortableDiv = $('#normal-sortables');

          if( draggableDiv.exists() ){
              draggableDiv.draggable({
                  addClasses: true,
                  helper: dragHelper,
                  connectToSortable: sortableDiv,
                  cancel: ".disableSPSortable"
              })
          }
        },

        /**
         * Given a compID that represents a component, copies that component
         * instance to a category represented by catID
         * @param compID - The component ID of the component to copy
         * @param catID - The categoryID receiving the copy
         * @param cl - closure that gets passed response from server
         */
        copyComponent: function(compID, catID, cl){
            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {
                    action : 'copyComponentAJAX',
                    nonce  : SP_NONCE,
                    compID : compID,
                    catID  : catID
                },
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    //Remove the outer parent: <div id="advanced-sortables">
                    cl($(response).html(),  statusText, jqXHR);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown, null);
                }
            })
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
                sp_admin.sp_catComponent.deleteComponent(compID, cl);
            })
        },

        /**
         *
         * @param deleteElem
         */
        handleDeleteCat: function(deleteElem){
            var self = this;
            deleteElem.click(function(){
                var confirm_ok = confirm( 'Are you sure you want to delete this template?' );
                if(confirm_ok){
                    var catID = $(this).attr("data-cat-id");
                    $.ajax({
                        url	 : SP_AJAX_URL,
                        type : 'POST',
                        data : {
                            action    : 'delete_template_ajax',
                            nonce     : SP_NONCE,
                            catID     : catID
                        },
                        dataType : 'json',
                        success  : function(response){
                            if(response.success){
                                window.location.href = SP_ADMIN_URL + '?page=smartpost';
                            }else{
                                // console.log(response);
                                self.showError('Something bad happened! Dump: ' + response, null);
                            }
                        },
                        error : function(jqXHR, statusText, errorThrown){
                            self.showError(errorThrown, null);
                        }
                    })
                }
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
         * Make component titles editable
         */
        editableCatCompTitle: function(titleElems){

            if( titleElems == undefined){
                titleElems = $('.editableCatCompTitle');
            }

            titleElems.editable(function(value, settings){
                    var compID = $(this).attr('comp-id');
                    if(sp_admin.sp_catComponent){
                        sp_admin.sp_catComponent.saveCatCompTitleAJAX(compID, value, function(response){});
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
            var self = this;

            // AJAX handler to load in all the nodes dynamically
            sp_catTree.dynatree({
                initAjax: {
                    url: SP_AJAX_URL,
                    type: 'POST',
                    data: {
                        action: 'get_category_json_tree_ajax',
                        nonce: SP_NONCE
                    }
                },
                imagePath: "",
                generateIds: true,
                persist: true,
                clickFolderMode: 1,
                onActivate: function (node) {
                    if(node.data.isFolder){
                        window.open(node.data.href, node.data.target);
                    }
                },
                onPostInit: function(isReloading, isError){
                    if( !isError )
                        self.makeCompDivsDraggable('clone', $('.dynatree-node'), null);
                },
                debugLevel: 0
            });
            sp_catTree.dynatree("getTree").renderInvisibleNodes();

            //Click handler for expand/collapse all
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
        },

        /**
         * Syncs a node's children via AJAX call
         * @param node
         */
        syncDynaTreeNodeChildren: function(node){
            var self = this;

            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action : 'get_category_json_tree_ajax',
                    nonce  : SP_NONCE,
                    parent : $('#catID').val(),
                    includeParent: true
                },
                dataType : 'json',
                success  : function(nodeChildren){

                    // console.log(nodeChildren[0].children);

                    node.removeChildren();
                    $(nodeChildren[0].children).each(function(index, childNode){
                        node.addChild(childNode);
                    });
                    node.render(false, true);

                },
                error    : function(jqXHR, statusText, errorThrown){
                    self.showError(errorThrown, null);
                }
            })

        },

        /**
         * Given a SmartPost category component container (@see sp_CatComponent.php),
         * binds the appropriate JS events/listeners to the container
         * @param componentElem - jQuery object representing the component
         * @param catID - The ID of the category the component belongs to
         */
        initializeComponent: function(componentElem, catID){
            var self = this;

            //Enable delete event
            self.handleDeleteComp(componentElem.find('.delComp'));

            //Enable required and default checkboxes
            if(sp_admin.sp_catComponent)
                sp_admin.sp_catComponent.disableDefault(componentElem.find('.requiredAndDefault input'));

            //Enable component rename
            self.editableCatCompTitle();

            // Initialize the component with its own init method
            var compType = componentElem.attr('id').split('-')[0]; // Get the component type
            var compObj = sp_admin.SP_TYPES[compType]; // Get component object

            // Note: category components with no options may not have a JS object!
            if( compObj instanceof Object ){
                compObj.initComponent(componentElem);
            }

            //TODO: Enable postbox open/close
        },

        /**
         * Copies all of the components of a source category to a destination category,
         * effectively copying over an entire template.
         * @param srcCatID - The source category from which to copy the components
         * @param destCatID - The destination category to copy the templates to
         * @param cl - closure that gets called after ajax success
         */
        copyTemplate: function(srcCatID, destCatID, cl){
            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action : 'copyTemplateAJAX',
                    nonce  : SP_NONCE,
                    srcCatID  : srcCatID,
                    destCatID : destCatID
                },
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    cl(response, statusText, jqXHR);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown, null);
                }
            })
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
            var loadingPlaceholder = $('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Loading ...</p>');

            var newCompHndlr = function(newComponent){
                var component = $(newComponent);
                component.hide();
                loadingPlaceholder.replaceWith( component );
                component.fadeIn();

                self.initializeComponent(component, catID);
                self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
            };

            // Handle drag n' drop via component draggables
            if ( ui.item.hasClass('catCompDraggable') ){
                var typeID = ui.item.attr("type-id").split("-")[1];
                ui.item.replaceWith( loadingPlaceholder );
                sp_admin.sp_catComponent.addComponent(catID, typeID, newCompHndlr);

            // Handle drag n' drop via dynaTree
            }else if( ui.item.hasClass('dynatree-node') ){

                var node = $.ui.dynatree.getNode(ui.item.context);

                // Case 1: We are dragging and dropping a component
                if(node.data.compID){
                    ui.item.replaceWith( loadingPlaceholder );
                    self.copyComponent(node.data.compID, catID, newCompHndlr);
                }

                // Case 2: We are dragging and dropping a template
                if(node.data.catID){
                    if( (node.childList instanceof Array) && node.childList.length > 0){

                        ui.item.replaceWith( loadingPlaceholder );
                        var copyTemplateHndlr = function(response, statusText, jqXHR){
                            var components = $(response).children('div');
                            components.hide();
                            loadingPlaceholder.replaceWith( components );
                            components.fadeIn();

                            $.each(components, function(key, val){
                                self.initializeComponent( $(val), catID );
                            });
                            self.saveCompOrder( $(".meta-box-sortables").sortable( 'toArray' ) );
                        };
                        self.copyTemplate(node.data.catID, catID, copyTemplateHndlr);
                    }else{
                        self.showError( 'The template you are trying to copy has no components!', null );
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
        submitNewTemplate: function( formElement ){
            var self = this;
            var spCatOptions = {
                url	 : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'new_sp_cat_ajax',
                    nonce : SP_NONCE
                },
                dataType: 'json',
                success	: function(response){
                    if(response){
                        if(response.error){
                            self.showError(response.error, null);
                        }else{
                            window.location.href = SP_ADMIN_URL + '?page=smartpost&catID=' + response.catID + '&update_msg=new_cat';
                        }
                    }
                },
                beforeSubmit: function(formData, jqForm){ // form validation
                    var form = jqForm[0];
                    if(!form.template_name.value){
                        alert( 'Please fill in the category name' );
                        form.template_name.focus();
                        return false;
                    }
                },
                error: function(data){
                    alert('Error:' + data.statusText);
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
                        action : 'switch_category_ajax',
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
         * Initializes the sp_admin object with click handlers and variables
         * necessary for initialization.
         */
        init: function(){
            var self = this;

            if( $( '#catID' ).exists() ){
                var catID = $( '#catID' ).val();
                var sortableDiv = $( "#normal-sortables" );

                //Initialize a dynatree instance for the SP category tree.
                this.initComponentTree( $( "#sp_catTree" ) );

                //Make the component widgets draggable
                this.makeCompDivsDraggable('clone', null, null);

                //Re-define sortable behavior on the admin page.
                sortableDiv.sortable({
                    axis: "y",
                    revert: true,
                    stop: function(e, ui){
                        self.replaceDroppedItem(e, ui);
                    },
                    start: function(e, ui){
                        var node = $.ui.dynatree.getNode(ui.item.context);
                        if(node){
                            if(node.data.catID > 0){
                                ui.placeholder.html('<div id="catCompIndicator" style="position: relative;"><div class="catDragCompCount">' + node.data.compCount + '</div></div>');
                            }
                        }
                    },
                    placeholder: "sortable-placeholder",
                    tolerance: "intersect"
                });

                //Enable template deletion
                this.handleDeleteCat( $('.deleteCat') )

                //Enable component deletion
                this.handleDeleteComp( $('.delComp') );

                //Limit click event only to hndl class
                $('.postbox h3').unbind('click.postboxes');

                //Enable editable component titles
                this.editableCatCompTitle( $('.editableCatCompTitle') );

                //Initialize the new template form dialog
                this.submitNewTemplate( $('#template_form') );

                //Click handler for enable/disable checkboxes
                $( '#sp_enabled' ).click(function(){
                    self.switchCategory($('#catID').val());
                });
            }

            //Click handler for hiding messages
            $( '.hideMsg' ).click(function(){
                $(this).parent().hide();
            });
        }
    };

    //Initialize admin page behavior.
    $(document).ready(function(){
        $('.tooltip').tooltipster({
            delay: 0,
            interactive: false
        });
        sp_admin.adminpage.init();
    });

})(jQuery);