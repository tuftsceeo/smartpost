<?php
if (!class_exists("sp_postAJAX")) {
	/**
	 * Handles AJAX calls for sp_post class
	 */
	class sp_postAJAX{
		
        public static function init(){
            add_action('wp_ajax_savePostTitleAJAX', array('sp_postAJAX', 'savePostTitleAJAX'));
            add_action('wp_ajax_sp_searchTagsAJAX', array('sp_postAJAX', 'sp_searchTagsAJAX'));
            add_action('wp_ajax_sp_addNewTagAJAX', array('sp_postAJAX', 'sp_addNewTagAJAX'));
            add_action('wp_ajax_sp_removeTagAJAX', array('sp_postAJAX', 'sp_removeTagAJAX'));
        }

        /**
         * Saves the post title
         */
        function savePostTitleAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                die('Security Check');
            }

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could not find postID.");
                exit;
            }

            if( empty($_POST['post_title']) ){
                header("HTTP/1.0 409 Post title is empty. Please fill in a post title!");
                exit;
            }

            $postID    = (int) $_POST['postID'];
            $postTitle = (string) stripslashes_deep($_POST['post_title']);

            $post = get_post($postID);

            if(is_null($post)){
                header("HTTP/1.0 409 Could not successfully load post.");
                exit;
            }

            $post->post_title = $postTitle;
            $success = wp_update_post($post);
            if($success === 0){
                header("HTTP/1.0 409 Could not successfully update the post title.");
                exit;
            }

            echo json_encode(array('success' => true, 'details' => 'Post title successfully updated', 'postID' => $postID));
            exit;
        }

        /**
         * Searches all the tags in the WP instance
         */
        function sp_searchTagsAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                exit;
            }

            if( empty($_POST['tagRequest']) ){
                header("HTTP/1.0 409 Could not find tag search request.");
                exit;
            }

            $searchTerm = (string) stripslashes_deep( $_POST['tagRequest']['term'] );

            $args = array(
                'orderby'    => 'name',
                'search'     => $searchTerm,
                'hide_empty' => false
            );

            $tags = get_tags($args);
            $results = array();
            foreach($tags as $tag){
                array_push($results, $tag->name);
            }

            echo json_encode( $results );
            exit;
        }

        /**
         * Adds a new tag to a post
         */
        function sp_addNewTagAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                exit;
            }

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could not find postID.");
                exit;
            }

            if( empty($_POST['tag']) ){
                header("HTTP/1.0 409 Could not find tag request.");
                exit;
            }

            if( !current_user_can('edit_posts') ){
                header("HTTP/1.0 409 Security Check.");
                exit;
            }

            $postID = (int) $_POST['postID'];
            $tag    = (string) stripslashes_deep( $_POST['tag'] );

            $success = wp_set_post_tags($postID, array($tag), true);

            if($success === false){
                header("HTTP/1.0 409 Could not add tag to post.");
                exit;
            }else{
                $results = get_tags( array('search' => $tag, 'number' => 1, 'hide_empty' => false) );
                $theTag  = $results[0];
                echo json_encode( array('success' => true, 'tag' => $tag, 'tagID' => $theTag->term_id, 'tagLink' => get_tag_link($theTag->term_id)) );
            }
            exit;
        }

        /**
         * Removes a tag from a post
         */
        function sp_removeTagAJAX(){
            $nonce = $_POST['nonce'];
            if( !wp_verify_nonce($nonce, 'sp_nonce') ){
                header("HTTP/1.0 409 Security Check.");
                exit;
            }

            if( empty($_POST['postID']) ){
                header("HTTP/1.0 409 Could not find postID.");
                exit;
            }

            if( empty($_POST['tagID']) ){
                header("HTTP/1.0 409 Could not find tagID.");
                exit;
            }

            if(!current_user_can('edit_posts')){
                header("HTTP/1.0 409 Security Check.");
                exit;
            }

            $postID = (int) $_POST['postID'];
            $tagID  = (string) stripslashes_deep( $_POST['tagID'] );

            $tags = get_the_tags($postID);
            $newTagArray = array();
            foreach($tags as $tag){
                if($tag->term_id != $tagID){
                    array_push($newTagArray, $tag->name);
                }
            }

            $success = wp_set_post_tags($postID, $newTagArray, false);

            if($success === false){
                header("HTTP/1.0 409 Could not remove tag.");
                exit;
            }else{
                echo json_encode( array('success' => true) );
            }
            exit;
        }
		
	}
}
?>