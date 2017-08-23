$(document).ready(function(){
    var is_shop_open = $("#gy_shop_open").val();
    if ( is_shop_open ==1 ) {
        ajaxLoadShoppingMember();
    }
//    if ( is_shop_open == 1 || is_shop_open == undefined) {
//        ajaxLoadShoppingCart(1);
//    }
});

function ajaxLoadShoppingMember(){
    $.post('/Home/User/showMemberInfo',{},function(htmlObj){
        $("#shopping_member_list").html(htmlObj);
    },'html');
}

///*********异步加载购物车****/
//function ajaxLoadShoppingCart(int_page_num){
//    if(!int_page_num){
//        int_page_num = 1;
//    }
//
//    $.post('/Home/Cart/Pagelist',{'int_page_num':int_page_num},function(htmlObj){
//        $("#shopping_cart_list").html(htmlObj);
//    },'html');
//}
///*********异步删除购物车数据********/
//function deleteFromMyCart(pid,type){
//    if(confirm('确定要将此商品从购物车删除吗？\n\n删除后，您还可以重新加入购物车。')){
//        $.get('/Home/Cart/doDel/',{'pid':pid,'type':type},function(da){
//            ajaxLoadShoppingCart(1);
//        },'json');
//    }
//
//}

//标签切换
function tagChange(opts) {
    var opts = opts ? opts :{};
    var dftIdx = opts.defaultIdx ? opts.defaultIdx : '0' ;
    var curCls = opts.currentClass ? opts.currentClass : 'current' ;
    var evt = opts.et ? opts.et : 'click' ;
    var tagObj = opts.tagObj;
    var tagCon = opts.tagCon;
    tagObj.eq(dftIdx).addClass(curCls).siblings().removeClass(curCls);
    tagCon.eq(dftIdx).show().siblings().hide();
    if(evt == 'hover') {
        tagObj[evt](function(){
            var idx = $(this).index();
            $(this).addClass(curCls);
            tagCon.eq(idx).show();
        },function(){
            var idx = $(this).index();
            $(this).removeClass(curCls);
            tagCon.eq(idx).hide();
        })
    }else if(evt == 'click') {
        tagObj[evt](function(){
            var idx = $(this).index();
            $(this).addClass(curCls).siblings().removeClass(curCls);
            tagCon.eq(idx).show().siblings().hide();
        })
    }

}

/* 开发新增的js 后面递增增加*/

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
    var result = arguments[0];
    var title = arguments[1] || '';
    var message = arguments[2] || '';
    var urls = arguments[3];
    var time = arguments[4] || 0;
    var open = arguments[5] || 0;
    //显示内容
    if(result==true || result==1){
        //显示笑脸
        //$("#alert_face").html(':)');
        $("#alert_face").removeClass('faceFalse');
        $("#alert_face").addClass('faceTrue');
    }else{
        //显示哭脸
        $("#alert_face").removeClass('faceTrue');
        $("#alert_face").addClass('faceFalse');
    }
    $('#alert_title').html(title);
    $('#alert_msg').html(message);
    if(!isArray(urls) && !isObject(urls)){
        urls = '';
    }
    //是否跳转到其他页面
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
    //开启弹窗
    $('#alert').dialog("open");
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

/**
 * 根据指定时间显示动态倒计时效果
 *
 * @param times 指定时间年月日 格式 Y-m-d H:i:s
 * @param 显示时间的id 顺序为 天->小时->分->秒
 * @author Joe <qianyijun@guanyisoft.com>
 */
