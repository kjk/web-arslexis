<?php

require( "../phpinc/noahsettings.inc" );

$err_prefix = "Error:";

# get definition of $word from the database. Returns an empty string
# if there is no definition.
function get_word_def($word)
{
	global $err_prefix;

	$ret = "";
	$conn_id = @mysql_connect(DBHOST, DBUSER, DBPWD);
	if ( !$conn_id )
		return $err_prefix . " couldn't connect. " . mysql_error() ;
	if ( !mysql_select_db("noahdb") )
		return $err_prefix . " couldn't select db. " . mysql_error();
	$query = "SELECT def FROM words WHERE word='$word';";
	$result_id = mysql_query( $query );
	if ( !$result_id)
		return $err_prefix . " select failed, query was=$query. " . mysql_error();
	if ($row = mysql_fetch_row ($result_id))
		$ret = $row[0];
	else
		$ret = "";
	mysql_free_result ($result_id);
	return $ret;
}

function was_error($def)
{
	global $err_prefix;

	$pos = strpos($def, $err_prefix);
	if ($pos === false)
		return false;
	if ($pos == 0 )
		return true;
	else
		return false;

}

function get_error_msg($err)
{
	$txt = "ERROR: $err";
	return $txt;
}

$word_to_translate = $HTTP_GET_VARS['word'];

$def = get_word_def($word_to_translate);

if ( $def == "" )
	$txt = "NO DEFINITION";
else
{
	if ( was_error($def) )
		$txt = get_error_msg($def);
	else
		$txt = $def;
}

print $txt;
?>















