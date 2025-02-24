# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Jun 07, 2002 at 09:13 PM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `SURVEY`
# --------------------------------------------------------

#
# Table structure for table `SURVEY`
#

CREATE TABLE IF NOT EXISTS SURVEY (
  SURVEY_ID int(11) NOT NULL auto_increment,
  NAME varchar(127) NOT NULL default '',
  LIST_ID int(11) NOT NULL default '0',
  FORM_ID int(11) NOT NULL default '0',
  CREATE_TS bigint(20) NOT NULL default '0',
  CREATOR_ID int(11) NOT NULL default '0',
  STATUS int(11) NOT NULL default '0',
  PRIMARY KEY  (SURVEY_ID),
  UNIQUE KEY NAME (NAME)
) TYPE=MyISAM;

#
# Dumping data for table `SURVEY`
#

# --------------------------------------------------------

#
# Table structure for table `SURVEY_EXECUTION`
#

CREATE TABLE IF NOT EXISTS SURVEY_EXECUTION (
  EXEC_ID int(11) NOT NULL auto_increment,
  SURVEY_ID int(11) NOT NULL default '0',
  SURVEY_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (EXEC_ID)
) TYPE=MyISAM COMMENT='Survey Execution Record';

#
# Dumping data for table `SURVEY_EXECUTION`
#

# --------------------------------------------------------

#
# Table structure for table `SURVEY_FORM`
#

CREATE TABLE IF NOT EXISTS SURVEY_FORM (
  FORM_ID int(11) NOT NULL auto_increment,
  NAME varchar(255) NOT NULL default '',
  TEMPLATE varchar(255) NOT NULL default '',
  SUBJECT varchar(127) NOT NULL default '',
  MAILFROM varchar(127) NOT NULL default '',
  CREATE_TS bigint(20) NOT NULL default '0',
  CREATOR_ID int(11) NOT NULL default '0',
  CHECKFLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (FORM_ID,NAME),
  UNIQUE KEY CHECKFLAG (CHECKFLAG),
  UNIQUE KEY NAME (NAME)
) TYPE=MyISAM;

#
# Dumping data for table `SURVEY_FORM`
#

# --------------------------------------------------------

#
# Table structure for table `SURVEY_FORM_FIELD_LBL`
#

CREATE TABLE IF NOT EXISTS SURVEY_FORM_FIELD_LBL (
  FORM_ID int(11) NOT NULL default '0',
  FIELD_ID int(11) NOT NULL default '0',
  LABEL varchar(255) NOT NULL default '',
  PRIMARY KEY  (FORM_ID,FIELD_ID)
) TYPE=MyISAM;

#
# Table structure for table `SURVEY_LIST`
#

CREATE TABLE IF NOT EXISTS SURVEY_LIST (
  LIST_ID int(11) NOT NULL auto_increment,
  NAME varchar(255) NOT NULL default '',
  FILENAME varchar(127) NOT NULL default '',
  RECORDS int(11) NOT NULL default '0',
  CREATE_TS bigint(20) NOT NULL default '0',
  CREATOR_ID int(11) NOT NULL default '0',
  CHECKFLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (LIST_ID),
  UNIQUE KEY CHECKFLAG (CHECKFLAG)
) TYPE=MyISAM;

#
# Dumping data for table `SURVEY_LIST`
#

# --------------------------------------------------------

#
# Table structure for table `SURVEY_LIST_DATA`
#

CREATE TABLE IF NOT EXISTS SURVEY_LIST_DATA (
  LIST_ID int(11) NOT NULL default '0',
  SUID int(11) NOT NULL auto_increment,
  EMAIL varchar(127) NOT NULL default '',
  FIRST varchar(50) NOT NULL default '',
  LAST varchar(50) NOT NULL default '',
  PRIMARY KEY  (SUID),
  UNIQUE KEY LIST_ID (LIST_ID,EMAIL)
) TYPE=MyISAM COMMENT='Survey List Data Table';

#
#
# Table structure for table `SURVEY_RESPONSE`
#

CREATE TABLE IF NOT EXISTS SURVEY_RESPONSE (
  ID int(11) NOT NULL auto_increment,
  EXEC_ID int(11) NOT NULL default '0',
  SUID int(11) NOT NULL default '0',
  FIELD_ID int(11) NOT NULL default '0',
  VALUE varchar(50) NOT NULL default '',
  PRIMARY KEY  (ID)
) TYPE=MyISAM COMMENT='Survey Response Data Storage Table';

#
# Table structure for table `SURVEY_RESPONSE_RECORD`
#

CREATE TABLE IF NOT EXISTS SURVEY_RESPONSE_RECORD (
  EXEC_ID int(11) NOT NULL default '0',
  SUID tinyint(4) NOT NULL default '0',
  SUBMIT_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (EXEC_ID,SUID)
) TYPE=MyISAM COMMENT='Survey Response Record Table';

