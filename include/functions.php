<?php

/* User flags */
define('BUGS_NORMAL_USER',  1<<0);
define('BUGS_DEV_USER',     1<<1);
define('BUGS_TRUSTED_DEV',  1<<2);
define('BUGS_SECURITY_DEV', 1<<3);

/* Contains functions and variables used throughout the bug system */

// used in mail_bug_updates(), below, and class for search results
$tla = array(
	'Open'          => 'Opn',
	'Not a bug'     => 'Nab',
	'Feedback'      => 'Fbk',
	'No Feedback'   => 'NoF',
	'Wont fix'      => 'Wfx',
	'Duplicate'     => 'Dup',
	'Critical'      => 'Ctl',
	'Assigned'      => 'Asn',
	'Analyzed'      => 'Ana',
	'Verified'      => 'Ver',
	'Suspended'     => 'Sus',
	'Closed'        => 'Csd',
	'Spam'          => 'Spm',
	'Re-Opened'     => 'ReO',
);

$bug_types = array(
	'Bug'                      => 'Bug',
	'Feature/Change Request'   => 'Req',
	'Documentation Problem'    => 'Doc',
	'Security'                 => 'Sec Bug'
);

$project_types = array(
	'PHP'   => 'php',
	'PECL'  => 'pecl'
);