function setTuanGouTime(times,showDay,showHouse,showFen,showMiao,now_time){
    var arr_time = times.split(" ");
    var fuckTime = arr_time[1].split(":");
    if(now_time != ''){
        var time = new Date(Date.parse(now_time.replace(/-/g,"/")));
    }else{
        var time = new Date();
    }
    var year = time.getFullYear();
    var month = time.getMonth()+1;
    var date = time.getDate();
    var Hourrs = time.getHours();
    var Minutes = time.getMinutes();
    var Seconds = time.getSeconds();
    var showHourrs = fuckTime[0]-Hourrs;
    var showMinutes = fuckTime[1]-Minutes;
    var showSeconds = fuckTime[2]-Seconds;
    var checkDay = daysBetween(arr_time[0],year+"-"+month+"-"+date);

    if(showSeconds < 0){
        showSeconds = 60-Math.abs(showSeconds);
        showMinutes = showMinutes-1;
    }
    if(showMinutes < 0){
        showMinutes = 60-Math.abs(showMinutes);
        showHourrs = showHourrs-1;
    }
    if(showHourrs <0){
        showHourrs = 24-Math.abs(showHourrs);
        checkDay = checkDay-1;
    }

    if(checkDay < 10){
        checkDay = "0"+checkDay;
    }
    var interval = setInterval(function(){

        if(showSeconds < 0){
            showSeconds = 60-Math.abs(showSeconds);
            showMinutes = showMinutes-1;
        }
        if(showMinutes < 0){
            showMinutes = 60-Math.abs(showMinutes);
            showHourrs = showHourrs-1;
        }
        if(showHourrs <0){
            showHourrs = 24-Math.abs(showHourrs);
            checkDay = checkDay-1;
        }
        var arr = (2+'').split('');
        if(arr.length != 1){
            checkDay = "0"+checkDay;
        }
        $("#"+showDay).html(checkDay);
        $("#"+showHouse).html(showHourrs);
        $("#"+showFen).html(showMinutes);
        $("#"+showMiao).html(showSeconds);
        showSeconds = --showSeconds;
    }, 1000);
}
/**
 * 根据指定时间显示动态倒计时效果
 *
 * @param times 指定时间年月日 格式 Y-m-d H:i:s
 * @param 显示时间的id 顺序为 天->小时->分->秒
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 */
 function setGroupbuyTime(times,showDay,showHouse,showFen,showMiao,now_time,buy_type,buy_status,gpEndTime,listId,gid){
    var day_elem = $("#showGroupTime"+listId).find('.'+showDay);
	var hour_elem = $("#showGroupTime"+listId).find('.'+showHouse);
	var minute_elem = $("#showGroupTime"+listId).find('.'+showFen);
	var second_elem = $("#showGroupTime"+listId).find('.'+showMiao);
	 
	var reg = new RegExp("-","g");
    var timeStr = times.replace(reg,"/");
    var nowtimeStr = now_time.replace(reg,"/");
    var timeStr = new Date(timeStr);
    var nowtimeStr = new Date(nowtimeStr);
	
	var end_time = timeStr.getTime(),//月份是实际月份-1
	sys_second = (end_time-nowtimeStr.getTime())/1000;
	
	var timer = setInterval(function(){
		if (sys_second > 0) {
			sys_second -= 1;
			var day = Math.floor((sys_second / 3600) / 24);
			var hour = Math.floor((sys_second / 3600)-(day * 24));
			var minute = Math.floor((sys_second / 60) % 60);
			var second = Math.floor(sys_second % 60);
			//day_elem && $(day_elem).text(day);//计算天
			$(day_elem).html(day);
			$(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
			$(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
			$(second_elem).text(second<10?"0"+second:second);//计算秒杀
		} else { 
			if(buy_type == 'miaos'){
				if(buy_status == 2){
					$('#showGrouupbuy'+listId).html('');
					$('#showSpike'+listId).html('<input type="button" id="addNotOrder" disabled class="notSpike" value="立即购买" />');
					$('#showGroupTime'+listId).html('<span><abbr>此秒杀已结束</abbr></span>');
				} else if(buy_status == 1){
					$('#showGrouupbuy'+listId).html('此商品正在参加秒杀，');
					$('#showSpike'+listId).html('<input type="button" id="addToOrder" class="maySpike" gid="{$detail.gid}"  value="立即购买" />');
					$('#showText'+listId).html('剩余时间：');
					setGroupbuyTime(gpEndTime,'day','hours','minutes','seconds',times,'miaos',2,'',listId,gid);
				}
			}
			else if(buy_type == 'tuan'){
				if(buy_status == 2){
					$('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" class="pastbuy goTobuy">该团购已结束</a>');
					$('#showGroupTime'+listId).html('<span><abbr>此团购已结束</abbr></span>');
				} else if(buy_status == 1){
					$('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" onclick="addToOrder(2);" class="goTobuy" >立即抢购</a>');
					setGroupbuyTime(gpEndTime,'day','hours','minutes','seconds',times,'tuan',2,'',listId,gid);
				}
			}
            clearInterval(timer);  
		}
	}, 1000);
}
function daysBetween(DateOne,DateTwo){
    var OneMonth = DateOne.substring(5,DateOne.lastIndexOf ('-'));
    var OneDay = DateOne.substring(DateOne.length,DateOne.lastIndexOf ('-')+1);
    var OneYear = DateOne.substring(0,DateOne.indexOf ('-'));

    var TwoMonth = DateTwo.substring(5,DateTwo.lastIndexOf ('-'));
    var TwoDay = DateTwo.substring(DateTwo.length,DateTwo.lastIndexOf ('-')+1);
    var TwoYear = DateTwo.substring(0,DateTwo.indexOf ('-'));

    var cha=((Date.parse(OneMonth+'/'+OneDay+'/'+OneYear)- Date.parse(TwoMonth+'/'+TwoDay+'/'+TwoYear))/86400000);
    return Math.abs(cha);
}

function setTab(name,cursel,n){
    for(i=1;i<=n;i++){
        var tab=document.getElementById(name+i);
        var con=document.getElementById("con_"+name+"_"+i);
        tab.className=i==cursel?"onHover":"";
        con.style.display=i==cursel?"block":"none";
    }
}

//获取商品规格
function getDetailSkus(item_id, item_type){
	$.ajax({
		url:'/Home/Products/getDetailSkus',
		dataType:'HTML',
		type:'GET',
		data:{
			item_id:item_id,
			item_type:item_type
		},
		success:function(msgObj){
			$("#showDetailSkus").html(msgObj);
			return false;
		}
	}); 
}