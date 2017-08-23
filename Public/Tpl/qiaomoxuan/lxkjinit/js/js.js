$(document).ready(function(){	
	$(".allGoodsCon ul li").hover(function(){
		$(this).children("div").show();
		$(this).css("backgroundColor","#F8F8F8");
		$(this).css("borderRight","1px solid #F8F8F8");
	},function(){
		$(this).children("div").hide();
		$(this).css("backgroundColor","#EEEDEF");
		$(this).css("borderRight","1px solid #2D4F8E");
	});

	$(".shopcartCon").hover(function(){
		$(this).css("border","1px solid #c5c5c5");
		$(this).css("borderBottom","1px solid #c5c5c5");
		$(".shopcartHide").show();
	},function(){
		$(this).css("border","1px solid #F8F8F8");
		$(".shopcartHide").hide();
	});	
	//判断页面是首页还是其他页面，首页展示类目其他页面隐藏类目导航
//	var is_show_category = ($('#is_show_category').val() == undefined)?0:1;
//	if(is_show_category != '1'){
//		$('#category_show').addClass('allGoodshide');
//	}
	$('#category_show').addClass('allGoodshide');
	//类目显示与隐藏
	$(".allGoods").hover(function(){
		$(".allGoodshide").show();
	},function(){
		$(".allGoodshide").hide();
	});
    
    //清空浏览历史
    $("#clear_history").live("click",function(){
    	var liContent =  $("#all_history_box");
		if(liContent){
			liContent.html('<ul id="all_history_box">暂无浏览历史</ul>');
		}
		var dc=document.cookie.split(';');
		var date = new Date();
		date.setTime(date.getTime() - 10000);
	    for ( var i=0;i < dc.length;i++) {
	        var c = dc[i];
	        while (c.charAt(0)==' ') c = c.substring(1,c.length);
	        if ( c.indexOf('HistoryItems')==0 ) {
	        	var cn = c.substring(0,c.indexOf('='));
	            //document.cookie = cn + "=" + "; expires=" + date.toUTCString();
	            $.ajax({
	                url:'/Home/Products/deleteBrowsehistory',
	                cache:false,
	                dataType:'json',
	                data:{gid:cn},
	                type:"POST"
	            });
	        }
	    }
    });
});

function setTab(name,cursel,n){
	//清除焦点
	$('.hold').removeClass('hold');
	//隐藏浮层
	$('#hover').hide();
	 for(i=1;i<=n;i++){
		  var tab=(document.getElementById(name+i))?document.getElementById(name+i):'';
		  if(tab !=''){
			  var con=document.getElementById("con_"+name+"_"+i);
			  tab.className=i==cursel?"onHover":"";
			  con.style.display=i==cursel?"block":"none"; 
		  }
	 }
}
function yLogin() {
	$.colorbox({ inline: true, href: "#yLogi", width: "402px", height: "282px"});
}
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

function getCollGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getCollGoodsPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#coll_goods").html(msgObj);
            return false;
        }
    }); 
}

function getRelateGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getRelateGoodsPage',
        dataType:'HTML',
        type:'POST',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#relate_goods").html(msgObj);
            return false;
        }
    }); 
}

function getGoodsAdvice(gid,page){
    $.ajax({
        url:'/Home/Products/getGoodsAdvice',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            page:page
        },
        success:function(msgObj){
            
            $("#question_title").val('');
            $("#question_content").val('');
            $("#con_tabAbp_3").html(msgObj);
            return false;
        }
    }); 
}

function getGoodsAdvicePage(gid,page){
    $.ajax({
        url:page,
        dataType:'HTML',
        type:'POST',
        data:{
            gid:gid,
            p:page
        },
        success:function(msgObj){
            $("#con_tabAbp_3").html(msgObj);
            return false;
        }
    }); 
}

function getBuyRecordPage(gid,num){
    $.ajax({
        url:'/Home/Products/getBuyRecordPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            num:num
        },
        success:function(msgObj){
            $("#con_tabAbp_4").html(msgObj);
            return false;
        }
    }); 
}

function getCommentPage(gid){
    $.ajax({
        url:'/Home/Comment/getCommentPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#con_tabAbp_2").html(msgObj);
            return false;
        }
    }); 
}


