<?php

# Author: Krzysztof Kowalczyk (krzysztofk@pobox.com)
#
# Stuff common to my scripts

define( 'DBHOST', 'localhost' );
define( 'DBUSER', 'koviorg' );
define( 'DBPWD', 'Dingo123' );
define( 'DBNAME', 'koviorg_arslexis' );
define( 'DLADMINPWD', 'algxml' );
define( 'MYEMAIL', 'arslexis@pobox.com' );

# should we dump the debug info into a browser window
# should be TRUE only for developement
$showDebugInfo = TRUE;

# lists symbolic names of all products that ArsLexis sells
# those are used, among other things, in the PayPal shopping cart
$products = array( 'thes_palm', 'noah_pro_palm', 'noah_pro_win', 'noah_pro_upg_palm' );
# lists corresponding file names and full product names
$productsFileName = array( 'thes.zip', 'noah_pro.zip', 'NoahProSetup.exe', 'noah_pro.zip' );
$productsFullName = array( "ArsLexis Thesaurus 1.2 for Palm OS", "Noah Pro 3.0 for Palm OS", "Noah Pro 1.0 for Windows", "Noah Pro 3.0 upgrade for Palm OS" );
# the absolute path to where the files reside on the disk. Should be 
# outside of the DocumentRoot!
$filesDir = "/var/www/arslexis/";

function getProductFullName( $prodName )
{
  global $products, $productsFullName;
  for( $i=0; $i<count( $products); $i++)
  {
    if ( $products[$i] == $prodName )
      return $productsFullName[$i];
  }
  doError( "product $prodName not found in getProductFullName()\n" );
}

function getProductFilePath( $prodName )
{
  global $products, $productsFileName, $filesDir;
  for( $i=0; $i<count( $products); $i++)
  {
    if ( $products[$i] == $prodName )
      return $filesDir . $productsFileName[$i];
  }
  doError( "product $prodName not found in getProductFilePath()\n" );
}

function getProductFileName( $prodName )
{
  global $products, $productsFileName;
  for( $i=0; $i<count( $products); $i++)
  {
    if ( $products[$i] == $prodName )
      return $productsFileName[$i];
  }
  doError( "product $prodName not found in getProductFileName()\n" );
}

function getPostVar( $name )
{
  global $HTTP_POST_VARS;
  return $HTTP_POST_VARS[ $name ];
}

function getGetVar( $name )
{
  global $HTTP_GET_VARS;
  return $HTTP_GET_VARS[ $name ];
}

function resultToTxt( $result )
{
  $txt = "";
  while ($row = mysql_fetch_row($result))
  {
    for ($i=0; $i<mysql_num_fields($result); $i++)
    {
      $txt .= $row[$i] . " ";
    }
    $txt .= "\n";
  }
  return $txt;
}

function dumpResult( $result )
{
  $txt = resultToTxt( $result );
  echo $txt;
}

$connection = 0;

function getConnection()
{
  global $connection;
  if ( 0 == $connection )
  {
    $connection = mysql_connect( DBHOST, DBUSER, DBPWD );
    mysql_select_db( DBNAME );
  }
  return $connection;
}

function doQuery( $query )
{
  $c = getConnection();
  $result = mysql_query( $query, $c );
  return $result;
}

function getOneResultQuery( $query )
{
  $result = doQuery( $query );
  # TODO: verify that this really is a query
  # returning one result
  $row = mysql_fetch_row( $result );
  return $row[0];
}

function getLastInsertId()
{
  return getOneResultQuery( "SELECT last_insert_id()" );
}

function getNewId()
{
  doQuery( "LOCK TABLES paypal_trans WRITE" );
  doQuery( "INSERT INTO paypal_trans VALUES (NULL,NULL)" );
  $newId = getLastInsertId();
  doQuery( "UNLOCK TABLES" );
  return $newId;
}

