<?php $this->extends('base.php') ?>

<?php $this->start('body') ?>
    <?= $this->block('sidebar') ?>

    <?= $this->block('content') ?>

    <?= $this->block('this_block_is_not_set') ?>

    <?= $layoutParameter_1 ?? '' ?>
    <?= $layoutParameter_2 ?? '' ?>
    <?= $layoutParameter_3 ?? '' ?>

    <?= $this->block('scripts') ?>

    <?= $this->include('includes/banner.php') ?>
<?php $this->end('body') ?>
