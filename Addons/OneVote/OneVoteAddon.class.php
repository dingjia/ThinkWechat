<?php
/**
 * Created by PhpStorm.
 * User: microrain
 * Date: 15-11-23
 * Time: 下午12:00
 * @author microrain<xinjy@qq.com>
 */
namespace Addons\OneVote;

use Common\Controller\Addon;

/**
 * 微投票插件
 * @author microrain
 */
class OneVoteAddon extends Addon
{
	public $info = array(
		'name' => 'OneVote',
		'title' => '微投票',
		'description' => '支持单选、多选的小投票插件。可以用来收集用户对某几个选项的意见。',
		'status' => 1,
		'author' => 'microrain',
		'version' => '0.1'
	);

	public $admin_list = array(
		'model' => 'onevote', //要查的表
		'fields' => '*', //要查的字段
		'map' => '', //查询条件, 如果需要可以再插件类的构造方法里动态重置这个属性
		'order' => 'id desc', //排序,
		'list_grid' => array( //这里定义的是除了id序号外的表格里字段显示的表头名和模型一样支持函数和链接
			'title:标题:[EDIT]',
			'description:说明',
			'voteconfig|viewtype:类型',
			'create_time|time_format:创建时间',
			'id:操作:[EDIT]|编辑,[DELETE]|删除'
		),
	);

	public function install()
	{
		$prefix = C("DB_PREFIX");
		$model = D();
		$model->execute("DELETE FROM `{$prefix}hooks`  WHERE `name` =\"OneVote\";");
		$model->execute("INSERT INTO `{$prefix}hooks` ( `name`, `description`, `type`, `update_time`, `addons`) VALUES
(\"OneVote\", \"举报钩子\", 1, 1429511732, \"OneVote\");");


		$model->execute("DROP TABLE IF EXISTS `{$prefix}onevote`");
		$model->execute("
CREATE TABLE IF NOT EXISTS `{$prefix}onevote` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `title` char(80) NOT NULL COMMENT '标题',
  `description` text COMMENT '描述',
  `options` text NOT NULL COMMENT '添加各种投票选项',
  `explanation` varchar(256) DEFAULT NULL COMMENT '备注',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `voteconfig` char(1) NOT NULL DEFAULT '1',
  `status` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
");

		return true;
	}

	public function uninstall()
	{
		$db_prefix = C('DB_PREFIX');
		$sql = "DROP TABLE IF EXISTS `{$db_prefix}onevote`;";
		D()->execute($sql);
		$model = M('hooks');
		if ($model->where("name='OneVote'")->delete()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $param
	 *在前台页面加入 {:hook('OneVote')}
	 */
	public function OneVote($param)
	{
		//获取插件的配置信息
		$config = $this->getConfig();
		if ($config['defaultid'] == 0) {
			$list = M('onevote')->order('id desc')->find();

		} else {
			$list = M('onevote')->where('id=' . $config["defaultid"])->find();
		}

		$options = $list["options"];
		$options = json_decode($options, true);
		$this->assign("id", $list['id']);
		$this->assign("title", $list["title"]);
		$this->assign("voteconfig", $list['voteconfig']);
		$this->assign("options", $options);

		$this->assign('addons_config', $config);
		if ($config['display'])
			$this->display(T('Addons://OneVote@OneVote/viewvote'));

	}

}