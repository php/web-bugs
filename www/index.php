<?php

/**
 * The bug system home page.
 */

use App\Repository\BugRepository;
use App\Template\Context;
use App\Template\Engine;

// Application bootstrap
require_once __DIR__.'/../include/prepend.php';

// Initialize template engine
$template = new Engine(__DIR__.'/../templates', new Context());

// Start session
session_start();

// If 'id' is passed redirect to the bug page
$id = !empty($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id) {
    redirect("bug.php?id={$id}");
}

if($_SERVER['REQUEST_URI'] == '/random') {
    $id = (new BugRepository($dbh))->findRandom();
    redirect("bug.php?id={$id[0]}");
}

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

$searches = [
    'Most recent open bugs (all)' => '&bug_type=All',
    'Most recent open bugs (all) with patch or pull request' => '&bug_type=All&patch=Y&pull=Y',
    'Most recent open bugs (PHP 5.6)' => '&bug_type=All&phpver=5.6',
    'Most recent open bugs (PHP 7.1)' => '&bug_type=All&phpver=7.1',
    'Most recent open bugs (PHP 7.2)' => '&bug_type=All&phpver=7.2',
    'Most recent open bugs (PHP 7.3)' => '&bug_type=All&phpver=7.3',
    'Open Documentation bugs' => '&bug_type=Documentation+Problem',
    'Open Documentation bugs (with patches)' => '&bug_type=Documentation+Problem&patch=Y'
];

if (!empty($_SESSION["user"])) {
    $searches['Your assigned open bugs'] = '&assign='.urlencode($_SESSION['user']);
}

// Output template with given template variables.
echo $template->render('pages/index.html.php', [
    'site_method' => $site_method,
    'site_url'    => $site_url,
    'searches'    => $searches,
]);
