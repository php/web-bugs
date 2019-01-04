<?php

/**
 * The bug system home page.
 */
use App\Repository\BugRepository;

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Authentication
require_once __DIR__.'/../include/auth.php';

// If 'id' is passed redirect to the bug page
$id = (int) ($_GET['id'] ?? 0);

if (0 !== $id) {
    redirect('bug.php?id='.$id);
}

if ('/random' === $_SERVER['REQUEST_URI']) {
    $id = $container->get(BugRepository::class)->findRandom();
    redirect('bug.php?id='.$id[0]);
}

$searches = [
    'Most recent open bugs (all)' => '&bug_type=All',
    'Most recent open bugs (all) with patch or pull request' => '&bug_type=All&patch=Y&pull=Y',
    'Most recent open bugs (PHP 7.1)' => '&bug_type=All&phpver=7.1',
    'Most recent open bugs (PHP 7.2)' => '&bug_type=All&phpver=7.2',
    'Most recent open bugs (PHP 7.3)' => '&bug_type=All&phpver=7.3',
    'Open Documentation bugs' => '&bug_type=Documentation+Problem',
    'Open Documentation bugs (with patches)' => '&bug_type=Documentation+Problem&patch=Y',
];

if (!empty($_SESSION['user'])) {
    $searches['Your assigned open bugs'] = '&assign='.urlencode($_SESSION['user']);
}

// Prefix query strings with base URL
$searches = preg_filter(
    '/^/',
    '/search.php?limit=30&order_by=id&direction=DESC&cmd=display&status=Open',
    $searches
);

// Output template with given template variables.
echo $template->render('pages/index.php', [
    'searches' => $searches,
]);
