<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-28
 * Time: 上午11:30
 * @author 郑钟良<zzl@ourstu.com>
 */

namespace News\Controller;


use Think\Controller;
use Common\Builder\AdminConfigBuilder;
use Common\Builder\AdminListBuilder;

use Wechat\Sdk\TPWechat;
use Wechat\Sdk\Wechat;
use Wechat\Sdk\wxauth;
use Wechat\Sdk\jssdk;
use Wechat\Sdk\errCode;


class IndexController extends Controller{

    protected $newsModel;
    protected $newsDetailModel;
    protected $newsCategoryModel;

    function _initialize()
    {
        // if(isset($_POST['keywords'])){
        //     $_GET['keywords']=json_encode(trim($_POST['keywords']));
        // }
        // $keywords=$_GET['keywords'];

        $this->newsModel = D('News/News');
        $this->newsDetailModel = D('News/NewsDetail');
        // $this->newsCategoryModel = D('News/NewsCategory');

        // $tree = $this->newsCategoryModel->getTree(0,true,array('status' => 1));
        // $this->assign('tree', $tree);
        // $menu_list['left'][]=array( 'title' => '首页', 'href' => U('News/Index/index'),'tab'=>'home');
        // foreach ($tree as $category) {
        //     $menu = array('tab' => 'category_' . $category['id'], 'title' => $category['title'], 'href' => U('News/index/index', array('category' => $category['id'],'keywords'=>$keywords)));
        //     if ($category['_']) {
        //         $menu['children'][] = array( 'title' => '全部', 'href' => U('News/index/index', array('category' => $category['id'],'keywords'=>$keywords)));
        //         foreach ($category['_'] as $child)
        //             $menu['children'][] = array( 'title' => $child['title'], 'href' => U('News/index/index', array('category' => $child['id'],'keywords'=>$keywords)));
        //     }
        //     $menu_list['left'][] = $menu;
        // }
        // $menu_list['right']=array();
        // if(is_login()){
        //     $menu_list['right'][]=array('tab' => 'myNews', 'title' => '我的投稿', 'href' =>U('News/index/my'));
        // }

        // $show_edit=S('SHOW_EDIT_BUTTON');
        // if($show_edit===false){
        //     $map['can_post']=1;
        //     $map['status']=1;
        //     $show_edit=$this->newsCategoryModel->where($map)->count();
        //     S('SHOW_EDIT_BUTTON',$show_edit);
        // }
        // if($show_edit){
        //     $menu_list['right'][]=array('tab' => 'create', 'title' => '<i class="icon-edit"></i> 投稿', 'href' =>is_login()?U('News/index/edit'):"javascript:toast.error('登录后才能操作')");
        //     $menu_list['right'][]=array('type'=>'search', 'input_title' => '输入标题/摘要关键字','input_name'=>'keywords','from_method'=>'post', 'action' =>U('News/index/index'));
        // }
        // $this->assign('tab','home');
        // $this->assign('sub_menu', $menu_list);
       
        // $options=D('Wechat/Wechat')->GetOptions(40);
        // $auth = new WechatSdkModel($options['base']);
        // dump($auth);die;
    }

    public function index($page=1)
    {
        if(json_decode($_GET['keywords'])!=''){
            $keywords=json_decode($_GET['keywords']);
            $this->assign('search_keywords',$keywords);
            $map['title|description']=array('like','%'.$keywords.'%');
        }else{
            $_GET['keywords']=null;
        }
        /* 分类信息 */
        $category = I('category',0,'intval');
        $current='';
        if($category){
            $this->_category($category);
            $cates=$this->newsCategoryModel->getCategoryList(array('pid'=>$category,'status'=>1));
            if(count($cates)){
                $cates=array_column($cates,'id');
                $cates=array_merge(array($category),$cates);
                $map['category']=array('in',$cates);
            }else{
                $map['category']=$category;
            }
            $now_category=$this->newsCategoryModel->find($category);
            $cid=$now_category['pid']==0?$now_category['id']:$now_category['pid'];
            $current='category_' . $cid;
        }
        $map['dead_line']=array('gt',time());
        $map['status']=1;

        $order_field=modC('NEWS_ORDER_FIELD','create_time','News');
        $order_type=modC('NEWS_ORDER_TYPE','desc','News');
        $order='sort desc,'.$order_field.' '.$order_type;

        /* 获取当前分类下资讯列表 */
        list($list,$totalCount) = $this->newsModel->getListByPage($map,$page,$order,'*',modC('NEWS_PAGE_NUM',20,'News'));
        foreach($list as &$val){
            $val['user']=query_user(array('space_url','nickname'),$val['uid']);
        }
        unset($val);
        /* 模板赋值并渲染模板 */
        $this->assign('list', $list);
        $this->assign('category', $category);
        $this->assign('totalCount',$totalCount);
        $current= ($current==''?'home':$current);
        $this->assign('tab',$current);
        $this->display();
    }


