<?php
return array(
    'mob' => array(//配置在表单中的键名 ,这个会是config[random]
        'title' => '是否开启手机网页版:',//表单的文字
        'type' => 'radio',         //表单的类型：text、textarea、checkbox、radio、select等
        'options' => array(         //select 和radion、checkbox的子选项
            '1' => '开启',         //值=>文字
            '0' => '关闭',
        ),
        'value' => '1',             //表单的默认值
    ),
    'android' => array(
        'title' => '安卓版下载地址:',
        'type' => 'text',
    ),
    'ios' => array(
        'title' => 'IOS版下载地址:',
        'type' => 'text',
    )
);
                        