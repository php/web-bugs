<?php /* vim: set noet ts=4 sw=4: : */

/* Generates an RSS/RDF feed for a particular bug specified as the "id"
 * parameter.  optionally, if "format" is "xml", generates data in a
 * non-standard xml format.
 *
 * Contributed by Sara Golemon <pollita@php.net>
 * ported from php-bugs-web by Gregory Beaver <cellog@php.net>
 */

require_once '../include/prepend.inc';

$bug_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'rss2';

$query  = "SELECT id,package_name,bug_type,email,sdesc,ldesc,php_version,
                  php_os,status,ts1,ts2,assign,package_version,handle,
                  UNIX_TIMESTAMP(ts1) as ts1a, UNIX_TIMESTAMP(ts2) as ts2a
                  FROM bugdb
                  WHERE id = ?"; 
                  
// What is this? -->  AND registered = 1";

$bug = $dbh->prepare($query)->execute(array($bug_id))->fetchRow(MDB2_FETCHMODE_ASSOC);

if (!$bug) {
	header('HTTP/1.0 404 Not Found');
	die('Nothing found');
}

$query = 'SELECT 
			c.ts,
			comment,
			IF(c.handle <> "",u.registered,1) as registered,
			u.showemail,
			u.handle,
			c.handle as bughandle,
			UNIX_TIMESTAMP(ts) as added
    FROM bugdb_comments c
    LEFT JOIN users u ON u.handle = c.handle
    WHERE c.bug = ?
    ORDER BY c.ts DESC';
$comments = $dbh->prepare($query)->execute(array($bug_id))->fetchAll(MDB2_FETCHMODE_ASSOC);
if ($format == 'xml') {
    header('Content-type: text/xml; charset=utf-8');
	include './xml.php';
	exit;
} elseif ($format == "rss2") {
	header('Content-type: application/rss+xml; charset=utf-8');

	$uri = "http://{$site_url}{$basedir}/bug.php?id={$bug['id']}";
	include './rss.php';
	exit;
		
} else {
    header('Content-type: application/rdf+xml; charset=utf-8');

	$uri = "http://{$site_url}{$basedir}/bug.php?id={$bug['id']}";
	include './rdf.php';
	exit;
}
