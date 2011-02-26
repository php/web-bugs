<?php

/* User flags */
define('BUGS_NORMAL_USER',  1<<0);
define('BUGS_DEV_USER',     1<<1);
define('BUGS_TRUSTED_DEV',  1<<2);
define('BUGS_SECURITY_DEV', 1<<3);

/* Contains functions and variables used throughout the bug system */

// used in mail_bug_updates(), below, and class for search results
$tla = array(
	'Open'			=> 'Opn',
	'Bogus'			=> 'Bgs',
	'Feedback'		=> 'Fbk',
	'No Feedback'	=> 'NoF',
	'Wont fix'		=> 'Wfx',
	'Duplicate'		=> 'Dup',
	'Critical'		=> 'Ctl',
	'Assigned'		=> 'Asn',
	'Analyzed'		=> 'Ana',
	'Verified'		=> 'Ver',
	'Suspended'		=> 'Sus',
	'Closed'		=> 'Csd',
	'Spam'			=> 'Spm',
	'Re-Opened'		=> 'ReO',
	'To be documented' => 'Tbd',
);

$bug_types = array(
	'Bug'						=> 'Bug',
	'Feature/Change Request'	=> 'Req',
	'Documentation Problem'		=> 'Doc',
	'Security'					=> 'Sec Bug'
);

// Used in show_state_options()
$state_types = array (
	'Open'			=> 2,
	'Closed'		=> 2,
	'Re-Opened'		=> 1,
	'Duplicate'		=> 1,
	'Critical'		=> 1,
	'Assigned'		=> 2,
	'Not Assigned'	=> 0,
	'Analyzed'		=> 1,
	'Verified'		=> 1,
	'Suspended'		=> 1,
	'Wont fix'		=> 1,
	'No Feedback'	=> 1,
	'Feedback'		=> 1,
	'Old Feedback'	=> 0,
	'Stale'			=> 0,
	'Fresh'			=> 0,
	'Bogus'			=> 1,
	'To be documented' => 1,
	'Spam'			=> 1,
	'All'			=> 0,
);

/**
 * Authentication
 */
function verify_password($user, $pass)
{
	global $errors;

	$post = http_build_query(
		array(
			'token' => getenv('AUTH_TOKEN'),
			'username' => $user,
			'password' => $pass,
		)
	);

	$opts = array(
		'method'	=> 'POST',
		'header'	=> 'Content-type: application/x-www-form-urlencoded',
		'content'	=> $post,
	);

	$ctx = stream_context_create(array('http' => $opts));

	$s = file_get_contents('https://master.php.net/fetch/cvsauth.php', false, $ctx);

	$a = @unserialize($s);
	if (!is_array($a)) {
		$errors[] = "Failed to get authentication information.\nMaybe master is down?\n";
		return false;
	}
	if (isset($a['errno'])) {
		$errors[] = "Authentication failed: {$a['errstr']}\n";
		return false;
	}

	return true;
}

function bugs_has_access ($bug_id, $bug, $pw, $user_flags)
{
	global $auth_user;
	
	if ($bug['private'] != 'Y') {
		return true;
	}

	// When the bug is private, only the submitter, trusted devs, security devs and assigned dev
	// should see the report info
	if ($user_flags & (BUGS_SECURITY_DEV | BUGS_TRUSTED_DEV)) {
		// trusted and security dev
		return true;
	} else if (($user_flags == BUGS_NORMAL_USER) && $pw != '' && verify_bug_passwd($bug_id, $pw)) {
		// The submitter
		return true;
	} else if (($user_flags & BUGS_DEV_USER) && $bug['assign'] != '' &&
		strtolower($bug['assign']) == strtolower($auth_user->handle)) {
		// The assigned dev
		return true;
	}
	
	return false;
}

function bugs_authenticate (&$user, &$pw, &$logged_in, &$user_flags)
{
	global $auth_user, $ROOT_DIR;

	// Default values
	$user = '';
	$pw = '';
	$logged_in = false;
	
	$user_flags = BUGS_NORMAL_USER;

	// Set username and password
	if (!empty($_POST['pw'])) {
		if (empty($_POST['user'])) {
			$user = '';
		} else {
			$user = htmlspecialchars($_POST['user']);
		}
		$pw = $_POST['pw'];

		// Remember password / user next time
		if (isset($_POST['save'])) { # non-developers don't have $user set
			if (DEVBOX) {
				$domain = null;
			} else {
				$domain = '.php.net';
			}
			setcookie('MAGIC_COOKIE', base64_encode("{$user}:{$pw}"), time() + 3600 * 24 * 12, '/', $domain);
		}
	} elseif (isset($auth_user) && is_object($auth_user) && $auth_user->handle) {
		$user = $auth_user->handle;
		$pw = $auth_user->password;
	} elseif (isset($_COOKIE['MAGIC_COOKIE'])) {
		@list($user, $pw) = explode(':', base64_decode($_COOKIE['MAGIC_COOKIE']), 2);
		if ($pw === null) {
			$pw = '';
		}
	}

	// Authentication and user level check
	// User levels are: reader (0), commenter/patcher/etc. (edit = 3), submitter (edit = 2), developer (edit = 1)
	if ($user != '' && $pw != '' && verify_password($user, $pw)) {
		$user_flags = BUGS_DEV_USER;
		$logged_in = 'developer';
		$auth_user->handle = $user;
		$auth_user->email = "{$user}@php.net";
		$auth_user->name = $user;
	} else {
		$auth_user->email = isset($_POST['in']['email']) ? $_POST['in']['email'] : '';
		$auth_user->handle = '';
		$auth_user->name = '';
	}

	// Check if developer is trusted
	if ($logged_in == 'developer') {
		require_once "{$ROOT_DIR}/include/trusted-devs.php";
		
		if (in_array($user, $trusted_developers)) {
			$user_flags |= BUGS_TRUSTED_DEV;
		}
		if (in_array($user, $security_developers)) {
			$user_flags |= BUGS_SECURITY_DEV;
		}
	}
}

/**
 * Fetches pseudo packages from database
 *
 * @param string	$project			define what project pseudo packages are returned
 * @param bool		$return_disabled	whether to return read-only items, defaults to true
 *
 * @return array	array of pseudo packages
 */
