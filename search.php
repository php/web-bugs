<?php /* vim: set noet ts=4 sw=4: : */
require_once 'prepend.inc';

if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
  list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
}

if ($search_for && !preg_match("/\\D/",trim($search_for))) {
	$x = $pw ? ($user ? "&edit=1" : "&edit=2") : "";
	header("Location: bug.php?id=$search_for$x");
	exit;
}

commonHeader("Search");

$errors = array();

if (isset($cmd) && $cmd == "display") {
	@mysql_connect("localhost","nobody","")
		or die("Unable to connect to SQL server.");
	@mysql_select_db("php3");

	if (!$bug_type) $bug_type = "Any";

    $query  = "SELECT *,TO_DAYS(NOW())-TO_DAYS(ts2) AS unchanged FROM bugdb ";

	if($bug_type=="Any") {
		$where_clause = "WHERE bug_type != 'Feature/Change Request'";
	} else {
		$where_clause = "WHERE bug_type = '$bug_type'";
	}

	/* Treat assigned, analyzed and critical bugs as open */
	if ($status == "Open") {
		$where_clause .= " AND (status='Open' OR status='Assigned' OR status='Analyzed' OR status='Critical')";
	} elseif ($status == "Old Feedback") {
		$where_clause .= " AND status='Feedback' AND TO_DAYS(NOW())-TO_DAYS(ts2)>60";
	} elseif ($status == "Fresh") {
		$where_clause .= " AND status != 'Closed' AND status != 'Duplicate' AND status != 'Bogus' AND TO_DAYS(NOW())-TO_DAYS(ts2) < 30";
	} elseif ($status == "Stale") {
		$where_clause .= " AND status != 'Closed' AND status != 'Duplicate' AND status != 'Bogus' AND TO_DAYS(NOW())-TO_DAYS(ts2) > 30";
	} elseif ($status && $status != "All") {
		$where_clause .= " AND status='$status'";
	}

	if (strlen($search_for)) {
		$where_clause .= " AND MATCH (email,sdesc,ldesc) AGAINST ('$search_for')";
	}

	if ($bug_age) {
		$where_clause .= " AND ts1 >= DATE_SUB(NOW(), INTERVAL $bug_age DAY)";
	}

	if ($php_os) {
		$where_clause .= " AND php_os like '%$php_os%'";
	}

	if (empty($phpver)) $phpver = "4";
	if ($phpver) {
		// there's an index on php_version(1) to speed this up.
		if (strlen($phpver) == 1) {
			$where_clause .= " AND SUBSTRING(php_version,1,1) = '$phpver'";
		}
		else {
			$where_clause .= " AND php_version LIKE '$phpver%'";
		}
	}

	if (!empty($assign)) {
	    $where_clause .= " AND assign = '$assign'";
	}

    $query .= "$where_clause ";

	/* we avoid adding an order by clause if using the full text search */
    if ($order_by || $reorder_by || !strlen($search_for)) {
		if (!$order_by) $order_by = "id";
		if (!$direction) $direction = "ASC";

		if ($reorder_by) {
			if ($order_by == $reorder_by) {
				$direction = $direction == "ASC" ? "DESC" : "ASC";
			}
			else {
				$direction = "ASC";
			}
			$order_by = $reorder_by;
		}
		$query .= " ORDER BY $order_by $direction";
    }

	if (!$begin) $begin = 0;
	if (!isset($limit)) $limit = 30;

	if($limit!='All') $query .= " LIMIT $begin,$limit";

    $res = @mysql_query("SELECT COUNT(*) FROM bugdb $where_clause");
	if (!$res) die(htmlspecialchars($query)."<br>".mysql_error());
    $row = mysql_fetch_row($res);

    $total_rows = $row[0];
	if (!$total_rows) {
		$errors[] = "No bugs with the specified criteria were found.";
	}
	else {
		$res = @mysql_query($query);
		if (!$res) die(htmlspecialchars($query)."<br>".mysql_error());
	    $rows = mysql_numrows($res);

		$link = "$PHP_SELF?cmd=display&amp;bug_type=" . urlencode ($bug_type) . "&amp;status=$status&amp;search_for=".urlencode(htmlspecialchars(stripslashes($search_for)))."&amp;php_os=".htmlspecialchars(stripslashes($php_os))."&amp;bug_age=$bug_age&amp;by=$by&amp;order_by=$order_by&amp;direction=$direction&amp;phpver=$phpver&amp;limit=$limit&amp;assign=$assign";
?>
<table align="center" border="0" cellspacing="2" width="95%">
 <?php show_prev_next($begin,$rows,$total_rows,$link,$limit);?>
 <tr bgcolor="#aaaaaa">
  <th><a href="<?php echo $link;?>&amp;reorder_by=id">ID#</a></th>
<?php if ($bug_type == "Any") {?>
  <th><a href="<?php echo $link;?>&amp;reorder_by=bug_type">Type</a></th>
<?php }?>
  <th><a href="<?php echo $link;?>&amp;reorder_by=status">Status</a></th>
  <th><a href="<?php echo $link;?>&amp;reorder_by=php_version">Version</a></th>
  <th><a href="<?php echo $link;?>&amp;reorder_by=php_os">OS</a></th>
  <th><a href="<?php echo $link;?>&amp;reorder_by=sdesc">Summary</a></th>
  <th><a href="<?php echo $link;?>&amp;reorder_by=assign">Assigned</a></th>
 </tr>
<?php
		while ($row = mysql_fetch_array($res)) {
			echo '<tr bgcolor="', get_row_color($row), '">';
			echo "<td align=\"center\"><a href=\"bug.php?id=$row[id]\">$row[id]</a>";
			echo "<br /><a href=\"bug.php?id=$row[id]&amp;edit=1\">(edit)</a></td>";
			if ($bug_type == "Any") {
				echo "<td>", htmlspecialchars($row[bug_type]), "</td>";
			}
			echo "<td>", htmlspecialchars($row[status]);
			if ($row[status] == "Feedback" && $row[unchanged] > 0) {
				printf ("<br>%d day%s", $row[unchanged], $row[unchanged] > 1 ? "s" : "");
			}
			echo "</td>";
			echo "<td>", htmlspecialchars($row[php_version]), "</td>";
			echo "<td>", $row[php_os] ? htmlspecialchars($row[php_os]) : "&nbsp;", "</td>";
			echo "<td>", $row[sdesc]  ? htmlspecialchars($row[sdesc]) : "&nbsp;",  "</td>";
			echo "<td>", $row[assign] ? htmlspecialchars($row[assign]) : "&nbsp;", "</td>";
			echo "</tr>\n";
		}

		show_prev_next($begin,$rows,$total_rows,$link,$limit);
?>
</table>
<?php
		commonFooter();
		exit;
	}
}

