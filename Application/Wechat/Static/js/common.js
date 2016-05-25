var Wechat = {
    'following': function (obj) {
        var $obj = $(obj);
        var id = $obj.data('id');
        $.post(U('Wechat/index/doFollowing'), {id: id}, function (msg) {
            handleAjax(msg);
            if (msg.status == 1) {
                if(msg.follow==0){
                    $obj.find('span').text('关注');
                    $obj.find('i').attr('class','icon-heart-empty');
                    $obj.attr('class','btn btn-primary btn-lg');
                }else{
                    $obj.find('span').text('已关注');
                    $obj.find('i').attr('class','icon-heart');
                    $obj.attr('class','btn btn-default btn-lg');
                }


            }

        })
    },
    'following_simple': function (obj) {

        var id = $(this).data('id');
        var $obj = $('[data-role=followingSimple][data-id='+id+']');

        $.post(U('Wechat/index/doFollowing'), {id: id}, function (msg) {
            handleAjax(msg);
            if (msg.status == 1) {
                if(msg.follow==0){
                    $obj.each(function(){
                        $(this).find('span').text('+ 关注');
                    })
                }else{
                    $obj.each(function(){
                        $(this).find('span').text('- 已关注');
                    })
                }


            }

        })
    }
};


$(function () {
    bindLzlEvent();
    $('[data-role=followingSimple]').click(Wechat.following_simple);
});


var getArgs = function (uri) {
    if (!uri) return {};
    var obj = {},
        args = uri.split("&"),
        l, arg;
    l = args.length;
    while (l-- > 0) {
        arg = args[l];
        if (!arg) {
            continue;
        }
        arg = arg.split("=");
        obj[arg[0]] = arg[1];
    }
    return obj;
};

function bindLzlEvent() {
    var reply_btn = $('.reply_lzl_btn');
    reply_btn.unbind('click');
    reply_btn.click(function () {
        var args = getArgs($(this).attr('args'));

        var to_f_reply_id = args['to_f_reply_id'];
        $('#show_textarea_' + to_f_reply_id).show();

        $('#reply_' + to_f_reply_id).val('回复@' + args['to_nickname'] + ' ：');
        $('#submit_' + to_f_reply_id).attr('args', $(this).attr('args'));

    });
    $('.input_tips').unbind('keypress');
    $('.input_tips').keypress(function (e) {
        if (e.ctrlKey && e.which == 13 || e.which == 10) {
            var re = $(this).attr('args');
            var args = getArgs($('#submit_' + re).attr('args'));

            var to_f_reply_id = args['to_f_reply_id'];
            var post_id = $('#submit_' + re).attr('post_id');
            var content = $('#reply_' + to_f_reply_id).val();
            var to_reply_id = args['to_reply_id'];
            var to_uid = args['to_uid'];
            submitLZLReply(post_id, to_f_reply_id, to_reply_id, to_uid, content);
        }
        // this.preventDefault();
    });

    var submitLZLReply = function (post_id, to_f_reply_id, to_reply_id, to_uid, content, p) {
        var url = U('Wechat/Lzl/doSendLZLReply');

        $.post(url, {
            post_id: post_id,
            to_f_reply_id: to_f_reply_id,
            to_reply_id: to_reply_id,
            to_uid: to_uid,
            content: content,
            p: p
        }, function (msg) {
            if (msg.status) {
                toast.success(msg.info, '温馨提示');
                $('#lzl_reply_list_' + to_f_reply_id).load(U('Wechat/LZL/lzlList', ['to_f_reply_id', to_f_reply_id, 'page', msg.url], true), function () {
                    ucard()
                })
                $('#reply_' + to_f_reply_id).val('');
            } else {
                toast.error(msg.info, '温馨提示');
            }
        }, 'json');
    };

    $(".submitReply").unbind('.submitReply');
    $(".submitReply").click(function () {
        var args = getArgs($(this).attr('args'));
        var to_f_reply_id = args['to_f_reply_id'];
        var post_id = $(this).attr('post_id');
        var content = $('#reply_' + to_f_reply_id).val();
        var to_reply_id = args['to_reply_id'];
        var to_uid = args['to_uid'];
        var p = args['p'];

        submitLZLReply(post_id, to_f_reply_id, to_reply_id, to_uid, content, p);

       preventDefault();
    });

    $('.reply_btn').unbind('click');
    $('.reply_btn').click(function (event) {
        var args = $(this).attr('args');
        $('#lzl_reply_div_' + args).toggle();
        event.preventDefault();
        //this.preventDefault();
    });
    $('.show_textarea').unbind('click');
    $('.show_textarea').click(function () {
        var args = $(this).attr('args');
        $('#show_textarea_' + args).toggle();
        this.preventDefault();
    })

    $('.del_lzl_reply').unbind('click');
    $('.del_lzl_reply').click(function (e) {
        if (confirm('确定要删除该回复么？')) {
            var args = getArgs($(this).attr('args'));
            var to_f_reply_id = args['to_f_reply_id'];
            var url = U('Wechat/LZL/delLZLReply');
            $.post(url, {id: args['lzl_reply_id']}, function (msg) {
                if (msg.status) {
                    toast.success('删除成功', '温馨提示');
                    $('#Wechat_lzl_reply_' + args['lzl_reply_id']).hide();
                    $('#reply_' + to_f_reply_id).val('');
                    $('#reply_btn_' + msg.post_reply_id).html('回复(' + msg.lzl_reply_count + ')');
                } else {
                    toast.error('删除失败', '温馨提示');
                }
            });
        }
        e.preventDefault();
        return false;

    });
    $('.del_post_btn').unbind('click');
    $('.del_post_btn').click(function () {
        if (confirm('确定要删除该贴子么？')) {
            var args = getArgs($(this).attr('args'));
            var url = U('Wechat/Index/delPost');
            $.post(url, {id: args['post_id']}, function (msg) {
                if (msg.status) {
                    toast.success('删除成功', '温馨提示');
                    location.href=msg.url;
                } else {
                    toast.error(msg.info, '温馨提示');
                }
            });

        }
        this.preventDefault();
    });
    $('.del_reply_btn').unbind('click');
    $('.del_reply_btn').click(function () {
        if (confirm('确定要删除该回复么？')) {
            var args = getArgs($(this).attr('args'));
            var url = U('Wechat/Index/delPostReply');
            $.post(url, {id: args['reply_id']}, function (msg) {
                if (msg.status) {
                    toast.success('删除成功', '温馨提示');
                    location.reload();
                } else {
                    toast.error('删除失败', '温馨提示');
                }
            });

        }
        this.preventDefault();
    });
}
function changePage(id, p) {
    $('#lzl_reply_list_' + id).load(U('Wechat/LZL/lzllist', ['to_f_reply_id', id, 'page', p], true), function () {
        ucard();
    })
}