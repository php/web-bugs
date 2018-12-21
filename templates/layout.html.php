<?php

// TODO: Refactor this into a better authentication service
$is_logged = false;

if ($logged_in === 'developer') {
    $is_logged = true;
    $username = $auth_user->handle;
} else if (!empty($_SESSION['user'])) {
    $is_logged = true;
    $username = $_SESSION['user'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<base href="<?= $site_method ?>://<?= $site_url.$basedir; ?>/">
<title><?= $siteBig ?> :: <?= $this->e($title) ?></title>
<link rel="shortcut icon" href="<?= $site_method ?>://<?= $site_url.$basedir ?>/images/favicon.ico">
<link rel="stylesheet" href="<?= $site_method ?>://<?= $site_url.$basedir ?>/css/style.css">
</head>

<body>

<table id="top" class="head" cellspacing="0" cellpadding="0">
<tr>
    <td class="head-logo">
        <a href="/"><img src="images/logo.png" alt="Bugs" vspace="2" hspace="2"></a>
    </td>

    <td class="head-menu">
        <a href="https://php.net/">php.net</a>&nbsp;|&nbsp;
        <a href="https://php.net/support.php">support</a>&nbsp;|&nbsp;
        <a href="https://php.net/docs.php">documentation</a>&nbsp;|&nbsp;
        <a href="report.php">report a bug</a>&nbsp;|&nbsp;
        <a href="search.php">advanced search</a>&nbsp;|&nbsp;
        <a href="search-howto.php">search howto</a>&nbsp;|&nbsp;
        <a href="stats.php">statistics</a>&nbsp;|&nbsp;
        <a href="random">random bug</a>&nbsp;|&nbsp;
        <?php if ($is_logged): ?>
            <a href="search.php?cmd=display&amp;assign=<?= $username ?>">my bugs</a>&nbsp;|&nbsp;
                <?php if ($logged_in === 'developer'): ?>
                    <a href="/admin/">admin</a>&nbsp;|&nbsp;
                <?php endif; ?>
            <a href="logout.php">logout</a>
        <?php else: ?>
            <a href="login.php">login</a>
        <?php endif; ?>
    </td>
</tr>

<tr>
    <td class="head-search" colspan="2">
        <form method="get" action="search.php">
            <p class="head-search">
                <input type="hidden" name="cmd" value="display">
                <small>go to bug id or search bugs for</small>
                <input class="small" type="text" name="search_for" value="<?= $this->e($_GET['search_for'] ?? '') ?>" size="30">
                <input type="image" src="images/small_submit_white.gif" alt="search" style="vertical-align: middle;">
            </p>
        </form>
    </td>
</tr>
</table>

<table class="middle" cellspacing="0" cellpadding="0">
<tr>
    <td class="content">
        <?= $this->section('content') ?>
    </td>
</tr>
</table>

<table class="foot" cellspacing="0" cellpadding="0">
<tr>
    <td class="foot-bar" colspan="2">&nbsp;</td>
</tr>

<tr>
    <td class="foot-copy">
        <small>
            <a href="https://php.net/"><img src="images/logo-small.gif" align="left" valign="middle" hspace="3" alt="PHP"></a>
            <a href="https://php.net/copyright.php">Copyright &copy; 2001-<?= date('Y'); ?> The PHP Group</a><br>
            All rights reserved.
        </small>
    </td>
    <td class="foot-source">
        <small>Last updated: <?= $this->noHtml($LAST_UPDATED) ?></small>
    </td>
</tr>
</table>

<?= $this->section('scripts') ?>
</body>
</html>
