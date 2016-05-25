/**
 * Created by Administrator on 15-3-18.
 * uctoo
 */


$(function () {
    re_bind();

});

var re_bind = function () {

    change_select();
    fix_form();
    add_one();
    add_two();
    remove_li();
    add_child();
    add_flag();
    bind_chose_icon();
}

var bind_chose_icon = function(){

    $('.chosen-container').remove();
    $('form select.chosen-icons').attr('class','chosen-icons');
    $('form select.chosen-icons').data('zui.chosenIcons',null);
    $('form select.chosen-icons').data('chosen',null);
    $('form select.chosen-icons').chosenIcons();

}
var change_select = function () {
    $('.nav-type').unbind('change');
    $('.nav-type').change(function () {
        var obj = $(this);
        switch (obj.val()) {
            case 'click':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'view':
                obj.closest('li>div').children('input.url').show();
                obj.closest('li>div').children('input.keyword').hide();
                break;
            case 'scancode_push':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'scancode_waitmsg':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'pic_sysphoto':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'pic_photo_or_album':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'pic_weixin':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'location_select':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').show();
                break;
            case 'none':
                obj.closest('li>div').children('input.url').hide();
                obj.closest('li>div').children('input.keyword').hide();
                break;
        }
    });
}

var fix_form = function () {
    $('.channel-ul').sortable({trigger: '.sort-handle-1', selector: 'li', dragCssClass: '',finish:function(){
        re_bind();
    }
    });
    $('.channel-ul .ul-2').sortable({trigger: '.sort-handle-2', selector: 'li', dragCssClass: '',finish:function(){
        re_bind();
    }});
}

var add_one = function () {
    $('.add-one').unbind('click');
    $('.add-one').click(function () {
        $(this).closest('.pLi').after($('#one-nav').html());
        re_bind();
    });
}

var add_two = function () {
    $('.add-two').unbind('click');
    $('.add-two').click(function () {
        $(this).closest('li').after($('#two-nav').html());
        re_bind();
    });
}

var remove_li = function () {
    $('.remove-li').unbind('click');
    $('.remove-li').click(function () {
        if( $(this).parents('form').find('.pLi').length > 1){
            $(this).closest('li').remove();
            re_bind();
        }else{
            updateAlert('不能再减了~');
        }

    });
}

var add_child = function () {
    $('.add-child').unbind('click');
    $('.add-child').click(function () {
        if ($(this).closest('li').find('.ul-2').length == 0) {
            $(this).closest('li').append('<div class="clearfix"></div><ul class="ul-2"  style="display: block;"></ul>')
        }
        $(this).closest('li').find('.ul-2').prepend($('#two-nav').html());
        re_bind();
    })
}

var add_flag = function () {
    $('.channel-ul .pLi').each(function (index, element) {
        $(this).attr('data-id', index);
        $(this).find('.sort').val($(this).attr('data-order'));
    });
    $('.ul-2 li').each(function (index, element) {
        $(this).find('.pid').val($(this).parents('.pLi').attr('data-id'));
        $(this).find('.sort').val($(this).attr('data-order'));
    });
}