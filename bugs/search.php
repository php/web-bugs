<?php /* vim: set noet ts=4 sw=4: : */

/**
 * Search for bugs
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

/* Redirect early if a bug id is passed as search string */
$search_for_id = (isset($_GET['search_for'])) ? (int) $_GET['search_for'] : 0;
if ($search_for_id)
{
    localRedirect("bug.php?id={$search_for_id}");
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $is_trusted_developer);

$newrequest = $_REQUEST;
if (isset($newrequest['PEAR_USER'])) {
    unset($newrequest['PEAR_USER']);
}
if (isset($newrequest['PEAR_PW'])) {
    unset($newrequest['PEAR_PW']);
}
if (isset($newrequest['PHPSESSID'])) {
    unset($newrequest['PHPSESSID']);
}
response_header(
	'Bugs :: Search',
	" <link rel='alternate'
			type='application/rdf+xml'
			title='RSS feed' href='http://{$site_url}{$basedir}/rss/search.php?" . http_build_query($newrequest) . "' />");

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

// Fetch pseudo packages
$pseudo_pkgs = get_pseudo_packages($site);

// Setup input variables..
$boolean_search = isset($_GET['boolean']) ? (int) $_GET['boolean'] : 0;
$status = !empty($_GET['status']) ? $_GET['status'] : 'Open';
$search_for = !empty($_GET['search_for']) ? $_GET['search_for'] : '';
$bug_type = (!empty($_GET['bug_type']) && $_GET['bug_type'] != 'All') ? $_GET['bug_type'] : '';
$bug_age = (int) (isset($_GET['bug_age'])) ? $_GET['bug_age'] : 0;
$bug_updated = (int) (isset($_GET['bug_updated'])) ? $_GET['bug_updated'] : 0;
$php_os = !empty($_GET['php_os']) ? $_GET['php_os'] : '';
$phpver = !empty($_GET['phpver']) ? $_GET['phpver'] : '';
$packagever = !empty($_GET['packagever']) ? $_GET['packagever'] : '';
$begin = (int) !empty($_GET['begin']) ? $_GET['begin'] : 0;
$limit = 30;
if (!empty($_GET['limit'])) {
    $limit = ($_GET['limit'] == 'All') ? 'All' : (($_GET['limit'] > 0) ? (int) $_GET['limit'] : $limit);
}
$direction = (!empty($_GET['direction']) && $_GET['direction'] != 'DESC') ? 'ASC' : 'DESC';
$order_by   = (!empty($_GET['order_by'])   && array_key_exists($_GET['order_by'],   $order_options)) ? $_GET['order_by']   : 'id';
$reorder_by = (!empty($_GET['reorder_by']) && array_key_exists($_GET['reorder_by'], $order_options)) ? $_GET['reorder_by'] : '';
$handle = !empty($_GET['handle']) ? $_GET['handle'] : '';
$assign = !empty($_GET['assign']) ? $_GET['assign'] : '';
$maintain = !empty($_GET['maintain']) ? $_GET['maintain'] : '';
$author_email = (!empty($_GET['author_email']) && is_valid_email($_GET['author_email'])) ? $_GET['author_email'] : '';
$package_name  = (isset($_GET['package_name'])  && is_array($_GET['package_name']))  ? $_GET['package_name']  : array();
$package_nname = (isset($_GET['package_nname']) && is_array($_GET['package_nname'])) ? $_GET['package_nname'] : array();

    
if (isset($_GET['cmd']) && $_GET['cmd'] == 'display')
{
    $query = '
    	SELECT SQL_CALC_FOUND_ROWS bugdb.*, TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) AS unchanged
        FROM bugdb
	';

    if ($maintain != '' || $handle != '') {
        $query .= '
        	LEFT JOIN packages ON packages.name = bugdb.package_name
		';
        if ($maintain != '' || $handle != '') {
            $query .= '
            	LEFT JOIN maintains ON packages.id = maintains.package
                	AND maintains.handle = '. ($maintain != '') ? $dbh->quote($maintain) : $dbh->quote($handle);
        }
        $query .= ' AND maintains.active = 1';
    }

    // Show un-registered bugs to developers
    $where_clause = ' WHERE ' . (($site == 'php' || $logged_in == 'developer') ? '1 = 1' : 'bugdb.registered = 1');

    if (!empty($package_name)) {
        $where_clause .= ' AND bugdb.package_name';
        if (count($package_name) > 1) {
            $where_clause .= " IN ('"
                           . join("', '", escapeSQL($package_name))
                           . "')";
        } else {
            $where_clause .= ' = ' . $dbh->quote($package_name[0]);
        }
    }

    if (!empty($package_nname)) {
        $where_clause .= ' AND bugdb.package_name';
        if (count($package_nname) > 1) {
            $where_clause .= " NOT IN ('"
                           . join("', '", escapeSQL($package_nname))
                           . "')";
        } else {
            $where_clause .= ' <> '
                           . $dbh->quote($package_nname[0]);
        }
    }

    /*
     * Ensure status is valid and tweak search clause
     * to treat assigned, analyzed, critical and verified bugs as open
     */
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
            $where_clause .= " AND bugdb.status='Feedback'
                               AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 60";
            break;
        case 'Fresh':
            $where_clause .= " AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Bogus')
                               AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) < 30";
            break;
        case 'Stale':
            $where_clause .= " AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Bogus')
                               AND TO_DAYS(NOW())-TO_DAYS(bugdb.ts2) > 30";
            break;
        case 'Not Assigned':
            $where_clause .= " AND bugdb.status NOT IN ('Closed', 'Duplicate', 'Bogus', 'Assigned', 'Wont Fix', 'Suspended')";
            break;
        case 'CRSLR': // Closed Reports Since Last Release
            if (empty($package_name) || count($package_name) > 1) {
                // Act as ALL
                break;
            }

            // Fetch the last release date
            include_once 'pear-database-package.php';
            $releaseDate = package::getRecent(1, $package_name[0]);
            if (PEAR::isError($releaseDate)) {
                break;
            }

            $where_clause .= " AND bugdb.status IN ('Closed', 'Duplicate', 'Bogus', 'Wont Fix', 'Suspended')
                               AND (UNIX_TIMESTAMP('{$releaseDate[0]['releasedate']}') < UNIX_TIMESTAMP(bugdb.ts2))
                             ";
            break;
        case 'OpenFeedback':
            $where_clause .= " AND bugdb.status IN ('Open', 'Assigned','Analyzed', 'Critical', 'Verified', 'Feedback')";
            break;
        default:
        case 'Open':
            $where_clause .= " AND bugdb.status IN ('Open', 'Assigned', 'Analyzed', 'Critical', 'Verified')";
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
        $where_clause .= " AND bugdb.php_os LIKE '%" . $dbh->escapeSimple($php_os) . "%'";
    }

    if ($phpver != '') {
        $where_clause .= " AND bugdb.php_version LIKE '" . $dbh->escapeSimple($phpver) . "%'";
    }

    if ($packagever != '') {
        $where_clause .= " AND bugdb.package_version LIKE '" . $dbh->escapeSimple($packagever) . "%'";
    }

    if ($handle == '') {
        if ($assign != '') {
            $where_clause .= ' AND bugdb.assign = ' . $dbh->quote($assign);
        }
        if ($maintain != '') {
            $where_clause .= ' AND maintains.handle = ' . $dbh->quote($maintain);
        }
    } else {
        $where_clause .= ' AND (maintains.handle = ' . $dbh->quote($handle)
                       . ' OR bugdb.assign = ' . $dbh->quote($handle). ')';
    }

	if ($author_email != '') {
        $qae = $dbh->quote($author_email);
        $where_clause .= " AND (bugdb.email = $qae OR bugdb.handle = $qae)";
    }

    $where_clause .= ($site != 'php') ? ' AND (packages.package_type = ' . $dbh->quote($site) : ' AND (1=1';

    if ($pseudo = array_intersect(array_keys($pseudo_pkgs), $package_name)) {
        $where_clause .= " OR bugdb.package_name";
        if (count($pseudo) > 1) {
            $where_clause .= " IN ('"
                           . join("', '", escapeSQL($pseudo)) . "')";
        } else {
            $where_clause .= " = '" . implode('', escapeSQL($pseudo)) . "'";
        }
    } else {
        $where_clause .= " OR bugdb.package_name IN ('"
                       . join("', '", escapeSQL(array_keys($pseudo_pkgs))) . "')";
    }

    $query .= "$where_clause )";

    if ($reorder_by != '') {
        if ($order_by == $reorder_by) {
            $direction = $direction == 'ASC' ? 'DESC' : 'ASC';
        } else {
            $direction = 'ASC';
            $order_by = $reorder_by;
        }
    }

    $query .= " ORDER BY $order_by $direction";

    // if status Feedback then sort also after last updated time.
    if ($status == 'Feedback') {
        $query .= ", bugdb.ts2 $direction";
    }

    if ($limit != 'All' && $limit > 0) {
        $query .= " LIMIT $begin, $limit";
    }

    if (stristr($query, ';')) {
        $errors[] = 'BAD HACKER!! No database cracking for you today!';
    } else {
        $res = $dbh->query($query);
        $rows = $res->numRows();

        $total_rows =& $dbh->queryOne('SELECT FOUND_ROWS()');

        /* Selected packages to search in */
        $package_name_string = '';
        if (count($package_name) > 0) {
            foreach ($package_name as $type_str) {
                $package_name_string.= '&amp;package_name[]=' . urlencode($type_str);
            }
        }
        
        /* Selected packages NOT to search in */
        $package_nname_string = '';
        if (count($package_nname) > 0) {
            foreach ($package_nname as $type_str) {
                $package_nname_string.= '&amp;package_nname[]=' . urlencode($type_str);
            }
        }

        $link = "search.php?cmd=display{$package_name_string}{$package_nname_string}".
                '&amp;search_for='  . urlencode($search_for) .
                '&amp;php_os='      . urlencode($php_os) .
                '&amp;author_email='. urlencode($author_email) .
                '&amp;bug_type='    . urlencode($bug_type) .
                "&amp;boolean=$boolean_search" .
                "&amp;bug_age=$bug_age" .
                "&amp;bug_updated=$bug_updated" .
                "&amp;order_by=$order_by" .
                "&amp;direction=$direction" .
                "&amp;limit=$limit" .
                '&amp;packagever='  . urlencode($packagever) .
                '&amp;phpver='      . urlencode($phpver) .
                '&amp;handle='      . urlencode($handle) .
                '&amp;assign='      . urlencode($assign) .
                '&amp;maintain='    . urlencode($maintain);

        if (isset($_GET['showmenu'])) {
            $link .= '&amp;showmenu=1';
        }

        if (!$rows) {
            if (isset($_GET['showmenu'])) {
                show_bugs_menu($package_name, $status, $link);
            } else {
                show_bugs_menu($package_name, $status);
            }
            $errors[] = 'No bugs were found.';
            display_bug_error($errors, 'warnings', '');
        } else {
            display_bug_error($warnings, 'warnings', 'WARNING:');
            if (isset($_GET['showmenu'])) {
                show_bugs_menu($package_name, $status, $link);
            } else {
                show_bugs_menu($package_name, $status);
            }

            $link .= '&amp;status=' . urlencode($status);
            $package_count = count($package_name);
?>

<table border="0" cellspacing="2" width="100%">

<?php show_prev_next($begin, $rows, $total_rows, $link, $limit);?>

<?php if ($package_count === 1) { ?>
 <tr>
  <td class="search-prev_next" style="text-align: center;" colspan="9">
<?php
   $pck = htmlspecialchars($package_name[0]);
   echo " Bugs for <a href='/package/{$pck}'>{$pck}</a>\n";
?>
  </td>
 </tr>
<?php } ?>

 <tr>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=id">ID#</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=ts1">Date</a></th>
<?php if ($package_count !== 1) { ?>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=package_name">Package</a></th>
<?php } ?>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=bug_type">Type</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=status">Status</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=package_version">Package Version</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=php_version">PHP Version</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=php_os">OS</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=sdesc">Summary</a></th>
  <th class="results"><a href="<?php echo $link;?>&amp;reorder_by=assign">Assigned</a></th>
 </tr>
            <?php

            while ($row =& $res->fetchRow(MDB2_FETCHMODE_ASSOC)) {
                echo ' <tr valign="top" class="' . $tla[$row['status']] . '">' . "\n";

                /* Bug ID */
                echo '  <td align="center"><a href="bug.php?id='.$row['id'].'">'.$row['id'].'</a>';
                echo '<br /><a href="bug.php?id='.$row['id'].'&amp;edit=1">(edit)</a></td>' . "\n";

                /* Date */
                echo '  <td align="center">'.format_date(strtotime($row['ts1'])).'</td>' . "\n";

                if ($package_count !== 1) {
                    $pck = htmlspecialchars($row['package_name']);
                    echo "<td><a href='/package/{$pck}'>{$pck}</a></td>\n";
                }

                $type_idx = !empty($row['bug_type']) ? $row['bug_type'] : 'Bug';
                echo '  <td>', htmlspecialchars($bug_types[$type_idx]), '</td>' . "\n";
                echo '  <td>', htmlspecialchars($row['status']);
                if ($row['status'] == 'Feedback' && $row['unchanged'] > 0) {
                    printf ("<br />%d day%s", $row['unchanged'], $row['unchanged'] > 1 ? 's' : '');
                }
                echo '</td>' . "\n";
                echo '  <td>', htmlspecialchars($row['package_version']), '</td>';
                echo '  <td>', htmlspecialchars($row['php_version']), '</td>';
                echo '  <td>', $row['php_os'] ? htmlspecialchars($row['php_os']) : '&nbsp;', '</td>' . "\n";
                echo '  <td>', $row['sdesc']  ? htmlspecialchars($row['sdesc'])             : '&nbsp;', '</td>' . "\n";
                echo '  <td>', $row['assign'] ? htmlspecialchars($row['assign']) : '&nbsp;', '</td>' . "\n";
                echo " </tr>\n";
            }

            show_prev_next($begin, $rows, $total_rows, $link, $limit);

            echo "</table>\n\n";
        }

        response_footer();
        exit;
    }
}

