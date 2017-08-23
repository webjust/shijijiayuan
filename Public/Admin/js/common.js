
/*
 * 框架布局设置
 */
function setFrameworkLayout() {
    window_width = $(window).width();
    window_height = $(window).height();
    //设置头部
    $('.header').css({
        'width':window_width-20,
        'height':'71px'
    });
    $('.footer').css({
        'width':window_width,
        'height':'45px'
    }).css({
        'clear':'both'
    });
    var content_height = window_height-91;
    $('.headerBox ul').css({
        'width':window_width-235,
        'height':'54px'
    }).css({
        'float':'left'
    });
    $('.contentBox').css({
        'width':window_width-20
    }).height(content_height).css({
        'float':'left'
    });
    $('.sildebarBox').width(186).height(content_height-14).css({
        'float':'left',
        'overflow':'hidden'
    });
    var right_width = window_width - 220;
    $('.breadcrumb').width(right_width-40).height(30).css({
        'float':'left'
    });
    $('.content').width(right_width-40).height(content_height-82).css({
        'float':'left',
        'overflow-x':'auto',
        'overflow-y':'auto'
    });
    $('.sliderNavBox').width(184).height(content_height-120).css({
        'float':'left',
        'overflow-y':'hidden',
        'overflow-x':'hidden',
        'margin-top':'10px'
    });/*添加打印纸*/
}

$(function() {
    $('body, .main').css({
        'padding':0,
        'margin':0,
        'float':'left',
        'width':'100%',
        'overflow':'hidden'
    });
    setFrameworkLayout();
    framework_layout_timeout_id = window.setInterval('setFrameworkLayout()', 500);
});

/**
 * 鼠标展开和收起二级菜单
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-05
 */
$(document).ready(function(){
    $("#sliderNavBoxInner h2").live("click",function(){
		//J，F分别代表二级菜单前面的+，-号。
		var img = $(this).find('.title');
        var obj = $(this).next('dl');
		//如果当前菜单已经处于展开状态，则不予处理
		if(obj.css("display")!="none"){
			//将二级菜单前面的-号替换成+号
			img.attr('src',img.attr('src').replace('F','J'));
			$(this).next("dl").hide();
			return false;
		}
		//将所有的二级菜单头的h2的img修改为关闭，也就是把小图标全部换成+号
		//并且将所有的子菜单全部关闭
		$("#sliderNavBoxInner h2 .title").each(function(){
			$(this).attr('src',$(this).attr('src').replace('F','J'));
			$(this).parent("h2").next("dl").hide();
		});
        if(obj.css('display')=='none'){
            img.attr('src',img.attr('src').replace('J','F'));
        }else{
            img.attr('src',img.attr('src').replace('F','J'));
        }
		obj.toggle('fast');
    });
});

/**
 * 表单验证插件设置
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-06
 */
$(document).ready(function(){
    $.metadata.setType("attr","validate");
});

/**
 * 时间日期控件
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-07
 */
$(document).ready(function(){
    $(".dater").datepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true,
        zIndex:999
    });
    $(".timer").datetimepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true,
        zIndex:999
    });
});

/**
 * 公共删除警告
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-08
 */
$(document).ready(function(){
    $('.confirm').click(function(){
        if(!confirm('你确定要这样做嘛，操作可能无法恢复')){
            return false;
        }
    });
});

/**
 * 全选与取消全选
 * 将全选的checkbox的class设为checkAll.列表中的checkbox的class设为checkSon
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-09
 */
