<?php $this->layout('layout.php', ['title' => 'Bugs homepage']) ?>

<?php $this->start('content'); ?>
<?= $this->addAsterisks($foo); ?>
<?php $this->end('content'); ?>
