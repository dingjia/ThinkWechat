<?php
/**
 * Created by PhpStorm.
 * User: microrain
 * Date: 15-11-23
 * Time: 下午12:14
 * @author microrain<xinjy@qq.com>
 */

namespace Addons\OneVote;

use Think\Model;

class OneVoteModel extends Model
{
	public function editData($data)
	{
		if ($data['id']) {
			$result = $this->save($data);
		} else {
			$data['create_time'] = time();
			$data['status'] = 1;
			$result = $this->add($data);
		}
		return $result;
	}

}