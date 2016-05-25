<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Wechat\Model;

use Think\Model;

class WechatPayModel extends Model
{
    protected $_validate = array(
        array('title', '1,99999', '标题不能为空', self::EXISTS_VALIDATE, 'length'),
        array('title', '0,100', '标题太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('update_time', NOW_TIME, 2),
        array('status', '1', self::MODEL_INSERT),
    );

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

    public function editData($data)
    {
        $data=$this->create();
        $data['token']=trim ($data['token']);
        $data['encodingaeskey']=trim ($data['encodingaeskey']);
        $data['appid']=trim ($data['appid']);
        $data['appsecret']=trim ($data['appsecret']);
        if($data['id']){
            
            $res=$this->save($data);
             S('wechat'.$data['id'],null);
             $this->  GetOptions($data['id']);
        }else{
            $data['aid']=session('user_auth.aid');
            $res=$this->add($data);

            S('wechat'.$res,null);
            $this->  GetOptions($res);
        }
       
       
        return $res;
    }

     public function GetOptions($appid){

        $options = S('wechat'.$appid);
        if($this->debug || !$options){
            $options = array();
            $map['id']=$appid;
            $options = $this->where($map)->limit(1)->find();
            $options['base']=array('token'=>$options['token'],'encodingaeskey'=>$options['encodingaeskey'],'appid'=>$options['appid'],'appsecret'=>$options['appsecret']);
            S('wechat'.$appid, $options);
        }

        return $options;
    }
    
    //必须找出一个微信来承接业务
    public function getWechats(){
             
            $map['aid']=session('user_auth.aid');
            $map['wechat_type']=3;
            $wechats= $this->where($map)->select();
          return $wechats;
    }

     public function mustOneWechat($aid=0){
             
            $map['aid']=$aid;
            $map['wechat_type']=3;
            $appid = D('Wechat')->where($map)->getField('id');
            // if ( !$appid) $this->mustOneWechat(0);
      
        return $appid;
    }


}