display_bug_error($errors);
display_bug_error($warnings, 'warnings', 'WARNING:');

?>
<form id="asearch" method="get" action="search.php">
<table id="primary" width="100%">
<tr valign="top">
  <th>Find bugs</th>
  <td style="white-space: nowrap">with all or any of the w<span class="accesskey">o</span>rds</td>
  <td style="white-space: nowrap"><input type="text" name="search_for" value="<?php echo htmlspecialchars($search_for);?>" size="20" maxlength="255" accesskey="o" />
      <br /><small><?php show_boolean_options($boolean_search) ?>
      (<?php print_link('search-howto.php', '?', true);?>)</small>
  </td>
  <td rowspan="3">
   <select name="limit"><?php show_limit_options($limit);?></select>
   &nbsp;
   <select name="order_by"><?php show_order_options($limit);?></select>
   <br />
   <small>
    <input type="radio" name="direction" value="ASC" <?php if($direction != "DESC") { echo('checked="checked"'); }?>/>Ascending
    &nbsp;
    <input type="radio" name="direction" value="DESC" <?php if($direction == "DESC") { echo('checked="checked"'); }?>/>Descending
   </small>
   <br /><br />
   <input type="hidden" name="cmd" value="display" />
   <label for="submit" accesskey="r">Sea<span class="accesskey">r</span>ch:</label>
   <input id="submit" type="submit" value="Search" />
  </td>
