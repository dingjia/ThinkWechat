<?php
/**
 * Created by PhpStorm.
 * User: ludashi
 * Date: 15-10-9
 * Time: PM5:41
 */

namespace Admin\Controller;

use Admin\Builder\AdminConfigBuilder;
use Admin\Builder\AdminListBuilder;

class FeedbackController extends AdminController
{
    protected $Model;

    function _initialize()
    {
        require_cache('./Application/Feedback/Common/function.php');
        $this->Model = D('Feedback/FeedbackList');
        $this->giftModel= D('Feedback/FeedbackGift');
        parent::_initialize();
        $this->rank=array(1=>'好评',2=>'中评',3=>'差评');
        $this->status=array(1=>"未处理",2=>"已处理");

    }
    public function index(){
        // $this->display(T('Feedback://Feedback@Admin/index'));
         
         if(IS_POST){
               $start=I('post.start', S('START'),'intval',time());
               $end=I('post.end', S('END'),'intval',time());
               $shopid=I('post.shopid', S('SHOP_ID'),'intval'); 

               if ($end-$start>86400*30*2)$this->error('查询起止时间不能超过2个月');
               
               S('START',$start);
               S('END',$end);
               S('SHOP_ID',$shopid);

                if($res===false){
                    $this->error(L('_ERROR_SETTING_').L('_PERIOD_'));
                }else{
                   S('DB_CONFIG_DATA',null);
                   $this->success('设置成功'.L('_PERIOD_'),'refresh');
                }

            }else{
                $start=S('START');
                $end=S('END');
                $shopid=S('SHOP_ID');
                $this->meta_title = L('_INDEX_MANAGE_');
               
               

                $count['start']=$start;
                $count['end']=$end;
                $count['shopid']=$shopid;



                $map['aid'] = array('eq',session('user_auth.aid'));
                $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
                $this->assign('shops', $shops);
               
                $count_day=($end-$start)/86400+1;
                for ($i = $count_day; $i--; $i >= 0) {
                    $day = $end - $i * 86400;
                    $day_after = $end - ($i - 1) * 86400;
                    //派送者
                   
                    $week_map=array('Mon'=>L('_MON_'),'Tue'=>L('_TUES_'),'Wed'=>L('_WEDNES_'),'Thu'=>L('_THURS_'),'Fri'=>L('_FRI_'),'Sat'=>'<strong>'.L('_SATUR_').'</strong>','Sun'=>'<strong>'.L('_SUN_').'</strong>');
                    $week[] = date('m月d日 ', $day). $week_map[date('D',$day)];
                    $thisDay = M('FeedbackList')->field('count(id) as total,count(CASE WHEN product=1 THEN id END) AS product_best,count(CASE WHEN product=2 THEN id END) AS product_good,count(CASE WHEN product=3 THEN id END) AS product_bad,count(CASE WHEN service=1 THEN id END) AS service_best,count(CASE WHEN service=2 THEN id END) AS service_good,count(CASE WHEN service=3 THEN id END) AS service_bad' )->where('shopid='.$shopid.' and status>-1  and create_time >=' . $day . ' and create_time < ' . $day_after)-> find();
                    //数据进行必要的处理
                    $thisDay['service_best'].='('.round ($thisDay['service_best']/$thisDay['total']*100,2).'%)';
                    $thisDay['service_good'].='('.round ($thisDay['service_good']/$thisDay['total']*100,2).'%)';
                    $thisDay['service_bad'].='('.round ($thisDay['service_bad']/$thisDay['total']*100,2).'%)';
                    $thisDay['product_best'].='('.round ($thisDay['product_best']/$thisDay['total']*100,2).'%)';
                    $thisDay['product_good'].='('.round ($thisDay['product_good']/$thisDay['total']*100,2).'%)';
                    $thisDay['product_bad'].='('.round ($thisDay['product_bad']/$thisDay['total']*100,2).'%)';
                   
                    $eachDay[] = $thisDay;
                    $product_bad[] = floatval($thisDay['product_bad']);
                    $service_bad[] = floatval($thisDay['service_bad']);

                }


                $eachDay=array_combine($week,$eachDay);
                
                
                foreach($eachDay as &$v){
                   if($v['product_bad']>0)$v['product_bad']="<font color='red'>".$v['product_bad']."</font>";
                   if($v['service_bad']>0)$v['service_bad']="<font color='red'>".$v['service_bad']."</font>";
                }

                $count['last_day']['days'] = json_encode($week);
                $count['last_day']['product_bad'] = json_encode($product_bad);
                $count['last_day']['service_bad'] = json_encode($service_bad);
                $this->assign('week', $week);
                $this->assign('eachDay', $eachDay);
                


               
                $total = M('FeedbackList')->field('count(id) as total,count(CASE WHEN product=1 THEN id END) AS product_best,count(CASE WHEN product=2 THEN id END) AS product_good,count(CASE WHEN product=3 THEN id END) AS product_bad,count(CASE WHEN service=1 THEN id END) AS service_best,count(CASE WHEN service=2 THEN id END) AS service_good,count(CASE WHEN service=3 THEN id END) AS service_bad' )->where('shopid='.$shopid.' and status>-1  and create_time >=' . $start . ' and create_time < ' . $end)-> find();
               
                   $total['service_best']=$total['service_best'].'('.floor($total['service_best']/$total['total']*100).'%)';
                   $total['service_good']=$total['service_good'].'('.floor($total['service_good']/$total['total']*100).'%)';
                   $total['service_bad']=$total['service_bad'].'('.floor($total['service_bad']/$total['total']*100).'%)';
                   $total['product_best']=$total['product_best'].'('.floor($total['product_best']/$total['total']*100).'%)';
                   $total['product_good']=$total['product_good'].'('.floor($total['product_good']/$total['total']*100).'%)';
                   $total['product_bad']=$total['product_bad'].'('.floor($total['product_bad']/$total['total']*100).'%)';
               
                $this->assign('total', $total);  



                

               
               
               
                // dump($count);exit;

                $this->assign('count', $count);
               
                $this->display(T('Application://Feedback@Index/index'));
            }
    }

   

