<?php

/**
 * Page with a quick info how to generate backtrace with compiler on Windows.
 */

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// Output template with given template variables.
echo $template->render('pages/bugs_generating_backtrace_win32.php');
