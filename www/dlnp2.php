<?php
error_reporting(E_ALL);
#define( 'FILEDIR', '/web/arslexis/' );
define( 'FILEDIR', 'c:\\' );
$file = "noah_pro2.zip";
define( 'ALLOWED_DLS_COUNT', 3 );

$filePath = FILEDIR . $file;
$pwdFile = FILEDIR . "np2.pwd";
$usedPwdFile = FILEDIR . "np2.dls";

function bailIfFileDoesntExists()
{
  global $filePath;
  if ( !is_file( $filePath ) )
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
  global $usedPwdFile;
  $fd = fopen( $usedPwdFile, "a+" );
  if ( !$successP )
    {
      fwrite( $fd, "!" . $pwd . "\n" );
    }
  else
    {
      fwrite( $fd, $pwd . "\n" );
    }
  fclose( $fd );
}


// return the number of times a password has been
// recorded
function recordedAsUsedCount($pwd)
{
  global $usedPwdFile;
  $count = 0;

  if ( !is_file( $usedPwdFile ) )
    return 0;

  $fd = fopen( $usedPwdFile, "r" );
  while ( !feof( $fd ) )
    {
      $line = fgets( $fd, 2048 );
      $line = rtrim( $line );
      if ( eregi( "^" . $pwd . "$", $line ) )
	{
	  $count = $count + 1;
	}
    }
  fclose( $fd );
  return $count;
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
  global $pwdFile;
  // check if the password $pwd is in the passwords file
  if ( !is_file( $pwdFile ) )
    return PWD_CHECK_NOT_FOUND;

  $fd = fopen( $pwdFile, "r" );
  $fPwdFound = 0;
  while ( (!feof( $fd )) AND !$fPwdFound )
    {
      $line = fgets( $fd, 2048 );
      $line = rtrim( $line );
      if ( eregi( "^" . $pwd . "$", $line ) )
	{
	  $fPwdFound = 1;
	}
    }
  fclose( $fd );

  if ( !$fPwdFound OR ($pwd == ""))
    {
      return PWD_CHECK_NOT_FOUND;
    }

  //check if the file hasn't been used more than 3 times
  $count = recordedAsUsedCount($pwd);
  if ( $count > (ALLOWED_DLS_COUNT-1) )
    {
      return PWD_CHECK_USED_TOO_MANY_TIMES;
    }
  return PWD_CHECK_VALID;
}

// start of the real thing
if ( !array_key_exists("pwd", $HTTP_GET_VARS)) {
  header("Location: dlerror-badpwd.php?pwd=no_pwd_given\n");
  exit();
} 
$pwd = $HTTP_GET_VARS["pwd"];
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

// other values must mean PWD_CHECK_VALID
// would be good to have assert() here

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
