// JavaScript Document
//异步获取会员信息
ajaxLoadShoppingMember();
function ajaxLoadShoppingMember(){
	$.post('/Home/User/showMemberInfo',{},function(htmlObj){
		$("#shopping_member").html(htmlObj);
	},'html');
}
/**
* JavaScript脚本实现回到页面顶部示例
* @param acceleration 速度
* @param stime 时间间隔 (毫秒)
**/
function gotoTop(acceleration,stime) {
   acceleration = acceleration || 0.1;
   stime = stime || 10;
   var x1 = 0;
   var y1 = 0;
   var x2 = 0;
   var y2 = 0;
   var x3 = 0;
   var y3 = 0; 
   if (document.documentElement) {
       x1 = document.documentElement.scrollLeft || 0;
       y1 = document.documentElement.scrollTop || 0;
   }
   if (document.body) {
       x2 = document.body.scrollLeft || 0;
       y2 = document.body.scrollTop || 0;
   }
   var x3 = window.scrollX || 0;
   var y3 = window.scrollY || 0;
 
   // 滚动条到页面顶部的水平距离
   var x = Math.max(x1, Math.max(x2, x3));
   // 滚动条到页面顶部的垂直距离
   var y = Math.max(y1, Math.max(y2, y3));
 
   // 滚动距离 = 目前距离 / 速度, 因为距离原来越小, 速度是大于 1 的数, 所以滚动距离会越来越小
   var speeding = 1 + acceleration;
   window.scrollTo(Math.floor(x / speeding), Math.floor(y / speeding));
 
   // 如果距离不为零, 继续调用函数
   if(x > 0 || y > 0) {
       var run = "gotoTop(" + acceleration + ", " + stime + ")";
       window.setTimeout(run, stime);
   }
}