$(document).ready(function(){
    //区域限购
    $("#restriction").click(function(){
        $.ajax({
            url:'/Home/Index/doCity',
            cache:false,
            dataType:'html',
            data:{},
            type:'POST',
            success:function(msgObj){
                $.webox({
                    height:376,
                    width:640,
                    bgvisibel:true,
                    title:'选择地区',
                    html: msgObj
                });
            }
        });
        
    });
});

$(document).ready(function(e) {
	$('#iToTop').click(function(){
		$(document).scrollTop(0);	
	})
});
/*加入收藏*/
function addToInterests(gid){
    if(parseInt(gid) <= 0){
        alert("商品不存在或者已经被下架");return false;
    }
    $.ajax({
        url:"/Home/Products/doAddGoodsCollect",
        cache:false,
        dataType:"json",
        data:{gid:gid},
        type:"post",
        success:function(msgObj){
            if(msgObj.status == '1'){
                $.ThinkBox.success("加入收藏成功");
            }else{
                $.ThinkBox.error(msgObj.info);
            }
        }
    });
    
    
}
/*加入对比*/
$(document).ready(function(){
    $("#clearToCompare").live("click",function(){
        var exp = $(this).attr("exp");
        if(exp == 'Expand'){
            $(this).attr("exp","Collapse");
            $(".contC").hide();
            $(".titD").hide();
            $(".contrast").css({"height":"auto"});
        }else{
            $(this).attr("exp","Expand");
            $(".contC").animate({display:''},1000,function(){
                $(".contrast").css({"height":"182px"});
            });
        }
    });
});
function addToCompare(gid) {
    if(isNaN(gid)){
        $.ThinkBox.error("商品不存在或者已经被下架");
        return false;
    }
    var compButton = $("#comp_bt_"+gid);
    if(compButton.hasClass('dbClick')) {
        delCompare(gid);
        toCompare();
    } else {
        $(compButton).addClass("dbClick");
        $.ajax({
            url:'/Home/Products/addToCompare',
            cache:false,
            dataType:'json',
            data:{gid:gid,check:'checked'},
            type:"POST",
            success:function(msgObj){
                if(msgObj.status == '0'){
                    $.ThinkBox.error(msgObj.info);
                    $(compButton).removeClass("dbClick");
                    return false;
                }else{
                    $.ThinkBox.success('加载中...');
                    toCompare();
                }
            }
        });
    }
}
function delCompare(gid) {
    if(gid >= 0){
        $.ajax({
            url:'/Home/Products/clearToCompareList',
            cache:false,
            dataType:'json',
            data:{gid:gid},
            type:"POST",
            success:function(msgObj){
                if(msgObj.status == '1'){
                    if(gid == 0) {
                        $(".compare_li").remove();
                        $(".dbClick").removeClass('dbClick');
                    } else {
                        $("#compare_li_"+gid).remove();
                    }
                    $(".db").each(function(){
                        if($(this).attr("gid") == gid){
                            $(this).removeClass("dbClick");
                        }
                    });
                    $.ThinkBox.success("删除成功");
                    return false;
                }else{
                    $.ThinkBox.error("删除失败");return false;
                }
            }
        });
    }else{
         $.ThinkBox.error("删除错误，请重试...");return false;
    }
} 
function toCompare(){
    $.ajax({
        url:'/Home/Products/getGoodsCompareList',
        cache:false,
        dataType:'HTML',
        data:{},
        type:"POST",
        success:function(msgObj){
            $("#contrast").css({"height":"182px"});
            $("#contrast").html(msgObj);
        }
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
 * @author Wangguibin <wangguibin@guanyisoft.com>
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

//加入收藏
function AddFavorite(sURL, sTitle) {
        sURL = encodeURI(sURL);
    try{  
        window.external.addFavorite(sURL, sTitle);  
    }catch(e) {  
        try{  
            window.sidebar.addPanel(sTitle, sURL, "");  
        }catch (e) {  
            alert("加入收藏失败，请使用Ctrl+D进行添加,或手动在浏览器里进行设置.");
        }  

    }
}

//设为首页

function SetHome(url){
    if (document.all) {
        document.body.style.behavior='url(#default#homepage)';
           document.body.setHomePage(url)
    }else{
        alert("您好,您的浏览器不支持自动设置页面为首页功能,请您手动在浏览器里设置该页面为首页!");
    }
}
