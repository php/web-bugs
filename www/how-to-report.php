<?php

/**
 * Page with info how to report a bug.
 */

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// Output template with given template variables.
echo $template->render('pages/how_to_report.php');
