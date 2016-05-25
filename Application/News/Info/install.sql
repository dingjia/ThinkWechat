-- -----------------------------
-- 表结构 `ocenter_news`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL COMMENT '标题',
  `description` varchar(200) NOT NULL COMMENT '描述',
  `category` int(11) NOT NULL COMMENT '分类',
  `status` tinyint(2) NOT NULL COMMENT '状态',
  `reason` varchar(100) NOT NULL COMMENT '审核失败原因',
  `sort` int(5) NOT NULL COMMENT '排序',
  `position` int(4) NOT NULL COMMENT '定位，展示位',
  `cover` int(11) NOT NULL COMMENT '封面',
  `view` int(10) NOT NULL COMMENT '阅读量',
  `comment` int(10) NOT NULL COMMENT '评论量',
  `collection` int(10) NOT NULL COMMENT '收藏量',
  `dead_line` int(11) NOT NULL COMMENT '有效期',
  `source` varchar(200) NOT NULL COMMENT '来源url',
  `create_time` int(11) NOT NULL,
  `update_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='资讯';


-- -----------------------------
-- 表结构 `ocenter_news_category`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_news_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL,
  `pid` int(11) NOT NULL,
  `can_post` tinyint(4) NOT NULL COMMENT '前台可投稿',
  `need_audit` tinyint(4) NOT NULL COMMENT '前台投稿是否需要审核',
  `sort` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='资讯分类';


-- -----------------------------
-- 表结构 `ocenter_news_detail`
-- -----------------------------
CREATE TABLE IF NOT EXISTS `ocenter_news_detail` (
  `news_id` int(11) NOT NULL,
  `content` text NOT NULL COMMENT '内容',
  `template` varchar(50) NOT NULL COMMENT '模板',
  PRIMARY KEY (`news_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='资讯详情';

-- -----------------------------
-- 表内记录 `ocenter_news_category`
-- -----------------------------
INSERT INTO `ocenter_news_category` VALUES ('1', '默认分类', '0', '1', '1', '1', '1');
