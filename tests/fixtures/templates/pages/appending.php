<?php $this->extends('layout.php', ['title' => 'Testing blocks appends']) ?>

<?php include __DIR__.'/../forms/form.php'; ?>

<?php $this->append('scripts'); ?>
<script src="/path/to/file_1.js"></script>
<?php $this->end('scripts'); ?>
