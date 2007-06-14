<?php /* vim: set noet ts=4 sw=4: : */

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

/* Maximum number of bugs to return */
define ('MAX_BUGS_RETURN', 150);

/**
 * Obtain common includes
 */
require_once '../include/prepend.inc';

$errors = array();
$warnings = array();
$order_options = array(
    ''             => 'relevance',
    'id'           => 'ID',
    'ts1'          => 'date',
    'package_name' => 'package',
    'bug_type'     => 'bug_type',
    'status'       => 'status',
    'package_version'  => 'package_version',
    'php_version'  => 'php_version',
    'php_os'       => 'os',
    'sdesc'        => 'summary',
    'assign'       => 'assignment',
);

// Pseudo packages
$pseudo_pkgs = get_pseudo_packages($site);

// Input variables
$boolean_search = (int) isset($_GET['boolean']) ? $_GET['boolean'] : 0;

// Get bugs
$query = "
	SELECT SQL_CALC_FOUND_ROWS 
		bugdb.*,
		UNIX_TIMESTAMP(ts1) as ts1a,
		UNIX_TIMESTAMP(ts2) as ts2a,
		TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) AS unchanged
    FROM {$site_data[$site]['db']} 
";

if (!empty($site) || !empty($_GET['maintain']) || !empty($_GET['handle'])) {
    $query .= ' LEFT JOIN packages ON packages.name = bugdb.package_name';
}

if (!empty($_GET['maintain']) || !empty($_GET['handle'])) {
    $query .= ' LEFT JOIN maintains ON packages.id = maintains.package';
    $query .= ' AND maintains.handle = ';
    if (!empty($_GET['maintain'])) {
        $query .= $dbh->quoteSmart($_GET['maintain']);
    } else {
        $query .= $dbh->quoteSmart($_GET['handle']);
    }
}

if (empty($_GET['package_name']) || !is_array($_GET['package_name'])) {
    $_GET['package_name'] = array();
    $where_clause = ' WHERE bugdb.registered=1';
} else {
    $where_clause = ' WHERE bugdb.registered=1 AND bugdb.package_name';
    if (count($_GET['package_name']) > 1) {
        $where_clause .= " IN ('"
                       . join("', '", escapeSQL($_GET['package_name']))
                       . "')";
    } else {
        $where_clause .= ' = '
                           . $dbh->quoteSmart($_GET['package_name'][0]);
    }
}

if (empty($_GET['package_nname']) || !is_array($_GET['package_nname'])) {
    $_GET['package_nname'] = array();
} else {
    $where_clause .= ' AND bugdb.package_name';
    if (count($_GET['package_nname']) > 1) {
        $where_clause .= " NOT IN ('"
                       . join("', '", escapeSQL($_GET['package_nname']))
                       . "')";
    } else {
        $where_clause .= ' <> '
                       . $dbh->quoteSmart($_GET['package_nname'][0]);
    }
}

/*
 * Ensure status is valid and tweak search clause
 * to treat assigned, analyzed, critical and verified bugs as open
 */
if (empty($_GET['status'])) {
    $status = 'Open';
} else {
    $status = $_GET['status'];
}
switch ($status) {
    case 'All':
        break;
    case 'Closed':
    case 'Duplicate':
    case 'Critical':
    case 'Assigned':
    case 'Analyzed':
    case 'Verified':
    case 'Suspended':
    case 'Wont fix':
    case 'No Feedback':
    case 'Feedback':
    case 'Bogus':
        $where_clause .= " AND bugdb.status='$status'";
        break;
    case 'Old Feedback':
        $where_clause .= " AND bugdb.status='Feedback'" .
                         ' AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 60';
        break;
    case 'Fresh':
        $where_clause .= ' AND bugdb.status NOT IN' .
                         " ('Closed', 'Duplicate', 'Bogus')" .
                         ' AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) < 30';
        break;
    case 'Stale':
        $where_clause .= ' AND bugdb.status NOT IN' .
                         " ('Closed', 'Duplicate', 'Bogus')" .
                         ' AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 30';
        break;
    case 'Not Assigned':
        $where_clause .= ' AND bugdb.status NOT IN' .
                         " ('Closed', 'Duplicate', 'Bogus', 'Assigned'," .
                         " 'Wont Fix', 'Suspended')";
        break;
    // Closed Reports Since Last Release
    case 'CRSLR':
        if (!isset($_GET['package_name']) || count($_GET['package_name']) > 1) {
            // Act as ALL
            break;
        }

        // Fetch the last release date
        include_once 'pear-database-package.php';
        $releaseDate = package::getRecent(1, rinse($_GET['package_name'][0]));
        if (PEAR::isError($releaseDate)) {
            break;
        }

        $where_clause .= ' AND bugdb.status IN' .
                         " ('Closed', 'Duplicate', 'Bogus', 'Wont Fix', 'Suspended')
                           AND (UNIX_TIMESTAMP('" . $releaseDate[0]['releasedate'] . "') < UNIX_TIMESTAMP(bugdb.ts2))
                         ";
        break;
    case 'Open':
    default:
        $where_clause .= " AND bugdb.status IN ('Open', 'Assigned'," .
                         " 'Analyzed', 'Critical', 'Verified')";
    case 'OpenFeedback':
    default:
        $where_clause .= " AND bugdb.status IN ('Open', 'Assigned'," .
                         " 'Analyzed', 'Critical', 'Verified', 'Feedback')";
}

