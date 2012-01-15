<?php

session_start();

require_once '../include/prepend.php';

$_SESSION["credentials"] = array();
session_destroy();

response_header('Logout');

?>

<p>You've been logged out.</p>

<?php response_footer();
