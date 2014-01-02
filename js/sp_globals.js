/**
 * SmartPost global variables go here.
 * Please keep global variables within the "sp_globals" namespace as to not pollute the global namespace.
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 */

var smartpost  = smartpost  || {};
var sp_widgets = sp_widgets || {};
var sp_globals = sp_globals || {};

//To-do: remove these global variables to minimize namespace pollution
var SP_AJAX_URL    = sp_globals.SP_AJAX_URL;
var SP_NONCE       = sp_globals.SP_NONCE;
var SP_IMAGE_PATH  = sp_globals.SP_IMAGE_PATH;
var SP_PLUGIN_PATH = sp_globals.SP_PLUGIN_PATH;
var SP_MAX_UPLOAD_SIZE = sp_globals.MAX_UPLOAD_SIZE;

if(jQuery){
    jQuery.fn.exists = function(){ return this.length > 0; };

    (function($){
        /**
         * Adds an inline nicEditor to the DOM. Editor is hidden until user clicks on element
         * represented by elementID.
         * Used in media, video, link, photo, photo gallery components as well as the admin area.
         *
         * @param elementID - The DOMElem id attribute to bind the editor to
         * @param panelID - The DOMelem id attribute to bind the coinciding panel to. If left empty, the panel will not be displayed.
         * @param placeHolder - A placeholder in case the editor is left empty after onblur.
         * @param saveContentFn - A save function to be called. Will be passed content, content container ID, and the editor instance.
         */
        smartpost.addInlineNicEditor = function(elementID, panelID, saveContentFn, placeHolder) {
            var buttons = [ 'save','bold','italic','underline','left','center','right','justify',
                'ol','ul','strikethrough','removeformat','indent','outdent','image',
                'forecolor','bgcolor','link','unlink','fontFormat','xhtml' ]

            var editor = new nicEditor({
                buttonList: buttons, iconsPath : SP_IMAGE_PATH + '/nicEditorIcons.gif',
                onSave : function(content, id, instance){ saveContentFn(content, id, instance) }
            }).addInstance(elementID);

            var counter = 1;

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
        }
    })(jQuery);
}

