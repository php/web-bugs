<?php

/**
 * Obtain common includes
 */
require_once '../include/prepend.inc';

Bug_DataObject::init();

if (isset($_GET['packagexml'])) {
    $roadmap = Bug_DataObject::bugDB('bugdb_roadmap');
    if (!isset($_GET['package'])) {
        response_header('Error :: No package selected');
        display_bug_error('No package selected');
        response_footer();
        exit;
    }
    if (!isset($_GET['roadmap'])) {
        response_header('Error :: No roadmap selected');
        display_bug_error('No roadmap selected, cannot generate package.xml');
        response_footer();
        exit;
    }
    $roadmap->package = $_GET['package'];
    $roadmap->roadmap_version = $_GET['roadmap'];
    if (!$roadmap->find()) {
        response_header('Error :: No roadmap found');
        display_bug_error('Roadmap not found, cannot generate package.xml');
        response_footer();
        exit;
    }
    require 'roadmap/package-generator.php';
    $gen = new Roadmap_Package_Generator($_GET['package']);
    $xml = $gen->getRoadmapPackage($_GET['roadmap']);
    if (!$xml) {
        require 'roadmap/info.php';
        if (!Roadmap_Info::percentDone($_GET['package'], $_GET['roadmap'])) {
            $xml = 'Unable to generate package.xml, no bugs closed yet';
        } else {
            $xml = 'Unable to generate package.xml, problems detected, please report to
            ' . PEAR_WEBMASTER_EMAIL . '.';
            foreach ($gen->getErrors() as $error) {
                $xml .= '<br />' . htmlspecialchars($error['message']) . "<br />";
            }
        }
    }
    $templateData = new stdClass();
    $templateData->xml = $xml;
    $templateData->package = $_GET['package'];
    $templateData->roadmap = $_GET['roadmap'];
    include "{$ROOT_DIR}/templates/roadmap_packagexml.php";
    exit;
}
if (isset($_GET['showornew'])) {
    $roadmap = Bug_DataObject::bugDB('bugdb_roadmap');
    $roadmap->roadmap_version = $_GET['showornew'];
    if (!$roadmap->find()) {
        // populate form with default values
        $_POST['roadmap_version'] = $_GET['showornew'];
        $_GET['new'] = 1;
        $_POST['description'] = 'Enter roadmap description';
        $_POST['releasedate'] = 'future';
    }
}
if (isset($_GET['edit'])) {
    $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');
    $bugdb->id = $_GET['edit'];
    if (!$bugdb->find(true)) {
        response_header('Error :: no such roadmap');
        display_bug_error('Unknown roadmap "' . htmlspecialchars($_GET['edit']));
        response_footer();
        exit;
    }
    $_GET['package'] = $bugdb->package;
}
if (isset($_GET['edit']) || isset($_GET['new']) || isset($_GET['delete'])) {
    auth_require();
    if (isset($_GET['delete'])) {
        $roadmap = Bug_DataObject::bugDB('bugdb_roadmap');
        $roadmap->id = $_GET['delete'];
        if ($roadmap->find(true)) {
            $_GET['package'] = $roadmap->package;
        } else {
            $_GET['package'] = '@#^$&*#^@*$&@';
        }
    }
    $bugtest = Bug_DataObject::pearDB('maintains');
    include_once 'pear-database-package.php';
    $bugtest->package = package::info($_GET['package'], 'id');
    $bugtest->handle = $auth_user->handle;
    if (!$bugtest->find(true) || !$bugtest->role == 'lead') {
        response_header('Error :: insufficient privileges');
        display_bug_error('You must be a lead maintainer to edit a package\'s roadmap');
        response_footer();
        exit;
    }
}
if (isset($_GET['new']) && isset($_POST['go'])) {
    $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');

    $templateData = new stdClass();
    $templateData->package = $_GET['package'];
    $allroadmaps = Bug_DataObject::bugDB('bugdb_roadmap');
    $allroadmaps->package = $_GET['package'];

    $allroadmaps->orderBy('releasedate ASC');
    $allroadmaps->find(false);
    $templateData->roadmap = array();
    $roadmap_v = array();
    while ($allroadmaps->fetch()) {
        $a = $allroadmaps->toArray();
        $templateData->roadmap[] = $a;
        $roadmap_v[] = $a['roadmap_version'];
    }

    if (isset($_POST['releasedate']) && $_POST['releasedate'] != 'future') {
        $_POST['releasedate'] = date('Y-m-d', strtotime($_POST['releasedate']));
    }
    $templateData->info = array(
        'package' => htmlspecialchars($_GET['package']),
        'releasedate' => isset($_POST['releasedate']) ?
            $_POST['releasedate'] : '',
        'roadmap_version' => isset($_POST['roadmap_version']) ? htmlspecialchars($_POST['roadmap_version']) :
            '',
        'description' => isset($_POST['description']) ? htmlspecialchars($_POST['description']) :
            '',
        );
    $templateData->isnew = true;
    $templateData->import = isset($_POST['importbugs']) ? true : false;
    $releases = package::info(htmlspecialchars($_GET['package']), 'releases');
    $templateData->lastRelease = count($releases) ? key($releases) : '';

    if (empty($_POST['roadmap_version'])) {
        $templateData->errors = array('Roadmap version cannot be empty');
        include "{$ROOT_DIR}/templates/roadmapform.php";
        exit;
    }

    // Check if the roadmap already exists
    if (in_array($_POST['roadmap_version'], $roadmap_v)) {
        $templateData->errors = array('Roadmap version ' . htmlspecialchars($_POST['roadmap_version']) . ' already exists');
        include "{$ROOT_DIR}/templates/roadmapform.php";
        exit;
    }

    $bugdb->roadmap_version = $_POST['roadmap_version'];
    if ($_POST['releasedate'] == 'future') {
        // my birthday will represent the future ;)
        $_POST['releasedate'] = '1976-09-02 17:15:30';
    }
    $bugdb->releasedate = date('Y-m-d H:i:s', strtotime($_POST['releasedate']));
    $bugdb->package = $_GET['package'];
    $bugdb->description = $_POST['description'];
    $rid = $bugdb->insert();

    if (isset($_POST['importbugs'])) {
        // Fetch the last release date
        include_once 'pear-database-package.php';
        $releaseDate = package::getRecent(1, rinse($_GET['package']));
        if (PEAR::isError($releaseDate)) {
            break;
        }
        $query = '
            SELECT SQL_CALC_FOUND_ROWS bugdb.id
            FROM bugdb
            LEFT JOIN packages ON packages.name = bugdb.package_name
            WHERE bugdb.registered IN(1,0)
            AND bugdb.package_name = ' . $dbh->quote(rinse($_GET['package'])) . '
            AND bugdb.status IN' .
            " ('Closed', 'Duplicate', 'Bogus', 'Wont Fix', 'Suspended')
            AND (UNIX_TIMESTAMP('" . $releaseDate[0]['releasedate'] . "') < UNIX_TIMESTAMP(bugdb.ts2))" .
            'AND (bugdb.bug_type = "Bug" OR bugdb.bug_type="Documentation Problem")';

        $link = Bug_DataObject::bugDB('bugdb_roadmap_link');
        $res =& $dbh->prepare($query)->execute();
        foreach ($res->fetchAll(MDB2_FETCHMODE_ASSOC) as $row) {
            $link->id = $row['id'];
            $link->delete();
            $link->id = $row['id'];
            $link->roadmap_id = $rid;
            $link->insert();
        }
    }

    unset($_GET['new']);
}

