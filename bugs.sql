# MySQL dump 4.0
#
# Host: localhost    Database: php3
#--------------------------------------------------------
DROP table bugdb;

CREATE TABLE bugdb (
  id int(8) NOT NULL AUTO_INCREMENT,
  bug_type char(32),
  email varchar(40) NOT NULL,
  sdesc varchar(80) NOT NULL,
  ldesc text NOT NULL,
  php_version char(16),
  php_os varchar(32),
  status varchar(16),
  ts1 datetime, # bug created date
  ts2 datetime, # bug last updated date
  dev_id varchar(16),# developer who last commented
  assign varchar(16),
  passwd varchar(20),# user password
  PRIMARY KEY (id),
  FULLTEXT (email,sdesc,ldesc)
);

CREATE TABLE bugdb_comments (
  id int(8) NOT NULL AUTO_INCREMENT,
  bug int(8) NOT NULL,
  email varchar(40) NOT NULL,
  ts datetime NOT NULL,
  comment text NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT (comment)
);
