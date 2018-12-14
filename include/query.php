<?php

use App\Repository\PackageRepository;

$errors = [];
$warnings = [];
$order_options = [
	''				=> 'relevance',
	'id'			=> 'ID',
	'ts1'			=> 'date',
	'ts2'			=> 'last modified',
	'package_name'	=> 'package',
	'bug_type'		=> 'bug_type',
	'status'		=> 'status',
	'php_version'	=> 'php_version',
	'php_os'		=> 'os',
	'sdesc'			=> 'summary',
	'assign'		=> 'assignment',
	'avg_score'		=> 'avg. vote score',
	'votes_count'	=> 'number of votes',
	'RAND()'	=> 'random',
];

// Fetch pseudo packages
$packageRepository = new PackageRepository($dbh);
$pseudo_pkgs = $packageRepository->findAll();

// Setup input variables..
$boolean_search = isset($_GET['boolean']) ? (int) $_GET['boolean'] : 0;
$status = !empty($_GET['status']) ? $_GET['status'] : 'Open';
$search_for = !empty($_GET['search_for']) ? $_GET['search_for'] : '';
$bug_type = (!empty($_GET['bug_type']) && $_GET['bug_type'] != 'All') ? $_GET['bug_type'] : '';
$bug_age = (int) (isset($_GET['bug_age']) ? $_GET['bug_age'] : 0);
$bug_updated = (int) (isset($_GET['bug_updated']) ? $_GET['bug_updated'] : 0);
$php_os = !empty($_GET['php_os']) ? $_GET['php_os'] : '';
$php_os_not = !empty($_GET['php_os_not']) ? 'not' : '';
$phpver = !empty($_GET['phpver']) ? $_GET['phpver'] : '';
$cve_id = !empty($_GET['cve_id']) ? $_GET['cve_id'] : '';
$cve_id_not = !empty($_GET['cve_id_not']) ? 'not' : '';
$patch = !empty($_GET['patch']) ? $_GET['patch'] : '';
$pull = !empty($_GET['pull']) ? $_GET['pull'] : '';
$private = !empty($_GET['private']) ? $_GET['private'] : '';
$begin = (int) ((!empty($_GET['begin']) && $_GET['begin'] > 0) ? $_GET['begin'] : 0);
$limit = (defined('MAX_BUGS_RETURN')) ? MAX_BUGS_RETURN : 30;
$project = (!empty($_GET['project']) && $_GET['project'] != 'All') ? $_GET['project'] : '';
if (!empty($_GET['limit'])) {
	$limit = ($_GET['limit'] == 'All') ? 'All' : (($_GET['limit'] > 0) ? (int) $_GET['limit'] : $limit);
}
$direction = (!empty($_GET['direction']) && $_GET['direction'] != 'DESC') ? 'ASC' : 'DESC';
$order_by = (!empty($_GET['order_by']) && array_key_exists($_GET['order_by'], $order_options)) ? $_GET['order_by'] : '';
$reorder_by = (!empty($_GET['reorder_by']) && array_key_exists($_GET['reorder_by'], $order_options)) ? $_GET['reorder_by'] : '';
$assign = !empty($_GET['assign']) ? $_GET['assign'] : '';
$author_email = !empty($_GET['author_email']) ? spam_protect($_GET['author_email'], 'reverse') : '';
$package_name = (isset($_GET['package_name']) && is_array($_GET['package_name'])) ? $_GET['package_name'] : [];
$package_nname = (isset($_GET['package_nname']) && is_array($_GET['package_nname'])) ? $_GET['package_nname'] : [];
$commented_by = !empty($_GET['commented_by']) ? spam_protect($_GET['commented_by'], 'reverse') : '';

