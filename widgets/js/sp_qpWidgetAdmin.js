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
        toggleCatMode: function(checkbox){
            var widget_num = $(checkbox).data('widget-num');
            $('#sp_qp_categories-' + widget_num).toggle();
        },
        init: function(){}
    };

    $(document).ready(function(){
        sp_widgets.sp_qpWidgetAdmin.init();
    });
})(jQuery);