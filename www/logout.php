<?php

session_start();

require_once '../include/prepend.php';

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    redirect('index.php');
}

unset($_SESSION['user']);
session_destroy();

echo $template->render('pages/logged_out.php');
