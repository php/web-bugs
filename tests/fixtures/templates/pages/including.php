<?php $this->extends('layout.php', ['title' => 'Testing blocks appends']) ?>

<?php $this->start('content') ?>
<?php $someVariable = 'foobarbaz' ?>
<?php $this->include('forms/form_2.php', ['importedVariable' => $someVariable]) ?>
<?php $this->end('content') ?>
