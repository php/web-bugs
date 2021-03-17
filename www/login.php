<?php

session_start();

require_once '../include/prepend.php';

if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';

if (isset($_POST['user'])) {
    $referer = $_POST['referer'];

    bugs_authenticate($user, $pwd, $logged_in, $user_flags);

    if ($logged_in === 'developer') {
        if (!empty($_POST['referer']) &&
            preg_match("/^{$site_method}:\/\/". preg_quote($site_url) .'/i', $referer) &&
            parse_url($referer, PHP_URL_PATH) !== '/logout.php') {
            redirect($referer);
        }

        redirect('index.php');
    }
}

echo $template->render('pages/login.php', [
    'referer'      => $referer,
    'username'     => $user ?? '',
    // if we have a posted username and we got here it means the login was invalid
    'invalidLogin' => isset($_POST['user']),
]);
