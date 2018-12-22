<html>
<head>
    <title><?=$this->e($title)?></title>
</head>
<body>
    <?= $this->section('sidebar') ?>

    <?= $this->section('content') ?>

    <?= $this->section('this_section_is_not_set') ?>

    <?= $layout_parameter_1 ?? '' ?>
    <?= $layout_parameter_2 ?? '' ?>
    <?= $layout_parameter_3 ?? '' ?>

    <?= $this->section('scripts') ?>
</body>
</html>
