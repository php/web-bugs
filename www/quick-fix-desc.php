<?php

/**
 * List of all quick fix responses.
 */

use App\Repository\ReasonRepository;

// Application bootstrap
require __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

$reasonRepository = $container->get(ReasonRepository::class);
list($reasons, $variations) = $reasonRepository->findByProject('php');

// Output template with given template variables.
echo $template->render('pages/quick_fix_desc.php', [
    'reasons' => $reasons,
    'variations' => $variations,
]);
