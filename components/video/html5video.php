<?php
/**
 * User: ryagudin
 * Date: 8/14/13
 * Time: 1:17 PM
 * Converts a video file to .webm and .mp4 formats using FFMpeg and HandBrakeCLI respectively.
 */

//Collect video info arguments
$ARGS = array(
    'BASE_PATH' => $argv[1],
    'POST_ID'   => $argv[2],
    'VID_FILE'  => $argv[3],
    'COMP_ID'   => $argv[4],
    'AUTH_ID'   => $argv[5],
    'MOV_ID'    => $argv[6],
);

//Collect info on wpmu
$WPMU_ARGS = array(
    'HTTP_HOST' => $argv[7],
    'BLOG_ID'   => $argv[8],
    'IS_WPMU'   => $argv[9]
);

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

//Get the paths of ffmpeg and HandBrakeCLI
$sp_hcli_path   = get_site_option('sp_hcli_path');
$sp_ffmpeg_path = get_site_option('sp_ffmpeg_path');

//Check that everything is in order before we start converting..
if( $ARGS['VID_FILE'] && $ARGS['POST_ID'] && isset($sp_hcli_path) && isset($sp_ffmpeg_path) ){

    $name = basename($ARGS['VID_FILE'], '.mov');
    $path = dirname($ARGS['VID_FILE']);

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
    $webm_filename = $filename . '.webm';

    $videoComponent = new sp_postVideo( $ARGS['COMP_ID'] );

    if( is_wp_error($videoComponent->errors) ){
        echo 'Could not instantiate sp_postVideo with component ID: ' . $ARGS['COMP_ID'] . ', ' . $videoComponent->errors->get_error_message();
        exit();
    }

    $sp_hcli_path   = ($sp_hcli_path == 'empty') ? '' : $sp_hcli_path;
    $sp_ffmpeg_path = ($sp_ffmpeg_path == 'empty') ? '' : $sp_ffmpeg_path;

    system( $sp_hcli_path . 'HandBrakeCLI -i ' . $ARGS['VID_FILE'] . ' -o ' . $filename . '.mp4 -v -m -E aac,ac3 -e x264 --maxHeight 480 --maxWidth 640' );
    system( $sp_ffmpeg_path . 'ffmpeg -i ' . $ARGS['VID_FILE'] . ' -acodec libvorbis -ac 2 -ab 96k -ar 44100 -b 345k -vf scale=320:-1 ' . $filename . '.webm' );

    $uploads = wp_upload_dir();

    //Create .webm and  .mp4 WordPress attachments!
    if( file_exists( $mp4_filename ) ){
        $videoComponent->videoAttachmentIDs['mp4'] = sp_core::create_attachment( $mp4_filename, $ARGS['POST_ID'], $content, $ARGS['AUTH_ID'] );
    }else{
        echo 'Could not generate ' . $mp4_filename . '!' . PHP_EOL;
        exit(1);
    }

    if( file_exists( $webm_filename ) ){
        $videoComponent->videoAttachmentIDs['webm'] = sp_core::create_attachment( $webm_filename, $ARGS['POST_ID'], $content, $ARGS['AUTH_ID'] );
    }else{
        echo 'Could not generate ' . $webm_filename . '!' . PHP_EOL;
        exit(1);
    }

    $videoComponent->videoAttachmentIDs['mov'] = $ARGS['MOV_ID'];
    $videoComponent->beingConverted = FALSE;
    $success = $videoComponent->update(null);

    exit(0);

}else{
    exit(1);
}
