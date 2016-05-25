-- phpMyAdmin SQL Dump
-- version phpStudy 2014
-- http://www.phpmyadmin.net
--
-- 主机: localhost
-- 生成日期: 2014 年 11 月 18 日 13:30
-- 服务器版本: 5.5.38
-- PHP 版本: 5.3.28

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";




--
-- 数据库: `110`
--

-- --------------------------------------------------------


-- --------------------------------------------------------

--
-- 表的结构 `ocenter_Feedback`
--

CREATE TABLE IF NOT EXISTS `ocenter_Feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderid` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `operaturl` varchar(255) NOT NULL,
  `tuidingurl` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `uid` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '4',
  `payid` varchar(255) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `create_time` varchar(11) DEFAULT NULL,
  `update_time` varchar(11) DEFAULT NULL,
  `ending` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orderid` (`orderid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

INSERT INTO `ocenter_menu` (`title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '订单管理', 0, 22, 'Feedback/index', 1, '', '', 0);

set @tmp_id=0;
select @tmp_id:= id from `ocenter_menu` where title = '订单管理';

INSERT INTO `ocenter_menu` ( `title`, `pid`, `sort`, `url`, `hide`, `tip`, `group`, `is_dev`) VALUES
( '配置', @tmp_id,0, 'Feedback/config', 0, '', '管理', 0),
( '退款订单', @tmp_id,1, 'Feedback/tp2', 0, '', '管理', 0),
( '退款', @tmp_id,1, 'Feedback/dopass', 1, '', '管理', 0),
( '已退款订单', @tmp_id,1, 'Feedback/tp5', 0, '', '订单', 0),
( '已付款订单', @tmp_id, 2, 'Feedback/tp1', 0, '', '订单', 0),
( '已完成订单', @tmp_id, 3, 'Feedback/ending', 0, '', '订单', 0),
( '问题订单', @tmp_id, 4, 'Feedback/tp3', 0, '', '订单', 0),
( '未付款订单', @tmp_id,5, 'Feedback/tp4', 0, '', '订单', 0),
( '图表', @tmp_id, 6, 'Feedback/index', 0, '', '今日收支', 0);


