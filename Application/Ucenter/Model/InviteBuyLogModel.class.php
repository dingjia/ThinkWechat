<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-25
 * Time: 下午5:23
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace Ucenter\Model;


use Think\Model;

class InviteBuyLogModel extends Model
{
    /**
     * 添加用户兑换名额记录
     * @param int $type_id
     * @param int $num
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function buy($type_id = 0, $num = 0)
    {
        $invite_type=D('Ucenter/InviteType')->where(array('id'=>$type_id))->find();
        $user=get_nickname();
        $data['content']="{$user['nickname']} 在 ".time_format(time()).L('_TIME_TO_BUY_').$num.' 个 '.$invite_type['title'].L('_INVITATION_');

        $data['uid']=is_login();
        $data['invite_type']=$type_id;
        $data['num']=$num;
        $data['create_time']=time();

        $result=$this->add($data);
        return $result;
    }

    /**
     * 获取兑换记录列表
     * @param array $map
     * @param int $page
     * @param string $order
     * @param int $r
     * @return array
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function getList($map=array(),$page=1,$order='create_time desc',$r=20)
    {
        if(count($map)){
            $totalCount=$this->where($map)->count();
            if($totalCount){
                $list=$this->where($map)->order($order)->page($page,$r)->select();
            }
        }else{
            $totalCount=$this->count();
            if($totalCount){
                $list=$this->order($order)->page($page,$r)->select();
            }
        }
        $list=$this->_initSelectData($list);
        return array($list,$totalCount);
    }

    /**
     * 初始化查询出的数据
     * @param array $list
     * @return array
     * @author 郑钟良<zzl@ourstu.com>
     */
    private function _initSelectData($list=array())
    {
        $inviteTypeModel=D('Ucenter/InviteType');
        foreach($list as &$val){
            $inviteType=$inviteTypeModel->getSimpleData(array('id'=>$val['invite_type']));
            $val['invite_type_title']=$inviteType['title']?$inviteType['title']:'[已删除类型]';
            $val['user']=get_nickname($val['uid']);
            $val['user']='['.$val['uid'].']'.$val['user'];
        }
        unset($val);
        return $list;
    }
} 