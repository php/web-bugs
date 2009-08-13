<?php

/**
 * Prints out the XHTML headers and top of the page.
 *
 * @param string $title	a string to go into the header's <title>
 * @return void
 */
function response_header($title, $extraHeaders = '')
{
	global $_header_done, $self, $auth_user, $logged_in, $siteBig, $basedir;

	if ($_header_done) {
		return;
	}

	$_header_done	= true;

	header('Content-Type: text/html; charset=ISO-8859-15');
	echo '<?xml version="1.0" encoding="ISO-8859-15" ?>';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<?php echo $extraHeaders; ?>
	<title><?php echo $siteBig; ?> :: <?php echo $title; ?></title>
	<link rel="shortcut icon" href="images/favicon.ico" />
	<link rel="stylesheet" href="css/style.css" />
</head>

<body>

<div><a id="TOP"></a></div>

<table id="head-menu" class="head" cellspacing="0" cellpadding="0">
	<tr>
		<td class="head-logo">
			<a href="/"><img src="<?php echo $basedir; ?>/images/logo.gif" alt="Bugs" /></a>
		</td>

		<td class="head-menu">
<?php

print_link('http://www.php.net/', 'php.net', false, 'class="menuWhite"');
echo delim();
print_link('http://www.php.net/support.php', 'support', false, 'class="menuWhite"');
echo delim();
print_link('http://www.php.net/docs.php', 'documentation', false, 'class="menuWhite"');
echo delim();
print_link('report.php', 'report a bug', false, 'class="menuWhite"');
echo delim();
print_link('search.php', 'advanced search', false, 'class="menuWhite"');
echo delim();
print_link('search-howto.php', 'search howto', false, 'class="menuWhite"');
echo delim();
print_link('stats.php', 'statistics', false, 'class="menuWhite"');
echo delim();
print_link('http://master.php.net/login.php', 'login', false, 'class="menuWhite"');

?>
		</td>
	</tr>

	<tr>
		<td class="head-search" colspan="2">
			<form method="get" action="search.php">
				<p class="head-search">
					<input type="hidden" name="cmd" value="display" />
					<small>go to bug id or search bugs for</small>
					<input class="small" type="text" name="search_for" value="" size="30" />
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
	<td class="foot-bar" colspan="2">
<?php
print_link('/about/privacy.php', 'PRIVACY POLICY', false, 'class="menuBlack"');
echo delim();
print_link('/about/credits.php', 'CREDITS', false, 'class="menuBlack"');
?>
	</td>
 </tr>

 <tr>
	<td class="foot-copy">
	<small>
<?php print_link('http://www.php.net/', "<img src='{$basedir}/images/logo-small.gif' alt='PHP' />"); ?>
<?php print_link("http://www.php.net/copyright.php", 'Copyright &copy; 2001-' . date('Y') . ' The PHP Group'); ?><br />
	All rights reserved.
	 </small>
	</td>
	<td class="foot-source">
	 <small>
	Last updated: <?php echo $LAST_UPDATED; ?><br />
	 </small>
	</td>
 </tr>
</table>
</body>
</html>
<?php
}

/**
 * Display errors or warnings as a <ul> inside a <div>
 *
 * Here's what happens depending on $in:
 *	 + string: value is printed
 *	 + array:	looped through and each value is printed.
 *			 If array is empty, nothing is displayed.
 *			 If a value contains a PEAR_Error object,
 *	 + PEAR_Error: prints the value of getMessage() and getUserInfo()
 *				 if DEVBOX is true, otherwise prints data from getMessage().
 *
 * @param string|array|PEAR_Error|Exception $in	see long description
 * @param string $class	name of the HTML class for the <div> tag.
 *						("errors", "warnings")
 * @param string $head	 string to be put above the message
 *
 * @return bool	true if errors were submitted, false if not
 */
