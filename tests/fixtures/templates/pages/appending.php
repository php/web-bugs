<?php $this->layout('layout.php', ['title' => 'Testing sections appends']) ?>

<?php include __DIR__.'/../forms/form_1.php'; ?>

<?php $this->append('scripts'); ?>
<script src="/path/to/file_1.js"></script>
<?php $this->end('scripts'); ?>
