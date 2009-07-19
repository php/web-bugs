<?php /* vim: set noet ts=4 sw=4: : */
$id = (int)$_REQUEST['id'];

if (!$id) {
  echo json_encode(array('result'=>array('error'=>'Missing bug id')));
  exit;
}

require './include/auth.inc';

if (isset($_POST['MAGIC_COOKIE'])) {
  list($user,$pw) = explode(":", base64_decode($_POST['MAGIC_COOKIE']),2);
} else {
  echo json_encode(array('result'=>array('error'=>'Missing credentials')));
  exit;
}

if (!verify_password($user,$pw)) {
  echo json_encode(array('result'=>array('error'=>'Invalid user or password')));
  exit;
}

@mysql_connect("localhost","nobody","") or die("Unable to connect to SQL server.");
@mysql_select_db("phpbugdb");

# fetch info about the bug into $bug
$query = "SELECT id,bug_type,email,sdesc,ldesc,php_version,php_os,status,ts1,ts2,assign,"
       . "UNIX_TIMESTAMP(ts1) AS submitted, UNIX_TIMESTAMP(ts2) AS modified,"
       . "COUNT(bug=id) AS votes,"
       . "SUM(reproduced) AS reproduced,SUM(tried) AS tried,"
       . "SUM(sameos) AS sameos, SUM(samever) AS samever,"
       . "AVG(score)+3 AS average,STD(score) AS deviation"
       . " FROM bugdb LEFT JOIN bugdb_votes ON id=bug WHERE id=$id"
       . " GROUP BY bug";

$res = mysql_query($query);

if ($res) {
    $bug = mysql_fetch_assoc($res);
}

if (!$res || !$bug) {
  echo json_encode(array('result'=>array('error'=>'No such bug')));
  exit;
}

if(!empty($_POST['ncomment'])) {
    $ncomment = trim($_POST['ncomment']);
    $query = "INSERT INTO bugdb_comments (bug,email,ts,comment) VALUES ('$id','$user@php.net',NOW(),'".mysql_real_escape_string($ncomment)."')";
    $success = @mysql_query($query);
    if($success) {
        echo json_encode(array('result'=>array('status'=>$bug)));
    } else {
        echo json_encode(array('result'=>array('error'=>mysql_error())));
        exit;
    }
}

echo json_encode(array('result'=>array('error'=>'Nothing to do')));
