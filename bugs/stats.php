<?php

/**
 * Produce statistical reports about bugs
 *
 * This source file is subject to version 3.0 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 *
 * @category  pearweb
 * @package   Bugs
 * @copyright Copyright (c) 1997-2005 The PHP Group
 * @license   http://www.php.net/license/3_0.txt  PHP License
 * @version   $Id$
 */

/**
 * Obtain common includes
 */
require_once './include/prepend.inc';

response_header('Bugs Stats');

$dbh->setFetchMode(MDB2_FETCHMODE_ASSOC);

$titles = array(
    'Closed'      => 'Closed',
    'Open'        => 'Open',
    'Critical'    => 'Crit',
    'Verified'    => 'Verified',
    'Analyzed'    => 'Analyzed',
    'Assigned'    => 'Assigned',
    'Feedback'    => 'Fdbk',
    'No Feedback' => 'No&nbsp;Fdbk',
    'Suspended'   => 'Susp',
    'Bogus'       => 'Bogus',
    'Duplicate'   => 'Dupe',
    'Wont fix'    => 'Wont&nbsp;Fix',
);

$category  = isset($_GET['category']) ? $_GET['category'] : '';
$developer = isset($_GET['developer']) ? $_GET['developer'] : '';
$rev       = isset($_GET['rev']) ? $_GET['rev'] : 1;
$sort_by   = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'Open';
$total     = 0;
$row       = array();
$pkg       = array();
$pkg_tmp   = array();
$pkg_total = array();
$pkg_names = array();
$all       = array();
$pseudo    = true;
$pseudo_pkgs = get_pseudo_packages($site);

if (!array_key_exists($sort_by, $titles)) {
    $sort_by = 'Open';
}

// Fetch all packages of the user if someone is logged in
if ($auth_user) {
    include_once 'pear-database-user.php';
    $pck = user::getPackages($auth_user->handle);
    $packages = array();
    foreach ($pck as $p) {
        $packages[] = $p['name'];
    }
}

$query  = 'SELECT b.package_name, b.status, COUNT(*) AS quant'
        . ' FROM bugdb AS b';

$from = '';
if ($site != 'php') {
	$from = ' LEFT JOIN packages AS p ON p.name = b.package_name';
	if ($category) {
	    $pseudo = false;
	    $from .= ' JOIN categories AS c ON c.id = p.category';
	    $from .= ' AND c.name = ' .  $dbh->quoteSmart($category);
	}
	if ($developer) {
	    $pseudo = false;
	    $from .= ' JOIN maintains AS m ON m.package = p.id';
	    $from .= ' AND m.handle = ' .  $dbh->quoteSmart($developer);
	}
}
switch ($site) {
    case 'pecl':
        $where = " WHERE p.package_type = 'pecl'";
        break;

    case 'pear':
        if ($pseudo) {
            $where = " WHERE (p.package_type = 'pear'"
                   . " OR b.package_name IN ('"
                   . implode("', '", $pseudo_pkgs) . "'))";
        } else {
            $where = " WHERE p.package_type = 'pear'";
        }
        break;
    default:
        $where = ' WHERE 1=1';
}

if ($developer) {
    $where .= ' AND m.active = 1';
}

if (empty($_GET['bug_type']) || $_GET['bug_type'] == 'All') {
    $bug_type = 'All';
} else {
    $bug_type = $_GET['bug_type'];
    $where .= ' AND bug_type = ' . $dbh->quoteSmart($bug_type);
}

$query .= $from . $where;
$query .= ' GROUP BY b.package_name, b.status';
$query .= ' ORDER BY b.package_name, b.status';

$result =& $dbh->query($query);

while ($result->fetchInto($row)) {
    $pkg_tmp[$row['status']][$row['package_name']]  = $row['quant'];
    @$pkg_total[$row['package_name']]               += $row['quant'];
    @$all[$row['status']]                           += $row['quant'];
    @$total                                         += $row['quant'];
    $pkg_names[$row['package_name']]                = 0;
}

if (count($pkg_tmp)) {
	foreach ($titles as $key => $val) {
	    if (isset($pkg_tmp[$key]) && is_array($pkg_tmp[$key])) {
	        $pkg[$key] = array_merge($pkg_names, $pkg_tmp[$key]);
	    } else {
	        $pkg[$key] = $pkg_names;
	    }
	}
}

if ($total > 0) {
    if ($rev == 1) {
        arsort($pkg[$sort_by]);
    } else {
        asort($pkg[$sort_by]);
    }
}


/*
 * Fetch list of all categories
 */
