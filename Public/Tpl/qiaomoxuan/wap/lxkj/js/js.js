/*加入收藏*/
function addToInterests(gid){
    if(parseInt(gid) <= 0){
        alert("商品不存在或者已经被下架");return false;
    }
    $.ajax({
        url:"/Wap/Collect/doAddGoodsCollect",
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
function getDetailSkus(item_id, item_type){
	$.ajax({
		url:'/Wap/Products/ajaxGetSku',
		dataType:'HTML',
		type:'POST',
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
					$('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" class="goBuy " disabled gid="{$detail.gid}" >秒杀结束</a>');
					$('#showGroupTime'+listId).html('<span><abbr>此秒杀已结束</abbr></span>');
				} else if(buy_status == 1){
					$('#showGrouupbuy'+listId).html('<a id="addToOrder" class="goBuy addToOrder" gid="{$detail.gid}" href="javascript:void(0);">立即抢购</a>');
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
