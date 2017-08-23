

$(document).ready(function(){
    $("#chec").click(function(){
        if($(this).is(":checked")){
            $("#home_register_submit").removeClass();
            $("#home_register_submit").addClass("buttonT");
        }else{
            $("#home_register_submit").removeClass();
            $("#home_register_submit").addClass("buttonNoT");
        }
    });
    
    
    
    //内嵌弹出层调用
    $('#inside').click(function(){
            $.webox({
                    height:480,
                    width:600,
                    bgvisibel:true,
                    title:'注册协议',
                    html:$("#box").html()
            });
    });
  
    
    //商品类目展示
    $(".proClaCon ul li").hover(function(){
            $(this).children(".subView").show();
    },function(){
            $(this).children(".subView").hide();
    })

    //文本框边框默认、得到焦点、失去焦点时的颜色变化
    $("table.loRe input[type=text]").css("border","1px solid #d7d7d7");
    $("table.loRe input[type=password]").css("border","1px solid #d7d7d7");
    $("table.loRe input[type=text]").focus(function(){
            $(this).css("border","1px solid #319036")
    })
    $(".loRe input[type=text]").blur(function(){
            $(this).css("border","1px solid #d7d7d7")
    })

    //"所有商品分类"，鼠标经过时显示二级菜单
    $(".proClassify").hover(function(){
            $(".proClaCon").show();
    },function(){
            $(".proClaCon").hide();
    })

    $(".navUL li").click(function(){
            $(this).addClass("on").children("dl").show().end().siblings().removeClass("on").children("dl").hide();
    })  
    /* 导航  */
	$('.wd_nav_map li').mousemove(function(){
		$(this).addClass('hover-bg');
	});
	$('.wd_nav_map li').mouseleave(function(){
		$(this).removeClass('hover-bg');
	});
	/*分类显示隐藏   */   	
	$('#category_container').mousemove(function(){
       $('.wd-bd').show();
       $('.wd-hd').addClass('bg');   
    });
    $('#category_container').mouseleave(function(){
      $(".wd-bd").hide();
      $('.wd-hd').removeClass('bg');   
    });

    $('.side-list').mousemove(function(){
			$(this).find('.wd-listall').show();
			$(this).find('h3').addClass('hover');
	});
	$('.side-list').mouseleave(function(){
		$(this).find('.wd-listall').hide();
		$(this).find('h3').removeClass('hover');
	});
	var t,k=0;
	var imgL=$(".wd_changeImages img").length;
	$(".wd_picNav a").mouseover(function(){
		k=$(".wd_picNav a").index(this);
		navImg(k);
	});
	$(".wd_flashBox").hover(function(){
			clearInterval(t);
		}
		,function(){
			t=setInterval(function(){
				k++;
				if(k==imgL) k=0;
				navImg(k);
			},3000);
	}).trigger("mouseleave");
	
	function navImg(index){
		$(".wd_changeImages img").hide().eq(index).show();
		$(".wd_picNav a").removeClass("picA2").eq(index).addClass("picA2");
	}
	
	$(".tg_tab_nav li").mouseover(function(){
        $(".tg_tab_nav li").removeClass("tg_tab_fouce");
    	$(this).addClass("tg_tab_fouce");
    	switch ($(this).attr("id")) {
    		case "today" :
    			$(".tg_nr").show();
    			$(".tg_nr2").hide();
    			$(".tg_nr3").hide();			
    			break;
    		case "future" :
    			$(".tg_nr").hide();
    			$(".tg_nr2").show();
    			$(".tg_nr3").hide();
    			break;
    		case "after" :
    			$(".tg_nr").hide();
    			$(".tg_nr2").hide();
    			$(".tg_nr3").show();
    			break;
    		};
    });
  //商品详情页商品图片展示
    $('#example3').etalage({
        thumb_image_width: 328,
        thumb_image_height: 328,
        source_image_width: 900,
        source_image_height: 900,
        zoom_area_width: 500,
        zoom_area_height: 500,
        zoom_area_distance: 5,
        small_thumbs: 5,
        smallthumb_inactive_opacity: 0.5,
        smallthumbs_position: 'top',
        show_icon: true,
        icon_offset: 20,
        autoplay: false,
        keyboard: false,
        zoom_easing: false
    });
    
});
function setTab(name, cursel, n){
    $(".tabAbp span").removeClass();
    $('.tabAbpCon').hide();
    $('#'+name+cursel).addClass('onHover');
    $("#con_"+name+"_"+cursel).show();
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
        type:'POST',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#coll_goods").html(msgObj);
            return false;
        }
    }); 
}
function getGoodsAdvice(gid,page){
    $.ajax({
        url:'/Home/Products/getGoodsAdvice',
        dataType:'HTML',
        type:'POST',
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
        type:'POST',
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
        type:'POST',
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
        url:"/Ucenter/Collect/doAddGoodsCollect",
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
 function setTuanGouTime(times,showDay,showHouse,showFen,showMiao){
    var arr_time = times.split(" ");
    var fuckTime = arr_time[1].split(":");
    var time = new Date();
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