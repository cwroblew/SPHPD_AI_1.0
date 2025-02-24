# phpMyAdmin MySQL-Dump
# version 2.2.5
# http://phpwizard.net/phpMyAdmin/
# http://phpmyadmin.sourceforge.net/ (download page)
#
# Host: localhost
# Generation Time: Nov 04, 2002 at 08:11 PM
# Server version: 3.23.35
# PHP Version: 4.1.0
# Database : `VOTE`
# --------------------------------------------------------

#
# Table structure for table `VOTES`
#

CREATE TABLE IF NOT EXISTS VOTES (
  POLL_ID int(11) NOT NULL default '0',
  VOTE int(11) NOT NULL default '0',
  VOTE_TS bigint(20) NOT NULL default '0'
) TYPE=MyISAM;

