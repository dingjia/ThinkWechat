<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 上午10:47
 * @author 钱枪枪<8314028@qq.com>
 */

namespace Wechat\Model;


use Think\Model;

class WechatPoilistModel extends Model{

   protected $_validate = array(
        array('name', '1,99999', '应用名称不能为空', self::EXISTS_VALIDATE, 'length'),
        array('name', '0,100', '应用名称太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('close', '0', self::MODEL_INSERT)
       
    );

    public function updatePoilist($shop=array()){
    
        $map['poi_id']=$shop['id'];
        $map['aid']=session('user_auth.aid');
        $shop['aid']=session('user_auth.aid');
        $shop['poi_id']=$shop['id'];
        $shop['business_name']=$shop['name'];


      $have = $this->where($map)->find();
      $data = $this->create($shop);
       unset($data['id']);
      
        if ($have){
            $this->where($map)->save($data);
          
            return "update";
        }else{
            $this->add($data);
          
            return "add";
        }
    }



}