function get_pseudo_packages ($project, $return_disabled = true)
{
	require_once 'Tree/Tree.php';

	$where = "project IN ('', '$project')";
	if (!$return_disabled)
		$where.= " AND disabled = 0";

	$pseudo_pkgs = array();
	$tree = Tree::setup (
		'Memory_MDB2simple',
		DATABASE_DSN,
		array (
			'order' => 'disabled, id',
			'whereAddOn' => $where,
			'table' => 'bugdb_pseudo_packages',
			'columnNameMaps' => array (
				'parentId' => 'parent',
			),
		)
	);
	$tree->setup();

	foreach ($tree->data as $data)
	{
		if (isset($data['children']))
		{
			$pseudo_pkgs[$data['name']] = array($data['long_name'], $data['disabled']);
			$long_names = array();
			foreach ($data['children'] as $k => $v) {
				$long_names[$k] = strtolower($v['long_name']);
			}
			array_multisort($long_names, SORT_ASC, SORT_STRING, $data['children']);
			foreach ($data['children'] as $child)
			{
				$pseudo_pkgs[$child['name']] = array("&nbsp;&nbsp;&nbsp;&nbsp;{$child['long_name']}", $child['disabled']);
			}
			
		} else if (!isset($pseudo_pkgs[$data['name']]))
			$pseudo_pkgs[$data['name']] = array($data['long_name'], $data['disabled']);
	}

	return $pseudo_pkgs;
}

/* Primitive check for SPAM. Add more later. */
function is_spam($string)
{
	if (substr_count(strtolower($string), 'http://') > 5) {
		return true;
	}
	if (preg_match("/(asian)|(spy)|(bdsm)|(massage)|(mortage)|(sex)(?<!OutOfBoundsEx(?=ception))|(11nong)|(oxycontin)|(distance-education)|(sismatech)|(justiceplan)|(prednisolone)|(baclofen)|(diflucan)|(unbra.se)|(objectis)/i", $string)) {
		return true;
	}
	if (preg_match("~/Members/~", $string)) {
		return true;
	}
	return false;
}


/**
 * Obfuscates email addresses to hinder spammer's spiders
 *
 * Turns "@" into character entities that get interpreted as "at" and
 * turns "." into character entities that get interpreted as "dot".
 *
 * @param string $txt		the email address to be obfuscated
 * @param string $format	how the output will be displayed ('html', 'text')
 *
 * @return string	the altered email address
 */
function spam_protect($txt, $format = 'html')
{
	/* php.net addresses are not protected! */
	if (preg_match('/^(.+)@php\.net/i', $txt)) {
		return $txt;
	}
	if ($format == 'html') {
		$translate = array(
			'@' => ' &#x61;&#116; ',
			'.' => ' &#x64;&#111;&#x74; ',
		);
	} else {
		$translate = array(
			'@' => ' at ',
			'.' => ' dot ',
		);
	}
	return strtr($txt, $translate);
}

/**
 * Escape strings so they can be used as literals in queries
 *
 * @param string|array	$in		data to be sanitized. If it's an array, each element is sanitized.
 *
 * @return string|array  the sanitized data
 *
 * @see oneof(), field(), txfield()
 */
function escapeSQL($in)
{
	global $dbh;

	if (is_array($in)) {
		$out = array();
		foreach ($in as $key => $value) {
			$out[$key] = $dbh->escape($value);
		}
		return $out;
	} else {
		return $dbh->escape($in);
	}
}

/**
 * Goes through each variable submitted and returns the value
 * from the first variable which has a non-empty value
 *
 * Handy function for when you're dealing with user input or a default.
 *
 * @param mixed		as many variables as you wish to check
 *
 * @return mixed	the value, if any
 *
 * @see escapeSQL(), field(), txfield()
 */
function oneof()
{
	foreach (func_get_args() as $arg) {
		if ($arg) {
			return $arg;
		}
	}
}

/**
 * Returns the data from the field requested and sanitizes
 * it for use as HTML
 *
 * If the data from a form submission exists, that is used.
 * But if that's not there, the info is obtained from the database.
 *
 * @param string $n		the name of the field to be looked for
 *
 * @return mixed		the data requested
 *
 * @see escapeSQL(), oneof(), txfield()
 */
function field($n)
{
	return oneof(isset($_POST['in']) ?
		htmlspecialchars($_POST['in'][$n]) : null,
			htmlspecialchars($GLOBALS['bug'][$n]));
}

/**
 * Escape string so it can be used as HTML
 *
 * @param string $in	the string to be sanitized
 *
 * @return string		the sanitized string
 *
 * @see txfield()
 */
function clean($in)
{
	return mb_encode_numericentity($in,
		array(
			0x0, 0x8, 0, 0xffffff,
			0xb, 0xc, 0, 0xffffff,
			0xe, 0x1f, 0, 0xffffff,
			0x22, 0x22, 0, 0xffffff,
			0x26, 0x27, 0, 0xffffff,
			0x3c, 0x3c, 0, 0xffffff,
			0x3e, 0x3e, 0, 0xffffff,
			0x7f, 0x84, 0, 0xffffff,
			0x86, 0x9f, 0, 0xffffff,
			0xfdd0, 0xfdef, 0, 0xffffff,
			0x1fffe, 0x1ffff, 0, 0xffffff,
			0x2fffe, 0x2ffff, 0, 0xffffff,
			0x3fffe, 0x3ffff, 0, 0xffffff,
			0x4fffe, 0x4ffff, 0, 0xffffff,
			0x5fffe, 0x5ffff, 0, 0xffffff,
			0x6fffe, 0x6ffff, 0, 0xffffff,
			0x7fffe, 0x7ffff, 0, 0xffffff,
			0x8fffe, 0x8ffff, 0, 0xffffff,
			0x9fffe, 0x9ffff, 0, 0xffffff,
			0xafffe, 0xaffff, 0, 0xffffff,
			0xbfffe, 0xbffff, 0, 0xffffff,
			0xcfffe, 0xcffff, 0, 0xffffff,
			0xdfffe, 0xdffff, 0, 0xffffff,
			0xefffe, 0xeffff, 0, 0xffffff,
			0xffffe, 0xfffff, 0, 0xffffff,
			0x10fffe, 0x10ffff, 0, 0xffffff,
		),
	'UTF-8');
}

/**
 * Returns the data from the field requested and sanitizes
 * it for use as plain text
 *
 * If the data from a form submission exists, that is used.
 * But if that's not there, the info is obtained from the database.
 *
 * @param string $n		the name of the field to be looked for
 *
 * @return mixed		the data requested
 *
 * @see clean()
 */
function txfield($n, $bug = null, $in = null)
{
	$one = (isset($in) && isset($in[$n])) ? $in[$n] : false;
	if ($one) {
		return $one;
	}

	$two = (isset($bug) && isset($bug[$n])) ? $bug[$n] : false;
	if ($two) {
		return $two;
	}
}

/**
 * Prints age <option>'s for use in a <select>
 *
 * @param string $current	the field's current value
 *
 * @return void
 */
