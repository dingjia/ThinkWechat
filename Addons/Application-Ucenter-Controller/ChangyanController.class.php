<?php

// +----------------------------------------------------------------------
// + 畅言评论 OS单点登录处理
// + Author：757871450@qq.com
// +----------------------------------------------------------------------
/*
获取用户信息接口URL：	http://www.xx.com/index.php?s=/ucenter/Changyan/getuserinfo
用户退出接口URL：		http://www.xx.com/index.php?s=/ucenter/Changyan/logout
*/

namespace Ucenter\Controller;
use Think\Controller;

class ChangyanController extends BaseController{
	
	//初始化
	public function _initialize() {

		if(is_login()){ 
			 $this->userinfo=$this->userInfo(is_login());//当前登录用户信息 $this->user_info
			 $this->userid=$this->userinfo['uid'];
			 //dump($this->userinfo);
		}else{
			$this->userid=0;
		}
    }
	
	//当前登录用户的信息
    private function userInfo($uid = null)
    {
        $user_info = query_user(array('avatar128', 'nickname', 'uid', 'space_url', 'score', 'title', 'fans', 'following', 'weibocount', 'rank_link', 'signature'), $uid);
        //获取用户封面id
        $map=getUserConfigMap('user_cover','',$uid);
        $map['role_id']=0;
        $model=D('Ucenter/UserConfig');
        $cover=$model->findData($map);
        $user_info['cover_id']=$cover['value'];
        $user_info['cover_path']=getThumbImageById($cover['value'],1140,230);

        $user_info['tags']=D('Ucenter/UserTagLink')->getUserTag($uid);
        $this->assign('user_info', $user_info);
        return $user_info;
    }

	//=====================================获取用户信息接口==========================
	//获取用户信息接口
	public function getuserinfo(){
	
        if($this->userid != 0){
            $avatar		='http://'.$_SERVER['HTTP_HOST'].'/'.$this->userinfo['avatar128'];
			$profileUrl	="";
			$ret=array(  
				"is_login"=>1, //已登录，返回登录的用户信息
				"user"=>array(
					"user_id"=>$this->userinfo['uid'],		//用户id
					"nickname"=>$this->userinfo['nickname'],	//用户昵称
					"img_url"=>$avatar,							//用户头像，如果没有可以返回""
					"profile_url"=>$profileUrl,					//用户主页地址。如果没有可以返回""
					"sign"=>$this->sign($CY_APPKEY,$avatar,$this->userinfo['nickname'],$profileUrl,$this->userinfo['uid'])
            	)
			);
        }else{
            $ret=array("is_login"=>0);//未登录
        }
        
        echo $_GET['callback'].'('.json_encode($ret).')';    
    }
	
	//获取用户信息接口 生成签名
	public static function sign($key, $imgUrl, $nickname, $profileUrl, $isvUserId){
            $toSign = "img_url=".$imgUrl."&nickname=".$nickname."&profile_url=".$profileUrl."&user_id=".$isvUserId;
            $signature = base64_encode(hash_hmac("sha1", $toSign, $key, true));
            return $signature;
     } 
	 
	 //畅言点击退出，也退出本站登录
    public function logout() {
        D('Member')->logout();
		
		$ret=array("reload_page"=>1);//刷新页面
		echo $_GET['callback'].'('.json_encode($ret).')';  
    }
	 

}
?>