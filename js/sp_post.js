/*
 * JS for sp_postComponent class
 * Used in front-end posts
 */
(function($){
    smartpost.sp_post = {

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
                extraPlugins: 'sourcedialog,confighelper',
                toolbar: [
                    { name: 'document', items: [ 'Bold', 'Italic', 'Underline' ] },
                    { name: 'links', items: [ 'Link', 'Unlink' ] },
                    { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight' ] },
                    { name: 'styles', items: [ 'FontSize' ] },
                    { name: 'colors', items: [ 'TextColor' ] },
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

            // Initialize all sp-editor instances
            $( '.sp-editor-content' ).each(function(){
                self.initCkEditors( $(this) );
            });

        }
    }

    $(document).ready(function(){
        smartpost.sp_post.init();
    });

})(jQuery);