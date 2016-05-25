<?php
/**
 * Created by PhpStorm.
 * User: microrain
 * Date: 15-11-23
 * Time: 下午12:10
 * @author microrain<xinjy@qq.com>
 */

namespace Addons\OneVote\Controller;

use Admin\Controller\AddonsController;
use Admin\Builder\AdminListBuilder;
use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminTreeListBuilder;

class AdminController extends AddonsController
{
	public function buildList($page = 1, $r = 20)
	{
		$map['status'] = array('egt', 0);
		$list = M('Onevote')->where($map)->page($page, $r)->order('id asc')->select();
		$reportCount = M('Onevote')->where($map)->count();
		int_to_string($list);

		$builder = new AdminListBuilder();
		$builder->title("投票列表");

		$builder->buttonNew(addons_url('OneVote://Admin/edit'), '新增')
			->buttonDelete(addons_url('OneVote://Admin/deleteOneVote'), '删除')
			->keyId()
			->keyText('title', "投票标题")
			->keyText('description', "说明")
			->key('voteconfig', '状态', 'status', array('1' => '单选', '2' => '多选'))
			->keyCreateTime('create_time', "创建时间")
			->keyText('explanation', "备注")
			->keyDoActionEdit('OneVote://Admin/edit?id=###|addons_url', '编辑');

		$builder->data($list);
		$builder->pagination($reportCount, $r);
		$builder->display();
	}


	/**
	 * 删除投票
	 */
	public function deleteOneVote()
	{
		$ids = I('ids', array());
		$map['id'] = array('in', $ids);
		$result = M('Onevote')->where($map)->delete();
		if ($result) {
			$this->success('删除成功', 0);
		} else {
			$this->error('删除失败');
		}
	}

	/**
	 * 编辑投票
	 */
	public function edit()
	{
		$aId = I('id', 0, 'intval');
		$title = $aId ? "编辑" : "新增";
		if (IS_POST) {
			$id = I('post.id');
			$title = I('post.title');
			$description = I('post.description');
			$options = I('post.options');
			$explanation = I('post.explanation');
			$voteconfig = I('post.voteconfig');
			$data = array(
				'title' => $title,
				'description' => $description,
				'options' => $options,
				'explanation' => $explanation,
				'voteconfig' => $voteconfig,
			);

			if ($id == "") {                    //新建
				$data['create_time'] = time();
				$ov = M('onevote')->add($data);
			} else {                            //修改
				$data['update_time'] = time();
				$ov = M('onevote')->where('id=' . $id)->save($data);
			}

			if ($ov) {
				$status = 1;
			} else {
				$status = 0;
			}
			echo($status);
		} else {
			if ($aId) {
				$data = M('onevote')->where(array('id' => $aId))->find();
			}
			$this->assign('data', $data);
			$this->display(T('Addons://OneVote@OneVote/edit'));

		}
	}

}