function insertOneVar($id, $varname, $value)
{
  $varname = addslashes( $varname );
  $value = addslashes( $value );
  $query = "INSERT INTO paypal_trans_info VALUES ($id, '$varname', '$value')";
  doQuery( $query );
}

function getLoggedVars($id)
{
  $query = "SELECT * from paypal_trans_info where id=$id";
  $result = doQuery( $query );
  $txt = resultToTxt( $result );
  return $txt;
}

function make_seed() {
   list($usec, $sec) = explode(' ', microtime());
   return (float) $sec + ((float) $usec * 100000);
}

function genPwd()
{
  srand(make_seed());
  $valChars = "abcdefghijklmnopqrstuvwxyz";
  $pwdLen = 6;
  $pwd = "";
  for ($i=0; $i<$pwdLen; $i++ )
    {
      $pos = rand(0,strlen($valChars)-1);
      $pwd .= substr( $valChars, $pos, 1 );
    }
  return $pwd;
}

function logVarsFromArray( $arr )
{
  $id = getNewId();
  foreach ($arr as $varname => $value)
  {
    insertOneVar( $id, $varname, $value );    
  }
  return $id;
}

function getVarsFromArray( $arr )
{
  $txt = "";
  foreach ($arr as $varname => $value)
  {
    $txt .= "$varname : $value \n";
  }
  return $txt;
}

function getPostVars()
{
  global $HTTP_POST_VARS;
  return getVarsFromArray( $HTTP_POST_VARS );
}

function logPostVars()
{
  global $HTTP_POST_VARS;
  return logVarsFromArray( $HTTP_POST_VARS );
}

function getInterestingVars()
{
  global $HTTP_USER_AGENT, $REMOTE_ADDR, $HTTP_REFERER;
  global $SCRIPT_FILENAME, $SERVER_SOFTWARE;

  $txt  = "HTTP_USER_AGENT : $HTTP_USER_AGENT\n";
  $txt .= "REMOTE_ADDR : $REMOTE_ADDR\n";
  $txt .= "HTTP_REFERER : $HTTP_REFERER\n";
  $txt .= "SCRIPT_FILENAME : $SCRIPT_FILENAME\n";
  $txt .= "SERVER_SOFTWARE : $SERVER_SOFTWARE\n";
  return $txt;
}

function logTransactionDetails()
{
  global $HTTP_USER_AGENT, $REMOTE_ADDR;
  # first log all vars posted by PayPal
  $id = logPostVars();
  # and a few other interesting ones
  insertOneVar( $id, "HTTP_USER_AGENT", addslashes($HTTP_USER_AGENT) );
  insertOneVar( $id, "REMOTE_ADDR", addslashes($REMOTE_ADDR) );
  return $id;
}

function sendEmail($to, $subject, $body)
{
  $headers  = "From: Arslexis <support@arslexis.com>\r\n";
  $headers .= "X-Sender: <support@arslexis.com>\r\n";
  $headers .= "X-Mailer: PHP\r\n";
  $headers .= "X-Priority: 1\r\n";
  $headers .= "Return-Path: <support@arslexis.com>\r\n";

  return mail( $to, $subject, $body, $headers );
}

$errorsTxtSoFar = "";
function addError( $txt )
{
  global $errorsTxtSoFar;
  $errorsTxtSoFar .= $txt;  
}

function getMysqlError()
{
  $txt = "MySQL error " . mysql_errno() . " : " . mysql_error();
  return $txt;
}

function doError( $txt )
{
  trigger_error($txt, E_USER_ERROR );
}

function msgShowTxt( $txt )
{
  echo "<html><body><pre>$txt</pre></body></html>";
}

function emptyResult( $result )
{
  if ( mysql_fetch_row( $result ) )
    return FALSE;
  return TRUE;
}

function isTxnUnique( $txnId )
{
  $txnId = addslashes( $txnId );
  $query = "SELECT * from paypal_trans_info where varname='txn_id' and value='$txnId'";
  $result = doQuery( $query );
  if ( emptyResult( $result ) )
    return TRUE;
  else
    return FALSE;
}

