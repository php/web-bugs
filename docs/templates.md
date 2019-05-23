# Templates

A simple template engine separates logic from the presentation and provides
methods for creating nested templates and escaping strings to protect against
too common XSS vulnerabilities.

Template engine initialization:

```php
$template = new App\Template\Engine(__DIR__.'/../path/to/templates');
```

Site-wide configuration parameters can be assigned before rendering so they are
available in all templates:

```php
$template->assign([
    'siteUrl' => 'https://bugs.php.net',
    // ...
]);
```

Page can be rendered in the controller:

```php
echo $template->render('pages/how_to_report.php', [
    'mainHeading' => 'How to report a bug?',
]);
```

The `templates/pages/how_to_report.php`:

```php
<?php $this->extends('layout.php', ['title' => 'Reporting bugs']) ?>

<?php $this->start('main_content') ?>
    <h1><?= $this->noHtml($mainHeading) ?></h1>

    <p><?= $siteUrl ?></p>
<?php $this->end('main_content') ?>

<?php $this->start('scripts') ?>
    <script src="/js/feature.js"></script>
<?php $this->end('scripts') ?>
```

The `templates/layout.php`:

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/css/style.css">
        <title>PHP Bug Tracking System :: <?= $title ?? '' ?></title>
    </head>
    <body>
        <?= $this->block('main_content') ?>

        <div><?= $siteUrl ?></div>

        <script src="/js/app.js"></script>
        <?= $this->block('scripts') ?>
    </body>
</html>
```

## Including templates

To include a partial template snippet file:

```php
<?php $this->include('forms/report_bug.php') ?>
```

which is equivalent to `<?php include __DIR__.'/../forms/contact.php' ?>`,
except that the variable scope is not inherited by the template that included
the file. To import variables into the included template snippet file:

```php
<?php $this->include('forms/contact.php', ['formHeading' => 'value', 'foo' => 'bar']) ?>
```

## Blocks

Blocks are main building elements that contain template snippets and can be
included into the parent file(s).

Block is started with the `$this->start('block_name')` call and ends with
`$this->end('block_name')`:

```php
<?php $this->start('block_name') ?>
    <h1>Heading</h1>

    <p>...</p>
<?php $this->end('block_name') ?>
```

### Appending blocks

Block content can be appended to existing blocks by the
`$this->append('block_name')`.

The `templates/layout.php`:

```html
<html>
<head></head>
<body>
    <?= $this->block('content'); ?>

    <?= $this->block('scripts'); ?>
</body>
</html>
```

The `templates/pages/index.php`:

```php
<?php $this->extends('layout.php'); ?>

<?php $this->start('scripts'); ?>
    <script src="/js/foo.js"></script>
<?php $this->end('scripts'); ?>

<?php $this->start('content') ?>
    <?php $this->include('forms/form.php') ?>
<?php $this->end('content') ?>
```

The `templates/forms/form.php`:

```php
<form>
    <input type="text" name="title">
    <input type="submit" value="Submit">
</form>

<?php $this->append('scripts'); ?>
    <script src="/js/bar.js"></script>
<?php $this->end('scripts'); ?>
```

The final rendered page:

```html
<html>
<head></head>
<body>
    <form>
        <input type="text" name="title">
        <input type="submit" value="Submit">
    </form>

    <script src="/js/foo.js"></script>
    <script src="/js/bar.js"></script>
</body>
</html>
```

## Helpers

Registering additional template helpers can be useful when a custom function or
class method needs to be called in the template.

### Registering function

```php
$template->register('formatDate', function (int $timestamp): string {
    return gmdate('Y-m-d H:i e', $timestamp - date('Z', $timestamp));
});
```

### Registering object method

```php
$template->register('doSomething', [$object, 'methodName']);
```

Using helpers in templates:

```php
<p>Time: <?= $this->formatDate(time()) ?></p>
<div><?= $this->doSomething('arguments') ?></div>
```

## Escaping

When protecting against XSS there are two built-in methods provided.

To replace all characters to their applicable HTML entities in the given string:

```php
<?= $this->noHtml($var) ?>
```

To escape given string and still preserve certain characters as HTML:

```php
<?= $this->e($var) ?>
```