if (empty($_GET['search_for'])) {
    $search_for = '';
} else {
    $search_for = htmlspecialchars($_GET['search_for']);
    list($sql_search, $ignored) = format_search_string($search_for, $boolean_search);
    $where_clause .= $sql_search;
    if (count($ignored) > 0 ) {
        $warnings[] = 'The following words were ignored: ' .
                rinse(implode(', ', array_unique($ignored)));
    }
}

if (empty($_GET['bug_type']) || $_GET['bug_type'] == 'All') {
    $bug_type = '';
} else {
    $bug_type = $_GET['bug_type'];
    if ($bug_type == 'Bugs') {
        $where_clause .= ' AND (bugdb.bug_type = "Bug" OR bugdb.bug_type="Documentation Problem")';
    } else {
        $where_clause .= ' AND bugdb.bug_type = ' . $dbh->quoteSmart($bug_type);
    }
}

if (empty($_GET['bug_age']) || !(int)$_GET['bug_age']) {
    $bug_age = 0;
} else {
    $bug_age = (int)$_GET['bug_age'];
    $where_clause .= ' AND bugdb.ts1 >= '
                   . " DATE_SUB(NOW(), INTERVAL $bug_age DAY)";
}

if (empty($_GET['bug_updated']) || !(int)$_GET['bug_updated']) {
    $bug_updated = 0;
} else {
    $bug_updated = (int)$_GET['bug_updated'];
    $where_clause .= ' AND bugdb.ts2 >= '
                   . " DATE_SUB(NOW(), INTERVAL $bug_updated DAY)";
}

if (empty($_GET['php_os'])) {
    $php_os = '';
} else {
    $php_os = $_GET['php_os'];
    $where_clause .= " AND bugdb.php_os LIKE '%"
                   . $dbh->escapeSimple($php_os) . "%'";
}

if (empty($_GET['phpver'])) {
    $phpver = '';
} else {
    $phpver = $_GET['phpver'];
    $where_clause .= " AND bugdb.php_version LIKE '"
                   . $dbh->escapeSimple($phpver) . "%'";
}

if (empty($_GET['packagever'])) {
    $packagever = '';
} else {
    $packagever = $_GET['packagever'];
    $where_clause .= " AND bugdb.package_version LIKE '"
                   . $dbh->escapeSimple($packagever) . "%'";
}

if (empty($_GET['handle'])) {
    $handle = '';
    if (empty($_GET['assign'])) {
        $assign = '';
    } else {
        $assign = $_GET['assign'];
        $where_clause .= ' AND bugdb.assign = '
                       . $dbh->quoteSmart($assign);
    }
    if (empty($_GET['maintain'])) {
        $maintain = '';
    } else {
        $maintain = $_GET['maintain'];
        $where_clause .= ' AND maintains.handle = '
                       . $dbh->quoteSmart($maintain);
    }
} else {
    $handle = $_GET['handle'];
    $where_clause .= ' AND (maintains.handle = '
                   . $dbh->quoteSmart($handle)
                   . ' OR bugdb.assign = '
                   . $dbh->quoteSmart($handle). ')';
}

if (empty($_GET['author_email'])) {
    $author_email = '';
} else {
    $author_email = $_GET['author_email'];
    $qae = $dbh->quoteSmart($author_email);
    $where_clause .= ' AND (bugdb.email = '
                   . $qae . ' OR bugdb.handle=' . $qae . ')';
}

$where_clause .= ' AND (packages.package_type = '
               . $dbh->quoteSmart($site);

if ($pseudo = array_intersect($pseudo_pkgs, $_GET['package_name'])) {
    $where_clause .= " OR bugdb.package_name";
    if (count($pseudo) > 1) {
        $where_clause .= " IN ('"
                       . join("', '", escapeSQL($pseudo)) . "')";
    } else {
        $where_clause .= " = '" . implode('', escapeSQL($pseudo)) . "'";
    }
} else {
    $where_clause .= " OR bugdb.package_name IN ('"
                   . join("', '", escapeSQL($pseudo_pkgs)) . "')";
}

$where_clause .= ')';

$query .= $where_clause;

if (isset($_GET['direction']) && $_GET['direction'] != 'DESC') {
    $direction = 'ASC';
} else {
    $direction = 'DESC';
}

if (empty($_GET['order_by']) ||
    !array_key_exists($_GET['order_by'], $order_options))
{
    $order_by = 'id';
} else {
    $order_by = $_GET['order_by'];
}

