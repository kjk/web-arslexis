<html>
  <head>
   <title>dl pwd admin</title>
   <style>
     body, tr, td, p {
       font-family :Arial, Helvetica, sans-serif;
       font-size: 12px;
     }
  </style>

  </head>
<body>

<?php
require( "../phpinc/settings.inc" );

error_reporting(E_ALL);

function showPwdForm()
{
echo "<form method=GET action=\"/dladmin.php\">
Password:
<input type=text name=pwd size=40 maxlength=100>
<input type=submit value=\"enter\">
</form>
";
}

function showAddPwdForm($pwd)
{
echo "<form method=GET action=\"/dladmin.php\">
Add password:
<input type=hidden name=\"action\" value=\"add-pwd\">
<input type=hidden name=pwd value=\"$pwd\">
<input type=text name=pwdToAdd size=40 maxlength=100>
<input type=submit value=\"add it\">
</form>
";
}

function addPassword($pwd)
{
  $conn = getConnection();
  $query = "INSERT INTO dl_pwds SET pwd='$pwd', when_added=now(), last_used=now(), status='n'";
  $result = mysql_query( $query, $conn);
}

function showBadPwd($pwd)
{
  echo "<font size=\"+1\">Pasword <font color=\"red\"><b>$pwd</b></font> is invalid. <p/></font>\n";
  showPwdForm();
}

function createRecentlyUsed()
{
  $maxEntries = 25; 

  $query = "SELECT pwd, status, date_format(last_used,\"%Y-%m-%d\"), dl_count FROM dl_pwds ORDER BY last_used DESC";
  $result = doQuery( $query );
  $res = "<table>\n";
  $res = $res . "<tr><td colspan=3><b>Recently used passwords:</b></td></tr>\n";
  $entryCount = 0;
  while (($row = mysql_fetch_row($result)) && ($entryCount<$maxEntries))
  {
    if ( 0 == $entryCount)
    {

      $txt = "<tr>\n";
      $txt =        "<tr>";
      $txt = $txt . "<td>Password</td>";
      $txt = $txt . "<td width=\"20\">&nbsp;</td>";
      $txt = $txt . "<td>D/L count</td>";
      $txt = $txt . "<td width=\"20\">&nbsp;</td>";
      $txt = $txt . "<td>Staus</td>\n";
      $txt = $txt . "<td width=\"20\">&nbsp;</td>";
      $txt = $txt . "<td>Last used</td></tr>\n";
      $res = $res . $txt;
    }

    $pwd = $row[0];
    $status = $row[1];
    if ( $status == 'n' ) { $status = "normal"; }
    if ( $status == 'r' ) { $status = "rogue"; }
    if ( $status == 'd' ) { $status = "disabled"; }
    $dateLastUsed = $row[2];
    $dlCount = $row[3];

    if ( $status == "rogue") {
       $txt = '<tr style="color:red";>';
    } else {
       $txt = "<tr>";
    }
    $txt .= "\n  <td>$pwd</td>";
    $txt .= "\n  <td width=\"20\">&nbsp;</td>";
    $txt .= "\n  <td align=\"center\">$dlCount</td>";
    $txt .= "\n  <td width=\"20\">&nbsp;</td>";
    $txt .= "\n  <td>$status</td>";
    $txt .= "\n  <td width=\"20\">&nbsp;</td>";
    $txt .= "\n  <td>$dateLastUsed</td>\n</tr>\n";
    $res = $res . $txt;
    $entryCount += 1;
  }
  $res = $res . "</table>\n";
  return $res;
}

function showRecentlyUsed()
{
  $txt = createRecentlyUsed();
  echo $txt;
}

// if no password provided
if ( ! array_key_exists("pwd", $HTTP_GET_VARS) )
{
  showPwdForm();
  echo "</body></html>\n";
  exit;
}

// check if password is correct
$pwd = $HTTP_GET_VARS["pwd"];
if ( $pwd != DLADMINPWD )
{
  showBadPwd($pwd);
  echo "</body></html>\n";
  exit;
}

if ( array_key_exists("action", $HTTP_GET_VARS) )
{
  $action = $HTTP_GET_VARS["action"];
  if ( $action != "add-pwd" )
  {
     echo "unknown action: <b>$action</b> </body> </html>";
     exit;
  }

  if ( ! array_key_exists("pwdToAdd", $HTTP_GET_VARS ) )
  {
     echo "action is add-pwd but no password! </body></html>";
     exit;
  }
  $pwdToAdd = $HTTP_GET_VARS["pwdToAdd"];
  $pwdToAdd = stripQuotes( myUrlDecode($pwdToAdd) );
  if ( $pwdToAdd == "" )
  {
     echo "action is add-pwd but password is empty!</body></html>";
     exit;
  }
  addPassword( $pwdToAdd );
  showAddPwdForm($pwd);
  showRecentlyUsed();
} else {
  showAddPwdForm($pwd);
  showRecentlyUsed();
}

?>

</body>
</html>



