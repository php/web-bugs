<?php /* vim: set noet ts=4 sw=4: : */

/* Generates an RSS/RDF feed for a set of bugs
 * based on search criteria as provided.
 *
 * Search code borrowed from /search.php (As of Revision: 1.59)
 * and accepts the same parameters.
 *
 * When changes are made to that API,
 * they should be reflected here for consistency
 *
 * Sara Golemon <pollita@php.net>
 */

/* For format_search_string() */
require_once('functions.inc');

/* bugs.php.net appears to have magic_quotes_gpc turned on
 * Let's be on the safe side though
 */
force_magic_quotes_gpc($_REQUEST);

@mysql_connect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("phpbugdb");

$query  = 'SELECT id,status,sdesc,ldesc,ts2 as ts_modified,UNIX_TIMESTAMP(ts2) as modified,email,bug_type FROM bugdb';
$warnings = array();

/* Criteria */
if (isset($_REQUEST['bug_type']) && 
	is_array($_REQUEST['bug_type']) &&
	count($_REQUEST['bug_type']) > 0) {
	$query .= " WHERE (bug_type IN ('" . implode("','", $_REQUEST['bug_type']) . "')) ";
} else {
	$query .= " WHERE (bug_type != 'Feature/Change Request')";
}

if (isset($_REQUEST['bug_ntype']) && 
	is_array($_REQUEST['bug_ntype']) &&
	count($_REQUEST['bug_ntype']) > 0) {
	$query .= " AND (bug_type NOT IN ('" . implode("','", $_REQUEST['bug_ntype']) . "')) ";
}

if (isset($_REQUEST['status']) && !empty($_REQUEST['status'])) {
	switch ($_REQUEST['status']) {
			/* Meta statuses */
		case 'Open':
			$query .= " AND ((status='Open') OR (status='Assigned') OR (status='Analyzed') OR (status='Critical') OR (status='Verified'))";
			break;
		case 'Old Feedback':
			/* Asked for feedback more than 60 days ago */
			$query .= " AND ((status='Feedback') AND ((TO_DAYS(NOW())-TO_DAYS(ts2)) > 60))";
			break;
		case 'Fresh':
			/* Some (non-closure) activity within the past 30 days */
			$query .= " AND ((status != 'Closed') AND (status != 'Duplicate') AND (status != 'Bogus') AND ((TO_DAYS(NOW())-TO_DAYS(ts2)) < 30))";
			break;
		case 'Stale':
			/* Non-closed bug with no activity in past 30 days */
			$query .= " AND ((status != 'Closed') AND (status != 'Duplicate') AND (status != 'Bogus') AND ((TO_DAYS(NOW())-TO_DAYS(ts2)) > 30))";
			break;
		default:
			/* Regular status */
			if ($_REQUEST['status'] != 'All') {
				$query .= " AND (status = '{$_REQUEST['status']}')";
			}
	}
}

if (isset($_REQUEST['search_for']) && (strlen($_REQUEST['search_for']) > 0)) {
	list($sql_search, $ignored) = format_search_string($_REQUEST['search_for']);
	$query .= $sql_search;
	if (count($ignored) > 0) {
		$warnings[] = "The following words were ignored: " . implode(', ', array_unique($ignored));
	}
}

if (isset($_REQUEST['bug_age']) && (intval($_REQUEST['bug_age']) > 0)) {
	$query .= ' AND (ts1 >= DATE_SUB(NOW(), INTERVAL ' . intval($_REQUEST['bug_age']) . ' DAY))';
}

if (isset($_REQUEST['php_os']) && !empty($_REQUEST['php_os'])) {
	$query .= " AND (php_os LIKE '%{$_REQUEST['php_os']}%')";
}

if (!isset($_REQUEST['phpver']) || empty($_REQUEST['phpver'])) {
	$query .= " AND ( (SUBSTRING(php_version, 1, 1) = '4') OR (SUBSTRING(php_version, 1, 1) = '5') OR (php_version = 'Irrelevant') )";
} else {
	if (strlen($_REQUEST['phpver']) == 1) {
		$query .= " AND (SUBSTRING(php_version, 1, 1) = '{$_REQUEST['phpver']}')";
	} else {
		$query .= " AND (php_version LIKE '{$_REQUEST['phpver']}%')";
	}
}

if (isset($_REQUEST['assign']) && !empty($_REQUEST['assign'])) {
	$query .= " AND (assign = '{$_REQUEST['assign']}')";
}

if (isset($_REQUEST['author_email']) && !empty($_REQUEST['author_email'])) {
	$query .= " AND (email = '{$_REQUEST['author_email']}')";
}

$query .= ' ORDER BY id DESC';

$res = @mysql_query($query);
if (!$res) {
	die('Error: ' . htmlentities(mysql_error()));
}

echo <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns="http://purl.org/rss/1.0/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
>
  <channel rdf:about="http://bugs.php.net/">
    <title>PHP Bugs Search Results</title>
    <link>http://bugs.php.net/rss/search.php</link>
    <description>$descr</description>
    <items>
     <rdf:Seq>

EOD;

if (mysql_num_rows($res) > 0) {
	$i = 0;
	while (($row = mysql_fetch_assoc($res)) && ($i++ < 100)) {
		echo "      <rdf:li rdf:resource=\"http://bugs.php.net/{$row['id']}\" />\n";
	}
	mysql_data_seek($res, 0);
} else {
	$warnings[] = "No bugs matched your criteria";
}

echo <<<EOD
     </rdf:Seq>
    </items>
  </channel>

  <image rdf:about="http://bugs.php.net/gifs/logo-bug.gif">
    <title>PHP Bugs</title>
    <url>http://bugs.php.net/gifs/logo-bug.gif</url>
    <link>http://bugs.php.net/</link>
  </image>

EOD;

$i = 0;
while ($row = mysql_fetch_assoc($res)) {
	$i++;
	echo "    <item>\n";
	echo '      <title>' . utf8_encode(htmlspecialchars('[' . $row['status'] . '] ' . $row['sdesc'])) . "</title>\n";
	echo "      <link>http://bugs.php.net/{$row['id']}</link>\n";
	echo '      <description>' . utf8_encode(htmlspecialchars($row['ldesc'])) . "</description>\n";
	echo '      <dc:date>' . date('Y-m-d',$row['modified']) . "</dc:date>\n";
	echo '      <dc:time>' . date('H:i:s',$row['modified']) . "</dc:time>\n";
	echo '      <dc:creator>' . utf8_encode(htmlspecialchars($row['email'])) . "</dc:creator>\n";
	echo '      <dc:subject>' . utf8_encode(htmlspecialchars($row['bug_type'])) . "</dc:subject>\n";
	echo "    </item>\n";
	if ($i >= 100) {
		$warnings[] = "Your query was too general, only the first 100 results were returned.";
		break;
	}
}
?>
</rdf:RDF>
<?php
if (count($warnings) > 0) {
	echo "<!--\n\n";
	echo "The following warnings occured during your request:\n\n";
	foreach($warnings as $warning) {
		echo utf8_encode(htmlspecialchars('* ' . $warning)) . "\n";
	}
	echo "-->\n";
}


function force_magic_quotes_gpc(&$array) {
  if (get_magic_quotes_gpc()) return;

  foreach($array as $key => $value) {
    if (is_array($value)) {
      force_magic_quotes_gpc($array[$key]);
    } else {
      $array[$key] = addslashes($value);
    }
  }
}

