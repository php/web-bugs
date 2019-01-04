<?php

/**
 * Page with search how to guide.
 */

// Application bootstrap
require __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// Output template with given template variables.
echo $template->render('pages/search_how_to.php');
