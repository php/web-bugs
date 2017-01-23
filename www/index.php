<?php

session_start();

/* The bug system home page */

// Obtain common includes
require_once '../include/prepend.php';

// If 'id' is passed redirect to the bug page
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
	redirect("bug.php?id={$id}");
}

if($_SERVER['REQUEST_URI'] == '/random') {
	$query  = "SELECT id FROM bugdb WHERE status NOT IN('Closed', 'Not a bug', 'Duplicate', 'Spam', 'Wont fix', 'No Feedback') AND private = 'N' ORDER BY RAND() LIMIT 1";

	$result = $dbh->prepare($query)->execute();
	$id = $result->fetchRow();
	redirect("bug.php?id={$id[0]}");
}

response_header('Bugs');

?>

<script type="text/javascript">
var bugid = window.location.hash.substr(1) * 1;
if (bugid > 0) {
	var loc = window.location;
	loc.href = loc.protocol + '//' + loc.host+(loc.port ? ':'+loc.port : '')+'/'+bugid;
}
</script>

<h1>PHP Bug Tracking System</h1>

<p>Before you report a bug, please make sure you:</p>

<ul>
	<li>
		Search bug database for similar reports.
	</li>

	<li>
		Make sure you are using the latest stable version (or a build if
		similar bugs have recently been fixed).
	</li>

	<li>
		Read our tips on <a href="how-to-report.php">how to report a bug that someone will want to help fix</a>.
	</li>

	<li>
		Read the <a href="https://wiki.php.net/security">security guidelines</a>, if you think an issue might be security related.
	</li>
	
	<li>
		Don't ask support question. Instead, see the
		see the <a href="http://www.php.net/support.php">support page</a>.
	</li>
</ul>

<h1>Search the Bug System</h1>

<p>You can search all of the bugs that have been reported on our
<a href="search.php">advanced search page</a>, or use the form
at the top of the page for a basic default search.  Read the 
<a href="search-howto.php">search howto</a> for instructions on 
how search works.</p>

<?php
	$base_default = "{$site_method}://{$site_url}/search.php?limit=30&amp;order_by=id&amp;direction=DESC&amp;cmd=display&amp;status=Open&amp;";

	$searches = [
		'all' => [
			'title' => 'Most recent open bugs (all)',
			'url' => 'bug_type=All'
		],
		'all-patches' => [
			'title' => 'Most recent open bugs (all) with patches',
			'url' => 'bug_type=All&patch=Y&pull=Y'
		],
		'docs' => [
			'title' => 'Open Documentation bugs',
			'url' => 'bug_type=Documentation+Problem'
		],
		'docs-patches' => [
			'title' => 'Open Documentation bugs with patches',
			'url' => 'bug_type=Documentation+Problem&patch=Y',
		],
		'56' => [
			'title' => 'Most recent open bugs (PHP 5.6)',
			'url' => 'bug_type=All&phpver=5.6'
		],
		'70' => [
			'title' => 'Most recent open bugs (PHP 7.0)',
			'url' => 'bug_type=All&phpver=7.0'
		],
		'71' => [
			'title' => 'Most recent open bugs (PHP 7.1)',
			'url' => 'bug_type=All&phpver=7.1'
		],
	];

	if (!empty($_SESSION["user"])) {
		$searches[] = [
			'title' => 'Your assigned bugs',
			'url' => 'assign='.urlencode($_SESSION['user'])
		];
	}

	// Make correct URLs
	$searches = array_map(function($search) use ($base_default) {
		$search['url'] = '<a href="' . $base_default . htmlspecialchars($search['url']) . '">' .$search['title'] . '</a>';

		return $search;
	}, $searches);
?>

<table border="0" cellpadding="3" class="standard">
	<tr>
		<th colspan="2">Common bug system searches</th>
	</tr>
	<tr>
		<td class="sub"><?= $searches['all']['url'] ?></td>
		<td><a href=""><?= $searches['docs']['url'] ?></a></td>
	</tr>
	<tr>
		<td class="sub"><?= $searches['all-patches']['url'] ?></td>
		<td><a href=""><?= $searches['docs-patches']['url'] ?></a></td>
	</tr>
	<tr>
		<td class="sub"><?= $searches['56']['url'] ?></td>
		<td><a href="/random">Random bug</a></td>
	</tr>
	<tr>
		<td class="sub"><?= $searches['70']['url'] ?></td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class="sub"><?= $searches['71']['url'] ?></td>
		<td>&nbsp;</td>
	</tr>
</table>

<?php response_footer();