function show_byage_options($current)
{
	$opts = array(
		'0' => 'the beginning',
		'1'	=> 'yesterday',
		'7'	=> '7 days ago',
		'15' => '15 days ago',
		'30' => '30 days ago',
		'90' => '90 days ago',
	);
	while (list($k,$v) = each($opts)) {
		echo "<option value=\"$k\"", ($current==$k ? ' selected="selected"' : ''), ">$v</option>\n";
	}
}

/**
 * Prints a list of <option>'s for use in a <select> element
 * asking how many bugs to display
 *
 * @param int $limit	the presently selected limit to be used as the default
 *
 * @return void
 */
function show_limit_options($limit = 30)
{
	for ($i = 10; $i < 100; $i += 10) {
		echo '<option value="' . $i . '"';
		if ($limit == $i) {
			echo ' selected="selected"';
		}
		echo ">$i bugs</option>\n";
	}

	echo '<option value="All"';
	if ($limit == 'All') {
		echo ' selected="selected"';
	}
	echo ">All</option>\n";
}

/**
 * Prints bug type <option>'s for use in a <select>
 *
 * Options include "Bug", "Documentation Problem" and "Feature/Change Request."
 *
 * @param string	$current	bug's current type
 * @param bool		$all		whether or not 'All' should be an option
 *
 * @retun void
 */
function show_type_options($current = 'Bug', $all = false)
{
	global $bug_types;

	if ($all) {
		if (!$current) {
			$current = 'All';
		}
		echo '<option value="All"';
		if ($current == 'All') {
			echo ' selected="selected"';
		}
		echo ">All</option>\n";
	} elseif (!$current) {
		$current = 'bug';
	} else {
		$current = strtolower($current);
	}

	foreach ($bug_types as $k => $v) {
		echo '<option value="', $k, '"', (($current == strtolower($k)) ? ' selected="selected"' : ''), ">{$k}</option>\n";
	}
}

/**
 * Prints bug state <option>'s for use in a <select> list
 *
 * @param string $state		the bug's present state
 * @param int	$user_mode	the 'edit' mode
 * @param string $default	the default value
 *
 * @return void
 */
function show_state_options($state, $user_mode = 0, $default = '', $assigned = 0)
{
	global $state_types;

	if (!$state && !$default) {
		$state = $assigned ? 'Assigned' : 'Open';
	} elseif (!$state) {
		$state = $default;
	}

// echo '</select>', "state: $state, user_mode: $user_mode, default: $default", '<select>';


	/* regular users can only pick states with type 2 for unclosed bugs */
	if ($state != 'All' && $state_types[$state] == 1 && $user_mode == 2) {
		switch ($state)
		{
			/* If state was 'Feedback', set state automatically to 'Assigned' if the bug was
			 * assigned to someone before it to be set to 'Feedback', otherwise set it to 'Open'.
			 */
			case 'Feedback':
				if ($assigned) {
					echo "<option>Assigned</option>\n";
				} else {	
					echo "<option>Open</option>\n";
				}
				break;
			case 'No Feedback':
				echo "<option>Re-Opened</option>\n";
				break;
			default:
				echo "<option>$state</option>\n";
				break;
		}
		/* Allow state 'Closed' always when current state is not 'Bogus' */
		if ($state != 'Bogus') {
			echo "<option>Closed</option>\n";
		}
	} else {
		foreach($state_types as $type => $mode) {
			if (($state == 'Closed' && $type == 'Open')
				|| ($state == 'Open' && $type == 'Re-Opened')) {
				continue;
			}
			if ($mode >= $user_mode) {
				echo '<option';
				if ($type == $state) {
					echo ' selected="selected"';
				}
				echo ">$type</option>\n";
			}
		}
	}
}

/**
 * Prints bug resolution <option>'s for use in a <select> list
 *
 * @param string $current	the bug's present state
 * @param int	$expande	whether or not a longer explanation should be displayed
 *
 * @return void
 */
function show_reason_types($current = '', $expanded = 0)
{
	global $RESOLVE_REASONS;

	if ($expanded) {
		echo '<option value=""></option>' . "\n";
	}
	foreach ($RESOLVE_REASONS as $val)
	{
		if (empty($val['package_name'])) {
			$sel = ($current == $val['name']) ? " selected='selected'" : '';
			echo "<option value='{$val['name']}' {$sel} >{$val['title']}";
			if ($expanded) {
				echo " ({$val['status']})";
			}
			echo "</option>\n";
		}
	}
}

/**
 * Prints PHP version number <option>'s for use in a <select> list
 *
 * @param string $current	the bug's current version number
 * @param string $default	a version number that should be the default
 *
 * @return void
 */
function show_version_options($current, $default = '')
{
	global $ROOT_DIR;

	$use = 0;

	echo '<option value="">--Please Select--</option>' , "\n";
	require_once "{$ROOT_DIR}/include/php_versions.php";
	while (list(,$v) = each($versions)) {
		echo '<option';
		if ($current == $v) {
			echo ' selected="selected"';
		}
		echo '>' , htmlspecialchars($v) , "</option>\n";
		if ($current == $v) {
			$use++;
		}
	}
	if (!$use && $current) {
		echo '<option selected="selected">' , htmlspecialchars($current) , "</option>\n";
	}
	echo '<option>Irrelevant</option>', "\n";
	echo '<option value="earlier">Earlier? Upgrade first!</option>', "\n";
}

/**
 * Prints package name <option>'s for use in a <select> list
 *
 * @param string $current	the bug's present state
 * @param int	$show_any	whether or not 'Any' should be an option. 'Any'
 *							will only be printed if no $current value exists.
 * @param string $default 	the default value
 *
 * @return void
 */
function show_package_options($current, $show_any, $default = '')
{
	global $dbh, $pseudo_pkgs;
	static $bug_items;

	if (!isset($bug_items)) {
		$bug_items = $pseudo_pkgs;
	}

	$use = 0;

	if (!$current && (!$default || $default == 'none') && !$show_any) {
		echo "<option value=\"none\">--Please Select--</option>\n";
	} elseif (!$current && $show_any == 1) {
		$current = 'Any';
	} elseif (!$current) {
		$current = $default;
	}

	if (!is_array($bug_items))
		return;

	foreach ($bug_items as $key => $value) {
		if ($show_any == 1 || $key != 'Any') {
			echo "<option value=\"$key\"";
			if ((is_array($current) && in_array($key, $current)) ||
				($key == $current))
			{
				echo ' selected="selected"';
			}
			// Show disabled categories with different background color in listing
			echo (($value[1]) ? ' style="background-color:#eee;"' : ''), ">{$value[0]}</option>\n";
			if ($key == $current) {
				$use++;
			}
		}
	}
}

/**
 * Prints a series of radio inputs to determine how the search
 * term should be looked for
 *
 * @param string $current	the users present selection
 *
 * @return void
 */