// Used in show_state_options()
$state_types = array (
	'Open'          => 2,
	'Closed'        => 2,
	'Re-Opened'     => 1,
	'Duplicate'     => 1,
	'Critical'      => 1,
	'Assigned'      => 2,
	'Not Assigned'  => 0,
	'Analyzed'      => 1,
	'Verified'      => 1,
	'Suspended'     => 1,
	'Wont fix'      => 1,
	'No Feedback'   => 1,
	'Feedback'      => 1,
	'Old Feedback'  => 0,
	'Stale'         => 0,
	'Fresh'         => 0,
	'Not a bug'     => 1,
	'Spam'          => 1,
	'All'           => 0,
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

    $_SESSION["user"] = $user;

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
	} else if (($user_flags == BUGS_NORMAL_USER) && $pw != '' && verify_bug_passwd($bug_id, bugs_get_hash($pw))) {
		// The submitter
		return true;
	} else if (($user_flags & BUGS_DEV_USER) && $bug['reporter_name'] != '' &&
		strtolower($bug['reporter_name']) == strtolower($auth_user->handle)) {
		// The submitter (php developer)
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
		$user = strtolower($user);
		$pw = $_POST['pw'];
	} elseif (isset($auth_user) && is_object($auth_user) && $auth_user->handle) {
		$user = $auth_user->handle;
		$pw = $auth_user->password;
    }

	// Authentication and user level check
	// User levels are: reader (0), commenter/patcher/etc. (edit = 3), submitter (edit = 2), developer (edit = 1)
	if (!empty($_SESSION["user"])) {
		$user = $_SESSION["user"];
		$user_flags = BUGS_DEV_USER;
		$logged_in = 'developer';
		$auth_user = new stdClass;
		$auth_user->handle = $user;
		$auth_user->email = "{$user}@php.net";
		$auth_user->name = $user;
	} elseif ($user != '' && $pw != '' && verify_password($user, $pw)) {
		$user_flags = BUGS_DEV_USER;
		$logged_in = 'developer';
		$auth_user = new stdClass;
		$auth_user->handle = $user;
		$auth_user->email = "{$user}@php.net";
		$auth_user->name = $user;
	} else {
		$auth_user = new stdClass;
		$auth_user->email = isset($_POST['in']['email']) ? $_POST['in']['email'] : '';
		$auth_user->handle = '';
		$auth_user->name = '';
	}

	// Check if developer is trusted
	if ($logged_in == 'developer') {
		require_once "{$ROOT_DIR}/include/trusted-devs.php";

		if (in_array(strtolower($user), $trusted_developers)) {
			$user_flags |= BUGS_TRUSTED_DEV;
		}
		if (in_array(strtolower($user), $security_developers)) {
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
function get_pseudo_packages($project, $return_disabled = true)
{
	global $dbh, $project_types;

	$pseudo_pkgs = $nodes = $tree = array();
	$where = '1=1';
	$project = strtolower($project);

	if ($project !== false && in_array($project, $project_types)) {
		$where .= " AND project IN ('', '$project')";
	}
	if (!$return_disabled) {
		$where.= " AND disabled = 0";
	}

	$data = $dbh->queryAll("SELECT * FROM bugdb_pseudo_packages WHERE $where ORDER BY parent, disabled, id", null, MDB2_FETCHMODE_ASSOC);

	// Convert flat array to nested strucutre
	foreach ($data as &$node)
	{
		$node['children'] = array();
		$id = $node['id'];
		$parent_id = $node['parent'];
		$nodes[$id] =& $node;

		if (array_key_exists($parent_id, $nodes)) {
			$nodes[$parent_id]['children'][] =& $node;
		} else {
			$tree[] =& $node;
		}
	}

	foreach ($tree as $data)
	{
		if (isset($data['children'])) {
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

		} elseif (!isset($pseudo_pkgs[$data['name']])) {
			$pseudo_pkgs[$data['name']] = array($data['long_name'], $data['disabled']);
		}
	}

	return $pseudo_pkgs;
}

/* Primitive check for SPAM. Add more later. */
function is_spam($string)
{
	// @php.net users are given permission to spam... we gotta eat! See also bug #48126
	if (!empty($GLOBALS['auth_user']->handle)) {
		return false;
	}

	if (substr_count(strtolower($string), 'http://') > 5) {
		return true;
	}

	$keywords = array(
		'spy',
		'bdsm',
		'massage',
		'mortage',
		'sex',
		'11nong',
		'oxycontin',
		'distance-education',
		'sismatech',
		'justiceplan',
		'prednisolone',
		'baclofen',
		'diflucan',
		'unbra.se',
		'objectis',
		'angosso',
		'colchicine',
		'zovirax',
		'korsbest',
		'coachbags',
		'chaneljpoutlet',
		'\/Members\/',
		'michaelkorsshop',
		'mkmichaelkors',
		'Burberrysale4u',
		'gadboisphotos',
		'oakleysunglasseslol',
		'partydressuk',
		'leslunettesdesoleil',
		'PaulRGuthrie',
		'[a-z]*?fuck[a-z]*?',
		'jerseys',
		'wholesale',
		'fashionretailshop01',
	);

	if (preg_match('/\b('. implode('|', $keywords) . ')\b/i', $string)) {
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
 * @param string $format	how the output will be displayed ('html', 'text', 'reverse')
 *
 * @return string	the altered email address
 */
function spam_protect($txt, $format = 'html')
{
	/* php.net addresses are not protected! */
	if (preg_match('/^(.+)@php\.net$/i', $txt)) {
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
		if ($format == 'reverse') {
			$translate = array_flip($translate);
		}
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
		htmlspecialchars(isset($_POST['in'][$n]) ? $_POST['in'][$n] : '') : null,
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
 * Prints bug project <option>'s for use in a <select>
 *
 * Options include "PHP" and "PECL".
 *
 * @param string	$current	bug's current project
 * @param bool		$all		whether or not 'All' should be an option
 *
 * @retun void
 */
function show_project_options($current = 'php', $all = false)
{
	global $project_types;

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
		$current = 'php';
	} else {
		$current = strtolower($current);
	}

	foreach ($project_types as $k => $v) {
		echo '<option value="', $k, '"', (($current == strtolower($k)) ? ' selected="selected"' : ''), ">{$k}</option>\n";
	}
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
	}

	foreach ($bug_types as $k => $v) {
		$selected = strcasecmp($current, $k) ? '' : ' selected="selected"';
		$k = htmlentities($k, ENT_QUOTES);
		echo "<option value=\"$k\"$selected>$k</option>";
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

	/* regular users can only pick states with type 2 for unclosed bugs */
	if ($state != 'All' && isset($state_types[$state]) && $state_types[$state] == 1 && $user_mode == 2) {
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
		/* Allow state 'Closed' always when current state is not 'Not a bug' */
		if ($state != 'Not a bug') {
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
	global $ROOT_DIR, $versions;

	$use = 0;

	echo '<option value="">--Please Select--</option>' , "\n";
	foreach($versions as $v) {
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
	global $pseudo_pkgs;
	static $bug_items;

	if (!isset($bug_items)) {
		$bug_items = $pseudo_pkgs;
	}

	if (!$current && (!$default || $default == 'none') && !$show_any) {
		echo "<option value=\"none\">--Please Select--</option>\n";
	} elseif (!$current && $show_any == 1) {
		$current = 'Any';
	} elseif (!$current) {
		$current = $default;
	}

	if (!is_array($bug_items)) {
		return;
	}

	foreach ($bug_items as $key => $value) {
		if ($show_any == 1 || $key != 'Any') {
			echo "<option value=\"$key\"";
			if ((is_array($current) && in_array($key, $current)) || ($key == $current)) {
				echo ' selected="selected"';
			}
			// Show disabled categories with different background color in listing
			echo (($value[1]) ? ' style="background-color:#eee;"' : ''), ">{$value[0]}</option>\n";
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
		echo ">$type&nbsp;\n";
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
function display_bug_error($in, $class = 'warn', $head = '<strong>Error</strong>')
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

	if (!empty($in['email']) && (trim($in['email']) != trim($bug['email']))) {
		$changed['reported_by']['from'] = spam_protect($bug['email'], 'text');
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
				if (in_array($name, array('private', 'block_user_comment'))) {
					$from = $from == 'Y' ? 'Yes' : 'No';
					$to = $to == 'Y' ? 'Yes' : 'No';
				}
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
	global $tla, $bug_types, $siteBig, $site_method, $site_url, $basedir;

	$text = array();
	$headers = array();
	$changed = bug_diff($bug, $in);
	$from = str_replace(array("\n", "\r"), '', $from);

	/* Default addresses */
	list($mailto, $mailfrom, $bcc, $params) = get_package_mail(oneof(@$in['package_name'], $bug['package_name']), $id, oneof(@$in['bug_type'], $bug['bug_type']));

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

	$wrapped_text = join("\n", $text);

	/* user text with attention, headers and previous messages */
	$user_text = <<< USER_TEXT
ATTENTION! Do NOT reply to this email!
To reply, use the web interface found at
{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=2

{$header_text}
{$wrapped_text}
USER_TEXT;

	/* developer text with headers, previous messages, and edit link */
	$dev_text = <<< DEV_TEXT
Edit report at {$site_method}://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=1

{$header_text}
{$wrapped_text}

--
Edit this bug report at {$site_method}://{$site_url}{$basedir}/bug.php?id={$bug['id']}&edit=1
DEV_TEXT;

	if (preg_match('/.*@php\.net\z/', $bug['email'])) {
		$user_text = $dev_text;
	}

	// Defaults
	$subj = $bug_types[$bug['bug_type']];
	$sdesc = txfield('sdesc', $bug, $in);

	/* send mail if status was changed, there is a comment, private turned on/off or the bug type was changed to/from Security */
	if (empty($in['status']) || $in['status'] != $bug['status'] || $ncomment != '' ||
		(isset($in['private']) && $in['private'] != $bug['private']) ||
		(isset($in['bug_type']) && $in['bug_type'] != $bug['bug_type'] &&
			($in['bug_type'] == 'Security' || $bug['bug_type'] == 'Security'))) {
		if (isset($in['bug_type']) && $in['bug_type'] != $bug['bug_type']) {
			$subj = $bug_types[$bug['bug_type']] . '->' . $bug_types[$in['bug_type']];
		}

		$old_status = $bug['status'];
		$new_status = $bug['status'];

		if (isset($in['status']) && $in['status'] != $bug['status'] && $edit != 3) {	/* status changed */
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
			"Bcc: {$bcc}\n" .
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
			"In-Reply-To: <bug-{$bug['id']}@{$site_url}>",
			$params
		);
	}

	/* if a developer assigns someone else, let that other person know about it */
	if ($edit == 1 && $in['assign'] && $in['assign'] != $bug['assign']) {

		$email = $in['assign'] . '@php.net';

		// If the developer assigns him self then skip
		if ($email == $from) {
			return;
		}

		bugs_mail(
			$email,
			$bug_types[$bug['bug_type']] . ' #' . $bug['id'] . ' ' . txfield('sdesc', $bug, $in),
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
function format_date($ts = null, $format = 'Y-m-d H:i')
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
	global $dbh, $site_method, $site_url, $basedir;

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
	")->execute([$bug_id]);

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
		$res = $dbh->prepare("SELECT ts1, email, ldesc FROM bugdb WHERE id = ?")->execute([$bug_id]);
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

    {$site_method}://{$site_url}{$basedir}/bug.php?id={$bug_id}
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
	$text = preg_replace("/((mailto|http|https|ftp|nntp|news):.+?)(&gt;|\\s|\\)|\\.\\s|,\\s|$)/i","<a href=\"\\1\" rel=\"nofollow\">\\1</a>\\3",$text);

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
	global $pseudo_pkgs;

	return isset($pseudo_pkgs[$package_name]);
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
	return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
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
	global $bug, $dbh, $bug_types, $versions;

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

	if (empty($in['php_version']) || ($initial && !in_array($in['php_version'], $versions))) {
		$errors[] = 'Please select a valid PHP version.';
	}

	if (empty ($in['package_name']) || $in['package_name'] == 'none') {
		$errors[] = 'Please select an appropriate package.';
	} else if (!package_exists($in['package_name'])) {
		$errors[] = 'Please select an appropriate package.';
	}

	if (empty($in['bug_type']) || !array_key_exists($in['bug_type'], $bug_types)) {
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
	$params = '-f noreply@php.net';
	$mailfrom = $bugEmail;

	if ($bug_type === 'Documentation Problem') {
		// Documentation problems *always* go to the doc team
		$to[] = $docBugEmail;
	} else if ($bug_type == 'Security') {
		// Security problems *always* go to the sec team
		$to[] = $secBugEmail;
		foreach ($security_distro_people as $user) {
		    $to[] = "${user}@php.net";
		}
		$params = '-f bounce-no-user@php.net';
	}
	else {
		/* Get package mailing list address */
		$res = $dbh->prepare('
			SELECT list_email, project
			FROM bugdb_pseudo_packages
			WHERE name = ?
		')->execute([$package_name]);

		if (PEAR::isError($res)) {
			throw new Exception('SQL Error in get_package_name(): ' . $res->getMessage());
		}

		list($list_email, $project) = $res->fetchRow();

		if ($project == 'pecl') {
			$mailfrom = 'pecl-dev@lists.php.net';
		}

		if ($list_email) {
			if ($list_email == 'systems@php.net') {
				$params = '-f bounce-no-user@php.net';
			}
			$to[] = $list_email;
		} else {
			// Get the maintainers handle
			if ($project == 'pecl') {
				$handles = $dbh->prepare("SELECT GROUP_CONCAT(handle) FROM bugdb_packages_maintainers WHERE package_name = ?")->execute([$package_name])->fetchOne();

				if ($handles) {
					foreach (explode(',', $handles) as $handle) {
						$to[] = $handle .'@php.net';
					}
				} else {
					$to[] = $mailfrom;
				}
			} else {
				// Fall back to default mailing list
				$to[] = $bugEmail;
			}
		}
	}

	/* Include assigned to To list and subscribers in Bcc list */
	if ($bug_id) {
		$bug_id = (int) $bug_id;

		$assigned = $dbh->prepare("SELECT assign FROM bugdb WHERE id= ? ")->execute([$bug_id])->fetchOne();
		if ($assigned) {
			$assigned .= '@php.net';
			if ($assigned && !in_array($assigned, $to)) {
				$to[] = $assigned;
			}
		}
		$bcc = $dbh->prepare("SELECT email FROM bugdb_subscribe WHERE bug_id=?")->execute([$bug_id])->fetchCol();

		$bcc = array_unique($bcc);
		return array(implode(', ', $to), $mailfrom, implode(', ', $bcc), $params);
	} else {
		return array(implode(', ', $to), $mailfrom, '', $params);
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
	foreach($words as $match)
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
	global $dbh, $siteBig, $site_method, $site_url, $bugEmail;

	$now = time();
	$hash = crypt($email . $bug_id, $now);

	$query = "
		UPDATE bugdb_subscribe
		SET unsubscribe_date = '{$now}',
			unsubscribe_hash = ?
		WHERE bug_id = ? AND email = ?
	";

	$affected = $dbh->prepare($query, null, MDB2_PREPARE_MANIP)->execute([$hash, $bug_id, $email]);

	if ($affected > 0) {
		$hash = urlencode($hash);
		/* user text with attention, headers and previous messages */
		$user_text = <<< USER_TEXT
ATTENTION! Do NOT reply to this email!

A request has been made to remove your subscription to
{$siteBig} bug #{$bug_id}

To view the bug in question please use this link:
{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug_id}

To confirm the removal please use this link:
{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug_id}&unsubscribe=1&t={$hash}


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

	$sub = $dbh->prepare($query)->execute([$bug_id, $hash])->fetch(MDB2_FETCHMODE_ASSOC);

	if (!$sub) {
		return false;
	}

	$now = time();
	$requested_on = $sub['unsubscribe_date'];
	/* 24hours delay to answer the mail */
	if (($now - $requested_on) > 86400) {
		return false;
	}

	$query = "
		DELETE FROM bugdb_subscribe
		WHERE bug_id = ? AND unsubscribe_hash = ? AND email = ?
	";
	$dbh->prepare($query)->execute([$bug_id, $hash, $sub['email']]);
	return true;
}


/**
 * Fetch bug resolves
 *
 * @return array array of resolves
 */
function get_resolve_reasons($project = false)
{
	global $dbh;

	$where = '';

	if ($project !== false) {
		$project = escapeSQL($project);
		$where.= "WHERE (project = '{$project}' OR project = '')";
	}

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
function bugs_get_bug($bug_id)
{
	global $dbh;

	$query = 'SELECT b.id, b.package_name, b.bug_type, b.email, b.reporter_name,
		b.sdesc, b.ldesc, b.php_version, b.php_os,
		b.status, b.ts1, b.ts2, b.assign, b.block_user_comment,
		b.private, b.cve_id,
		UNIX_TIMESTAMP(b.ts1) AS submitted,
		UNIX_TIMESTAMP(b.ts2) AS modified,
		COUNT(bug=b.id) AS votes,
		IFNULL((SELECT z.project FROM bugdb_pseudo_packages z WHERE z.name = b.package_name LIMIT 1), "php") project,
		SUM(reproduced) AS reproduced, SUM(tried) AS tried,
		SUM(sameos) AS sameos, SUM(samever) AS samever,
		AVG(score)+3 AS average, STD(score) AS deviation
		FROM bugdb b
		LEFT JOIN bugdb_votes ON b.id = bug
		WHERE b.id = ?
		GROUP BY bug';

	return $dbh->prepare($query)->execute([$bug_id])->fetchRow(MDB2_FETCHMODE_ASSOC);
}

/**
 * Fetch bug comments
 *
 * @return mixed array of bug comments or object with error info
 */
function bugs_get_bug_comments($bug_id)
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
	return $dbh->prepare($query)->execute([$bug_id])->fetchAll(MDB2_FETCHMODE_ASSOC);
}

/**
 * Add bug comment
 */
function bugs_add_comment($bug_id, $from, $from_name, $comment, $type = 'comment')
{
	global $dbh;

	return $dbh->prepare("
		INSERT INTO bugdb_comments (bug, email, reporter_name, comment, comment_type, ts, visitor_ip)
		VALUES (?, ?, ?, ?, ?, NOW(), INET_ATON(?))
	")->execute(array(
		$bug_id, $from, $from_name, $comment, $type, (!empty($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.0.0.1')
	));
}

/**
 * Change bug status
 */
function bugs_status_change($bug_id, $new_status)
{
	global $dbh;

	return $dbh->prepare("
		UPDATE bugdb SET status = ? WHERE id = ? LIMIT 1
	")->execute([$new_status, $bug_id]);
}

/**
 * Verify bug password
 *
 * @return bool
 */

function verify_bug_passwd($bug_id, $passwd)
{
	global $dbh;

	return (bool) $dbh->prepare('SELECT 1 FROM bugdb WHERE id = ? AND passwd = ?')->execute([$bug_id, $passwd])->fetchOne();
}

/**
 * Mailer function. When DEVBOX is defined, this only outputs the parameters as-is.
 *
 * @return bool
 *
 */
function bugs_mail($to, $subject, $message, $headers = '', $params = '')
{
	if (empty($params)) {
		$params = '-f noreply@php.net';
	}
	if (DEVBOX === true) {
		if (defined('DEBUG_MAILS')) {
			echo '<pre>';
			var_dump(htmlspecialchars($to), htmlspecialchars($subject), htmlspecialchars($message), htmlspecialchars($headers));
			echo '</pre>';
		}
		return true;
	}
	return @mail($to, $subject, $message, $headers, $params);
}

/**
 * Prints out the XHTML headers and top of the page.
 *
 * @param string $title	a string to go into the header's <title>
 * @return void
 */
function response_header($title, $extraHeaders = '', $preContent = '')
{
	global $_header_done, $self, $auth_user, $logged_in, $siteBig, $site_method, $site_url, $basedir;

	$is_logged = false;

	if ($_header_done) {
		return;
	}

	if ($logged_in === 'developer') {
		$is_logged = true;
		$username = $auth_user->handle;
	} else if (!empty($_SESSION['user'])) {
		$is_logged = true;
		$username = $_SESSION['user'];
	}

	$_header_done = true;

	$search = isset($_GET['search_for']) ? htmlspecialchars($_GET['search_for']) : '';

	header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title><?php echo $siteBig; ?> :: <?php echo $title; ?></title>
  <link rel="stylesheet" href="assets/css/fonts.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <?php echo $extraHeaders; ?>
</head>
<body>

<nav id="head-nav" class="navbar navbar-fixed-top">
  <div class="navbar-inner clearfix">
    <a href="index.php" class="brand"><img src="assets/images/php-logo.svg" width="48" height="24" alt="php">bugs</a>
    <div id="mainmenu-toggle-overlay"></div>
    <input type="checkbox" id="mainmenu-toggle">
    <ul class="nav">
      <li class=""><a href="report.php">Report a bug</a></li>
      <li class=""><a href="search.php">Advanced search</a></li>
<?php if ($is_logged): ?>
      <?php if ($logged_in == 'developer'): ?><li class=""><a href="admin/index.php">Admin</a></li><?php endif; ?>
      <li class=""><a href="search.php?cmd=display&amp;assign=<?= $username ?>">My bugs</a></li>
      <li class=""><a href="logout.php" >Logout</a></li>
<?php else: ?>
      <li class=""><a href="login.php" >Login</a></li>
<?php endif; ?>
    </ul>
    <form class="navbar-search" id="topsearch" action="search.php">
      <input type="search" name="pattern" class="search-query" placeholder="Search bugs" value="<?= $search ?>" accesskey="s">
    </form>
  </div>
</nav>

<?= $preContent ?>

<div id="layout" class="clearfix">
  <section id="layout-content">

<?php
}


function response_footer($extra_html = '')
{
	global $_footer_done, $basedir;

	if ($_footer_done) {
		return;
	}
	$_footer_done = true;
?>
</section>
</div>

<footer>
  <div class="container footer-content">
    <div class="row-fluid">
      <ul class="footmenu">
        <li><a href="http://php.net/copyright.php">Copyright &copy; 2001-2017 The PHP Group</a></li>
        <li><a href="http://php.net/sites">Other PHP.net sites</a></li>
        <li><a href="stats.php">Bug Statistics</a></li>
        <li><a href="search-howto.php">Search Howto</a></li>
        <li><a href="random">Random Bug</a></li>
        <li class="footer-utc">All timestamps are in UTC</li>
      </ul>
    </div>
  </div>
</footer>

<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/scripts.js"></script>
<?php echo $extra_html; ?>

<a id="toTop" href="javascript:;"><span id="toTopHover"></span><img width="40" height="40" alt="To Top" src="assets/images/to-top@2x.png"></a>
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
	exit;
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
 * @param string $text	the text to check for bug numbers
 *
 * @return string the string with bug numbers hyperlinked
 */
function make_ticket_links($text)
{
	return preg_replace(
		'/(?<![>a-z])(bug(?:fix)?|feat(?:ure)?|doc(?:umentation)?|req(?:uest)?|duplicated of)\s+#?([0-9]+)/i',
		"<a href='bug.php?id=\\2'>\\0</a>",
		$text
	);
}

function get_ticket_links($text)
{
	$matches = array();

	preg_match_all('/(?<![>a-z])(?:bug(?:fix)?|feat(?:ure)?|doc(?:umentation)?|req(?:uest)?|duplicated of)\s+#?([0-9]+)/i', $text, $matches);

	return $matches[1];
}

function handle_pear_errors($error_obj)
{
	error_log($error_obj->getMessage());
	response_header("Oops! We are sorry that you are unable to report an undocumented feature today.");

	$error  = "<p>Greetings! We are experiencing an error, and in the spirit of Open Source would like you to fix it. ";
	$error .= "Or more likely, just wait and someone else will find and solve this.</p>\n";
	$error .= "<p>It's our guess that the database is down. Argh!!!</p>\n";

	// FIXME: If logged in, show other stuff....
	response_footer($error);
	exit;
}


/**
 * Generates a random password
 */
function bugs_gen_passwd($length = 8)
{
	return substr(md5(uniqid(time(), true)), 0, $length);
}

function bugs_get_hash($passwd)
{
	return hash_hmac('sha256', $passwd, getenv('USER_PWD_SALT'));
}


/**
 * Inline content menu
 *
 * . The base_url is a prefix for the location, currently does not support _SERVER['QUERY_STRING']
 * . The current_action is used to indicate which page is currently being displayed
 * . Menu items are in (action_name => Title) array pairs
 */
function inline_content_menu($base_url, $current_action, array $menu)
{
	if (!$menu) {
		return;
	}

	$buffer = '';

	foreach ($menu as $action => $title) {
		if ($current_action === $action) {
			$buffer .= sprintf('<strong>%s</strong> | ', $title);
		} else {
			$buffer .= sprintf('<a href="%s?action=%s">%s</a> | ', $base_url, $action, $title);
		}
	}

	echo "<p>";
	echo rtrim($buffer, ' | ');
	echo "</p>\n";
}

function mailto_list(array $mails)
{
	if(!$mails) {
		return;
	}

	$buffer = '';

	foreach ($mails as $mail) {
		$buffer .= sprintf('<a href="mailto:%1$s">%1$s</a>, ', $mail);
	}

	echo rtrim($buffer, ', ');
}

function esc($string) {
	return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
}

function flash_message($text, $type = 'success') {
	return "<div class='headsup {$type}'>{$text}</div>";
}

if (!function_exists('apc_fetch')) {
	function apc_fetch($key, &$success = false) {
		return apcu_fetch($key, $success);
	}
}

if (!function_exists('apc_store')) {
	function apc_store($key, $var, $ttl = 0) {
		return apcu_store($key, $var, $ttl);
	}
}
