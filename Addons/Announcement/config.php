<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 15-4-23
 * Time: 下午3:30
 * @author 郑钟良<zzl@ourstu.com>
 */

return array(
    'color'=>array(
        'title'=>'设置默认颜色：',
        'type'=>'radio',
        'options'=>array(
            ' '=>'默认(灰色)',
            'alert-success'=>'绿色',
            'alert-info'=>'蓝色',
            'alert-warning'=>'黄色',
            'alert-danger'=>'红色'
        ),
        'value'=>' ',//安装时有用，之后都是从数据库读取配置
    ),
    'addons_cache'=>array(
        'type'=>'hidden',
        'value'=>'ANNOUNCEMENT_COLOR_CONFIG',
    )
);