function show_boolean_options($current)
{
	$options = array('any', 'all', 'raw');
	while (list($val, $type) = each($options)) {
		echo '<input type="radio" name="boolean" value="', $val, '"';
		if ($val === $current) {
			echo ' checked="checked"';
		}
		echo " />$type&nbsp;\n";
	}
}

/**
 * Display errors or warnings as a <ul> inside a <div>
 *
 * Here's what happens depending on $in:
 *	 + string:	value is printed
 *	 + array: 	looped through and each value is printed.
 *				If array is empty, nothing is displayed.
 *				If a value contains a PEAR_Error object,
 *	 + PEAR_Error: prints the value of getMessage() and getUserInfo()
 *				if DEVBOX is true, otherwise prints data from getMessage().
 *
 * @param string|array|PEAR_Error $in see long description
 * @param string $class		name of the HTML class for the <div> tag. ("errors", "warnings")
 * @param string $head		string to be put above the message
 *
 * @return bool		true if errors were submitted, false if not
 */
function display_bug_error($in, $class = 'errors', $head = 'ERROR:')
{
	if (PEAR::isError($in)) {
		if (DEVBOX == true) {
			$in = array($in->getMessage() . '... ' . $in->getUserInfo());
		} else {
			$in = array($in->getMessage());
		}
	} elseif (!is_array($in)) {
		$in = array($in);
	} elseif (!count($in)) {
		return false;
	}

	echo "<div class='{$class}'>{$head}<ul>";
	foreach ($in as $msg) {
		if (PEAR::isError($msg)) {
			if (DEVBOX == true) {
				$msg = $msg->getMessage() . '... ' . $msg->getUserInfo();
			} else {
				$msg = $msg->getMessage();
			}
		}
		echo '<li>' , htmlspecialchars($msg) , "</li>\n";
	}
	echo "</ul></div>\n";
	return true;
}

/**
 * Prints a message saying the action succeeded
 *
 * @param string $in	the string to be displayed
 *
 * @return void
 */
function display_bug_success($in)
{
	echo "<div class='success'>{$in}</div>\n";
}

/**
 * Returns array of changes going to be made
 */
function bug_diff($bug, $in)
{
	$changed = array();

	if ($in['email'] && (trim($in['email']) != trim($bug['email']))) {
		$changed['reported_by']['from'] = $bug['email'];
		$changed['reported_by']['to'] = spam_protect(txfield('email', $bug, $in), 'text');
	}

	$fields = array(
		'sdesc'				=> 'Summary',
		'status'			=> 'Status',
		'bug_type'			=> 'Type',
		'package_name'		=> 'Package',
		'php_os'			=> 'Operating System',
		'php_version'		=> 'PHP Version',
		'assign'			=> 'Assigned To',
		'block_user_comment' => 'Block user comment',
		'private'			=> 'Private report',
		'cve_id'			=> 'CVE-ID'
	);

	foreach (array_keys($fields) as $name) {
		if (array_key_exists($name, $in) && array_key_exists($name, $bug)) {
			$to   = trim($in[$name]);
			$from = trim($bug[$name]);
			if ($from != $to) {
				$changed[$name]['from'] = $from;
				$changed[$name]['to'] = $to;
			}
		}
	}

	return $changed;
}

function bug_diff_render_html($diff)
{
	$fields = array(
		'sdesc'				=> 'Summary',
		'status'			=> 'Status',
		'bug_type'			=> 'Type',
		'package_name'		=> 'Package',
		'php_os'			=> 'Operating System',
		'php_version'		=> 'PHP Version',
		'assign'			=> 'Assigned To',
		'block_user_comment' => 'Block user comment',
		'private'			=> 'Private report',
		'cve_id'			=> 'CVE-ID'
	);

	// make diff output aligned
	$actlength = $maxlength = 0;
	foreach (array_keys($diff) as $v) {
		$actlength = strlen($fields[$v]) + 2;
		$maxlength = ($maxlength < $actlength) ? $actlength : $maxlength;
	}

	$changes = '<div class="changeset">' . "\n";
	$spaces = str_repeat(' ', $maxlength + 1);
	foreach ($diff as $name => $content) {
		// align header content with headers (if a header contains
		// more than one line, wrap it intelligently)
		$field = str_pad($fields[$name] . ':', $maxlength);
		$from = wordwrap('-'.$field.$content['from'], 72 - $maxlength, "\n$spaces"); // wrap and indent
		$from = rtrim($from); // wordwrap may add spacer to last line
		$to	= wordwrap('+'.$field.$content['to'], 72 - $maxlength, "\n$spaces"); // wrap and indent
		$to	= rtrim($to); // wordwrap may add spacer to last line
		$changes .= '<span class="removed">' . clean($from) . '</span>' . "\n";
		$changes .= '<span class="added">' . clean($to) . '</span>' . "\n";
	}
	$changes .= '</div>';

	return $changes;
}

/**
 * Send an email notice about bug aditions and edits
 *
 * @param
 *
 * @return void
 */
