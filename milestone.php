<?php
require_once 'prepend.inc';
require_once 'cvs-auth.inc';
require_once 'trusted-devs.inc';
	
$is_dev = false;

if (isset($MAGIC_COOKIE) && !isset($user) && !isset($pw)) {
	list($user,$pw) = explode(":", base64_decode($MAGIC_COOKIE));
	$is_dev = verify_password($user, stripslashes($pw)); 
}

@mysql_connect("localhost","nobody","") or die("Unable to connect to SQL server.");
@mysql_select_db("phpbugdb");

if ($is_dev && isset($_POST['new_milestone']) && strlen($_POST['name'])) {
	$name = mysql_escape_string(stripslashes($_POST['name']));
	mysql_query("insert into bugdb_milestones (name) values ('$name')");
}

commonHeader("Milestones");

$milestones = array();

static $closed_status = array('Closed', 'Bogus', 'Wont Fix');

$res = mysql_query("SELECT id, name from bugdb_milestones order by name");
while (($row = mysql_fetch_assoc($res))) {
	$closed_bugs = array();
	$open_bugs = array();
	$closed = 0;

	$milestone = $row['name'];

	$id = (int)$row['id'];
	$q = mysql_query("SELECT id, bug_type, sdesc, status, assign from bugdb where milestone_id=$id");
	while (($row = mysql_fetch_assoc($q))) {
		if (in_array($row['status'], $closed_status)) {
			$closed_bugs[] = $row;
			$closed++;
		} else {
			$open_bugs[] = $row;
		}
	}


	if (count($open_bugs))
		$pct = round($closed / (count($open_bugs) + $closed) * 100);
	else
		$pct = 100;

	echo "<h1>Milestone: " . htmlspecialchars($milestone) . " [$pct %]</h1>";

	if (count($open_bugs) || $closed) {
		echo "<table class=\"milestone\">";

		echo "<tr><th>Todo </th><th>ID#</th><th>Type</th><th>Status</th><th>Summary</th><th>Assigned</th></tr>";
		$color = 0;
		foreach ($open_bugs as $row) {
			if (!$color) {
				$class = 'color1';
				$color++;
			} else {
				$class = 'color2';
				$color--;
			}

			echo "<tr>";
			echo "<td style=\"background: #a00;\">&nbsp;</td>";
			echo "<td class=\"$class\"><a href=\"bug.php?id=$row[id]\">$row[id]</a></td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['bug_type']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['status']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['sdesc']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['assign']) . "</td>";
			echo "</tr>";
		}

		echo "<tr><td colspan=\"6\" style=\"background-color: transparent; border: 0px;\">&nbsp;</tr>";

		echo "<tr><th>Done </th><th>ID#</th><th>Type</th><th>Status</th><th>Summary</th><th>Assigned</th></tr>";
		$color = 0;
		foreach ($closed_bugs as $row) {
			if (!$color) {
				$class = 'color1';
				$color++;
			} else {
				$class = 'color2';
				$color--;
			}

			echo "<tr>";
			echo "<td style=\"background: #0a0;\">&nbsp;</td>";
			echo "<td class=\"$class\"><a href=\"bug.php?id=$row[id]\">$row[id]</a></td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['bug_type']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['status']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['sdesc']) . "</td>";
			echo "<td class=\"$class\">" . htmlspecialchars($row['assign']) . "</td>";
			echo "</tr>";
		}

		echo "</table>";
	}
	echo "<br/>";
}

if ($is_dev) {
?>
<br/>
<br/>
<br/>
<form method="POST" action="milestone.php">
Add new milestone: <input type="text" name="name"> <input type="submit" name="new_milestone" value="Add">
</form>

<?php
}

commonFooter();