function report_error($in, $class = 'errors', $head = 'ERROR:')
{
	if (PEAR::isError($in) || $in instanceof Exception) {
		if (DEVBOX == true) {
			if ($in instanceof Exception) {
				$in = array($in->__toString());
			} else {
				$in = array($in->getMessage() . '... ' . $in->getUserInfo());
			}
		} else {
			$in = array($in->getMessage());
		}
	} elseif (!is_array($in)) {
		$in = array($in);
	} elseif (!count($in)) {
		return false;
	}

	echo '<div class="' . $class . '">' . $head . '<ul>';
	foreach ($in as $msg) {
		if (PEAR::isError($msg) || $msg instanceof Exception) {
			if (DEVBOX == true) {
				if ($msg instanceof Exception) {
					$msg = array($msg->__toString());
				} else {
					$msg = array($msg->getMessage() . '... ' . $msg->getUserInfo());
				}
			} else {
				$msg = $msg->getMessage();
			}
		}
		echo '<li>' . htmlspecialchars($msg) . "</li>\n";
	}
	echo "</ul></div>\n";
	return true;
}

/**
 * Forwards warnings to report_error()
 *
 * For use with PEAR_ERROR_CALLBACK to get messages to be formatted
 * as warnings rather than errors.
 *
 * @param string|array|PEAR_Error $in	see report_error() for more info
 *
 * @return bool	true if errors were submitted, false if not
 *
 * @see report_error()
 */
function report_warning($in)
{
	return report_error($in, 'warnings', 'WARNING:');
}

/**
 * Displays success messages inside a <div>
 *
 * @param string $in	the message to be displayed
 *
 * @return void
 */
function report_success($in)
{
	echo '<div class="success">';
	echo htmlspecialchars($in);
	echo "</div>\n";
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
 * Returns a hyperlink to something
 */
function make_link($url, $linktext = '', $target = '', $extras = '', $title = '')
{
	return sprintf('<a href="%s"%s%s%s>%s</a>',
		$url,
		($target ? ' target="'.$target.'"' : ''),
		($extras ? ' '.$extras : ''),
		($title ? ' title="'.$title.'"' : ''),
		($linktext != '' ? $linktext : $url)
	);
}

/**
 * Echos a hyperlink to something
 */
function print_link($url, $linktext = '', $target = '', $extras = '')
{
	echo make_link($url, $linktext, $target, $extras);
}

/**
 * Creates a link to the bug system
 */
function make_bug_link($package, $type = 'list', $linktext = '')
{
	switch ($type) {
		case 'list':
			if (!$linktext) {
				$linktext = 'Package Bugs';
			}
			return make_link('search.php?cmd=display&amp;status=Open&amp;package_name[]=' . urlencode($package), $linktext);
		case 'report':
			if (!$linktext) {
				$linktext = 'Report a new bug';
			}
			return make_link('report.php?package=' . urlencode($package), $linktext);
	}
}

/**
 * Turns the provided email address into a "mailto:" hyperlink.
 *
 * The link and link text are obfuscated by alternating Ord and Hex
 * entities.
 *
 * @param string $email	 	the email address to make the link for
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
 * Print a pipe delimiter
 */
function delim()
{
	return '&nbsp;|&nbsp;';
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
	global $site_url, $basedir, $site_data;
	
	$url = "{$site_data['url']}{$site_data['basedir']}";
	$text = preg_replace('/(?<=php)\s*(bug(?:fix)?|feat(?:ure)?|doc(?:umentation)?|req(?:uest)?)\s+#?([0-9]+)/i',
						 " <a href='http://{$url}/\\2'>\\1 \\2</a>", $text);
	// Local
	$text = preg_replace('/(?<![>a-z])(bug(?:fix)?|feat(?:ure)?|doc(?:umentation)?|req(?:uest)?)\s+#?([0-9]+)/i',
						 "<a href='http://{$site_url}{$basedir}/\\2'>\\0</a>", $text);
	return $text;
}