if ($errors) display_errors($errors);
?>
<form id="asearch" method="post" action="<?php echo $PHP_SELF?>">
<table id="primary" width="95%">
 <tr>
  <th>Find bugs</th>
  <td nowrap="nowrap">with <b>any</b> of the words</td>
  <td><input type="text" name="search_for" value="<?echo htmlspecialchars($search_for);?>" size="20" maxlength="255" /></td>
  <td rowspan="2">
   <select name="limit"><?php show_limit_options($limit);?></select>
   <br />
   <select name="order_by"><?php show_order_options($limit);?></select>
   <br />
   <input type="hidden" name="cmd" value="display" />
   <input type="submit" value="Search" />
  </td>
 </tr>
 <tr>
  <th>Status</th>
  <td nowrap="nowrap">Return only bugs with <b>status</b></td>
  <td><select name="status"><?php show_state_options($status);?></select></td>
 </tr>
</table>
<table>
 <tr>
  <th>Category</th>
  <td nowrap="nowrap">Return only bugs in <b>category</b></td>
  <td><select name="bug_type"><?php show_types($bug_type,1);?></select></td>
 </tr>
 <tr>
  <th>OS</th>
  <td nowrap="nowrap">Return bugs with <b>operating system</b></td>
  <td><input type="text" name="php_os" value="<?echo htmlspecialchars($php_os);?>" /></td>
 </tr>
 <tr>
  <th>Version</th>
  <td nowrap="nowrap">Return bugs reported with <b>PHP version</b></td>
  <td><input type="text" name="phpver" value="<?echo htmlspecialchars($phpver);?>" /></td>
 </tr>
 <tr>
  <th>Assigned</th>
  <td nowrap="nowrap">Return only bugs <b>assigned</b> to</td>
  <td><input type="text" name="assign" value="<?echo htmlspecialchars($assign);?>" />
<?php
    if (!empty($user)) {
	$u = stripslashes($user);
        print "<input type=\"button\" value=\"set to $u\" onclick=\"form.assign.value='$u'\" />";
    }
?>
  </td>
 </tr>
 <tr>
  <th>Date</th>
  <td nowrap="nowrap">Return bugs submitted</td>
  <td><select name="bug_age"><?php show_byage_options($bug_age);?></select></td>
 </tr>
</table>
</form>

<?php
commonFooter();

function show_prev_next($begin,$rows,$total_rows,$link,$limit) {
	if($limit=='All') return;
	echo "<tr bgcolor=\"#cccccc\"><td align=\"center\" colspan=\"7\">";
    echo '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr>';
	if ($begin > 0) {
		echo "<td align=\"left\" width=\"33%\"><a href=\"$link&amp;begin=",max(0,$begin-$limit),"\">&laquo; Show Previous $limit Entries</a></td>";
	}
    else {
        echo "<td width=\"33%\">&nbsp;</td>";
    }
    echo "<td align=\"center\" width=\"34%\">Showing ",$begin+1,"-", $begin+$rows, " of $total_rows</td>";
	if ($begin+$rows < $total_rows) {
		echo "<td align=\"right\" width=\"33%\"><a href=\"$link&amp;begin=",$begin+$limit,"\">Show Next $limit Entries &raquo;</a></td>";
	}
    else {
        echo "<td width=\"33%\">&nbsp;</td>";
    }
	echo "</tr></table></td></tr>";
}

function show_order_options ($current) {
	$opts = array(
		'' => "relevance",
		'id' => "ID",
		'bug_type' => "type",
		'status' => "status",
		'php_version' => "version",
		'php_os' => "os",
		'sdesc' => "summary",
		'assign' => "assignment",
	);
	foreach ($opts as $k => $v) {
		echo '<option value="', $k, '"',
		     ($v == $current ? ' selected="selected"' : ''),
		     '>Sort by ', $v, "</option>\n";
	}
}
