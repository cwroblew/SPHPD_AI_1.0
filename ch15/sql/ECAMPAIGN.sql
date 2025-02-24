# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Jun 07, 2002 at 09:11 PM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `ECAMPAIGN`
# --------------------------------------------------------

#
# Table structure for table `ASSEMBLY`
#

CREATE TABLE IF NOT EXISTS ASSEMBLY (
  LIST_ID int(11) NOT NULL default '0',
  REC_ID int(11) NOT NULL default '0',
  FIRST varchar(255) default NULL,
  LAST varchar(255) default NULL,
  EMAIL varchar(255) NOT NULL default '',
  AGE varchar(255) default NULL,
  INCOME varchar(255) default NULL,
  SEX varchar(255) default NULL
) TYPE=MyISAM;

#
# Dumping data for table `ASSEMBLY`
#

# --------------------------------------------------------

#
# Table structure for table `BOUNCED`
#

CREATE TABLE IF NOT EXISTS BOUNCED (
  CAMPAIGN_ID tinyint(4) NOT NULL default '0',
  LIST_ID tinyint(4) NOT NULL default '0',
  REC_ID tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (LIST_ID,REC_ID,CAMPAIGN_ID)
) TYPE=MyISAM;

#
# Dumping data for table `BOUNCED`
#

# --------------------------------------------------------

#
# Table structure for table `CAMPAIGN`
#

CREATE TABLE IF NOT EXISTS CAMPAIGN (
  CAMPAIGN_ID int(11) NOT NULL auto_increment,
  NAME varchar(127) NOT NULL default '',
  LIST_ID int(11) NOT NULL default '0',
  MSG_ID int(11) NOT NULL default '0',
  STATUS int(4) NOT NULL default '0',
  PRIMARY KEY  (CAMPAIGN_ID)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table `ECAMPAIGN_EXECUTION`
#

CREATE TABLE IF NOT EXISTS ECAMPAIGN_EXECUTION (
  EXEC_ID int(11) NOT NULL auto_increment,
  CAMPAIGN_ID int(11) NOT NULL default '0',
  CAMPAIGN_TS timestamp(14) NOT NULL,
  PRIMARY KEY  (EXEC_ID),
  UNIQUE KEY CAMPAIGN_TS (CAMPAIGN_TS)
) TYPE=MyISAM;

#
# Table structure for table `LIST`
#

CREATE TABLE IF NOT EXISTS LIST (
  LIST_ID int(11) NOT NULL auto_increment,
  NAME varchar(127) NOT NULL default '',
  DB_HOST varchar(127) NOT NULL default '',
  DB_USER varchar(127) NOT NULL default '',
  DB_PASSWD varchar(127) NOT NULL default '',
  DB_TYPE varchar(127) NOT NULL default '',
  DB_NAME varchar(127) NOT NULL default '',
  DB_TABLE varchar(127) NOT NULL default '',
  LIMIT_CONDITION varchar(255) default NULL,
  CREATE_TS bigint(20) NOT NULL default '0',
  CREATOR_ID int(11) NOT NULL default '0',
  CHECK_FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (LIST_ID),
  UNIQUE KEY CHECK_FLAG (CHECK_FLAG),
  UNIQUE KEY NAME (NAME)
) TYPE=MyISAM;

#
#
# Table structure for table `LIST_FIELD_MAP`
#

CREATE TABLE IF NOT EXISTS LIST_FIELD_MAP (
  LIST_ID int(11) NOT NULL default '0',
  REC_ID varchar(127) NOT NULL default '',
  FIRST varchar(127) default NULL,
  LAST varchar(127) default NULL,
  EMAIL varchar(127) NOT NULL default '',
  AGE varchar(127) default NULL,
  INCOME varchar(127) default NULL,
  SEX varchar(127) default NULL,
  PRIMARY KEY  (LIST_ID)
) TYPE=MyISAM;

#
# Table structure for table `MESSAGE`
#

CREATE TABLE IF NOT EXISTS MESSAGE (
  MSG_ID tinyint(4) NOT NULL auto_increment,
  NAME varchar(127) NOT NULL default '',
  BODY text NOT NULL,
  CREATE_TS bigint(20) NOT NULL default '0',
  CREATOR_ID int(11) NOT NULL default '0',
  PRIMARY KEY  (MSG_ID),
  UNIQUE KEY NAME (NAME)
) TYPE=MyISAM;

#
# Table structure for table `MESSAGE_HDRS`
#

CREATE TABLE IF NOT EXISTS MESSAGE_HDRS (
  MSG_ID int(11) NOT NULL default '0',
  HDR_ID int(11) NOT NULL default '0',
  HDR_VALUE varchar(127) NOT NULL default ''
) TYPE=MyISAM;

#
# Table structure for table `TRACK`
#

CREATE TABLE IF NOT EXISTS TRACK (
  ID int(11) NOT NULL auto_increment,
  USER_ID int(11) NOT NULL default '0',
  CAMP_ID int(11) NOT NULL default '0',
  URL_ID int(11) NOT NULL default '0',
  TRACK_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (ID)
) TYPE=MyISAM;

#
# Table structure for table `UNSUB`
#

CREATE TABLE IF NOT EXISTS UNSUB (
  ID int(11) NOT NULL auto_increment,
  REC_ID int(11) NOT NULL default '0',
  LIST_ID int(11) NOT NULL default '0',
  CAMPAIGN_ID int(11) NOT NULL default '0',
  UNSUB_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (ID,REC_ID,LIST_ID),
  UNIQUE KEY REC_ID (REC_ID,LIST_ID)
) TYPE=MyISAM;

#
# Dumping data for table `UNSUB`
#

INSERT INTO UNSUB VALUES (3, 1, 22, 35, 1023105525);
# --------------------------------------------------------

#
# Table structure for table `URL`
#

CREATE TABLE IF NOT EXISTS URL (
  URL_ID int(11) NOT NULL auto_increment,
  NAME varchar(127) NOT NULL default '',
  URL varchar(255) NOT NULL default '',
  PRIMARY KEY  (URL_ID),
  UNIQUE KEY NAME (NAME)
) TYPE=MyISAM;

#
# Dumping data for table `URL`
#

INSERT INTO URL VALUES (1, 'Yahoo', 'http://www.yahoo.com/');
INSERT INTO URL VALUES (2, 'Hotmail', 'http://www.hotmail.com');
INSERT INTO URL VALUES (3, 'EVOKNOW', 'http://www.evoknow.com');