if (isset($_GET['cmd']) && $_GET['cmd'] == 'display')
{
	$query = '
		SELECT SQL_CALC_FOUND_ROWS
		bugdb.*,
		TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) AS unchanged,
		UNIX_TIMESTAMP(ts1) AS submitted,
		UNIX_TIMESTAMP(ts2) AS modified
		FROM bugdb
	';

	if (in_array($order_by, ['votes_count', 'avg_score'])) {
		$query .= 'LEFT JOIN bugdb_votes v ON bugdb.id = v.bug';
	}

	if ($commented_by != '') {
		$query .= ' LEFT JOIN bugdb_comments c ON bugdb.id = c.bug';
	}

	$where_clause = ' WHERE 1 = 1 ';

	if (isset($user_flags) && ($user_flags & (BUGS_SECURITY_DEV | BUGS_TRUSTED_DEV))) {
		if ($private != '') {
			$where_clause .= ' AND bugdb.private = "Y" ';
		}
	} else {
		/* Non trusted developer should see the Security related bug report just when it is public */
		$where_clause .= ' AND (bugdb.bug_type <> "Security" OR private = "N") ';
	}

	if (!empty($package_name)) {
		$where_clause .= ' AND bugdb.package_name';
		if (count($package_name) > 1) {
			$package_name = array_map([$dbh, 'quote'], $package_name);
			$where_clause .= " IN (" . join(", ", $package_name) . ")";
		} else {
			$where_clause .= ' = ' . $dbh->quote($package_name[0]);
		}
	}

	if (!empty($package_nname)) {
		$where_clause .= ' AND bugdb.package_name';
		if (count($package_nname) > 1) {
			$package_nname = array_map([$dbh, 'quote'], $package_nname);
			$where_clause .= " NOT IN (" . join(", ", $package_nname) . ")";
		} else {
			$where_clause .= ' <> ' . $dbh->quote($package_nname[0]);
		}
	}

	// Ensure status is valid and tweak search clause to treat assigned, analyzed, critical and verified bugs as open
	switch ($status) {
		case 'All':
			break;
		case 'Closed':
		case 'Re-Opened':
		case 'Duplicate':
		case 'Critical':
		case 'Assigned':
		case 'Analyzed':
		case 'Verified':
		case 'Suspended':
		case 'Wont fix':
		case 'No Feedback':
		case 'Feedback':
		case 'Not a bug':
			$where_clause .= "	AND bugdb.status='$status'";
			break;
		case 'Old Feedback':
			$where_clause .= "	AND bugdb.status='Feedback'
								AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 60";
			break;
		case 'Fresh':
			$where_clause .= "	AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Not a bug')
								AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) < 30";
			break;
		case 'Stale':
			$where_clause .= "	AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Not a bug')
								AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 30";
			break;
		case 'Not Assigned':
			$where_clause .= " AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Not a bug', 'Assigned', 'Wont Fix', 'Suspended')";
			break;
		case 'OpenFeedback':
			$where_clause .= " AND bugdb.status IN ('Open', 'Re-Opened', 'Assigned','Analyzed', 'Critical', 'Verified', 'Feedback')";
			break;
		default:
		case 'Open':
			$where_clause .= " AND bugdb.status IN ('Open', 'Re-Opened', 'Assigned', 'Analyzed', 'Critical', 'Verified')";
	}

	if ($search_for != '') {
		list($sql_search, $ignored) = format_search_string($search_for, $boolean_search);
		$where_clause .= $sql_search;
		if (count($ignored) > 0 ) {
			$warnings[] = 'The following words were ignored: ' . implode(', ', array_unique($ignored));
		}
	}

	if ($bug_type != '') {
		if ($bug_type == 'Bugs') {
			$where_clause .= ' AND (bugdb.bug_type = "Bug" OR bugdb.bug_type="Documentation Problem")';
		} else {
			$where_clause .= ' AND bugdb.bug_type = ' . $dbh->quote($bug_type);
		}
	}

	if ($bug_age > 0) {
		$where_clause .= " AND bugdb.ts1 >= DATE_SUB(NOW(), INTERVAL $bug_age DAY)";
	}

	if ($bug_updated > 0) {
		$where_clause .= " AND bugdb.ts2 >= DATE_SUB(NOW(), INTERVAL $bug_updated DAY)";
	}

	if ($php_os != '') {
		$where_clause .= " AND bugdb.php_os {$php_os_not} LIKE " . $dbh->quote('%'.$php_os.'%');
	}

	if ($phpver != '') {
		$where_clause .= " AND bugdb.php_version LIKE " . $dbh->quote($phpver.'%');
	}

	if ($project != '') {
		$where_clause .= " AND EXISTS (SELECT 1 FROM bugdb_pseudo_packages b WHERE b.name = bugdb.package_name AND  b.project = ". $dbh->quote($project) ." LIMIT 1)";
	}

	if ($cve_id != '') {
		$where_clause .= " AND bugdb.cve_id {$cve_id_not} LIKE " . $dbh->quote($cve_id.'%');
	}

	/* A search for patch&pull should be (patch or pull) */
	if ($patch != '' || $pull != '') {
		$where_clause .= " AND (1=2";
	}
	if ($patch != '') {
		$where_clause .= " OR EXISTS (SELECT 1 FROM bugdb_patchtracker WHERE bugdb_id = bugdb.id LIMIT 1)";
	}
	if ($pull != '') {
		$where_clause .= " OR EXISTS (SELECT 1 FROM bugdb_pulls WHERE bugdb_id = bugdb.id LIMIT 1)";
	}
	if ($patch != '' || $pull != '') {
		$where_clause .= ")";
	}
	if ($assign != '') {
		$where_clause .= ' AND bugdb.assign = ' . $dbh->quote($assign);
	}

	if ($author_email != '') {
		$where_clause .= ' AND bugdb.email = ' . $dbh->quote($author_email);
	}
	if ($commented_by != '') {
		$where_clause .= ' AND c.email = ' . $dbh->quote($commented_by);
	}

	$where_clause .= ' AND (1=1';

	if ($pseudo = array_intersect(array_keys($pseudo_pkgs), $package_name)) {
		$where_clause .= " OR bugdb.package_name";
		if (count($pseudo) > 1) {
			$pseudo = array_map([$dbh, 'quote'], $pseudo);
			$where_clause .= " IN (" . join(", ", $pseudo) . ")";
		} else {
			$where_clause .= " = " . $dbh->quote(reset($pseudo));
		}
	} else {
		$items = array_map([$dbh, 'quote'], array_keys($pseudo_pkgs));
		$where_clause .= " OR bugdb.package_name IN (" . join(", ", $items) . ")";
	}

	$query .= "$where_clause )";

	if ($reorder_by != '') {
		if ($order_by == $reorder_by) {
			$direction = $direction == 'ASC' ? 'DESC' : 'ASC';
		} else {
			$direction = $reorder_by == 'ts2' ? 'DESC' : 'ASC';
			$order_by = $reorder_by;
		}
	}

	$order_by_clauses = [];
	if (in_array($order_by, ['votes_count', 'avg_score'])) {
		$query .= ' GROUP BY bugdb.id';

		switch ($order_by) {
			case 'avg_score':
				$order_by_clauses = [
					"IFNULL(AVG(v.score), 0)+3 $direction",
					"COUNT(v.bug) DESC"
				];
				break;
			case 'votes_count':
				$order_by_clauses = ["COUNT(v.bug) $direction"];
				break;
		}
	} elseif ($order_by != '') {
		$order_by_clauses = ["$order_by $direction"];
	}

	if ($status == 'Feedback') {
		$order_by_clauses[] = "bugdb.ts2 $direction";
	}

	if (count($order_by_clauses)) {
		$query .= ' ORDER BY ' . implode(', ', $order_by_clauses);
	}

	if ($limit != 'All' && $limit > 0) {
		$query .= " LIMIT $begin, $limit";
	}

	if (stristr($query, ';')) {
		$errors[] = 'BAD HACKER!! No database cracking for you today!';
	} else {
		try {
			$result = $dbh->prepare($query)->execute()->fetchAll();
			$rows = count($result);
			$total_rows = $dbh->prepare('SELECT FOUND_ROWS()')->execute()->fetch(\PDO::FETCH_NUM)[0];
		} catch (Exception $e) {
			$errors[] = 'Invalid query: ' . $e->getMessage();
		}
		if (defined('MAX_BUGS_RETURN') && $total_rows > $rows) {
			$warnings[] = 'The search was too general, only ' . MAX_BUGS_RETURN . ' bugs will be returned';
		}
	}
}
