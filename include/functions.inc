<?php /* vim: set noet ts=4 sw=4 ft=php : */

/* Email spam protection */
function spam_protect($txt)
{
	$translate = array('@' => ' at ', '.' => ' dot ');

	/* php.net addresses are not protected! */
	if (preg_match('/^(.+)@php\.net/i', $txt)) {
		return $txt;
	} else {
		return strtr($txt, $translate);
	}
}

/* Primitive check for SPAM. Add more later. */
function is_spam($string)
{
	if (substr_count(strtolower($string), 'http://') > 5) {
		return true;
	}
	if (preg_match("/(asian)|(spy)|(bdsm)|(massage)|(mortage)|(sex)/i", $string)) {
		return true;
	}
	return false;
}

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

function mysql_get_one($query)
{
	$res = mysql_query($query);
	if ($res && mysql_num_rows($res)) {
		return mysql_result($res,0);
	}
	return false;
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
		echo "<option value=\"$i\"", ($limit==$i ? " selected" : ""),">$i bugs</option>\n";
		$i=$i+10;
	}
	echo '<option value="All"', ($limit=='All' ? " selected": ""),">All</option>\n";
}

$state_types = 	array (
	"Open" => 2, 
	"Closed" => 2,
	"Duplicate" => 0,
	"Critical" => 1, 
	"Assigned" => 1,
	"Analyzed" => 1,
	"Verified" => 1,
	"Suspended" => 1,
	"Wont fix" => 1,
	"No Feedback" => 1,
	"Feedback" => 1,
	"Old Feedback" => 0,
	"Stale" => 0,
	"Fresh" => 0,
	"Bogus" => 2,
	"All" => 0
);

$no_comments_allowed = array('Closed', 'Duplicate', 'Wont fix', 'Bogus', 'Stale');
	
