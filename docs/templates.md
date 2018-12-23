# Templates

A simple template engine is integrated in the application to separate logic from
the presentation.

Template engine is initialized in the application bootstrap `includes/prepend.php`
with the main templates folder defined:

```php
$template = new App\Template\Engine(__DIR__.'/../templates');
```

Before rendering the template, some site-wide parameters can be assigned to be
available in all templates:

```php
$template->assign([
    'LAST_UPDATED' => $LAST_UPDATED,
    'site_url'     => $site_url,
    // ...
]);
```

Particular template is then rendered in `www/index.php` or controller:

```php
echo $template->render('pages/index.php', [
    'variable' => 'Value',
]);
```

The `templates/pages/index.php` looks something like this:

```php
<?php $this->layout('layout.php', ['title' => 'Optional additional title']) ?>

<?php $this->start('content') ?>
    <h1>PHP Bugs System</h1>

    <p>Variable: <?= $this->noHtml($variable) ?></p>
<?php $this->end('content') ?>

<?php $this->start('scripts') ?>
    <script src="/js/feature.js"></script>
<?php $this->end('scripts') ?>
```

The `templates/layout.php`:

```html
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="/css/style.css">
        <title>PHP Bug Tracking System :: <?= $title ?? '' ?></title>
    </head>
    <body>
        <?= $this->section('content') ?>

        <script src="/js/app.js"></script>
        <?= $this->section('scripts') ?>
    </body>
</html>
```

Using assigned variables in the template file:

```php
<p><?= $site ?></p>
```

## Including templates

To include partial template snippet file in other template or layout:

```php
<?php $this->include('forms/form.php') ?>
```

## Sections

Sections are main building blocks where template snippet can be included into
the main layout file.

Section snippet is started with the `$this->start('section_name')` call:

```php
<?php $this->start('content') ?>
    <h1>PHP Bugs System</h1>

    <p>Variable: <?= $this->noHtml($variable) ?></p>
<?php $this->end('content') ?>
```

To mark end of the section snippet call `$this->end('section_name')`.

### Appending sections

To append section content into existing sections:

In `templates/layout.php`:

```html
<html>
<head></head>
<body>
<?= $this->section('scripts'); ?>
</body>
</html>
```

In `templates/pages/index.php`:

```php
<?php $this->layout('layout.php'); ?>

<?php $this->start('scripts'); ?>
    <script src="/js/foo.js"></script>
<?php $this->end('scripts'); ?>

<?php $this->include('forms/form.php') ?>
```

In `templates/forms/form.php`:

```php
<?php $this->append('scripts'); ?>
    <script src="/js/bar.js"></script>
<?php $this->end('scripts'); ?>
```

This way the end result looks something like this:

```html
<html>
<head></head>
<body>
    <script src="/js/foo.js"></script>
    <script src="/js/bar.js"></script>
</body>
</html>
```

## Template helpers

Registering additional template helpers:

```php
$template->register('formatDate' => function (int $timestamp): string {
    return gmdate('Y-m-d H:i e', $timestamp - date('Z', $timestamp));
});
```

Using helpers in the template file:

```php
<p>Time: <?= $this->formatDate(time()) ?></p>
```

## Escaping

When protecting against XSS there are two built-in methods provided.

To replaces all characters to their applicable HTML entities in the given
string:

```php
<?= $this->noHtml($var) ?>
```

To escape given string and still preserves certain characters as HTML:

```php
<?= $this->e($var) ?>
```
