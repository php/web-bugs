<?php /* vim: set noet ts=4 sw=4 ft=php3: : */

/* scrub user input so it can be re-output */
function clean($in)
{
	return htmlspecialchars(get_magic_quotes_gpc()?stripslashes($in):$in);
}
/* just rinse out any slashes. :) */
function rinse($in)
{
	return get_magic_quotes_gpc()?stripslashes($in):$in;
}

/* handy function for when you're dealing with user input or a default. */
function oneof()
{
	foreach (func_get_args() as $arg) {
		if ($arg) return $arg;
	}
}

function show_byage_options($current)
{
	$opts = array(
		"0"   => "the beginning",
		"1"   => "yesterday",
		"7"   => "7 days ago",
		"15"  => "15 days ago",
		"30"  => "30 days ago",
		"90"  => "90 days ago",
	);
	while (list($k,$v) = each($opts)) {
		echo "<option value=\"$k\"", ($current==$k ? " selected" : ""),
		     ">$v</option>\n";
	}
}

function show_limit_options($limit=30)
{
	for($i=10;$i<100;) {
		echo "<option value=\"$i\"", ($limit==$i ? " selected" : ""),">$i</option>\n";
		$i=$i+10;
	}
	echo '<option value="All"', ($limit=='All' ? " selected": ""),">All</option>\n";
}

function show_state_options($state, $user_mode=0, $default="") 
{
	if (!$state && !$default) {
		$state = "Open";
	}
	elseif (!$state) {
		$state = $default;
	}

	$state_types = 	array (
		"Open" => 2, 
		"Closed" => 2,
		"Duplicate" => 2,
		"Critical" => 1, 
		"Assigned" => 1,
		"Analyzed" => 1,
		"Suspended" => 1,
		"No Feedback" => 1,
		"Feedback" => 1,
		"Old Feedback" => 0,
		"Stale" => 0,
		"Fresh" => 0,
		"Bogus" => 1,
		"All" => 0
	);
	
	/* regular users can only pick states with type 2 for unclosed bugs */
	if($state != "All" && $state_types[$state] == 1 && $user_mode == 2) {
		/* If state was 'Feedback', set state to 'Open' automatically. */
		if($state == "Feedback") {
			echo "<option>Open</option>\n";
		} else {
			echo "<option>$state</option>\n";
		}
		if($state != "Bogus") echo "<option>Closed</option>\n";
	} else {
		foreach($state_types as $type => $mode) {
			if ($mode >= $user_mode) {
				echo "<option";
				if($type == $state) echo " selected";
				echo ">$type</option>\n";
			}
		}
	}
}

function show_version_options($current,$default="") 
{
	$versions = array("4.1.1", "4.1.0", "4.0.6","4.0.5","4.0CVS-".date("Y-m-d"));
	while (list(,$v) = each($versions)) {
		echo "<option", ($current == $v ? " selected" : ""), ">$v</option>\n";
		if ($current == $v) $use++;
	}
	if (!$use && $current) echo "<option selected>$current</option>\n";
	echo "<option value=\"earlier\">Earlier? Upgrade first!</option>\n";
}

function show_types($current,$show_any,$default="") 
{
	include 'bugtypes.inc';

	if (!$current && !$default && !$show_any) {
		echo "<option value=\"none\">--Please Select--</option>\n";
	}
	elseif (!$current && $show_any) {
		$current = "Any";
	}
	elseif (!$current) {
		$current = $default;
	}

	while (list($key,$value) = each($items)) {
		if ($show_any || $key != "Any") {
			echo "<option value=\"$key\"",
				 ($key == $current? " selected" : ""),
				 ">$value</option>\n";
			if ($key == $current) $use++;
		}
	}
	if (!$use && $current) {
		echo "<option selected>$current</option>\n";
	}
}

