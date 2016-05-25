###微投票插件
这是一个比较简单的投票插件。支持外部调用。

###用法
* 页面调用
>> 在需要的地方加入  {:hook('OneVote')}

* 浏览器直接调用
>> http://XXXX.com/home/addons/execute/_addons/OneVote/_controller/ViewVote/_action/detaile/id/投票ID.html
>> 将投票ID换成计划显示的记录ID即可。
>>
>>如果觉得URL显示的太长不好看，可以配置HOME目录下的config.php文件。
>>如果想达到这个效果 ：http://sagoo.com/onevote/18.html
>>可以按下面的代码配置


代码配置如下：
```
    'URL_ROUTE_RULES'=> array(

		'onevote/:id' => 'home/addons/execute?_addons=OneVote&_controller=ViewVote&_action=detaile',
    ),
```