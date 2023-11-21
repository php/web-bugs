-- Packages take from bugs.php.net 8th Feb. 2021.

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(1,0,'*General Issues','General Issues','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(2,1,'Feature/Change Request','Feature/Change Request','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(3,1,'Documentation problem','Documentation problem','php','doc-bugs@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(4,1,'Translation problem','Documentation translation problem','php','doc-bugs@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(5,1,'Doc Build problem','Doc Build (PhD) problem','php','doc-bugs@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(6,1,'Website problem','PHP.net Website problem','php','php-webmaster@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(7,1,'Online Doc Editor problem','Online Documentation Editor problem','php','doc-bugs@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(8,1,'Systems problem','PHP.net Systems Operation problem','php','systems@example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(9,1,'Output Control','Output Control','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(10,1,'Performance problem','Performance problem','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(11,1,'Reproducible crash','Reproducible crash','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(12,1,'Scripting Engine problem','Scripting Engine problem','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(13,1,'SPL related','SPL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(14,1,'Reflection related','Reflection related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(15,1,'Session related','Session related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(16,1,'Filter related','Filter related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(17,1,'Streams related','Streams related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(18,1,'PHP-GTK related','PHP-GTK related','php','php-gtk-dev@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(19,0,'PDO related','PDO related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(20,1,'PHAR related','PHAR related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(21,0,'*Compile Issues','Compile Issues','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(22,21,'Compile Failure','Compile Failure','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(23,21,'Compile Warning','Compile Warning','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(24,0,'*Configuration Issues','Configuration Issues','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(25,24,'Dynamic loading','Dynamic loading','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(26,24,'PHP options/info functions','PHP options/info functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(27,24,'Safe Mode/open_basedir','Safe Mode/open_basedir related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(28,24,'Windows Installer','Windows Installer related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(29,0,'*Web Server problem','Web Server problem','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(30,29,'CGI/CLI related','CGI/CLI related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(31,29,'Apache related','Apache related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(32,29,'Apache2 related','Apache2 related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(33,29,'IIS related','IIS related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(34,29,'iPlanet related','iPlanet related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(35,29,'PWS related','PWS related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(36,29,'Servlet related','Servlet related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(37,29,'Other web server','Other web server','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(38,0,'*Calendar problems','Calendar problems','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(39,38,'Date/time related','Date/time related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(40,38,'Calendar related','Calendar related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(41,0,'*Compression related','Compression related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(42,41,'Bzip2 Related','Bzip2 Related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(43,41,'Zip Related','Zip Related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(45,41,'Zlib related','Zlib related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(46,0,'*Directory/Filesystem functions','Directory/Filesystem functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(47,46,'Directory function related','Directory function related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(48,46,'Filesystem function related','Filesystem function related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(49,0,'*Directory Services problems','Directory Services problems','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(50,49,'LDAP related','LDAP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(51,0,'*Database Functions','Database Functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(52,51,'Adabas-D related','Adabas-D related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(53,51,'dBase related','dBase related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(54,51,'DBM/DBA related','DBM/DBA related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(55,51,'DBX related','DBX related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(56,51,'FrontBase related','FrontBase related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(57,51,'Informix related','Informix related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(58,51,'Ingres II related','Ingres II related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(59,51,'InterBase related','InterBase related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(60,51,'mSQL related','mSQL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(61,51,'MSSQL related','MSSQL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(62,51,'MySQL related','MySQL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(63,51,'MySQLi related','MySQLi related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(64,51,'OCI8 related','OCI8 related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(65,51,'Oracle related','Oracle related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(66,51,'ODBC related','ODBC related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(67,51,'PostgreSQL related','PostgreSQL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(68,51,'Solid related','Solid related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(69,51,'SQLite related','SQLite related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(70,51,'Sybase (dblib) related','Sybase (dblib) related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(71,51,'Sybase-ct (ctlib) related','Sybase-ct (ctlib) related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(72,0,'*Data Exchange functions','Data Exchange functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(73,72,'JSON related','JSON related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(74,72,'WDDX related','WDDX related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(75,0,'*Extensibility Functions','Extensibility Functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(76,75,'COM related','COM related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(77,75,'Java related','Java related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(78,75,'ncurses related','ncurses related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(79,75,'Program Execution','Program Execution','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(80,75,'POSIX related','POSIX functions related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(81,75,'PCNTL related','PCNTL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(82,75,'Readline related','Readline related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(83,75,'Semaphore related','Semaphore related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(84,75,'Win32API related','Win32API related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(85,0,'*E-commerce functions','E-commerce functions','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(86,85,'Verisign Payflow Pro related','Verisign Payflow Pro related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(87,0,'*Graphics related','Graphics related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(88,87,'EXIF related','EXIF related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(89,87,'GD related','GD related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(90,87,'GetImageSize related','GetImageSize related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(91,87,'Ming related','Ming related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(92,0,'*Languages/Translation','Languages/Translation','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(93,92,'Gettext related','Gettext related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(94,92,'ICONV related','ICONV related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(95,92,'mbstring related','MBstring related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(96,92,'Recode related','Recode related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(97,0,'*Mail Related','Mail Related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(98,97,'IMAP related','IMAP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(99,97,'Mail related','mail function related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(100,0,'*Math Functions','Math Functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(101,100,'BC math related','BC math related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(102,100,'GNU MP related','GNU MP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(103,100,'Math related','Math related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(104,0,'*Encryption and hash functions','Encryption and hash functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(105,104,'mcrypt related','mcrypt related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(106,104,'hash related','hash related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(107,104,'mhash related','mhash related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(108,104,'OpenSSL related','OpenSSL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(109,0,'*Network Functions','Network Functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(110,109,'Network related','Network related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(111,109,'SNMP related','SNMP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(112,109,'FTP related','FTP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(113,109,'HTTP related','HTTP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(114,109,'Sockets related','Sockets related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(115,0,'*PDF functions','PDF functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(116,115,'ClibPDF related','ClibPDF related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(117,115,'FDF related','FDF related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(118,115,'PDF related','PDF related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(119,0,'*Programming Data Structures','Programming Data Structures','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(120,119,'Class/Object related','Class/Object related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(121,119,'Arrays related','Arrays related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(122,119,'Strings related','Strings related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(123,119,'Variables related','Variables related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(124,0,'*Regular Expressions','Regular Expressions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(125,124,'PCRE related','PCRE related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(126,124,'Regexps related','Regexps related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(127,0,'*Spelling functions','Spelling functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(128,127,'Pspell related','Pspell related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(130,176,'mnoGoSearch related','mnoGoSearch related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(131,0,'*XML functions','XML functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(132,131,'DOM XML related','DOM XML related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(133,131,'SOAP related','SOAP related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(134,131,'SimpleXML related','SimpleXML related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(135,131,'XML Reader','XML Reader','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(136,131,'XML Writer','XML Writer','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(137,131,'XML related','XML related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(138,131,'XMLRPC-EPI related','XMLRPC-EPI related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(139,131,'XSLT related','XSLT related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(140,0,'*URL Functions','URL Functions','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(141,140,'cURL related','cURL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(142,140,'URL related','URL related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(143,0,'*Unicode Issues','Unicode Issues','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(175,131,'Tidy','Tidy','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(145,143,'I18N and L10N related','I18N and L10N related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(146,143,'Unicode Engine related','Unicode Engine related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(147,0,'Unknown/Other Function','Unknown/Other Function','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(148,176,'*Function Specific','Function specific','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(158,176,'FrontPage related','FrontPage related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(159,176,'Installation problem','Installation problem','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(160,176,'Livedocs problem','Livedocs problem','php','doc-bugs@lists.example.com',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(161,176,'Misbehaving function','Misbehaving function','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(163,176,'Other','Other','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(164,176,'Parser error','Parser error','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(165,176,'PEAR related','PEAR related','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(167,176,'Sablotron XSL','Sablotron XSL','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(171,29,'FPM related','FPM related','php','php-bugs@lists.example.com,fat@example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(172,127,'Enchant related','Enchant related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(170,0,'Security related','Security related','php','security@example.com',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(176,0,'Disabled packages','--- Disabled ---','php','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(177,29,'Built-in web server','PHP built-in web server related','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(178,1,'Testing related','Testing related','php','php-bugs@lists.example.com,php-qa@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(179,0,'PECL','PECL','pecl','pecl-dev@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(180,179,'PECL website','PECL website','pecl','pecl-dev@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(181,179,'PECL bug system','PECL bug system','pecl','pecl-dev@lists.example.com',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(528,179,'hidef','hidef','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(527,179,'cairo_wrapper','cairo_wrapper','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(526,179,'swish','swish','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(525,179,'uploadprogress','uploadprogress','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(524,179,'PAM','PAM','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(523,179,'sam','sam','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(522,179,'WBXML','WBXML','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(521,179,'geoip','geoip','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(520,179,'Parse_Tree','Parse_Tree','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(519,179,'yp','yp','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(518,179,'mnogosearch','mnogosearch','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(517,179,'pdo_user','pdo_user','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(516,179,'axis2','axis2','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(515,179,'json','json','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(514,179,'SPL_Types','SPL_Types','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(513,179,'htscanner','htscanner','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(512,179,'stem','stem','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(511,179,'operator','operator','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(510,179,'ncurses','ncurses','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(509,179,'GDChart','GDChart','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(508,179,'phar','phar','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(507,179,'clucene','clucene','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(506,179,'hash','hash','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(505,179,'gnupg','gnupg','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(504,179,'rpmreader','rpmreader','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(503,179,'filter','filter','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(502,179,'timezonedb','timezonedb','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(501,179,'expect','expect','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(500,179,'mcve','mcve','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(499,179,'lchash','lchash','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(498,179,'win32ps','win32ps','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(497,179,'dbx','dbx','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(496,179,'SCA_SDO','SCA_SDO','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(495,179,'svn','svn','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(494,179,'mysql','mysql','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(493,179,'shape','shape','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(492,179,'domxml','domxml','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(491,179,'runkit','runkit','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(490,179,'intercept','intercept','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(489,179,'dio','dio','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(488,179,'pecl_http','pecl_http','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(487,179,'archive','archive','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(486,179,'colorer','colorer','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(485,179,'maxdb','maxdb','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(484,179,'ssh2','ssh2','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(483,179,'coin_acceptor','coin_acceptor','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(482,179,'imlib2','imlib2','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(481,179,'DBDO','DBDO','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(480,179,'esmtp','esmtp','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(479,179,'xattr','xattr','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(478,179,'PHPScript','PHPScript','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(477,179,'xmlwriter','xmlwriter','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(476,179,'docblock','docblock','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(475,179,'tk','tk','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(474,179,'translit','translit','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(473,179,'threads','threads','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(472,179,'pdflib','pdflib','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(471,179,'intl','intl','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(470,179,'rar','rar','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(469,179,'PDO_FIREBIRD','PDO_FIREBIRD','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(468,179,'id3','id3','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(467,179,'BLENC','BLENC','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(466,179,'PDO_MYSQL','PDO_MYSQL','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(465,179,'PDO_PGSQL','PDO_PGSQL','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(464,179,'newt','newt','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(463,179,'PDO_ODBC','PDO_ODBC','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(462,179,'PDO_OCI','PDO_OCI','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(461,179,'PDO','PDO','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(460,179,'perforce','perforce','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(459,179,'PDO_SQLITE','PDO_SQLITE','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(458,179,'parsekit','parsekit','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(457,179,'xmlReader','xmlReader','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(456,179,'idn','idn','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(455,179,'panda','panda','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(454,179,'yaz','yaz','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(453,179,'Valkyrie','Valkyrie','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(452,179,'mdbtools','mdbtools','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(451,179,'PDO_IBM','PDO_IBM','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(450,179,'perl','perl','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(449,179,'enchant','enchant','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(448,179,'date_time','date_time','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(447,179,'fann','fann','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(446,179,'ps','ps','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(445,179,'Fileinfo','Fileinfo','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(444,179,'memcache','memcache','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(443,179,'crack','crack','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(442,179,'statgrab','statgrab','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(441,179,'ffi','ffi','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(440,179,'POP3','POP3','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(439,179,'xdiff','xdiff','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(438,179,'FreeImage','FreeImage','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(437,179,'ingres','ingres','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(436,179,'oci8','oci8','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(435,179,'ibm_db2','ibm_db2','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(434,179,'SPL','SPL','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(433,179,'cvsclient','cvsclient','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(432,179,'Paradox','Paradox','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(605,1,'opcache','Opcache','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(431,179,'win32std','win32std','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(430,179,'tcpwrap','tcpwrap','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(429,179,'lzf','lzf','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(428,179,'vld','vld','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(604,179,'udis86','udis86','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(427,179,'oggvorbis','oggvorbis','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(426,179,'tidy','tidy','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(603,179,'ares','ares','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(425,179,'sasl','sasl','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(424,179,'PDO_DBLIB','PDO_DBLIB','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(602,179,'pthreads','pthreads','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(423,179,'APC','APC','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(422,179,'DTrace','DTrace','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(601,179,'xcommerce','xcommerce','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(421,179,'PECL_Gen','PECL_Gen','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(600,179,'msgpack','msgpack','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(420,179,'html_parse','html_parse','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(599,179,'leveldb','leveldb','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(419,179,'uuid','uuid','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(418,179,'dbplus','dbplus','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(598,179,'yar','yar','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(416,179,'kadm5','kadm5','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(415,179,'cyrus','cyrus','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(414,179,'bz2','bz2','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(413,179,'zip','zip','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(412,179,'mqseries','mqseries','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(597,179,'uri_template','uri_template','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(411,179,'WinBinder','WinBinder','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(596,179,'Trader','Trader','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(410,179,'scream','scream','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(409,179,'SQLite','SQLite','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(408,179,'event','event','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(407,179,'fribidi','fribidi','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(406,179,'isis','isis','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(595,179,'Bitset','Bitset','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(405,179,'PDO_INFORMIX','PDO_INFORMIX','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(404,179,'cybercash','cybercash','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(403,179,'stats','stats','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(593,179,'lapack','lapack','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(592,179,'taint','taint','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(402,179,'mono','mono','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(401,179,'mailparse','mailparse','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(400,179,'python','python','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(591,179,'sundown','sundown','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(399,179,'vpopmail','vpopmail','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(398,179,'ecasound','ecasound','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(590,179,'pcsc','pcsc','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(397,179,'spread','spread','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(396,179,'apd','apd','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(395,179,'radius','radius','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(394,179,'bcompiler','bcompiler','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(589,179,'unicodestring','unicodestring','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(393,179,'sphinx','sphinx','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(392,179,'imagick','imagick','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(588,179,'cld','cld','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(391,179,'xmms','xmms','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(587,179,'eio','eio','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(390,179,'openal','openal','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(389,179,'syck','syck','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(586,179,'meta','meta','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(388,179,'big_int','big_int','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(387,179,'printer','printer','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(386,179,'chdb','chdb','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(585,179,'varnish','varnish','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(385,179,'igbinary','igbinary','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(384,179,'KTaglib','KTaglib','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(383,179,'optimizer','optimizer','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(529,179,'haru','haru','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(530,179,'amfext','amfext','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(531,179,'lua','lua','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(532,179,'bbcode','bbcode','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(533,179,'yami','yami','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(534,179,'tdb','tdb','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(535,179,'funcall','funcall','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(536,179,'params','params','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(537,179,'PHK','PHK','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(538,179,'automap','automap','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(539,179,'inclued','inclued','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(540,179,'sqlite3','sqlite3','pecl','',1);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(541,179,'xrange','xrange','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(542,179,'mysqlnd_uh','mysqlnd_uh','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(543,179,'informix','informix','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(544,179,'fpdf','fpdf','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(545,179,'ming','ming','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(546,179,'cairo','cairo','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(547,179,'inotify','inotify','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(548,179,'oauth','oauth','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(549,179,'mogilefs','mogilefs','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(550,179,'DBus','DBus','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(551,179,'libevent','libevent','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(552,179,'fuse','fuse','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(553,179,'memtrack','memtrack','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(554,179,'memcached','memcached','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(555,179,'proctitle','proctitle','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(556,179,'spidermonkey','spidermonkey','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(557,179,'mongo','mongo','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(558,179,'markdown','markdown','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(559,179,'xhprof','xhprof','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(560,179,'tokyo_tyrant','tokyo_tyrant','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(561,179,'bloomy','bloomy','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(562,179,'PDO_4D','PDO_4D','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(563,179,'gupnp','gupnp','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(564,179,'gmagick','gmagick','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(565,179,'gearman','gearman','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(566,179,'xslcache','xslcache','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(567,179,'drizzle','drizzle','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(568,179,'solr','solr','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(569,179,'WinCache','WinCache','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(570,179,'stomp','stomp','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(571,179,'APM','APM','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(572,179,'yaml','yaml','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(573,179,'amqp','amqp','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(574,179,'mysqlnd_qc','mysqlnd_qc','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(575,179,'ssdeep','ssdeep','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(576,179,'mysqlnd_ms','mysqlnd_ms','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(577,179,'dbase','dbase','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(578,179,'win32service','win32service','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(579,179,'rrd','rrd','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(580,179,'v8js','v8js','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(581,179,'sqlsrv','sqlsrv','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(582,179,'pdo_sqlsrv','pdo_sqlsrv','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(583,179,'Weakref','Weakref','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(584,179,'yaf','yaf','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(606,147,'phpdbg','phpdbg','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(607,19,'PDO Core','PDO Core','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(608,19,'PDO DBlib','PDO DBlib','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(609,19,'PDO ODBC','PDO ODBC','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(610,19,'PDO MySQL','PDO MySQL','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(611,19,'PDO OCI','PDO OCI','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(612,19,'PDO Firebird','PDO Firebird','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(613,19,'PDO SQLite','PDO SQLite','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(614,19,'PDO PgSQL','PDO PgSQL','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(618,1,'JIT','JIT (Just In Time compilation)','php','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(616,179,'mysqlnd_memcache','mysqlnd_memcache','pecl','',0);

INSERT INTO `bugdb_pseudo_packages` (`id`, `parent`, `name`, `long_name`, `project`, `list_email`, `disabled`)
VALUES
(617,179,'mongodb','mongodb','pecl','',0);

