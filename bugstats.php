<?php /* vim: set noet ts=4 sw=4: : */

require_once 'prepend.inc';

commonHeader("Statistics");

@mysql_connect("localhost","nobody","")
	or die("unable to connect to database");
@mysql_select_db("phpbugdb")
	or die("unable to select database");

$sort_by = isset($_GET['sort_by']) ? htmlspecialchars($_GET['sort_by'], ENT_QUOTES) : 'open';
$rev     = isset($_GET['rev'])     ? (int) $_GET['rev']    : 1;
$phpver  = isset($_GET['phpver'])  ? (int) $_GET['phpver'] : 0;

$query = "SELECT status,bug_type,email,php_version,php_os FROM bugdb";

if ($phpver > 0) {
	$query .= " WHERE SUBSTRING(php_version,1,1) = '$phpver'";
}

$query .= " ORDER BY bug_type";

$result = mysql_unbuffered_query($query);

while ($row = mysql_fetch_assoc($result)) {
	$bug_type['all'][$row['bug_type']]++;
	$status_str = strtolower($row['status']);
	$bug_type[$status_str][$row['bug_type']]++;
	$bug_type[$status_str]['all']++;
	$email[$row['email']]++;
	$php_version[$row['php_version']]++;
	$php_os[$row['php_os']]++;
	$status[$row['status']]++;
	$total++;
}

// Exit if there are no bugs for this version
if ($total == 0) {
	echo '<p>No bugs found for this PHP version</p>';
	commonFooter();
	exit;
}

if ($phpver > 0) {
	echo "<p>Currently displaying <strong>PHP {$phpver}</strong> bugs only."; 
} else {
	echo "<p>Currently displaying <strong>all</strong> bugs."; 
}
echo '<p>Display either <a href="bugstats.php?phpver=0">all</a> bugs or optionally choose a specific PHP Version: ';
echo '<a href="bugstats.php?phpver=4">4</a> or <a href="bugstats.php?phpver=5">5</a> or <a href="bugstats.php?phpver=6">6</a></p>';

echo '<p>See also bug statistics for <a href="http://pecl.php.net/bugs/stats.php">PECL</a> or <a href="http://pear.php.net/bugs/stats.php">PEAR</a></p>';

function bugstats ($status, $type)
{
	global $bug_type, $phpver;

	if ($bug_type[$status][$type] > 0) {
		return '<a href="index.php?cmd=display&amp;status=' . ucfirst($status) . "&amp;phpver=" . $phpver . ($type == 'all' ? '' : '&amp;bug_type[]=' . urlencode($type)) . '&amp;by=Any">' . $bug_type[$status][$type] . "</a>\n";
	}
}

mysql_free_result($result);
echo "<table>\n";

/* prepare for sorting by bug report count */
foreach($bug_type['all'] as $type => $value) {
	if (!isset($bug_type['closed'][$type]))      $bug_type['closed'][$type] = 0;
	if (!isset($bug_type['bogus'][$type]))       $bug_type['bogus'][$type] = 0;
	if (!isset($bug_type['open'][$type]))        $bug_type['open'][$type] = 0;
	if (!isset($bug_type['critical'][$type]))    $bug_type['critical'][$type] = 0;
	if (!isset($bug_type['analyzed'][$type]))    $bug_type['analyzed'][$type] = 0;
	if (!isset($bug_type['verified'][$type]))    $bug_type['verified'][$type] = 0;
	if (!isset($bug_type['suspended'][$type]))   $bug_type['suspended'][$type] = 0;
	if (!isset($bug_type['duplicate'][$type]))   $bug_type['duplicate'][$type] = 0;
	if (!isset($bug_type['assigned'][$type]))    $bug_type['assigned'][$type] = 0;
	if (!isset($bug_type['no feedback'][$type])) $bug_type['no feedback'][$type] = 0;
	if (!isset($bug_type['wont fix'][$type]))    $bug_type['wont fix'][$type] = 0;
	if (!isset($bug_type['feedback'][$type]))    $bug_type['feedback'][$type] = 0;
}

if (!isset($bug_type[$sort_by])) {
	$sort_by = 'open';
}

if ($rev == 1) {
	arsort($bug_type[$sort_by]);
} else {
	asort($bug_type[$sort_by]);
}
reset($bug_type);

function sort_url ($type)
{
	global $sort_by,$rev,$phpver;

	if ($type == $sort_by) { 
		$reve = ($rev == 1) ? 0 : 1;		
	} else {
		$reve = 1;
	}	
	$ver = ($phpver != 0) ? "phpver=$phpver&amp;" : '';
	return '<A href="./bugstats.php?'.$ver.'sort_by='.urlencode($type).'&amp;rev='.$reve.'">'.ucfirst($type).'</a>';
}

function display_stat_header($total, $grandtotal = true) {
	if ($grandtotal) {
		echo "<tr bgcolor=#aabbcc><th align=right>Total bug entries in system:</th><td>$total</td>";
	} else {
		echo "<tr bgcolor=#aabbcc><th align=right>&nbsp;</th><td>&nbsp;</td>";
	}
	echo "<th>",sort_url('closed'),"</th>";
	echo "<th>",sort_url('open'),"</th>";
	echo "<th>",sort_url('critical'),"</th>";
	echo "<th>",sort_url('verified'),"</th>";
	echo "<th>",sort_url('analyzed'),"</th>";
	echo "<th>",sort_url('assigned'),"</th>";
	echo "<th>",sort_url('suspended'),"</th>";
	echo "<th>",sort_url('duplicate'),"</th>";
	echo "<th>",sort_url('feedback'),"</th>";
	echo "<th nowrap>",sort_url('no feedback'),"</th>";
	echo "<th nowrap>",sort_url('wont fix'),"</th>";
	echo "<th>",sort_url('bogus'),"</th>";
	echo "</tr>\n";
}