function mail_bug_updates($bug, $in, $from, $ncomment, $edit = 1, $id = false)
{
	global $tla, $bug_types, $siteBig, $site_url, $basedir;

	$text = array();
	$headers = array();
	$changed = bug_diff($bug, $in);
	$from = str_replace(array("\n", "\r"), '', $from);

	/* Default addresses */
	list($mailto, $mailfrom, $Bcc) = get_package_mail(oneof($in['package_name'], $bug['package_name']), $id, oneof($in['bug_type'], $bug['bug_type']));

	$headers[] = array(' ID', $bug['id']);

	switch ($edit) {
		case 4:
			$headers[] = array(' Patch added by', $from);
			$from = "\"{$from}\" <{$mailfrom}>";
			break;
		case 3:
			$headers[] = array(' Comment by', $from);
			$from = "\"{$from}\" <{$mailfrom}>";
			break;
		case 2:
			$from = spam_protect(txfield('email', $bug, $in), 'text');
			$headers[] = array(' User updated by', $from);
			$from = "\"{$from}\" <{$mailfrom}>";
			break;
		default:
			$headers[] = array(' Updated by', $from);
	}

	$fields = array(
		'email'				=> 'Reported by',
		'sdesc'				=> 'Summary',
		'status'			=> 'Status',
		'bug_type'			=> 'Type',
		'package_name'		=> 'Package',
		'php_os'			=> 'Operating System',
		'php_version'		=> 'PHP Version',
		'assign'			=> 'Assigned To',
		'block_user_comment' => 'Block user comment',
		'private'			=> 'Private report',
		'cve_id'			=> 'CVE-ID',
	);

	foreach ($fields as $name => $desc) {
		$prefix = ' ';
		if (isset($changed[$name])) {
			$headers[] = array("-{$desc}", $changed[$name]['from']);
			$prefix = '+';
		}

		/* only fields that are set get added. */
		if ($f = txfield($name, $bug, $in)) {
			if ($name == 'email') {
				$f = spam_protect($f, 'text');
			}
			$foo = isset($changed[$name]['to']) ? $changed[$name]['to'] : $f;
			$headers[] = array($prefix.$desc, $foo);
		}
	}

	/* Make header output aligned */
	$maxlength = 0;
	$actlength = 0;
	foreach ($headers as $v) {
		$actlength = strlen($v[0]) + 1;
		$maxlength = (($maxlength < $actlength) ? $actlength : $maxlength);
	}

	/* Align header content with headers (if a header contains more than one line, wrap it intelligently) */
	$header_text = '';

	$spaces = str_repeat(' ', $maxlength + 1);
	foreach ($headers as $v) {
		$hcontent = wordwrap($v[1], 72 - $maxlength, "\n{$spaces}"); // wrap and indent
		$hcontent = rtrim($hcontent); // wordwrap may add spacer to last line
		$header_text .= str_pad($v[0] . ':', $maxlength) . " {$hcontent}\n";
	}

	if ($ncomment) {
		$ncomment = preg_replace('#<div class="changeset">(.*)</div>#sUe', "ltrim(strip_tags('\\1'))", $ncomment);
		$text[] = " New Comment:\n\n{$ncomment}";
	}

	$old_comments = get_old_comments($bug['id'], empty($ncomment));
	$old_comments = preg_replace('#<div class="changeset">(.*)</div>#sUe', "ltrim(strip_tags('\\1'))", $old_comments);

	$text[] = $old_comments;

	/* format mail so it looks nice, use 72 to make piners happy */
	$wrapped_text = wordwrap(join("\n", $text), 72);

	/* user text with attention, headers and previous messages */
	$user_text = <<< USER_TEXT
ATTENTION! Do NOT reply to this email!
To reply, use the web interface found at
http://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=2

{$header_text}
{$wrapped_text}
USER_TEXT;

	/* developer text with headers, previous messages, and edit link */
	$dev_text = <<< DEV_TEXT
Edit report at http://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=1

{$header_text}
{$wrapped_text}

-- 
Edit this bug report at http://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=1
DEV_TEXT;

	if (preg_match('/.*@php\.net\z/', $bug['email'])) {
		$user_text = $dev_text;
	}

	// Defaults
	$subj = $bug_types[$bug['bug_type']];
	$sdesc = txfield('sdesc', $bug, $in);

	/* send mail if status was changed, there is a comment, private turned on/off or the bug type was changed to/from Security */
	if ($in['status'] != $bug['status'] || $ncomment != '' ||
		(isset($in['private']) && $in['private'] != $bug['private']) ||
		(isset($in['bug_type']) && $in['bug_type'] != $bug['bug_type'] &&
			($in['bug_type'] == 'Security' || $bug['bug_type'] == 'Security'))) {
		if (isset($in['bug_type']) && $in['bug_type'] != $bug['bug_type']) {
			$subj = $bug_types[$bug['bug_type']] . '->' . $bug_types[$in['bug_type']];
		}

		$old_status = $bug['status'];
		$new_status = $bug['status'];

		if ($in['status'] != $bug['status'] && $edit != 3) {	/* status changed */
			$new_status = $in['status'];
			$subj .= " #{$bug['id']} [{$tla[$old_status]}->{$tla[$new_status]}]";
		} elseif ($edit == 4) {	/* patch */
			$subj .= " #{$bug['id']} [PATCH]";
		} elseif ($edit == 3) {	/* comment */
			$subj .= " #{$bug['id']} [Com]";
		} else {	/* status did not change and not comment */
			$subj .= " #{$bug['id']} [{$tla[$bug['status']]}]";
		}

		// the user gets sent mail with an envelope sender that ignores bounces
		bugs_mail(
			$bug['email'],
			"{$subj}: {$sdesc}",
			$user_text,
			"From: {$siteBig} Bug Database <{$mailfrom}>\n" .
			"Bcc: {$Bcc}\n" .
			"X-PHP-Bug: {$bug['id']}\n" .
			"X-PHP-Site: {$siteBig}\n" .
			"In-Reply-To: <bug-{$bug['id']}@{$site_url}>"
		);

		// Spam protection
		$tmp = $edit != 3 ? $in : $bug;
		$tmp['new_status'] = $new_status;
		$tmp['old_status'] = $old_status;
		foreach (array('bug_type', 'php_version', 'package_name', 'php_os') as $field) {
			$tmp[$field] = strtok($tmp[$field], "\r\n");
		}

		// but we go ahead and let the default sender get used for the list
		bugs_mail(
			$mailto,
			"{$subj}: {$sdesc}",
			$dev_text,
			"From: {$from}\n".
			"X-PHP-Bug: {$bug['id']}\n" .
			"X-PHP-Site: {$siteBig}\n" .
			"X-PHP-Type: {$tmp['bug_type']}\n" .
			"X-PHP-Version: {$tmp['php_version']}\n" .
			"X-PHP-Category: {$tmp['package_name']}\n" .
			"X-PHP-OS: {$tmp['php_os']}\n" .
			"X-PHP-Status: {$tmp['new_status']}\n" .
			"X-PHP-Old-Status: {$tmp['old_status']}\n" .
			"In-Reply-To: <bug-{$bug['id']}@{$site_url}>"
		);
	}

	/* if a developer assigns someone else, let that other person know about it */
	if ($edit == 1 && $in['assign'] && $in['assign'] != $bug['assign']) {

		$email = $in['assign'];

		// If the developer assigns him self then skip
		if ($email == $from) {
			return;
		}

		bugs_mail(
			$email,
			$subj . txfield('sdesc', $bug, $in),
			"{$in['assign']} you have just been assigned to this bug by {$from}\n\n{$dev_text}",
			"From: {$from}\n" .
			"X-PHP-Bug: {$bug['id']}\n" .
			"In-Reply-To: <bug-{$bug['id']}@{$site_url}>"
		);
	}
}

/**
 * Turns a unix timestamp into a uniformly formatted date
 *
 * If the date is during the current year, the year is omitted.
 *
 * @param int $ts			the unix timestamp to be formatted
 * @param string $format	format to use
 *
 * @return string	the formatted date
 */
function format_date($ts = null, $format = 'Y-m-d H:i e')
{
	if (!$ts) {
		$ts = time();
	}
	return gmdate($format, $ts - date('Z', $ts));
}

/**
 * Produces a string containing the bug's prior comments
 *
 * @param int $bug_id	the bug's id number
 * @param int $all		should all existing comments be returned?
 *
 * @return string	the comments
 */
