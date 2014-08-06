<?php

/* The bug system error page */

// Obtain common includes
require_once '../include/prepend.php';

// If 'id' is passed redirect to the bug page
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
	redirect("bug.php?id={$id}");
}

response_header('Bugs :: 404 Not Found');

?>
<h1>404 Not Found</h1>

<p>Doh.</p>

<?php response_footer();
