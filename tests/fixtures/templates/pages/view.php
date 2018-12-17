<?php $this->extends('layout.php', ['title' => 'Bugs homepage']) ?>

<?php $this->start('content'); ?>
<?= $foo; ?>
<?php $this->end('content'); ?>

<?php $this->start('sidebar'); ?>
<?= $sidebar; ?>
<?php $this->end('sidebar'); ?>
