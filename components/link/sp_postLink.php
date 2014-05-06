<?php 
if (!class_exists("sp_postLink")) {
	/**
	 * Extends sp_postComponent
	 * 
	 * @see sp_postComponent
	 */	
	class sp_postLink extends sp_postComponent{
			
        private $url;
        private $urlTitle;
        private $urlType; // MIME type of url, text/html, application/pdf, image/png, image/jpg, or image/gif
        private $urlInfo; // Used with parse_url to capture url information
        private $urlThumbs = array(); // Array of thumbnails for the url provides
        private $urlThumb; // The "selected" thumbnail (a wp attachment ID)
        private $urlDesc; // Description of the url
        public $youTube = ''; // Whether the link provided is a youtube.com link

        function __construct($compID = 0, $catCompID = 0, $compOrder = 0,
                               $name = '', $value = '', $postID = 0){
            $compInfo = compact("compID", "catCompID", "compOrder", "name", "value", "postID");
            $this->initComponent($compInfo);

            // Define URL properties
            if( !empty($this->value) ){
                $this->url = $this->value->url;
                $this->urlThumb = $this->value->urlThumb;
                $this->urlDesc = $this->value->urlDesc;
                $this->urlType = $this->value->urlType;
                $this->youTube = $this->value->youTube;
            }
        }

        /**
         * @see parent::renderEditMode()
         */
        function renderEditMode($value = ""){
            $html = '';
            $html .= '<div id="sp-link-content-' . $this->ID . '" class="sp-link-content">';

                if( !$this->youTube ){ // Special case for YouTube videos
                    $html .= empty( $this->urlThumbs ) ? $this->renderThumb() : $this->renderThumbs();
                }

                $html .= '<div id="sp_link_right_wrapper-' . $this->ID . '" class="sp_link_right_wrapper">';
                    $html .= $this->renderDesc();
                    $html .= $this->renderYouTubePlayer();
                    $placeholder = empty($this->url) ? 'sp-link-placeholder' : '';
                    $html .= $this->youTube ? '<div id="sp-link-youtube-link-' . $this->ID . '" class="sp-link-youtube-link">' : '';
                    $html .= '<div id="sp_the_link-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp_the_link editable sp_textIcon ' . $placeholder . '">';
                        $html .= $this->url;
                    $html .= '</div>';
                    $html .= $this->youTube ? '</div>' : ''; // end sp-link-youtube-link
                    $html .= '<input type="hidden" id="sp-link-url-' . $this->ID . '" name="sp-link-url-' . $this->ID . '" value="' . $this->url . '">';
                $html .= '</div><!-- .sp_link_right_wrapper -->';
                $html .= '<div class="clear"></div>';
            $html .= '</div>';
            return $html;
        }

        /**
         * @see parent::renderViewMode()
         */
        function renderViewMode(){
            $html = '';
            $html .= '<div id="sp_link-' . $this->ID . '" class="sp_link">';
                $html .= '<div id="thumbAndDesc-' . $this->ID . '" class="sp_link_content">';

                    if( !$this->youTube ){ // Special case for YouTube videos
                        $html .= $this->renderThumb(false);
                    }

                    $html .= '<div id="sp_link_right_wrapper-' . $this->ID . '" class="sp_link_right_wrapper">';
                        $html .= $this->renderDesc(false);
                        $html .= $this->renderYouTubePlayer();
                        $html .= $this->youTube ? '<div id="sp-link-youtube-link-' . $this->ID . '" class="sp-link-youtube-link">' : '';
                            $html .= '<a href="' . $this->url .'" target="_blank" class="sp-link-view">' . $this->url . '</a>';
                        $html .= $this->youTube ? '</div>' : ''; // end sp-link-youtube-link
                    $html .= '</div><!-- .sp_link_desc -->';
                    $html .= '<div class="clear"></div>';
                $html .= '</div>';
            $html .= '</div>';

            return $html;
        }
			
        /**
         * @see parent::renderPreview()
         */
        function renderPreview(){
            return $this->renderDesc(false);
        }

        /**
         * Renders the selected thumbnail if one exists
         * @param bool $editMode
         * @return string the link thumbnails
         */
        function renderThumb( $editMode = true ){
            $html = "";
            $img = wp_get_attachment_image($this->urlThumb, null, false);
            if( $img && !$this->youTube ){
                $html .= '<div id="sp_link_thumb-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp_link_' . $this->urlType . '">';
                    $html .= '<a href="' . $this->url . '" class="sp_link_thumb_link thickbox">';
                        $html .= $img;
                    $html .=	'</a>';
                    $html .= '<div class="clear"></div>';
                $html .= '</div>';
            }

            return $html;
        }

        /**
         * Renders possible thumbnails to pick from, should be used after processHTML()
         * @return string the link thumbnails
         */
        function renderThumbs(){
            $html = "";
            if( !empty($this->urlThumbs) ){

                $html .= '<div id="sp_link_thumbs-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp_link_thumbs">';
                    $html .= '<div id="thumb_results-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp-link-thumbs-results">';
                    $count = 0;

                    foreach($this->urlThumbs as $thumbID => $thumb){
                        $hide = ($count > 0) ? 'style="display: none;"' : '';
                        $html .= '<a href="' . $this->url . '" class="sp_link_thumb_link thickbox">';
                            $html .= '<img src="' . $thumb . '" ' . $hide . ' data-thumbid="' . $thumbID . '" />';
                        $html .= '</a>';
                        $count++;
                    }
                    $html .= '</div><!-- end #thumb_results-' . $this->ID . ' -->';

                $html .= '<div class="clear"></div>';

                if( count($this->urlThumbs) > 1 ){
                    $html .= '<div id="thumbSelection-' . $this->ID . '" class="thumbSelection">';
                        $html .= '<span type="button" id="prevThumb-' . $this->ID . '" class="sp_link_prev"></span>';
                        $html .= '<button type="button" id="selectThumb-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp_link_select_thumb">Select thumb</button>';
                        $html .= '<span type="button" id="nextThumb-' . $this->ID . '" class="sp_link_next"></span>';
                    $html .= '</div>';
                }

                $html .= '<div class="clear"></div>';
                $html .= '</div><!-- end #sp_link_thumbs-' . $this->ID . ' -->';

            }else{ //No Thumbnails were found
                $html .= '<div id="sp_link_thumb-' . $this->ID . '" data-compid="' . $this->ID . '" class="sp_link_' . $this->urlType . ' emptyLinkThumb">';
                    $html .= '<br />Drag and drop your own thumbnail!<br />';
                $html .= '</div>';
            }

            return $html;
        }

        /**
         * Renders the link's description
         * @param bool $editMode
         * @return string the link description
         */
        function renderDesc( $editMode = true ){
            $html = '';
            if( $editMode && !empty( $this->url ) ){
                $html .= '<div id="sp_link_desc-' . $this->ID . '" class="sp_link_desc">';
                $html .= sp_core::sp_editor(
                    $this->urlDesc,
                    $this->ID,
                    false,
                    'Click to add a description...',
                    array( 'data-action' => 'saveLinkDescAJAX', 'data-compid' => $this->ID )
                );
                $html .= '</div> <!-- end .sp_link_desc -->';
            }else if( !empty($this->urlDesc) ){
                $html .= '<div id="sp_link_desc-' . $this->ID . '" class="sp-link-desc-view">';
                    $html .= $this->urlDesc;
                $html .= '</div> <!-- end .sp_link_desc -->';
            }

            return $html;
        }

        /**
         * Embed a YouTube player if the URL provided is a YouTube video
         */
        function renderYouTubePlayer(){
            if( $this->youTube ){
                $html = '<div id="sp-link-youtube-' . $this->ID . '" class="sp-link-youtube-player">';
                    $html .= '<iframe width="560" height="315" src="//www.youtube.com/embed/' . $this->youTube . '" frameborder="0" allowfullscreen></iframe>';
                $html .= '</div>';
                return $html;
            }
        }

        static function init(){
            require_once('ajax/sp_postLinkAJAX.php');
            require_once('simple_html_dom.php');
            sp_postLinkAJAX::init();
            self::enqueueCSS();
            self::enqueueJS();
        }

        static function enqueueJS(){
            wp_register_script( 'sp_postLinkJS', plugins_url('/js/sp_postLink.js', __FILE__), array( 'jquery', 'sp_globals', 'sp_postComponentJS' ));
            wp_enqueue_script( 'sp_postLinkJS' );
        }

        static function enqueueCSS(){
            wp_register_style( 'sp_postLinkCSS', plugins_url('/css/sp_postLink.css', __FILE__));
            wp_enqueue_style( 'sp_postLinkCSS' );
        }
				
        /**
         * @see parent::update();
         */
        function update(){
            $urlData = new stdClass();
            $urlData->url = $this->url;
            $urlData->urlThumb = $this->urlThumb;
            $urlData->urlDesc = sanitize_text_field( $this->urlDesc );
            $urlData->urlType = $this->urlType;
            $urlData->youTube = $this->youTube;
            $serializedData = maybe_serialize( $urlData );
            return sp_core::updateVar('sp_postComponents', $this->ID, 'value', $serializedData, '%s');
        }

        /**
         * @see parent::isEmpty();
         */
        function isEmpty(){
            return empty($this->url);
        }

        /**
         * Set the link / url
         *
         * @param string $url The url/link
         * @return bool|Object True on success, otherwise a WP_Error object
         */
        function setLink($url){
            $url = esc_url_raw($url);

            // Remove any previous thumbnails as we're looking up a new URL
            $this->removeThumb();

            // If it's empty, set everything to empty
            if( empty($url) ){
                $this->url = "";
                $this->urlDesc = "";
                return $this->update();
            }

            // Otherwise parse the URL and update it
            $success = $this->parseURL($url);

            if( is_wp_error($success) ){

                // Set the url even if we get an error, should still link to URL even if it fails test
                $this->url = $url;
                $this->urlDesc = $url;
                $this->update();
                return $success;

            }else{
                return $this->update();
            }
        }

        /**
         * Parses a url and calls up the a processing function depending on the MIME type
         * @param $url
         * @return bool|obj|WP_Error True if succesfully parsed, otherwise a WP_Error object.
         */
        private function parseURL($url){

            $status = $this->is_url($url); // Check if it's a properly formatted URL

            if($status == false || empty($status)){
                return new WP_Error('broke', ('Warning: could not resolve ' . $url));
            }

            // If we were redirected
            if( is_string($status) ){
                $url = $status;
            }

            $this->urlInfo = parse_url($url);
            $type = $this->url_type($url);

            // In case we get wierd text/html; charset = blah types
            $isTextHtml = strpos($type, 'text/html');
            if($isTextHtml !== false){
                $type = 'text/html';
            }

            switch($type){
                case 'text/html':
                    $success = $this->processHTML($url);
                    $this->urlThumb = $this->downloadUrlThumb($this->urlThumbs[0]);	//Make the selected url the first one by default
                    break;

                case 'image/png':
                    $success = $this->processImage($url);
                    $this->urlDesc  = $this->urlInfo['scheme'] . '://' . $this->urlInfo['host'];
                    $this->urlType  = 'img';
                    $this->urlThumb = $this->downloadUrlThumb($this->urlThumbs[0]);
                    break;

                case 'image/jpeg':
                    $success = $this->processImage($url);
                    $this->urlDesc  = $this->urlInfo['scheme'] . '://' . $this->urlInfo['host'];
                    $this->urlType  = 'img';
                    $this->urlThumb = $this->downloadUrlThumb($this->urlThumbs[0]);
                    break;

                case 'image/gif':
                    $success = $this->processImage($url);
                    $this->urlDesc  = $this->urlInfo['scheme'] . '://' . $this->urlInfo['host'];
                    $this->urlType  = 'img';
                    $this->urlThumb = $this->downloadUrlThumb($this->urlThumbs[0]);
                    break;

                case 'application/pdf':
                    $success = $this->processPDF($url);
                    break;

                default:
                    return new WP_Error('broke', ('Could not decipher type of URL correctly.'));
            }

            $this->url = $url;

            return $success;
        }
			
        /**
         * Downloads the URL thumbnail (if one exists) and creates
         * a wordpress attachment from it. It will also set the thumb as the featured
         * image of the current post if no featured is set yet.
         *
         * @param string $thumbURL A valid URL pointing to a remote image file (.jpeg, .jpg, .png or .gif)
         * @return int|Boolean Attachment ID on success, otherwise false
         */
        function downloadUrlThumb($thumbURL){
            if( empty($thumbURL) ){
                return false;
            }

            $size = getimagesize($thumbURL);

            if($size){

                $ext = image_type_to_extension($size[2]);
                $postID   = $this->getPostID();
                $uploads  = wp_upload_dir();
                $filename = $uploads['path'] . '/linkThumb_' . date(ymdhs) . $ext;

                switch($ext){
                    case ".gif":
                        $img = imagecreatefromgif($thumbURL);
                        imagegif($img, $filename);
                        break;
                    case ".jpeg":
                        $img = imagecreatefromjpeg($thumbURL);
                        imagejpeg($img, $filename);
                        break;
                    case ".jpg":
                        $img = imagecreatefromjpeg($thumbURL);
                        imagejpeg($img, $filename);
                        break;
                    case ".png":
                        $img = imagecreatefrompng($thumbURL);
                        imagepng($img, $filename);
                        break;
                    default:
                        $img = false;
                        break;
                }

                // Create an attachment!
                $attach_id = sp_core::create_attachment( $filename, $this->postID, 'Link Thumbnail', get_current_user_id() );

                // Set featured image if it's not already set
                if( !has_post_thumbnail($postID) ){
                    set_post_thumbnail($postID, $attach_id);
                }

                return $attach_id;
            }else{
                return false;
            }
        }
			
        /**
         * Attempts to retrieve headers of the specified url. If headers
         * successfully retrieved and http status of 200 verified, then url is valid
         *
         * @see http://www.ajaxapp.com/2009/03/23/to-validate-if-an-url-exists-use-php-curl/
         * @param string $url The URL string
         * @return bool|string True if $url returned a 200 status code, otherwise false.
         *																					In the case of redirects, returns the final location.
         */
        function is_url($url){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3); // redirect a max of 3 times
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // five second timeout
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $data = curl_exec($ch);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            /* Used for debugging
            if($data === false){
                echo 'Curl error: ' . curl_error($ch);
            }
            */

            curl_close($ch);
            preg_match("/HTTP\/1\.[1|0]\s(\d{3})/", $data, $matches);

            // In case we have been redirected
            if($matches[1] == '301' || $matches[1] == '302'){
                return $effectiveUrl;
            }
            return ($matches[1] == '200');
        }

        /**
         * Figure out what the mime type of the url (i.e. if it's a PDF, Image, or just a HMTL page)
         * @param $url
         * @return mixed|WP_Error
         */
        function url_type($url){
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);

            if( curl_errno($ch) > 0 ){
                $curl_error = curl_error($ch);
                return new WP_Error('broke', ($curl_error));
            }
            $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
            return $type;
        }

        /**
         * Parses a url, looks for thumbnails and a description
         * @param $url
         * @return bool|WP_Error - True if successfully parsed, otherwise a WP_Error object.
         */
        function processHTML($url){
				
            // Get the type of URL

            $html = file_get_html($url);

            if($html === false){
                return new WP_Error('broke', ('Could not load html content.'));
            }
            $this->urlType = 'html';

            // Set the urlTitle
            $urlTitle = $html->find('title');
            $this->urlTitle = $urlTitle[0]->innertext;

            // Try and find the description
            $urlDesc = $html->find('meta[name=description]');
            $this->urlDesc = $urlDesc[0]->content;

            if( empty($this->urlDesc) ){
                $urlDesc = $html->find('meta[name=Description]');
                $this->urlDesc = $urlDesc[0]->content;
            }

            // If desc is still empty, try and use the title as the description
            if( empty($this->urlDesc) ){
                $this->urlDesc = $this->urlTitle;
            }

            // If it's still empty, just use the url as a description
            if( empty($this->urlDesc) ){
                $this->urlDesc = $this->urlInfo['scheme'] . '://' . $this->urlInfo['host'];
            }

            // YouTube videos
            if( $this->urlInfo['host'] == 'www.youtube.com' && !empty( $this->urlInfo['query'] ) ){
                $query = $this->urlInfo['query'];
                parse_str($query);

                for($i = 0; $i < 4; $i++){
                    array_push($this->urlThumbs, 'http://img.youtube.com/vi/' . $v . '/' . $i . '.jpg');
                }
                $this->youTube = $v;
                return true;
            }else{
                $this->youTube = '';
            }

            // Try and find any thumbnails
            $linkThumbs = $html->find('link[rel=image_src]');
            if( !empty($linkThumbs) ){
                foreach($linkThumbs as $linkThumb){
                    $this->processImage($linkThumb->href);
                }
            }

            $metaItemPropImages = $html->find('meta[itemprop=image]');
            if( !empty($metaItemPropImages) ){
                foreach($metaItemPropImages as $itemPropImg){
                    $this->processImage($itemPropImg->content);
                }
            }

            //Find more images if urlThumbs count is low
            if(count($this->urlThumbs) < 3){
                $thumbs = $html->find('img');
                if( !empty($thumbs) ){
                    $count = 0;
                    foreach($thumbs as $thumb){
                        $this->processImage($thumb->src);
                        $count++;

                        //Limit search to 15 tries to limit wait time
                        if( $count > 15)
                            break;
                    }
                }
            }

            //If we've reached this far, return true
            return true;
        }
			
        /**
         * Validates image url and adds it to $this->urlThumbs if it passes
         *
         * @todo Download the image and create a thumbnail for optimization
         * @param string $img_url the image url
         * @return bool True on success, false otherwise
         */
        private function processImage($img_url){

            //check for relative urls starting with '//'
            if(preg_match('/^\/\//', $img_url))
                 $img_url = $this->urlInfo['scheme'] . ':' . $img_url;

            //check for relative urls starting with '/'
            if(preg_match('/^\//', $img_url))
                $img_url = $this->urlInfo['scheme'] . '://' . $this->urlInfo['host'] . $img_url;

            //If it's still not a valid URL, forget about it..
            if($this->is_url($img_url) == false)
                    return false;

            //Try and get the image size
            $size = getimagesize($img_url);

            //If it's not an image, quit
            if(!$size)
            return false;

            //Filter out images less than 75px wide
            if($size[0] < 75)
            return false;

            //Otherwise add the image to $urlThumbs
            array_push( $this->urlThumbs, $img_url );
            return true;
        }
			
        /**
         * Processes the application/pdf header
         */
        function processPDF(){
            $this->urlType = 'pdf';
            $this->urlThumb = includes_url() . 'images/crystal/document.png';
            $this->urlThumbs = array( 0 => $this->urlThumb );
            $this->urlDesc = 'Application/PDF';
            return true;
        }

        /**
         * Overloads parent delete()
         * Necessary to remove any thumbnails lying around..
         */
        function delete(){
            global $wpdb;

            if( !empty($this->urlThumb) ){
                if(is_numeric($this->urlThumb)){
                    wp_delete_attachment($this->urlThumb, true);
                }
            }

            $tableName = $wpdb->prefix . 'sp_postComponents';
                return $wpdb->query( $wpdb->prepare("DELETE FROM $tableName WHERE id = %d", $this->ID )
            );
        }
			
        function setUrlDesc($desc){
            $this->urlDesc = (string) $desc;
        }

        /**
         * Sets the url thumb via the urlThumbs array
         */
        function setUrlThumbByID($thumbID){
            $this->urlThumb = $this->urlThumbs[$thumbID];
        }
			
        /**
         * Removes the current thumb and set $this->urlThumb to 0
         * @uses wp_delete_attachment()
         */
        function removeThumb(){
            if( !empty($this->urlThumb) ){
                // Remove the featured thumb if it's our thumbnail
                if( get_post_thumbnail_id( $this->postID ) == $this->urlThumb ){
                    delete_post_thumbnail( $this->postID );
                }
                wp_delete_attachment($this->urlThumb, true);
            }
            $this->urlThumb = 0;
        }

        function setUrlThumb($thumb){
            $this->urlThumb = (int) $thumb;
        }

        function getUrlThumb(){
            return $this->urlThumb;
        }

        function getUrlType(){
            return $this->urlType;
        }

        function getUrl(){
            return $this->url;
        }
	}
}