$(document).ready(function(){
    /*全选与取消*/
        $('.checkAll').click(function(){
            if($(this).attr('checked')=='checked'){
                $('.checkSon').attr('checked','checked');
            }else{
                $('.checkSon').removeAttr('checked');
            }
        });
    
    if($('.tbList').length) {
        var checkAll = $('input.checkAll');
        
        $.each(checkAll,function(){
            var check_all = $(this), check_items;
            //分组各纵横项
            var check_all_direction = check_all.data('direction');
            check_items = $('input.checkSon[data-'+ check_all_direction +'id="'+ check_all.data('checklist') +'"]');
            //点击全选框
            check_all.change(function (e) {
                var check_wrap = check_all.parents('.tbList'); //当前操作区域所有复选框的父标签（重用考虑）

                if ($(this).attr('checked')) {
                    //全选状态
                    check_items.attr('checked', true);

                    //所有项都被选中
                    if( check_wrap.find('input.checkSon').length === check_wrap.find('input.checkSon:checked').length) {
                        check_wrap.find(checkAll).attr('checked', true);
                    }

                } else {
                    //非全选状态
                    check_items.removeAttr('checked');

                    //另一方向的全选框取消全选状态
                    var direction_invert = check_all_direction === 'x' ? 'y' : 'x';
                    check_wrap.find($('input.checkAll[data-direction="'+ direction_invert +'"]')).removeAttr('checked');
                }

            });
            
            //点击非全选时判断是否全部勾选
            check_items.change(function(){

                if($(this).attr('checked')) {

                    if(check_items.filter(':checked').length === check_items.length) {
                        //已选择和未选择的复选框数相等
                        check_all.attr('checked', true);
                    }

                }else{
                    check_all.removeAttr('checked');
                }

            });
        });
    }
    
});

/**
 * 鼠标悬浮在表单时，最后一列提示文字高亮显示
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-09
 */
$(document).ready(function(){
    $('.tbForm tr td').hover(function(){
        $(this).parent('tr').find('.last').addClass('green').addClass('bolder');
    },function(){
        $(this).parent('tr').find('.last').removeClass('green').removeClass('bolder');
    });
});

/**
 * 后退按钮点击时的操作。请将需要后退操作的元素class名设置为back
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-09
 */
$(document).ready(function(){
    $('.back').click(function(){
        history.back();
    });
});

$(function() {
    $('#alert').dialog({
        resizable:false,
        autoOpen: false,
        modal: true,
        buttons: {
            '确认': function() {
                $( this ).dialog( "close" );
            }
        }
    });
});

/**
 * 无敌高级语音提示
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-24
 * @param words string 要读出来的话
 * @param from string 语言种类，zh-汉语，en-英语，jp-日语
 * @param to string 语言种类，zh-汉语，en-英语，jp-日语
 * @param func function 读完后的回调方法
 * @return
 */
function say(){
    //入参 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    var words = arguments[0] || '';
    var from = arguments[1] || 'zh';
    var to = arguments[2] || 'zh';
    var func = arguments[3] || function(){};

    var url = '/Ucenter/User/say/';
    $.get(url,{
        words:words,
        from:from,
        to:to
    },function(data){
        //$("#reader").attr('onended',func);
        $("#reader").attr('src',data);
        document.getElementById('reader').play();
    },'json');
}

/**
 * 公共提醒性弹出框
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-11
 * @param result boolean 操作成功/失败。true显示笑脸，false显示哭脸。
 * @param title string 提示标题
 * @param message string 提示语句
 * @param urls mix 点击确认后跳转的地址，如果不填则代表确认就是关闭本窗口
 */
function showAlert(){
    //入参 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    var result = arguments[0];
    var title = arguments[1] || '';
    var message = arguments[2] || '';
    var urls = arguments[3];
    var time = arguments[4] || 0;
	var open = arguments[5] || 0;
    //显示内容 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    if(result==true || result==1){
        //显示笑脸
        //$("#alert_face").html(':)');
        $("#alert_face").removeClass('faceFalse');
        $("#alert_face").addClass('faceTrue');
    }else{
        //显示哭脸
        //$("#alert_face").html(':(');
        $("#alert_face").removeClass('faceTrue');
        $("#alert_face").addClass('faceFalse');
    }
    $('#alert_title').html(title);
    $('#alert_msg').html(message);
    //是否跳转到其他页面 +++++++++++++++++++++++++++++++++++++++++++++++++++++++
    if(urls){
        var button = {};
        for(var u in urls){
            button[u] = function(e){
                var text = ( $(e.target).find('span').html() == undefined ) ? e.target.innerHTML : $(e.target).find('span').html();
                //console.log($(e.target).find('span').html());
                if(''==text){
                    $( this ).dialog( "close" );
                }else{
                    location.href = urls[text];
                }
            }
        }
        $('#alert').dialog('option','buttons',button);
    }else{
        $('#alert').dialog('option','buttons',{
            '确认': function() {
				if(open == 1){
					location.reload();
				}			
                $( this ).dialog( "close" );
            }
        });
    }
    //开启弹窗 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    $('#alert').dialog("open");
    //语音提示
    //say(title + ' ' + message);
    return false;
}

