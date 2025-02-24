# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Oct 31, 2002 at 03:26 PM
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
  flag varchar(127) NOT NULL default '',
  PRIMARY KEY  (id)
) TYPE=MyISAM;