if (isset($_GET['edit']) && isset($_POST['go'])) {
    $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');
    $bugdb->id = $_GET['edit'];
    if (empty($_POST['roadmap_version'])) {
        $templateData = new stdClass();
        $templateData->package = $_GET['package'];
        $allroadmaps = Bug_DataObject::bugDB('bugdb_roadmap');
        $allroadmaps->package = $_GET['package'];
        $allroadmaps->orderBy('releasedate ASC');
        $allroadmaps->find(false);
        $templateData->roadmap = array();
        while ($allroadmaps->fetch()) {
            $templateData->roadmap[] = $allroadmaps->toArray();
        }
        if (isset($_POST['releasedate']) && $_POST['releasedate'] != 'future') {
            $_POST['releasedate'] = date('Y-m-d', strtotime($_POST['releasedate']));
        }
        $templateData->info = array(
            'package' => htmlspecialchars($_GET['package']),
            'releasedate' => isset($_POST['releasedate']) ?
                $_POST['releasedate'] : '',
            'roadmap_version' => isset($_POST['roadmap_version']) ? htmlspecialchars($_POST['roadmap_version']) :
                '',
            'description' => isset($_POST['description']) ? htmlspecialchars($_POST['description']) :
                '',
            );
        $templateData->isnew = true;
        $templateData->errors = array('Roadmap version cannot be empty');
        include "{$ROOT_DIR}/templates/roadmapform.php";
        exit;
    }
    if ($bugdb->find(false)) {
        $bugdb->roadmap_version = $_POST['roadmap_version'];
        if ($_POST['releasedate'] == 'future') {
            // my birthday will represent the future ;)
            $_POST['releasedate'] = '1976-09-02 17:15:30';
        }
        $bugdb->releasedate = date('Y-m-d H:i:s', strtotime($_POST['releasedate']));
        $bugdb->package = $_GET['package'];
        $bugdb->description = $_POST['description'];
        $bugdb->update();
        unset($_GET['edit']);
    }
}
if (isset($_GET['delete'])) {
    $links = Bug_DataObject::bugDB('bugdb_roadmap_link');
    $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');
    $links->roadmap_id = $bugdb->id = $_GET['delete'];
    $links->delete();
    $bugdb->delete();
}
if (isset($_POST['saveaddbugs'])) {
    auth_require('pear.dev');
    if (!isset($_POST['package'])) {
        response_header('Error :: No package selected');
        display_bug_error('No package selected');
        response_footer();
        exit;
    }
    $roadmap = Bug_DataObject::bugDB('bugdb_roadmap');
    $roadmap->package = $_POST['package'];
    if (!isset($_POST['roadmap'])) {
        response_header('Error :: No roadmap selected');
        display_bug_error('No roadmap selected');
        response_footer();
        exit;
    }
    $roadmap->roadmap_version = $_POST['roadmap'];
    if (!$roadmap->find(true)) {
        response_header('Error :: no such roadmap');
        display_bug_error('Unknown roadmap "' . htmlspecialchars($_GET['roadmap']) . '"');
        response_footer();
        exit;
    }
    $roadmaps = Bug_DataObject::bugDB('bugdb_roadmap_link');
    $roadmaps->roadmap_id = $roadmap->id;
    $roadmaps->delete(); // empty out existing

    if (isset($_POST['bugs']) && is_array($_POST['bugs'])) {
        foreach ($_POST['bugs'] as $bug => $unused) {
            $roadmaps->id = $bug;
            $roadmaps->roadmap_id = $roadmap->id;
            $roadmaps->insert();
        }
    }
    $_GET['package'] = $_POST['package'];
    $_GET['roadmap'] = $_POST['roadmap'];
    $_GET['addbugs'] = 1;
}
$test = Bug_DataObject::pearDB('packages');
$test->name = $_GET['package'];
if (!isset($_GET['package'])) {
    response_header('Error :: No package selected');
    display_bug_error('No package selected');
    response_footer();
    exit;
}
if (!$test->find()) {
    response_header('Error :: no such package');
    display_bug_error('Unknown package "' . htmlspecialchars($_GET['package']) . '"');
    response_footer();
    exit;
}
if (isset($_GET['addbugs'])) {
    auth_require('pear.dev');
    $roadmap = Bug_DataObject::bugDB('bugdb_roadmap');
    $roadmap->package = $_GET['package'];
    if (!isset($_GET['roadmap'])) {
        response_header('Error :: No roadmap selected');
        display_bug_error('No roadmap selected');
        response_footer();
        exit;
    }
    $roadmap->roadmap_version = $_GET['roadmap'];
    if (!$roadmap->find(true)) {
        response_header('Error :: no such roadmap');
        display_bug_error('Unknown roadmap "' . htmlspecialchars($_GET['roadmap']) . '"');
        response_footer();
        exit;
    }

    $bugdb = Bug_DataObject::bugDB('bugdb');
    $bugdb->package_name = $_GET['package'];
    $bugdb->orderBy('id');
    $features = clone($bugdb);
    $bugdb->whereAdd('bug_type IN ("Bug", "Documentation Problem")');
    $releases = Bug_DataObject::pearDB('releases');
    include_once 'pear-database-package.php';
    $releases->package = package::info($_GET['package'], 'id');
    $releases->orderBy('releasedate DESC');
    if ($releases->find(true)) {
        $bugdb->whereAdd('(ts2 > "' . date('Y-m-d H:i:s', strtotime($releases->releasedate)) .
            '" AND status="Closed") OR status in ("Open", "Feedback", "Analyzed", ' .
            '"Assigned", "Critical", "Verified", "Suspended")');
        $features->whereAdd('(ts2 > "' . date('Y-m-d H:i:s', strtotime($releases->releasedate)) .
            '" AND status="Closed") OR status in ("Open", "Feedback", "Analyzed", ' .
            '"Assigned", "Critical", "Verified", "Suspended")');
    } else {
        $bugdb->whereAdd('status in ("Open", "Feedback", "Analyzed", ' .
            '"Assigned", "Critical", "Verified", "Suspended")');
        $features->whereAdd('status in ("Open", "Feedback", "Analyzed", ' .
            '"Assigned", "Critical", "Verified", "Suspended")');
    }
    $features->bug_type = 'Feature/Change Request';
    $bugdb->find();
    $roadmaps = Bug_DataObject::bugDB('bugdb_roadmap_link');
    $roadmaps->roadmap_id = $roadmap->id;
    $roadmaps->find();
    $existing = array();
    while ($roadmaps->fetch()) {
        $existing[$roadmaps->id] = 1;
    }
    $allb = $allf = array();
    while ($bugdb->fetch()) {
        $allb[$bugdb->id] = array(
            'summary' => $bugdb->sdesc,
            'status' => $bugdb->status,
            'lastupdate' => $bugdb->ts2,
            'inroadmap' => false);
        if (isset($existing[$bugdb->id])) {
            $allb[$bugdb->id]['inroadmap'] = true;
        }
    }
    $features->find();
    while ($features->fetch()) {
        $allf[$features->id] = array(
            'summary' => $features->sdesc,
            'status' => $features->status,
            'lastupdate' => $features->ts2,
            'inroadmap' => false);
        if (isset($existing[$features->id])) {
            $allf[$features->id]['inroadmap'] = true;
        }
    }
    $templateData = new stdClass();
    $templateData->saved = isset($_POST['saveaddbugs']);
    $templateData->package = $_GET['package'];
    $templateData->roadmap = $_GET['roadmap'];
    $templateData->bugs = $allb;
    $templateData->features = $allf;
    $templateData->tla = $tla;
    include "{$ROOT_DIR}/templates/roadmapadd.php";
    exit;
}
$order_options = array(
    ''             => 'relevance',
    'id'           => 'ID',
    'ts1'          => 'date',
    'package'      => 'package',
    'bug_type'     => 'bug_type',
    'status'       => 'status',
    'package_version'  => 'package_version',
    'php_version'  => 'php_version',
    'php_os'       => 'os',
    'sdesc'        => 'summary',
    'assign'       => 'assignment',
);
$bugdb = Bug_DataObject::bugDb('bugdb');
$templateData = new stdClass();