function show_state_options($state, $user_mode=0, $default="") 
{
	global $state_types;

	if (!$state && !$default) {
		$state = "Open";
	}
	elseif (!$state) {
		$state = $default;
	}

	/* regular users can only pick states with type 2 for unclosed bugs */
	if($state != "All" && $state_types[$state] == 1 && $user_mode == 2) {
		/* If state was 'Feedback', set state to 'Open' automatically. */
		if($state == "Feedback" || $state == "No Feedback") {
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
	$date = date("Y-m-d");
	$versions = array(
		"4.4.2RC1",
		"4.4.1",
		"4CVS-{$date} (snap)",
		"4CVS-{$date} (CVS)",
		"5CVS-{$date} (snap)",
		"5CVS-{$date} (CVS)",
		"6CVS-{$date} (snap)",
		"6CVS-{$date} (CVS)",
		"Irrelevant",
	);
	echo "<option value=\"\">--Please Select--</option>\n";
	while (list(,$v) = each($versions)) {
		echo "<option", ($current == $v ? " selected" : ""), ">$v</option>\n";
		if ($current == $v) $use++;
	}
	if (!$use && $current) echo "<option selected>$current</option>\n";
	echo "<option value=\"earlier\">Earlier? Upgrade first!</option>\n";
}

function display_errors($errors)
{ 
	echo '<div class="errors">';
	if (count($errors) > 1) {
		echo "You need to do the following before your submission will be accepted:<ul>";
		foreach ($errors as $error) {
			echo "<li>$error</li>\n";
		}
		echo "</ul>";
	}
	else {
		echo $errors[0];
	}
	echo '</div>';
}

function display_warnings($warnings)
{ 
	echo '<div class="warnings">';
	if (count($warnings) > 1) {
		echo "Please notice the following:<ul>\n";
		foreach ($warnings as $warning) {
			echo "<li>$warning</li>\n";
		}
		echo "</ul>";
	}
	else {
		echo $warnings[0];
	}
	echo '</div>';
}

function show_types($current,$show_any,$default="") 
{
	include 'bugtypes.inc';

	if (!$current && !$default && !$show_any) {
		echo "<option value=\"none\">--Please Select--</option>\n";
	}
	elseif (!$current && $show_any == 1) {
		$current = "Any";
	}
	elseif (!$current) {
		$current = $default;
	}

	while (list($key,$value) = each($items)) {
		if ($show_any == 1 || $key != "Any") {
			echo "<option value=\"$key\"",
			((is_array($current) && in_array($key, $current)) || ($key == $current) ? " selected" : ""),
			">$value</option>\n";
			if ($key == $current) $use++;
		}
	}
}

function show_boolean_options($current) {
    $options = array('any', 'all', 'raw');
	while (list($val, $type) = each($options)) {
		echo '<input type="radio" name="boolean" value="', $val, '"';
		if ($val === $current) {
			echo ' CHECKED';
		}
		echo "> $type &nbsp;\n";
	}
}

function mail_bug_updates($bug, $in, $from, $ncomment, $edit=1)
{
    $text = array();
    $headers = array();

	/* Default addresses */
	list($mailto,$mailfrom) = get_bugtype_mail(oneof($in['bug_type'],$bug['bug_type']));

	/* Get rid of slashes in bug status */
	$bug['status'] = stripslashes($bug['status']);

	$tla = array(
		'Open'        => 'Opn',
		'Bogus'       => 'Bgs',
		'Feedback'    => 'Fbk',
		'No Feedback' => 'NoF',
		'Wont fix'    => 'WFx',
		'Duplicate'   => 'Dup',
		'Critical'    => 'Ctl',
		'Assigned'    => 'Asn',
		'Analyzed'    => 'Ana',
		'Verified'    => 'Ver',
		'Suspended'   => 'Sus',
		'Closed'      => 'Csd',
	);
    
    $headers[] = array(" ID", $bug['id']);
    
	switch ($edit) {
	case 3:
		$from_addr = spam_protect(rinse($in['commentemail']));
		$headers[] = array(" Comment by", $from_addr);
		$from = "\"$from_addr\" <$mailfrom>";
		break;
	case 2:
		$from_addr = spam_protect(txfield('email'));
		$headers[] = array(" User updated by", $from_addr);
		$from = "\"$from_addr\" <$mailfrom>";
		break;
	default:
		$from_addr = spam_protect($from);
		$headers[] = array(" Updated by", $from);
	}

	if (changed('sdesc')) {
		$headers[] = array("-Summary", $bug['sdesc']);
	}
	
	$prefix = " ";
	if (changed('email')) {
		$headers[] = array("-Reported By", spam_protect($bug['email']));
		$prefix = "+";
	}
	if ($f = spam_protect(txfield('email'))) {
		$headers[] = array($prefix.'Reported By', $f);
	}

	$fields = array(
		'status'      => 'Status',
		'bug_type'    => 'Bug Type',
		'php_os'      => 'Operating System',
		'php_version' => 'PHP Version',
		'assign'      => 'Assigned To'
	);

	foreach ($fields as $name => $desc) {
		$prefix = " ";
        if (changed($name)) {
			$headers[] = array("-$desc", $bug[$name]);
            $prefix = "+";
		}
		/* only fields that are set get added. */
		if ($f = txfield($name)) {
			$headers[] = array($prefix.$desc, $f);
        }
	}
    
    # make header output aligned
    $maxlength = 0;
    $actlength = 0;
    foreach ($headers as $v) {
        $actlength = strlen($v[0]) + 1;
        $maxlength = (($maxlength < $actlength) ? $actlength : $maxlength);
    }
    
    # align header content with headers (if a header contains
    # more than one line, wrap it intelligently)
    $header_text = "";
    $spaces = str_repeat(" ", $maxlength + 1);
    foreach ($headers as $v) {
        $hcontent = wordwrap($v[1], 72-$maxlength, "\n$spaces"); # wrap and indent
        $hcontent = rtrim($hcontent); # wordwrap may add spacer to last line
        $header_text .= str_pad($v[0] . ":", $maxlength) . " " . $hcontent . "\n";
    }

	if ($ncomment) {
		$text[] = " New Comment:\n\n".stripslashes($ncomment);
	}
	
	$text[] = get_old_comments($bug['id'], empty($ncomment));

	/* format mail so it looks nice, use 72 to make piners happy */
	$wrapped_text = wordwrap(join("\n",$text), 72);

	/* user text with attention, headers and previous messages */
    $user_text  = "ATTENTION! Do NOT reply to this email!\n" .
                  "To reply, use the web interface found at\n" .
                  "http://bugs.php.net/?id=$bug[id]&edit=2\n\n\n" .
                  $header_text .
                  $wrapped_text;

	/* developer text with headers, previous messages, and edit link */
	$dev_text = $header_text . 
                $wrapped_text .
                "\n-- \nEdit this bug report at " .
                "http://bugs.php.net/?id=$bug[id]&edit=1\n";

	/* send mail if status was changed or there is a comment */
	if ($in['status'] != $bug['status'] || $ncomment != "") {

		$old_status = $bug['status'];
		$new_status = $bug['status'];

		if ($in[status] != $bug[status] && $edit != 3) {	/* status changed */
			$new_status = $in['status'];
			$subj = "#{$bug['id']} [{$tla[$old_status]}->{$tla[$new_status]}]: ";
		} elseif ($edit == 3) {				/* comment */
			$subj = "#{$bug['id']} [Com]: ";
		} else {							/* status did not change and not comment */
			$subj = "#{$bug['id']} [{$tla[$bug['status']]}]: ";
		}

		if (!DEVBOX) {
			# the user gets sent mail with an envelope sender that ignores bounces
			@mail($bug['email'],
		    	  "Bug " . rinse($subj) . txfield('sdesc'),
		    	  $user_text,
		    	  "From: PHP Bug Database <$mailfrom>\n".
		    	  "X-PHP-Bug: $bug[id]\n".
		    	  "In-Reply-To: <bug-$bug[id]@bugs.php.net>",
		    	  "-fbounces-ignored@php.net");
			# but we go ahead and let the default sender get used for the list
			@mail($mailto,
		      	rinse($subj) . txfield('sdesc'),
		      	$dev_text,
		      	"From: $from\n".
		      	"X-PHP-Bug: $bug[id]\n".
		      	"X-PHP-Version: "    . stripslashes((($edit != 3) ? $in['php_version'] : $bug['php_version'])) . "\n" .
		      	"X-PHP-Category: "   . stripslashes((($edit != 3) ? $in['bug_type']    : $bug['bug_type']))    . "\n" .
		      	"X-PHP-OS: "         . stripslashes((($edit != 3) ? $in['php_os']      : $bug['php_os']))      . "\n" .
		      	"X-PHP-Status: "     . stripslashes($new_status) . "\n" .
		      	"X-PHP-Old-Status: " . stripslashes($old_status) . "\n" .
		      	"X-PHP-Reported-By: " . stripslashes(spam_protect(rinse($bug['email']))) . "\n" .
		      	"X-PHP-Updated-By: " . stripslashes($from_addr) . "\n" .
		      	"In-Reply-To: <bug-$bug[id]@bugs.php.net>");
		}
	}

	/* if a developer assigns someone else, let that other person know about it */
	if ($edit == 1 && $in['assign'] && $in['assign'] != $bug['assign']
		&& ($in['assign'] . "@php.net") != $from) {

		$unknowing_victim = $in['assign'] . '@php.net';

		if (!DEVBOX) {
			@mail($unknowing_victim,
			      rinse($subj) . txfield('sdesc'),
			      wordwrap($unknowing_victim . ' you have just been assigned to this bug by ' . $from . "\n\n") .
				  $dev_text,
			      "From: $from\n".
			      "X-PHP-Bug: $bug[id]\n".
			      "In-Reply-To: <bug-$bug[id]@bugs.php.net>");
		}
	}

}

function changed($n) {
	return $GLOBALS['in'][$n]
	    && stripslashes(trim($GLOBALS['in'][$n])) != trim($GLOBALS['bug'][$n]);
}

function field($n) {
	return oneof(clean($GLOBALS['in'][$n]),
	             htmlspecialchars($GLOBALS['bug'][$n]));
}

function format_date($date) {
  if (date("Y") != date("Y", $date)) {
    return date("j M Y g:ia T", $date);
  }
  return date("j M g:ia T", $date);
}

function txfield($n)
{
	return oneof(rinse($GLOBALS['in'][$n]),
	             $GLOBALS['bug'][$n]);
}

function get_old_comments ($bug_id, $all = 0) 
{
	$divider = str_repeat("-", 72);
	$max_message_length = 10 * 1024;
    $max_comments = 5;
    $output = ""; $count = 0;

	$res = @mysql_query("SELECT ts, email, comment FROM bugdb_comments WHERE bug=$bug_id ORDER BY ts DESC");

    if (!$res) return "";
    
    # skip the most recent unless the caller wanted all comments
    if (!$all) {
        $row = mysql_fetch_row($res);
        if (!$row) return "";
    }

    while (($row = mysql_fetch_row($res)) && strlen($output) < $max_message_length && $count++ < $max_comments) {
		$output .= "[$row[0]] ". spam_protect($row[1]) ."\n\n$row[2]\n\n$divider\n\n";
    }

    if (strlen($output) < $max_message_length && $count < $max_comments) {
    	$res=@mysql_query("SELECT ts1,email,ldesc FROM bugdb WHERE id=$bug_id");
    	if (!$res) return $output;
    	$row = mysql_fetch_row($res);
    	if (!$row) return $output;
		return ("\n\nPrevious Comments:\n$divider\n\n" . $output . "[$row[0]] ". spam_protect($row[1]) ."\n\n$row[2]\n\n$divider\n\n");
    }
    else {
		return ("\n\nPrevious Comments:\n$divider\n\n" . $output . "The remainder of the comments for this report are too long. To view\nthe rest of the comments, please view the bug report online at\n    http://bugs.php.net/$bug_id\n");
    }

    return "";
}

function addlinks($text) 
{
	$text = htmlspecialchars($text);
    $text = preg_replace("/((mailto|http|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/i","<a rel=\"nofollow\" href=\"\\1\">\\1</a>\\3",$text);
    # what the heck is this for?
    $text = preg_replace("/[.,]?-=-\"/", '"', $text);
	return $text;
}

/* validate an incoming bug report */
function incoming_details_are_valid ($in, $initial=0)
{
	global $bug;

	$errors = array();
	if ($initial || (!empty($in['email']) && $bug['email'] != $in['email'])) {
		if (!preg_match("/[.\\w+-]+@[.\\w-]+\\.\\w{2,}/i",$in['email'])) {
			$errors[] = "Please provide a valid email address.";
		}
	}

	if ($in['bug_type'] == "none") {
		$errors[] = "Please select an appropriate bug type.";
	}

	if (isset($in['php_version']) && $in['php_version'] == 'earlier') {
		$errors[] = "Please select a valid PHP version. If your PHP version is too old, please upgrade first and see if the problem has not already been fixed.";
	}

	if (empty($in['php_version']) && !is_phpversion_irrelevant($in['bug_type'])) {
		$errors[] = "Please select a valid PHP version.";
	}

	if ($in['php_version'] == 'Irrelevant' && !is_phpversion_irrelevant($in['bug_type'])) {
		$errors[] = "This category requires you to pick a PHP version.";
	}

	if (empty($in['sdesc'])) {
		$errors[] = "You must supply a summary of the bug you are reporting.";
	}

	if ($initial && empty($in['ldesc'])) {
		$errors[] = "You must supply a long description of the bug you are reporting.";
	}

	if ($initial && empty($in['passwd'])) {
		$errors[] = "You must supply a password for this bug report.";
	}

    if ($initial && !validate_captcha()) {
        $errors[] = 'Incorrect CAPTCHA';
    }

	return $errors;
}

function is_phpversion_irrelevant($bugtype)
{
	if (in_array($bugtype, array('Documentation problem','Livedocs problem','DocWeb problem','Website problem','Systems problem'))) {
		return true;
	}
	return false;
}

function get_bugtype_mail($bug_type) 
{
	global $mail_bugs_to;

	if (eregi("documentation", $bug_type)) {
		return array("phpdoc@lists.php.net", "phpdoc@lists.php.net");
	}
	elseif (eregi("livedocs", $bug_type)) {
		return array("phpdoc@lists.php.net", "phpdoc@lists.php.net");
	}
	elseif (eregi("docweb", $bug_type)) {
		return array("doc-web@lists.php.net", "doc-web@lists.php.net");
	}
	elseif (eregi("systems", $bug_type)) {
		return array("systems@php.net","systems@php.net");
	}
	elseif (eregi("website", $bug_type)) {
		return array("php-mirrors@lists.php.net","php-mirrors@lists.php.net");
	}
	elseif (eregi("pear", $bug_type)) {
		return array("pear-dev@lists.php.net","pear-dev@lists.php.net");
	}
	elseif (eregi("pecl", $bug_type)) {
		return array("pecl-dev@lists.php.net","pecl-dev@lists.php.net");
	}
	elseif (eregi("gtk", $bug_type)) {
		return array("php-gtk-dev@lists.php.net","php-gtk-dev@lists.php.net");
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
		case "Verified":
			return "#ffff00";
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

function format_search_string($search)
{
	// Function will be updated to make results more relevant.
	// Quick hack for indicating ignored words.
	
	global $mysql4;

	$min_word_len=3;
	
	$words = preg_split("/\s+/", stripslashes($search));
	$ignored = $used = array();
	foreach($words AS $match) {
		if(strlen($match) < $min_word_len) {
			array_push($ignored, $match);
		} else {
		        array_push($used, $match);
		}
	}
	
	if ($mysql4 && BOOLEAN_SEARCH) {
		// require all used words (all)
		if (BOOLEAN_SEARCH === 1) {
			$newsearch = '';
			foreach ($used as $word) {
				$newsearch .= "+$word ";
			}
			return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . addslashes($newsearch) . "' IN BOOLEAN MODE)", $ignored);

		// allow custom boolean search (raw)
		} elseif (BOOLEAN_SEARCH === 2) {
			return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . addslashes($search) . "' IN BOOLEAN MODE)", $ignored);
		}
	}
	// require any of the words (any)
	return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . addslashes($search) . "')", $ignored);
}

/* Figure out which IP the user is coming from avoiding RFC 1918 space */
function get_real_ip () {
	global $HTTP_X_FORWARDED_FOR, $REMOTE_ADDR;

	$ip = false;

	/**
	 * User is behind a proxy and check that we discard RFC1918 IP
	 * addresses if they are behind a proxy then only figure out which
	 * IP belongs to the user.  Might not  need any more hacking if
	 * there is a squid reverse proxy infront of apache.
	 */
	if (!empty($HTTP_X_FORWARDED_FOR)) {
		$ips = explode (", ", $HTTP_X_FORWARDED_FOR);
		if ($ip) { array_unshift($ips, $ip); $ip = false; }
		for ($i = 0; $i < count($ips); $i++) {
			/**
			 * Skip RFC 1918 IP's 10.0.0.0/8, 172.16.0.0/12 and
			 * 192.168.0.0/16 -- jim kill me later with my regexp pattern
			 * below.
			 */
			if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i]) &&
				preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $ips[$i])) {
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $REMOTE_ADDR);
}

