<?php /* vim: set noet ts=4 sw=4: : */
require_once 'prepend.inc';

if (isset($cmd) && $cmd == "display") {
	header("Location: search.php?$QUERY_STRING");
    exit;
} elseif (isset($id)) {
    header("Location: bug.php?$QUERY_STRING");
}

commonHeader();
?>

<h1>Report a Bug</h1>

<p>Before you report a bug, please make sure you have completed the following steps:</p>

<ul>
<li>Used the form above or our <a href="search.php">advanced search page</a> 
to make sure nobody has reported the bug already.</li>

<li>Made sure you are using the latest stable version or a build from CVS, if
similar bugs have recently been fixed and committed.</li>  

<li>Read our tips on <a href="how-to-report.php">how to report
a bug that someone will want to help fix</a>.</li>
</ul>

<p>Once you've double-checked that the bug you've found hasn't already been
reported, and that you have collected all the information you need to file an
excellent bug report, you can do so on our <a href="report.php">bug reporting
page</a>.</p>

<h1>Search the Bug System</h1>

<p>You can search all of the bugs that have been reported on our
<a href="search.php">advanced search page</a>, or use the form
at the top of the page.</p>

<h1>Bug System Statistics</h1>

<p>You can view a variety of statistics about the bugs that have been
reported on our <a href="bugstats.php">bug statistics page</a>.</p>

<?php
commonFooter();
