<?php /* vim: set noet ts=4 sw=4: : */

/* Generates an RSS/RDF feed for a particular bug specified as the "id"
 * parameter.  optionally, if "format" is "xml", generates data in a
 * non-standard xml format.
 *
 * Contributed by Sara Golemon <pollita@php.net>
 */

$id = (int)$_REQUEST['id'];
$format = $_REQUEST['format'];

@mysql_pconnect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("php3");

$query  = "SELECT id,bug_type,email,sdesc,ldesc,"
		. "php_version,php_os,status,ts1 as ts_submitted,ts2 as ts_modified,assign,"
		. "UNIX_TIMESTAMP(ts1) AS submitted, UNIX_TIMESTAMP(ts2) AS modified,"
		. "COUNT(bug=id) AS votes,"
		. "SUM(reproduced) AS reproduced,SUM(tried) AS tried,"
		. "SUM(sameos) AS sameos, SUM(samever) AS samever,"
		. "AVG(score)+3 AS average,STD(score) AS deviation"
		. " FROM bugdb LEFT JOIN bugdb_votes ON id=bug WHERE id=$id"
		. " GROUP BY bug";

$res = @mysql_query($query);

if ($res) $bug = mysql_fetch_array($res,MYSQL_ASSOC);
if (!$res || !$bug) {
	outputHeader(array(),$format);
	outputFooter($format);
	exit;
}

outputHeader($bug,$format);

$query  = "SELECT email,comment,UNIX_TIMESTAMP(ts) as added"
		. " FROM bugdb_comments WHERE bug=$id ORDER BY ts";
$res = @mysql_query($query);
if ($res) outputbug($bug, $res, $format);

outputFooter($format);

function outputHeader($bug,$format) {
	switch ($format) {
		case 'xml':
			echo "<phpbug>\n";  
			foreach($bug as $key => $value)
				echo "  <$key>" . htmlspecialchars($value) . "</$key>\n";
			break;
		case 'rss':
		default:
			echo <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns="http://purl.org/rss/1.0/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
>

EOD;
			echo "  <channel rdf:about=\"http://bugs.php.net/{$bug['id']}\">\n";
			echo '    <title>' . utf8_encode(htmlspecialchars("[{$bug['status']}] {$bug['sdesc']}")) . "</title>\n";
			echo '    <link>http://bugs.php.net/' . intval($bug['id']) . "</link>\n";
			echo '    <description>';
			echo utf8_encode(htmlspecialchars("{$bug['bug_type']}\n"));
			echo utf8_encode(htmlspecialchars("{$bug['email']}\n"));
			echo date('n/j/Y g:i A',$bug['submitted'] . "\n");
			echo utf8_encode(htmlspecialchars("PHP: {$bug['php_version']} OS: {$bug['php_os']}\n\n"));
			echo utf8_encode(htmlspecialchars($bug['ldesc']));
			echo "    </description>\n"; 
	}
}

function outputbug($bug, $res, $format) {
	while ($row = mysql_fetch_array($res,MYSQL_ASSOC)) {
		switch ($format) {
			case 'xml':
				echo "  <comment>\n";
				foreach ($row as $key => $value)
					echo "    <$key>" . htmlspecialchars($value) . "</$key>\n";
				echo "  </comment>\n";
				break;
			case 'rss':
			default:
				echo "    <item>\n";
				echo '      <title>' . utf8_encode(htmlspecialchars($row['email'])) . "</title>\n";
				echo "      <link>http://bugs.php.net/{$bug['id']}</link>\n";
				echo '      <description>' . utf8_encode(htmlspecialchars($row['comment'])) . "</description>\n";
				echo '      <dc:date>' . date('Y-m-d',$row['added']) . "</dc:date>\n";
				echo '      <dc:time>' . date('H:i:s',$row['added']) . "</dc:time>\n";
				echo "    </item>\n";
		}
	}
}


function outputFooter($format) {
	switch ($format) {
		case 'xml':
			echo "</phpbug>\n";
			break;
		case 'rss':
		default:
			echo "  </channel>\n";
			echo "</rdf:RDF>\n";
	}
}

?>