/**
 * Sets <var>$_SESSION['captcha']</var> and
 * <var>$_SESSION['captcha_time']</var> then prints the XHTML that
 * displays a CAPTCHA image and a form input element
 *
 * Only generate a new <var>$_SESSION['captcha']</var> if it doesn't exist
 * yet.  This avoids the problem of the CAPTCHA value being changed but the
 * old image remaining in the browser's cache.  This is necessary because
 * caching can not be reliably disabled.
 *
 * Use upper case letters to reduce confusion with some of these fonts.
 * Input is passed through strtoupper() before comparison.
 *
 * Don't use "I" or "O" to avoid confusion with numbers.  Don't use digits
 * because some of the fonts don't handle them.
 *
 * @return string  the CAPTCHA image and form intut
 *
 * @see validate_captcha(), captcha-image.php
 */
function generate_captcha()
{
    if (!isset($_SESSION['captcha'])) {
        $_SESSION['captcha'] = '';
        $useable = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        for ($i = 0; $i < 4; $i++) {
            $_SESSION['captcha'] .= substr($useable, mt_rand(0, 23), 1);
        }
        $_SESSION['captcha_time'] = time();
    }
    return 'Type <img src="/captcha-image.php?x=' . time()
           . '" alt="If you are unable to'
           . ' read this image, click the help link to the right of'
           . ' the input box" align="top" /> into this box...'
           . ' <input type="text" size="4" maxlength="4" name="captcha" />'
           . ' (<a href="/captcha-help.php" target="_blank">help</a>)'
           . ' <br />If this image is hard to read, reload the page.';

}