$res = array();
if ($site != 'php') {
	include_once 'pear-database-category.php';
	$res = category::listAll();

	// DB error
	if (is_object($res)) {
	    response_header('DB error');
	    display_bug_error($res);
	    response_footer();
	    exit;
	}
}
?>

<table>
 <tr>
  <td style="white-space: nowrap">
   <form method="get" action="stats.php">
   <strong>
    <label for="category" accesskey="o">
     Categ<span class="accesskey">o</span>ry:
    </label>
   </strong>
   <select class="small" name="category" id="category"
           onchange="this.form.submit(); return false;">
    <option value=""
<?php

if (!$category) {
    echo ' selected="selected"';
}
echo '>All</option>' . "\n";

foreach ($res as $row) {
    echo '    <option value="' . $row['name'] . '"';
    if ($category == $row['name']) {
        echo ' selected="selected"';
    }
    echo '>' . $row['name'] . '</option>' . "\n";
}

?>

   </select>

   <strong>Developer:</strong>
   <select class="small" name="developer" id="developers"
           onchange="this.form.submit(); return false;">
    <option value=""
<?php

/*
 * Fetch list of developers
 */
if ($site != 'php') {
	$users =& $dbh->query('SELECT u.handle AS handle, u.name AS name'
                      . ' FROM users u, maintains m'
                      . ' WHERE u.handle = m.handle'
                      . ' GROUP BY handle ORDER BY u.name');

	if (!$developer) {
	    echo ' selected="selected"';
	    $developer = '';
	}
}
echo '>All</option>' . "\n";

if ($site != 'php') {
	foreach ($users->fetchAll(MDB2_FETCHMODE_ASSOC) as $u) {
	    echo '    <option value="' . $u['handle'] . '"';
	    if ($developer == $u['handle']) {
	        echo ' selected="selected"';
	    }
	    echo '>' . $u['name'] . '</option>' . "\n";
	}
}
?>

   </select>

   <strong>Bug Type:</strong>
   <select class="small" id="bug_type" name="bug_type" onchange="this.form.submit(); return false;">

   <?php show_type_options($bug_type, true) ?>

   </select>

   <input class="small" type="submit" name="submitStats" value="Search" />
   </form>
  </td>
 </tr>
</table>

<table style="width: 100%; margin-top: 1em;">

<?php

/*
 * Display results
 */
// Exit if there are no bugs for this version
if ($total == 0) {
    echo '<tr><td>No bugs found</td></tr></table>' . "\n";
    response_footer();
    exit;
}

echo display_stat_header($total, true);

echo " <tr>\n";
echo '  <td class="bug_head">All' . "</td>\n";
echo '  <td class="bug_bg0">' . $total . "</td>\n";

$i = 1;
foreach ($titles as $key => $val) {
    echo '  <td class="bug_bg' . $i++ % 2 . '">';
    echo bugstats($key, 'all') . "</td>\n";
}
echo ' </tr>' . "\n";

$stat_row = 1;
foreach ($pkg[$sort_by] as $name => $value) {
    // Check if the logged in user maintains this package
    if (isset($packages) && in_array($name, $packages)) {
        $class = ' bug_bg_selected';
    } else {
        $class = '';
    }

    if ($name != 'all') {
        /* Output a new header row every 40 lines */
        if (($stat_row++ % 40) == 0) {
            echo display_stat_header($total, false);
        }
        echo " <tr>\n";
        echo '  <td class="bug_head'.$class.'">' . package_link($name) . "</td>\n";
        echo '  <td class="bug_bg0'.$class.'">' . $pkg_total[$name];
        echo "</td>\n";

        $i = 1;
        foreach ($titles as $key => $val) {
            echo '  <td class="bug_bg' . $i++ % 2 . ''.$class.'">';
            echo bugstats($key, $name) . "</td>\n";
        }
        echo ' </tr>' . "\n";
    }
}

echo "</table>\n";
echo '<a name="devs">&nbsp;</a>';
echo "<h1>Most Active Bug-fixing Developers</h1>";
echo "<p>The following is some informational statistics on bug fixing and reporting.
Developers are considered to have fixed a bug if the bug is marked <strong>Closed</strong> and is assigned to the developer.</p>";
echo '<table>';
echo '<tr><th>All Time</th><th>Last 30 Days</th><th>Bug Reporting</th></tr>';
echo '<tr><td valign="top">';
require_once 'include/classes/bug_stats.php';
$bugs = new Bug_Stats($site);
$develstats = $bugs->allDevelStats();
$lastmonth = $bugs->lastMonthStats();
$reporters = $bugs->reporterStats();
echo '<table>'; ?>
 <tr>
  <th class="bug_header">Closed Bugs</th>
  <th class="bug_header">Developer</th>
 </tr>
