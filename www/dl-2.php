<?php

# Author: Krzysztof Kowalczyk (krzysztofk@pobox.com)
#
# Script called from dl.php, just redirects the download
# to the file if login/pwd/name of the product are valid

require( "../phpinc/settings.inc" );

error_reporting(E_ALL);
set_error_handler( "errorHandler" );

# make sure we got login, pwd, name variables
verifyGetVarExists( 'login' );
verifyGetVarExists( 'pwd' );
verifyGetVarExists( 'name' );

$login = stripQuotes( myUrlDecode(getGetVar( 'login' ) ));
$pwd = stripQuotes( myUrlDecode(getGetVar( 'pwd' ) ));

$productName = getGetVar( myUrlDecode('name') );

if ( !canDownloadProduct( $login, $pwd, $productName ) )
  doError( "Cannot download for login=$login, pwd=$pwd, product=$productName\n" );

$fullPath = getProductFilePath( $productName );
$fileName = getProductFileName( $productName );

verifyFileExists( $fullPath );

updateDlCount( $login, $pwd, $productName );

// and finally return the file
header("Content-Type: application/octet-stream\n");
header("Content-disposition: attachment; filename=$fileName\n");
header("Content-transfer-encoding: binary\n");
header("Content-Length: " . filesize( $fullPath ) . "\n" );

$fp = fopen( $fullPath, "rb" );
fpassthru($fp);
?>
