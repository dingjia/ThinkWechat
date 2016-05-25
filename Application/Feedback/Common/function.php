<?php
/**
 * Created by PhpStorm.
 * User: Administrators
 * Date: 2015/10/16
 * Time: 12:49
 */

function getRoomNameLink($id = 0){
    if(empty($id)) return '错误的订单';
    $roomTitle = M('ding')->where("id={$id}")->getField('title');

    return  '<a href="'.U('ding/index/room',array('id'=>$id)). '">'. $roomTitle.'</a>';
}
function getUserLink($value = 0){
    $value = query_user(array('username', 'uid', 'space_url'), $value);

    return "<a href='" . $value['space_url'] . "' target='_blank'>[{$value[uid]}]" . $value['username'] . '</a>';
}

function curl_get($url,$timeout = 30){


    $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;

    $ch = curl_init();

    $opt = array(

        CURLOPT_URL     => $url,

        CURLOPT_POST    => 1,

        CURLOPT_HEADER  => 0,


        CURLOPT_RETURNTRANSFER  => 1,

        CURLOPT_TIMEOUT         => $timeout,

    );

    if ($ssl)

    {

        $opt[CURLOPT_SSL_VERIFYHOST] = 1;

        $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;

    }

    curl_setopt_array($ch, $opt);

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;

}
function curl_post($url, $data, $timeout = 30)

{

    $ssl = substr($url, 0, 8) == "https://" ? TRUE : FALSE;

    $ch = curl_init();

    $opt = array(

        CURLOPT_URL     => $url,

        CURLOPT_POST    => 1,

        CURLOPT_HEADER  => 0,

        CURLOPT_POSTFIELDS      => $data,

        CURLOPT_RETURNTRANSFER  => 1,

        CURLOPT_TIMEOUT         => $timeout,

    );

    if ($ssl)

    {

        $opt[CURLOPT_SSL_VERIFYHOST] = 1;

        $opt[CURLOPT_SSL_VERIFYPEER] = FALSE;

    }

    curl_setopt_array($ch, $opt);

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;

}