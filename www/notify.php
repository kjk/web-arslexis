<?php
#
# Author: Krzysztof Kowalczyk (krzysztofk@pobox.com)
#
# Script for handling PayPal IPNs (Instant Payment Notification)
#
# The way IPN works:
# - user buys stuff
# - PayPal POST to a URL chosen by me all the info
# - my scripts writes the info back to PayPal
# - PayPal responds with either VERIFIED (I log the transaction info)
#   or INVALID (then I send myself an e-mail with error info)
# - on VERIFIED, the script should make sure that:
#      payment_status = "Completed"
#      txn_id is unique
#      receiver_email = "arslexis@pobox.com"
#      item_number is correct
# - if all is OK, I log all the info in the database, send an e-mail to user
#   sending him a password and telling him how to download software

# My products are:
# item_number: (valid values are in $products array)
#
# Other interesting variables in a POST:
# num_cart_items
# item_number1, 2 etc.
# quantity1, 2, etc.
# payment_status - only "Completed" counts
# payment_gross - total payment
# payment_fee - how much PayPal takes out of it
# txn_id
# payer_email
#

#
# to test the script:
# http://www.eliteweaver.co.uk/testing/ipntest.php
# mysql -h localhost -u koviorg -p (pwd is: Dingo123)

require( "../phpinc/settings.inc" );

error_reporting(E_ALL);
set_error_handler( "errorHandler" );

function verifyVarsExist()
{
  verifyPostVarExists( 'num_cart_items' );
  verifyPostVarExists( 'receiver_email' );
  verifyPostVarExists( 'payment_status' );
  verifyPostVarExists( 'payment_gross' );
  verifyPostVarExists( 'payment_fee' );
  verifyPostVarExists( 'txn_id' );
  verifyPostVarExists( 'txn_type' );
  verifyPostVarExists( 'payer_email' );
}

# verify that the transaction type ('txn_type') is 'cart'
function verifyTxType()
{
  $type = getPostVar( 'txn_type' );
  if ( $type != 'cart' )
    doError( "invalid txn_type (is $type, should be 'cart')\n" );
}

# check if $name is one of the valid product names (stored in $products)
function verifyProdNameCorrect($name)
{
  global $products;
  $validProducts = "";
  foreach ($products as $prod)
  {
    if ( $name == $prod )
      return;
    $validProducts .= $prod;
    $validProducts .= " ";
  }
  doError( "invalid item_name (is $name, can only be: $validProducts\n" );
}

function getProductsBought()
{
  $ret = array();
  $cart_items = getPostVar('num_cart_items');
  for( $i=1; $i<=$cart_items; $i++)
  {
    $itemNum = sprintf( "item_number%d", $i );
    $itemName = getPostVar( $itemNum );
    $ret[] = $itemName;
  }

  //TESTIONG ONLY!
  //$ret = array( 'noah_pro_palm', 'noah_pro_win' );
  return $ret;
}

function verifyCartItems()
{
  $cartItems = getPostVar('num_cart_items');
  if ($cartItems < 1) 
    doError( "num_cart_items should be >0 and is $cartItems\n" );
}

function verifyProducts( $prods )
{
  foreach ($prods as $prod )
  {
    verifyProdNameCorrect( $prod );
  }
}

$productsBought = getProductsBought();
if ( 0==count( $productsBought ) )
  doError( "productsBought array is empty\n");

verifyMethodPost();
verifyVarsExist();
verifyTxType();
verifyCartItems();
verifyProducts( $productsBought );

// IPN Posting Modes, Choose: 1 or 2
//* 1 = Live Via PayPal Network
//* 2 = Test Via EliteWeaver UK
$postmode = "1";

// Read the Posted IPN and Add "cmd" for Post back Validation
$postvars = array();
while (list ($key, $value) = each ($HTTP_POST_VARS))
{
  $postvars[] = $key;
}
$req = 'cmd=_notify-validate';
for ($var = 0; $var < count ($postvars); $var++)
{
  $postkey = $postvars[$var];
  $postvalue = $$postvars[$var];
  $req .= "&" . $postkey . "=" . urlencode ($postvalue);
}

// Selected PostMode was Probably Not Set to 1 or 2
if ( ($postmode != 1) && ($postmode != 2) )
{
  doError( "invalid postmode (is $postmode and should be 1 or 2)\n" );
}

