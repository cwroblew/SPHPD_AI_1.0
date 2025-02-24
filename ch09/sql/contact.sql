# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Feb 24, 2003 at 03:28 AM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `CONTACTS`
# --------------------------------------------------------

#
# Table structure for table `CONTACT_CATEGORY`
#

CREATE TABLE IF NOT EXISTS CONTACT_CATEGORY (
  CAT_ID int(11) NOT NULL auto_increment,
  CAT_NAME varchar(255) NOT NULL default '',
  CAT_DESC text,
  CAT_PARENT int(11) NOT NULL default '0',
  PRIMARY KEY  (CAT_ID),
  UNIQUE KEY CAT_NAME (CAT_NAME,CAT_PARENT)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CONTACT_INFO`
#

CREATE TABLE IF NOT EXISTS CONTACT_INFO (
  CONTACT_ID mediumint(9) NOT NULL auto_increment,
  CAT_ID int(11) NOT NULL default '0',
  CONTACT_FIRST varchar(255) NOT NULL default '',
  CONTACT_INITIAL varchar(255) default NULL,
  CONTACT_LAST varchar(255) default NULL,
  EMAIL varchar(255) default NULL,
  PHONE varchar(255) default NULL,
  FAX varchar(255) default NULL,
  URL varchar(255) default NULL,
  COMPANY_NAME varchar(255) default NULL,
  COMPANY_ADDRESS text,
  HOME_ADDRESS text,
  SOURCE varchar(255) default NULL,
  REFERENCE varchar(255) default NULL,
  FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (CONTACT_ID),
  UNIQUE KEY FLAG (FLAG)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CONTACT_KEYWORD`
#

CREATE TABLE IF NOT EXISTS CONTACT_KEYWORD (
  CONTACT_ID int(11) NOT NULL default '0',
  KEYWORD varchar(255) NOT NULL default '',
  PRIMARY KEY  (CONTACT_ID,KEYWORD)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CONTACT_MAIL`
#

CREATE TABLE IF NOT EXISTS CONTACT_MAIL (
  MAIL_ID int(11) NOT NULL auto_increment,
  CONTACT_ID int(11) NOT NULL default '0',
  CC_TO varchar(255) default NULL,
  SUBJECT text,
  BODY text NOT NULL,
  SEND_TS bigint(20) NOT NULL default '0',
  CHECK_FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (MAIL_ID),
  UNIQUE KEY CHECK_FLAG (CHECK_FLAG)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CONTACT_REMINDER`
#

CREATE TABLE IF NOT EXISTS CONTACT_REMINDER (
  CONTACT_ID int(11) NOT NULL default '0',
  CREATED_BY int(11) NOT NULL default '0',
  REMIND_ABOUT text NOT NULL,
  REMIND_DATE bigint(20) NOT NULL default '0',
  MOTD_ID int(11) NOT NULL default '0'
) TYPE=MyISAM;


