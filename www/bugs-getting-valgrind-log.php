<?php

/**
 * Valgring log guide page.
 */

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// Output template with given template variables.
echo $template->render('pages/bugs_getting_valgrind_log.php');
