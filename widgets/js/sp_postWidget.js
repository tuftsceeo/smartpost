/**
 * JS for the sp_postWidget class. Used in
 * front-end posts for post manipulation and
 * options.
 */
(function($){
    sp_widgets.sp_postWidget = {

        /**
         * Makes the SP Widget draggable
         */
        makeWidgetDraggable: function(draggableContainer, sortableElemID){
            $('.catComponentWidget').draggable({
                addClasses: false,
                helper: 'clone',
                revert: 'invalid',
                connectToSortable: sortableElemID
            });
        },

        /**
         * Initializes the postWidget
         */
        init: function(){
            var self = this;
            $( '.sp-widget-post-settings-draggable-comps' ).each(function(){
                self.makeWidgetDraggable( $(this), '.sortableSPComponents' );
            });
        }
    }

    $(document).ready(function(){
        sp_widgets.sp_postWidget.init();
    });
})(jQuery);