     public function feedbackList($page = 1,  $r = 20,$shopid='',$service='',$product='',$nickname='',$start_time='',$end_time='')
    {
       
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
        if ($nickname != '') {
            $map['nickname|openid|id'] = array('like', '%' . $nickname . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($start_time != '' and $end_time != '') {
            $map['create_time'] = array('between', array($start_time,$end_time));
        }


        if ($shopid) $map['shopid'] = $shopid;
        if ($service) $map['service'] = $service;
        if ($product) $map['product'] = $product;
        
     
        $list = $this->Model->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = $this->Model->where($map)->count();

        foreach ($list as &$v) {
         $v['service'] = $this->rank[ $v['service']];
         $v['product'] = $this->rank[ $v['product']];
         $wechat= D('Wechat/Wechat')->info( $v['appid']);
         $v['appid'] =$wechat['name'];
         
        }
        unset($map);
       
        $shops =  D('Qwechat/QwechatShop')->getData('','id,name as value');
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('客情管理' . $WechatTitle)

          
            ->setStatusUrl(U('Feedback/setListStatus'))->buttonDelete()->buttonNew(U('Feedback/index/sendRob'),'发送今日邀请')
            ->setSelectPostUrl(U('Admin/Feedback/FeedbackList'))
            ->select('','shopid','select','','','',array_merge(array(array('appid'=>0,'value'=>'全部分店')),$shops))
            ->select('','product','select','','','',array(array('id'=>0,'value'=>'产品评价'),array('id'=>1,'value'=>'好评'),array('id'=>2,'value'=>'中评'),array('id'=>3,'value'=>'差评')))
             ->select('','service','select','','','',array(array('id'=>0,'value'=>'服务评价'),array('id'=>1,'value'=>'好评'),array('id'=>2,'value'=>'中评'),array('id'=>3,'value'=>'差评')))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'选择状态'),array('id'=>1,'value'=>'未处理'),array('id'=>2,'value'=>'已处理')))
            ->setSearchPostUrl(U('Admin/Feedback/orders'))->search('顾客', 'nickname')->search('手机', 'mobile')->search('开始时间', 'start_time','date')->search('结束时间', 'end_time','date')
            ->keyLink('nickname','反馈者', 'orderWay?oid=###')
            ->keyText('product', '产品')->keyText('service', '服务')
            ->keyText('target', '对象')->keyText('content', '描述','600px')
            ->keyCreateTime()->keyDoActionEdit('editList?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function setListStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('FeedbackList', $ids, $status);
    }


