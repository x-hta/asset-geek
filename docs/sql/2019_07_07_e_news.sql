-- Adminer 4.3.2-dev MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `e_news`;
CREATE TABLE `e_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bot_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `lang` char(2) NOT NULL DEFAULT 'ru',
  `page_title` varchar(255) NOT NULL,
  `page_heading` varchar(255) NOT NULL,
  `meta_keywords` text NOT NULL,
  `meta_desc` text NOT NULL,
  `url` varchar(255) NOT NULL,
  `full_text` longtext NOT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `summary` varchar(255) NOT NULL,
  `add_date` int(10) unsigned NOT NULL,
  `edit_date` int(10) unsigned NOT NULL,
  `publish_date` int(10) unsigned NOT NULL,
  `author_id` int(11) NOT NULL DEFAULT '0',
  `views` int(11) NOT NULL DEFAULT '0',
  `add_params` text NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `indexed_text` text,
  `related_items` text,
  `main_platform` int(10) unsigned DEFAULT NULL,
  `add_date_day` date GENERATED ALWAYS AS (date_format(from_unixtime(`add_date`),'%Y-%m-%d')) VIRTUAL,
  `add_date_hour` char(2) GENERATED ALWAYS AS (date_format(from_unixtime(`add_date`),'%H')) VIRTUAL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `active` (`active`),
  KEY `add_date` (`add_date`),
  KEY `add_date_day` (`add_date_day`),
  KEY `add_date_hour` (`add_date_hour`),
  FULLTEXT KEY `index_search` (`indexed_text`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `e_news` (`id`, `bot_id`, `user_id`, `title`, `lang`, `page_title`, `page_heading`, `meta_keywords`, `meta_desc`, `url`, `full_text`, `active`, `summary`, `add_date`, `edit_date`, `publish_date`, `author_id`, `views`, `add_params`, `status`, `indexed_text`, `related_items`, `main_platform`) VALUES
(1,	0,	1,	'test',	'',	'',	'',	'',	'',	'test',	'<p>asdadok</p>\r\n\r\n<p>gfdgkodfgoertw</p>\r\n\r\n<p>werrkweorof</p>\r\n\r\n<p>sdfsdfksdofwekrwe</p>\r\n\r\n<p>&nbsp;</p>\r\n',	1,	'preview',	1562075121,	1562162337,	1562076660,	6,	0,	'',	0,	'TEST ASDADOK GFDGKODFGOERTW WERRKWEOROF SDFSDFKSDOFWEKRWE BITCOIN',	'[]',	NULL);

-- 2019-07-08 13:02:04
