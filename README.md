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

Create a new database using `sql/database.sql`, create database schema
`sql/schema.sql` and insert fixtures using `sql/fixtures.sql`.

## Tests

Application unit tests can be executed in development environment after
installing dependencies by running `phpunit`:

```bash
phpunit
```
