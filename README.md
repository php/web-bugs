# PHP Bug Tracking System

This is a unified bug tracking system for PHP hosted online at
[bugs.php.net](https://bugs.php.net).

## Requirements

- PHP 7.2+
- ext/pdo
- ext/pdo_mysql
- ext/openssl (for https:// fopen wrapper)
- PEAR packages:
  - Text_CAPTCHA_Numeral
  - Text_Diff
  - HTTP_Upload (1.0.0b4 or later)

## Local installation

* Copy configuration and modify it accordingly for your local system:

```bash
cp local_config.php.sample local_config.php
```

* Install required dependencies using PEAR:

```bash
pear channel-update pear.php.net
pear install --alldeps Text_CAPTCHA_Numeral Text_Diff HTTP_Upload-1.0.0b4
```

* Database:

Create a new database using `sql/database.sql`, create database schema
`sql/schema.sql` and insert fixtures using `sql/fixtures.sql`.
