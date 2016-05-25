<?php

return array(

    'switch'=>array(//配置在表单中的键名 ,这个会是config[title]
        'title'=>'是否开启第一信息短信：',//表单的文字
        'type'=>'radio',		 //表单的类型：text、textarea、checkbox、radio、select等
        'options'=>array(
            '1'=>'启用',
            '0'=>'禁用',
        ),
        'value'=>'1',
        'tip'=>'默认开启'
    ),


);


