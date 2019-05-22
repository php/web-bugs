<?php

/**
 * Page with a quick info how to generate gdb backtrace on Unix.
 */

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// Output template with given template variables.
echo $template->render('pages/bugs_generating_backtrace.php');
