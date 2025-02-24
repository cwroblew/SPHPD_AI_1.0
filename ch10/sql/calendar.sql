# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Feb 24, 2003 at 06:19 AM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `CALENDAR`
# --------------------------------------------------------

#
# Table structure for table `CALENDAR_EVENT`
#

CREATE TABLE IF NOT EXISTS CALENDAR_EVENT (
  EVENT_ID int(11) NOT NULL auto_increment,
  USER_ID int(11) NOT NULL default '0',
  EVENT_TITLE varchar(255) NOT NULL default '',
  EVENT_DATE varchar(255) NOT NULL default '',
  EVENT_DESC text,
  REMINDER_ID int(11) NOT NULL default '0',
  FLAG bigint(20) NOT NULL default '0',
  PRIMARY KEY  (EVENT_ID),
  UNIQUE KEY FLAG (FLAG)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CALENDAR_EVENT_VIEWER`
#

CREATE TABLE IF NOT EXISTS CALENDAR_EVENT_VIEWER (
  EVENT_ID int(11) NOT NULL default '0',
  VIEWER_ID tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (EVENT_ID,VIEWER_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `CALENDAR_REPETITIVE_EVENTS`
#

CREATE TABLE IF NOT EXISTS CALENDAR_REPETITIVE_EVENTS (
  EVENT_ID int(11) NOT NULL default '0',
  REPEAT_MODE varchar(127) NOT NULL default '',
  PRIMARY KEY  (EVENT_ID,REPEAT_MODE)
) TYPE=MyISAM;


