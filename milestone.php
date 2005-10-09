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

static $closed_status = array('Closed', 'Bogus');

$res = mysql_query("SELECT id, name from bugdb_milestones order by name");
while (($row = mysql_fetch_assoc($res))) {
	$bugs = array();
	$closed = 0;

	$milestone = $row['name'];

	$id = (int)$row['id'];
	$q = mysql_query("SELECT id, bug_type, sdesc, status, assign from bugdb where milestone_id=$id");
	while (($row = mysql_fetch_assoc($q))) {
		$bugs[] = $row;
		$closed += in_array($row['status'], $closed_status);
	}


	if (count($bugs))
		$pct = $closed / count($bugs) * 100;
	else
		$pct = 100;

	echo "<h1>Milestone: " . htmlspecialchars($milestone) . " [$pct %]</h1>";

	if (count($bugs)) {
		echo "<table class=\"milestone\">";

		echo "<tr><th>ID#</th><th>Type</th><th>Status</th><th>Summary</th><th>Assigned</th></tr>";

		foreach ($bugs as $row) {
			echo "<tr>";
			echo "<td><a href=\"bug.php?id=$row[id]\">$row[id]</a></td>";
			echo "<td>" . htmlspecialchars($row['bug_type']) . "</td>";
			echo "<td>" . htmlspecialchars($row['status']) . "</td>";
			echo "<td>" . htmlspecialchars($row['sdesc']) . "</td>";
			echo "<td>" . htmlspecialchars($row['assign']) . "</td>";
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

