<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title>{$info.top_title}</title>
    <link rel="stylesheet" href="__WECHAT_CSS__/weui.css"/>
    <link rel="stylesheet" href="__WECHAT_EXAMPLE__/example.css"/>
</head>


<body ontouchstart>


<div class="weui_msg">
    <div class="weui_icon_area">
    <?php if(isset($success_message)) {
      echo '<i class="weui_icon_success weui_icon_msg"></i>';
      }else{
      echo '<i class="weui_icon_warn weui_icon_msg"></i>'; 
      }
    ?>
    
    </div>
    <div class="weui_text_area">
        <h2 class="weui_msg_title"><?php echo($success_message?'恭喜':'对不起'); ?></h2>
        <p class="weui_msg_desc"><?php echo($error_message?$error_message:$success_message); ?></p>
    </div>
    <div class="weui_opr_area">
        <p class="weui_btn_area">
            <a    class="weui_btn weui_btn_primary"><b id="wait">5</b>秒后进行跳转</a>
            <a href="<?php echo($jumpUrl); ?>" id="href" class="weui_btn weui_btn_primary">立即跳转</a>
            <a href="http://{$_SERVER['HTTP_HOST']}__ROOT__" id="href" class="weui_btn weui_btn_primary">返回首页</a>
            <a href="javascript:;" class="weui_btn weui_btn_default">取消</a>
            
        </p>
        
    </div>
    <div class="weui_extra_area">
        <a href="">查看详情</a>
    </div>
</div>


  
    <script src="__WECHAT_EXAMPLE__/zepto.min.js"></script>
    <script src="__WECHAT_EXAMPLE__/router.min.js"></script>
    <script src="__WECHAT_EXAMPLE__/example.js"></script>


<script type="text/javascript">
                (function(){
                var wait = document.getElementById('wait'),href = document.getElementById('href').href;
                var interval = setInterval(function(){
                        var time = --wait.innerHTML;
                        if(time <= 0) {
                            location.href = href;
                            clearInterval(interval);
                        };
                     }, 1000);
                  window.stop = function (){
                       
                            clearInterval(interval);
                 }
                 })();
</script>

</body>
</html>





