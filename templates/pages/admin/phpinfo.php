<?php $this->extends('layout.php', ['title' => 'Admin :: phpinfo()']) ?>

<?php $this->start('content') ?>

<?php $this->include('pages/admin/menu.php'); ?>

<?= $info; ?>

<?php $this->end('content') ?>