if (empty($_GET['reorder_by']) ||
    !array_key_exists($_GET['reorder_by'], $order_options))
{
    $reorder_by = '';
} else {
    $reorder_by = $_GET['reorder_by'];
    if ($order_by == $reorder_by) {
        $direction = $direction == 'ASC' ? 'DESC' : 'ASC';
    } else {
        $direction = 'ASC';
        $order_by = $reorder_by;
    }
}

$query .= ' ORDER BY ' . $order_by . ' ' . $direction;

// if status Feedback then sort also after last updated time.
if ($status == 'Feedback') {
    $query .= ', bugdb.ts2 ' . $direction;
}

if (empty($_GET['limit']) || !(int)$_GET['limit']) {
    if (!empty($_GET['limit']) && $_GET['limit'] == 'All') {
        $limit = 'All';
    } else {
        $limit = MAX_BUGS_RETURN;
        $query .= " LIMIT 0, $limit";
    }
} else {
    $limit  = (int)$_GET['limit'];
    $query .= " LIMIT 0, $limit";
}

if (stristr($query, ';')) {
    die('BAD HACKER!! No database cracking for you today!');
} else {
    $res  = $dbh->query($query);
    $rows =  $res->numRows();

    $total_rows = $dbh->getOne('SELECT FOUND_ROWS()');
    if ($total_rows > $rows) {
        $warnings[] = 'The search was too general, only ' . MAX_BUGS_RETURN .
            ' bugs will be returned';
    }
}


header('Content-type: text/xml');

echo '<?xml version="1.0"?>
<?xml-stylesheet 
href="http://www.w3.org/2000/08/w3c-synd/style.css" type="text/css"
?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:admin="http://webns.net/mvcb/" xmlns:content="http://purl.org/rss/1.0/modules/content/">';
echo "\n    <channel rdf:about=\"http://" . urlencode($_SERVER['HTTP_HOST']) . "/bugs/search.php\">\n";
echo "    <title>PEAR Bug Search Results</title>\n";
echo '    <link>http://' . htmlspecialchars(urlencode($_SERVER['HTTP_HOST']) . '/bugs/search.php?' .
 http_build_query($_GET)) . "</link>\n";
echo "    <description>Search Results</description>\n";
echo "    <dc:language>en-us</dc:language>\n";
echo "    <dc:creator>pear-webmaster@lists.php.net</dc:creator>\n";
echo "    <dc:publisher>pear-webmaster@lists.php.net</dc:publisher>\n";
echo "    <admin:generatorAgent rdf:resource=\"http://" . $_SERVER['HTTP_HOST'] . "/bugs\"/>\n";
echo "    <sy:updatePeriod>hourly</sy:updatePeriod>\n";
echo "    <sy:updateFrequency>1</sy:updateFrequency>\n";
echo "    <sy:updateBase>2000-01-01T12:00+00:00</sy:updateBase>\n";
echo '    <items>
     <rdf:Seq>
';

if ($total_rows > 0) {
    $i = 0;
    $items = array();
    while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $i++;
        echo "      <rdf:li rdf:resource=\"http://" . $_SERVER['HTTP_HOST'] . "/bug/{$row['id']}\" />\n";
        $items[$i] = "    <item rdf:about=\"http://" . $_SERVER['HTTP_HOST'] . "/bug/{$row['id']}\">\n";
        $items[$i] .= '      <title>' . utf8_encode(htmlspecialchars($row['bug_type'] . ' ' . $row['id'] . ' [' . $row['status'] . '] ' . $row['sdesc'])) . "</title>\n";
        $items[$i] .= "      <link>http://" . $_SERVER['HTTP_HOST'] . "/bugs/{$row['id']}</link>\n";
        $items[$i] .= '      <description><![CDATA[' . utf8_encode(htmlspecialchars($row['ldesc'])) . "]]></description>\n";
        if (!$row['unchanged']) {
            $items[$i] .= '      <dc:date>' . rssdate($row['ts1a']) . "</dc:date>\n";
        } else {
            $items[$i] .= '      <dc:date>' . rssdate($row['ts2a']) . "</dc:date>\n";
        }
        $items[$i] .= '      <dc:creator>' . utf8_encode(htmlspecialchars(spam_protect($row['email']))) . "</dc:creator>\n";
        $items[$i] .= '      <dc:subject>' .
           utf8_encode(htmlspecialchars($row['package_name'])) . ' ' .
           utf8_encode(htmlspecialchars($row['bug_type'])) . "</dc:subject>\n";
        $items[$i] .= "    </item>\n";
    }
    $items = implode('', $items);
} else {
    $warnings[] = "No bugs matched your criteria";
}

echo '
     </rdf:Seq>
    </items>
  </channel>

  <image rdf:about="http://' , $site_url , '/gifs/', $site, '-logo.gif">
    <title>' , $siteBig , ' Bugs</title>
    <url>http://' , $site_url , '/gifs/', $site, '-logo.gif</url>
    <link>http://' , $site_url , $basedir, '</link>
  </image>

', $items;
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

function rssdate($date)
{
    return date('r', $date - date('Z', $date));
}