</tr>
<tr valign="top">
  <th>Status</th>
  <td style="white-space: nowrap">
   <label for="status" accesskey="n">Retur<span class="accesskey">n</span> bugs
   with <b>status</b></label>
  </td>
  <td><select id="status" name="status"><?php show_state_options($status);?></select></td>
</tr>
<tr valign="top">
  <th>Type</th>
  <td style="white-space: nowrap">
   <label for="bug_type">Return bugs with <b>type</b></label>
  </td>
  <td><select id="bug_type" name="bug_type"><?php show_type_options($bug_type, true);?></select></td>
</tr>
</table>

<table style="font-size: 100%;">
<tr valign="top">
  <th><label for="category" accesskey="c">Pa<span class="accesskey">c</span>kage</label></th>
  <td style="white-space: nowrap">Return bugs for these <b>packages</b></td>
  <td><select id="category" name="package_name[]" multiple="multiple" size="6"><?php show_types($package_name, 2);?></select></td>
</tr>
<tr valign="top">
  <th>&nbsp;</th>
  <td style="white-space: nowrap">Return bugs <b>NOT</b> for these <b>packages</b></td>
  <td><select name="package_nname[]" multiple="multiple" size="6"><?php show_types($package_nname, 2);?></select></td>
</tr>
<tr valign="top">
  <th>OS</th>
  <td style="white-space: nowrap">Return bugs with <b>operating system</b></td>
  <td><input type="text" name="php_os" value="<?php echo htmlspecialchars($php_os);?>" /></td>