function get_old_comments($bug_id, $all = 0)
{
	global $dbh, $site_url, $basedir;

	$divider = str_repeat('-', 72);
	$max_message_length = 10 * 1024;
	$max_comments = 5;
	$output = '';
	$count = 0;

	$res = $dbh->prepare("
		SELECT ts, email, comment
		FROM bugdb_comments
		WHERE bug = ? AND comment_type != 'log'
		ORDER BY ts DESC
	")->execute(array($bug_id));

	// skip the most recent unless the caller wanted all comments
	if (!$all) {
		$row = $res->fetchRow(MDB2_FETCHMODE_ORDERED);
		if (!$row) {
			return '';
		}
	}

	while (($row = $res->fetchRow(MDB2_FETCHMODE_ORDERED)) && strlen($output) < $max_message_length && $count++ < $max_comments) {
		$email = spam_protect($row[1], 'text');
		$output .= "[{$row[0]}] {$email}\n\n{$row[2]}\n\n{$divider}\n";
	}

	if (strlen($output) < $max_message_length && $count < $max_comments) {
		$res = $dbh->prepare("SELECT ts1, email, ldesc FROM bugdb WHERE id = ?")->execute(array($bug_id));
		if (!$res) {
			return $output;
		}
		$row = $res->fetchRow(MDB2_FETCHMODE_ORDERED);
		if (!$row) {
			return $output;
		}
		$email = spam_protect($row[1], 'text');
		return ("

Previous Comments:
{$divider}
{$output}[{$row[0]}] {$email}

{$row[2]}

{$divider}

");
	} else {
		return "

Previous Comments:
{$divider}
{$output}

The remainder of the comments for this report are too long. To view
the rest of the comments, please view the bug report online at

    http://{$site_url}{$basedir}/bug.php?id={$bug_id}
";
	}

	return '';
}

/**
 * Converts any URI's found in the string to hyperlinks
 *
 * @param string $text	the text to be examined
 *
 * @return string	the converted string
 */
function addlinks($text)
{
	$text = htmlspecialchars($text);
	$text = preg_replace("/((mailto|http|https|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|$)/i","<a href=\"\\1\">\\1</a>\\3",$text);

	# what the heck is this for?
	$text = preg_replace("/[.,]?-=-\"/", '"', $text);
	return $text;
}

/**
 * Determine if the given package name is legitimate
 *
 * @param string $package_name	the name of the package
 *
 * @return bool
 */
function package_exists($package_name)
{
	global $dbh, $pseudo_pkgs;

	if (empty($package_name)) {
		return false;
	}
	if (isset($pseudo_pkgs[$package_name])) {
		return true;
	}
	return false;
}

/**
 * Validate an email address
 */
function is_valid_email($email, $phpnet_allowed = true)
{
	if (!$phpnet_allowed) {
		if (false !== stripos($email, '@php.net')) {
			return false;
		}
	}
	return (bool) preg_match("/^[.\\w+-]+@[.\\w-]+\\.\\w{2,}\z/i", $email);
}

/**
 * Validate an incoming bug report
 *
 * @param
 *
 * @return void
 */
function incoming_details_are_valid($in, $initial = 0, $logged_in = false)
{
	global $bug, $dbh, $bug_types;

	$errors = array();
	if (!is_array($in)) {
		$errors[] = 'Invalid data submitted!';
		return $errors;
	}
	if ($initial || (!empty($in['email']) && $bug['email'] != $in['email'])) {
		if (!is_valid_email($in['email'])) {
			$errors[] = 'Please provide a valid email address.';
		}
	}
	if (!$logged_in && $initial && empty($in['passwd'])) {
		$errors[] = 'Please provide a password for this bug report.';
	}

	if (isset($in['php_version']) && $in['php_version'] == 'earlier') {
		$errors[] = 'Please select a valid PHP version. If your PHP version is too old, please upgrade first and see if the problem has not already been fixed.';
	}

	if (empty($in['php_version'])) {
		$errors[] = 'Please select a valid PHP version.';
	}

	if (empty ($in['package_name']) || $in['package_name'] == 'none') {
		$errors[] = 'Please select an appropriate package.';
	} else if (!package_exists($in['package_name'])) {
		$errors[] = 'Please select an appropriate package.';
	}

	if (!array_key_exists($in['bug_type'], $bug_types)) {
		$errors[] = 'Please select a valid bug type.';
	}

	if (empty($in['sdesc'])) {
		$errors[] = 'You must supply a short description of the bug you are reporting.';
	}

	if ($initial && empty($in['ldesc'])) {
		$errors[] = 'You must supply a long description of the bug you are reporting.';
	}

	return $errors;
}

/**
 * Produces an array of email addresses the report should go to
 *
 * @param string $package_name	the package's name
 *
 * @return array		an array of email addresses
 */
function get_package_mail($package_name, $bug_id = false, $bug_type = 'Bug')
{
	global $dbh, $bugEmail, $docBugEmail, $secBugEmail, $security_distro_people;

	$to = array();
	
	if ($bug_type === 'Documentation Problem') {
		// Documentation problems *always* go to the doc team
		$to[] = $docBugEmail;
	} else if ($bug_type == 'Security') {
		// Security problems *always* go to the sec team
		$to[] = $secBugEmail;
	}
	else {
		/* Get package mailing list address */
		$res = $dbh->prepare('
			SELECT list_email
			FROM bugdb_pseudo_packages
			WHERE name = ?
		')->execute(array($package_name));
	
		if (PEAR::isError($res)) {
			throw new Exception('SQL Error in get_package_name(): ' . $res->getMessage());
		}
	
		$list_email = $res->fetchOne();
	
		if ($list_email) {
			$to[] = $list_email;
		} else { // Fall back to default mailing list
			$to[] = $bugEmail;
		}
	}

	/* Include assigned to To list and subscribers in Bcc list */
	if ($bug_id) {
		$bug_id = (int) $bug_id;

		$assigned = $dbh->prepare("SELECT assign FROM bugdb WHERE id= ? ")->execute(array($bug_id))->fetchOne();
		if ($assigned) {
			$assigned .= '@php.net';
			if ($assigned && !in_array($assigned, $to)) {
				$to[] = $assigned;
			}
		}
		$bcc = $dbh->prepare("SELECT email FROM bugdb_subscribe WHERE bug_id=?")->execute(array($bug_id))->fetchCol();

		// Add the security distro people
		if ($bug_type == 'Security') {
			$bcc = array_merge($bcc, $security_distro_people);
		}
		$bcc = array_diff($bcc, $to);
		$bcc = array_unique($bcc);
		return array(implode(', ', $to), $bugEmail, implode(', ', $bcc));
	} else {
		return array(implode(', ', $to), $bugEmail);
	}
}

/**
 * Prepare a query string with the search terms
 *
 * @param string $search	the term to be searched for
 *
 * @return array
 */
function format_search_string($search, $boolean_search = false)
{
	// Function will be updated to make results more relevant.
	// Quick hack for indicating ignored words.
	$min_word_len=3;

	$words = preg_split("/\s+/", $search);
	$ignored = $used = array();
	foreach($words AS $match)
	{
		if (strlen($match) < $min_word_len) {
			array_push($ignored, $match);
		} else {
			array_push($used, $match);
		}
	}

	if ($boolean_search) {
		// require all used words (all)
		if ($boolean_search === 1) {
			$newsearch = '';
			foreach ($used as $word) {
				$newsearch .= "+$word ";
			}
			return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . escapeSQL($newsearch) . "' IN BOOLEAN MODE)", $ignored);

		// allow custom boolean search (raw)
		} elseif ($boolean_search === 2) {
			return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . escapeSQL($search) . "' IN BOOLEAN MODE)", $ignored);
		}
	}
	// require any of the words (any)
	return array(" AND MATCH (bugdb.email,sdesc,ldesc) AGAINST ('" . escapeSQL($search) . "')", $ignored);
}

/**
 * Send the confirmation mail to confirm a subscription removal
 *
 * @param integer	bug ID
 * @param string	email to remove
 * @param array		bug data
 *
 * @return void
 */
function unsubscribe_hash($bug_id, $email)
{
	global $dbh, $siteBig, $site_url, $bugEmail;

	$now = time();
	$hash = crypt($email . $bug_id, $now);

	$query = "
		UPDATE bugdb_subscribe
		SET unsubscribe_date = '{$now}',
			unsubscribe_hash = ?
		WHERE bug_id = ? AND email = ?
	";

	$res = $dbh->prepare($query)->execute(array($hash,$bug_id, $email));

	$affected = $dbh->affectedRows($res);

	if ($affected > 0) {
		$hash = urlencode($hash);
		/* user text with attention, headers and previous messages */
		$user_text = <<< USER_TEXT
ATTENTION! Do NOT reply to this email!

A request has been made to remove your subscription to
{$siteBig} bug #{$bug_id}

To view the bug in question please use this link:
http://{$site_url}{$basedir}/bug.php?id={$bug_id}

To confirm the removal please use this link:
http://{$site_url}{$basedir}/bug.php?id={$bug_id}&unsubscribe=1&t={$hash}


USER_TEXT;

		bugs_mail(
			$email,
			"[$siteBig-BUG-unsubscribe] #{$bug_id}",
			$user_text,
			"From: {$siteBig} Bug Database <{$bugEmail}>\n".
			"X-PHP-Bug: {$bug_id}\n".
			"In-Reply-To: <bug-{$bug_id}@{$site_url}>"
		);
	}
}


/**
 * Remove a subscribtion
 *
 * @param integer	bug ID
 * @param string	hash
 *
 * @return void
 */
function unsubscribe($bug_id, $hash)
{
	global $dbh;

	$hash = escapeSQL($hash);
	$bug_id = (int) $bug_id;

	$query = "
		SELECT bug_id, email, unsubscribe_date, unsubscribe_hash
		FROM bugdb_subscribe
		WHERE bug_id = ? AND unsubscribe_hash = ? LIMIT 1
	";

	$sub = $dbh->prepare($query)->execute(array($bug_id,$hash))->fetch(MDB2_FETCHMODE_ASSOC);

	if (!$sub) {
		return false;
	}

	$now = time();
	$requested_on = $sub['unsubscribe_date'];
	/* 24hours delay to answer the mail */
	if (($now - $requested_on) > (24*60*60)) {
		return false;
	}

	$query = "
		DELETE FROM bugdb_subscribe
		WHERE bug_id = ? AND unsubscribe_hash = ? AND email = ?
	";
	$dbh->prepare($query)->execute(array($bug_id,$hash,$sub['email']));
	return true;
}


/**
 * Fetch bug resolves
 *
 * @return array array of resolves
 */
function get_resolve_reasons ($project = false)
{
	global $dbh;

	$where = '';

	if ($project !== false)
		$where.= "WHERE (project = '{$project}' OR project = '')";

	$resolves = $variations = array();
	$res = $dbh->prepare("SELECT * FROM bugdb_resolves $where")->execute(array());
	if (PEAR::isError($res)) {
		throw new Exception("SQL Error in get_resolve_reasons");
	}
	while ($row = $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
		if (!empty($row['package_name'])) {
			$variations[$row['name']][$row['package_name']] = $row['message'];
		} else {
			$resolves[$row['name']] = $row;
		}
	}
	return array($resolves, $variations);
}

/**
 * Fetch bug data
 *
 * @return mixed array of bug data or object with error info
 */
function bugs_get_bug ($bug_id)
{
	global $dbh;

	$query = 'SELECT b.id, b.package_name, b.bug_type, b.email, b.reporter_name,
		b.sdesc, b.ldesc, b.php_version, b.php_os,
		b.status, b.ts1, b.ts2, b.assign, b.block_user_comment,
		b.private, b.cve_id,
		UNIX_TIMESTAMP(b.ts1) AS submitted,
		UNIX_TIMESTAMP(b.ts2) AS modified,
		COUNT(bug=b.id) AS votes,
		SUM(reproduced) AS reproduced, SUM(tried) AS tried,
		SUM(sameos) AS sameos, SUM(samever) AS samever,
		AVG(score)+3 AS average, STD(score) AS deviation
		FROM bugdb b
		LEFT JOIN bugdb_votes ON b.id = bug
		WHERE b.id = ?
		GROUP BY bug';

	return $dbh->prepare($query)->execute(array($bug_id))->fetchRow(MDB2_FETCHMODE_ASSOC);
}

/**
 * Fetch bug comments
 *
 * @return mixed array of bug comments or object with error info
 */
function bugs_get_bug_comments ($bug_id)
{
	global $dbh;

	$query = "
		SELECT c.id, c.email, c.comment, c.comment_type,
			UNIX_TIMESTAMP(c.ts) AS added,
			c.reporter_name AS comment_name
		FROM bugdb_comments c
		WHERE c.bug = ?
		GROUP BY c.id ORDER BY c.ts
	";
	return $dbh->prepare($query)->execute(array($bug_id))->fetchAll(MDB2_FETCHMODE_ASSOC);
}

/**
 * Add bug comment
 */
function bugs_add_comment ($bug_id, $from, $from_name, $comment, $type = 'comment')
{
	global $dbh;

	return $dbh->prepare("
		INSERT INTO bugdb_comments (bug, email, reporter_name, comment, comment_type, ts)
		VALUES (?, ?, ?, ?, ?, NOW())
	")->execute(array(
		$bug_id, $from, $from_name, $comment, $type,
	));
}