/**
 * 公共简单ajax请求，返回统一弹框
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-25
 * @param ajaxUrl string 请求地址
 * @param ajaxData mix 请求数据
 * @param method sting 请求方式，默认为get
 * @param type sting 请求方式，默认为json
 */
function ajaxReturn(){
    //入参 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    var ajaxUrl = arguments[0] || '';
    var ajaxData = arguments[1] || {};
    var method = arguments[2] || 'get';
    var type = arguments[3] || 'json';

    $.ajax({
        url:ajaxUrl,
        data:ajaxData,
        success:function(result){
            showAlert(result.status,result.info,'',result.url);
        },
        error:function(){
            alert('请求无响应或超时');
        },
        type:method,
        dataType:type
    });
}

$(document).ready(function(){
    $(".module-item").change(function(){
        var parent = $(this).parent().parent().parent().parent();
        if(this.checked)
        {
            $('.select-all,.action-item',parent).attr({
                //'disabled':true,
                'checked':true
            });
        }
        else
        {
            $('.select-all,.action-item',parent).attr({
                'disabled':false,
                'checked':false
            });
        }
    });
    $(".select-all").change(function(){
        var parent = $(this).parent().parent().parent();
        if(this.checked)
        {
            $('.action-item',parent).attr({
                'checked':true
            });
        }
        else
        {
            $('.action-item',parent).attr({
                'checked':false
            });
        }
    });

    $(".action-item").change(function(){
        var parent = $(this).parent().parent().parent().parent().parent().parent();
        if($(".action-item:checked",parent).length == $(".action-item",parent).length)
        {
            $('.module-item',parent).attr({
                'checked':true
            });
        }
        else
        {
            $('.module-item',parent).attr({
                'checked':false
            });
        }
    });

    //切换
    $('img[data-tdtype="toggle"]').live('click', function() {
        var url = $(".rightInner").attr("data-uri");
        var img    = this,
        s_val  = ($(img).attr('data-value'))== 0 ? 1 : 0,
        s_name = $(img).attr('data-field'),
        s_id   = $(img).attr('data-id'),
        s_src  = $(img).attr('src');
        s_msg = ($(img).attr('data-value'))== 0 ? '启用' : '停用';
        $.ajax({
            url:url,
            cache:false,
            dataType:"json",
            data: {
                id:s_id, 
                field:s_name, 
                val:s_val
            },
            type:"POST",
            beforeSend:function(){
                $("#J_ajax_loading").stop().removeClass('ajax_error').addClass('ajax_loading').html("提交请求中，请稍候...").show();
            },
            error:function(){
                $("#J_ajax_loading").addClass('ajax_error').html("AJAX请求发生错误！").show().fadeOut(5000);
            },
            success:function(msgObj){
                $("#J_ajax_loading").hide();
                if(msgObj.status == '1'){
                    if(s_src.indexOf('0')>-1) {
                        $(img).attr({
                            'src':s_src.replace('0','1'),
                            'data-value':s_val,
                            'title':s_msg
                        });
                    } else {
                        $(img).attr({
                            'src':s_src.replace('1','0'),
                            'data-value':s_val,
                            'title':s_msg
                        });
                    }
                }else{
                    $("#J_ajax_loading").addClass('ajax_error').html(msgObj.info).show().fadeOut(5000);
                }
            }
        });
    });

    //确认操作
    $(".confirmurl").live('click',function(){
        var self = $(this),
        url = self.attr('data-uri'),
        acttype = self.attr('data-acttype'),
        msg = self.attr('data-msg');
        $("#pro_dialog #tip_div").html(msg);
        $("#tip_div").dialog({
            width:200,
            modal:true,
            title:'提示消息',
            resizable: false,
            buttons:{
                '删除':function(){
                    $("#tip_div").dialog('destroy');
                    $('#pro_dialog').append($('#tip_div'));
                    if(acttype == 'ajax'){
                        $.ajax({
                            url:url,
                            cache:false,
                            dataType:"json",
                            data: {},
                            type:"POST",
                            beforeSend:function(){
                                $("#J_ajax_loading").stop().removeClass('ajax_error').addClass('ajax_loading').html("提交请求中，请稍候...").show();
                            },
                            error:function(){
                                $("#J_ajax_loading").addClass('ajax_error').html("AJAX请求发生错误！").show().fadeOut(5000);
                            },
                            success:function(msgObj){
                                $("#J_ajax_loading").hide();
                                if(msgObj.status == '1'){
                                    $("#J_ajax_loading").removeClass('ajax_error').addClass("ajax_success").html(msgObj.info).show().fadeOut(5000);
                                    window.location.reload();
                                }else{
                                    $("#J_ajax_loading").addClass('ajax_error').html(msgObj.info).show().fadeOut(5000);
                                }
                            }
                        });
                    }else{
                        location.href = url;
                    }
                },
                "取消": function() {
                    $("#tip_div").dialog('destroy');
                    $('#pro_dialog').append($('#tip_div'));
                }
            }
        });
    });
});

