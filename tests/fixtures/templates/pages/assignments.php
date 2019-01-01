<?php $this->extends('layout.php', ['title' => 'Testing variables']) ?>

<?php $this->start('content'); ?>
Defined parameter is <?= $parameter; ?>.<br>
<?= $foo; ?>
<?php $this->end('content'); ?>
