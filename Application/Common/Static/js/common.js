//dom加载完成后执行的js
;
$(function () {

    //全选的实现
    $(".check-all").click(function () {
        $(".ids").prop("checked", this.checked);
    });
    $(".ids").click(function () {
        var option = $(".ids");
        option.each(function (i) {
            if (!this.checked) {
                $(".check-all").prop("checked", false);
                return false;
            } else {
                $(".check-all").prop("checked", true);
            }
        });
    });

    //ajax get请求
    $('.ajax-get').click(function () {
        var target;
        var that = this;
        if ($(this).hasClass('confirm')) {
            if (!confirm('确认要执行该操作吗?')) {
                return false;
            }
        }
        if ((target = $(this).attr('href')) || (target = $(this).attr('url'))) {
            $.get(target).success(function (data) {
                if (data.status == 1) {
                    if (data.url) {
                        updateAlert(data.info + ' 页面即将自动跳转~', 'success');
                    } else {
                        updateAlert(data.info, 'success');
                    }
                    setTimeout(function () {
                        if (data.url) {
                            location.href = data.url;
                        } else if ($(that).hasClass('no-refresh')) {
                            $('#top-alert').find('button').click();
                        } else {
                            location.reload();
                        }
                    }, 3000);
                } else {
                    updateAlert(data.info);
                    setTimeout(function () {
                        if (data.url) {
                            location.href = data.url;
                        } else {
                            $('#top-alert').find('button').click();
                        }
                    }, 15000);
                }
            });

        }
        return false;
    });

    //ajax post submit请求
    $('.ajax-post').click(function () {
        var target, query, form;
        var target_form = $(this).attr('target-form');
        var that = this;
        var nead_confirm = false;
        if (($(this).attr('type') == 'submit') || (target = $(this).attr('href')) || (target = $(this).attr('url'))) {
            form = $('.' + target_form);

            if ($(this).attr('hide-data') === 'true') {//无数据时也可以使用的功能
                form = $('.hide-data');
                query = form.serialize();
            } else if (form.get(0) == undefined) {
                updateAlert('没有可操作数据。','danger');
                return false;
            } else if (form.get(0).nodeName == 'FORM') {
                if ($(this).hasClass('confirm')) {
                    var confirm_info = $(that).attr('confirm-info');
                    confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                    if (!confirm(confirm_info)) {
                        return false;
                    }
                }
                if ($(this).attr('url') !== undefined) {
                    target = $(this).attr('url');
                } else {
                    target = form.get(0).action;
                }
                query = form.serialize();
            } else if (form.get(0).nodeName == 'INPUT' || form.get(0).nodeName == 'SELECT' || form.get(0).nodeName == 'TEXTAREA') {
                form.each(function (k, v) {
                    if (v.type == 'checkbox' && v.checked == true) {
                        nead_confirm = true;
                    }
                })
                if (nead_confirm && $(this).hasClass('confirm')) {
                    var confirm_info = $(that).attr('confirm-info');
                    confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                    if (!confirm(confirm_info)) {
                        return false;
                    }
                }
                query = form.serialize();
            } else {
                if ($(this).hasClass('confirm')) {
                    var confirm_info = $(that).attr('confirm-info');
                    confirm_info=confirm_info?confirm_info:"确认要执行该操作吗?";
                    if (!confirm(confirm_info)) {
                        return false;
                    }
                }
                query = form.find('input,select,textarea').serialize();
            }
            if(query==''&&$(this).attr('hide-data') != 'true'){
                updateAlert('请勾选操作对象。','danger');
                return false;
            }
            $(that).addClass('disabled').attr('autocomplete', 'off').prop('disabled', true);
            $.post(target, query).success(function (data) {
                if (data.status == 1) {
                    if (data.url) {
                        updateAlert(data.info + ' 页面即将自动跳转~', 'success');
                    } else {
                        updateAlert(data.info, 'success');
                    }
                    setTimeout(function () {
                        if (data.url) {
                            location.href = data.url;
                        } else if ($(that).hasClass('no-refresh')) {
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled', false);
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    updateAlert(data.info,'error');
                    setTimeout(function () {
                        if (data.url) {
                            location.href = data.url;
                        } else {
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled', false);
                        }
                    }, 1500);
                }
            });
        }
        return false;
    });

    /**顶部警告栏*/
    var content = $('#main');
    var top_alert = $('#top-alert');
    top_alert.find('.close').on('click', function () {
        top_alert.removeClass('block').slideUp(200);
        // content.animate({paddingTop:'-=55'},200);
    });

    window.updateAlert = function (text, c) {


        if(typeof c !='undefined')
        {
            var msg = $.zui.messager.show(text, {placement: 'bottom',type:c});
        }else {
            var msg =  $.zui.messager.show(text, {placement: 'bottom'})
        }
        msg.show();
    };



    // 独立域表单获取焦点样式
    $(".text").focus(function () {
        $(this).addClass("focus");
    }).blur(function () {
        $(this).removeClass('focus');
    });
    $("textarea").focus(function () {
        $(this).closest(".textarea").addClass("focus");
    }).blur(function () {
        $(this).closest(".textarea").removeClass("focus");
    });
});



//标签页切换(无下一步)
function showTab() {
    $(".tab-nav li").click(function () {
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();
}

//标签页切换(有下一步)
function nextTab() {
    $(".tab-nav li").click(function () {
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
        showBtn();
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();

    $("#submit-next").click(function () {
        $(".tab-nav li.current").next().click();
        showBtn();
    });
}

// 下一步按钮切换
function showBtn() {
    var lastTabItem = $(".tab-nav li:last");
    if (lastTabItem.hasClass("current")) {
        $("#submit").removeClass("hidden");
        $("#submit-next").addClass("hidden");
    } else {
        $("#submit").addClass("hidden");
        $("#submit-next").removeClass("hidden");
    }
}

//导航高亮
function highlight_subnav(url) {
    $('#sub_menu').find('a[href="' + url + '"]').closest('li').addClass('active');
}

moduleManager = {
    'install': function (id) {
        $.post(U('admin/module/install'),{id:id},function(msg){
            handleAjax(msg);
        })
    },
    'uninstall': function (id) {
        $.post(U('admin/module/uninstall'),{id:id},function(msg){
            handleAjax(msg);
        })

    }

}
/**
 * 处理ajax返回结果
 */
function handleAjax(msg) {
    //如果需要跳转的话，消息的末尾附上即将跳转字样
    if (msg.url) {
        msg.info += '，页面即将跳转～';
    }

    //弹出提示消息
    if (msg.status) {
        updateAlert(msg.info, 'success');
    } else {
        updateAlert(msg.info, 'danger');
    }

    //需要跳转的话就跳转
    var interval = 1500;
    if (msg.url == "refresh") {
        setTimeout(function () {
            location.href = location.href;
        }, interval);
    } else if (msg.url) {
        setTimeout(function () {
            location.href = msg.url;
        }, interval);
    }
}

/**
 * 模拟U函数
 * @param url
 * @param params
 * @returns {string}
 * @constructor
 */
function U(url, params, rewrite) {


    if (window.Think.MODEL[0] == 2) {

        var website = window.Think.ROOT + '/';
        url = url.split('/');

        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '' + url[0] + '/' + url[1] + '/' + url[2];

        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }

    } else {
        var website = window.Think.ROOT + '/index.php';
        url = url.split('/');
        if (url[0] == '' || url[0] == '@')
            url[0] = APPNAME;
        if (!url[1])
            url[1] = 'Index';
        if (!url[2])
            url[2] = 'index';
        website = website + '?s=/' + url[0] + '/' + url[1] + '/' + url[2];
        if (params) {
            params = params.join('/');
            website = website + '/' + params;
        }
        if (!rewrite) {
            website = website + '.html';
        }
    }

    if(typeof (window.Think.MODEL[1])!='undefined'){
        website=website.toLowerCase();
    }
    return website;
}



admin_image ={
    /**
     *
     * @param obj
     * @param attachId
     */
    removeImage: function (obj, attachId) {
        // 移除附件ID数据
        this.upAttachVal('del', attachId, obj);
        obj.parents('.each').remove();

    },
    /**
     * 更新附件表单值
     * @return void
     */
    upAttachVal: function (type, attachId,obj) {
        var $attach_ids = obj.parents('.controls').find('.attach');
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
}