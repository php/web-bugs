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
ALTER TABLE bugdb ADD handle varchar(20) NOT NULL default '' AFTER bug_type;
ALTER TABLE bugdb ADD reporter_name varchar(80) default '' AFTER email;
ALTER TABLE bugdb ADD package_version varchar(100) default NULL AFTER ldesc;
ALTER TABLE bugdb ADD registered tinyint(1) NOT NULL default '0' AFTER passwd;
ALTER TABLE bugdb ADD KEY package_version (package_version(1));
ALTER TABLE bugdb ADD KEY package_name (package_name);

# bugdb_comments
ALTER TABLE bugdb_comments MODIFY bug int(8) NOT NULL default '0';
ALTER TABLE bugdb_comments MODIFY email varchar(40) NOT NULL default '';
ALTER TABLE bugdb_comments ADD handle varchar(20) NOT NULL default '' AFTER email;
ALTER TABLE bugdb_comments ADD reporter_name varchar(80) default '' AFTER handle;
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
CREATE TABLE `bugdb_subscribe` (
  bug_id int(8) NOT NULL default '0',
  email varchar(40) NOT NULL default '',
  unsubscribe_date int(11) default NULL,
  unsubscribe_hash varchar(80) default '',
  PRIMARY KEY (bug_id, email),
  KEY (unsubscribe_hash)
) TYPE=MyISAM;

CREATE TABLE bugdb_roadmap_link (
  id int(8) NOT NULL auto_increment,
  roadmap_id int(8) NOT NULL default 0,
  PRIMARY KEY (id, roadmap_id)
);

CREATE TABLE bugdb_roadmap (
  id int(8) NOT NULL auto_increment,
  package varchar(80) NOT NULL default '',
  roadmap_version varchar(100) NOT NULL,
  releasedate datetime NOT NULL default '0000-00-00',
  description text NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY (package, roadmap_version)
);

CREATE TABLE bugdb_patchtracker (
  bugdb_id int(8) NOT NULL,
  patch varchar(40) NOT NULL,
  revision int(8) NOT NULL,
  developer varchar(20) NOT NULL,
  PRIMARY KEY (bugdb_id, patch, revision)
);

CREATE TABLE bugdb_obsoletes_patches (
  bugdb_id int(8) NOT NULL,
  patch varchar(40) NOT NULL,
  revision int(8) NOT NULL,
  obsolete_patch varchar(40) NOT NULL,
  obsolete_revision int(8) NOT NULL,
  PRIMARY KEY (bugdb_id, patch, revision, obsolete_patch, obsolete_revision)
);

CREATE TABLE bug_account_request (
  id INT NOT NULL AUTO_INCREMENT,
  created_on DATE NOT NULL,
  handle VARCHAR(20) NOT NULL,
  salt CHAR(32) NOT NULL,
  email VARCHAR(65) NOT NULL,
  PRIMARY KEY(id)
);

CREATE TABLE bugdb_resolves (
  id INT NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  status varchar(16) default NULL,
  title varchar(100) NOT NULL,
  message text NOT NULL,
  project varchar(40) NOT NULL default '',
  package_name varchar(80) default NULL,
  PRIMARY KEY (id)
);

CREATE TABLE bugdb_pseudo_packages (
  id INT NOT NULL AUTO_INCREMENT,
  parent INT NOT NULL default '0',
  name varchar(80) NOT NULL default '',
  long_name varchar(100) NOT NULL default '',
  project varchar(40) NOT NULL default '',
  disabled tinyint(1) NOT NULL default 0, # Disabled == read-only (no new reports in these!)
  PRIMARY KEY (id),
  UNIQUE KEY (name, project)
);

# Default pseudo packages (common for all projects)
INSERT INTO bugdb_pseudo_packages SET id = '1', parent = '0', name = 'Web Site',   long_name = 'Web Site',   project = '';
INSERT INTO bugdb_pseudo_packages SET id = '2', parent = '1', name = 'Bug System', long_name = 'Bug System', project = '';

# PEAR specific pseudo packages
INSERT INTO bugdb_pseudo_packages SET id = '3', parent = '1', name = 'PEPr', long_name = 'PEPr', project = 'pear';
INSERT INTO bugdb_pseudo_packages SET id = '4', parent = '0', name = 'Documentation', long_name = 'Documentation', project = 'pear';

#
# This table is copy of pearweb/sql/package.sql
#
CREATE TABLE packages (
  id int(11) NOT NULL default '0',
  name varchar(80) NOT NULL default '',
  category int(11) default NULL,
  stablerelease varchar(20) default NULL,
  develrelease varchar(20) default NULL,
  license varchar(50) default NULL,
  summary text,
  description text,
  homepage varchar(255) default NULL,
  package_type enum('pear','pecl') NOT NULL default 'pear',
  doc_link varchar(255) default NULL,
  cvs_link varchar(255) default NULL,
  approved tinyint(4) NOT NULL default '0',
  wiki_area tinyint(1) NOT NULL default '0',
  blocktrackbacks tinyint(1) NOT NULL default '0',
  unmaintained tinyint(1) NOT NULL default '0',
  newpk_id int(11) default NULL,
  newpackagename varchar(100) default NULL,
  newchannel varchar(255) default NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name),
  KEY category (category)
);

#
# This table is copy of pearweb/sql/users.sql
#
CREATE TABLE users (
  handle varchar(20) NOT NULL default '',
  password varchar(64) default NULL,
  name varchar(100) default NULL,
  email varchar(100) default NULL,
  homepage varchar(255) default NULL,
  created datetime default NULL,
  createdby varchar(20) default NULL,
  lastlogin datetime default NULL,
  showemail tinyint(1) default NULL,
  registered tinyint(1) default NULL,
  admin tinyint(1) default NULL,
  userinfo text default NULL,
  pgpkeyid varchar(20) default NULL,
  pgpkey text,
  wishlist varchar(255) NOT NULL default '',
  active tinyint(1) NOT NULL default 1,
  from_site varchar(4) NOT NULL default '',
  PRIMARY KEY  (handle),
  KEY handle (handle,registered),
  KEY pgpkeyid (pgpkeyid),
  KEY email (email(25)),
  UNIQUE KEY email_u (email)
);

#
# This table is copy of pearweb/sql/maintains.sql
#
CREATE TABLE maintains (
  handle varchar(20) NOT NULL default '',
  package int(11) NOT NULL default '0',  
  role enum('lead','developer','contributor','helper') NOT NULL default 'lead',
  active tinyint(4) NOT NULL default '1',
  PRIMARY KEY  (handle,package)
);
          
