var atwho_config;
$(function () {
    $('.open-popup-link').magnificPopup({
        type: 'inline',
        midClick: true,
        closeOnBgClick: false
    });//绑定发微博弹窗
    ucard();//绑定用户小名片
    bindTool();//回到顶部

    $('input,area').placeholder();//修复ieplace holder
    if (is_login()) {
        bindMessageChecker();//绑定用户消息
    } else {
        bindLogin();//快捷登录
        bindRegister();
    }
    checkMessage();//检查一次消息
    bindLogout();
    $('.scroller').slimScroll({
        height: '350px'
    });

    $('#scrollArea_chat').slimScroll({
        height: '320px',
        alwaysVisible: true,
        start: 'bottom'
    });
    $(document).scroll(function () {
        var left = '-' + $(window).scrollLeft() + 'px';
        $('#nav_bar').css('left', left);
        $('#sub_nav').css('left', left);
    });

    $('.adv-wrap').mouseenter(function () {
        $(this).find('.adv-tool,.adv-size').show();
    });
    $('.adv-wrap').mouseleave(function () {
        $(this).find('.adv-tool,.adv-size').hide();
    })
});

$(function () {
    /**
     * ajax-post
     * 将链接转换为ajax请求，并交给handleAjax处理
     * 参数：
     * data-confirm：如果存在，则点击后发出提示。
     * 示例：<a href="xxx" class="ajax-post">Test</a>
     */
    $(document).on('click', '.ajax-post', function (e) {
        //取消默认动作，防止跳转页面
        e.preventDefault();

        //获取参数（属性）
        var url = $(this).attr('href');
        var confirmText = $(this).attr('data-confirm');

        //如果需要的话，发出确认提示信息
        if (confirmText) {
            var result = confirm(confirmText);
            if (!result) {
                return false;
            }
        }

        //发送AJAX请求
        $.post(url, {}, function (a, b, c) {
            handleAjax(a);
        });
    });

    /**
     * ajax-form
     * 通过ajax提交表单，通过oneplus提示消息
     * 示例：<form class="ajax-form" method="post" action="xxx">
     */
    $(document).on('submit', 'form.ajax-form', function (e) {
        //取消默认动作，防止表单两次提交
        e.preventDefault();

        //禁用提交按钮，防止重复提交
        var form = $(this);
        $('[type=submit]', form).addClass('disabled');

        //获取提交地址，方式
        var action = $(this).attr('action');
        var method = $(this).attr('method');

        //检测提交地址
        if (!action) {
            return false;
        }

        //默认提交方式为get
        if (!method) {
            method = 'get';
        }

        //获取表单内容
        var formContent = $(this).serialize();

        //发送提交请求
        var callable;
        if (method == 'post') {
            callable = $.post;
        } else {
            callable = $.get;
        }
        callable(action, formContent, function (a) {
            handleAjax(a);
            $('[type=submit]', form).removeClass('disabled');
        });

        //返回
        return false;
    });
    follower.bind_follow();
});

var follower = {
    'bind_follow': function () {
        $('[data-role="follow"]').unbind('click')
        $('[data-role="follow"]').click(function () {
            var $this = $(this);
            var uid = $this.attr('data-follow-who');
            $.post(U('Core/Public/follow'), {uid: uid}, function (msg) {
                if (msg.status) {

                    $this.attr('class', $this.attr('data-before'));
                    $this.attr('data-role', 'unfollow');
                    $this.html('已关注');
                    follower.bind_follow();
                    toast.success(msg.info, L('_KINDLY_REMINDER_'));
                } else {
                    toast.error(msg.info, L('_KINDLY_REMINDER_'));
                }
            }, 'json');
        })

        $('[data-role="unfollow"]').unbind('click')
        $('[data-role="unfollow"]').click(function () {
            var $this = $(this);
            var uid = $this.attr('data-follow-who');
            $.post(U('Core/Public/unfollow'), {uid: uid}, function (msg) {
                if (msg.status) {
                    $this.attr('class', $this.attr('data-after'));
                    $this.attr('data-role', 'follow');
                    $this.html('关注');
                    follower.bind_follow();
                    toast.success(msg.info, L('_KINDLY_REMINDER_'));
                } else {
                    toast.error(msg.info, L('_KINDLY_REMINDER_'));
                }
            }, 'json');
        })
    }
}


/**
 * 绑定回到顶部
 */
