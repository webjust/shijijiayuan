
	/*窗口大小改变事件，重置右侧高度*/
	$(window).resize(function(){
		rheight();
	});

	$("#search_submit_button").click(function(){
	    var search_key=$("#head_serach_keyword").val();
	    if(search_key==''){return false;}
	    search_key=search_key.replace(/%0D%0A/,'');
	    search_key=search_key.replace(/%0d%0a/,'');
	    var __search_base_url="/Home/Hisense?keyword="+search_key+"#search_result_wrap";
	    window.location.href=__search_base_url;
	});

	function EnterPress(e){
	    var e=e||window.event;
	    if(e.keyCode==13){
	        var search_key=$("#head_serach_keyword").val();
	        if(search_key==''){return false;}
	        search_key=search_key.replace(/%0D%0A/,'');
	        search_key=search_key.replace(/%0d%0a/,'');
	        var __search_base_url="/Home/Hisense?keyword="+search_key+"#search_result_wrap";
	        window.location.href=__search_base_url;
	    }
	}
	//倒计时
    function timer(intDiff,id,type) {
        window.setInterval(function () {
            var day = 0,
                hour = 0,
                minute = 0,
                second = 0; //时间默认值
            if (intDiff > 0) {
                day = Math.floor(intDiff / (60 * 60 * 24));
                hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
                minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
                second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
            }
            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            if(intDiff <= 0){
                $('#day_time_'+id).html("活动已结束！");
                $('#day_show_'+id+',#hour_show_'+id+',#minute_show_'+id+',#second_show_'+id).html("");
            }else{
                $('#day_show_'+id).html(day + "天");
                $('#hour_show_'+id).html(hour + '时');
                $('#minute_show_'+id).html(minute + '分');
                $('#second_show_'+id).html(second + '秒');
            }
            intDiff--;
        }, 1000);
    }

    $(".quick_links_panel li").mouseenter(function(){
        $(this).children(".mp_tooltip").animate({left:-92,queue:true});
        $(this).children(".mp_tooltip").css("visibility","visible");
        $(this).children(".ibar_login_box").css("display","block");
    });
    $(".quick_links_panel li").mouseleave(function(){
        $(this).children(".mp_tooltip").css("visibility","hidden");
        $(this).children(".mp_tooltip").animate({left:-121,queue:true});
        $(this).children(".ibar_login_box").css("display","none");
    });
    $(".quick_toggle li").mouseover(function(){
        $(this).children(".mp_qrcode").show();
    });
    $(".quick_toggle li").mouseleave(function(){
        $(this).children(".mp_qrcode").hide();
    });

// 元素以及其他一些变量
var eleFlyElement = document.querySelector("#flyItem"), eleShopCart = document.querySelector("#shopCart");
var numberItem = 0;
// 抛物线运动
var myParabola = funParabola(eleFlyElement, eleShopCart, {
    speed: 400, //抛物线速度
    curvature: 0.0008, //控制抛物线弧度
    complete: function() {
        eleFlyElement.style.visibility = "hidden";
        //eleShopCart.querySelector("span").innerHTML = ++numberItem;//更新购物车的数量
    }
});
// 绑定点击事件