// PostMode 1: Live Via PayPal Network
if ($postmode == 1)
{
  $fp = fsockopen ("www.paypal.com", 80, $errno, $errstr, 30);
  $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
#$header .= "Host: www.paypal.com\r\n"; // Host on Dedicated IP
  $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $header .= "Content-Length: " . strlen ($req) . "\r\n\r\n";
  //* Note: "Connection: Close" is Not required Using HTTP/1.0
}

// PostMode 2: Test Via EliteWeaver UK
if ($postmode == 2)
{
  $fp = fsockopen ("www.eliteweaver.co.uk", 80, $errno, $errstr, 30);
  $header = "POST /testing/ipntest.php HTTP/1.0\r\n";
  $header .= "Host: www.eliteweaver.co.uk\r\n"; // Host on Shared IP
  $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
  $header .= "Content-Length: " . strlen ($req) . "\r\n\r\n";
  //* Note: "Connection: Close" is Not required Using HTTP/1.0
}

// Problem: Now is this your Firewall or your Ports?
// Maybe Setup a little email Notification here. . .

if (!$fp)
  doError( "Error opening socket. Error Number: $errno Error String: $errstr\n" );

$isVerified = FALSE;

fputs ($fp, $header . $req);
while (!feof($fp))
{
  $res = fgets ($fp, 1024);
  $res = trim ($res); // Required on some Environments

  // IPN was Confirmed as both Genuine and VERIFIED
  if (strcmp ($res, "VERIFIED") == 0)
    {
      // Check that the "payment_status" variable is: Completed
      // If it is Pending you may Want to Inform your Customer?
      // Check your DB to Ensure this "txn_id" is Not a Duplicate
      // You may want to Check the "payment_gross" matches listed Prices?
      // You definately want to Check the "receiver_email" is yours
      // Update your DB and Process this Payment accordingly
      $isVerified = TRUE;
    }
  // IPN was Not Validated as Genuine and is INVALID
  elseif (strcmp ($res, "INVALID") == 0)
    {
      // Check your code for any Post back Validation problems
      // Investigate the Fact that this Could be a spoofed IPN
      // If updating your DB, Ensure this "txn_id" is Not a Duplicate
      // echo "Result: $res"; // uncomment for testing
      fclose( $fp );
      doError( "we got $res from PayPal instead of VERIFIED\n" );
    }
}
// Terminate the Socket connection and Exit
fclose ($fp);

if ( !$isVerified)
{
  doError( "payment is not VERIFIED, res=$res\n" );
}

$status = getPostVar( 'payment_status' );
if ( $status != "Completed" )
{
  doError( "invalid payment_status (is $status, should be Completed)\n" );
}

$myEmail = getPostVar( 'receiver_email' );
if ( 0!=strcasecmp( $myEmail, MYEMAIL ) )
{
  doError( "invalid receiver_email (is $myEmail, should be arslexis@pobox.com)\n" );
}

$txnId = getPostVar( 'txn_id' );
if ( !isTxnUnique( $txnId ) )
{
  doError( "txn_id ($txnId) is not unique\n" );
}

# this was a succesful transaction. Log details in the database
# and notify me with an e-mail
$id = logTransactionDetails();
$pwd = genPwd();
insertOneVar( $id, 'dl_pwd', $pwd );

$customerEmail = getPostVar( 'payer_email' );

$body = getLoggedVars( $id );
$subject = "[PP] TD $customerEmail";
$ret = sendEmail( MYEMAIL, $subject, $body );

# update the database with products to download
$login = $customerEmail;
foreach( $productsBought as $prod )
{
  insertProduct( $login, $pwd, $prod );
}

# send confirmation e-mail to the customer

$body  = "Thank you for purchasing ArsLexis software.\n";
$body .= "To download the software please go to http://www.arslexis.com/dl.html\n";
$body .= "and use '$login' as login and '$pwd' as a password.\n";
$body .= "\n";
$body .= "Please use only a standard browser like IE or Netscape to download.\n";
$body .= "Other software, esp. download managers, may fail to download.\n";
$body .= "\n";
$body .= "Let us know if you have problems downloading by e-mailing support@arslexis.com\n";
$body .= "Regards,\n";
$body .= "\n";
$body .= "ArsLexis support\n";
$body .= "\n";
$body .= "http://www.arslexis.com\n";

$subject = "ArsLexis software purchase confirmation e-mail";
sendEmail( $customerEmail, $subject, $body );

# for testing purposes: send the e-mail to myself as well
sendEmail( MYEMAIL, "[PP] $customerEmail sales confirmation copy", $body );

# and that's all folks
?>