function get_old_comments ($bug_id) 
{
	$divider = str_repeat("-", 72);
	$max_message_length = 10 * 1024;
    $max_comments = 5;
    $output = ""; $count = 0;

	$res = @mysql_query("SELECT ts, email, comment FROM bugdb_comments WHERE bug=$bug_id ORDER BY ts DESC");

    if (!$res) return "";
    
    # skip the most recent (this is get_old_comments, not get_all_comments!)
    $row = mysql_fetch_row($res);
    if (!$row) return "";

    while (($row = mysql_fetch_row($res)) && strlen($output) < $max_message_length && $count++ < $max_comments) {
		$output .= "[$row[0]] $row[1]\n\n$row[2]\n\n$divider\n\n";
    }

    if (strlen($output) < $max_message_length && $count < $max_comments) {
    	$res=@mysql_query("SELECT ts1,email,ldesc FROM bugdb WHERE id=$bug_id");
    	if (!$res) return $output;
    	$row = mysql_fetch_row($res);
    	if (!$row) return $output;
		return ("\n\nPrevious Comments:\n$divider\n\n" . $output . "[$row[0]] $row[1]\n\n$row[2]\n\n$divider\n\n");
    }
    else {
		return ("\n\nPrevious Comments:\n$divider\n\n" . $output . "The remainder of the comments for this report are too long. To view\nthe rest of the comments, please view the bug report online at\n    http://bugs.php.net/?id=$bug_id\n");
    }

    return "";
}

function addlinks($text) 
{
	$text = htmlspecialchars($text);
    $text = preg_replace("/((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/i","<a href=\"\\1\">\\1</a>\\3",$text);
    # what the heck is this for?
    $text = preg_replace("/[.,]?-=-\"/", '"', $text);
	return $text;
}

/* validate an incoming bug report */
function incoming_details_are_valid($in,$initial=0) 
{
	$errors = array();
	if(!preg_match("/[.\\w+-]+@[.\\w-]+\\.\\w{2,}/i",$in['email'])) {
		$errors[] = "Please provide a valid email address.";
	}

	if ($in['bug_type'] == "none") {
		$errors[] = "Please select an appropriate bug type.";
	}

	if ($in['php_version'] == 'earlier') {
		$errors[] = "Please select a valid PHP version. If your PHP version is too old, please upgrade first and see if the problem has not already been fixed.";
	}

    if (!$in['sdesc']) {
		$errors[] = "You must supply a short description of the bug you are reporting.";
	}

    if ($initial && !$in['ldesc']) {
		$errors[] = "You must supply a long description of the bug you are reporting.";
	}

	if ($initial && !$in['passwd']) {
		$errors[] = "You must supply a password for this bug report.";
	}

	return $errors;
}

function get_bugtype_mail($bug_type) 
{
	global $mail_bugs_to;

	if (eregi("documentation", $bug_type)) {
		return array("$mail_bugs_to,phpdoc@lists.php.net",$mail_bugs_to);
	}
	elseif (eregi("website", $bug_type)) {
		return array("php-mirrors@lists.php.net","php-mirrors@lists.php.net");
	}
	elseif (eregi("pear", $bug_type)) {
		return array("pear-dev@lists.php.net","pear-dev@lists.php.net");
	}
	else {
		return array($mail_bugs_to,$mail_bugs_to);
	}
}

function get_row_color($row) 
{
	if ($row["bug_type"]=="Feature/Change Request") {
		return "#aaaaaa";
	}
	switch($row["status"]) {
		case "Open":
			return "#ffbbaa";
			break;
		case "Critical":
			return "#ff0000";
			break;
		case "Closed":
			return "#aaffbb";
			break;
		case "No Feedback":
			return "#bbffcc";
			break;
		case "Suspended":
			return "#ffccbb";
			break;
		case "Assigned":
			return "#bbaaff";
			break;
		case "Feedback":
			return "#bbeeff";
			break;
		case "Analyzed":
			return "#99bbaa";
			break;
		case "Duplicate":
			return "#bbbbbb";
			break;
		default:
			return "#aaaaaa";
			break;
	}
}
