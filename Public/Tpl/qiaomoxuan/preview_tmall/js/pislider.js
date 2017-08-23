
(function($){
	$.fn.pislider=function(options){
		var defaults={
			child:"child",
			triggers:"triggers",
			scrollTime:500,
			autoScroll:"true",
			autoTime:4000
		};
	
		var options=$.extend(defaults,options);
		var _this=$(this);
		var child=_this.find("."+options.child);
		var triggers=$("#"+options.triggers);

		var len=child.length-1;
		child.wrapAll("<div id=scroll_wrapper></div>");//包裹层

		var height=child.height();
		
		var two_height=height*2;//两倍宽度
		var thr_height=height*3;//三倍宽度
	
		var wrap=$("#scroll_wrapper");
		wrap.css({height:thr_height+"px",top:-height+"px"});

		child.not(":first").hide();//除了第一个child都隐藏
	
		//判断动画是否在运行中
		function noMove(){
			if(!wrap.is(":animated")){
				return true;
			}
			else{
				return false;
			};
		};
		
		//根据图片数量生成小图标
		child.each(function(index){
			if(index==0){
				triggers.append("<a href='javascript:;' class='cur'></a>");
			}
			else{
				triggers.append("<a href='javascript:;'></a>");
			};
		});
		
		var cur_a=triggers.find("a.cur");//获取当前项a

		triggers.find("a").click(function(){
			var clickIndex=$(this).index();
			var nowIndex=triggers.find("a.cur").index();

			if(noMove()){
				if (clickIndex > nowIndex){
					scroll(clickIndex,two_height);
				}
				else if(clickIndex < nowIndex)
				{
					scroll(clickIndex,"0");
				}
				else
				{
					return false;
				};
			};
			return false;
		});		

		//执行动画
		function scroll(num,scroll_height){
			triggers.find("a").eq(num).addClass("cur").siblings().removeClass("cur");

			child.eq(num).show().css({top:scroll_height+"px"});//显示当前项对应图片，并赋left值

			wrap.animate({top:-scroll_height+"px"},options.scrollTime,function(){
				//动画执行后返回一下参数
				child.eq(num).css({top:height+"px"}).siblings().hide();
				wrap.css({top:-height+"px"});
			});
		};
		
		//上下按钮
		$("#btn_prev").click(function(){
			var curIndex=triggers.find("a.cur").index();
			if(noMove()){
				//滚动到第一个的时候跳到最后一个
				if (curIndex == 0){
					scroll(len,"0");
				}
				else{ 
					triggers.find("a.cur").prev("a").trigger("click");
				};
			};
			return false;
		});
	
		$("#btn_next").click(function(){
			var curIndex=triggers.find("a.cur").index();
			if(noMove()){
				//滚动到最后的时候跳到第一个
				if (curIndex == len){
					scroll("0",two_height);
				}
				else{
					triggers.find("a.cur").next("a").trigger("click");
				};
			};
			return false;
		});

		//时间开关控制
		if(options.autoScroll=="true" && child.length!=1){
			autoScroll=setInterval(function(){
				$("#btn_next").trigger("click")
			},options.autoTime);

			autoPlay=function(){
				autoScroll=setInterval(function(){
				$("#btn_next").trigger("click")
				},options.autoTime);
			};
			stopPlay=function(){
				clearInterval(autoScroll);
			};
			//鼠标经过事件
			_this.hover(stopPlay,autoPlay);
			$("#btn_prev,#btn_next").hover(stopPlay,autoPlay);
		};
		
	};
})(jQuery);