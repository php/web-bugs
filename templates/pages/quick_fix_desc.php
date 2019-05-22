<?php $this->extends('layout.php', ['title' => 'Quick fix descriptions']) ?>

<?php $this->start('content') ?>

<table border="1" cellpadding="3" cellspacing="1">
<?php foreach ($reasons as $key => $reason): ?>
    <?php if (!empty($reason['package_name'])): ?>
        <?php $reason['title'] = $reason['title'].' ('.$reason['package_name'].')'; ?>
    <?php endif ?>

    <tr>
        <td><?= $this->e($reason['title']) ?></td>
        <td>Status: <?= $this->e($reason['status']) ?></td>
        <td><pre><?= $this->e($reason['message']) ?></pre></td>
    </tr>

    <?php if (isset($variations[$key])): ?>
        <?php foreach ($variations[$key] as $type => $variation): ?>
            <tr>
                <td><?= $this->e($reason['title']) ?> (<?= $this->e($type) ?>)</td>
                <td>Status: <?= $this->e($reason['status']) ?></td>
                <td><pre><?= $this->e($variation) ?></pre></td>
            </tr>
        <?php endforeach ?>
    <?php endif ?>
<?php endforeach ?>
</table>

<?php $this->end('content') ?>
