/**
 * Created by ryagudin on 1/1/14.
 * @author Rafi Yagudin
 * @project SmartPost
 * @version 2.0
 * @description Handles dashboard JS for the SmartPost QuickPost widget
 */
(function($){
    sp_widgets.sp_qpWidgetAdmin = {
        /**
         * Toggles the sp_qp_categories div
         */
        toggleCatMode: function(){
            $('.sp_catMode').click(function(){
                var widget_number = $(this).attr('data-widget-num');
                $('#sp_qp_categories-' + widget_number).toggle();
            });
        },
        init: function(){
            var self = this;
            self.toggleCatMode();
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_qpWidgetAdmin.init();
    });
})(jQuery);