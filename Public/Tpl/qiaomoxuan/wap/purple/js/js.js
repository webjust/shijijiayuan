// JavaScript Document
	/*********异步加载购物车****/
	function ajaxLoadShoppingCart(int_page_num){
		if(!int_page_num){
			int_page_num = 1;
		}

		$.post('/Wap/Cart/mycartAjax',{'int_page_num':int_page_num},function(htmlObj){
			$("#shopping_cart_list").html(htmlObj);
		},'html');
	}
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
                alert("加入收藏成功");
            }else{
                alert(msgObj.info);
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
$(function(){
	$(".selector ul li a").click(function(){
		var inputselect=$("#inputselect");
		var txt = $(this).text();
		$(".selector .select").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(".selector ul").hide();

	});
	$(".selectorO ul li a").click(function(){
		var inputselect=$("#inputselect");
		var txt = $(this).text();
		$(".selectorO .select").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(".selectorO ul").hide();

	});
	$(".selectorT ul li a").click(function(){
		var inputselect=$("#inputselect");
		var txt = $(this).text();
		$(".selectorT .select").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(".selectorT ul").hide();

	});
	$(".selectorTr ul li a").click(function(){
		var inputselect=$("#inputselect");
		var txt = $(this).text();
		$(".selectorTr .select").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(".selectorTr ul").hide();

	});
	$(".navB a").click(function(){
		$(this).parent().toggleClass("selected");
		$(".menu").animate({height:"toggle"});
	})
	$(".navB a").toggle(function(){
		var navB1  = $("#navB1").val();
		$(this).children().attr("src",navB1);
	},
	function(){
		var navB  = $("#navB").val();
		$(this).children().attr("src",navB);
	})
	$(".menu a").click(function(){
		$(this).parent().animate({height:"hide",speed:400});
	})
	$(".footer ul>li a").click(function(){
		$(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
		$(this).children("i").toggleClass("show");
		$(this).parent().siblings().children(".menu_list").hide(500);
		$(this).siblings(".menu_list").animate({height:"toggle",speed:500});
	})
	$(".proNav ul>li>a").click(function(){
		$(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
		$(this).children("i").toggleClass("show");
		$(this).parent().siblings().children(".menu_list").hide(500);
		$(this).siblings(".menu_list").animate({height:"toggle",speed:500});
	})
	$(".proNav ul>li .menu_list span a ").click(function(){
		$(this).parent().siblings().children("a").removeClass("on");       
		 $(this).toggleClass("on");
	 })
	 $("#selectors .select").click(function(){
		 var ul = $("#selectors ul");
		 if(ul.css("display")=="none"){
			 ul.slideDown("fast");
		 }
		 else{
			 ul.slideUp("fast");
		 }
	})
/**********详细页js开始***************/

$(".Choice .color a ").click(function(){
$(this).siblings().removeClass("on");
	$(this).addClass("on");
	})
	$(".Choice .Size a").click(function(){
	$(this).siblings().removeClass("on");
	$(this).addClass("on");
	})
	var total=$(".cm em").text();
	var index=$(".gray").index();
	 for(var i=0;i<index;i++){
	 var aa=$(".countNum").eq(i).find("em").text();
	 $(".gray").eq(i).find(".purple").css("width",(aa/total)*175+"px");
}
	var sy_num=$(".text_input").val();
	var sy_nums=parseInt(sy_num);
	$("#minus").click(function(){
		if(sy_nums<=1){
			sy_nums=1;
		}else{
			sy_nums=sy_nums-1;
		}
		$(".text_input").attr("value",sy_nums);

	});
	$("#plus").click(function(){
		sy_nums=sy_nums+1;
		 $(".text_input").attr("value",sy_nums);

	});

	$(".detailCenterNav a").click(function(){
		$(this).parent().siblings().children("a").removeClass("on");
		$(this).addClass("on");
		$(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
		$(this).children("i").toggleClass("show");
		$(this).parent().siblings().children(".detailList").hide(500);
		$(this).siblings(".detailList").animate({height:"toggle",speed:500});
	})
/**********详细页js结束***************/
$(".selectorO .select").click(function(){
		var ul = $(".selectorO ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}
		else{
			ul.slideUp("fast");
		}
	})
	$(".selectorT .select").click(function(){
		var ul = $(".selectorT ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}
		else{
			ul.slideUp("fast");
		}
	})
	$(".selectorTr .select").click(function(){
		var ul = $(".selectorTr ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}
		else{
			ul.slideUp("fast");
		}
	})
jQuery.divselect = function(divselectid,inputselectid) {
    var inputselect = $(inputselectid);
    $(divselectid+" .select").click(function(){
    var ul = $(divselectid+" ul");
    if(ul.css("display")=="none"){
    ul.slideDown("fast");
    }else{
    ul.slideUp("fast");
    }
    });
    $(divselectid+" ul li").click(function(){
    var txt = $(this).children("a").html();
    $(divselectid+" .select").html(txt);
    var value = $(this).attr("selectid");
    inputselect.val(value);
    $(divselectid+" ul").hide();

    })
    };
})
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




