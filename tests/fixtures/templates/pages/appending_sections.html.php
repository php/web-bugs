<?php $this->layout('layout.html.php', ['title' => 'Testing sections appends']) ?>

<?php include __DIR__.'/../forms/form_1.html.php'; ?>

<?php $this->start('scripts', true); ?>
<script src="/path/to/file_1.js"></script>
<?php $this->end('scripts'); ?>