</tr>
<tr valign="top">
  <th>Version</th>
  <td style="white-space: nowrap">Return bugs reported with <b>Package version</b></td>
  <td><input type="text" name="packagever" value="<?php echo htmlspecialchars($packagever);?>" /></td>
</tr>
<tr valign="top">
  <th>PHP Version</th>
  <td style="white-space: nowrap">Return bugs reported with <b>PHP version</b></td>
  <td><input type="text" name="phpver" value="<?php echo htmlspecialchars($phpver);?>" /></td>
</tr>
<tr valign="top">
  <th>Assigned</th>
  <td style="white-space: nowrap">Return bugs <b>assigned</b> to</td>
  <td><input type="text" name="assign" value="<?php echo htmlspecialchars($assign);?>" />
<?php
    if (!empty($auth_user->handle)) {
        $u = htmlspecialchars($auth_user->handle);
        echo "<input type=\"button\" value=\"set to $u\" onclick=\"form.assign.value='$u'\" />";
    }
?>
  </td>
</tr>
  <tr valign="top">
  <th>Maintainer</th>
  <td nowrap="nowrap">Return only bugs in packages <b>maintained</b> by</td>
  <td><input type="text" name="maintain" value="<?php echo htmlspecialchars($maintain);?>" />
<?php
    if (!empty($auth_user->handle)) {
        $u = htmlspecialchars($auth_user->handle);
        echo "<input type=\"button\" value=\"set to $u\" onclick=\"form.maintain.value='$u'\" />";
    }