//导航分类自动切换行号
var num=0;
function tabCat(obj,index_cli) {	
	$(obj).addClass("onhover").siblings().removeClass("onhover");
	num = index_cli;
}
$(function(){
	
	setTimeout(function(){
		$(".imagOne").slideUp(1000,function(){
			$(".imagTwo").slideDown(1000);
		});
	},2000)
	
	//头部搜索下面的nav
	$(".search p.p02 a:even").each(function(){
		$(this).css("color","#C40000")
	})
	
	//楼层导航条最后一个span去掉
	$(".title p span:last,.search p.p02 span:last").each(function(){
		$(this).hide();
	})
	
	//轮播
	var sWidth = $("#focus").width();	
	var len = $("#focus ul li").length;	

	var index = 0;
	var picTimer;
	
	var btn = "<div class='btn'>";
	for(var i=0; i < len; i++) {
		btn += "<span></span>";
	}
	
	btn += "</div>";
	$("#focus").append(btn);
	
	$("#focus .btn span").mouseenter(function() {
		index = $("#focus .btn span").index(this);
		showPics(index);
	}).eq(0).trigger("mouseenter");

	$("#focus ul").css("width",sWidth * (len));
	
	$("#focus").hover(function() {
		clearInterval(picTimer);
	},function() {
		picTimer = setInterval(function() {
			showPics(index);
			index++;
			if(index == len) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics(index) { 
		var nowLeft = -index*sWidth; 
		$("#focus ul li").css("width",sWidth);
		$("#focus ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
	
	//所有商品分类
	var ti;
	$(".classi").mouseover(function(){
		clearTimeout(ti);
		$(".claCon02").slideDown();
	})
	$(".classi").mouseout(function(){
		ti=setTimeout(function(){
			$(".claCon02").slideUp();
		},500)
	})

	//首页所有商品分类
	var ttime;
	var cli =$(".claCon ul li");
	//console.log(cli);
	

	/*
	cli.mouseover(function(){
		var index_num =cli.index(this);
		cli.eq(index_num).addClass("onhover").siblings().removeClass("onhover");
		num = index_num;
		console.log(num);
	});
	*/
	
	cli.hover(function(){
		clearInterval(ttime);
	},function(){
		ttime=setInterval(function(){
			cli.eq(num).addClass("onhover").siblings().removeClass("onhover");
			num++;
			if(num==cli.length){
			   num=0;
			}
		},30000)
	}).eq(0).trigger("mouseleave");
	
	
	//首页商品详情
	/**
	var det =$(".deta");
	var detSpan =$(".picSmall span");
	for(var d=0; d<detSpan.length; d++){
		detSpan.eq(d).click(function(){
			var n=detSpan.index(this);
			$(this).addClass("onHover").siblings().removeClass("onHover");
			det.eq(n).css("display","block").siblings().css("display","none");
		})
	}
	**/
	//试用列表页
	var syList =$(".syL05 p span");
	var brandlist = $(".brandlist");
	for(var p=0; p<syList.length; p++){
		syList.eq(p).click(function(){
			var nu=syList.index(this);
			$(this).addClass("onHover").siblings().removeClass("onHover");
			brandlist.eq(nu).css("display","block").siblings("div").css("display","none");
		})
	}
	
	var syconSpan =$(".sycon p.p01 span");
	var syconlist =$(".syconlist");
	for(var p=0; p<syconSpan.length; p++){
		syconSpan.eq(p).click(function(){
			var nu=syconSpan.index(this);
			$(this).addClass("onhover").siblings().removeClass("onhover");
			syconlist.eq(nu).css("display","block").siblings("div").css("display","none");
		})
	}
	
	
	
	//团购列表页  浮层部分
	$(".tgNav dt").click(function(){
		$(this).next().slideDown().siblings("dd").slideUp();
	})
	
	//团购列表页  li鼠标经过效果
	$(".tgLThree ul li").hover(function(){
		$(this).css({"backgroundColor":"#F1F1F1","position":"relative","z-index":"2"})
	},function(){
		$(this).css({"backgroundColor":"white","position":"relative","z-index":"1"})
	})
	
	//团购列表排序选择
	$(".tgLTwo span a").click(function(){
		$(this).css({"color":"#F04C44","backgroundColor":"white"}).siblings().css({"color":"#333","backgroundColor":"#F5F5F5"})
	})
	
	//商品详情页  右边切换效果
	var numLi =0;
	var liLen =$(".topdcon ul li").length;
	
	$(".preNext a.next").click(function(){
		numLi +=1;
		if(numLi==liLen){ numLi=0};
		showli(numLi);
	})
	$(".preNext a.pre").click(function(){
		numLi -=1;
		if(numLi==-1){ numLi=liLen -1};
		showli(numLi);
	})
	
	function showli(numLi){
		var nowTop=-numLi*474;
		$(".topdcon ul").css("height",474*liLen).animate({
			"top":nowTop
		},300);
	}
	
})

$(function(){
	//轮播
	var sWidth2 = $("#focus2").width();	
	var len2 = $("#focus2 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus2").append(btn2);
	
	$("#focus2 .btn span").mouseenter(function() {
		index = $("#focus2 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus2 ul").css("width",sWidth2 * (len2));
	
	$("#focus2").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth2; 
		$("#focus2 ul li").css("width",sWidth2);
		$("#focus2 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus2 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth3 = $("#focus3").width();	
	var len3 = $("#focus3 ul li").length;	
	var index = 0;
	var picTimer;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus3").append(btn2);
	
	$("#focus3 .btn span").mouseenter(function() {
		index = $("#focus3 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus3 ul").css("width",sWidth3 * (len3));
	
	$("#focus3").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth3; 
		$("#focus3 ul li").css("width",sWidth3);
		$("#focus3 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus3 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth4 = $("#focus4").width();	
	var len3 = $("#focus4 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus4").append(btn2);
	
	$("#focus4 .btn span").mouseenter(function() {
		index = $("#focus4 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus4 ul").css("width",sWidth4 * (len3));
	
	$("#focus4").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth4; 
		$("#focus4 ul li").css("width",sWidth4);
		$("#focus4 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus4 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth5 = 120;	
	var len3 = $("#focus5 dl dd").length;	
	var index = 0;
	var t;
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus5").append(btn2);
	
	$("#focus5 .btn span").mouseenter(function() {
		index = $("#focus5 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus5 dl").css("width",sWidth5 * (len3));
	
	$("#focus5").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth5; 
		$("#focus5 dl dd").css("width",sWidth5);
		$("#focus5 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus5 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus6 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus6").append(btn2);
	
	$("#focus6 .btn span").mouseenter(function() {
		index = $("#focus6 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus6 dl").css("width",sWidth6 * (len3));
	
	$("#focus6").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus6 dl dd").css("width",sWidth6);
		$("#focus6 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus6 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus7 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus7").append(btn2);
	
	$("#focus7 .btn span").mouseenter(function() {
		index = $("#focus7 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus7 dl").css("width",sWidth6 * (len3));
	
	$("#focus7").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus7 dl dd").css("width",sWidth6);
		$("#focus7 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus7 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus8 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus8").append(btn2);
	
	$("#focus8 .btn span").mouseenter(function() {
		index = $("#focus8 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus8 dl").css("width",sWidth6 * (len3));
	
	$("#focus8").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus8 dl dd").css("width",sWidth6);
		$("#focus8 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus8 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus9 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus9").append(btn2);
	
	$("#focus9 .btn span").mouseenter(function() {
		index = $("#focus9 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus9 dl").css("width",sWidth6 * (len3));
	
	$("#focus9").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus9 dl dd").css("width",sWidth6);
		$("#focus9 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus9 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus10 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus10").append(btn2);
	
	$("#focus10 .btn span").mouseenter(function() {
		index = $("#focus10 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus10 dl").css("width",sWidth6 * (len3));
	
	$("#focus10").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus10 dl dd").css("width",sWidth6);
		$("#focus10 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus10 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus11 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus11").append(btn2);
	
	$("#focus11 .btn span").mouseenter(function() {
		index = $("#focus11 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus11 dl").css("width",sWidth6 * (len3));
	
	$("#focus11").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus11 dl dd").css("width",sWidth6);
		$("#focus11 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus11 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus12 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus12").append(btn2);
	
	$("#focus12 .btn span").mouseenter(function() {
		index = $("#focus12 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus12 dl").css("width",sWidth6 * (len3));
	
	$("#focus12").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus12 dl dd").css("width",sWidth6);
		$("#focus12 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus12 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus13 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus13").append(btn2);
	
	$("#focus13 .btn span").mouseenter(function() {
		index = $("#focus13 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus13 dl").css("width",sWidth6 * (len3));
	
	$("#focus13").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus13 dl dd").css("width",sWidth6);
		$("#focus13 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus13 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus14 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus14").append(btn2);
	
	$("#focus14 .btn span").mouseenter(function() {
		index = $("#focus14 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus14 dl").css("width",sWidth6 * (len3));
	
	$("#focus14").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus14 dl dd").css("width",sWidth6);
		$("#focus14 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus14 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus15 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus15").append(btn2);
	
	$("#focus15 .btn span").mouseenter(function() {
		index = $("#focus15 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus15 dl").css("width",sWidth6 * (len3));
	
	$("#focus15").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus15 dl dd").css("width",sWidth6);
		$("#focus15 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus15 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth6 = 120;	
	var len3 = $("#focus16 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus16").append(btn2);
	
	$("#focus16 .btn span").mouseenter(function() {
		index = $("#focus16 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus16 dl").css("width",sWidth6 * (len3));
	
	$("#focus16").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth6; 
		$("#focus16 dl dd").css("width",sWidth6);
		$("#focus16 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus16 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
	
	
	
})

$(function(){
	//轮播
	var sWidth17 = 660;
	var len2 = $("#focus17 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus17").append(btn2);
	
	$("#focus17 .btn span").mouseenter(function() {
		index = $("#focus17 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus17 dl").css("width",sWidth17 * (len2));
	
	$("#focus17").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth17; 
		$("#focus17 dl dd").css("width",sWidth17);
		$("#focus17 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus17 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth18 = 660;
	var len2 = $("#focus18 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus18").append(btn2);
	
	$("#focus18 .btn span").mouseenter(function() {
		index = $("#focus18 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus18 dl").css("width",sWidth18 * (len2));
	
	$("#focus18").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth18; 
		$("#focus18 dl dd").css("width",sWidth18);
		$("#focus18 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus18 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth19 = 660;
	var len2 = $("#focus19 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus19").append(btn2);
	
	$("#focus19 .btn span").mouseenter(function() {
		index = $("#focus19 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus19 dl").css("width",sWidth19 * (len2));
	
	$("#focus19").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth19; 
		$("#focus19 dl dd").css("width",sWidth19);
		$("#focus19 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus19 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth20 = 660;
	var len2 = $("#focus20 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus20").append(btn2);
	
	$("#focus20 .btn span").mouseenter(function() {
		index = $("#focus20 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus20 dl").css("width",sWidth20 * (len2));
	
	$("#focus20").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth20; 
		$("#focus20 dl dd").css("width",sWidth20);
		$("#focus20 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus20 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth21 = 660;
	var len2 = $("#focus21 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus21").append(btn2);
	
	$("#focus21 .btn span").mouseenter(function() {
		index = $("#focus21 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus21 dl").css("width",sWidth21 * (len2));
	
	$("#focus21").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth21; 
		$("#focus21 dl dd").css("width",sWidth21);
		$("#focus21 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus21 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth22 = 660;
	var len2 = $("#focus22 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus22").append(btn2);
	
	$("#focus22 .btn span").mouseenter(function() {
		index = $("#focus22 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus22 dl").css("width",sWidth22 * (len2));
	
	$("#focus22").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth22; 
		$("#focus22 dl dd").css("width",sWidth22);
		$("#focus22 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus22 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth23 = 660;
	var len2 = $("#focus23 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus23").append(btn2);
	
	$("#focus23 .btn span").mouseenter(function() {
		index = $("#focus23 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus23 dl").css("width",sWidth23 * (len2));
	
	$("#focus23").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth23; 
		$("#focus23 dl dd").css("width",sWidth23);
		$("#focus23 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus23 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth24 = 660;
	var len2 = $("#focus24 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus24").append(btn2);
	
	$("#focus24 .btn span").mouseenter(function() {
		index = $("#focus24 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus24 dl").css("width",sWidth24 * (len2));
	
	$("#focus24").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth24; 
		$("#focus24 dl dd").css("width",sWidth24);
		$("#focus24 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus24 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth25 = 660;
	var len2 = $("#focus25 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus25").append(btn2);
	
	$("#focus25 .btn span").mouseenter(function() {
		index = $("#focus25 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus25 dl").css("width",sWidth25 * (len2));
	
	$("#focus25").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth25; 
		$("#focus25 dl dd").css("width",sWidth25);
		$("#focus25 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus25 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})


$(function(){
	//轮播
	var sWidth26 = 660;
	var len2 = $("#focus26 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus26").append(btn2);
	
	$("#focus26 .btn span").mouseenter(function() {
		index = $("#focus26 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus26 dl").css("width",sWidth26 * (len2));
	
	$("#focus26").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth26; 
		$("#focus26 dl dd").css("width",sWidth26);
		$("#focus26 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus26 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})


$(function(){
	//轮播
	var sWidth27 = 660;
	var len2 = $("#focus27 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus27").append(btn2);
	
	$("#focus27 .btn span").mouseenter(function() {
		index = $("#focus27 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus27 dl").css("width",sWidth27 * (len2));
	
	$("#focus27").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth27; 
		$("#focus27 dl dd").css("width",sWidth27);
		$("#focus27 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus27 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})


$(function(){
	//轮播
	var sWidth28 = 660;
	var len2 = $("#focus28 dl dd").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus28").append(btn2);
	
	$("#focus28 .btn span").mouseenter(function() {
		index = $("#focus28 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus28 dl").css("width",sWidth28 * (len2));
	
	$("#focus28").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth28; 
		$("#focus28 dl dd").css("width",sWidth28);
		$("#focus28 dl").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus28 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth29 = 1000;	
	var len2 = $("#focus29 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focus29").append(btn2);
	
	$("#focus29 .btn span").mouseenter(function() {
		index = $("#focus29 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus29 ul").css("width",sWidth29 * (len2));
	
	$("#focus29").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth29; 
		$("#focus29 ul li").css("width",sWidth29);
		$("#focus29 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus29 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//轮播
	var sWidth30 = $("#focus30").width();	
	var len3 = $("#focus30 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus30").append(btn2);
	
	$("#focus30 .btn span").mouseenter(function() {
		index = $("#focus30 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus30 ul").css("width",sWidth30 * (len3));
	
	$("#focus30").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth30; 
		$("#focus30 ul li").css("width",sWidth30);
		$("#focus30 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus30 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth31 = $("#focus31").width();	
	var len3 = $("#focus31 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus31").append(btn2);
	
	$("#focus31 .btn span").mouseenter(function() {
		index = $("#focus31 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus31 ul").css("width",sWidth31 * (len3));
	
	$("#focus31").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth31; 
		$("#focus31 ul li").css("width",sWidth31);
		$("#focus31 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus31 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth32 = $("#focus32").width();	
	var len3 = $("#focus32 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus32").append(btn2);
	
	$("#focus32 .btn span").mouseenter(function() {
		index = $("#focus32 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus32 ul").css("width",sWidth32 * (len3));
	
	$("#focus32").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth32; 
		$("#focus32 ul li").css("width",sWidth32);
		$("#focus32 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus32 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth33 = $("#focus33").width();	
	var len3 = $("#focus33 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus33").append(btn2);
	
	$("#focus33 .btn span").mouseenter(function() {
		index = $("#focus33 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus33 ul").css("width",sWidth33 * (len3));
	
	$("#focus33").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth33; 
		$("#focus33 ul li").css("width",sWidth33);
		$("#focus33 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus33 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})

$(function(){
	//轮播
	var sWidth34 = $("#focus34").width();	
	var len3 = $("#focus34 ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len3; i++) {
		btn2 += "<span></span>";
	}
	
	btn2 += "</div>";
	$("#focus34").append(btn2);
	
	$("#focus34 .btn span").mouseenter(function() {
		index = $("#focus34 .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focus34 ul").css("width",sWidth34 * (len3));
	
	$("#focus34").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len3) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth34; 
		$("#focus34 ul li").css("width",sWidth34);
		$("#focus34 ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focus34 .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 -5px"); 
	}
})


function setTab(name,cursel,n){
	for(i=1;i<=n;i++){
		var tab=document.getElementById(name+i);
		var con=document.getElementById("con_"+name+"_"+i);
		tab.className=i==cursel?"onHover":"";
		con.style.display=i==cursel?"block":"none";
	}
}

//首页详情页效果部分
window.onload=function(){
	/*var picBig =document.getElementById('picBig');
	var picsmall =document.getElementById('picsmall');
	var bigpic = picBig.getElementsByTagName("div");
	var smallpic =picsmall.getElementsByTagName('span');
	
	for(var i=0;i<smallpic.length;i++){
		smallpic[i].index=i;//这个index是自定义属性
		smallpic[i].onclick=function(){
			for(var t=0;t<smallpic.length;t++){
				bigpic[t].style.display='none';
				smallpic[t].className='';
			}
			this.className='onHover';
			bigpic[this.index].style.display='block';
		}
	}*/
	
/*	//首页所有商品分类导航
	var allNav =document.getElementById('navAll');
	var allNavli =allNav.getElementsByTagName('li');
	for(var n=0;n<allNavli.length;n++){
		allNavli[n].onmouseover=function(){
			for(var m=0;m<allNavli.length;m++){
				allNavli[m].className='';
			}
			this.className='onhover'
		}
		
	}*/
	
	
}

$(function(){
	$(".morePro span.span01").click(function(){
		$(this).hide();
		$(".p-btns").show();
		$(".proLTwoOR ul").css("height","auto");	
		$(".proLTwoOR").addClass("brandselect");	
		$(".proLTwoOR ul li a").attr("onclick","javascript:void(0);return false;");	
	})
	$("a.pcancel").click(function(){
		$(".morePro span.span01").show();
		$(".p-btns").hide();
		$(".proLTwoOR ul").css({"height":"106px","overflow":"hidden"});
		$(".proLTwoOR ul li a").attr("onclick","");
	})
	
	$(".morePro span.span02").toggle(function(){
		$(this).css("background","url("+images_url+"up.png) no-repeat 30px center").html("收起");
		$(this).parent().siblings().css("height","auto");
	},function(){
		$(this).html("更多");
		$(this).parent().siblings().css({"height":"106px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})

	$(".morePro span.span03").toggle(function(){
		$(this).css("background","url("+images_url+"up.png) no-repeat 30px center").html("收起");
		$(this).parent().siblings().css("height","auto");
	},function(){
		$(this).html("更多");
		$(this).parent().siblings().css({"height":"73px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})
	$(".morePro span.span04").toggle(function(){
		$(this).css("background","url("+images_url+"up.png) no-repeat 30px center").html("收起");
		$(this).parent().siblings().css("height","auto");
	},function(){
		$(this).html("更多");
		$(this).parent().siblings().css({"height":"78px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})
	$(".morePro span.span05").toggle(function(){
		$(this).css("background","url("+images_url+"up.png) no-repeat 30px center").html("收起");
		$(this).parent().siblings().css("height","auto");
	},function(){
		$(this).html("更多");
		$(this).parent().siblings().css({"height":"78px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})
/***	
	$(".morePro span.span03").toggle(function(){
		$(this).css("background","url("+images_url+"/up.png) no-repeat 30px center").html("收起");
		$(".proLTwoTR ul").css("height","auto");
	},function(){
		$(this).html("更多");
		$(".proLTwoTR ul").css({"height":"72px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})
	
	$(".morePro span.span04").click(function(){
		$(this).hide();
		$(".x-btns").show();
		$(".proLThR ul").css("height","auto");
	})
	$("a.xcancel").click(function(){
		$(".morePro span.span04").show();
		$(".x-btns").hide();
		$(".proLThR ul").css({"height":"36px","overflow":"hidden"});
	})
	
	$(".morePro span.span05").toggle(function(){
		$(this).css("background","url("+images_url+"/up.png) no-repeat 30px center").html("收起");
		$(this).parent().siblings().css("height","auto");
	},function(){
		$(this).html("更多");
		$(this).parent().siblings().css({"height":"36px","overflow":"hidden"});
		$(this).css("background","url("+images_url+"/xia.png) no-repeat 30px center").html("更多");
	})
    ***/
	
	$(".proLTwoF span").toggle(function(){
		$(".j_proLTh").show();
		$(this).html("精简选项");
	},function(){
		$(".j_proLTh").hide();
		$(this).html("更多选项");
	})
	
	$(".proF ul li").hover(function(){
		$(this).css("border","4px solid #C40000");
	},function(){
		$(this).css("border","4px solid #fff");
	})
	//判断页面是首页还是其他页面，首页展示类目其他页面隐藏类目导航
	var is_show_category = ($('#is_show_category').val() == undefined)?0:1;
	if(is_show_category != '1'){
		$('#category_show').addClass('claCon02');
	}
	if(is_show_category == '1'){
		$('#category_show').css('display','');
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
})

$(function(){
	//轮播
	var sWidth2 = $("#focusGroupbuy").width();	
	var len2 = $("#focusGroupbuy ul li").length;	
	var index = 0;
	var t;
	
	var btn2 = "<div class='btn'>";
	for(var i=0; i < len2; i++) {
		btn2 += "<span>"+(i+1)+"</span>";
	}
	
	btn2 += "</div>";
	$("#focusGroupbuy").append(btn2);
	
	$("#focusGroupbuy .btn span").mouseenter(function() {
		index = $("#focusGroupbuy .btn span").index(this);
		showPics2(index);
	}).eq(0).trigger("mouseenter");

	$("#focusGroupbuy ul").css("width",sWidth2 * (len2));
	
	$("#focusGroupbuy").hover(function() {
		clearInterval(t);
	},function() {
		t = setInterval(function() {
			showPics2(index);
			index++;
			if(index == len2) {index = 0;}
		},3000); 
	}).trigger("mouseleave");

	function showPics2(index) { 
		var nowLeft = -index*sWidth2; 
		$("#focusGroupbuy ul li").css("width",sWidth2);
		$("#focusGroupbuy ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focusGroupbuy .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

$(function(){
	//限时抢购首页轮播
	var sWidthqg = $("#focusqg").width();	
	var len = $("#focusqg ul li").length;	
	var index = 0;
	var picTimer;
	
	var btn = "<div class='btnBg'></div><div class='btn'>";
	for(var i=0; i < len; i++) {
		btn += "<span>"+(i+1)+"</span>";
	}
	
	btn += "</div><div class='preNext pre'></div><div class='preNext next'></div>";
	$("#focusqg").append(btn);

	
	$("#focusqg .btn span").mouseenter(function() {
		index = $("#focusqg .btn span").index(this);
		showPics(index);
	}).eq(0).trigger("mouseenter");
	
	
	$("#focusqg").hover(function(){
		$("#focusqg .preNext").show();
	},function(){
		$("#focusqg .preNext").hide();
	})
	$("#focusqg .pre").hover(function() {
		$(this).css("backgroundPosition","-138px -246px")
	},function() {
		$(this).css("backgroundPosition","0 -246px")
	});
	$("#focusqg .next").hover(function() {
		$(this).css("backgroundPosition","-46px -246px")
	},function() {
		$(this).css("backgroundPosition","-92px -246px")
	});

	
	$("#focusqg .pre").click(function() {
		index -= 1;
		if(index == -1) {index = len - 1;}
		showPics(index);
	});

	
	$("#focusqg .next").click(function() {
		index += 1;
		if(index == len) {index = 0;}
		showPics(index);
	});

	
	$("#focusqg ul").css("width",sWidthqg * (len));
	
	
	$("#focusqg").hover(function() {
		clearInterval(picTimer);
	},function() {
		picTimer = setInterval(function() {
			showPics(index);
			index++;
			if(index == len) {index = 0;}
		},3000); 
	}).trigger("mouseleave");
	
	//
	function showPics(index) { 
		var nowLeft = -index*sWidthqg; 
		$("#focusqg ul li").css("width",sWidthqg);
		
		$("#focusqg ul").stop(true,false).animate({"left":nowLeft},300); 
		$("#focusqg .btn span").stop(true,false).css("backgroundPosition","0 0").eq(index).stop(true,false).css("backgroundPosition","0 bottom"); 
	}
})

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
                    $('#showGrouupbuy'+listId).html('<input type="button" id="addNotOrder" disabled class="notSpike" value="立即购买" />');
                    $('#showGroupTime'+listId).html('<span><abbr>此秒杀已结束</abbr></span>');
                } else if(buy_status == 1){
                    $('#showGrouupbuy'+listId).html('<input type="button" id="addToOrder" class="maySpike" gid="{$detail.gid}"  value="立即购买" />');
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
			else if(buy_type == 'presale'){
				if(buy_status == 2){
					$('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" class="pastbuy goTobuy">该预售已结束</a>');
					$('#showGroupTime'+listId).prev().html('<strong>此预售已结束</strong>');
				} else if(buy_status == 1){
					$('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" onclick="addToOrder(2);" class="goTobuy" >立即抢购</a>');
					setGroupbuyTime(gpEndTime,'day','hours','minutes','seconds',times,'presale',2,'',listId,gid);
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
 *团购列表页倒计时
 *@ param intDiff 倒计时总秒数
 */
function timer(intDiff,obj,strTime){
    window.setInterval(function(){
    var day=0,
        hour=0,
        minute=0,
        second=0;//时间默认值        
    if(intDiff > 0){
        day = Math.floor(intDiff / (60 * 60 * 24));
        hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
        minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
        second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
    }
    if (minute <= 9) minute = '0' + minute;
    if (second <= 9) second = '0' + second;
    
    obj.html(strTime+'<label>'+day+'</label> 天 <label>'+hour+'</label> 时 <label>'+minute+'</label> 分 <label>'+second+'</label> 秒');
    //$('#day_show').html(day+"天");
    //$('#hour_show').html('<s id="h"></s>'+hour+'时');
    //$('#minute_show').html('<s></s>'+minute+'分');
    //$('#second_show').html('<s></s>'+second+'秒');
    intDiff--;
    }, 1000);
}


/**
 * 动态获取商品销售规格列表
 * @param item_id
 * @param item_type
 */
function getDetailSkus(item_id, item_type){
    $.ajax({
        url:'/Home/Products/getDetailSkus',
        dataType:'HTML',
        type:'GET',
        data:{
            item_type: item_type,
            item_id: item_id
        },
        success:function(msgObj){
            $("#showDetailSkus").html(msgObj);
            return false;
        }
    });
}
