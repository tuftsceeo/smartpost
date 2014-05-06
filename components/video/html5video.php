<?php
/**
 * User: ryagudin
 * Date: 8/14/13
 * Time: 1:17 PM
 * Converts a video file to .webm and .mp4 formats using ffmpeg.
 */

// Collect video info arguments
$ARGS = array(
    'BASE_PATH' => $argv[1], // Base directory of WordPress
    'POST_ID'   => $argv[2], // The post ID of where the video was uploaded
    'VID_FILE'  => $argv[3], // Path of the file to be converted
    'COMP_ID'   => $argv[4], // The SmartPost component ID
    'AUTH_ID'   => $argv[5], // The author ID
    'WIDTH'     => $argv[6], // scaled video width
    'HEIGHT'    => $argv[7]  // scaled video height
);

// Collect info on wpmu
$WPMU_ARGS = array(
    'HTTP_HOST' => $argv[8],
    'BLOG_ID'   => $argv[9],
    'IS_WPMU'   => $argv[10]
);

// If this is a WPMU instance, switch to the blog we're on
if( !empty( $WPMU_ARGS['IS_WPMU'] ) ){

    $_SERVER['HTTP_HOST'] = $WPMU_ARGS['HTTP_HOST'];
    ini_set('display_errors', true);
    define( 'BASE_PATH', $ARGS['BASE_PATH'] );
    global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
    require( BASE_PATH . 'wp-load.php' );
    switch_to_blog( $WPMU_ARGS['BLOG_ID'] );

} else {
    require_once( $ARGS['BASE_PATH'] . 'wp-load.php' );
}

// Get full ffmpeg path
$sp_ffmpeg_path = get_site_option('sp_ffmpeg_path');

// Check that everything is in order before we start converting..
if( $ARGS['VID_FILE'] && $ARGS['POST_ID'] && !is_wp_error( $sp_ffmpeg_path ) ){

    $file_info = pathinfo( $ARGS['VID_FILE'] );
    $name = $file_info['filename'];
    $path = dirname( $ARGS['VID_FILE'] );

    $filename = $path . DIRECTORY_SEPARATOR . $name;

    if(DEBUG_SP_VIDEO){
        echo 'ABSPATH: ' . $base_path . PHP_EOL;
        echo 'name: ' . $name . PHP_EOL;
        echo 'path: ' . $path . PHP_EOL;
        echo 'filename: ' . $filename . PHP_EOL;
        echo 'postID: ' . $ARGS['POST_ID'] . PHP_EOL;
        echo 'compID: ' . $ARGS['COMP_ID'] . PHP_EOL;
    }

    $mp4_filename  = $filename . '.mp4';
    $png_filename  = $filename . '.png';
    $content = $filename;

    $videoComponent = new sp_postVideo( $ARGS['COMP_ID'] );

    if( is_wp_error($videoComponent->errors) ){
        echo 'Could not instantiate sp_postVideo with component ID: ' . $ARGS['COMP_ID'] . ', ' . $videoComponent->errors->get_error_message();
        exit();
    }

    /**
     * Get the correct orientation of the quick time video if it exists
     * @todo Check if ffprobe and grep are callable on the operating system
     */
    $rotation = exec( $sp_ffmpeg_path . 'ffprobe -v quiet -show_streams ' . $ARGS['VID_FILE'] . ' | grep -o "rotate=[-]\?[0-9]\+" | grep -o "[-]\?[0-9]\+"' );
    $rotationFilter = '';
    if( !empty($rotation) ){
        switch( $rotation ){
            case '-270':
                $rotationFilter = 'transpose=2, transpose=2, transpose=2, ';
                break;
            case '270':
                $rotationFilter = 'transpose=1, transpose=1, transpose=1, ';
                break;
            case '-180':
                $rotationFilter = 'transpose=2, transpose=2, ';
                break;
            case '180':
                $rotationFilter = 'transpose=1, transpose=1, ';
                break;
            case '-90':
                $rotationFilter = 'transpose=2, ';
                break;
            case '90':
                $rotationFilter = 'transpose=1, ';
                break;
            default:
                $rotationFilter = '';
                break;
        }
    }

    /**
     * -q:v - Use video quality 2 (where 0 is equivalent to input video, and 31 is worst quality).
     * -vf  - Scaling and padding for videos that are not in 16:9 ratios
     * -metadata:s:v:0 rotate=0 - Makes sure iOS/Mac devices don't unnecessarily rotate the video
     */
    $filter = '"' . $rotationFilter . 'scale=iw*min(' . $ARGS['WIDTH'] . '/iw\,' . $ARGS['HEIGHT'] . '/ih):ih*min(' . $ARGS['WIDTH'] . '/iw\,' . $ARGS['HEIGHT'] . '/ih), pad=' . $ARGS['WIDTH'] . ':' . $ARGS['HEIGHT'] . ':(' . $ARGS['WIDTH'] . '-iw*min(' . $ARGS['WIDTH'] . '/iw\,' . $ARGS['HEIGHT'] . '/ih))/2:(' . $ARGS['HEIGHT'] . '-ih*min(' . $ARGS['WIDTH'] . '/iw\,' . $ARGS['HEIGHT'] . '/ih))/2"';
    system( $sp_ffmpeg_path . 'ffmpeg -i ' . $ARGS['VID_FILE'] . ' -qscale 2 -filter:v ' . $filter . ' -metadata:s:v:0 rotate=0 ' . $filename . '.mp4' ); // .mp4 conversion

    $uploads = wp_upload_dir();

    // Create a .mp4 attachment and a .png file based off the .mp4 file in case it got rotated
    if( file_exists( $mp4_filename ) ){
        system( $sp_ffmpeg_path . 'ffmpeg -i ' . $mp4_filename . ' -f image2 -vframes 1 ' . $filename  .'.png'); // Video thumb creation
        $videoComponent->videoAttachmentIDs['mp4'] = sp_core::create_attachment( $mp4_filename, $ARGS['POST_ID'], $mp4_filename, $ARGS['AUTH_ID'] );
    }else{
        $videoComponent->errors['mp4'] = 'Could not generate ' . $mp4_filename . '!' . PHP_EOL;
        echo $videoComponent->errors['mp4'];
    }

    // Add a featured image if one doesn't already exist
    if( file_exists( $png_filename ) ){
        $videoComponent->videoAttachmentIDs['img'] = sp_core::create_attachment( $png_filename, $ARGS['POST_ID'], $content, $ARGS['AUTH_ID'] );
        if( !has_post_thumbnail( $ARGS['POST_ID'] ) ){
            set_post_thumbnail( $ARGS['POST_ID'], $videoComponent->videoAttachmentIDs['img']);
        }
    }else{
        $videoComponent->errors['img'] = 'Could not generate ' . $png_filename . '!' . PHP_EOL;
        echo $videoComponent->errors['img'];
    }

    if( $ARGS['VID_FILE'] != $mp4_filename){
        unlink( $ARGS['VID_FILE'] ); // Remove the original .mov or .avi files
    }
    $videoComponent->beingConverted = false;
    $success = $videoComponent->update();

    exit(0);
} else {
    if( empty( $ARGS['COMP_ID'] ) ){ echo 'COMP_ID argument not provided' . PHP_EOL; }
    if( empty( $ARGS['VID_FILE'] ) ){ echo 'VID_FILE argument not provided' . PHP_EOL; }
    if( empty( $ARGS['POST_ID'] ) ){ echo 'POST_ID argument not provided' . PHP_EOL; }
    if( empty( $sp_ffmpeg_path ) ){ echo 'FFmpeg path not found' . PHP_EOL; }
    exit(1);
}
