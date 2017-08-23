
function countDown(time,id,status,nowtime,endtime,diration){
	var day_elem = $("#box"+id).find('#day');
	var hour_elem = $("#box"+id).find('#hour');
	var minute_elem = $("#box"+id).find('#minute');
	var second_elem = $("#box"+id).find('#second');
    
    var reg = new RegExp("-","g");
    var timeStr = time.replace(reg,"/");
    var nowtimeStr = nowtime.replace(reg,"/");
    var timeStr = new Date(timeStr);
    var nowtimeStr = new Date(nowtimeStr);
    
	var end_time = timeStr.getTime(),//月份是实际月份-1
	sys_second = (end_time-nowtimeStr.getTime())/1000;
	var timer = setInterval(function(){
		if (sys_second > 0) {
			sys_second -= 1;
			var day = Math.floor((sys_second / 3600) / 24);
			var hour = Math.floor((sys_second / 3600));
			var minute = Math.floor((sys_second / 60) % 60);
			var second = Math.floor(sys_second % 60);
			day_elem && $(day_elem).text(day);//计算天
			$(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
			$(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
			$(second_elem).text(second<10?"0"+second:second);//计算秒杀
		} else { 
            if(status == 1){
                $("#tuangou"+id).html("<a class='end'>已结束</a>");
                $("#tuangouD"+id).attr("class",'js'+diration).text("已结束");
            } else if(status == 2){
                $("#tuangou"+id).html('<a href="/Home/Bulk/detail?gp_id='+id+'">马上团</a>');
                $("#tuangouD"+id).attr("class",'jx'+diration).text("进行时...");
                $("#colockbox"+id).find('.changemsg').text('距结束');
                countDown(endtime,id,1,time);
            }
            clearInterval(timer);  
		}
	}, 1000);
}//倒计时

function setTuanGouTime(time,id,status,nowtime,endtime,diration){
	var day_elem = $("#boxlist"+id).find('#day');
	var hour_elem = $("#boxlist"+id).find('#hour');
	var minute_elem = $("#boxlist"+id).find('#minute');
	var second_elem = $("#boxlist"+id).find('#second');
    
    var reg = new RegExp("-","g");
    var timeStr = time.replace(reg,"/");
    var nowtimeStr = nowtime.replace(reg,"/");
    var timeStr = new Date(timeStr);
    var nowtimeStr = new Date(nowtimeStr);
    
	var end_time = timeStr.getTime(),//月份是实际月份-1
	sys_second = (end_time-nowtimeStr.getTime())/1000;
	var timer = setInterval(function(){
		if (sys_second > 0) {
			sys_second -= 1;
			var day = Math.floor((sys_second / 3600) / 24);
			var hour = Math.floor((sys_second / 3600));
			var minute = Math.floor((sys_second / 60) % 60);
			var second = Math.floor(sys_second % 60);
			day_elem && $(day_elem).text(day);//计算天
			$(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
			$(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
			$(second_elem).text(second<10?"0"+second:second);//计算秒杀
		} else { 
            if(status == 1){
                $("#tuangou"+id).html("<a class='end'>已结束</a>");
                $("#tuangouD"+id).attr("class",'js'+diration).text("已结束");
            } else if(status == 2){
                $("#tuangou"+id).html('<a href="/Home/Bulk/detail?gp_id='+id+'">马上团</a>');
                $("#tuangouD"+id).attr("class",'jx'+diration).text("进行时...");
                $("#colockbox"+id).find('.changemsg').text('距结束');
                countDown(endtime,id,1,time);
            }
            clearInterval(timer);  
		}
	}, 1000);
}//倒计时
