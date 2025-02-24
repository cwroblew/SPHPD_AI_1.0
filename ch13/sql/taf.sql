# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Oct 30, 2002 at 12:29 PM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `TELL_A_FRIEND`
# --------------------------------------------------------

#
# Table structure for table `TAF_FORM`
#

CREATE TABLE IF NOT EXISTS TAF_FORM (
  FRM_ID int(11) NOT NULL auto_increment,
  FRM_NAME varchar(255) binary NOT NULL default '',
  ACTIVATION_TS bigint(20) NOT NULL default '0',
  TERMINATION_TS bigint(20) NOT NULL default '0',
  FRIENDS_MSG_ID int(11) NOT NULL default '0',
  ORIGIN_MSG_ID int(11) NOT NULL default '0',
  SUBSCRIBER_MSG_ID int(11) NOT NULL default '0',
  MAX_FRIEND_PER_ORIGIN int(11) NOT NULL default '0',
  SCORE_PER_FRIEND_SUBMISSION int(11) NOT NULL default '0',
  SCORE_PER_FRIEND_SUBSCRIPTION tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (FRM_ID),
  UNIQUE KEY FRM_NAME (FRM_NAME)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_FRM_BANNED_IP`
#

CREATE TABLE IF NOT EXISTS TAF_FRM_BANNED_IP (
  FRM_ID int(11) NOT NULL default '0',
  BANNED_IP varchar(255) NOT NULL default '',
  PRIMARY KEY  (FRM_ID,BANNED_IP)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_FRM_OWNER_IP`
#

CREATE TABLE IF NOT EXISTS TAF_FRM_OWNER_IP (
  FRM_ID int(11) NOT NULL default '0',
  OWNER_IP varchar(255) NOT NULL default '',
  PRIMARY KEY  (FRM_ID,OWNER_IP)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_MESSAGE`
#

CREATE TABLE IF NOT EXISTS TAF_MESSAGE (
  MSG_ID int(11) NOT NULL auto_increment,
  MSG_NAME varchar(255) NOT NULL default '',
  BODY text NOT NULL,
  MSG_FROM varchar(255) NOT NULL default '',
  REPLY_TO varchar(255) NOT NULL default '',
  SUBJECT varchar(255) NOT NULL default '',
  PRIMARY KEY  (MSG_ID),
  UNIQUE KEY MSG_NAME (MSG_NAME)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_MSG_OWNER_IP`
#

CREATE TABLE IF NOT EXISTS TAF_MSG_OWNER_IP (
  MSG_ID int(11) NOT NULL default '0',
  OWNER_IP varchar(255) NOT NULL default '0',
  PRIMARY KEY  (MSG_ID,OWNER_IP)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_SUBMISSION`
#

CREATE TABLE IF NOT EXISTS TAF_SUBMISSION (
  FRND_ID int(11) NOT NULL auto_increment,
  FRND_EMAIL varchar(127) NOT NULL default '',
  FRND_NAME varchar(127) NOT NULL default '',
  FRM_ID int(11) NOT NULL default '0',
  ORIGIN_EMAIL varchar(127) NOT NULL default '',
  ORIGIN_IP varchar(127) NOT NULL default '',
  SUBMIT_TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (FRND_ID),
  UNIQUE KEY FRND_EMAIL (FRND_EMAIL,FRM_ID)
) TYPE=MyISAM;
# --------------------------------------------------------

#
# Table structure for table `TAF_SUBSCRIPTION`
#

CREATE TABLE IF NOT EXISTS TAF_SUBSCRIPTION (
  FRM_ID int(11) NOT NULL default '0',
  FRND_EMAIL varchar(127) NOT NULL default '',
  ORIGIN_EMAIL varchar(127) NOT NULL default '',
  SUBSCRIPTION varchar(10) NOT NULL default '',
  TS bigint(20) NOT NULL default '0',
  PRIMARY KEY  (FRM_ID,FRND_EMAIL)
) TYPE=MyISAM;

