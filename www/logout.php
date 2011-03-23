<?php

require_once '../include/prepend.php';

setcookie("MAGIC_COOKIE","",time()-3600,'/','.php.net');
setcookie("MAGIC_COOKIE","",time()-3600,'/');
$_SESSION["credentials"] = array();
session_destroy();

response_header('Logout');

?>

<p>You've been logged out.</p>

<?php response_footer();
