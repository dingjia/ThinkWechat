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

class WechatCardModel extends Model{

   protected $_validate = array(
        array('name', '1,99999', '应用名称不能为空', self::EXISTS_VALIDATE, 'length'),
        array('name', '0,100', '应用名称太长', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('create_time', NOW_TIME, self::MODEL_INSERT),
        array('close', '0', self::MODEL_INSERT)
       
    );

    public function updateCard($card=array()){
    
        $map['id']=$card['id'];
        $map['aid']=session('user_auth.aid');
        $card['aid']=session('user_auth.aid');

      $have = $this->where($map)->find();
     
      
        if ($have){
            $this->where($map)->save($card);
          
            return "update";
        }else{
            $this->add($card);
          
            return "add";
        }
    }

    public function getCards($map=array(),$field=true){
     
      $map['_string']="status='CARD_STATUS_VERIFY_OK' or status='CARD_STATUS_DISPATCH' ";
      $cards = $this->where($map)->select();
     
      return $cards;
        
    }

     public function info($id,$field=true){
     
       if (!$id) return false;
        /* 获取分类信息 */
        $map = array();
        if (is_numeric($id)){
            $map['card_id'] = $id;
        }else{
            $map['id'] = $id;
        }

        $card=$this->field($field)->where($map)->find();
        return $card;
    }





}