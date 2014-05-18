PHP Bug Tracking System
=======================
This was a collaboration between PEAR, PECL and PHP core to create a unified bug tracker.

Requirements
============
- PHP 5.4+

Installation
============
1. Copy `local_config.php.sample` to `local_config.php` and modify accordingly
2. PHP.net Bug Tracking System requires `ext/openssl` (for https:// fopen wrapper)
3. Required PEAR packages:
	- MDB2
	- MDB2#mysql
	- DB_DataObject
	- Text_CAPTCHA_Numeral
	- Text_Diff
	- Tree
	- HTTP
	- HTTP_Upload

Command to install all required packages:
`pear install MDB2 MDB2#mysql MDB2#mysqli DB_DataObject Text_CAPTCHA_Numeral Text_Diff Tree-beta HTTP HTTP_Upload`

TODO
====
- AJAXify where it's useful
- Automate (and centralize, @master.php.net?) PHP versions fetching
- Add project support (f.e. PHP-GTK, PHP, PEAR, PECL..)