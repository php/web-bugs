<?php /* vim: set noet ts=4 sw=4: : */
require_once 'prepend.inc';

if (!($id = (int)$id)) {
  header("Location: /");
  exit;
}

if (!isset($score)) die("missing parameter score");
$score = (int)$score;
if ($score < -2 || $score > 2) {
  die("invalid score: $score");
}

if (!isset($reproduced)) die("missing parameter reproduced");
$reproduced = (int)$reproduced;
$samever = (int)$samever;
$sameos = (int)$sameos;

@mysql_connect("localhost","nobody","")
	or die("Unable to connect to SQL server.");
@mysql_select_db("phpbugdb");

$ip = ip2long($HTTP_X_FORWARDED_FOR ? $HTTP_X_FORWARDED_FOR : $REMOTE_ADDR);
// TODO: check if ip address has been banned. hopefully this will
//       never need to be implemented.

// verify the bug id is real (no voting on unreported bugs! :)

// add the vote
$query = "INSERT INTO bugdb_votes (bug,ip,score,reproduced,tried,sameos,samever)"
       . " VALUES($id,$ip,$score,"
       . ($reproduced == 1 ? "1," : "0,")
       . ($reproduced != 2 ? "1," : "0,")
       . ($reproduced ? "$sameos," : "NULL,")
       . ($reproduced ? "$samever" : "NULL")
       . ");";

mysql_query($query)
  or die("query <tt>$query</tt> failed: ".mysql_error());

// redirect to the bug page (which will display the success message)

header("Location: bug.php?id=$id&thanks=6");
