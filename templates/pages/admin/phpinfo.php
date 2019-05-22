<?php $this->extends('layout.php', ['title' => 'Admin :: phpinfo()']) ?>

<?php $this->start('content') ?>

<?php $this->include('pages/admin/menu.php', ['action' => $action]); ?>

<?= $info; ?>

<?php $this->end('content') ?>