function bindTool() {
    $(function () {
        $(window).on('scroll', function () {
            var st = $(document).scrollTop();
            if (st > 0) {
                $('#go-top').css('display','block');
            } else {
                $('#go-top').hide();
            }
        });
        $('#tool .go-top').on('click', function () {
            $('html,body').animate({'scrollTop': 0}, 500);
        });

        $('#go-top .uc-2vm').hover(function () {
            $('#go-top .uc-2vm-pop').removeClass('dn');
        }, function () {
            $('#go-top .uc-2vm-pop').addClass('dn');
        });
    });
}


/**
 * 绑定消息检查
 */
function bindMessageChecker() {
    $hint_count = $('#nav_hint_count');
    $nav_bandage_count = $('#nav_bandage_count');
    if (Config.GET_INFORMATION) {
        setInterval(function () {
            checkMessage();
        }, Config.GET_INFORMATION_INTERNAL);
    }

}

function play_bubble_sound() {
    playsound('Public/Core/js/ext/toastr/message.wav');
}
function paly_ios_sound() {
    playsound('Public/Core/js/ext/toastr/tip.mp3');
}
/**
 * 检查是否有新的消息
 */
function checkMessage() {
    $.get(U('Ucenter/Public/getInformation'), {}, function (msg) {
        if (msg.messages) {
            var count = parseInt($hint_count.text());
            if (count == 0) {
                $('#nav_message').html('');
            }

            paly_ios_sound();

            for (var index in msg.messages) {
                var message = msg['messages'];
                tip_message(message[index]['content']['content'] + '<div style="text-align: right"> ' + message[index]['ctime'] + '</div>', message[index]['content']['title']);
                var html = '<li><a data-url="' + message[index]['content']['web_url'] + '" onclick="Notify.readMessage(this,' + message[index]['id'] + ')">' +
                    '<h3 class="margin-top-0"><i class="icon-bell"></i>' + message[index]['content']['title'] + '</h3>' +
                    '<p>' + message[index]['content']['content'] + '</p> ' +
                    '<span class="time">' + message[index]['ctime'] + '</span></a></li>';
                $('#nav_message').prepend(html);
            }

            $hint_count.text(count + msg.messages.length);
            $nav_bandage_count.show();
            $nav_bandage_count.text(count + msg.messages.length);
        }

        if (msg.new_talks) {
            play_bubble_sound();
            //发现有新的聊天
            $.each(msg.new_talks, function (index, talk) {
                    talker.prepend_session(talk.talk);
                }
            );
        }


        function message_box_showing(talk_message) {
            return ($('#chat_id').val() == talk_message.talk_id) && ($('#chat_box').is(":visible"));
        }

        if (msg.new_talk_messages) {
            play_bubble_sound();
            //发现有新的聊天
            $.each(msg.new_talk_messages, function (index, talk_message) {
                    if (message_box_showing(talk_message)) {
                        talker.append_message(talker.fetch_message_tpl(talk_message, MID));
                        //发起一个获取聊天的请求来将该聊天设为已读
                        $.get(U('Ucenter/Session/getSession'), {id: talk_message.talk_id}, function () {

                        }, 'json');

                    }
                    else {
                        talker.set_session_unread(talk_message.talk_id);
                    }
                }
            );
        }


    }, 'json');

}


/**
 * 消息中心提示有新的消息
 * @param text
 * @param title
 */
function tip_message(text, title) {
    toast.info(text);
}


/**
 * 初始化聊天框
 */
function op_initTalkBox() {
    $('#scrollArea').slimScroll({
        height: '400px',
        alwaysVisible: true,
        start: 'bottom'
    });
}
/**
 * 向聊天窗添加一条消息
 * @param html 消息内容
 */
function op_appendMessage(html) {
    $('#scrollContainer').append(html);
    $('#scrollArea').slimScroll({scrollTo: $('#scrollContainer').height()});
    ucard();
}


/**
 * 渲染消息模板
 * @param message 消息体
 * @param mid 当前用户ID
 * @returns {string}
 */
function op_fetchMessageTpl(message, mid) {
    var tpl_right = '<div class="row talk_right">' +
        '<div class="time"><span class="timespan">{ctime}</span></div>' +
        '<div class="row">' +
        '<div class="col-md-9 bubble_outter">' +
        '<h3>我</h3>' +
        '<i class="bubble_sharp"></i>' +
        '<div class="talk_bubble">{content}' +
        '</div>' +
        '</div>' +
        ' <div class="col-md-3 "><img ucard="{uid}" class="avatar-img talk-avatar"' +
        'src="{avatar128}"/>' +
        '</div> </div> </div>';

    var tpl_left = '<div class="row">' +
        '<div class="time"><span class="timespan">{ctime}</span></div>' +
        '<div class="row">' +
        '<div class="col-md-3 "><img ucard="{uid}" class="avatar-img talk-avatar"' +
        'src="{avatar128}"/>' +
        '</div><div class="col-md-9 bubble_outter">' +
        '<h3>{nickname}</h3>' +
        '<i class="bubble_sharp"></i>' +
        '<div class="talk_bubble">{content}' +
        '</div></div></div></div>';
    var tpl = message.uid == mid ? tpl_right : tpl_left;
    $.each(message, function (index, value) {
        tpl = tpl.replace('{' + index + '}', value);
    });
    return tpl;
}


