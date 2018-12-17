<?php $this->extends('layout.php', ['title' => 'Testing variables', 'layoutParameter_3' => 'Layout overridden parameter 3']) ?>

<?php $this->start('content'); ?>
<?= $pageParameter_1 ?>
<?= $pageParameter_2 ?>
<?php $this->end('content'); ?>
