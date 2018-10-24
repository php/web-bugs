-- ts1     bug created date
-- ts2     bug last updated date
-- passwd  user password

CREATE TABLE bugdb (
  id int(8) NOT NULL auto_increment,
  package_name varchar(80) default NULL,
  bug_type varchar(32) NOT NULL default 'Bug',
  email varchar(40) NOT NULL default '',
  reporter_name varchar(80) default '',
  sdesc varchar(80) NOT NULL default '',
  ldesc text NOT NULL,
  php_version varchar(100) default NULL,
  php_os varchar(32) default NULL,
  status varchar(16) default NULL,
  ts1 datetime default NULL,
  ts2 datetime default NULL,
  assign varchar(20) default NULL,
  passwd varchar(64) default NULL,
  registered tinyint(1) NOT NULL default '0',
  block_user_comment char(1) default 'N',
  cve_id varchar(15) default NULL,
  private char(1) default 'N',
  visitor_ip varbinary(16) NOT NULL,
  PRIMARY KEY (id),
  KEY php_version (php_version(1)),
  KEY status (status),
  KEY package_name (package_name),
  FULLTEXT KEY email (email,sdesc,ldesc)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1;

CREATE TABLE bugdb_comments (
  id int(8) NOT NULL auto_increment,
  bug int(8) NOT NULL default '0',
  email varchar(40) NOT NULL default '',
  reporter_name varchar(80) default '',
  ts datetime NOT NULL default CURRENT_TIMESTAMP,
  comment text NOT NULL,
  comment_type varchar(10) default 'comment',
  visitor_ip varbinary(16) NOT NULL,
  PRIMARY KEY  (id),
  KEY bug (bug,id,ts),
  FULLTEXT KEY comment (comment)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 PACK_KEYS=1;

CREATE TABLE bugdb_obsoletes_patches (
  bugdb_id int(8) NOT NULL,
  patch varchar(80) NOT NULL,
  revision int(8) NOT NULL,
  obsolete_patch varchar(80) NOT NULL,
  obsolete_revision int(8) NOT NULL,
  PRIMARY KEY  (bugdb_id,patch,revision,obsolete_patch,obsolete_revision)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_patchtracker (
  bugdb_id int(8) NOT NULL,
  patch varchar(80) NOT NULL,
  revision int(8) NOT NULL,
  developer varchar(40) NOT NULL,
  PRIMARY KEY  (bugdb_id,patch,revision)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_pseudo_packages (
  id int(11) NOT NULL auto_increment,
  parent int(11) NOT NULL default '0',
  name varchar(80) NOT NULL default '',
  long_name varchar(100) NOT NULL default '',
  project varchar(40) NOT NULL default '',
  list_email varchar(80) NOT NULL default '',
  disabled tinyint(1) NOT NULL default 0, # Disabled == read-only (no new reports in these!)
  PRIMARY KEY (id),
  UNIQUE KEY (name, project)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_resolves (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  status varchar(16) default NULL,
  title varchar(100) NOT NULL,
  message text NOT NULL,
  project varchar(40) NOT NULL default '',
  package_name varchar(80) default NULL,
  webonly tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_subscribe (
  bug_id int(8) NOT NULL default '0',
  email varchar(40) NOT NULL default '',
  unsubscribe_date int(11) default NULL,
  unsubscribe_hash varchar(80) default '',
  PRIMARY KEY  (bug_id,email),
  KEY unsubscribe_hash (unsubscribe_hash)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- score's value can be 1 through 5
CREATE TABLE bugdb_votes (
  bug int(8) NOT NULL default '0',
  ts timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  ip int(10) unsigned NOT NULL default '0',
  score int(3) NOT NULL default '0',
  reproduced int(1) NOT NULL default '0',
  tried int(1) NOT NULL default '0',
  sameos int(1) default NULL,
  samever int(1) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_pulls (
  bugdb_id int(8) NOT NULL default '0',
  github_repo varchar(255) NOT NULL,
  github_pull_id int NOT NULL,
  github_title varchar(255) NOT NULL,
  developer varchar(40) NOT NULL,
  github_html_url varchar(255) NOT NULL,
  PRIMARY KEY (bugdb_id, github_repo, github_pull_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
