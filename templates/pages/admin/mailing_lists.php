<?php $this->extends('layout.php', ['title' => 'Admin :: Package mailing lists']) ?>

<?php $this->start('content') ?>

<?php $this->include('pages/admin/menu.php', ['action' => $action]); ?>

<dl>
    <?php foreach ($lists as $list): ?>
        <dt><?= $this->e($list['name']); ?>: </dt>
        <dd>
            <a href="mailto:<?= $this->noHtml($list['list_email']); ?>">
                <?= $this->e($list['list_email']); ?>
            </a>
        </dd>
    <?php endforeach ?>
</dl>

<?php $this->end('content') ?>
