# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Mar 07, 2003 at 03:52 AM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `WEBFORMS`
# --------------------------------------------------------

#
# Table structure for table `ASK_TBL`
#

CREATE TABLE IF NOT EXISTS ASK_TBL (
  id bigint(20) NOT NULL auto_increment,
  fname varchar(127) NOT NULL default '',
  lname varchar(127) NOT NULL default '',
  company varchar(255) NOT NULL default '',
  email varchar(255) NOT NULL default '',
  url tinytext NOT NULL,
  about tinytext NOT NULL,
  subject tinytext NOT NULL,
  details text NOT NULL,
  SUBMIT_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `WEBFORMS_DL_TBL`
#

CREATE TABLE IF NOT EXISTS WEBFORMS_DL_TBL (
  FORM_ID varchar(255) NOT NULL default '0',
  DOWNLOAD_TS bigint(20) NOT NULL default '0',
  RECORD_ID int(11) NOT NULL default '0'
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `X_TBL`
#

CREATE TABLE IF NOT EXISTS X_TBL (
  id int(11) NOT NULL auto_increment,
  x_field_1 varchar(255) NOT NULL default '',
  x_field_2 text NOT NULL,
  x_field_3 int(11) NOT NULL default '0',
  SUBMIT_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (id)
) TYPE=MyISAM;


