PHP Bug Tracking System
=======================
This was a collaboration between PEAR, PECL and PHP core to create a unified bug tracker.

Requirements
============
- PHP 5.4+
- `ext/openssl` (for https:// fopen wrapper)
- PEAR packages:
	- Text_CAPTCHA_Numeral
	- Text_Diff
	- HTTP_Upload

Installation
============
1. Copy `local_config.php.sample` to `local_config.php` and modify accordingly
2. Install all required packages:
`pear install Text_CAPTCHA_Numeral Text_Diff HTTP_Upload`
3. Import SQL schema from `sql/bugs.sql`

TODO
====
- AJAXify where it's useful
- Add project support (f.e. PHP-GTK, PEAR..)
