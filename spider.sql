SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `category_bag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Name` text NOT NULL,
  `Parent category` int(11) unsigned NOT NULL DEFAULT '0',
  `Description` text NOT NULL,
  `Meta title` text NOT NULL,
  `Meta keywords` text NOT NULL,
  `Meta description` text NOT NULL,
  `Image` text NOT NULL,
  `Image Urls` text NOT NULL,
  `pageNum` int(11) unsigned NOT NULL DEFAULT '1',
  `productNum` int(11) unsigned NOT NULL DEFAULT '0',
  `state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未采集，1:已采集，2:已分析，3:已分页',
  `url` text NOT NULL,
  `urlCrc32` int(11) unsigned NOT NULL DEFAULT '0',
  `content` longtext CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urlCrc32` (`urlCrc32`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `page_bag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `categoryId` int(11) unsigned NOT NULL DEFAULT '0',
  `state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0：未采集，1：已采集，2：已分析',
  `url` text NOT NULL,
  `urlCrc32` int(11) unsigned NOT NULL DEFAULT '0',
  `content` longtext CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urlCrc32` (`urlCrc32`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `product_bag` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `Active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Name` text NOT NULL,
  `Categories` text NOT NULL,
  `Price` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `Wholesale price` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `On sale` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Discount amount` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `Discount percent` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `Reference` varchar(255) NOT NULL,
  `Manufacturer` varchar(255) NOT NULL,
  `Weight` float(6,2) unsigned NOT NULL DEFAULT '0.00',
  `Quantity` int(11) unsigned NOT NULL DEFAULT '99999',
  `Short description` text NOT NULL,
  `Description` text NOT NULL,
  `Tags` text NOT NULL,
  `Meta title` text NOT NULL,
  `Meta keywords` text NOT NULL,
  `Meta description` text NOT NULL,
  `Available for order` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Show price` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `Image` text NOT NULL,
  `Image Urls` text NOT NULL,
  `Feature` text NOT NULL,
  `state` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '0:未采集，1:已采集，2:已分析，3:已采集图片',
  `url` text NOT NULL,
  `urlCrc32` int(11) unsigned NOT NULL DEFAULT '0',
  `content` longtext CHARACTER SET utf8mb4 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urlCrc32` (`urlCrc32`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `proxy` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `times` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '最大失败次数，超过此数值，将不出现在采集IP列表中',
  `address` char(21) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`),
  KEY `times` (`times`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `task` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `site` char(16) NOT NULL COMMENT '网站名称',
  `table` tinyint(3) unsigned NOT NULL COMMENT '1：目录表，2：分页表，3：产品表',
  PRIMARY KEY (`id`),
  KEY `site` (`site`,`table`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
