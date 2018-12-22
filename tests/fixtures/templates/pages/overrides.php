<?php $this->layout('layout.php', ['title' => 'Testing variables', 'layout_parameter_3' => 'Layout overridden parameter 3']) ?>

<?php $this->start('content'); ?>
<?= $page_parameter_1 ?>
<?= $page_parameter_2 ?>
<?php $this->end('content'); ?>
