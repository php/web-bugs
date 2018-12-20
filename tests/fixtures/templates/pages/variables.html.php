<?php $this->layout('layout.html.php', ['title' => 'Testing variables']) ?>

<?php $this->start('content'); ?>
Defined parameter is <?= $parameter; ?>.<br>
<?= $foo; ?>
<?php $this->end('content'); ?>

<?php $this->start('sidebar'); ?>
<?= $sidebar; ?>
<?php $this->end('sidebar'); ?>