display_stat_header($total, true);

echo "<tr><th align=right bgcolor=#aabbcc>All:</th>",
     "<td align=center bgcolor=#ccddee>$total</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('closed','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ccddee>".bugstats('open', 'all')."&nbsp;</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('critical', 'all')."&nbsp;</td>",
     "<td align=center bgcolor=#ccddee>".bugstats('verified', 'all')."&nbsp;</td>",     
     "<td align=center bgcolor=#ccddee>".bugstats('analyzed', 'all')."&nbsp;</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('assigned','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('suspended','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ccddee>".bugstats('duplicate', 'all')."&nbsp;</td>",
     "<td align=center bgcolor=#ccddee>".bugstats('feedback','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('no feedback','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ddeeff>".bugstats('wont fix','all')."&nbsp;</td>",
     "<td align=center bgcolor=#ccddee>".bugstats('bogus', 'all')."&nbsp;</td>",
     "</tr>\n";

$stat_row = 1;
foreach ($bug_type[$sort_by] as $type => $value) {
	if(($bug_type['open'][$type] > 0 || 
		$bug_type['critical'][$type] > 0 ||
		$bug_type['analyzed'][$type] > 0 ||
		$bug_type['verified'][$type] > 0 ||
		$bug_type['suspended'][$type] > 0 ||
		$bug_type['duplicate'][$type] > 0 ||
		$bug_type['assigned'][$type] > 0 ||
		$bug_type['feedback'][$type] > 0 ) && $type != 'all') 
	{ 
		/* Output a new header row every 40 lines */
		if (($stat_row++ % 40) == 0) display_stat_header($total, false);

		echo "<tr><th align=right bgcolor=#aabbcc>$type:</th>",
		     "<td align=center bgcolor=#ccddee>".$bug_type['all'][$type]."</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('closed', $type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('open', $type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('critical',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('verified', $type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('analyzed', $type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('assigned',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('suspended',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('duplicate', $type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('feedback',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('no feedback',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ddeeff>".bugstats('wont fix',$type)."&nbsp;</td>",
		     "<td align=center bgcolor=#ccddee>".bugstats('bogus', $type)."&nbsp;</td>",
		     "</tr>\n";
	}
}

echo "</table>\n";

$query = "SELECT COUNT(*) AS count,MAX(UNIX_TIMESTAMP(ts2)-UNIX_TIMESTAMP(ts1)) AS slowest,MIN(UNIX_TIMESTAMP(ts2)-UNIX_TIMESTAMP(ts1)) AS quickest,AVG(UNIX_TIMESTAMP(ts2)-UNIX_TIMESTAMP(ts1)) AS average FROM bugdb WHERE ts2 > ts1";
if ($phpver > 0) {
	$query .= " AND SUBSTRING(php_version,1,1) = '$phpver'";
}
$res = mysql_query($query);
$row = mysql_fetch_assoc($res);

$half = (int) ($row['count']/2);
$query = "SELECT UNIX_TIMESTAMP(ts2)-UNIX_TIMESTAMP(ts1) AS half FROM bugdb WHERE ts2 > ts1";
if ($phpver > 0) {
	$query .= " AND SUBSTRING(php_version,1,1) = '$phpver'";
}
$query .= " ORDER BY UNIX_TIMESTAMP(ts2)-UNIX_TIMESTAMP(ts1) LIMIT $half,1";
$res = mysql_query($query);
$median = mysql_result($res,0);

echo "<p><b>Bug Report Time to Close Stats</b>\n";
echo "<table>\n";
echo "<tr bgcolor=#aabbcc><th align=right>Average life of a report:</th><td bgcolor=#ccddee>",ShowTime((int)$row['average']),"</td></tr>\n";
echo "<tr bgcolor=#aabbcc><th align=right>Median life of a report:</th><td bgcolor=#ccddee>",ShowTime($median),"</td></tr>\n";
echo "<tr bgcolor=#aabbcc><th align=right>Slowest report closure:</th><td bgcolor=#ccddee>",ShowTime($row['slowest']),"</td></tr>\n";
echo "<tr bgcolor=#aabbcc><th align=right>Quickest report closure:</th><td bgcolor=#ccddee>",ShowTime($row['quickest']),"</td></tr>\n";
echo "</table>\n";

commonFooter();

function ShowTime($sec)
{
	if ($sec < 60) {
		return "$sec seconds";
	} else if ($sec < 120) {
		return (int)($sec / 60)." minute ".($sec%60)." seconds";
	} else if ($sec < 3600) {
		return (int)($sec / 60)." minutes ".($sec%60)." seconds";
	} else if ($sec < 7200) {
		return (int)($sec / 3600)." hour ".(int)(($sec%3600)/60)." minutes ".(($sec%3600)%60)." seconds";
	} else if ($sec < 86400) {
		return (int)($sec/3600)." hours ".(int)(($sec%3600)/60)." minutes ".(($sec%3600)%60)." seconds";
	} else if ($sec < 172800) {
		return (int)($sec / 86400)." day ".(int)(($sec%86400)/3600)." hours ".(int)((($sec%86400)%3600)/60)." minutes ".((($sec%86400)%3600)%60)." seconds";
	} else {
		return (int)($sec / 86400)." days ".(int)(($sec%86400)/3600)." hours ".(int)((($sec%86400)%3600)/60)." minutes ".((($sec%86400)%3600)%60)." seconds";
	}
}
