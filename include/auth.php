<?php

/**
 * Temporary helper placeholder file to overcome the authentication service
 * migration.
 *
 * TODO: Refactor this into a better authentication service.
 */

// Start session
session_start();

// Authenticate
bugs_authenticate($user, $pw, $logged_in, $user_flags);

if ('developer' === $logged_in) {
    $isLoggedIn = true;
    $username = $auth_user->handle;
} elseif (!empty($_SESSION['user'])) {
    $isLoggedIn = true;
    $username = $_SESSION['user'];
} else {
    $isLoggedIn = false;
    $username = '';
}

$template->assign([
    'authIsLoggedIn' => $isLoggedIn,
    'authUsername' => $username,
    'authRole' => $logged_in,
]);