     public function game($id=4,$openid='')
    {
       //初始化游戏
        $game=D('News/NewsGame')->info($id);

        
         //如果有分享者
        if (I('shower'))$shower_info=D('Wechat/WechatMember')-> info(I('shower'),'openid,appid,nickname,sex,headimgurl');
        if ($openid)$member=D('Wechat/WechatMember')-> info($openid,'openid,appid,nickname,sex,headimgurl');
        
        if($game['appid']){
          $appid=$game['appid'];
        }else{
          $appid=D('Wechat/Wechat')->mustOneWechat();
        }
        $this->assign('appid',$appid);

        //检查登录
         if ($appid  and is_weixin()){
         // require_once(APP_PATH.'Wechat/Sdk/Wechat.class.php');
         // require_once(APP_PATH.'Wechat/Sdk/jssdk.class.php');
         $jssdk = new jssdk($appid);
         $myjssdk=$jssdk->jssdk;
         }
                 
       
         if ($appid and !$member and is_weixin()){
         // require_once(APP_PATH.'Wechat/Sdk/wxauth.class.php');
         $auth = new wxauth($appid,$openid);
         $member=$auth->wxuser ;     
         
         }

          //判断是否有资格
        $week_map=array('Mon'=>'星期一','Tue'=>'星期二','Wed'=>'星期三','Thu'=>'星期四','Fri'=>'星期五','Sat'=>'星期六','Sun'=>'星期日');
        if ($game['showtime']){
            $showtimes=explode(',', $game['showtime']);
            foreach ($showtimes as $key => &$showtime) {
              $open.=$week_map[$showtime].'、';
           }
        }  
         
       
    

        $wx_show=array(
            'title'=>$member['nickname'].'邀请你挑战'.$game['name'],
            'desc'=>'好多奖品，你也玩玩，试试手气，一起去兑奖',
            'link'=>'http://'.$_SERVER['HTTP_HOST'].U('news/index/game', array('id'=>$game['id'])),
            'imgUrl'=>$member['headimgurl'],
        ); 
        $this->assign('myjssdk',$myjssdk);
        $this->assign('wx_show',$wx_show);
        $this->assign('member', $member);
        $this->assign('game', $game);
        $this->assign('open', $open);
        $this->display( T('Application://News@Index/'.$game['token']) );
    }

     public function setGameStatus($ids, $status)
    {
        $builder = new AdminListBuilder();
        $builder->doSetStatus('NewsGame', $ids, $status);
    }

     public function ajaxGame($id,$openid,$gift_rank,$nickname='')
    {
      
     
        $game=D('News/NewsGame')->info($id);

          //判断是否有资格
        $week_map=array('Mon'=>'星期一','Tue'=>'星期二','Wed'=>'星期三','Thu'=>'星期四','Fri'=>'星期五','Sat'=>'星期六','Sun'=>'星期日');
        if ($game['showtime']){
            $showtimes=explode(',', $game['showtime']);
            foreach ($showtimes as $key => &$showtime) {
              $open.=$week_map[$showtime].'、';
           }
           
            if (strstr($game['showtime'],date('D',time()))=='') {
                echo '每周'.$open.'开放，感谢关注';
                die;
            }
           
         }  
         

        //检查有效性
        if ($game['limit']){
        $today = date('Y-m-d', time());
        $today = strtotime($today); 
        $have= D('News/NewsGameScore')->where('openid="'.$openid.'" and game_id="'.$id.'" and create_time>='.$today)->count();
            if ($have>=$game['limit']) {
                echo '你已参与,每天只能参与'.$game['limit'].'次';
                die;
            }
        }

        $data['game_id']=$id;
        $data['game']=$game['name'];
        $data['openid']=$openid;
        $data['gift_rank']=$gift_rank;
        $data['gift']=$game['gift'.$gift_rank];
        $data['nickname']=$nickname;
        $data['status']=0;
        $data['create_time']=time();
      
            
        $res=D('News/NewsGameScore')->add($data);
        if ($res>0) echo '恭喜您获得'.$game['gift'.$gift_rank].',已经放入你的微信';


       
    }

