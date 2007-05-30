<?php /* vim: set noet ts=4 sw=4: : */

/* Generates an RSS/RDF feed for a particular bug specified as the "id"
 * parameter.  optionally, if "format" is "xml", generates data in a
 * non-standard xml format.
 *
 * Contributed by Sara Golemon <pollita@php.net>
 * ported from php-bugs-web by Gregory Beaver <cellog@php.net>
 */

require_once dirname(dirname(__FILE__)) . '/include/functions.inc';

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$format = isset($_REQUEST['format']) ? $_REQUEST['format'] : 'rss';

$query  = "SELECT id,package_name,bug_type,email,sdesc,ldesc,php_version,
                  php_os,status,ts1,ts2,assign,package_version,handle,
                  UNIX_TIMESTAMP(ts1) as ts1a, UNIX_TIMESTAMP(ts2) as ts2a
                  FROM bugdb
                  WHERE id=?
                  AND registered=1";

$res = $dbh->getAll($query, array($id), DB_FETCHMODE_ASSOC);

if (count($res)) {
    $bug = $res[0];
}
if (!$res || !$bug) {
    die('Nothing found');
    outputHeader(array(), $format);
    outputFooter($format);
    exit;
}

outputHeader($bug, $format);

$query  = "SELECT c.email,comment,ts,UNIX_TIMESTAMP(ts) as added, IF(c.handle <> \"\",u.registered,1) as registered, u.handle,c.handle as bughandle"
        . " FROM bugdb_comments c LEFT JOIN users u ON u.handle = c.handle WHERE bug=? ORDER BY ts DESC";
$res = $dbh->getAll($query, array($id), DB_FETCHMODE_ASSOC);
if ($res) {
    outputbug($bug, $res, $format);
}

outputFooter($format);

function outputHeader($bug,$format) {
    header('Content-type: text/xml; charset=utf-8');
    switch ($format) {
        case 'xml':
            echo "<pearbug>\n";  
            foreach($bug as $key => $value)
                echo "  <$key>" . htmlspecialchars($value) . "</$key>\n";
            break;
        case 'rss':
        default:
            $query = 'SELECT c.ts, IF(c.handle <> "",u.registered,1) as registered,
                u.showemail, u.handle,c.handle as bughandle
                FROM bugdb_comments c
                LEFT JOIN users u ON u.handle = c.handle
                WHERE c.bug = ?
                ORDER BY c.ts DESC';
            $res = $GLOBALS['dbh']->getAll($query, array($bug['id']));
            echo '<?xml version="1.0"?>
<?xml-stylesheet 
 href="http://www.w3.org/2000/08/w3c-synd/style.css" type="text/css"
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">';
            echo "\n    <channel rdf:about=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/{$bug['id']}/bug\">\n";
            echo "    <title>PEAR Bug #" . intval($bug['id']) . "</title>\n";
            echo '    <link>http://' . urlencode($_SERVER['HTTP_HOST']) . '/bugs/' . intval($bug['id']) . "</link>\n";
            echo "    <description>" . utf8_encode(htmlspecialchars("[{$bug['status']}] {$bug['sdesc']}")) . "</description>\n";
            echo "    <dc:language>en-us</dc:language>\n";
            echo "    <dc:creator>pear-webmaster@lists.php.net</dc:creator>\n";
            echo "    <dc:publisher>pear-webmaster@lists.php.net</dc:publisher>\n";
            echo "    <admin:generatorAgent rdf:resource=\"http://pear.php.net/bugs\"/>\n";
            echo "    <sy:updatePeriod>hourly</sy:updatePeriod>\n";
            echo "    <sy:updateFrequency>1</sy:updateFrequency>\n";
            echo "    <sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>\n";
            echo "    <items>\n";
            echo "     <rdf:Seq>\n";
            echo "      <rdf:li rdf:resource=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/" .
                 intval($bug['id']) . "\"/>\n";
            foreach ($res as $comment) {
                if (!$comment[1]) continue;
                $comment = urlencode($comment[0]);
                echo "      <rdf:li rdf:resource=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/" .
                     intval($bug['id']) . "/$comment#$comment\"/>\n";
            }
            echo "     </rdf:Seq>\n";
            echo "    </items>\n";
            echo "  </channel>\n";
            $desc = "{$bug['package_name']} {$bug['bug_type']}\nReported by ";
            if ($bug['handle']) {
                $desc .= "{$bug['handle']}\n";
            } else {
                $desc .= substr($bug['email'], 0, strpos($bug['email'], '@')) . "@...\n";
            }
            $desc .= rssdate($bug['ts1a']) . "\n";
            $desc .= "PHP: {$bug['php_version']} OS: {$bug['php_os']} Package Version: {$bug['package_version']}\n\n";
            $desc .= $bug['ldesc'];
            $desc = '<pre>' . htmlspecialchars($desc) . '</pre>';
            echo "    <item rdf:about=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/" .
                 $bug['id'] . "\">\n";
            echo '      <title>';
            if ($bug['handle']) {
                echo utf8_encode(htmlspecialchars($bug['handle'])) . "</title>\n";
            } else {
                echo utf8_encode(htmlspecialchars(substr($bug['email'], 0, strpos($bug['email'], '@')))) . "@... [{$bug['ts1']}]</title>\n";
            }
            echo "      <link>http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/{$bug['id']}</link>\n";
            echo '      <description><![CDATA[' . $desc . "]]></description>\n";
            echo '      <content:encoded><![CDATA[' . $desc . "]]></content:encoded>\n";
            echo '      <dc:date>' . rssdate($bug['ts1a']) . "</dc:date>\n";
            echo "    </item>\n";
    }
}

function outputbug($bug, $res, $format) {
    foreach ($res as $row) {
        if (!$row['registered']) continue;
        switch ($format) {
            case 'xml':
                echo "  <comment>\n";
                foreach ($row as $key => $value)
                    echo "    <$key>" . htmlspecialchars($value) . "</$key>\n";
                echo "  </comment>\n";
                break;
            case 'rss':
            default:
                $ts = urlencode($row['ts']);
                $displayts = date('Y-m-d H:i', $row['added'] - date('Z', $row['added']));
                echo "    <item rdf:about=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/" .
                     $bug['id'] . "/$ts#$ts\">\n";
                echo '      <title>';
                if ($row['handle']) {
                    echo utf8_encode(htmlspecialchars($row['handle'])) . " [$displayts]</title>\n";
                } else {
                    echo utf8_encode(htmlspecialchars(substr($row['email'], 0, strpos($row['email'], '@')))) . "@... [$displayts]</title>\n";
                }
                echo "      <link>http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/{$bug['id']}#$row[added]</link>\n";
                $row['comment'] = '<pre>' . htmlspecialchars($row['comment']) . '</pre>';
                echo '      <description><![CDATA[' . $row['comment'] . "]]></description>\n";
                echo '      <content:encoded><![CDATA[' . $row['comment'] . "]]></content:encoded>\n";
                echo '      <dc:date>' . rssdate($row['added']) . "</dc:date>\n";
                echo "    </item>\n";
        }
    }
}

function rssdate($date)
{
    return date('Y-m-d\TH:i:s-00:00', $date - date('Z', $date));
}

function outputFooter($format) {
    switch ($format) {
        case 'xml':
            echo "</pearbug>\n";
            break;
        case 'rss':
        default:
            echo "</rdf:RDF>";
    }
}
?>
