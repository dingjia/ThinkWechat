<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-8
 * Time: PM4:14
 */

namespace Qwechat\Model;

use Think\Model;

class QwechatMemberModel extends Model
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

   public function updateMember($member=array())
    {
        $map['userid']=$member['userid'];
        $map['aid']=session('user_auth.aid');
        $member['aid']=session('user_auth.aid');

        //数据处理
        $member['department']=implode(',',$member['department']);
        $member['extattr']=serialize($member['extattr']);

        $have = M("QwechatMember")->where($map)->find();

        if ($have){
            M("QwechatMember")->where($map)->save($member);
            return "update";
        }else{
            M("QwechatMember")->add($member);
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
            $map['id|mobile'] = $id;
        } else { //通过标识查询
            $map['name'] = $id;
        }
        $wechatMember=$this->field($field)->where($map)->find();
       
        $notice.="/:sun粉丝号：".$wechatMember['id']." \n";
        $notice.="/:sun手机号：". ($wechatMember['mobile']?$wechatMember['mobile']:'未完善信息')." \n";
        $notice.="/:sun持卡人：". ($wechatMember['remark']?$wechatMember['remark']:$wechatMember['name'])." \n";
        $notice.="/:sun账户余额：".($wechatMember['amount']?$wechatMember['amount']:0)."元 \n";
       
       

        $wechatMember['notice']=$notice;
        return $wechatMember;
    }

      /**
     * 获取分类详细信息
     * @param $id
     * @param bool $field
     * @return mixed
     * @author 郑钟良<zzl@ourstu.com>
     */
    public function infoByUserid($userid, $field = true){
        /* 获取分类信息 */
        $map['userid'] = $userid;

        $wechatMember=$this->field($field)->where($map)->find();
        $shop=M('QwechatShop')->where(array('id'=>$wechatMember['shopid']))->getfield('name');
        $notice.="/:sun姓名：". ($wechatMember['remark']?$wechatMember['remark']:$wechatMember['name'])." \n";
        $notice.="/:sun所在分店：".$shop." \n";
        $notice.="/:sun员工编号：".$wechatMember['id']." \n";
        $notice.="/:sun微信编号：".$wechatMember['userid']." \n";
        $notice.="/:sun手机号：". ($wechatMember['mobile']?$wechatMember['mobile']:'未完善信息')." \n";
        $notice.="/:sun账户余额：".($wechatMember['amount']?$wechatMember['amount']:0)."元 \n";
        // $notice.="/:sun ".'<a href="http://'.$_SERVER[HTTP_HOST].U('Qwechat/index/editMy',array('userid'=>$wechatMember['userid'])).'">更改我的资料</a>';
       
      

        $wechatMember['notice']=$notice;
       
        return $wechatMember;
    }


     public function infoByMobile($mobile, $field = true)
    {
        /* 获取分类信息 */
        $map = array();
        $map['mobile'] = $mobile;
       

        $members=$this->field($field)->where($map)->select();
       
       foreach ($members as $key => $member) {
            $notice.="/:sun员工编号：".$member['id']." \n";
            $notice.="/:sun微信编号：".$member['userid']." \n";
            $notice.="/:sun手机号：". ($member['mobile']?$member['mobile']:'未完善信息')." \n";
            $notice.="/:sun持卡人：". ($member['remark']?$member['remark']:$member['name'])." \n";
            $notice.="/:sun账户余额：".($member['amount']?$member['amount']:0)."元 ";
            // $notice.="/:sun ".'<a href="http://'.$_SERVER[HTTP_HOST].U('Qwechat/index/editMy',array('userid'=>$wechatMember['userid'])).'">更改我的资料</a>';
       
           
       }
       
        
        return $notice;
    }




    public function editData($data)
    {
        $data=$this->create();
        
        if($data['id']){
            $res=$this->save($data);
        }else{
            $data['aid']=session('user_auth.aid');
            $res=$this->add($data);
        }
        return $res;
    }

    public function getBirthday($day,$field='name,birthday,calendar')
    {
      
        // 获得今年生日时间戳
        $current_birthday   = "UNIX_TIMESTAMP(concat(YEAR(NOW()),FROM_UNIXTIME(birthday,'-%m-%d')))";
        // 获得来年生日时间戳
        $next_birthday      = "UNIX_TIMESTAMP(concat(YEAR(NOW())+1,FROM_UNIXTIME(birthday,'-%m-%d')))";

        // 7 天 = 604800 秒
        // 条件一 今年生日(非跨年)的情况     语句为: $current_birthday - UNIX_TIMESTAMP() <= 604800 
        // 条件二 来年生日(跨年)的情况      语句为: $next_birthday - UNIX_TIMESTAMP() <= 604800 

        // 减去当前时间戳
        $subtrSql           = ' - UNIX_TIMESTAMP() <='.$day*3600*24;
        //条件语句为
        $whereSql           = $current_birthday.$subtrSql.' OR '.$next_birthday.$subtrSql;

        $birthdays  = $this->field($field)->where($whereSql)->select();
      
        $calendar=array('阴历','农历');
        foreach ($birthdays as $key => $birthday) {
           $birthdays[$key]['calendar']=$calendar[$birthday['calendar']];
        }
        
        return $birthdays;

    }

    

   
}
