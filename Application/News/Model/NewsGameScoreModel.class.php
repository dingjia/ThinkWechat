<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-27
 * Time: 下午1:22
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Model;


use Think\Model;

class NewsGamescoreModel extends Model{

    public function getGift($openid,$limit=10,$field='id,gift,create_time')
    {
        $cur_date = strtotime(date('Y-m-d',time()));
        $map['create_time']=array('EGT',$cur_date);

        $map['status']=0;
        $map['openid'] =$openid;
        $gifts=$this->where($map)->limit($limit)->order('create_time desc')->select();
        return $gifts;
    }

    public function getRank()
    {
       
        $ranks=$this->field('nickname,max(score) as score')->order('score desc')->group('openid')->limit(10)->select();
        foreach ($ranks as $key => $rank) {
        	$ranks[$key]['nickname']=$rank['nickname']?$rank['nickname']:'匿名用户';
        }
        return $ranks;
    }

  
} 