    public function my($page=1)
    {
        $this->_needLogin();
        $map['uid']=get_uid();
        /* 获取当前分类下资讯列表 */
        list($list,$totalCount) = $this->newsModel->getListByPage($map,$page,'update_time desc','*',modC('NEWS_PAGE_NUM',20,'News'));
        foreach($list as &$val){
            if($val['dead_line']<=time()){
                $val['audit_status']= '<span style="color: #7f7b80;">已过期</span>';
            }else{
                if($val['status']==1){
                    $val['audit_status']='<span style="color: green;">审核通过</span>';
                }elseif($val['status']==2){
                    $val['audit_status']='<span style="color:#4D9EFF;">待审核</span>';
                }elseif($val['status']==-1){
                    $val['audit_status']='<span style="color: #b5b5b5;">审核失败</span>';
                }
            }

        }
        unset($val);
        /* 模板赋值并渲染模板 */
        $this->assign('list', $list);
        $this->assign('totalCount',$totalCount);

        $this->assign('tab','myNews');
        $this->display();
    }

    public function detail()
    {
       
        $aId=I('id',0,'intval');
        $openid=I('openid');    //获取文章带的openid
        $shower=I('shower');
        

        
        $info=S('news_'.$aId);
        if (!$info){
        $info=$this->newsModel->getData($aId);
        S('news_'.$aId,$info,60);   
        }

        if ( $info['dead_line']<time()) $this->error ("活动已经过期");
       
        $appid=$info['login_appid'];
       
        $info['top_title']=$info['top_title']?$info['top_title']:$info['title'];
        $info['openid']=$member['openid'];
        $info['nickname']=$member['nickname'];

        //获取文章排行
      

      
        $info['my']=D('NewsShow')->myRank($member['openid'],$aId);
        $info['shows']=D('News/NewsShow')->getRanks($aId,200);
       
        //猜您喜欢
        $map['aid']=$info['aid'];
        $map['dead_line']=array('GT',time());
        $map['status']=array('EGT',0);
        list($list,$totalCount) = $this->newsModel->getListByPage($map,$page,$order,'*',modC('NEWS_PAGE_NUM',20,'News'));
        
        $wechat=D('Wechat/Wechat')->where(array('id'=>$appid))->getfield('name');

        if ($shower) {
        $shower_info=D('Wechat/WechatMember')-> info($shower,'id,openid,appid,nickname,sex,headimgurl');   //分享者信息
       //增加此人绩效
        $view['news_id']=$aId;
        $view['shower']=$shower;
        $view['viewer']=$member['openid']?$member['openid']:$_SESSION['openid'];
        $view['nickname']=$member['nickname'];
        $view['shower_name']=$shower_info['nickname'];
        $view['showerid']=$shower_info['id'];
        D('NewsView')->view($view); 
        }


        /* 更新浏览数 */
        $map = array('id' => $aId);
        $this->newsModel->where($map)->setInc('view');
        $this->assign('info',$info);
        $this->assign('appid',$appid);
        $this->assign('list',$list);
        $this->assign('wechat',$wechat);
        
       
        if (!empty($info['detail']['template'])) { //已定制模板
            $tmpl = 'Index/tmpl/'.$info['detail']['template'];
        } else { //使用默认模板
            $tmpl = 'Index/tmpl/detail';
        }
        $this->display($tmpl);


    }

      public function formatShow($info,$member)
    {
        $info['show_title']=str_replace("{粉丝昵称}",$member['nickname'], $info['show_title']);
        $show=array(
            'title'=>$info['show_title'],
            'desc'=>$info['show_description'],
            'link'=>$info['show_link']?$info['show_link']: 'http://'.$_SERVER['HTTP_HOST'].U('news/index/detail', array('id' => $info['id'],'shower'=>$member['openid'])),
            'imgUrl'=>'http://'.$_SERVER['HTTP_HOST'].get_cover(($info['cover2']? $info['cover2']:$info['cover']), 'path'),
              ); 
        return $show;

        
    }


     public function showlist($newsid=0,$openid='')
    {
        $info=S('news_'.$newsid);
        if (!$info){
        $info=$this->newsModel->getData($newsid);
        S('news_'.$newsid,$info,60);   
        }


        // $info=$this->newsModel->getData($newsid);
        $info['my']=D('NewsShow')->myRank($openid,$newsid);
        $info['shows']=D('News/NewsShow')->getRanks($newsid,200);
        $this->assign('info', $info);
        $this->display();

        
    }



