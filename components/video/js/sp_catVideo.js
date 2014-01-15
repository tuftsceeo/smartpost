/**
 * Handles dashboard JS for the video component.
 *
 * @version 1.2
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu
 * @project SmartPost
 */

(function($){
     sp_admin.sp_catVideo = {

         /**
          * AJAX handler for calling sp_catVideoAJAX::check_for_ffmpeg() asychronously.
          * @param ffmpeg_path the full path to the ffmpeg program.
          */
        check_for_ffmpeg: function(ffmpeg_path){
            $.ajax({
                url : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'check_for_ffmpeg',
                    nonce: SP_NONCE,
                    ffmpeg_path: ffmpeg_path
                },
                dataType : 'json',
                success : function(response){
                    if( response.success ){

                        $( '#check_ffmpeg_results').html('Success! ffmpeg was successfully called using "' + response.output + '". This path has been saved and will be used to invoke ffmpeg.');

                        var ffmpeg_not_found = $( '#ffmpeg_not_found' );

                        if( ffmpeg_not_found.exists() ){
                            ffmpeg_not_found.removeClass( 'error').addClass( 'updated').html( '<p>The provided path works! Refresh the page to see new settings.</p>' );
                        }
                    }else{
                        sp_admin.adminpage.showError( 'Exit code: ' + response.status_code, null );
                    }
                },
                error : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError( statusText + ': ' + errorThrown, null );
                }
            })
        },

        /**
         * Enables HTML5Video conversion if available via the use
         * of a checkbox.
         * @param checkBoxElem - jQuery object representing a checkbox element
         */
        enableHTML5Video: function(checkBoxElem){
            var convertToHTML5 = checkBoxElem.is(':checked') ? 1 : 0;
            var compID = checkBoxElem.attr('compID');

            $.ajax({
                url	 : SP_AJAX_URL,
                type : 'POST',
                data: {
                    action : 'enableHTML5VideoAJAX',
                    nonce  : SP_NONCE,
                    convertToHTML5 : convertToHTML5
                },
                dataType : 'json',
                success  : function(response, statusText, jqXHR){
                    console.log(response);
                },
                error : function(jqXHR, statusText, errorThrown){
                    sp_admin.adminpage.showError(errorThrown);
                }
            });
        },
        init: function(){
            var self = this;
            $( '#check_ffmpeg_path' ).click(function(){
                var ffmpeg_path = $( '#ffmpeg_path' ).val();
                self.check_for_ffmpeg( ffmpeg_path );
            })

            $('.enableHTML5Video').click(function(){
                self.enableHTML5Video( $(this) );
            });
        }
    }

    $(document).ready(function(){
       sp_admin.sp_catVideo.init();
    });

})(jQuery);