    public function editList($id = null, $name = '')
    {
       
        if (IS_POST) {
            if (D('Feedback/FeedbackList')->editData()) {
                //缓存
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('FeedbackList'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').D('Feedback/FeedbackList')->getError());
            }
        }else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $data = M('FeedbackList')->find( $id);
             } else {
                $data = array('create_time' => time());
            }

            $shops =  D('Qwechat/QwechatShop')->getData('','id,name');
            $shops =array_column($shops, 'name', 'id');
    
   
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($isEdit ? '修改反馈' : '添加反馈') 
            ->keyId()->keySelect('shopid','分店','',$shops)   
            ->keySelect('product','产品','',$this->rank,61) ->keySelect('service','服务','',$this->rank,62) 
            ->keyEditor('content','订单备注')->data($data)
            ->buttonSubmit(U('editList'))->buttonBack()
            ->display();
         
         }
   }

    public function gift($page = 1,  $r = 20,$shopid='',$service='',$product='',$nickname='',$start_time='',$end_time='')
    {
       
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        $map['aid'] = array('eq',session('user_auth.aid'));
      
        
     
        $list = $this->giftModel->where($map)->page($page, $r)->order('id desc')->select();
        $totalCount = $this->giftModel->where($map)->count();

        foreach ($list as &$v) {
         $v['gift_auto'] = $v['gift_auto']?'已设置':'-';
         $v['gift1'] = $v['gift1']?'已设置':'-';
         $v['gift2'] = $v['gift2']?'已设置':'-';
         $v['gift3'] = $v['gift3']?'已设置':'-';
         $v['gift4'] = $v['gift4']?'已设置':'-';
         
        }
        unset($map);
       
       
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('奖品管理' )

          
            ->setStatusUrl(U('Feedback/setGiftStatus'))->buttonDelete()->buttonNew(U('Feedback/create_gifts'))
            ->setSearchPostUrl(U('Admin/Feedback/game'))->search('顾客', 'nickname')->search('手机', 'mobile')->search('开始时间', 'start_time','date')->search('结束时间', 'end_time','date')
            ->keyLink('wechat','反馈者', 'editGift?id=###')->keyText('gift_auto', '自动发送')
            ->keyText('gift1', '一等奖')->keyText('gift2', '二等奖')
            ->keyText('gift3', '三等奖')->keyText('gift4', '四等奖')
            ->keyDoActionEdit('editGift?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }

    public function create_gifts()
    {
         $map = array('status' => array('EGT', 0));
         $map['aid'] = array('eq',session('user_auth.aid'));
         $wechats =  D('Wechat/Wechat')->where($map)->select();
        
         foreach ($wechats as $key => $wechat) {
           $have= D('Feedback/FeedbackGift')->where (array('appid'=>$wechat['id'],'status'=> array('EGT', 0)))->find();
           if (!$have)D('Feedback/FeedbackGift')->where (array('appid'=>$wechat['id']))->add(array('aid'=>session('user_auth.aid'),'appid'=>$wechat['id'],'wechat'=>$wechat['name']));
          
         }
         $this->success('创建成功');
    }

     public function setGiftStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('FeedbackGift', $ids, $status);
    }


    public function editGift($id = null, $name = '')
    {
       
        if (IS_POST) {
            if (D('Feedback/FeedbackGift')->editData()) {
                //缓存
                $this->success($title.L('_SUCCESS_').L('_PERIOD_'), U('gift'));
            } else {
                $this->error($title.L('_FAIL_').L('_EXCLAMATION_').D('Feedback/FeedbackGift')->getError());
            }
        }else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;

            //如果是编辑模式，读取贴吧的属性
            if ($isEdit) {
                $data = M('FeedbackGift')->find( $id);
             } else {
                $data = array('create_time' => time());
            }
         
            $map['appid']=$data['appid'];
            $cards = D('Wechat/WechatCard')->getCards($map,'title,id');
            $cards =array_column($cards, 'title', 'id');
           
    
   
           //显示页面
            $builder = new AdminConfigBuilder();
            $builder->title($data['wechat'].($isEdit ? '修改奖品' : '添加奖品')) 
            ->keyId()  
            ->keySelect('gift_auto','自动发送','',$cards)->keySelect('gift1','一等奖','',$cards) ->keySelect('gift2','二等奖','',$cards) ->keySelect('gift3','三等奖','',$cards) ->keySelect('gift4','四等奖','',$cards)    
            ->data($data)
            ->buttonSubmit(U('editGift'))->buttonBack()
            ->display();
          
         }
   }

    



     public function config()
    {


        $builder=new AdminConfigBuilder();
        $data=$builder->handleConfig();

        $wechats=D('Qwechat/Qwechat')->getQwechats();
        $wechats =array_column($wechats, 'name', 'id');

        $builder->title('系统配置')->data($data);
        $builder->keySelect('QWECHAT_ROB','接收客情','这个企业微信会负责接收客情',$wechats)
        ->keyText('SUCCESS_URL','评价后跳转','评价后会跳转到这个网址')
        
        ->buttonSubmit()->buttonBack()
        ->display();
    }


    
}
