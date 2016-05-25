<?php
/**
 * Created by PhpStorm.
 * User: caipeichao
 * Date: 14-3-14
 * Time: AM9:28
 */

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendlyDate($sTime,$type = 'normal',$alt = 'false') {
    if (!$sTime)
        return '';
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime      =   time();
    $dTime      =   $cTime - $sTime;
    $dDay       =   intval(date("z",$cTime)) - intval(date("z",$sTime));
    //$dDay     =   intval($dTime/3600/24);
    $dYear      =   intval(date("Y",$cTime)) - intval(date("Y",$sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if($type=='normal'){
        if( $dTime < 60 ){
            if($dTime < 10){
                return L('_JUST_');    //by yangjs
            }else{
                return intval(floor($dTime / 10) * 10).L('_SECONDS_AGO_');
            }
        }elseif( $dTime < 3600 ){
            return intval($dTime/60).L('_MINUTES_AGO_');
            //今天的数据.年份相同.日期相同.
        }elseif( $dYear==0 && $dDay == 0  ){
            //return intval($dTime/3600).L('_HOURS_AGO_');
            return L('_TODAY_').date('H:i',$sTime);
        }elseif($dYear==0){
            return date("m月d日 H:i",$sTime);
        }else{
            return date("Y-m-d H:i",$sTime);
        }
    }elseif($type=='mohu'){
        if( $dTime < 60 ){
            return $dTime.L('_SECONDS_AGO_');
        }elseif( $dTime < 3600 ){
            return intval($dTime/60).L('_MINUTES_AGO_');
        }elseif( $dTime >= 3600 && $dDay == 0  ){
            return intval($dTime/3600).L('_HOURS_AGO_');
        }elseif( $dDay > 0 && $dDay<=7 ){
            return intval($dDay).L('_DAYS_AGO_');
        }elseif( $dDay > 7 &&  $dDay <= 30 ){
            return intval($dDay/7) . L('_WEEK_AGO_');
        }elseif( $dDay > 30 ){
            return intval($dDay/30) . L('_A_MONTH_AGO_');
        }
        //full: Y-m-d , H:i:s
    }elseif($type=='full'){
        return date("Y-m-d , H:i:s",$sTime);
    }elseif($type=='ymd'){
        return date("Y-m-d",$sTime);
    }else{
        if( $dTime < 60 ){
            return $dTime.L('_SECONDS_AGO_');
        }elseif( $dTime < 3600 ){
            return intval($dTime/60).L('_MINUTES_AGO_');
        }elseif( $dTime >= 3600 && $dDay == 0  ){
            return intval($dTime/3600).L('_HOURS_AGO_');
        }elseif($dYear==0){
            return date("Y-m-d H:i:s",$sTime);
        }else{
            return date("Y-m-d H:i:s",$sTime);
        }
    }
}

function dateformat($date){
    if(!empty($date))
        return date('Y-m-d',$date);
    else
        return '';
}

/* 
*function：计算两个日期相隔多少年，多少月，多少天 
*param string $date1[格式如：2011-11-5] 
*param string $date2[格式如：2012-12-01] 
*return array array('年','月','日'); 
*/  
function diffDate($date1,$date2){  

    
    if(strtotime($date1)>strtotime($date2)){  
        $tmp=$date2;  
        $date2=$date1;  
        $date1=$tmp;
        $back['bool']=0;
    }  
    list($Y1,$m1,$d1)=explode('-',$date1);  
    list($Y2,$m2,$d2)=explode('-',$date2);  
    $Y=$Y2-$Y1;  
    $m=$m2-$m1;  
    $d=$d2-$d1;  
    if($d<0){  
        $d+=(int)date('t',strtotime("-1 month $date2"));  
        $m--;  
    }  
    if($m<0){  
        $m+=12;  
        $y--;  
    }

    $back['year']=$Y;
    $back['month']=$m;
    $back['day']=$d;
    
   
    return $back;  
}  