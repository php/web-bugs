<?php

/* Generates an RSS/RDF feed for a set of bugs
 * based on search criteria as provided.
 *
 * Search code borrowed from /search.php (As of Revision: 1.82)
 * and accepts the same parameters.
 *
 * When changes are made to that API,
 * they should be reflected here for consistency
 *
 * borrowed from php-bugs-web, implementation by Sara Golemon <pollita@php.net>
 * ported by Gregory Beaver <cellog@php.net>
 */

$format = (isset($_GET['format']) && $_GET['format'] === 'rss2') ? 'rss2' : 'rdf';

// Maximum number of bugs to return
if ($format === 'rss2') {
	// RSS channel shows way more data (e.g. no bug description) thus
	// we can fetch more rows
	define ('MAX_BUGS_RETURN', 500);
} else {
	define ('MAX_BUGS_RETURN', 150);
}

// Obtain common includes
require_once '../../include/prepend.php';
require "{$ROOT_DIR}/include/query.php";

if ($format === 'rss2') {
	require "{$ROOT_DIR}/templates/search-rss2.php";
} else {
	require "{$ROOT_DIR}/templates/search-rdf.php";
}

if (count($warnings) > 0) {
	echo "<!--\n\n";
	echo "The following warnings occured during your request:\n\n";
	foreach($warnings as $warning) {
		echo clean('* ' . $warning) . "\n";
	}
	echo "-->\n";
}
