#
# This file is for converting bugs.php.net DB to be compatible with the pecl/pear bug db.
#

# bugdb 
ALTER TABLE bugdb DROP project;
ALTER TABLE bugdb DROP severity;
ALTER TABLE bugdb DROP dev_id;
ALTER TABLE bugdb DROP bugpack_id;
ALTER TABLE bugdb DROP milestone_id;
ALTER TABLE bugdb DROP KEY bug_type;
ALTER TABLE bugdb MODIFY email varchar(40) NOT NULL default '';
ALTER TABLE bugdb MODIFY sdesc varchar(80) NOT NULL default '';
ALTER TABLE bugdb MODIFY php_version varchar(100) default NULL;
ALTER TABLE bugdb MODIFY php_os varchar(32) default NULL;
ALTER TABLE bugdb MODIFY status varchar(16) default NULL;
ALTER TABLE bugdb MODIFY ts1 datetime default NULL;
ALTER TABLE bugdb MODIFY ts2 datetime default NULL;
ALTER TABLE bugdb MODIFY assign varchar(20) default NULL;
ALTER TABLE bugdb MODIFY passwd varchar(20) default NULL;
ALTER TABLE bugdb CHANGE bug_type package_name varchar(80) default NULL;
ALTER TABLE bugdb ADD bug_type varchar(32) NOT NULL default 'Bug' AFTER package_name;
ALTER TABLE bugdb ADD reporter_name varchar(80) default '' AFTER email;
ALTER TABLE bugdb ADD registered tinyint(1) NOT NULL default '0' AFTER passwd;
ALTER TABLE bugdb ADD KEY package_name (package_name);

# bugdb_comments
ALTER TABLE bugdb_comments MODIFY bug int(8) NOT NULL default '0';
ALTER TABLE bugdb_comments MODIFY email varchar(40) NOT NULL default '';
ALTER TABLE bugdb_comments ADD reporter_name varchar(80) default '' AFTER email;
ALTER TABLE bugdb_comments ADD comment_type varchar(10) default 'comment' AFTER comment;
ALTER TABLE bugdb_comments ADD INDEX bug (bug, id, ts);

# bugdb_votes
ALTER TABLE bugdb_votes MODIFY bug int(8) NOT NULL default '0';
ALTER TABLE bugdb_votes MODIFY ts timestamp(14) NOT NULL;
ALTER TABLE bugdb_votes MODIFY ip int(10) unsigned NOT NULL default '0';
ALTER TABLE bugdb_votes MODIFY score int(3) NOT NULL default '0';
ALTER TABLE bugdb_votes MODIFY reproduced int(1) NOT NULL default '0';
ALTER TABLE bugdb_votes MODIFY tried int(1) NOT NULL default '0';
ALTER TABLE bugdb_votes MODIFY sameos int(1) default NULL;
ALTER TABLE bugdb_votes MODIFY samever int(1) default NULL;

# Drop unused tables
DROP TABLE IF EXISTS bugdb_milestones;
DROP TABLE IF EXISTS bugdb_packs;

# New tables not in phpbugsdb
CREATE TABLE bugdb_subscribe (
  bug_id int(8) NOT NULL default '0',
  email varchar(40) NOT NULL default '',
  unsubscribe_date int(11) default NULL,
  unsubscribe_hash varchar(80) default '',
  PRIMARY KEY (bug_id, email),
  KEY (unsubscribe_hash)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_patchtracker (
  bugdb_id int(8) NOT NULL,
  patch varchar(80) NOT NULL,
  revision int(8) NOT NULL,
  developer varchar(20) NOT NULL,
  PRIMARY KEY (bugdb_id, patch, revision)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_obsoletes_patches (
  bugdb_id int(8) NOT NULL,
  patch varchar(80) NOT NULL,
  revision int(8) NOT NULL,
  obsolete_patch varchar(40) NOT NULL,
  obsolete_revision int(8) NOT NULL,
  PRIMARY KEY (bugdb_id, patch, revision, obsolete_patch, obsolete_revision)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_resolves (
  id INT NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  status varchar(16) default NULL,
  title varchar(100) NOT NULL,
  message text NOT NULL,
  project varchar(40) NOT NULL default '',
  package_name varchar(80) default NULL,
  webonly tinyint(1) NOT NULL default '0',
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE bugdb_pseudo_packages (
  id INT NOT NULL AUTO_INCREMENT,
  parent INT NOT NULL default '0',
  name varchar(80) NOT NULL default '',
  long_name varchar(100) NOT NULL default '',
  project varchar(40) NOT NULL default '',
  list_email varchar(80) NOT NULL default '',
  disabled tinyint(1) NOT NULL default 0, # Disabled == read-only (no new reports in these!)
  PRIMARY KEY (id),
  UNIQUE KEY (name, project)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

#
# Convert all tables to utf8
#
ALTER TABLE bugdb DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_comments DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_obsoletes_patches DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_patchtracker DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_pseudo_packages DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_resolves DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_subscribe DEFAULT CHARSET=utf8;
ALTER TABLE bugdb_votes DEFAULT CHARSET=utf8;

#
# Set bug type to be what it supposed to be here
#
UPDATE bugdb SET bug_type = 'Feature/Change Request' WHERE package_name = 'Feature/Change Request';
UPDATE bugdb SET bug_type = 'Documentation Problem' WHERE package_name = 'Documentation problem';