if (eleFlyElement && eleShopCart) {   
    [].slice.call(document.getElementsByClassName("btnCart")).forEach(function(button) {
        button.addEventListener("click", function(event) {
            // 滚动大小
            var thisurl = $(this).attr('_src');
            var flyItemurl = $("#flyItem img").attr('_src');
            if(thisurl!=null){
            	$("#flyItem img").attr('src',thisurl);
            }else{
            	$("#flyItem img").attr('src',flyItemurl);
            }
            var scrollLeft = document.documentElement.scrollLeft || document.body.scrollLeft || 0,
                scrollTop = document.documentElement.scrollTop || document.body.scrollTop || 0;
            eleFlyElement.style.left = event.clientX + scrollLeft + "px";
            eleFlyElement.style.top = event.clientY + scrollTop + "px";
            eleFlyElement.style.visibility = "visible";
            
            // 需要重定位
            myParabola.position().move();         
        });
    });
};
(function($){
	var z = {
		init : function(){
			this.initStat();
		}
	};
	$.extend(z,{
		initStat : function(){
				setTimeout(function(){	
					$(".top_bar").animate({"margin-top":"-145px"});
				},3000)	
				$(".top_bar").hover(function(){
					$(this).stop().animate({"margin-top":"0"});
				},function(){
					$(this).stop().animate({"margin-top":"-145px"});
				})
			$(".btn_enable_multi").click(function(){
				alert(1)
				$(".btn_fliter_expan").toggleClass("expand")
				$(this).toggleClass( "enable")
				$(".search_filter").toggleClass( "multi") 
				$(".filter_attrs").css({"height":"auto"})
				$(".search_filter.multi .filter_attrs ul li a").click(function(){
					$(this).toggleClass( "selected")

				})
				$(".btn_multi_reset").click(function(){
					$(".search_filter.multi .filter_attrs ul li a").removeClass("selected")
				})
			});
			//详情页列表收缩功能
			$(".btn_fliter_expan").toggle(function(){
				var html1=$(this).html()

				if(html1="更多"){
					$(this).html("收起")
				}
				$(this).parent().siblings(".filter_attrs").addClass("expand")
				$(this).addClass("expand")

			},function(){
				$(this).parent().siblings(".filter_attrs").removeClass("expand")
				$(this).removeClass("expand")
				if(html1="收起"){
					$(this).html("更多")
				}
			});

			$('input').blur(function() {
				var str = $(this).val();
				str = $.trim(str);
				var b=$(".textbox_ui label").css("display");
				if((str == "") && (b == "block")){
				}else{
					$('.submit_btn').css({"background":"#bfbfbf"})
				}
			});
			$('input').focus(function() {
				    $('.submit_btn').css({"background":"#bc005e"})
			});
			$(".tt_content .left dt").click(function(){
				$(this).siblings().slideToggle()
				$(this).toggleClass( "expend") 
			});
			$("#radio_dynamic").click(function(){
				
				$("#login-dynamic-form").css({"display":"block"})
				$("#login-user-form").css({"display":"none"})
			})	
			$("#radio_normal").click(function(){
								
				$("#login-user-form").css({"display":"block"})
				$("#login-dynamic-form").css({"display":"none"})

			})
			$('#aUserAgreement').click(function(e) {
				//让它显示 
				$('.login').fadeIn();
		    });	

			$('.deal_right_pic li').click(function(e) {
				//让它显示 
				
				$(this).addClass("i_selected").siblings().removeClass("i_selected")
		    });
			//首页鼠标移上去图片切换
		    $(".big_ul .brand-item").hover(function(){
				$('.big_ul .pic img').attr('alt',$(this).attr("alt"));
				$('.big_ul .pic img').attr('src',$(this).attr("data-src"));
				$('.big_ul .word div').html($(this).attr("data-word"));
		    })

			var wimb;
		    var wimw;
		    $(".conmment_star .stars").click(function() {
		        wimb = $(this).find("img").attr("src");
		        wimw = $(this).find("img").attr("_src");
		        $(this).find('img').attr('src', '' + wimw + '');
		    });
		    $(".conmment_star .stars").hover(function() {
		        wimb = $(this).find("img").attr("src");
		        wimw = $(this).find("img").attr("_src");
		        $(this).find('img').attr('src', '' + wimw + '');
		    }, function() {
		        $(this).find('img').attr('src', '' + wimb + '');
		    });
		}
	});
	z.init();
})(jQuery);
//详情页列表收缩功能
//$(function(){
//	var $filter=$(".filter_attrs ul li:gt(16)");
//	$filter.hide();
//	var $toggleFilterBtn=$(".filter_btn>a");
//	$toggleFilterBtn.click(function(){
//		if($filter.is(":visible")){
//			$filter.hide();
//			$toggleFilterBtn.text("更多");
//			$(this).removeClass("expand")
//		}else{
//			$filter.show();
//			$toggleFilterBtn.text("收起");
//			$(this).addClass("expand")
//		}
//		return false;
//	});
//});