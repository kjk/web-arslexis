<?php
require( "../phpinc/settings.inc" );

error_reporting(E_ALL);
$file = "noah_pro2.zip";
$filePath = "/var/www/arslexis/" . $file;
define( 'ALLOWED_DLS_COUNT', 3 );

function bailIfFileDoesntExists()
{
  global $filePath;
  if ( !@is_file( $filePath ) )
  {
      header( "Location: dlerror-nofile.html" );
      exit();
  }
}

// record than this password has been used
// $pwd is a password, $successP says if it
// was sucessfully used or not (i.e. not found
// at all)
function recordPasswordAsUsed( $pwd, $successP )
{
  if ( $successP )
  {
    doQuery("UPDATE dl_pwds SET dl_count = dl_count + 1 WHERE pwd='$pwd' AND status='n'");
  }
  else
  {
    doQuery( "INSERT INTO dl_pwds SET pwd='$pwd', when_added=now(), last_used=now(), status='r'");
  }
}

// Check if a given password is valid
// returns the following:
// PWD_CHECK_VALID - password is valid
// PWD_CHECK_NOT_FOUND - password not found at all
// PWD_CHECK_USED_TOO_MANY_TIMES - password used more than 3 times

define( 'PWD_CHECK_VALID', 0 );
define( 'PWD_CHECK_NOT_FOUND', 1);
define( 'PWD_CHECK_USED_TOO_MANY_TIMES', 2);

function checkPassword( $pwd )
{
  $query = "
	SELECT dl_count
	  FROM dl_pwds
	 WHERE pwd='$pwd'
           AND status='n'";
  $result = doQuery( $query );
  
  if ( ! ($row = mysql_fetch_row($result) ) )
  {
    return PWD_CHECK_NOT_FOUND;
  }
  $count = $row[0];

  //check if the file hasn't been used more than 3 times
  if ( $count > (ALLOWED_DLS_COUNT-1) )
  {
      return PWD_CHECK_USED_TOO_MANY_TIMES;
  }
  return PWD_CHECK_VALID;
}

// start of the real thing
if ( !array_key_exists("pwd", $HTTP_GET_VARS)) {
//if ( ! key_exists("pwd", $HTTP_GET_VARS) ) {
  header("Location: dlerror-badpwd.php?pwd=no_pwd_given\n");
  exit();
} 
$pwd = $HTTP_GET_VARS["pwd"];
$pwd = stripQuotes( myUrlDecode($pwd) );
bailIfFileDoesntExists();

$res = checkPassword( $pwd );
if ( $res == PWD_CHECK_NOT_FOUND )
{
  recordPasswordAsUsed( $pwd, 0 );

  header("Location: dlerror-badpwd.php?pwd=" . urlencode($pwd) . "\n" );
  exit();
} 

if ( $res == PWD_CHECK_USED_TOO_MANY_TIMES )
{
  header("Location: dlerror-toomany.php?pwd=" . urlencode($pwd) . "\n" );
  exit();
}

// update the file with used password to mark that the
// password has been used

recordPasswordAsUsed( $pwd, 1 );

// and finally return the file
header("Content-Type: application/octet-stream\n");
header("Content-disposition: attachment; filename=$file\n");
header("Content-transfer-encoding: binary\n");
header("Content-Length: " . filesize( $filePath ) . "\n" );

$fp = fopen( $filePath, "rb" );
fpassthru($fp);
?>

