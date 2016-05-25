<?php
/**
 * 所属项目 OpenSNS
 * 开发者: 陈一枭
 * 创建日期: 2014-12-01
 * 创建时间: 15:55
 */

namespace Wechat\Model;


use Think\Model;

class WechatNoticeModel extends Model
{
   

    /**下载微信粉丝
     * @param int  $id
     * @param bool $field
     * @return array
     * @auth 陈一枭
     */
    public function addNotice($notice=array())
    {
       
        $map['MsgId']=$notice['MsgId'];
        $map['CreateTime']=$notice['CreateTime'];
        $map['FromUserName']=$notice['FromUserName'];
        $have = M("WechatNotice")->where($map)->find();

        if ($have){
            return '';
        }else{
            M("WechatNotice")->add($notice);
            return true;
        }
         
    }

    




    /**
     * 获取分类详细信息
     * @param  milit   $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function info($map=array(), $field = true)
    {
       

        return $this->field($field)->where($map)->find();
    }

} 