$bugdb->selectAdd('SQL_CALC_FOUND_ROWS');
$bugdb->selectAdd('TO_DAYS(NOW())-TO_DAYS(bugb.ts2) AS unchanged');
$bugdb->package_name = $_GET['package'];

if (empty($_GET['direction']) || $_GET['direction'] != 'DESC') {
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

$bugdb->orderBy($order_by . ' ' . $direction);

if (empty($_GET['begin']) || !(int)$_GET['begin']) {
    $begin = 0;
} else {
    $begin = (int)$_GET['begin'];
}

if (empty($_GET['limit']) || !(int)$_GET['limit']) {
    if (!empty($_GET['limit']) && $_GET['limit'] == 'All') {
        $limit = 'All';
    } else {
        $limit = 30;
        $bugdb->limit($begin, $limit);
    }
} else {
    $limit  = (int)$_GET['limit'];
    $bugdb->limit($begin, $limit);
}

include_once 'pear-database-package.php';

$releases = package::info($_GET['package'], 'releases');
$templateData->showold = isset($_GET['showold']);
$templateData->releases = array_keys($releases);
$allroadmaps = Bug_DataObject::bugDB('bugdb_roadmap');
$allroadmaps->package = $_GET['package'];
$allroadmaps->orderBy('releasedate ASC');
$allroadmaps->find(false);
$roadmaps = Bug_DataObject::bugDB('bugdb_roadmap_link');
$roadmaps->selectAs();
$templateData->bugs = $templateData->features = $templateData->roadmap = $templateData->totalbugs =
    $templateData->closedbugs = $templateData->totalfeatures = $templateData->closedfeatures =
    $templateData->summary = array();
$peardb = Bug_DataObject::pearDB('releases');
$peardb->package = $_GET['package'];
while ($allroadmaps->fetch()) {
    $test = clone($peardb);
    $test->version = $allroadmaps->roadmap_version;
    if ($test->find()) {
        // already released, so this is defunct
        continue;
    }
    if (isset($_GET['roadmapdetail']) && $_GET['roadmapdetail'] === $allroadmaps->roadmap_version) {
        $features = clone($bugdb);
        $bugs     = clone($bugdb);

        $roadmaps->roadmap_id = $allroadmaps->id;
        $features->selectAs();
        $features->joinAdd($roadmaps);
        $features->bug_type = 'Feature/Change Request';
        $rows = $features->find(false);
        $total_rows = $dbh->prepare('SELECT FOUND_ROWS()')->execute()->fetchOne();

        if ($rows) {
            $package_string = '';

            $link = 'roadmap.php' .
                    '?' .
                    $package_string  .
                    '&amp;order_by='    . $order_by .
                    '&amp;direction='   . $direction .
                    '&amp;limit='       . $limit;

            $templateData->begin = $begin;
            $templateData->rows = $rows;
            $templateData->total_rows = $total_rows;
            $templateData->link = $link;
            $templateData->limit = $limit;
            $results = array();
            while ($features->fetch()) {
                $results[] = $features->toArray();
            }
            $templateData->results = $results;
            $templateData->tla = $tla;
            $templateData->types = $bug_types;
            ob_start();
        	include "{$ROOT_DIR}/templates/searchresults.php";
        	$features = ob_get_contents();
        	ob_end_clean();
        } else {
            $features = 'No features';
        }

        $bugs->selectAs();
        $bugs->joinAdd($roadmaps);
        $bugs->whereAdd('bugdb.bug_type IN("Bug", "Documentation Problem")');
        $rows = $bugs->find(false);
        $total_rows = $dbh->prepare('SELECT FOUND_ROWS()')->execute()->fetchOne();

        if ($rows) {
            $package_string = '';

            $link = 'roadmap.php' .
                    '?' .
                    $package_string  .
                    '&amp;order_by='    . $order_by .
                    '&amp;direction='   . $direction .
                    '&amp;limit='       . $limit;

            $templateData->begin = $begin;
            $templateData->rows = $rows;
            $templateData->total_rows = $total_rows;
            $templateData->link = $link;
            $templateData->limit = $limit;
            $results = array();
            while ($bugs->fetch()) {
                $results[] = $bugs->toArray();
            }
            $templateData->results = $results;
            $templateData->tla = $tla;
            $templateData->types = $bug_types;
            ob_start();
        	include "{$ROOT_DIR}/templates/searchresults.php";
        	$bugs = ob_get_contents();
        	ob_end_clean();
        } else {
            $bugs = 'No bugs';
        }
        $templateData->bugs[$allroadmaps->roadmap_version] = $bugs;
        $templateData->feature_requests[$allroadmaps->roadmap_version] = $features;
        $templateData->summary[$allroadmaps->roadmap_version] = false;
    } else {
        // this just shows a summary of closed bugs and a percentage fixed
        $templateData->summary[$allroadmaps->roadmap_version] = true;
        $bugquery = 'SELECT COUNT(bugdb.id) FROM bugdb_roadmap_link r, bugdb
            WHERE r.roadmap_id = ? AND bugdb.id = r.id AND bugdb.bug_type IN
                ("Bug", "Documentation Problem")';
        $featurequery = 'SELECT COUNT(bugdb.id) FROM bugdb_roadmap_link r, bugdb
            WHERE r.roadmap_id = ? AND bugdb.id = r.id AND bugdb.bug_type =
                "Feature/Change Request"';
        if ($templateData->totalbugs[$allroadmaps->roadmap_version] = $dbh->prepare($bugquery)->execute(
              array($allroadmaps->id))->fetchOne()) {
            $templateData->closedbugs[$allroadmaps->roadmap_version] = $dbh->prepare('
                SELECT COUNT(bugdb.id) FROM bugdb, bugdb_roadmap_link r
                WHERE
                    bugdb.id = r.id AND
                    r.roadmap_id = ? AND
                    bugdb.bug_type IN ("Bug", "Documentation Problem") AND
                    bugdb.status = "Closed"')->execute(array($allroadmaps->id))->fetchOne();
        }
        if ($templateData->totalfeatures[$allroadmaps->roadmap_version] = $dbh->prepare($featurequery)->execute(
              array($allroadmaps->id))->fetchOne()) {
            $templateData->closedfeatures[$allroadmaps->roadmap_version] = $dbh->prepare('
                SELECT COUNT(bugdb.id) FROM bugdb, bugdb_roadmap_link r
                WHERE
                    bugdb.id = r.id AND
                    r.roadmap_id = ? AND
                    bugdb.bug_type = "Feature/Change Request" AND
                    bugdb.status = "Closed"')->execute( array($allroadmaps->id))->fetchOne();
        }
    }
    $templateData->roadmap[] = $allroadmaps->toArray();
}
$templateData->package = $_GET['package'];
if (isset($_GET['edit'])) {
    $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');
    $bugdb->id = $_GET['edit'];
    if (!$bugdb->find(true)) {
        response_header('Error :: no such roadmap');
        display_bug_error('Unknown roadmap "' . htmlspecialchars($_GET['edit']));
        response_footer();
        exit;
    }
    $templateData->info = $bugdb->toArray();
    $templateData->isnew = false;
    $templateData->errors = false;
    include "{$ROOT_DIR}/templates/roadmapform.php";
    exit;
}
if (isset($_GET['new'])) {
    $templateData->errors = false;
    if (isset($_POST['go'])) {
        if ($_POST['releasedate'] == 'future') {
            // my birthday will represent the future ;)
            $_POST['releasedate'] = '1976-09-02 17:15:30';
        }
        $bugdb = Bug_DataObject::bugDB('bugdb_roadmap');
        $bugdb->description = $_POST['description'];
        $bugdb->releasedate = date('Y-m-d H:i:s', strtotime($_POST['releasedate']));
        $bugdb->package = $_GET['package'];
        $bugdb->roadmap_version = $_POST['roadmap_version'];
        if (empty($_POST['roadmap_version'])) {
            $templateData->errors = array('Roadmap version cannot be empty');
        } else {
            $bugdb->insert();
        }
    }
    if (isset($_POST['releasedate']) && $_POST['releasedate'] != 'future') {
        $_POST['releasedate'] = date('Y-m-d', strtotime($_POST['releasedate']));
    }
    $templateData->info = array(
        'package' => htmlspecialchars($_GET['package']),
        'releasedate' => isset($_POST['releasedate']) ?
            $_POST['releasedate'] : '',
        'roadmap_version' => isset($_POST['roadmap_version']) ? htmlspecialchars($_POST['roadmap_version']) :
            '',
        'description' => isset($_POST['description']) ? htmlspecialchars($_POST['description']) :
            '',
        );
    $templateData->isnew = true;
    $templateData->import = isset($_POST['importbugs']) ? true : false;
    $releases = package::info(htmlspecialchars($_GET['package']), 'releases');
    $templateData->lastRelease = count($releases) ? key($releases) : '';
    include "{$ROOT_DIR}/templates/roadmapform.php";
    exit;
}

include "{$ROOT_DIR}/templates/roadmap.php";