/**
 * Check if the CAPTCHA value submitted by the user in
 * <var>$_POST['captcha']</var> matches <var>$_SESSION['captcha']</var>
 * and that the submission was made within the allowed time frame
 * of the CAPTCHA being generated
 *
 * If the two values aen't the same or the length of time between CAPTCHA
 * generation and form submission is too long, this function will unset()
 * <var>$_SESSION['captcha']</var>.  Unsetting it will cause
 * generate_captcha() to come up with a new CAPTCHA value and image.
 * This prevents brute force attacks.
 *
 * Similarly, if the submission is correct <var>$_SESSION['captcha']</var>
 * is unset() in order to keep robots from making multiple requests with
 * a correctly guessed CAPTCHA value.
 *
 * @param int $max_age  the length of time in seconds since the CAPTCHA was
 *                      generated during which a submission should be
 *                      considered valid.  Default is 300 seconds
 *                      (aka 5 minutes).
 *
 * @return bool  true if input matches captcha, false if not
 *
 * @see generate_captcha(), captcha-image.php
 */
function validate_captcha($max_age = 300)
{
    if (!isset($_POST['captcha']) ||
        !isset($_SESSION['captcha']) ||
        (time() - $_SESSION['captcha_time']) > $max_age ||
        $_SESSION['captcha'] != strtoupper($_POST['captcha']))
    {
        unset($_SESSION['captcha']);
        unset($_SESSION['captcha_time']);
        return false;
    } else {
        unset($_SESSION['captcha']);
        unset($_SESSION['captcha_time']);
        return true;
    }
}

/**
 * Turns the provided email address into a "mailto:" hyperlink.
 *
 * The link and link text are obfuscated by alternating Ord and Hex
 * entities.
 *
 * @param string $email     the email address to make the link for
 * @param string $linktext  a string for the visible part of the link.
 *                           If not provided, the email address is used.
 * @param string $extras    a string of extra attributes for the <a> element
 *
 * @return string  the HTML hyperlink of an email address
 */
function make_mailto_link($email, $linktext = '', $extras = '')
{
    $tmp = '';
    for ($i = 0, $l = strlen($email); $i<$l; $i++) {
        if ($i % 2) {
            $tmp .= '&#' . ord($email[$i]) . ';';
        } else {
            $tmp .= '&#x' . dechex(ord($email[$i])) . ';';
        }
    }

    return '<a ' . $extras . ' href="&#x6d;&#97;&#x69;&#108;&#x74;&#111;&#x3a;'
           . $tmp . '">' . ($linktext != '' ? $linktext : $tmp) . '</a>';
}
?>