<?php
$handle = isset($auth_user->handle) ? $auth_user->handle : '';

foreach ($develstats as $stat) {
    $style = $stat['handle'] == $handle ? ' style="background-color: yellow;"' : '';
    echo " <tr>\n";
    echo '  <td class="bug_bg0"'.$style.'>' . $stat['c'] . "</td>\n";
    echo '  <td class="bug_bg0"'.$style.'><a href="/user/' . $stat['handle'] . '">' .
        $stat['handle'] . "</a></td>\n";
    echo " </tr>\n";
}
echo "</table>\n";

echo '</td><td valign="top">';
echo '<table>'; ?>
 <tr>
  <th class="bug_header">Closed Bugs</th>
  <th class="bug_header">Developer</th>
 </tr>
<?php
foreach ($lastmonth as $stat) {
    $style = $stat['handle'] == $handle ? ' style="background-color: yellow;"' : '';
    echo " <tr>\n";
    echo '  <td class="bug_bg0"'.$style.'>' . $stat['c'] . "</td>\n";
    echo '  <td class="bug_bg0"'.$style.'><a href="/user/' . $stat['handle'] . '">' .
        $stat['handle'] . "</a></td>\n";
    echo " </tr>\n";
}
echo "</table>\n";
echo '</td><td valign="top">';
echo '<table>'; ?>
 <tr>
  <th class="bug_header">Bugs + Comments + Patches</th>
  <th class="bug_header">Developer</th>
 </tr>
<?php
foreach ($reporters as $dev => $stat) {
    $style = $dev == $handle ? ' style="background-color: yellow;"' : '';
    echo " <tr>\n";
    echo '  <td class="bug_bg0"'.$style.'>' . $stat . "</td>\n";
    echo '  <td class="bug_bg0"'.$style.'><a href="/user/' . $dev . '">' .
        $dev . "</a></td>\n";
    echo " </tr>\n";
}
echo "</table>\n";
echo '</td>';
echo '</tr>';
echo '</table>';

response_footer();



/*
 * DECLARE FUNCTIONS ===================================
 */

function bugstats($status, $name)
{
    global $pkg, $all, $bug_type;

    if ($name == 'all') {
        if (isset($all[$status])) {
            return '<a href="search.php?cmd=display&amp;' .
                   'bug_type='.$bug_type.'&amp;status=' .$status .
                   '&amp;by=Any&amp;limit=30">' .
                   $all[$status] . "</a>\n";
        }
    } else {
        if (empty($pkg[$status][$name])) {
            return '&nbsp;';
        } else {
            return '<a href="search.php?cmd=display&amp;'.
                   'bug_type='.$bug_type.'&amp;status=' .
                   $status .
                   '&amp;package_name%5B%5D=' . urlencode($name) .
                   '&amp;by=Any&amp;limit=30">' .
                   $pkg[$status][$name] . "</a>\n";
        }
    }
}

function sort_url($name)
{
    global $sort_by, $rev, $phpver, $category, $developer, $titles;

    if ($name == $sort_by) {
        $reve = ($rev == 1) ? 0 : 1;
    } else {
        $reve = 1;
    }
    if ($sort_by != $name) {
        $attr = 'class="bug_stats"';
    } else {
        $attr = 'class="bug_stats_choosen"';
    }
    return '<a href="./stats.php?sort_by=' . urlencode($name) .
           '&amp;rev=' . $reve . '&amp;category=' . $category .
           '&amp;developer=' . $developer . '" ' . $attr . '>' .
           $titles[$name] . '</a>';
}

function package_link($name)
{
    global $pseudo_pkgs;

    if (!isset($pseudo_pkgs[$name])) {
        return '<a href="/package/' . $name . '" class="bug_stats">' .
               $name . '</a>';
    } else {
        return $name;
    }
}

function display_stat_header($total, $grandtotal = true)
{
    global $titles;

    $stat_head  = " <tr>\n";
    if ($grandtotal) {
        $stat_head .= '  <th class="bug_header">Name</th>' . "\n";
    } else {
        $stat_head .= '  <th class="bug_header">&nbsp;</th>' . "\n";
    }
    $stat_head .= '  <th class="bug_header">&nbsp;</th>' . "\n";

    foreach ($titles as $key => $val) {
        $stat_head .= '  <th class="bug_header">' . sort_url($key) . "</th>\n";
    }

    $stat_head .= '</tr>' . "\n";
    return $stat_head;
}