function onUrl(url){
    location.href = url;
}

function openTopUrl(url){
    $.cookie('nav0', "-1");
    $.cookie('nav1', "-1");
    $.cookie('nav2', "-1");
//    alert($.cookie('nav2'));return false;
    window.location.href=url;
}

function openUrl(menu,url){
    var str = menu.split(',');
    var nav0 = str[0];
    try{
        var nav1 = str[1];
        var nav2 = str[2];
    }catch(ex){}
    for(var i=0;i<3;i++){
        
        $.cookie('nav'+i,null);
        $.cookie('nav'+i, eval("nav"+i));
    }
//    alert($.cookie('nav2'));return false;
    window.location.href=url;
    return false;
}

$(document).ready(function(){
    $("#GyMap").click(function(){
        var title = $(this).attr('title');
        var data_uri = $(this).attr('data-uri');
        $.ajax({
            url:data_uri,
            cache:false,
            dataType:'html',
            type:'POST',
            data:{},
            beforeSend:function(){
                $('<div id="resultMessage" />').addClass("msgError").html('正在加载中,请稍后...').appendTo('.mainBox').fadeOut(1000);
            },
            success:function(msgObj){
                $("#tip_dialog").html(msgObj);
                $("#tip_dialog").dialog({
                    height:'550',
                    width:'940',
                    resizable:false,
                    modal:true,
                    title:title,
                    close:function(){
                        $("#member_dialog").dialog('destroy');
                        $('#tip_dialog').append($('#member_dialog'));
                    }
                });
                return false;
//                if(msgObj.status == '1'){
//                   
//                }else{
//                    $('<div id="resultMessage" />').addClass("msgError").html(msgObj.info).appendTo('.mainBox').fadeOut(1000);
//                }
            },
            error:function(){
                $('<div id="resultMessage" />').addClass("msgError").html('AJAX请求发生错误！').appendTo('.mainBox').fadeOut(1000);
            }
            
        });
        
    });
    
    
});

/**
 * 鼠标经过显示左侧菜单
 * @author Terry<admin@huicms.cn>
 * @date 2013-09-09
 */
 $(document).ready(function(){
//    $(".headerBox ul li").hover(function(){
//		$(".headerBox ul li").each(function(){
//			$(this).removeClass("on");
//		});
//    	$(this).addClass("on");
//        var nav_id = $(this).attr("nav");
//        if(isNaN(nav_id)){
//            showAlert("提示","参数有误,请重试");
//            layer.msg('参数有误,请重试', 2, 3);
//        }else{
//            $.ajax({
//                url:'/Admin/Index/getLeftMenu',
//                cache:false,
//                dataType:"html",
//                type:"POST",
//                data:{"nav_id":nav_id},
//                success:function(msgObj){
//                    $("#sliderNavBoxInner").html(msgObj);
//                }
//            });
//        }
//    },function(){});

 });