function errorHandler($errno, $errstr, $errfile, $errline)
{
  global $errorsTxtSoFar, $showDebugInfo;
  $body = "Error: $errstr, $errfile, $errline\n";
  $body .= getInterestingVars();
  $body .= getPostVars();
  $body .= $errorsTxtSoFar;
  $subject = "[PAYPAL ERROR] error encountered";
  sendEmail("support@arslexis.com", $subject ,$body );
  if ($showDebugInfo)
    msgShowTxt( $body );
  die();
}

# work around the fact that urldecode() doesn't decode \' and \"
function myUrlDecode( $str )
{
  $res = urldecode( $str );
  $res = str_replace( "\\'", "'", $res );
  $res = str_replace( '\"', '"', $res );
  return $res;
}

function stripQuotes( $str )
{
  $res = strtr( $str, "\"'", "  " );
  $res = trim( $res );
  return $res;
}

# returns an array of product names (must much those in $products)
# and count of downloads so far for this product
# that people with a given login/pwd can download
# if is empty then there are not products
function getProductsForLoginPwd( $login, $pwd )
{
  // TODO: remove possible quotes at the beginning and end
  // of both $login and $pwd
  $login = addslashes( $login );
  $pwd = addslashes( $pwd );
  $query = "SELECT product_id, count FROM paypal_dls WHERE login='$login' and pwd='$pwd' AND disabled_p='n'";
  $result = doQuery( $query );
  $ret = array();
  while ($row = mysql_fetch_row($result))
  {
    if ( 2 != mysql_num_fields($result) )
      doError( "mysql_num_fields in getProductsForLoginPwd != 2" );

    $ret[ $row[0] ] = $row[1];
  }
  return $ret;
}

function canDownloadProduct( $login, $pwd, $prodName )
{
  $login = addslashes( $login );
  $pwd = addslashes( $pwd );
  $prodName = addslashes( $prodName );
  $query = "SELECT product_id FROM paypal_dls WHERE login='$login' AND pwd='$pwd' AND product_id='$prodName' AND disabled_p='n'";
  $result = doQuery( $query );
  if (emptyResult($result))
    return FALSE;
  else
   return TRUE;
}

function insertProduct( $login, $pwd, $prodName )
{
  $login = addslashes( $login );
  $pwd = addslashes( $pwd );
  $prodName = addslashes( $prodName );
  $query = "INSERT INTO paypal_dls VALUES (0, '$login', '$pwd', '$prodName', 'n')";
  doQuery( $query );
}

function updateDlCount( $login, $pwd, $prodName )
{
  $login = addslashes( $login );
  $pwd = addslashes( $pwd );
  $prodName = addslashes( $prodName );
  $query = "UPDATE paypal_dls SET count = count+1 WHERE login='$login' AND pwd='$pwd' AND product_id='$prodName'";
  $result = doQuery( $query );
}

function verifyMethodPost()
{
  global $REQUEST_METHOD;
  if ( $REQUEST_METHOD != "POST" )
  {
    doError( "REQUEST_METHOD is $REQUEST_METHOD and not POST\n" );
  }
}

function verifyFileExists($file)
{
  if ( !@is_file( $file ) )
    doError( "file $file doesn't exist!\n" );
}

function verifyPostVarExists($varname)
{
  global $HTTP_POST_VARS;
  if( !isset($HTTP_POST_VARS[$varname]) )
  {
    doError( "variable $varname doesn't exist in HTTP_POST_VARS\n" );
  }
}

function verifyGetVarExists($varname)
{
  global $HTTP_GET_VARS;
  if( !isset($HTTP_GET_VARS[$varname]) )
  {
    doError( "variable $varname doesn't exist in HTTP_GET_VARS\n" );
  }
}

?>
