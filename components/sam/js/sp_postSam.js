/**
 * JS sp_postSam Component class
 * Used alongside sp_postSamAJAX for AJAX calls
 * Used in front-end posts
 *
 * @version 1.0
 * @author Rafi Yagudin <rafi.yagudin@tufts.edu>
 * @project SmartPost 
 */

(function($){
    var videoObj = { "video": true };
    var errBack = function(error) {
                console.log("Video capture error: ", error.code); 
            };
    var webCamStream;
    
    smartpost.sp_postSam = {
        
        /**
         * Required for all post component JS objects.
         * Used in sp_globals.SP_TYPES to determine which
         * methods to call for different post component types
         */
        setTypeID: function(){
            if(sp_globals){
                var types = sp_globals.SP_TYPES;

                //!Important - the raw name of the type
                if(types['SAM']){
                    this.typeID = types['SAM'];
                    sp_globals.SP_TYPES[this.typeID] = this;
                }
            }else{
                return 0;
            }
        },

        /**
         * Returns true if content component is empty, otherwise false
         *
         * @param object component The component
         * @return bool True if it's empty, otherwise false
         */
        isEmpty: function(component){
            var compID = $(component).attr('data-compid');
            return $(component).find('#samFramesIndicator-' + compID).charAt(0) == '0';
        },

        /**
         * Saves a SAM component's content to the database.
         *
         * @param string    content   The content to be saved
         *    content is {'fps', 'frames', 'startFrame'};
         *    the database will update the desired fps every time in case user changes it
         * @param string    contentID The DOMElem id of the content's container
         */
        saveSamContent: function(content, contentID){
            var thisObj = this;
            console.log(content);
            var compID = contentID.split('-')[1];
            var postID = $("#sp_sam-"+compID);
            postID = postID[0].getAttribute("data-postid");
            $.ajax({
                url  : SP_AJAX_URL,
                type : 'POST',
                data : {
                    action: 'saveSamAJAX',
                    nonce: SP_NONCE,
                    compID: compID,
                    postID: postID,
                    fps: content.fps,
                    img: content.frame,
                    frameNum: content.frameNum
                },
                dataType : 'html',
                success: function(data) {
                    console.log(data);
                },
                error    : function(jqXHR, statusText, errorThrown){
                    if(smartpost.sp_postComponent)
                        smartpost.sp_postComponent.showError('Status: ' + statusText + ', Error Thrown:' + errorThrown);
                }
            });
        },

        /**
         * Initializes editors for a specific component. Used when created a new element.
         * @see smartpost.sp_postComponent.initComponent()
         * @param component
         * @param postID
         * @param autoFocus
         */
        initComponent: function(component, postID, autoFocus){
            // some things we'll need...
            var self = $(component);
            var emptyVideo = self.find( '.samEmptyVideo' )[0],
                canvas = self.find( '.samCanvas' )[0],
                context = canvas.getContext( '2d' ),
                overlay = self.find( '.samOverlay' )[0],
                parent = self.parents( '.sp_sam' ),
                samContainer = self.find( '.samContainer' ),
                frames = [],    // hold frames (ImageData) in browser for later saving
                fps = 5,        // maybe add ability for user to adjust this
                maxFrames = 60, // this should be option to set in backend
                playing = false,
                showOverlay = true,
                width = 440,
                height = 330,
                submittedFrames = 0,    // number of frames already sent to database
                filledTimeline = self.find( '.samFilledTime' )[0],
                emptyTimeline = self.find( '.samEmptyTime' )[0],
                videoContainer = self.find( '.samVideoContainer' ),
                recButton = self.find( '.samRecordFrame' ),
                playButton = self.find( '.samPlayButton' ),
                redoButton = self.find( '.samRedoButton' ),
                toggleButton = self.find( '.samToggleOverlay' ),
                framesIndicator = self.find( '.samFramesIndicator' )[0];
            var obj = this;
            
            (function checkspacing(i){
                // make sure element isn't offscreen to right
                // needed if SP is in right sidebar
                //  need to call twice because offset.left is 0
                //  when first loaded if used as default SP object
                if (i < 2) {
                    var x = samContainer.offset().left,
                        y = samContainer.width(),
                        w = window.innerWidth;
                    i++;
                    if ( x + y > w )
                        samContainer.css("float", "right");
                    else
                        setTimeout( function() { checkspacing(i); }, 200);
                }
            })(0);
                
            // make sure visibility is correct, show live stream
            var initVideoStream = function() {
                canvas.style.display = "block";
                overlay.style.display = "block";
                emptyVideo.style.display = "none";
                
                // this is the desired interval between frames [ms]
                // try to account for time taken on other things
                var interval = 50;
                var start = new Date().getTime();
                
                // Every interval, copy the video image to the canvas
               setInterval(function() {
                  if (video.paused || video.ended || playing) return;
                  context.fillRect(0, 0, width, height);
                  context.drawImage(video, 0, 0, width, height);
               }, interval-((new Date().getTime()-start)%interval));
            };
            
            // get video object -- only one per page
            // each individual sam object pulls from video to its own canvas
            var video = $.find( '#samVideo' )[0];
            if (video === undefined) {
                // create element in parent if it doesn't exist
                self.parents( '.sp-quickpost-form' ).append('<video id="samVideo" width="440" height="330" autoplay style="display:none"></video>');
                video = $.find( '#samVideo' )[0];
                
                // get access to web cam, store reference to stream so we can stop it later
                if(navigator.getUserMedia) { // Standard
                    console.log('standard');
                    navigator.getUserMedia(videoObj, function(stream) {
                        initVideoStream();
                        webCamStream = stream;
                        video.src = stream;
                        video.play();
                    }, errBack);
                } 
                else if(navigator.webkitGetUserMedia) { // WebKit-prefixed
                    console.log('webkit');
                    navigator.webkitGetUserMedia(videoObj, function(stream){
                        initVideoStream();
                        webCamStream = stream;
                        video.src = window.webkitURL.createObjectURL(stream);
                        video.play();
                    }, errBack);
                }
                else if(navigator.mozGetUserMedia) { // Firefox-prefixed
                    console.log('firefox');
                    navigator.mozGetUserMedia(videoObj, function(stream){
                        initVideoStream();
                        webCamStream = stream;
                        video.src = window.URL.createObjectURL(stream);
                        video.play();
                    }, errBack);
                }
            }
            else if ( video.readyState ) {
                // if video already existed, start showing it
                initVideoStream();
            }
            
            
            // shows and hides video controls as necessary
            videoContainer.on({
                mouseover: function(){
                    if (canvas.style.display != "none") {
                        recButton.fadeTo(50, 0.5);
                        playButton.fadeTo(50, 0.5);
                    }
                },
                mouseleave: function(){
                    if (canvas.style.display != "none") {
                        recButton.fadeTo(100, 0);
                        playButton.fadeTo(100, 0);
                    }
                }
            });
            
            // start video stream again if needed -- maybe unnecessary???
            video.addEventListener('play', function() {
               initVideoStream();
            });
            
            // toggles display of the last frame overlay on the video
            var toggleOverlay = function() {
                showOverlay = !showOverlay;
                var root = SP_PLUGIN_PATH+"components/sam/images/";
                if (showOverlay) {
                    overlay.style.display = "block";
                    toggleButton[0].style.backgroundImage="url("+root+"onion_on.png)";
                }
                else {
                    overlay.style.display = "none";
                    toggleButton[0].style.backgroundImage="url("+root+"onion_off.png)";
                }
            };
    
            // calculates the overlay from the last frame
            var renderOverlay = function(toggled) {
                if (toggled)
                    toggleOverlay();
                // if we can and are supposed to show the overlay
                if (frames.length > 0 && showOverlay) {
                    var ctx = overlay.getContext('2d');
                    if ( !(toggled && ctx.getImageData(0,0,1,1).data.length < 0) ) {
                        // get the last frame
                        var lastFrame = frames[frames.length-1];        
                        // we need to do a deep copy
                        var imageData = ctx.createImageData(width, height);
                        imageData.data.set(lastFrame.data);
                        // set alpha channel so image is translucent
                        // alpha is in range [0-255]:[transparent-opaque]
                        for(var i = 0; i < imageData.data.length; i+=4) {
                            imageData.data[i+3] = 100;
                        }
                        // draw image
                        ctx.putImageData(imageData, 0, 0);
                    }
                }
            };
            
            // set visibility of progress bar to reflect number of frames, max frame length
            var setProgressBar = function() {
                var percentFull = frames.length / maxFrames;
                var empty = 400 - 400*percentFull;
                empty = String(empty)+"px";
                emptyTimeline.style.width = empty;
            };
            
            toggleButton.on("click", function() {
                if (!playing) {
                    // toggle state
                    renderOverlay(true);
                }
            });
            
            // Trigger photo take
            recButton.on("click", function() { 
                if (video.readyState && frames.length < maxFrames && !playing) {
                    frames.push(context.getImageData(0, 0, width, height));
            
                    var numFrames = String(frames.length) + "/" + String(maxFrames);
                    framesIndicator.innerHTML = numFrames;
            
                    setProgressBar();
                    renderOverlay(false);
                    
                    // send frame to server
                    var compId = this.id;
                    var frame = convertImageDataToURL(frames[frames.length-1]);
                    content = {'fps': fps, 'frame': frame, 'frameNum': submittedFrames};
                    obj.saveSamContent(content, compId);
                    submittedFrames++;
                }
            });
            
            // Play back all frames taken so far
            playButton.on("click", function() { 
                if (!playing) {
                    // start playing the movie
                    console.log("playing video");
                    var overlay = showOverlay;
                    if (overlay)
                        toggleOverlay();
                    playing = true;
                    var i = 0;
                    var len = frames.length;
                    var start = new Date().getTime();
                    (function playCallback(i) {
                        if (i < len && playing) {
                            context.putImageData(frames[i], 0, 0);
                            i++;
                            // this is the desired interval between frames [ms]
                            // try to account for time taken on other things
                            var interval = 1000/fps;
                            setTimeout(function() {
                                 playCallback(i);
                            }, interval-((new Date().getTime()-start)%interval) );
                        }
                        else {
                            // stop movie, show live stream
                            playing = false;
                            initVideoStream();
                            renderOverlay(true);
                            if (!overlay)
                                toggleOverlay();
                        }
                    })(i);
                }
                else {
                    // tell loop to stop playing
                    playing = false;
                }
            });
            
            //clear all frames taken so far
            redoButton.on("click", function() {
                playing = false;
                frames = [];
                setProgressBar();
                submittedFrames = 0;
                framesIndicator.innerText = "0/"+String(maxFrames);
                overlay.getContext("2d").clearRect( 0, 0, width, height );
            });
            
            // we want to turn off webcam stream if this is the last one
            self.on("remove", function () {
                var samObjs = $( '.sp_sam' );
                if ( samObjs.length <= 1 ) {
                    webCamStream.stop();
                    video.remove();
                }
            });
            
            // return a base64 encoded string of the image data
            var convertImageDataToURL = function(imageData) {
                var c, ctx;
                c = document.createElement('canvas');
                c.width = width;
                c.height = height;
                ctx = c.getContext( '2d' );
                ctx.putImageData(imageData, 0, 0);
                c.remove();
                return c.toDataURL();
            };
            
            var editor = $(component).find( '.sp-editor-content' );
            smartpost.sp_post.initCkEditors(editor);
        },

        /**
         * Statically initializes all media components on document.ready
         */
        init: function(){
            this.setTypeID();
            
            
            
        }
    }

    $(document).ready(function(){
        smartpost.sp_postSam.init();
    });
})(jQuery);