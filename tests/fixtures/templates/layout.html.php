<html>
<head>
    <title><?=$this->e($title)?></title>
</head>
<body>
    <?= $this->section('sidebar') ?>

    <?= $this->section('content') ?>

    <?= $this->section('this_section_is_not_set') ?>

    <?= $this->section('scripts') ?>
</body>
</html>
