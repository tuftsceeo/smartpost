/*
 * JS for sp_postComponent class
 * Used in front-end posts
 */
(function($){
    smartpost.sp_post = {

        /**
         * Adds a tag to the tag set if one doesn't exist,
         * otherwise adds it to the tag set.
         */
        addTag: function(tag, postID){

            var self = this;

            // Find the post ID
            if(postID == undefined){
                postID = $('#postID').val();
            }

            // If it's still undefined, then we are in using the QuickPost widget
            if(postID == undefined){
                postID = $('#sp_qpPostID').val();
            }

            $.ajax({
                url     : SP_AJAX_URL,
                dataType: 'json',
                type    : 'POST',
                data    : {
                    action: 'sp_addNewTagAJAX',
                    nonce: SP_NONCE,
                    tag: tag,
                    postID: postID
                },
                success : function( data, status, xhr ) {
                    //console.log(data);
                    var tagElem = $('#tag-' + data.tagID);
                    if( tagElem.length > 0){
                        smartpost.sp_postComponent.showError( 'Tag "' + data.tag + '" already used!' );
                    }else{
                        $( '#sp-tags' ).append(
                            '<div class="sp-tag" id="tag-' + data.tagID + '">' +
                                '<a href="' + data.tagLink + '">' + data.tag + '</a> ' +
                                '<span class="sp-remove-tag sp_xButton" data-tagid="' + data.tagID + '" title="Remove Tag" alt="Remove Tag"></span>' +
                            '</div>'
                        );

                        // Enable remove button
                        $('#tag-' + data.tagID).find('.sp-remove-tag').click(function(){
                            self.removeTag( postID, data.tagID );
                        });
                    }
                }
            });
        },

        /**
         * Returns false if tag is < 2 chars or is empty.
         * Empty entails the following strings:
         * "", '', "\"\"", '\'\''
         */
        validateTag: function(tag){
            if( tag.length < 1 ){
                return false;
            }else if( tag == "" ){
                return false;
            }else	if( tag == '' ){
                return false;
            }else if( tag == "\"\"" ){
                return false;
            }else if( tag == '\'\''){
                return false;
            }else{
                return true;
            }
        },
        /**
         * Initialize autocomplete on searchElem where searchElem
         * is an HTML DOMElement.
         */
        initAutoComplete: function(searchElem){
            var cache = {}, lastXHR, self = this;

            searchElem.autocomplete({
                create: function( event, ui ){
                    $(this).keypress(function (e) {
                        if (e.which == 13) {
                            var tag  = $(this).val();
                            if ( self.validateTag( tag ) ){
                                self.addTag( tag );
                                $(this).val( '' );
                            }else{
                                smartpost.sp_postComponent.showError( "Please type in a tag name that is at least 1 characters long." );
                            }
                        }
                    });
                },
                minLength: 1,
                select: function( event, ui ){
                    var tag = ui.item.label;
                    self.addTag( tag );
                    $(this).val( '' );
                    return false;
                },
                source: function( request, response ) {
                    var term = request.term;
                    if ( term in cache ) {
                        response( cache[ term ] );
                        return;
                    }
                    lastXhr = $.ajax({
                        url     : SP_AJAX_URL,
                        dataType: 'json',
                        type    : 'POST',
                        data    : {
                            action: 'sp_searchTagsAJAX',
                            nonce: SP_NONCE,
                            tagRequest: request
                        },
                        success : function( data, status, xhr ) {
                            cache[ term ] = data;
                            if ( xhr === lastXhr ) {
                                response( data );
                            }
                        }
                    });
                }
            });
        },

        /**
         * Remove a post tag
         */
        removeTag: function(postID, tagID){
            if(tagID == undefined || tagID == 0){
                return;
            }
            $.ajax({
                url     : SP_AJAX_URL,
                dataType: 'json',
                type    : 'POST',
                data    : {
                    action: 'sp_removeTagAJAX',
                    nonce: SP_NONCE,
                    tagID: tagID,
                    postID: postID
                },
                success : function( data ) {
                    if(data.success){
                        $('#tag-' + tagID).remove();
                    }
                }
            });
        },

        /**
         * Save the title of a component
         */
        savePostTitle: function(title, postID){

            if( title.length < 5 ){
                smartpost.sp_postComponent.showError('Title field must be at least 5 characters long!');
                return;
            }

            if( postID == undefined ){
                smartpost.sp_postComponent.showError('Could not find the postID needed to update the title!');
                return;
            }

            $.ajax({
                url		 : SP_AJAX_URL,
                type     : 'POST',
                data	 : {action: 'savePostTitleAJAX', nonce: SP_NONCE, postID: postID, post_title: title},
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    if(sp_postComponent)
                    console.log(jqXHR);
                    console.log(statusText);
                    console.log(errorThrown);
                    smartpost.sp_postComponent.showError(errorThrown + statusText + jqXHR);
                }
            })
        },

        /**
         * Make component titles editable
         */
        editablePostTitle: function(titleElems){
            var thisObj = this;

            if( titleElems == undefined){
                titleElems = $('.sp_postTitle');
            }

            titleElems.editable(function(value, settings){
                    var postID = $('#postID').val();
                    thisObj.savePostTitle(value, postID);
                    return value;
                },
                {
                    placeholder: 'Click to add a title',
                    onblur     : 'submit',
                    cssclass   : 'sp_compTitleEditable',
                    maxlength  : 65,
                    event      : 'click'
                })
        },

        /**
         * Initializes a CKEditor instance given a DOM elem.
         * @param editorElem
         */
        initCkEditors: function(editorElem){
            // Get AJAX data
            var editor_data = editorElem.data();

            // Create inline editors for each .sp-editor-content element
            CKEDITOR.inline( editorElem.attr('id'), {
                enterMode: CKEDITOR.ENTER_BR,
                extraPlugins: 'sourcedialog,confighelper,removeformat',
                toolbar: [
                    { name: 'document', items: [ 'Bold', 'Italic', 'Underline' ] },
                    { name: 'links', items: [ 'Link', 'Unlink' ] },
                    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight' ] },
                    { name: 'styles', items: [ 'FontSize' ] },
                    { name: 'colors', items: [ 'TextColor' ] },
                    { name: 'Remove Formatting', items: [ 'RemoveFormat' ] },
                    { name: 'Source', items: [ 'Sourcedialog' ] }
                ],
                allowedContent:
                    'h1 h2 h3 p strong em ol li ul br;' +
                    'a[!href];' +
                    'img(left,right)[!src,alt,width,height];' +
                    'p{font-family, color, font-size, text-align};' +
                    'div{font-family, color, font-size, text-align};' +
                    'span{font-family, color, font-size, text-align};' +
                    'span(!marker);' +
                    'del ins',
                removePlugins: editor_data.toolbar
            }).on('blur', function(e){
                // Add nonce and content before sending it off
                editor_data.nonce   = SP_NONCE;
                editor_data.content = this.getData();
                smartpost.ajaxcall(
                    editor_data,
                    function(response){},
                    function(response){},
                    null
                )
            });

            // Click handler for edit icon
            $( '.sp-editor-identifier' ).click(function(){
                $(this).next().focus();
            })
        },

        /**
         * Initializes the post with any necessary init methods
         *
         * @uses editablePostTitle()
         */
        init: function(){
            var self = this;

            // Turn off automatic editor creation first.
            CKEDITOR.disableAutoInline = true;

            // Remove silly title attribute
            CKEDITOR.on('instanceCreated', function(event) {
                var editor = event.editor;
                editor.on('instanceReady', function(e) {
                    $(e.editor.element.$).removeAttr("title");
                });
            });
            // Initialize all sp-editor instances
            $( '.sp-editor-content' ).each(function(){
                self.initCkEditors( $(this) );
            });

            // Enable tagging
            smartpost.sp_post.initAutoComplete( $( '#sp-add-tags' ) );

            // Enable tag removal in posts
            $('.sp-remove-tag').each(function(){
                $(this).click(function(){
                    console.log( this );
                    console.log( $(this) );
                    var tagID = $(this).data( 'tagid' );
                    self.removeTag( $('#postID').val(), tagID );
                });
            });

            // hide plupload thing
            $('.plupload').css('height', '0px');
        }
    };

    $(document).ready(function(){
        smartpost.sp_post.init();
    });

})(jQuery);