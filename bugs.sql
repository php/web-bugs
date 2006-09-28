# MySQL dump 4.0
#
# Host: localhost    Database: php3
#--------------------------------------------------------
DROP table IF EXISTS bugdb;

CREATE TABLE bugdb (
  id int(8) NOT NULL AUTO_INCREMENT,
  project tinyint(4) NOT NULL default '1',
  bug_type varchar(32) NOT NULL default '',
  severity tinyint(4) NOT NULL default '0',
  email varchar(40) NOT NULL,
  sdesc varchar(80) NOT NULL,
  ldesc text NOT NULL,
  php_version char(100),
  php_os varchar(32),
  status varchar(16),
  ts1 datetime, # bug created date
  ts2 datetime, # bug last updated date
  dev_id varchar(16) default NULL,
  assign varchar(16),
  passwd varchar(20),# user password
  bugpack_id int(5) default NULL,
  milestone_id int(11) default NULL,
  PRIMARY KEY  (id),
  KEY php_version (php_version(1)),
  KEY bug_type (bug_type),
  KEY status (status),
  FULLTEXT KEY email (email,sdesc,ldesc)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1;

DROP TABLE IF EXISTS bugdb_comments;

CREATE TABLE bugdb_comments (
  id int(8) NOT NULL AUTO_INCREMENT,
  bug int(8) NOT NULL,
  email varchar(40) NOT NULL,
  ts datetime NOT NULL default '0000-00-00 00:00:00',
  comment text NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT KEY comment (comment)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=1;

DROP TABLE IF EXISTS bugdb_votes;

CREATE TABLE bugdb_votes (
  bug int(8) NOT NULL,
  ts timestamp NOT NULL,
  ip int(10) default NULL,
  score int(3) NOT NULL, /* 1-5 */
  reproduced int(1) NOT NULL,
  tried int(1) NOT NULL,
  sameos int(1),
  samever int(1)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
