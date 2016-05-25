<?php

namespace Wechat\Controller;
use Think\Controller;

class WxpayController extends Controller {
	//初始化
	public function _initialize()
	{
		
        $this->buy_url ="http://".$_SERVER[HTTP_HOST]. U('Wechat/Wxpay/index');
        $this->notify_url ="http://".$_SERVER[HTTP_HOST]. U('Wechat/Wxpay/notify');
	}

  /*
  直接支付模式
  $for  为谁支付
  $appid 发起支付微信
  $product
  $um
  $price
  $url
   */
	public function index($buyer='',$appid='',$product='',$disc='',$um=1,$price='',$url='',$ext=''){
		    
        //数据有效性
        if (!$appid)$appid=D('Wechat/Wechat')->mustOneWechat();
        if(!$price || !$product)$this->error('参数错误，请使用合法参数发起支付');
        ini_set('date.timezone','Asia/Shanghai');
        $options =D('Wechat/Wechat')->GetOptions($appid);
        //全局引入微信支付类
        require_once(APP_PATH.'Wechat/PaySdk/example/WxPay.JsApiPay.php');
        //①、获取用户openid
        $tools = new \JsApiPay();
       
        $openId = $tools->GetOpenid();

        
       
        $data=array(
        'aid'=>$options['aid'],
        'buyer'=>$buyer,
        'product'=>$product,
        'disc'=>$disc?$disc:'无描述',
        'um'=>$um,
        'price'=>$price,
        'url'=>($url?U($url.'/Pay/paySuccess'):U('paySuccess')),
        'ext'=>$ext
        );
      
        //②、统一下单
        $input = new \WxPayUnifiedOrder();

        // $input->SetAppid($options['appid']);
        // $input->SetMch_id($options['mchid']?$options['mchid']:'10032251');
      
        $input->SetBody($product);
        $input->SetAttach($product);
         
        $input->SetOut_trade_no($out_trade_no=($options['mchid']?$options['mchid']:\WxPayConfig::MCHID).date("YmdHis"));
        
        $input->SetTotal_fee($price*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($disc);
        $input->SetNotify_url( $this->notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
      
      
      $order = \WxPayApi::unifiedOrder($input);
      // $this->printf_info($order);
      $jsApiParameters = $tools->GetJsApiParameters($order);
       
        //获取共享收货地址js函数参数
      $editAddress = $tools->GetEditAddressParameters();

      //写入支付记录，标记为未支付
      $data['out_trade_no']=$out_trade_no;
      $this->addPay($data);

     
       $this->assign('jsApiParameters',$jsApiParameters);
       $this->assign('data',$data);
       $this->assign('appid',$appid?$appid:\WxPayConfig::MCHID);
       $this->display( T('Application://Wechat@Index/pay') );
	}

   public function addPay($data){
     
     if ($data['buyer'] and !is_numeric($data['buyer']))$member=D('Wechat/WechatMember')->info($data['buyer']);
     if ($member['aid'])$data['aid']=$member['aid'];
     $data['appid']=$member['appid'];
     $data['sys_id']=$member['id'];
     $data['nickname']=$member['nickname'];
     $data['create_time']=time();
     M('WechatPay')->add($data);
  


   }

   

  /*
  订单支付模式
  id  订单id
   */
  public function payByOrder($id=''){
        
    ini_set('date.timezone','Asia/Shanghai');
    $order = M('shop_buy')->find($id);
    $product = D('shop/shop')->find($order['pid']);
    //获取配置的支付微信
    $appid = myConfig('SHOP_PAY_WECHAT', 2,'', 'SHOP');
    if ($appid )$appid = myConfig('SHOP_PAY_WECHAT', 1,'', 'SHOP');
    $options =D('Wechat/Wechat')->GetOptions($appid);
        
    //全局引入微信支付类
    require_once(APP_PATH.'Wechat/PaySdk/example/WxPay.JsApiPay.php');
    //①、获取用户openid
    $tools = new \JsApiPay();
    $openId = $tools->GetOpenid();

        
        //②、统一下单
        $input = new \WxPayUnifiedOrder();

        $input->SetBody($product['name']);
        $input->SetAttach($product['name']);
         
        $input->SetOut_trade_no(($options['mchid']?$options['mchid']:\WxPayConfig::MCHID).date("YmdHis"));
        
        $input->SetTotal_fee($product['price']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($product['introduct']);
        $input->SetNotify_url( $this->notify_url);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
      
      
        $order = \WxPayApi::unifiedOrder($input);
       
        $jsApiParameters = $tools->GetJsApiParameters($order);
       
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();

       $this->assign('jsApiParameters',$jsApiParameters);
       $this->assign('product',$product);
       $this->assign('order',$order);
       $this->display( T('Application://Wechat@Index/pay') );
  }

     public function paySuccess($out_trade_no='',$ext=''){
        $map['out_trade_no']=$out_trade_no;

        $order = M('WechatPay')->where($map)->find();
        M('WechatPay')->where($map)->save(array('status'=>1));
      

         
         $notice=$order['nickname']."自助购买".$order['product'].$order['um'].'份';
          
            //通知
            
        $my_rob=D('Feedback/WechatRob')->checkRob($order['aid']);
        sendQmessage('@all',$my_rob,$notice);
        
       
        echo '购买成功';
       
        
        

            
     }

    function printf_info($data){
            foreach($data as $key=>$value){
                echo "<font color='#00ff55;'>$key</font> : $value <br/>";
            }
   }

   public function notify($data='', &$msg=''){
           ini_set('date.timezone','Asia/Shanghai');
          send_mail('498944516@qq.com','微信支付在回调消息',$data.$msg);
          Log::DEBUG("call back:" . json_encode($data));
            $notfiyOutput = array();
            
            if(!array_key_exists("transaction_id", $data)){
                $msg = "输入参数不正确";
                return false;
            }
            //查询订单，判断订单真实性
            if(!$this->Queryorder($data["transaction_id"])){
                $msg = "订单查询失败";
                return false;
            }
            return true;
    }
	
	
}