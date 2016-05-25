<?php
return array(
	'display' => array(//配置在表单中的键名 ,这个会是config[random]
		'title' => '是否显示投票:',//表单的文字
		'type' => 'radio',         //表单的类型：text、textarea、checkbox、radio、select等
		'options' => array(         //select 和radion、checkbox的子选项
			'1' => '开启',         //值=>文字
			'0' => '关闭',
		),
		'value' => '0',             //表单的默认值
	),
	'defaultid' => array(
		'title' => '默认显示的投票id（纯数字）',
		'type' => 'text',
		'value' => "20",
	)
);
                        