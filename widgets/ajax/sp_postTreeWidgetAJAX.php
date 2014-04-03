<?php
if (!class_exists("sp_postTreeWidgetAJAX")) {
	class sp_postTreeWidgetAJAX{
		
		static function init(){
            add_action( 'wp_ajax_setTreeDisplayAJAX', array('sp_postTreeWidgetAJAX', 'setTreeDisplayAJAX') );
            add_action( 'wp_ajax_getCatTreeAJAX', array('sp_postTreeWidgetAJAX', 'getCatTreeAJAX') );
            add_action( 'wp_ajax_nopriv_getCatTreeAJAX', array('sp_postTreeWidgetAJAX', 'getCatTreeAJAX') );
		}

        /**
         * Helper function to select which categories are selected and which aren't
         * @todo Figure out a way to replace parent cats with child cats
         * @param $catTree
         * @param $displayCats
         */
        private static function hideNodes(&$catTree, $displayCats){

            if( is_array( $catTree ) && is_array( $displayCats ) ){
                foreach($catTree as $key => $node){
                    if( isset( $node->catID ) && !in_array($node->catID, $displayCats) ){
                        unset( $catTree[$key] );
                        $catTree = array_values( $catTree ); // re-index the array to keep proper indexing for dynatree
                    }else{
                        if( !empty($node->children) ){
                            self::hideNodes($node->children, $displayCats);
                        }
                    }
                }
            }
        }

        /**
         * Tree for the front-end
         */
        function getCatTreeAJAX(){
            if( empty($_POST['widgetId']) ){
                header("HTTP/1.0 409 Could find widget ID to update.");
                exit;
            }

            $widgetId = (int) $_POST['widgetId'];

            $args = array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0, '' );
            $postArgs = array( 'posts_per_page' => -1, 'numberposts' => -1 );
            $catTree = sp_postTreeWidget::buildCatDynaTree( $args, $postArgs );

            // Get Tree Settings
            $sp_treeWidgets = get_option( 'widget_sp_posttreewidget' );
            $displayCats = $sp_treeWidgets[$widgetId]['displayCats'];

            /*
            error_log('BEFORE');
            error_log( print_r( $displayCats, true ) );
            error_log( print_r( $catTree, true ) );
            */

            self::hideNodes($catTree, $displayCats);

            /*
            error_log('AFTER');
            error_log( print_r( $displayCats, true ) );
            error_log( print_r( $catTree, true ) );
            */

            echo json_encode( array_values($catTree) ); // Important to have array_values() here after filtering hidden nodes
            exit;
        }
	}
}