<?php

session_start();

/* The bug system home page */

use App\Repository\BugRepository;

// Obtain common includes
require_once '../include/prepend.php';

// If 'id' is passed redirect to the bug page
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
	redirect("bug.php?id={$id}");
}

if($_SERVER['REQUEST_URI'] == '/random') {
	$id = (new BugRepository($dbh))->findRandom();
	redirect("bug.php?id={$id[0]}");
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

response_header('Bugs');

?>

<script>
var bugid = window.location.hash.substr(1) * 1;
if (bugid > 0) {
	var loc = window.location;
	loc.href = loc.protocol + '//' + loc.host+(loc.port ? ':'+loc.port : '')+'/'+bugid;
}
</script>

<h1>PHP Bug Tracking System</h1>

<p>Before you report a bug, please make sure you have completed the following steps:</p>

<ul>
	<li>
		Used the form above or our <a href="search.php">advanced search page</a>
		to make sure nobody has reported the bug already.
	</li>

	<li>
		Make sure you are using the latest stable version or a build from Git, if
		similar bugs have recently been fixed and committed.
	</li>

	<li>
		Read our tips on <a href="how-to-report.php">how to report a bug that someone will want to help fix</a>.
	</li>

	<li>
		Read the <a href="https://wiki.php.net/security">security guidelines</a>, if you think an issue might be security related.
	</li>

	<li>
		See how to get a backtrace in case of a crash:
		<a href="bugs-generating-backtrace.php">for *NIX</a> and
		<a href="bugs-generating-backtrace-win32.php">for Windows</a>.
	</li>

	<li>
		Make sure it isn't a support question. For support,
		see the <a href="https://php.net/support.php">support page</a>.
	</li>
</ul>

<p>Once you've double-checked that the bug you've found hasn't already been
reported, and that you have collected all the information you need to file an
excellent bug report, you can do so on our <a href="report.php">bug reporting
page</a>.</p>

<h1>Search the Bug System</h1>

<p>You can search all of the bugs that have been reported on our
<a href="search.php">advanced search page</a>, or use the form
at the top of the page for a basic default search.  Read the
<a href="search-howto.php">search howto</a> for instructions on
how search works.</p>

<p>If you have 10 minutes to kill and you want to help us out, grab a
random open bug and see if you can help resolve it. We have made it
easy. Hit <a href="<?php echo $site_method?>://<?php echo $site_url?>/random">
<?php echo $site_method?>://<?php echo $site_url?>/random</a> to go directly
to a random open bug.</p>

<p>Common searches</p>
<ul>
<?php
	$base_default = "{$site_method}://{$site_url}/search.php?limit=30&amp;order_by=id&amp;direction=DESC&amp;cmd=display&amp;status=Open";

	$searches = [
		'Most recent open bugs (all)' => '&bug_type=All',
		'Most recent open bugs (all) with patch or pull request' => '&bug_type=All&patch=Y&pull=Y',
		'Most recent open bugs (PHP 5.6)' => '&bug_type=All&phpver=5.6',
		'Most recent open bugs (PHP 7.1)' => '&bug_type=All&phpver=7.1',
		'Most recent open bugs (PHP 7.2)' => '&bug_type=All&phpver=7.2',
		'Most recent open bugs (PHP 7.3)' => '&bug_type=All&phpver=7.3',
		'Open Documentation bugs' => '&bug_type=Documentation+Problem',
		'Open Documentation bugs (with patches)' => '&bug_type=Documentation+Problem&patch=Y'
	];

	if (!empty($_SESSION["user"])) {
		$searches['Your assigned open bugs'] = '&assign='.urlencode($_SESSION['user']);
	}

	foreach ($searches as $title => $sufix) {
		echo '<li><a href="' . $base_default . htmlspecialchars($sufix) . '">' . $title . '</a></li>' . "\n";
	}
?>
</ul>

<h1>Bug System Statistics</h1>

<p>You can view a variety of statistics about the bugs that have been
reported on our <a href="stats.php">bug statistics page</a>.</p>

<?php response_footer();
