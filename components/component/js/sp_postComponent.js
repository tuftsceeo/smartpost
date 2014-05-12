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
            var self = this;
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
                        var loadingPlaceholder = $('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Loading ...</p>');
                        var catCompID = ui.item.attr("data-compid");
                        var typeID    = ui.item.attr("data-typeid");
                        ui.item.replaceWith( loadingPlaceholder );
                        var replaceMe = function(newComponent){
                            newComponent.hide();
                            loadingPlaceholder.replaceWith( newComponent );
                            newComponent.fadeIn();
                            self.setCompOrder(sortableElem.sortable('toArray'), postID);
                        }
                        self.addNewComponent(catCompID, typeID, postID, false, replaceMe);
                    }else{
                        self.setCompOrder($(this).sortable('toArray'), postID);
                    }
                }
            });
        },

        /**
         * Save the title of a component
         */
        saveCompTitle: function(title, compID){
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveCompTitleAJAX',
                    nonce: SP_NONCE,
                    compID: compID,
                    title: title
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError(errorThrown);
                }
            })
        },

        /**
         * Make component titles editable
         */
        editableCompTitle: function(titleElems){
            var self = this;

            if( titleElems == undefined){
                titleElems = $( '.editableCompTitle' );
            }

            titleElems.editable(function(value, settings){
                var compID = $(this).data( 'compid' );
                self.saveCompTitle(value, compID);
                return value;
            },
            {
                placeholder: 'Click to add a title',
                onblur     : 'submit',
                cssclass   : 'sp_compTitleEditable',
                maxlength  : 50
            });
        },

        /**
         * Adds a new post component instance to the post. Note: it is not recommended
         * to provide both the componentStack and replaceWith parameters together.
         *
         * @param catCompID - The category component ID
         * @param typeID int - The category component typeID - used for
         *                     binding init methods for newly added components (i.e. added to the DOM after)
         * @param componentStack - The stack of component DOM elements
         * @param postID int - The ID of the current post
         * @param compHandler func - A function that recieves the component as a parameter
         */
        addNewComponent: function(catCompID, typeID, postID, componentStack, compHandler){
            var self = this;
            var newComponent = null;

            if(componentStack == undefined && componentStack != false ){
                componentStack = $('#spComponents');
            }

            if( postID == undefined){
                postID = $('#postID').val();
            }

            $.ajax({
                url : SP_AJAX_URL,
                type : 'POST',
                data :
                {
                    action : 'newPostComponentAJAX',
                    nonce : SP_NONCE,
                    catCompID : catCompID,
                    postID : postID
                },
                dataType : 'html',
                success : function(response){
                    newComponent = $(response);

                    // Add the new component to the DOM window if necessary
                    if(componentStack){
                        newComponent.hide(); // Hide the component initially for a nice fade-in
                        newComponent.appendTo(componentStack);
                        newComponent.fadeIn();
                    }

                    // Call compHandler now that we have the component
                    if(compHandler){
                        compHandler(newComponent);
                    }

                    self.initializeComponent(newComponent, typeID);

                },
                error: function(jqXHR, statusText, errorThrown){
                    self.showError(errorThrown);
                }
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

            if(sp_globals.SP_TYPES){
                var componentJS = sp_globals.SP_TYPES[typeID];
            }else{
                alert('sp_globals var missing, could not initialize component!');
            }

            var titleDiv = $(component).find('.editableCompTitle');
            this.editableCompTitle($(titleDiv));

            //Bind any init methods
            if(componentJS){
                componentJS.initComponent(component, postID, autoFocus);
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
                    url  : SP_AJAX_URL,
                    type : 'POST',
                    data : {
                        action    : 'setPostCompOrderAJAX',
                        nonce     : SP_NONCE,
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
                });
                }
        },

        /**
         * Displays any errors that results from the different operations on
         * the post components.
         *
         * @param string errorText The error text to display
         */
        showError: function(errorText){
            $( '#component_errors' ).show().append('<p>Error: ' + errorText + '</p>');
            $( 'html, body' ).animate({ scrollTop: 0 }, 0);
        },

        /**
         * Clears the error messages in the error div
         */
        clearErrors: function(clearButton){
            clearButton.click(function(){
                var errorDiv = $('#component_errors');
                errorDiv.find('p').remove();
                errorDiv.hide();
            });
        },

        /**
         * Delete the component when "Delete" is clicked from the component menu
         */
        deleteComponent: function(component){
            var self = this;
            var deleteElem;

            //In case we need to bind this function to a DOMElem
            if(component){
                deleteElem = $(component).find('.sp_delete');
            }else{
                deleteElem = '.sp_delete';
            }

            $(deleteElem).click(function(){
                var compID    = $(this).attr('data-compid');
                var doomedComponent =  $('#comp-' + compID);
                var catCompID = doomedComponent.attr('data-catcompid');
                var typeID    = doomedComponent.attr('data-typeid');
                var loadingPlaceholder = $('<p><img src="' + SP_IMAGE_PATH + '/loading.gif" /> Deleting ...</p>');

                $.ajax({
                    url  : SP_AJAX_URL,
                    type : 'POST',
                    data : {
                        action : 'deletePostComponentAJAX',
                        nonce  : SP_NONCE,
                        compID : compID
                    },
                    dataType : 'json',
                    beforeSend: function(){
                        doomedComponent.replaceWith( loadingPlaceholder );
                    },
                    success  : function(response, statusText, jqXHR){
                        //Remove the component from the DOM
                        loadingPlaceholder.remove();

                        // Mark required components if any exist
                        if(catCompID > 0 && self.isRequired(compID)){
                            var filter = new Array();

                            var components = self.getComponents(filter[0] = catCompID);
                            if(components.length == 1 ){
                                var componentJS = sp_globals.SP_TYPES ? sp_globals.SP_TYPES[typeID] : false;
                                if(componentJS.isEmpty(components[0])){
                                    $(components[0]).addClass('requiredComponent');
                                }
                            }
                        }
                    },
                    error : function(jqXHR, statusText, errorThrown){
                        self.showError(errorThrown);
                    }
                });
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
         * @param filter An array containing catCompID's to filter, i.e. filter[0] = "7", filter[1] = "4", etc
         * @returns {Array}
         */
        getComponents: function(filter){
            var components = [];
            if( filter ){
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
            this.deleteComponent();
            this.editableCompTitle();
            this.clearErrors( $('#clearErrors') );
        }
    }

    $(document).ready(function(){
        smartpost.sp_postComponent.init();
    });
})(jQuery);