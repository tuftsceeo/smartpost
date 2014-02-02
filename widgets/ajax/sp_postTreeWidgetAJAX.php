<?php
if (!class_exists("sp_postTreeWidgetAJAX")) {
	class sp_postTreeWidgetAJAX{
		
		static function init(){
            add_action( 'wp_ajax_setTreeDisplayAJAX', array('sp_postTreeWidgetAJAX', 'setTreeDisplayAJAX') );
            add_action( 'wp_ajax_getCatAdminTreeAJAX', array('sp_postTreeWidgetAJAX', 'getCatAdminTreeAJAX') );
            add_action( 'wp_ajax_getCatTreeAJAX', array('sp_postTreeWidgetAJAX', 'getCatTreeAJAX') );
            add_action( 'wp_ajax_nopriv_getCatTreeAJAX', array('sp_postTreeWidgetAJAX', 'getCatTreeAJAX') );
		}

        /**
         * Admin Tree for the widget area
         */
        function getCatAdminTreeAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['widgetId']) ){
                header("HTTP/1.0 409 Could find widget ID to update.");
                exit;
            }

            $widgetId = (int) $_POST['widgetId'];
            $args = array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 );
            $catTree = sp_postTreeWidget::buildCatDynaTree( $args, false );

            // Get Tree Settings
            $sp_treeWidgets = get_option( 'widget_sp_posttreewidget' );
            $displayItems = $sp_treeWidgets[$widgetId]['displayItems'];

            if( !empty($displayItems) ){
                self::setCheckBoxes($catTree, $displayItems);
            }

            echo json_encode( $catTree );
            exit;
        }

        /**
         * Helper function to select which categories are selected and which aren't
         * @param $catTree
         * @param $displayItems
         */
        private static function setCheckBoxes(&$catTree, $displayItems){
            if( !empty($catTree) ){
                foreach($catTree as $node){
                    if( in_array($node->key, $displayItems) ){
                        $node->select = true;
                    }
                    if( !empty($node->children) ){
                        self::setCheckBoxes($node->children, $displayItems);
                    }
                }
            }
        }

        /**
         * Helper function to select which categories are selected and which aren't
         * @param $catTree
         * @param $displayItems
         */
        private static function hideNodes(&$catTree, $displayItems){
            foreach($catTree as $key => $node){
                if( in_array($node->key, $displayItems) ){
                    unset($catTree[$key]);
                }else{
                    if( !empty($node->children) ){
                        self::hideNodes($node->children, $displayItems);
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

            $args = array( 'orderby' => 'name','order' => 'ASC', 'hide_empty' => 0 );
            $catTree = sp_postTreeWidget::buildCatDynaTree( $args );

            // Get Tree Settings
            $sp_treeWidgets = get_option( 'widget_sp_posttreewidget' );
            $displayItems = $sp_treeWidgets[$widgetId]['displayItems'];

            self::hideNodes($catTree, $displayItems);
            echo json_encode( array_values($catTree) ); // Important to have array_values() here after filtering hidden nodes
            exit;
        }

        /**
         * Sets which items on the tree to hide/display
         */
        function setTreeDisplayAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 403 Security Check.");
                die('Security Check');
            }

            if( empty($_POST['widgetid']) ){
                header("HTTP/1.0 409 Could find widget ID to update.");
                exit;
            }

            $widgetId = (int) $_POST['widgetid'];
            $displayItems = $_POST['displayItems'];

            $sp_treeWidgets = get_option( 'widget_sp_posttreewidget' );
            $sp_treeWidgets[$widgetId]['displayItems'] = $displayItems;

            $success = update_option( 'widget_sp_posttreewidget', $sp_treeWidgets );

            if( $success ){
                echo json_encode( array( 'success' => $success ) );
            }else{
                header("HTTP/1.0 409 Could update the SP Post Tree widget successfully.");
                exit;
            }

            exit;
        }
	}
}