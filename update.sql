REPLACE INTO `ocenter_action` VALUES ('9', 'send_verify', '发送验证码', '发送验证码', '', '发送验证码', '1', '1', '1452158048', 'Ucenter');
INSERT INTO `ocenter_action_limit` VALUES ('5', 'send_verify', '发送验证码', '1', '50', 'second', 'warning', '0', '操作过频。', '[send_verify]', '1', '0', 'Ucenter');
DROP TABLE IF EXISTS `ocenter_expression`;
CREATE TABLE IF NOT EXISTS `ocenter_expression` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `path` varchar(200) NOT NULL,
  `driver` varchar(50) NOT NULL,
  `create_time` int(11) NOT NULL,
  `expression_pkg_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `ocenter_expression_pkg`;
CREATE TABLE IF NOT EXISTS `ocenter_expression_pkg` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pkg_title` varchar(50) NOT NULL,
  `pkg_name` varchar(50) NOT NULL,
  `path` varchar(200) NOT NULL,
  `driver` varchar(50) NOT NULL,
  `create_time` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS `ocenter_iexpression`;
CREATE TABLE IF NOT EXISTS `ocenter_iexpression` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `path` varchar(200) NOT NULL,
  `driver` varchar(50) NOT NULL,
  `from` varchar(50) NOT NULL,
  `create_time` int(11) NOT NULL,
  `uid` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;