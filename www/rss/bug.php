<?php

use App\Repository\BugRepository;
use App\Repository\CommentRepository;

/* Generates an RSS/RDF feed for a particular bug specified as the "id"
 * parameter.  optionally, if "format" is "xml", generates data in a
 * non-standard xml format.
 *
 * Contributed by Sara Golemon <pollita@php.net>
 * ported from php-bugs-web by Gregory Beaver <cellog@php.net>
 */

require_once '../../include/prepend.php';

$bug_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'rss2';

$bugRepository = new BugRepository($dbh);
$bug = $bugRepository->findOneById($bug_id);

if (!$bug) {
	header('HTTP/1.0 404 Not Found');
	die('Nothing found');
}

if ($bug['private'] == 'Y') {
	header('HTTP/1.0 403 Forbidden');
	die('Access restricted');
}

$commentRepository = new CommentRepository($dbh);
$comments = $commentRepository->findByBugId($bug_id);

if ($format == 'xml') {
	header('Content-type: text/xml; charset=utf-8');
	include './xml.php';
	exit;
} elseif ($format == "rss2") {
	header('Content-type: application/rss+xml; charset=utf-8');
	$uri = "{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug['id']}";
	include './rss.php';
	exit;
} else {
	header('Content-type: application/rdf+xml; charset=utf-8');
	$uri = "{$site_method}://{$site_url}{$basedir}/bug.php?id={$bug['id']}";
	include './rdf.php';
	exit;
}