/**
 * Verify bug password
 *
 * @return bool
 */

function verify_bug_passwd($bug_id, $passwd)
{
	global $dbh;

	return (bool) $dbh->prepare('SELECT 1 FROM bugdb WHERE id = ? AND passwd = ?')->execute(array($bug_id, $passwd))->fetchOne();
}

/**
 * Mailer function. When DEVBOX is defined, this only outputs the parameters as-is. 
 * 
 * @return bool
 *
 */
function bugs_mail($to, $subject, $message, $headers = '')
{
	if (DEVBOX === true) {
		if (defined('DEBUG_MAILS')) {
			echo '<pre>';
			var_dump(htmlspecialchars($to), htmlspecialchars($subject), htmlspecialchars($message), htmlspecialchars($headers));
			echo '</pre>';
		}
		return true;
	}
	return @mail($to, $subject, $message, $headers, '-f noreply@php.net');
}

/**
 * Prints out the XHTML headers and top of the page.
 *
 * @param string $title	a string to go into the header's <title>
 * @return void
 */
function response_header($title, $extraHeaders = '')
{
	global $_header_done, $self, $auth_user, $logged_in, $siteBig, $site_url, $basedir;

	if ($_header_done) {
		return;
	}

	$_header_done	= true;

	header('Content-Type: text/html; charset=UTF-8');
	echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<?php echo $extraHeaders; ?>
	<base href="<?php echo (!empty($_SERVER['HTTPS'])) ? 'https://' : 'http://', $site_url, $basedir; ?>/" />
	<title><?php echo $siteBig; ?> :: <?php echo $title; ?></title>
	<link rel="shortcut icon" href="http://<?php echo $site_url, $basedir; ?>/images/favicon.ico" />
	<link rel="stylesheet" href="css/style.css" />
</head>

<body>

<div><a id="TOP"></a></div>

<table id="head-menu" class="head" cellspacing="0" cellpadding="0">
	<tr>
		<td class="head-logo">
			<a href="/"><img src="images/logo.gif" alt="Bugs" vspace="2" hspace="2" /></a>
		</td>

		<td class="head-menu">
			<a href="http://www.php.net/" class="menuWhite">php.net</a>&nbsp;|&nbsp;
			<a href="http://www.php.net/support.php" class="menuWhite">support</a>&nbsp;|&nbsp;
			<a href="http://www.php.net/docs.php" class="menuWhite">documentation</a>&nbsp;|&nbsp;
			<a href="report.php" class="menuWhite">report a bug</a>&nbsp;|&nbsp;
			<a href="search.php" class="menuWhite">advanced search</a>&nbsp;|&nbsp;
			<a href="search-howto.php" class="menuWhite">search howto</a>&nbsp;|&nbsp;
			<a href="stats.php" class="menuWhite">statistics</a>&nbsp;|&nbsp;
			<a href="http://master.php.net/login.php" class="menuWhite">login</a>
		</td>
	</tr>

	<tr>
		<td class="head-search" colspan="2">
			<form method="get" action="search.php">
				<p class="head-search">
					<input type="hidden" name="cmd" value="display" />
					<small>go to bug id or search bugs for</small>
					<input class="small" type="text" name="search_for" value="<?php print isset($_GET['search_for']) ? htmlspecialchars($_GET['search_for']) : ''; ?>" size="30" />
					<input type="image" src="images/small_submit_white.gif" alt="search" style="vertical-align: middle;" />
				</p>
			</form>
		</td>
	</tr>
</table>

<table class="middle" cellspacing="0" cellpadding="0">
	<tr>
		<td class="content">
<?php
}


