# MySQL dump 4.0
#
# Host: localhost    Database: php3
#--------------------------------------------------------
DROP table IF EXISTS bugdb;

CREATE TABLE bugdb (
  id int(8) NOT NULL AUTO_INCREMENT,
  bug_type char(32),
  email varchar(40) NOT NULL,
  sdesc varchar(80) NOT NULL,
  ldesc text NOT NULL,
  php_version char(100),
  php_os varchar(32),
  status varchar(16),
  ts1 datetime, # bug created date
  ts2 datetime, # bug last updated date
  assign varchar(16),
  passwd varchar(20),# user password
  PRIMARY KEY (id),
  INDEX (php_version(1)),
  FULLTEXT (email,sdesc,ldesc)
);

DROP TABLE IF EXISTS bugdb_comments;

CREATE TABLE bugdb_comments (
  id int(8) NOT NULL AUTO_INCREMENT,
  bug int(8) NOT NULL,
  email varchar(40) NOT NULL,
  ts datetime NOT NULL,
  comment text NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT (comment)
);

DROP TABLE IF EXISTS bugdb_votes;

CREATE TABLE bugdb_votes (
  bug int(8) NOT NULL,
  ts timestamp NOT NULL,
  ip int unsigned NOT NULL,
  score int(3) NOT NULL, /* 1-5 */
  reproduced int(1) NOT NULL,
  tried int(1) NOT NULL,
  sameos int(1),
  samever int(1)
);
