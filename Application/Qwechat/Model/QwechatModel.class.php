<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Qwechat\Model;

use Think\Model;

class QwechatModel extends Model
{
    protected $_validate = array(
        array('name', '1,99999', '应用名称不能为空', self::EXISTS_VALIDATE, 'length'),
        array('name', '0,100', '应用名称太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, 2),
        array('status', '1', self::MODEL_INSERT),
    );

    public function updateAgent($agent_info=array())
    {
        $map['agentid']=$agent_info['agentid'];
        $map['aid']=session('user_auth.aid');
        $agent_info['aid']=session('user_auth.aid');

        $have = M("Qwechat")->where($map)->find();

        if ($have){
            M("Qwechat")->where($map)->save($agent_info);
            return "update";
        }else{
            M("Qwechat")->add($agent_info);
            return "add";
        }
    }

     /**
     * 获取分类详细信息
     * @param $id
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function info($id, $field = true){
        /* 获取分类信息 */
        $map = array();
        if(is_numeric($id)){ //通过ID查询
            $map['id'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        return $this->field($field)->where($map)->find();
    }

    public function getQwechats($map, $field = true){
        /* 获取分类信息 */
       
        $map['aid']=session('user_auth.aid');
        $map['close']=0;
        return $this->field($field)->where($map)->select();
    }

    public function editData($data)
    {
        $data=$this->create();
        if($data['id']){
            $res=$this->save($data);
            S('qwechat'.$data['id'],null);
            $this->  GetOptions($data['id']);
        }else{
            $res=$this->add($data);
            S('qwechat'.$res,null);
            $this->  GetOptions($res);
        }


       
        return $res;
    }

     public function GetOptions($appid=0){

        if ($appid==0){
            $map['agentid']=0;
            $map['aid']=session('user_auth.aid');
            $appid = M("Qwechat")->where($map)->getfield('id');
        }
        $options = S('qwechat'.$appid);
        if($this->debug || !$options){
            $options = array();
            $map['id']=$appid;
            $options = M('Qwechat')->where($map)->limit(1)->find();
            $options['base']=array('token'=>$options['token'],'encodingaeskey'=>$options['encodingaeskey'],'appid'=>$options['appid'],'appsecret'=>$options['appsecret'],'agentid'=>$options['agentid']);
            S('qwechat'.$appid, $options);
        }

        return $options;
    }

     //根据AID检查发送客情的机器人
     public function checkRob($aid,$agentid=0)
    {   
       
        //发送信息
         $map['aid']=$aid;
         $map['agentid']=$agentid;
         $my_rob = $this->where($map)->getfield('id');
                  
         if (!$my_rob) {
             $map['aid']=0;
             $my_rob = $this->where($map)->getfield('id');
         }

         return $my_rob;

     
     
    }

    

   
}
