/*
 * JS for sp_postComponent class
 * Used in front-end posts
 */
(function($){
    smartpost.sp_postComponent = {

        /**
         * Makes all the component divs sortable
         */
        makeSortable: function(sortableElem, postID){
            var thisObj = this;

            if(sortableElem == undefined){
                sortableElem = $('.sortableSPComponents');
            }

            sortableElem.sortable({
                handle     : '.componentHandle',
                placeholder: 'componentPlaceholder',
                forcePlaceholderSize: true,
                axis  : "y",
                helper : function(event, ui){
                    var title = ui.find('.editableCompTitle').text();
                    if(title == ''){
                        title = "Component";
                    }
                    return '<div class="sp_component_helper">' + title + '</div>';
                },
                stop  : function(event, ui){
                    if( ui.item.hasClass('catComponentWidget') ){
                        var catCompID = ui.item.attr("data-compid");
                        var typeID    = ui.item.attr("data-typeid");
                        var replaceMe = function(newComponent){
                            sortableElem.find('.catComponentWidget').replaceWith(newComponent);
                            thisObj.setCompOrder(sortableElem.sortable('toArray'), postID);
                        }
                        thisObj.addNewComponent(catCompID, typeID, postID, false, replaceMe);
                    }else{
                        thisObj.setCompOrder($(this).sortable('toArray'), postID);
                    }
                }
            });
        },

        /**
         * Makes the SP Widget draggable
         */
        makeWidgetDraggable: function(sortableElem){
            var thisObj = this;
            if($('#catCompList').exists()){

                $('.catComponentWidget').draggable({
                    addClasses: false,
                    helper: 'clone',
                    revert: 'invalid',
                    connectToSortable: sortableElem
                });

            }
        },

        /**
         * Save the title of a post component
         */
        saveCompTitle: function(title, compID){
            $.ajax({
                url		 : ajaxurl,
                type     : 'POST',
                data	 : {action: 'saveCompTitleAJAX', nonce: spNonce, compID: compID, title: title},
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Make component titles editable
         */
        editableCompTitle: function(titleElems){
            var thisObj = this;

            if( titleElems == undefined){
                titleElems = $('.editableCompTitle');
            }

            titleElems.editable(function(value, settings){
                    var compID = $(this).attr('data-compid');
                    thisObj.saveCompTitle(value, compID);
                    return value;
                },
                {
                    placeholder: 'Click to add a title',
                    onblur     : 'submit',
                    cssclass   : 'sp_compTitleEditable',
                    maxlength  : 65
                })
        },

        /**
         * Adds a new post component instance to the post. Note: it is not recommended
         * to provide both the componentStack and replaceWith parameters together.
         *
         * @param int catCompID The category component ID
         * @param int typeID The category component typeID - used for
         *                   binding init methods for newly added components
         *                   (i.e. added to the DOM after)
         * @param int postID The ID of the current post
         * @param closure compHandler A function that recieves the component as a parameter
         */
        addNewComponent: function(catCompID, typeID, postID, componentStack, compHandler){
            var thisObj      = this;
            var newComponent = null;

            if(componentStack == undefined && componentStack != false ){
                var componentStack = $('#spComponents');
            }

            if( postID == undefined){
                postID = $('#postID').val();
            }

            //Used to bind new component to its necessary events
            //Finds the right component type via sp_globals.types[] and typeID
            $.ajax({
                url		 : ajaxurl,
                type     : 'POST',
                data	 : {
                    action      : 'newPostComponentAJAX',
                    nonce       : spNonce,
                    catCompID   : catCompID,
                    postID      : postID
                },
                dataType : 'html',
                success  : function(response, statusText, jqXHR){
                    newComponent = $(response);

                    //Add the new component to the DOM window if necessary
                    if(componentStack){
                        newComponent.appendTo(componentStack);
                    }

                    //Call compHandler now that we have the component
                    if(compHandler){
                        compHandler(newComponent);
                    }

                    thisObj.initializeComponent(newComponent, typeID);

                },
                error    : function(jqXHR, statusText, errorThrown){
                    thisObj.showError(errorThrown);
                }
            })
        },

        /**
         * Adds a nicEditor to the DOM. Used in the link and media components.
         *
         * @param string elementID The DOMElem id attribute to bind the editor to
         * @param string panelID   The DOMelem id attribute to bind the coinciding panel to. If
         *																									left empty, the panel will not be displayed
         * @param string placeHolder A placeholder in case the editor is left empty after onblur
         * @param func   saveContentFn A save function to be called. Will be passed content,
         * 																												content container ID, and the editor instance.
         */
        addNicEditor: function(elementID, panelID, saveContentFn, placeHolder){
            var thisObj   = this;
            var buttons   = ['save','bold','italic','underline','left','center','right','justify',
                'ol','ul','strikethrough','removeformat','indent','outdent','image',
                'forecolor','bgcolor','link','unlink','fontFormat','xhtml']
            var editor    = new nicEditor({buttonList: buttons,
                iconsPath : IMAGE_PATH + '/nicEditorIcons.gif',
                onSave    :	function(content, id, instance){ saveContentFn(content, id, instance) }
            }).addInstance(elementID);
            var counter   = 1;

            //Reset counter so it saves after onfocus
            editor.addEvent('focus', function(){
                counter = 0;
                if(panelID){
                    editor.setPanel(panelID);
                }
                var content = editor.instanceById(elementID).getContent();
                if(content == placeHolder){
                    editor.instanceById(elementID).setContent('');
                    $('#' + elementID).click().focus();
                }
            });

            //Save content only after 1st onblur event
            editor.addEvent('blur', function(){
                if(counter < 1){
                    if(panelID){	$('#' + panelID).html('');	}
                    var content = editor.instanceById(elementID).getContent();
                    if($.trim($('#' + elementID).text()) == ''){
                        editor.instanceById(elementID).setContent(placeHolder);
                    }else{
                        saveContentFn(content, elementID, editor.instanceById(elementID));
                    }
                }
                counter++;
            });
        },

        /**
         * Initializes an HTML component element
         */
        initializeComponent: function(component, typeID, postID, autoFocus){
            if(postID == undefined){
                postID = $('#postID').val();
            }

            if(typeID == undefined){
                typeID = $(component).attr('data-typeid');
            }

            if(autoFocus == undefined){
                autoFocus = true;
            }

            if(sp_globals.types){
                var componentJS = sp_globals.types[typeID];
            }else{
                alert('sp_globals var missing, could not initialize component!');
            }

            var titleDiv = $(component).find('.editableCompTitle');
            this.editableCompTitle($(titleDiv));

            //Bind any init methods
            if(componentJS){
                componentJS.initComponent(component, postID, autoFocus);
            }

            //Enable simple menu
            if(simpleMenu){
                simpleMenu.initComponent(component);
            }

            //Enable delete
            this.deleteComponent(component);

            //Remove required marker if necessary
            var catCompID =	$(component).attr("data-catcompid");
            $('.sp_component[data-catcompid="' + catCompID + '"]').removeClass('requiredComponent');
        },

        /**
         * Saves the post components order
         *
         * @param int catCompID The category component ID
         */
        setCompOrder: function(components, postID){
            if(components.length > 0){
                var thisObj   = this;
                var compOrder = new Array();
                if( postID == undefined ){
                    postID = $('#postID').val();
                }

                $(components).each(function(index, value){
                    var compID = value.split('-')[1];
                    compOrder[index] = compID;
                });

                $.ajax({
                    url		 : ajaxurl,
                    type     : 'POST',
                    data	 : {
                        action    : 'setPostCompOrderAJAX',
                        nonce     : spNonce,
                        compOrder : compOrder,
                        postID    : postID
                    },
                    dataType : 'json',
                    success  : function(response, statusText, jqXHR){
                        console.log(response);
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        thisObj.showError(errorThrown);
                    }
                })
            }
        },

        /**
         * Displays any errors that results from the different operations on
         * the post components.
         *
         * @param string errorText The error text to display
         */
        showError: function(errorText){
            $('#component_errors').show().html(
                'Error: ' +	errorText);
            $('html, body').animate({ scrollTop: 0 }, 0);
        },

        /**
         * Delete the component when "Delete" is clicked from the component menu
         */
        deleteComponent: function(component){
            var thisObj = this;
            var deleteElem;

            //In case we need to bind this function to a DOMElem
            if(component){
                deleteElem = $(component).find('.sp_delete');
            }else{
                deleteElem = '.sp_delete';
            }

            $(deleteElem).click(function(){
                var compID    = $(this).attr('data-compid');
                var catCompID = $('#comp-' + compID).attr('data-catcompid');
                var typeID    = $('#comp-' + compID).attr('data-typeid');

                $.ajax({
                    url				  : ajaxurl,
                    type     : 'POST',
                    data			  : {action      : 'deletePostComponentAJAX',
                        nonce       : spNonce,
                        compID      : compID
                    },
                    dataType : 'json',
                    success  : function(response, statusText, jqXHR){
                        //Remove the component from the DOM
                        $('#comp-' + compID).remove();

                        //Mark required components if any exist
                        if(catCompID > 0 && thisObj.isRequired(compID)){
                            var filter = new Array();

                            var components = thisObj.getComponents(filter[0] = catCompID);


                            if(components.length == 1 ){
                                var componentJS = sp_globals.types ? sp_globals.types[typeID] : false;
                                if(componentJS.isEmpty(components[0])){
                                    $(components[0]).addClass('requiredComponent');
                                }
                            }
                        }
                    },
                    error    : function(jqXHR, statusText, errorThrown){
                        thisObj.showError(errorThrown);
                    }
                })
                return false;
            });
        },

        /**
         * If a post component is the last of its kind
         *
         * @param int the component's ID
         * @return bool true if the post is the last of its kind, otherwise false
         */
        isLast: function(compID){
            var catCompID = $('#comp-' + compID).attr('data-catcompid');
            return ($('.sp_component[data-catcompid="' + catCompID + '"]').length == 1);
        },
        /**
         * If the post component is required
         *
         * @param int compID the component id
         * @reutnr bool true if it's required, false otherwise
         */
        isRequired: function(compID){
            return Boolean( $('#comp-' + compID).attr("data-required") );
        },
        /**
         * Returns an array of components based off the filter, otherwise it will
         * return all the components in the post.
         *
         * @param array filter An array containing catCompID's to filter, i.e. filter[0] = "7", filter[1] = "4", etc
         * @return array The components
         */
        getComponents: function(filter){
            var count = 0;
            var components = new Array();
            if(filter){
                for(var i=0; i < filter.length; i++){
                    components[i] = $('.sp_component[data-catcompid="' + filter[i] + '"]');
                }
            }else{
                components[0] = $('.sp_component').length;
            }
            return components;
        },
        /**
         * Initializes components with any necessary init methods
         */
        init: function(){
            this.makeSortable()
            this.makeWidgetDraggable('.sortableSPComponents');
            this.deleteComponent();
            this.editableCompTitle();
        }
    }//end smartpost.sp_postComponent

    $(document).ready(function(){
        smartpost.sp_postComponent.init();
    });

})(jQuery);