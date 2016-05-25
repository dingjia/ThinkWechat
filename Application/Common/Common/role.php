<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-3-13
 * Time: 下午5:28
 * @author 郑钟良<zzl@ourstu.com>
 */




/**
 * 获取当前用户登录的角色的标识
 * @return int 角色id
 * @author 郑钟良<zzl@ourstu.com>
 */
function get_login_role()
{
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['role_id'] : 0;
    }
}

/**
 * 获取当前用户登录的角色是否审核通过
 * @return status 用户角色审核状态  1：通过，2：待审核，0：审核失败
 * @author 郑钟良<zzl@ourstu.com>
 */
function get_login_role_audit()
{
    $user = session('user_auth');
    if (empty($user)) {
        return 0;
    } else {
        return session('user_auth_sign') == data_auth_sign($user) ? $user['audit'] : 0;
    }
}

/**
 * 根据用户uid获取角色id
 * @param int $uid
 * @return int
 * @author 郑钟良<zzl@ourstu.com>
 */
function get_role_id($uid=0)
{
    !$uid&&$uid=is_login();
    if($uid==is_login()){//自己
        $role_id=get_login_role();
    }else{//不是自己
        $role_id=query_user(array('show_role'),$uid);
        $role_id=$role_id['show_role'];
    }
    return $role_id;
}

/**
 * 获取角色配置表 D('RoleConfig')查询条件
 * @param $type 类型
 * @param int $role_id 角色id
 * @return mixed 查询条件 $map
 * @author 郑钟良<zzl@ourstu.com>
 */
function getRoleConfigMap($type,$role_id=0){
    $map['role_id']=$role_id;
    $map['category']='';
    $map['name']=$type;
    switch($type){
        case 'score'://积分
        case 'avatar'://默认头像
        case 'rank'://默认头衔
        case 'user_tag'://用户可拥有标签
            break;
        case 'expend_field'://角色拥有的扩展字段
        case 'register_expend_field'://注册时角色要填写的扩展字段
            $map['category']='expend_field';
            break;
        default:;
    }
    return $map;
}

/**
 * 清除角色缓存
 * @param int $role_id 角色id
 * @param $type 要清除的缓存，空：清除所有；字符串（Role_Expend_Info_）：清除一个缓存；数组array('Role_Expend_Info_','Role_Avatar_Id_','Role_Register_Expend_Info_')：清除多个缓存
 * @return bool
 * @author 郑钟良<zzl@ourstu.com>
 */
function clear_role_cache($role_id=0,$type){
    if(isset($type)){
        if(is_array($type)){
            foreach($type as $val){
                S($val.$role_id,null);
            }
            unset($val);
        }else{
            S($type.$role_id,null);
        }
    }else{
        S('Role_Expend_Info_'.$role_id,null);
        S('Role_Avatar_Id_'.$role_id,null);
        S('Role_Register_Expend_Info_'.$role_id,null);
    }
    return true;
}