/**
 * 绑定登出事件
 */
function bindLogout() {
    $('[event-node=logout]').click(function () {
        $.get(U('Ucenter/System/logout'), function (msg) {
            $('body').append(msg.html);
            toast.success(msg.message);
            setTimeout(function () {
                location.href = msg.url;
            }, 1500);
        }, 'json')
    });
}
/**
 * 绑定点赞事件
 */
function bind_support() {
    $('.support_btn').unbind('click');
    $('.support_btn').click(function () {
        // event.stopPropagation();
        var me = $(this);
        if (MID == 0) {
            toast.error('请在登陆后再点赞。', L('_KINDLY_REMINDER_'));
            return;
        } else {
            var row = $(this).attr('row');
            var table = $(this).attr('table');
            var uid = $(this).attr('uid');
            var jump = $(this).attr('jump');
            if (typeof(THIS_MODEL_NAME) != 'undefined') {
                MODULE_NAME = THIS_MODEL_NAME;
            }
            $.post(SUPPORT_URL, {appname: MODULE_NAME, row: row, table: table, uid: uid, jump: jump}, function (msg) {
                if (msg.status) {
                    var num_tag = $('#support_' + MODULE_NAME + '_' + table + '_' + row);
                    var pos = $('#support_' + MODULE_NAME + '_' + table + '_' + row + '_pos');
                    if (pos.text() == '') {
                        var html = '<span id="' + '#support_' + MODULE_NAME + '_' + table + '_' + row + '">1</span>';
                        pos.html('&nbsp;( ' + html + '&nbsp;)');

                    } else {
                        var num = num_tag.text();
                        num++;
                        num_tag.text(num);
                    }
                    var ico = me.find('#ico_like');
                    ico.removeClass();
                    ico.addClass('icon-heart');
                    toast.success(msg.info, L('_KINDLY_REMINDER_'));

                } else {
                    toast.error(msg.info, L('_KINDLY_REMINDER_'));
                }

            }, 'json');
        }

    });
}


/*微博表情*/
var insertFace = function (obj) {
    $('.XT_insert').css('z-index', '1000');
    $('.XT_face').remove();
    var html = '<div class="XT_face  XT_insert"><div class="triangle sanjiao"></div><div class="triangle_up sanjiao"></div>' +
        '<div class="XT_face_main"><div class="XT_face_title"><span class="XT_face_bt" style="float: left">常用表情</span>' +
        '<a onclick="close_face()" class="XT_face_close">X</a></div><div id="face" style="padding: 10px;">{:getPagination($totalPageCount,20)}</div></div></div>';
    obj.parents('.weibo_post_box').find('#emot_content').html(html);
    getFace(obj.parents('.weibo_post_box').find('#emot_content'), '');
};

var face_chose = function (obj) {
    var textarea = obj.parents('.weibo_post_box').find('textarea');
    textarea.focus();
    //textarea.val(textarea.val()+'['+obj.attr('title')+']');

    var pos = getCursortPosition(textarea[0]);
    var s = textarea.val();
    if (obj.attr('data-type') == 'miniblog') {
        textarea.val(s.substring(0, pos) + '[' + obj.attr('title') + ']' + s.substring(pos));
        setCaretPosition(textarea[0], pos + 2 + obj.attr('title').length);
    } else {
        textarea.val(s.substring(0, pos) + '[' + obj.attr('title') + ':' + obj.attr('data-type') + ']' + s.substring(pos));
        setCaretPosition(textarea[0], pos + 3 + obj.attr('title').length + obj.attr('data-type').length);
    }


}

var bind_face_pkg = function () {
    $('[data-role="change_pkg"]').unbind('click');
    $('[data-role="change_pkg"]').click(function () {
        var $this = $(this)
        var pkg = $this.attr('data-name');
        getFace($this.closest('#emot_content'), pkg);
    })
}

