<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <title>Buy ArsLexis software</title>
  <meta name="description" content="Buy ArsLexis software">
  <meta name="keywords" content="Palm, CLIE, Visor, Treo, dictionary, thesaurus, Noah, English dictionary, PalmPilot, PDA, handheld">
  <link href="css/default.css" rel="stylesheet" type="text/css">
</head>

<body>

<table class="page" cellspacing="0" cellpadding="0">
<tr><td>
  <table class="tabs" cellspacing="0" width="100%">
  <tr><td class="tabTopPadding"></td></tr>
  <tr>
   <td class="tabLeftPadding">&nbsp;</td>
   <td class="tabDummy"><a href="http://www.arslexis.com"> <img align="middle" alt="Home page" border=0 src="gfx/arslexis-logo-4.gif"></a></td>
   <td class="tab"><a class="hidden" href="index.html">Products</a></td>
   <td class="tab"><a class="hidden" href="download.html">Downloads</a></td>
   <td class="activeTab">Buy</td>
   <td class="tab"><a class="hidden" href="docs.html">Documentation</a></td>
   <td class="tab"><a class="hidden" href="news.html">News</a></td>
   <td class="tab"><a class="hidden" href="support.html">Support</a></td>
   <td class="tabRightPadding" width=100%>&nbsp;</td>
  </tr>
  </table>
</td></tr>
</table>

<h4> <a href="/">Home</a> &raquo; Thank you </h4>

<h3>Thank you for purchasing ArsLexis software</h3>

You should soon receive a confirmation e-mail with a login and password
for downloading the software you purchased.
<?php
if ( array_key_exists("payer_email", $HTTP_POST_VARS)) {
	$email = $HTTP_POST_VARS["payer_email"];
	echo "The e-mail will be sent to your PayPal e-mail address <b>$email</b> so please make sure";
	echo " to check this e-mail account.";
} 
?>
 
<p>
If there are any problems, please <a href="mailto:support@arslexis.com">e-mail ArsLexis</a>.
</p>
<hr>

<div class="copyright">Copyright &copy;&nbsp;<a href="http://www.arslexis.com">ArsLexis</a></div>

</body>

</html>
