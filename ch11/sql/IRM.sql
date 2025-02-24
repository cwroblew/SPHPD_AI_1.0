# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Feb 24, 2003 at 08:06 AM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `IRM`
# --------------------------------------------------------

#
# Table structure for table `CATEGORY`
#

CREATE TABLE IF NOT EXISTS CATEGORY (
  CATEGORY_ID int(11) NOT NULL auto_increment,
  CATEGORY_NAME varchar(127) NOT NULL default '',
  P_CATEGORY_ID int(11) NOT NULL default '0',
  CREATED_BY int(11) NOT NULL default '0',
  CREATE_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (CATEGORY_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `RESOURCE`
#

CREATE TABLE IF NOT EXISTS RESOURCE (
  RESOURCE_ID int(11) NOT NULL auto_increment,
  RESOURCE_TITLE varchar(127) NOT NULL default '',
  RESOURCE_LOCATION text NOT NULL,
  RESOURCE_CATEGORY int(11) NOT NULL default '0',
  RESOURCE_RATING tinyint(4) NOT NULL default '0',
  RESOURCE_DESCRIPTION text,
  RESOURCE_ADDED_BY int(11) NOT NULL default '0',
  CREATE_TS bigint(20) NOT NULL default '0',
  FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (RESOURCE_ID),
  UNIQUE KEY FLAG (FLAG)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `RESOURCE_KEYWORD`
#

CREATE TABLE IF NOT EXISTS RESOURCE_KEYWORD (
  RESOURCE_ID int(11) NOT NULL default '0',
  KEYWORD varchar(31) NOT NULL default ''
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `RESOURCE_VISITOR`
#

CREATE TABLE IF NOT EXISTS RESOURCE_VISITOR (
  RESOURCE_ID int(11) NOT NULL default '0',
  VISITOR_ID int(11) NOT NULL default '0',
  VISIT_TS bigint(20) NOT NULL default '0'
) TYPE=MyISAM;


