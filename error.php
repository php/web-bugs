<?php
$uri=substr($REDIRECT_ERROR_NOTES,strpos($REDIRECT_ERROR_NOTES,$DOCUMENT_ROOT)+strlen($DOCUMENT_ROOT)+1);

if ($uri[0] == "/") $uri = substr($uri,1);
Header("Location: http://bugs.php.net/bug.php?id=$uri");
?>
