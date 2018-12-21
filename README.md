# PHP Bug Tracking System

This is a unified bug tracking system for PHP hosted online at
[bugs.php.net](https://bugs.php.net).

## Local installation

* Copy configuration and modify it accordingly for your local system:

```bash
cp local_config.php.sample local_config.php
```

* Install development dependencies with Composer:

```bash
composer install
```

* Install required dependencies using PEAR:

```bash
pear channel-update pear.php.net
pear install --alldeps Text_Diff
```

* Database:

Create a new MySQL/MariaDB database using `sql/database.sql`, create database
schema `sql/schema.sql` and insert fixtures using `sql/fixtures.sql`.

## Tests

Application unit tests can be executed in development environment after
installing dependencies by running `phpunit`:

```bash
phpunit
```

## Directory structure

Source code of this application is structured in the following directories:

```bash
<web-bugs>/
 ├─ .git/                   # Git configuration and source directory
 └─ include/                # Application helper functions and configuration
    ├─ classes/             # PEAR class overrides
    ├─ prepend.php          # Autoloader, DB connection, container, app initialization
    └─ ...
 └─ scripts/                # Command line development tools and scripts
    ├─ cron/                # Various systems scripts to run periodically on the server
    └─ ...
 ├─ sql/                    # Database schema and fixtures
 ├─ src/                    # Application source code classes
 ├─ templates/              # Application templates
 ├─ tests/                  # Application automated tests
 ├─ uploads/                # Uploaded patch files
 ├─ vendor/                 # Dependencies generated by Composer
 └─ www/                    # Publicly accessible directory for online bugs.php.net
    ├─ css/                 # Stylesheets
    ├─ images/              # Images
    ├─ js/                  # JavaScript assets
    └─ ...
 ├─ composer.json           # Composer dependencies and project meta definition
 ├─ composer.lock           # Dependencies versions currently installed
 ├─ local_config.php        # Application configuration
 ├─ local_config.php.sample # Distributed configuration example
 ├─ phpunit.xml.dist        # PHPUnit's default XML configuration
 └─ ...
```

## Contributing

Issues with the application and new feature requests can be reported to
[bugs.php.net](https://bugs.php.net) and discussed by sending message to the
[webmaster mailing list](http://news.php.net/php.webmaster) to the address
php-webmaster@lists.php.net.

Application source code is located in the
[git.php.net](https://git.php.net/?p=web/bugs.git) repository.

Contributions can be done by forking the [GitHub mirror](https://github.com/php/web-bugs)
repository and sending a pull request.

```bash
git clone git@github.com:your-username/web-bugs
cd web-bugs
git checkout -b patch-1
git add .
git commit -m "Describe changes"
git push origin patch-1
```

A good practice is to also set the upstream remote in case the upstream master
branch updates. This way your master branch will track remote upstream master
branch of the root repository.

```bash
git checkout master
git remote add upstream git://github.com/php/web-bugs
git config branch.master.remote upstream
git pull --rebase
```

## Application architecture

### Templates

Application has a simple template engine built in to separate logic from the
presentation layer with several options.

Initialization of the template engine:

```php
<?php

// In front controller (index.php) or bootstrap (includes/prepend.php)
$template = new App\Template(__DIR__.'/../templates', new App\Template\Context());

// Output the processed template content
echo $template->render('pages/index.html.php', [
    'parameter' => 'Value',
]);
```

Above template `pages/index.html.php` is located in the templates directory:

```php
<?php $this->layout('layout.html.php', ['title' => 'Optional additional title']) ?>

<?php $this->start('content') ?>
    <h1>PHP Bugs System</h1>

    <p>...</p>

    Parameter value: <?= $this->noHtml($parameter) ?>
<?php $this->end('content') ?>
```

Main `layout.html.php`:

```php
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
    </body>
</html>
```
