<?php /* vim: set noet ts=4 sw=4: : */
require_once 'prepend.inc';

if ($search_for && !preg_match("/\\D/",trim($search_for))) {
	header("Location: bug.php?id=$search_for");
	exit;
}

commonHeader("Search", false);

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
	if ($phpver) $where_clause .= " AND SUBSTRING(php_version,1,1) = '$phpver'";
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
		echo "<h2 class=\"error\">No bugs with the specified criteria were found.</h2>";
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
  <th><a href="<?php echo $link;?>&amp;reorder_by=sdesc">Description</a></th>
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
	}
} else {?>
<table bgcolor="#ccccff" border="0" cellspacing="1">
 <form method="POST" action="<?php echo $PHP_SELF?>">
 <input type="hidden" name="cmd" value="display" />
  <tr>
   <td rowspan="5" valign="top"><input type="submit" value="Display" /></td>
   <td align="right">bugs&nbsp;with&nbsp;status:</td>
   <td><select name="status"><?php show_state_options($status);?></select></td>
   <td align="right">reported&nbsp;since:</td>
   <td><select name="bug_age"><?php show_byage_options($bug_age);?></select></td>
  </tr>
  <tr>
   <td align="right">of type:</td>
   <td colspan="3"><select name="bug_type"><?php show_types($bug_type,1);?></select></td>
  </tr>
  <tr>
   <td align="right">OS (substring):</td>
   <td><input type="text" name="php_os" value="<?echo htmlspecialchars($php_os);?>" /></td>
   <td align="right">assigned to:</td>
   <td><input size="10" type="text" name="assign" value="<?echo htmlspecialchars($assign);?>" />&nbsp;<?php
    if (!empty($user)) {
	$u = stripslashes($user);
        print "<input type=\"button\" value=\"set to $u\" onclick=\"form.assign.value='$u'\" />";
    }
?></td>
  </tr>
  <tr>
   <td align="right">with text:</td>
   <td colspan="3"><input type="text" name="search_for" value="<?echo htmlspecialchars($search_for);?>" /> in the report or email address</td>
  </tr>
  <tr>
   <td align="right">max:</td>
   <td colspan="3"><select name="limit"><?php show_limit_options($limit);?></select> entries / page.</td>
  </tr>
 </form>
 <tr>
  <td bgcolor="#000000" colspan="5"><?echo spacer(1,1);?></td>
 </tr>
 <form method="GET" action="bug.php">
  <tr>
   <td align="right"><input type="submit" value="Edit" /></td>
   <td align="right">bug number:</td>
   <td colspan="3"><input type="text" name="id" value="<?echo $id?>"></td>
   <input type="hidden" name="edit" value="<?php echo isset($MAGIC_COOKIE) ? 1 : 2;?>">
  </tr>
 </form>
</table>
<?php
}
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
