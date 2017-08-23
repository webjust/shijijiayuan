$(function() {
    $('#alert').dialog({
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
 * 全选与取消全选
 * 将全选的checkbox的class设为checkAll.列表中的checkbox的class设为checkSon
 * @author listen <lixin@guanyisoft.com>
 * @date 2013-01-11
 */
$(document).ready(function(){
    /*全选与取消*/
    $('.ckeckAll').click(function(){
        if($(this).attr('checked')=='checked'){
            $('.checkSon').attr('checked','checked');
        }else{
            $('.checkSon').removeAttr('checked');
        }
    });
});


/**
 * 时间日期控件
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-01-13
 */
$(document).ready(function(){
    $(".dater").datepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true
    });
    $(".timer").datetimepicker({
        showMonthAfterYear: true,
        changeMonth: true,
        changeYear: true,
        buttonImageOnly: true
    });
});
function isArray(o) {
    return Object.prototype.toString.call(o) === '[object Array]';
}
function isObject(o) {
    return Object.prototype.toString.call(o) === '[object Object]';
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
    if(!isArray(urls) && !isObject(urls)){
        urls = '';
    }
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
    //say(title + ' ' + message);
    return false;
}


/**
 * 公共提醒性弹出框
 * @author zuo <wanghui@guanyisoft.com>
 * @date 2013-07-28
 * @param result boolean 操作成功/失败。true显示笑脸，false显示哭脸。
 * @param title string 提示标题
 * @param message string 提示语句
 */
function showTips(){
    //入参 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    var result = arguments[0];
    var title = arguments[1] || '';
    var message = arguments[2] || '';
    $.webox({
        height: 90,
        width: 200,
        bgvisibel: true,
        title: '<b>'+title+'</b>',
        html: '<div class="left_tips" style="width:18%;float:left;background: url(http://mat1.gtimg.com/www/mb/img/v1/ui_i_pic_.png) no-repeat;background-position:-32px 2px;height:35px;margin-top:10px;margin-left:10px;"></div><div class="tip_content"  style="width:70%;float:left;margin-left:10px;margin-top:10px;">'+message+'</div>'
    });
}

/**
 * 无敌高级语音提示，支持汉语英语日语
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
    /**
     * 点击左侧导航事件
     */
    $('#menus h2').css('cursor','pointer');
    $('#menus h2').click(function(){
        //先收起全部菜单
        $('.subMenus').hide();
        //再将当前点击的子菜单展开
        $(this).next('.subMenus').show('fast');
    });

    /**
     * 各种按钮鼠标挪入颜色变换
     */
    $(function(){
        $(".blue").hover(function(){
            $(this).css({
                "color":"white",
                "background-color":"#0F89C4",
                "text-decoration":"none"
            })
        },function(){
            $(this).css({
                "color":"white",
                "background-color":"#FF3E79"
            })
        })

        $(".brown").hover(function(){
            $(this).css({
                "color":"white",
                "background-color":"#EF8D22",
                "text-decoration":"none"
            })
        },function(){
            $(this).css({
                "color":"white",
                "background-color":"#ff9b2e"
            })
        })
		$(".green").hover(function(){
            $(this).css({
                "color":"white",
                "background-color":"#72b804",
                "text-decoration":"none"
            })
        },function(){
            $(this).css({
                "color":"white",
                "background-color":"#00CC33"
            })
        })
    })

});

$(document).ready(function(){
    $('.thumb').mouseover(function(){
        var theImage = new Image();
        theImage.src = this.src;
        var imageWidth = theImage.width;
        var imageHeight = theImage.height;
        var $tip=$('<div id="tip"><div class="t_box"><div><s><i></i></s><img src="'+this.src+'" width="'+(imageWidth/2)+'" height="'+(imageHeight/2)+'" /></div></div></div>');
        $('body').append($tip);
        $('#tip').show('fast');
    }).mouseout(function(){
        $('#tip').remove();
    }).mousemove(function(e){
        $('#tip').css({
            "top":(e.pageY-60)+"px",
            "z-index":"999",
            "left":(e.pageX+30)+"px"
        })
    })
});

/**
 * 图片延迟加载
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2013-03-25
 * @link http://www.neoease.com/lazy-load-jquery-plugin-delay-load-image/
 * @link http://www.appelsiini.net/projects/lazyload
 * @example 使用的时候将图片的真正地址写在img的data-original属性中，而src中写占位付的地址，需要指定图像宽高。<img class="lazy" src="__PUBLIC__/Ucenter/images/grey.gif" data-original="__PUBLIC__/Goods/xxxxxxxxxxx.jpg"  width="350" heigh="350" />
 */
//$(document).ready(function(){
//    $("img.lazy").lazyload({effect : "fadeIn"});
//});