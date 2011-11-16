--
-- Database: `riverwire`
--
-- --------------------------------------------------------
--
-- Table structure for table `guard_seeds`
--

CREATE TABLE IF NOT EXISTS `guard_seeds` (
  `seed_id` int(11) NOT NULL AUTO_INCREMENT,
  `seed` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`seed_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=61498 ;

-- --------------------------------------------------------
--
-- Table structure for table `river_items`
--

CREATE TABLE IF NOT EXISTS `river_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `river_source_id` int(11) DEFAULT NULL,
  `feed_item_id` varchar(255) DEFAULT NULL,
  `item_url` varchar(1024) DEFAULT NULL,
  `item_title` varchar(1024) DEFAULT NULL,
  `item_author` varchar(255) DEFAULT NULL,
  `item_thumbnail` varchar(1024) DEFAULT NULL,
  `publish_date` datetime DEFAULT NULL,
  `permalink` varchar(1024) DEFAULT NULL,
  `item_excerpt` text,
  `body` text,
  `feed_url` varchar(1024) DEFAULT NULL,
  `feed_section` varchar(1024) DEFAULT NULL,
  `feed_title` varchar(1024) DEFAULT NULL,
  `website_url` varchar(1024) DEFAULT NULL,
  `capture_date` datetime DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `feed_item_id` (`feed_item_id`),
  KEY `capture_date` (`capture_date`),
  KEY `river_source_id` (`river_source_id`),
  KEY `publish_date` (`publish_date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1237 ;