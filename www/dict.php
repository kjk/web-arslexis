<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Noah on-line English dictionary</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
<!--
<center><h3>Noah - on-line English dictionary</h3></center>
-->

<H4> <a href="/">ArsLexis.com</a> &raquo; on-line dictionary </h4>

<center>
<form action="dict.php" method="get" name="dict">
<input name="word" type="text" maxlength="25">
<input type="submit" value="go">
</form>
</center>

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

function get_pos_full_name( $pos )
{
	# todo: don't know why I need to do that on rackshack and not in local
	$pos = trim($pos);
	if ($pos=="v") return "verb";
	if ($pos=="n") return "noun";
	if ($pos=="a") return "adjective";
	if ($pos=="r") return "adverb";
	if ($pos=="s") return "adjective"; # adjective satellite
	return "unknown ($pos)";
}

define( 'ST_START', 1 );
define( 'ST_AFTER_WORD', 2);
define( 'ST_AFTER_POS', 3);
define( 'ST_AFTER_DEF', 4);
define( 'ST_AFTER_EXAMPLE', 5);

function state_to_name( $state )
{
	if ($state==ST_START) return "ST_START";
	if ($state==ST_AFTER_WORD) return "ST_AFTER_WORD";
	if ($state==ST_AFTER_POS) return "ST_AFTER_POS";
	if ($state==ST_AFTER_DEF) return "ST_AFTER_DEF";
	if ($state==ST_AFTER_EXAMPLE) return "ST_AFTER_EXAMPLE";
	return "ST_UNKNOWN";
}


function synsets_to_html_1( $all_synsets )
{
	$res = "";
	for($i=0; $i<count($all_synsets); $i++)
	{
		$n = $i+1;
		$synset = $all_synsets[$i];
		$words = $synset[0];
		$pos = $synset[1];
		$pos_full = get_pos_full_name($pos);
		$def = $synset[2];
		$examples = $synset[3];		
		# $res .= "<b>$n.</b>&nbsp;($pos)&nbsp;" . implode(", ",$words) . "<br>\n";
		$res .= "<b>$n.</b>&nbsp;<i>($pos_full)</i>&nbsp;<b>" . implode(", ",$words) . "</b><br>\n";
		$res .= "&nbsp;&nbsp;&nbsp; $def <br>\n";
		for ($e=0; $e<count($examples); $e++)
		{
			$example = $examples[$e];
			$res .= "&nbsp;&nbsp;&nbsp; <i>$example</i> <br>\n";
		}
	}
	return $res;
}

function synsets_to_html_2( $all_synsets )
{
	$res = "";
	for($i=0; $i<count($all_synsets); $i++)
	{
		$n = $i+1;
		$synset = $all_synsets[$i];
		$words = $synset[0];
		$pos = $synset[1];
		$pos_full = get_pos_full_name($pos);
		$def = $synset[2];
		$examples = $synset[3];		
		$res .= "<b>$n.</b>&nbsp;<i>($pos_full)</i> $def <br>\n";
		for ($e=0; $e<count($examples); $e++)
		{
			$example = $examples[$e];
			$res .= "&nbsp;&nbsp;&nbsp;&nbsp;<i>$example</i> <br>\n";
		}
		if ( count($words)>1 ) # one word means: there are no synonyms, there's just a headword
		{
			$synonyms = "&nbsp;&nbsp;&nbsp;&nbsp;<font size=\"-1\"><i>synonyms: ";
			for ($w=0; $w<count($words); $w++)
			{
				$word = $words[$w];
				$synonyms .= "<a href=\"dict.php?word=$word\">$word</a>";
				# add a separator between words if it's not the last word
				if ( $w != count($words)-1 )
				{
					$synonyms .= ",&nbsp;";
				}
			}
			$res .= $synonyms . "</i></font><br>\n";
		}
	}
	return $res;
}

function format_def_txt($def)
{
	$def_lines = explode("\n", $def);
	$count = count($def_lines);
	$state = ST_START;
	$cur_synset = range(0,3); # 0 - array of words, 1 - part of speech, 2 - definition, 3 - array of examples (can be empty)
	$cur_words = array();
	$cur_examples = array();
	$all_synsets = array();
	$res = "";
	for ($i=0; $i<$count; $i++)
	{
		$line = $def_lines[$i];
		$type = substr($line,0,1);
		$line_data = substr($line,1);

		# print state_to_name($state) . ", line: $line <br>\n";
		if ( $type == "!" )
		{
			# this is a word
			if ( ($state == ST_AFTER_DEF) || ($state == ST_AFTER_EXAMPLE) )
			{
				# we have accumulated a synset
				$cur_synset[0] = $cur_words;
				$cur_synset[3] = $cur_examples;
				array_push($all_synsets, $cur_synset);
				$cur_synset = range(0,3);
				$cur_words = array();
				$cur_examples = array();
			}

			# $line_data is a word
			#print "found word <b>$line_data</b><br>\n";
			array_push( $cur_words, $line_data );
			$state = ST_AFTER_WORD;
		}
		elseif ( $type == "$" )
		{
			# this is part of speech
			# can be: s, ...
			# TODO: assert that $state is ST_AFTER_WORDS
			# $line_data is part of speech
			$cur_synset[1] = $line_data;
			$state = ST_AFTER_POS;
		}
		elseif ( $type == "@" )
		{
			# this is a definition, $line_data is synset definition
			# TODO: assert that $state is ST_AFTER_POS
			$cur_synset[2] = $line_data;
			$state = ST_AFTER_DEF;
		}
		elseif ( $type == "#" )
		{
			# this is an example, $line_data is example
			# TODO: assert that state is either ST_AFTER_DEF or ST_AFTER_EXAMPLE
			array_push( $cur_examples, $line_data );
			$state = ST_AFTER_EXAMPLE;
		}
		else
		{
			# this is an error
			#print "There was an error for $line\n";
			#die; # hard
		}

		$res .= $line;
		$res .= "<br>\n";
	}

	$cur_synset[0] = $cur_words;
	$cur_synset[3] = $cur_examples;
	array_push( $all_synsets, $cur_synset );

	$html = synsets_to_html_2( $all_synsets );
	return $html;
}


# translate definition from the format in the database
# to html that can be displayed
function def_to_html($word, $def)
{
	$def = format_def_txt($def);
	$ret = "<center>
<table width=\"90%\"  border=\"0\">
  <tr bgcolor=\"#DFDFFF\">
    <td><font size=\"+2\"><b>$word</b></font></td>
  </tr>
  <tr bgcolor=\"#FFFFCA\">
    <td>$def</td>
  </tr>
</table>
</center>";
	return $ret;
}

function no_def_html($word)
{
	return "Definition of word <b>$word</b> has not been found. Please try another word.";
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
	$html = "<b><p><p><font size=+1 color=red><pre>$err.\nPlease contact ArsLexis (support@arslexis.com) about the problem.</pre></font></b>";
	return $html;
}

$word_to_translate = $HTTP_GET_VARS['word'];

$def = get_word_def($word_to_translate);

if ( $def == "" )
	$html = no_def_html($word_to_translate);
else
{
	if ( was_error($def) )
		$html = get_error_msg($def);
	else
		$html = def_to_html($word_to_translate, $def );
}

print $html;

#debug code, display the raw definition
#print "\n<p><pre>$def</pre>\n";
?>



<hr>
<center>
<cite>Copyright &copy;&nbsp;<a href="/">ArsLexis</a></cite>
</center>

</body>
</html>