?>
  </td>
 </tr>
<tr valign="top">
  <th>Author e<span class="accesskey">m</span>ail</th>
  <td style="white-space: nowrap">Return bugs with author email/handle</td>
  <td><input accesskey="m" type="text" name="author_email" value="<?php echo htmlspecialchars($author_email); ?>" />
<?php
    if (!empty($auth_user->handle)) {
        $u = htmlspecialchars($auth_user->handle);
        echo "<input type=\"button\" value=\"set to $u\" onclick=\"form.author_email.value='$u'\" />";
    }
?>
  </td>
</tr>
<tr valign="top">
  <th>Date</th>
  <td style="white-space: nowrap">Return bugs submitted</td>
  <td><select name="bug_age"><?php show_byage_options($bug_age);?></select></td>
 </tr>
 <tr>
  <td>&nbsp;</td><td style="white-space: nowrap">Return bugs updated</td>
  <td><select name="bug_updated"><?php show_byage_options($bug_updated);?></select></td>
</tr>
</table>
</form>

<?php
response_footer();

function show_prev_next($begin, $rows, $total_rows, $link, $limit)
{
    echo "<!-- BEGIN PREV/NEXT -->\n";
    echo " <tr>\n";
    echo '  <td class="search-prev_next" colspan="10">' . "\n";

    if ($limit=='All') {
        echo "$total_rows Bugs</td></tr>\n";
        return;
    }

    echo '   <table border="0" cellspacing="0" cellpadding="0" width="100%">' . "\n";
    echo "    <tr>\n";
    echo '     <td class="class-prev">';
    if ($begin > 0) {
        echo '<a href="' . $link . '&amp;begin=';
        echo max(0, $begin - $limit);
        echo '">&laquo; Show Previous ' . $limit . ' Entries</a>';
    } else {
        echo '&nbsp;';
    }
    echo "</td>\n";

    echo '     <td class="search-showing">Showing ' . ($begin+1);
    echo '-' . ($begin+$rows) . ' of ' . $total_rows . "</td>\n";

    echo '     <td class="search-next">';
    if ($begin+$rows < $total_rows) {
        echo '<a href="' . $link . '&amp;begin=' . ($begin+$limit);
        echo '">Show Next ' . $limit . ' Entries &raquo;</a>';
    } else {
        echo '&nbsp;';
    }
    echo "</td>\n    </tr>\n   </table>\n  </td>\n </tr>\n";
    echo "<!-- END PREV/NEXT -->\n";
}

function show_order_options($current)
{
    global $order_options;
    foreach ($order_options as $k => $v) {
        echo '<option value="', $k, '"',
             ($v == $current ? ' selected="selected"' : ''),
             '>Sort by ', $v, "</option>\n";
    }
}
