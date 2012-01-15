<?php

session_start();

require_once '../include/prepend.php';

unset($_SESSION['user']);
session_destroy();

response_header('Logout');

?>

<p>You've been logged out.</p>

<?php response_footer();
