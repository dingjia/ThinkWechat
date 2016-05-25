<?php
/**
 * Created by PhpStorm.
 * User: microrain
 * Date: 15-11-23
 * Time: 下午12:12
 * @author microrain<xinjy@qq.com>
 */
namespace Addons\OneVote\Controller;

use Home\Controller\AddonsController;

class ViewVoteController extends AddonsController
{
	public function detaile()
	{
		$id = I('get.id');
		$list = M('onevote')->where('id=' . $id)->find();
		$options = $list["options"];
		$options = json_decode($options, true);

		$votecount = 0; //总票数
		while (list($key) = each($options)) {
			$votecount += $options[$key]['num'];
		}

		$this->assign("title", $list["title"]);
		$this->assign("votecount", $votecount);
		$this->assign("voteconfig", $list['voteconfig']);
		$this->assign("description", $list['description']);
		$this->assign("options", $options);
		$this->display(T('Addons://OneVote@OneVote/detaile'));
	}

	public function saveSubmit()
	{
		$id = I('post.id'); //获取操作投票的ID
		$op = I('post.op'); //投票数据
		//查询操作的投票记录
		$list = M('onevote')->where('id=' . $id)->find();
		$options = $list['options'];
		$arrop = json_decode($options, true);
		while (list($key) = each($arrop)) {
			if ($arrop[$key]['voteconfig'] == '2') { //多选值的处理
				if (in_array($arrop[$key]['id'], $op))
					$arrop[$key]['num'] += 1;
			} else {
				if ($arrop[$key]['id'] == $op)        //单选值的处理
					$arrop[$key]['num'] += 1;
			}
		}
		$voteobj = M("onevote");
		$data['options'] = json_encode($arrop);
		$voteobj->where('id=' . $id)->save($data);
		$this->success('投票成功！', "http://" . $_SERVER['SERVER_NAME'] . U('Home/addons/execute', array('_addons' => 'OneVote', '_controller' => 'ViewVote', '_action' => 'detaile', 'id' => $id), true, false, true));
	}

}