var getFace = function (obj, pkg) {
    if (typeof pkg == 'undefined') {
        pkg = '';
    }

    $.post(U('Core/Expression/getSmile'), {pkg: pkg}, function (res) {
        var expression = res.expression;
        var pkgList = res.pkgList;
        var _imgHtml = '';
        if (pkgList.length > 0) {
            if (pkgList.length > 1) {
                _imgHtml = "<div class='face-tab'><ul>";
                for (var e in pkgList) {
                    if (pkgList[e].name == res.pkg) {
                        _imgHtml += "<li class='active' ><a data-role='change_pkg'  data-name='" + pkgList[e].name + "'>" + pkgList[e].title + "</a></li>";
                    } else {
                        _imgHtml += "<li><a data-role='change_pkg' data-name='" + pkgList[e].name + "'>" + pkgList[e].title + "</a></li>";
                    }

                }
                _imgHtml += "</ul></div>";
            }
            for (var k in expression) {
                _imgHtml += '<a href="javascript:void(0)" data-type="' + expression[k].type + '" title="' + expression[k].title + '" onclick="face_chose($(this))";><img src="' + expression[k].src + '" width="24" height="24" /></a>   ';

            }
            _imgHtml += '<div class="c">{:getPagination('+expression+'.totalCount,20)}</div>';

        } else {
            _imgHtml = '获取表情失败';
        }

        obj.find('#face').html(_imgHtml);
        bind_face_pkg()
    }, 'json');
}

var close_face = function () {
    $('.XT_face').remove();
}


function getCursortPosition(ctrl) {//获取光标位置函数

    var CaretPos = 0;	// IE Support
    if (document.selection) {
        ctrl.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
        CaretPos = ctrl.selectionStart;
    return (CaretPos);
}

function setCaretPosition(ctrl, pos) {//设置光标位置函数
    if (ctrl.setSelectionRange) {
        ctrl.focus();
        ctrl.setSelectionRange(pos, pos);
    }
    else if (ctrl.createTextRange) {
        var range = ctrl.createTextRange();
        range.collapse(true);
        range.moveEnd('character', pos);
        range.moveStart('character', pos);
        range.select();
    }
}

/*微博表情end*/

/*登录*/

/**
 * 绑定登录按钮
 * [data-login="quick_login"] 强制弹出快捷登录窗
 * [data-login="do_login"] 根据条件选择登录方式(弹窗/跳转登录页面)
 * @returns {boolean}
 */
function bindLogin() {
    if (!is_login()) {
        $('[data-login="quick_login"]').click(quickLogin);
        $('[data-login="do_login"]').click(doLogin);
    }
    return true;
}

/**
 * 强制弹出快捷登录窗
 */
var quickLogin = function () {//快捷登录
    if (!is_login()) {
        var myModalTrigger = new ModalTrigger({
            remote: U('Ucenter/Member/quickLogin'),
            title: "登录"
        });
        myModalTrigger.show();
    }
}

/**
 * 根据条件选择登录方式(弹窗/跳转登录页面)
 */
var doLogin = function () {//登录界面
    if (!is_login()) {
        if (OPEN_QUICK_LOGIN == 1) {
            var myModalTrigger = new ModalTrigger({
                remote: U('Ucenter/Member/quickLogin'),
                title: "登录"
            });
            myModalTrigger.show();
        } else {
            window.location.href = U('Ucenter/Member/login');
        }
    }
}
function bindRegister() {
    if (!is_login()) {
        $('[data-role="do_register"]').click(doRegister);
    }
}
var doRegister = function () {
    if (!is_login()) {
        if (ONLY_OPEN_REGISTER == "1") {
            var myModalTrigger = new ModalTrigger({
                remote: U('Ucenter/Member/inCode'),
                title: "邀请用户才能注册！"
            });
            myModalTrigger.show();
        } else {
            var url = $(this).attr('data-url');
            location.href = url;
        }
    }
}
/*登录end*/


/**
 * 更新附件表单值
 * @return void
 */
var upAttachVal = function (type, attachId, obj) {
    var $attach_ids = obj;
    var attachVal = $attach_ids.val();
    var attachArr = attachVal.split(',');
    var newArr = [];
    for (var i in attachArr) {
        if (attachArr[i] !== '' && attachArr[i] !== attachId.toString()) {
            newArr.push(attachArr[i]);
        }
    }
    type === 'add' && newArr.push(attachId);
    $attach_ids.val(newArr.join(','));
    return newArr;
}
jQuery.cookie = function (name, value, options) {
    name = cookie_config.prefix + name;
    if (typeof value != 'undefined') {
        options = options || {};
        if (value === null) {
            value = '';
            options = $.extend({}, options);
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString();
        }
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else {
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};



function L(key, obj) {
    if('undefined' == typeof(LANG[key])) {
        return key;
    }
    if('object' != typeof(obj)) {
        return LANG[key];
    } else {
        var r = LANG[key];
        for(var i in obj) {
            r = r.replace("{"+i+"}", obj[i]);
        }
        return r;
    }
};