function response_footer($extra_html = '')
{
	global $_footer_done, $LAST_UPDATED, $basedir;

	if ($_footer_done) {
		return;
	}
	$_footer_done = true;
?>
		</td>
	</tr>
</table>

<?php echo $extra_html; ?>

<table class="foot" cellspacing="0" cellpadding="0">
	<tr>
		<td class="foot-bar" colspan="2">&nbsp;</td>
	</tr>

	<tr>
		<td class="foot-copy">
			<small>
				<a href="http://www.php.net/"><img src="images/logo-small.gif" align="left" valign="middle" hspace="3" alt="PHP" /></a>
				<a href="http://www.php.net/copyright.php">Copyright &copy; 2001-<?php echo date('Y'); ?> The PHP Group</a><br />
				All rights reserved.
			</small>
		</td>
		<td class="foot-source">
			<small>Last updated: <?php echo $LAST_UPDATED; ?></small>
		</td>
	</tr>
</table>
</body>
</html>
<?php
}


/**
 * Redirects to the given full or partial URL.
 *
 * @param string $url Full/partial url to redirect to
 */
function redirect($url)
{
	header("Location: {$url}");
}


/**
 * Turns the provided email address into a "mailto:" hyperlink.
 *
 * The link and link text are obfuscated by alternating Ord and Hex
 * entities.
 *
 * @param string $email		the email address to make the link for
 * @param string $linktext	a string for the visible part of the link.
 *							If not provided, the email address is used.
 * @param string $extras	a string of extra attributes for the <a> element
 *
 * @return string			the HTML hyperlink of an email address
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

	return "<a {$extras} href='&#x6d;&#97;&#x69;&#108;&#x74;&#111;&#x3a;{$tmp}'>" . ($linktext != '' ? $linktext : $tmp) . '</a>';
}

/**
 * Turns bug/feature request numbers into hyperlinks
 *
 * If the bug number is prefixed by the word "PHP, PEAR, PECL" the link will
 * go to correct bugs site.	Otherwise, the bug is considered "local" bug.
 *
 * @param string $text	the text to check for bug numbers
 *
 * @return string the string with bug numbers hyperlinked
 */
function make_ticket_links($text)
{
	return preg_replace(
		'/(?<![>a-z])(bug(?:fix)?|feat(?:ure)?|doc(?:umentation)?|req(?:uest)?)\s+#?([0-9]+)/i',
		"<a href='bug.php?id=\\2'>\\0</a>",
		$text
	);
}

function handle_pear_errors ($error_obj)
{
	response_header("Oops! We are sorry that you are unable to report an undocumented feature today.");
	
	$error  = "<p>Greetings! We are experiencing an error, and in the spirit of Open Source would like you to fix it. ";
	$error .= "Or more likely, just wait and someone else will find and solve this.</p>\n";
	$error .= "<p>It's our guess that the database is down. Argh!!!</p>\n";
	
	// FIXME: If logged in, show other stuff....

	response_footer($error);
	exit;
}
