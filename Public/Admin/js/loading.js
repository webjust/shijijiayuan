/**
 * 页面如果是通过ajax请求载入的，需要加上此js，已完成对页面元素的相关操作重新绑定
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */

/**
 * 时间日期控件
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    $(".load .dater").datepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true
    });
    $(".load .timer").datetimepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true
    });
});

/**
 * 公共删除警告
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    $('.load .confirm').click(function(){
        if(!confirm('你确定要这样做嘛，操作可能无法恢复')){
            return false;
        }
    });
});

/**
 * 全选与取消全选
 * 将全选的checkbox的class设为checkAll.列表中的checkbox的class设为checkSon
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    /*全选与取消*/
    $('.load .ckeckAll').click(function(){
        if($(this).attr('checked')=='checked'){
            $('.checkSon').attr('checked','checked');
        }else{
            $('.checkSon').removeAttr('checked');
        }
    });
});

/**
 * 鼠标悬浮在表单时，最后一列提示文字高亮显示
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    $('.load .tbForm tr td').hover(function(){
        $(this).parent('tr').find('.last').addClass('green').addClass('bolder');
    },function(){
        $(this).parent('tr').find('.last').removeClass('green').removeClass('bolder');
    });

    $('.load td').die().hover(function(){
        $(this).parent('tr').find('.last').addClass('green').addClass('bolder');
    },function(){
        $(this).parent('tr').find('.last').removeClass('green').removeClass('bolder');
    });
});

/**
 * 后退按钮点击时的操作。请将需要后退操作的元素class名设置为back
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    $('.load .back').click(function(){
        history.back();
    });
});

/**
 * 载入页面中的分页被劫持，由直接超链接改为刷新弹框内的信息
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-11
 */
$(function() {
    $('.load .page a').die().click(function(){
        var url = $(this).attr('href');
        if(url!='' || url!='#' || url != '###' || url != 'javascript:void(0);'){
            $('#isAjax').parent().load(url);
            return false;
        }
    });
});