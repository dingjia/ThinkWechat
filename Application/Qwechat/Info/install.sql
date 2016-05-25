-- -----------------------------
-- 表结构 `ocenter_Wechat`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `create_time` int(11) NOT NULL,
  `post_count` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `allow_user_group` text NOT NULL,
  `sort` int(11) NOT NULL,
  `logo` int(11) NOT NULL,
  `background` int(11) NOT NULL,
  `description` varchar(5000) NOT NULL,
  `admin` varchar(100) NOT NULL,
  `type_id` int(11) NOT NULL,
  `last_reply_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;


-- -----------------------------
-- 表结构 `ocenter_Wechat_bookmark`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_bookmark` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `create_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


-- -----------------------------
-- 表结构 `ocenter_Wechat_follow`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_follow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `Wechat_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=utf8 COMMENT='版块关注';


-- -----------------------------
-- 表结构 `ocenter_Wechat_lzl_reply`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_lzl_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `to_f_reply_id` int(11) NOT NULL,
  `to_reply_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `uid` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL,
  `ctime` int(11) NOT NULL,
  `is_del` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=85 DEFAULT CHARSET=utf8;


-- -----------------------------
-- 表结构 `ocenter_Wechat_post`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `Wechat_id` int(11) NOT NULL,
  `title` text NOT NULL,
  `parse` int(11) NOT NULL,
  `content` text NOT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `last_reply_time` int(11) NOT NULL,
  `view_count` int(11) NOT NULL,
  `reply_count` int(11) NOT NULL,
  `is_top` tinyint(4) NOT NULL COMMENT '是否置顶',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;


-- -----------------------------
-- 表结构 `ocenter_Wechat_post_reply`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_post_reply` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `parse` int(11) NOT NULL,
  `content` text NOT NULL,
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;


-- -----------------------------
-- 表结构 `ocenter_Wechat_type`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_Wechat_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL COMMENT '标题',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `sort` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='微信分类表';

-- -----------------------------
-- 表内记录 `ocenter_Wechat`
-- -----------------------------
INSERT INTO `ocenter_Wechat` VALUES ('1', '默认版块', '1407114174', '0', '1', '1', '0', '133', '123', '浑身发抖活动方式  发的撒健康福简单覆盖给艰苦奋斗是就是的撒伐', '[1],[2]', '1', '0');
INSERT INTO `ocenter_Wechat` VALUES ('2', '官方公告', '1417424922', '2', '1', '1', '0', '134', '117', '官方公告发布区', '', '2', '1433468728');
-- -----------------------------
-- 表内记录 `ocenter_Wechat_type`
-- -----------------------------
INSERT INTO `ocenter_Wechat_type` VALUES ('1', '默认分类', '1', '0', '0');
INSERT INTO `ocenter_Wechat_type` VALUES ('2', '官方微信', '1', '1', '0');
