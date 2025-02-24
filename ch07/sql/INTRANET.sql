# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Feb 14, 2003 at 09:02 AM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `INTRANET`
# --------------------------------------------------------

#
# Table structure for table `LD_CATEGORY`
#

CREATE TABLE IF NOT EXISTS LD_CATEGORY (
  CAT_ID int(11) NOT NULL auto_increment,
  CAT_NAME varchar(127) NOT NULL default '',
  CAT_DESC text,
  CAT_ORDER int(11) NOT NULL default '0',
  PRIMARY KEY  (CAT_ID),
  UNIQUE KEY CAT_ORDER (CAT_ORDER),
  UNIQUE KEY CAT_NAME (CAT_NAME)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `LD_CAT_PUBLISHER`
#

CREATE TABLE IF NOT EXISTS LD_CAT_PUBLISHER (
  CAT_ID int(11) NOT NULL default '0',
  PUBLISHER_ID int(11) NOT NULL default '0',
  PRIMARY KEY  (CAT_ID,PUBLISHER_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `LD_CAT_VIEWER`
#

CREATE TABLE IF NOT EXISTS LD_CAT_VIEWER (
  CAT_ID int(11) NOT NULL default '0',
  VIEWER_ID int(11) NOT NULL default '0',
  PRIMARY KEY  (CAT_ID,VIEWER_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `LD_DOCUMENT`
#

CREATE TABLE IF NOT EXISTS LD_DOCUMENT (
  DOC_ID mediumint(9) NOT NULL auto_increment,
  CAT_ID int(11) NOT NULL default '0',
  HEADING varchar(127) NOT NULL default '',
  BODY mediumtext NOT NULL,
  PUBLISH_DATE bigint(20) NOT NULL default '0',
  PRIMARY KEY  (DOC_ID),
  UNIQUE KEY HEADING (HEADING)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `LD_RESPONSE`
#

CREATE TABLE IF NOT EXISTS LD_RESPONSE (
  RESPONSE_ID int(11) NOT NULL auto_increment,
  RESPONDER varchar(127) NOT NULL default '',
  SUBJECT varchar(127) NOT NULL default '',
  RATE int(11) NOT NULL default '0',
  COMMENT text NOT NULL,
  DOC_ID mediumint(9) NOT NULL default '0',
  RESPONSE_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (RESPONSE_ID),
  UNIQUE KEY RESPONSE_TS (RESPONSE_TS)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `LD_TRACK`
#

CREATE TABLE IF NOT EXISTS LD_TRACK (
  DOC_ID mediumint(9) NOT NULL default '0',
  UID int(4) NOT NULL default '0',
  VISIT_TS bigint(4) NOT NULL default '0',
  PRIMARY KEY  (DOC_ID,UID,VISIT_TS)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `MESSAGE`
#

CREATE TABLE IF NOT EXISTS MESSAGE (
  MSG_ID bigint(20) NOT NULL auto_increment,
  MSG_TITLE varchar(127) NOT NULL default '',
  MSG_CONTENTS text NOT NULL,
  MSG_DATE bigint(20) NOT NULL default '0',
  AUTHOR_ID int(11) NOT NULL default '0',
  MSG_TYPE int(11) NOT NULL default '0',
  FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (MSG_ID),
  UNIQUE KEY FLAG (FLAG)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `MSG_TRACK`
#

CREATE TABLE IF NOT EXISTS MSG_TRACK (
  USER_ID int(11) NOT NULL default '0',
  MSG_ID bigint(20) NOT NULL default '0',
  READ_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (USER_ID,MSG_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `MSG_VIEWER`
#

CREATE TABLE IF NOT EXISTS MSG_VIEWER (
  MSG_ID bigint(11) NOT NULL default '0',
  VIEWER_ID int(11) NOT NULL default '0',
  PRIMARY KEY  (MSG_ID,VIEWER_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `THEME`
#

CREATE TABLE IF NOT EXISTS THEME (
  THEME_ID tinyint(4) NOT NULL default '0',
  THEME_NAME varchar(128) NOT NULL default '',
  PRIMARY KEY  (THEME_ID)
) TYPE=MyISAM COMMENT='Theme table';
# --------------------------------------------------------

#
# Table structure for table `USER_DETAILS`
#

CREATE TABLE IF NOT EXISTS USER_DETAILS (
  USER_ID bigint(20) NOT NULL default '0',
  FIRST varchar(20) NOT NULL default '',
  LAST varchar(20) NOT NULL default '',
  ADDRESS1 varchar(128) NOT NULL default '',
  ADDRESS2 varchar(128) default NULL,
  CITY varchar(20) default NULL,
  STATE bigint(2) NOT NULL default '0',
  ZIPCODE varchar(10) NOT NULL default '',
  COUNTRY varchar(20) NOT NULL default '',
  PHONE varchar(30) default NULL,
  START_DATE bigint(128) NOT NULL default '0',
  PRIMARY KEY  (USER_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `USER_PREFERENCE`
#

CREATE TABLE IF NOT EXISTS USER_PREFERENCE (
  USER_ID bigint(20) NOT NULL default '0',
  PREFERENCE_ID tinyint(4) NOT NULL default '0',
  VALUE varchar(128) NOT NULL default ''
) TYPE=MyISAM COMMENT='USER PREFERENCE INFORMATIONS';

#
# Dumping data for table `THEME`
#

INSERT INTO THEME VALUES (3, 'standard');
INSERT INTO THEME VALUES (2, 'wheat');
INSERT INTO THEME VALUES (1, 'blue');
