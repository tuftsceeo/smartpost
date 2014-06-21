<?php
/**
 * Created by PhpStorm.
 * User: ryagudin
 * Date: 6/20/14
 * Time: 11:03 PM
 */
if( isset( $_GET['sam_mov'] ) ){
    $file = $_GET['sam_mov'];

    if( file_exists( $file ) ){
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename=' . basename($file) );
        header( 'Expires: 0' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . filesize( $file ) );
        flush();
        readfile( $file );
        exit;
    }
}