<?php
/**
 * Converts a video file to .webm and .mp4 formats using ffmpeg.
 * Assumes that the server can use exec()
 */

// Collect args
define( 'DB_NAME', $argv[1] );
define( 'DB_USER', $argv[2] );
define( 'DB_HOST', $argv[3] );
define( 'DB_PASS', $argv[4] );
define( 'WP_DB_PREFIX', $argv[5] );
define( 'VID_FILE', $argv[6] );
define( 'COMP_ID', $argv[7] );
define( 'WIDTH', $argv[8] );
define( 'HEIGHT', $argv[9] );
define( 'FFMPEG_PATH', $argv[10] );

$errors = array();
$video_file_info = pathinfo( VID_FILE );
$video_encoded_name = $video_file_info['dirname'] . DIRECTORY_SEPARATOR . $video_file_info['filename'] . '_encoded';

/**
 * Get the correct orientation of the quick time video if it exists
 */
$ffprobe_cmd = FFMPEG_PATH . 'ffprobe -v quiet -show_streams ' . VID_FILE . ' | grep -o "rotate=[-]\?[0-9]\+" | grep -o "[-]\?[0-9]\+"';
$rotation = exec( $ffprobe_cmd, $ffprobe_output, $ffprobe_result );
if( $result !== 0 ){
    $ffprobe_error = 'ffprobe exited with an error: ' . print_r($ffprobe_output, true) . PHP_EOL;
    echo $ffprobe_error;
}else{
    $rotationFilter = '';
    if( !empty( $rotation ) ){
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
}

/**
 * -q:v - Use video quality 2 (where 0 is equivalent to input video, and 31 is worst quality).
 * -vf  - Scaling and padding for videos that are not in 16:9 ratios
 * -y - automatically overwrite files
 * -metadata:s:v:0 rotate=0 - Makes sure iOS/Mac devices don't unnecessarily rotate the video
 */
$filter = '"' . $rotationFilter . 'scale=iw*min(' . WIDTH . '/iw\,' . HEIGHT . '/ih):ih*min(' . WIDTH . '/iw\,' . HEIGHT . '/ih), pad=' . WIDTH . ':' . HEIGHT . ':(' . WIDTH . '-iw*min(' . WIDTH . '/iw\,' . HEIGHT . '/ih))/2:(' . HEIGHT . '-ih*min(' . WIDTH . '/iw\,' . HEIGHT . '/ih))/2"';
$ffmpeg_cmd = FFMPEG_PATH . 'ffmpeg -i ' . VID_FILE . ' -qscale 2 -filter:v ' . $filter . ' -metadata:s:v:0 rotate=0 ' . $video_encoded_name . '.mp4';

exec( $ffmpeg_cmd, $ffmpeg_output, $ffmpeg_result ); // .mp4 conversion

if( $ffmpeg_result !== 0 ){
    $ffmpeg_error = 'ffmpeg exited with an error: ' . print_r($ffmpeg_output, true) . PHP_EOL;
    echo $ffmpeg_error;
    exit(1);
}

// If we've successfully converted the video, then try and create a .png video thumbnail
if( file_exists($video_encoded_name . '.mp4' ) ){
    $png_filename = $video_encoded_name . '.png';
    $ffmpeg_png_cmd = FFMPEG_PATH . 'ffmpeg -i ' . $video_encoded_name . '.mp4' . ' -f image2 -vframes 1 ' . $png_filename;
    exec( $ffmpeg_png_cmd, $ffmpeg_png_output, $ffmpeg_png_status );

    if( $ffmpeg_png_status !== 0 ){
        echo 'Error: ffmpeg exited with an error: ' . print_r( $ffmpeg_png_output, true) . '. Command: ' . $ffmpeg_png_cmd;
    }
}else{
    echo 'Error: converted vidoe file not found! Path: ' . $video_encoded_name . '.mp4';
}



// Connect to the DB to update the user that conversion is complete
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    echo 'Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error;
    exit(1);
}

/*
 * Use this instead of $connect_error if you need to ensure
 * compatibility with PHP versions prior to 5.2.9 and 5.3.0.
 */
if (mysqli_connect_error()) {
    echo 'Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error();
    exit(1);
}

$post_component_result = $mysqli->query( 'SELECT * FROM ' . WP_DB_PREFIX . 'sp_postComponents  WHERE id=' . COMP_ID . ';' );

if( $post_component_result->num_rows === 1 ){
    $post_component_obj = $post_component_result->fetch_object();

    $video_data = unserialize( $post_component_obj->value );
    $video_data->beingConverted = false;
    $video_data->just_converted = true;
    $video_data->videoAttachmentIDs['encoded_video'] = $video_encoded_name . '.mp4';
    $video_data->videoAttachmentIDs['png_file'] = $video_encoded_name . '.png';

    $update_sql = "UPDATE " . WP_DB_PREFIX . "sp_postComponents SET value='" . serialize( $video_data ) . "'  WHERE id=" . COMP_ID . ";";
    $update_result = $mysqli->query( $update_sql );
    if( $update_result === false ){
        echo 'Query could not be run: ' .$update_sql;
        exit(1);
    }
}

$mysqli->close();

exit(0);
