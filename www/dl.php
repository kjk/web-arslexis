<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<HTML>
<HEAD>
<link href="css/default.css" rel="stylesheet" type="text/css">
<TITLE>Download ArsLexis software</TITLE>

</HEAD>

<BODY>

<center>
<a href="/index.html"> <img border="0" src="gfx/arslexis-logo-4.gif" width="264" height="29" alt="ArsLexis home page"></a>

</center>

<H4> <a href="/">Home</a> &raquo; Download ArsLexis software</h4>

<p/>

<h3> Download ArsLexis software </h3>

<?php

# Author: Krzysztof Kowalczyk (krzysztofk@pobox.com)
#
# This page gets the login and password from the form,
# checks their validity and displays list of software
# to download

require( "../phpinc/settings.inc" );

error_reporting(E_ALL);
set_error_handler( "errorHandler" );

function getLoginForm()
{
  $txt  = '<form method=POST action="/dl.php">';
  $txt .= "<table>\n";
  $txt .= "<tr>\n";
  $txt .= "  <td> Login: </td>\n";
  $txt .= '  <td> <input type="text" name="login" size="40" maxlength="80"> </td>';
  $txt .= "</tr>\n";
  $txt .= "<tr>\n";
  $txt .= "  <td> Password: </td>\n";
  $txt .= '  <td> <input type="text" name="pwd" size="40" maxlength="80"> </td>';
  $txt .= "</tr>\n";
  $txt .= "<tr> <td>&nbsp;</td></tr>\n";
  $txt .= "<tr>\n";
  $txt .= '  <td> <input type="submit" value="download"> </td>';
  $txt .= "</tr>\n";
  $txt .= "</table>\n";
  $txt .= "</form>\n";
  return $txt;
}

verifyMethodPost();
# check if all the POST variables are present (login, pwd)
verifyPostVarExists( 'login' );
verifyPostVarExists( 'pwd' );

$login = stripQuotes( myUrlDecode(getPostVar( 'login' ) ));
$pwd = stripQuotes( myUrlDecode(getPostVar( 'pwd' ) ));

$productList = getProductsForLoginPwd( $login, $pwd );
if (0 == count( $productList))
{
  echo "<b><font color=\"red\">The login '$login' and password '$pwd' combination didn't match anything in our database.\n";
  echo "Please try again. </font></b>\n";
  echo "If problem persists, please e-mail <a href=\"mailto:support@arslexis.com\">ArsLexis</a>\n";
  echo "<p>\n";
  echo getLoginForm();
  $subject = "[PAYPAL ERROR] failed login";
  $body  = "Failed attempt to login using login='$login' and pwd='$pwd'\n";
  $body .= getInterestingVars();
  sendEmail( MYEMAIL, $subject, $body );
} else {
  # display a list of products with links to download
  echo "<table>\n<tr>\n";
  echo "<td width=\"30\">&nbsp;</td>\n<td>\n";

    echo "<table>\n";
    foreach ($productList as $name => $dlCount)
    {
      $fullName = getProductFullName( $name );
      $dlUrl= "dl-2.php?login=" . urlencode($login) . "&pwd=" . urlencode($pwd) . "&name=" . urlencode($name);
      echo "<tr>\n";
      echo "  <td valign=\"top\"><img src=\"/gfx2/black_bullet.gif\">&nbsp;$fullName &nbsp;&nbsp;&nbsp;</td>\n";
      echo "  <td><a href=\"$dlUrl\">Download</a></td>\n";
      # echo "  <td>current download count: $dlCount</td>\n";
      echo "</tr>\n";
    }
    echo "</table>\n";
  echo "</td>\n</tr>\n</table>\n";
}
?>

<hr>
Please e-mail <a href="mailto:support@arslexis.com">ArsLexis</a> in case of any difficulties.
</body>
</html>
