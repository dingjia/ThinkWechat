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

class OrderwayController extends AdminController
{
    protected $Model;

    function _initialize()
    {
       
        $this->Model = D('Feedback/orderWay');
        parent::_initialize();
        $this->platform=array("微信","美团","饿了么","淘点点","百度");
        $this->status=array("录单","出品","打包","派送","成功");

    }
    public function index(){
        $this->display(T('Feedback://Feedback@Admin/index'));
    }

     public function Way($page = 1, $appid = null, $r = 20, $customer = '', $mobile = '',$platform ='',$status ='')
    {
        //读取帖子数据
        $map = array('status' => array('EGT', 0));
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        if ($customer != '') {
            $map['customer'] = array('like', '%' . $customer . '%');
        }
        if ($mobile != '') {
            $map['mobile'] = array('like', '%' . $mobile . '%');
        }
        if ($appid) $map['appid'] = $appid;
        if ($platform) $map['platform'] = $platform;
        if ($status) $map['status'] = $status;
        $list = $this->Model->where($map)->page($page, $r)->select();
        $totalCount = $this->Model->where($map)->count();


        foreach ($list as &$v) {
         $v['platform'] = $this->platform[ $v['platform']];
         $v['status'] = $this->status[ $v['status']];  
        }
        unset($map);
        if (!is_administrator()) $map['aid'] = array('eq',session('user_auth.aid'));
        $wechats = M('Qwechat')->where($map)->field('id,name as value')->select();
        //读取微信基本信息
       

        //显示页面
        $builder = new AdminListBuilder();
        $builder->title('订单管理' . $WechatTitle)

            ->buttonNew(U('Feedback/editOrder'))
            ->setStatusUrl(U('Feedback/setWechatStatus'))->buttonDelete()
            ->ajaxButton(U('Feedback/getUserList'),array('status' => $status),'查看工序')
            ->buttonModalPopup(U('Wechat/sendMessage'), array('user_group' => $aUserGroup, 'role' => $aRole), L('_SEND_A_MESSAGE_'), array('data-title' => L('_MASS_MESSAGE_'), 'target-form' => 'ids', 'can_null' => 'true'))
            ->setSelectPostUrl(U('Admin/Feedback/orders'))
            ->select('','appid','select','','','',array_merge(array(array('id'=>0,'value'=>'全部分店')),$wechats))
            ->select('','platform','select','','','',array(array('id'=>0,'value'=>'选择平台'),array('id'=>1,'value'=>'美团'),array('id'=>2,'value'=>'饿了么'),array('id'=>3,'value'=>'淘点点'),array('id'=>4,'value'=>'百度')))
            ->select('','status','select','','','',array(array('id'=>0,'value'=>'选择状态'),array('id'=>1,'value'=>'录单'),array('id'=>2,'value'=>'出品'),array('id'=>3,'value'=>'打包'),array('id'=>4,'value'=>'派送'),array('id'=>5,'value'=>'签收')))
            ->keyId()->keyLink('orderid','订单号', 'Feedback/post?Wechat_id=###')->keyText('platform', '平台')->keyText('platform_um', '序号')
            ->keyText('customer', '顾客')->keyText('mobile', '电话')->keyText('address', '地址')
            ->keyCreateTime()->keyText('status', '状态')->keyDoActionEdit('editOrder?id=###')
            ->data($list)
            ->pagination($totalCount, $r)
            ->display();
    }


     public function editOrder($id = null, $name = '', $create_time =0, $status = 0,  $logo = 0)
    {
       
        if (IS_POST) {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            $model = M('FeedbackOrder');
             // dump($_POST);

                //写入数据库
                if ($isEdit) {
                    $data = $model->create();
                    $result = $model->where(array('id' => $id))->save();
                    if ($result === false) {
                        $this->error(L('_FAIL_EDIT_'));
                    }
                } else {
                    //生成订单
                    $ORDERKEY=array('A','B','C','D','E');
                    $_POST['orderid']=$ORDERKEY[intval(date('Y')) - 2016] . date('m') . date('d') . substr(microtime(), 4, 3) . sprintf('%02d', rand(0, 99));
                    $_POST['aid']=session('user_auth.aid');
                    $data = $model->create();
                    $result = $model->add();
                  
                    if (!$result) {
                        $this->error(L('_ERROR_CREATE_FAIL_'));
                    }
                }
           
         
            //返回成功信息
            $this->success($isEdit ? L('_SUCCESS_EDIT_') : L('_SUCCESS_SAVE_'),U('Orders'));
           } else {
            //判断是否为编辑模式
            $isEdit = $id ? true : false;
            if ($isEdit) {
                $orders = $this->Model->where(array('id' => $id))->find();
            } else {
                $orders = array('create_time' => time());
            }
            
            //显示页面
            $builder = new AdminConfigBuilder();
            $builder
                ->title($isEdit ? '修改订单' : '添加订单')
                ->keyId()
                ->keyText('platform_um','平台序号','','',106)->keySelect('appid', '所在分店', '', array(0=>'订阅号',1=>'认证订阅号',2=>'服务号',3=>'认证服务号'),1021)->keySelect('platform', '所在平台', '', $this->platform,1022)
                ->keyText('price','订单价格','','',106)->keySelect('appid', '支付方式', '', array(1=>'平台支付',2=>'货到付款'),1021)->keySelect('status', '订单状态', '', $this->status,1022)
                ->keyText('mobile','顾客手机','','',61)->keyText('customer','顾客姓名','','',62)
                ->keyText('address','顾客地址','','',108)->keyText('distance','距离','','',1022)
                ->keyTime('send_time','预定时间','','datetime',108) ->keySelect('is_advance', '送达时间', '', array(1=>'立即送达',2=>'预定单'),1022)
                ->keyText('description','订单备注')->keyCreateTime()
                ->keyLoctionMap('coordinate','地址坐标')
                ->data($orders)
                ->buttonSubmit(U('editOrder'))->buttonBack()
                ->display();
             }

    }


    
}
