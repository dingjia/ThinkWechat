var announcement_now=1;
var announcement_i=0;
$(function(){
    $('#announcement').css('margin-top',$('#nav_bar').height()+$('#sub_nav').height()+15);
    $('#announcement').css('margin-bottom',-($('#nav_bar').height()+$('#sub_nav').height()));
    $('.one_announcement').hover(function(){
        $('[data-role="unshow"]').children('.word').show();
    }).mouseleave(function(){
            $('[data-role="unshow"]').children('.word').hide();
        });
    start();
    run_over();
    $('[data-role="unshow"]').click(function(){
        var id=$(this).attr('data-id');
        //当前不再显示
        announcement_num=parseInt(announcement_num)-1;
        if(announcement_num==0){
            $('.announcement').slideUp();
        }else{
            $(this).parents('.one_announcement').slideUp();
            if(announcement_now>announcement_num){
                announcement_now=1;
                setTimeout(function(){
                    $('.announcement_list').css('top','0px');
                },500)
            }
        }
        stop();
        run_over();
        //写入缓存
        var unshow_announcement=$.cookie('announcement_cookie_ids');
        unshow_announcement=unShowIds(unshow_announcement,id);
        $.cookie('announcement_cookie_ids',unshow_announcement);
    });
});
var up_block=function(){
    if(announcement_num!=1){
        var top=-71*announcement_now;
        $('.announcement_list').animate({'top':top+'px'});
        if(announcement_now==announcement_num){
            announcement_now=1;
            setTimeout(function(){
                $('.announcement_list').css('top','0px');
            },500)
        }else{
            announcement_now=parseInt(announcement_now)+1;
        }
    }
    return true;
}
var run_over=function(){
    if(announcement_num!=1){
        $('.announcement').mouseover(stop).mouseleave(start);
    }
    return true;
}
var stop=function(){
    clearInterval(announcement_i);
    return true;
}
var start=function(){
    stop();
    if(announcement_num!=1){
        announcement_i=setInterval(up_block,5000);
    }
    return true;
}

function unShowIds(unshow_ids, id) {
    var newArr = [];
    if(unshow_ids!=undefined){
        var attachArr = unshow_ids.split(',');
        for (var i in attachArr) {
            if (attachArr[i] !== '' && attachArr[i] !== id.toString()) {
                newArr.push(attachArr[i]);
            }
        }
    }
    newArr.push(id);
    unshow_ids=newArr.join(',');
    return unshow_ids;
}