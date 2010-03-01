<?php
#$uri=substr($REDIRECT_ERROR_NOTES,strpos($REDIRECT_ERROR_NOTES,$DOCUMENT_ROOT)+strlen($DOCUMENT_ROOT)+1);
$uri = $_SERVER['REQUEST_URI'];
if ($uri[0] == "/") $uri = (int)substr($uri,1);
header("Location: http://bugs.php.net/bug.php?id=$uri");
?>
