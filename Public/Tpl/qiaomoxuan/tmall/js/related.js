function SETRED(times,nowTime,showDay,showHouse,showFen,showMiao){
        var arr_time = times.split(" ");
        var fuckTime = arr_time[1].split(":");
        var time = new Date();
        
        
        var allYear = nowTime.split(" ");
        var aryYear = allYear[0].split("-");
        var aryHour = allYear[1].split(":");
        var year = aryYear[0];
        var month = aryYear[1];
        var date = aryYear[2];
        var Hourrs = aryHour[0];
        var Minutes = aryHour[1];
        var Seconds = aryHour[2];
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
        if(checkDay < 0){
            $("#stat_time_1").hide();
            $("#stat_time_3").show();
            return false;
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
                if(checkDay <= 0){
                    $("#stat_time_1").hide();
                    $("#stat_time_3").show();
                    clearInterval(interval);
                    return false;
                }
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
$(document).ready(function(){
    
    $("#stat_time_3").click(function(){
        var requsetUrl = $("#url").val();
        var _obj = $(this);
        $.ajax({
            url:url,
            type:'POST',
            data:{rd_id:rd_id},
            dataType:'json',
            success:function(html){
                if(html.info== '本场结束'){
                    $("#stat_time_3").hide();
                    $("#stat_time_2").show();
                    return false;
                }
                $("#showContent").show();
                $("#showContent").html(html.info);
                if(html.info == '请先登录'){
                    location.href = '/Home/User/login?requsetUrl='+requsetUrl;
                }
                $("#stat_time_3").hide();
                $("#stat_time_2").show().html('马上开抢');
                
                
            },
            error:function(){
            
            }
        });
    });

});