    public function showAjax($shower,$nickname,$news_id=0,$way='')
    {
      
      $data=array(
            'news_id'=>$news_id,
            'shower'=>$shower,
            'nickname'=>$nickname,
            'way'=>$way,
             );

      $res=D('News/NewsShow')->editData($data);
     
      $this->newsModel->where(array('id'=>$data['news_id']))->setInc('show_um');
       
       
    }


    public function edit()
    {
        $this->_needLogin();
        if(IS_POST){
            $this->_doEdit();
        }else{
            $aId=I('id',0,'intval');
            if($aId){
                $data=$this->newsModel->getData($aId);
                if(!check_auth('News/Index/edit',-1)){
                    if($data['uid']==is_login()){
                        if($data['status']==1){
                            $this->error('该资讯已被审核，不能被编辑！');
                        }
                    }else{
                        $this->error('你没有编辑该资讯权限！');
                    }
                }
                $this->assign('data',$data);
            }else{
                $this->checkAuth('News/Index/add',-1,'你没有投稿权限！');
            }
            $title=$aId?"编辑":"新增";
            $category=$this->newsCategoryModel->getCategoryList(array('status'=>1,'can_post'=>1),1);
            $this->assign('category',$category);
            $this->assign('title',$title);
        }
        $this->assign('tab','create');
        $this->display();
    }

    private function _doEdit()
    {
        $aId=I('post.id',0,'intval');
        $data['category']=I('post.category',0,'intval');

        if($aId){
            $data['id']=$aId;
            $now_data=$this->newsModel->getData($aId);
            if(!check_auth('News/Index/edit',-1)){
                if($now_data['uid']==is_login()){
                    if($now_data['status']==1){
                        $this->error('该资讯已被审核，不能被编辑！');
                    }
                }else{
                    $this->error('你没有编辑该资讯权限！');
                }
            }
            $category=$this->newsCategoryModel->where(array('status'=>1,'id'=>$data['category']))->find();
            if($category){
                if($category['can_post']){
                    if($category['need_audit']&&!check_auth('Admin/News/setNewsStatus')){
                        $data['status']=2;
                    }else{
                        $data['status']=1;
                    }
                }else{
                    $this->error('该分类不能投稿！');
                }
            }else{
                $this->error('该分类不存在或被禁用！');
            }
            $data['template']=$now_data['detail']['template']?:'';
        }else{
            $this->checkAuth('News/Index/add',-1,'你没有投稿权限！');
            $this->checkActionLimit('add_news','News',0,is_login(),true);
            $data['uid']=get_uid();
            $data['sort']=$data['position']=$data['view']=$data['comment']=$data['collection']=0;
            $category=$this->newsCategoryModel->where(array('status'=>1,'id'=>$data['category']))->find();
            if($category){
                if($category['can_post']){
                    if($category['need_audit']&&!check_auth('Admin/News/setNewsStatus')){
                        $data['status']=2;
                    }else{
                        $data['status']=1;
                    }
                }else{
                    $this->error('该分类不能投稿！');
                }
            }else{
                $this->error('该分类不存在或被禁用！');
            }
            $data['template']='';
        }
        $data['title']=I('post.title','','text');
        $data['cover']=I('post.cover',0,'intval');
        $data['description']=I('post.description','','text');
        $data['dead_line']=I('post.dead_line','','text');
        if($data['dead_line']==''){
            $data['dead_line']=2147483640;
        }else{
            $data['dead_line']=strtotime($data['dead_line']);
        }
        $data['source']=I('post.source','','text');
        $data['content']=I('post.content','','filter_content');

        if(!mb_strlen($data['title'],'utf-8')){
            $this->error('标题不能为空！');
        }
        if(mb_strlen($data['content'],'utf-8')<20){
            $this->error('内容不能少于20个字！');
        }

        $res=$this->newsModel->editData($data);
        $title=$aId?"编辑":"新增";
        if($res){
            if(!$aId){
                $aId=$res;
                if($category['need_audit']&&!check_auth('Admin/News/setNewsStatus')){
                    $this->success($title.'资讯成功！'.cookie('score_tip').' 请等待审核~',U('News/Index/detail',array('id'=>$aId)));
                }
            }
            $this->success($title.'资讯成功！'.cookie('score_tip'),U('News/Index/detail',array('id'=>$aId)));
        }else{
            $this->error($title.'资讯失败！'.$this->newsModel->getError());
        }
    }

    private function _category($id=0)
    {
        $now_category=$this->newsCategoryModel->getTree($id,'id,title,pid,sort',array('status'=>1));
        $this->assign('now_category',$now_category);
        return $now_category;
    }
    private function _needLogin()
    {
        if(!is_login()){
            $this->error('请先登录！');
        }
    }
} 