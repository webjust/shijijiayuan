// JavaScript Document
// JavaScript Document
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
    tagObj[evt](function(){
            var idx = $(this).index();
            $(this).addClass(curCls).siblings().removeClass(curCls);
            tagCon.eq(idx).show().siblings().hide();
    })

}
// JavaScript Document
jQuery.divselect = function(divselectid,inputselectid) {
	var inputselect = $(inputselectid);
	$(divselectid+" span").click(function(){
		var ul = $(divselectid+" ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}else{
			ul.slideUp("fast");
		}
	});
	$(divselectid+" ul li a").click(function(){
		var txt = $(this).text();
		$(divselectid+" span").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(divselectid+" ul").hide();
		
	});
	$(document).click(function(){
		$(divselectid+" ul").hide();
	});
};
$(function(){		
	$(".Product .ProList ul li:nth-child(5n)").css("margin-right","0px");
	$(".pcontentL ul li:nth-child(2n)").css("background-color","#e7e7e7");
	$(".product .productL .pic a:last-child").css("margin-right","0px");
	$(".pic01 a:last-child").css("margin-right","0px");
	$(".pcontentL ul li:nth-child(2n)").css("background-color","#e7e7e7");
	$(".flSlider").slide({
		titCell: ".hd ul",
		mainCell: ".bd ul",
		effect: "fold",
		interTime: 4000,
		innerTime: 2000,
		autoPlay: true,
		autoPage: "<li><a></a></li>"
	});
	$(".flSlider1").slide({
		titCell: ".hd ul",
		mainCell: ".bd ul",
		effect: "left",
		innerTime: 2000,
		autoPlay: true,
		autoPage: "<li><a></a></li>",	   
	});
	$(".flSlider2").slide({
		titCell: ".hd ul",
		mainCell: ".bd ul",
		effect: "left",
		innerTime: 2000,
		autoPlay: true,
		autoPage: "<li><a></a></li>",   
	});
	$(".flSlider3").slide({
		titCell: ".hd ul",
		mainCell: ".bd ul",
		effect: "left",
		innerTime: 2000,
		autoPlay: true,
		autoPage: "<li><a></a></li>", 
	});
	$(".flSlider4 .tempWrap,.flSlider5 .tempWrap,.flSlider4 .tempWrap ul,.flSlider5 .tempWrap ul").css("width","700px");
	$(".pcontentL ul li b").click(function(){
		$(this).siblings("ul").toggle();
	})

	$(window).resize(function(){
		$(".flSlider .bd li a").css("width","100%");
		$(".flSlider .bd li a").parent().css("width","100%");
		$(".flSlider .bd li a").parent().parent().css("width","100%");
	})

	$(".proPrice ul li dl dd a").click(function(){
		$(this).addClass("on");
		$(this).siblings().removeClass("on");
	})
	$(".proPrice ul li dl dd a.num-icon").click(function(){
		$(this).removeClass("on");
	})
	$(".nowBuyBtn").click(function(){
		$("div.choose_type").animate({height: 'toggle', opacity: 'show'});	
	})
	$(".quitSelect").click(function(){
		$("div.choose_type").animate({height: 'hide'});	
	})
})

// JavaScript Document
$(function(){
	
	})
$(function(){
    var head=$(".headOne").outerHeight();
    var headSearch=$(".headerSearch").outerHeight();
    var wrap=$(".wrapT");
    var wrapD=$(".wrapD")
    var headN=$(".headN");
    wrap.css({"padding-top":head});
    wrapD.css({"padding-top":"0px"});
    headN.css({"top":head,"*top":head+headSearch});

    $(".searchBtn").click(function(){
        $(".headerSearch").animate({height:"show"},300).css("display","block");
        wrap.animate({"padding-top":head+headSearch},300);
        headN.animate({"top":head+headSearch},300);
        wrapD.animate({"padding-top":"0px"},300);
        document.getElementById('head_serach_keyword').focus();
    })
    $(".headerSearch .quitSearch").click(function(){

        $(this).parent().parent().animate({height:"hide"},300);
        wrap.animate({"padding-top":head},300);
        wrapD.animate({"padding-top":"0px"},300);
        headN.animate({"top":head},300);
    })
    $(".proList .bd ul li").mouseenter(function(){
        $(this).children(".property").show();
        $(this).addClass("hover");
        $(this).find("img").animate({width:"270px",height:"270px","padding":"5px"},300);
    })
    $(".proList .bd ul li").mouseleave(function(){
        $(this).children(".property").hide();
        $(this).removeClass("hover");
        $(this).find("img").animate({width:"280px",height:"280px","padding":"0px"},300);
    })
    $(".axuSlider").slide({
        titCell: ".hd ul",
        mainCell: ".bd ul",
        effect: "left",
        innerTime: 2000,
        autoPlay: true,
        autoPage: "<li><a></a></li>",


    });
    $(".productL").hover(function(){
        $(this).children(".picBtn").toggle();
    })

    $(".proPrice ul li dl dd a").click(function(){
        $(this).addClass("on");
        $(this).siblings().removeClass("on");
    })
    $(".nowBuyBtn").click(function(){
        $("div.choose_type").animate({height: 'toggle', opacity: 'show'});
    })
    $(".quitSelect").click(function(){
        $("div.choose_type").animate({height: 'hide'});
    })
})

$(window).resize(function(){
    $(".axuSlider .bd li a").css("width","100%");
    $(".axuSlider .bd li a").parent().css("width","100%");
    $(".axuSlider .bd li a").parent().parent().css("width","100%");
})


/*首页里的评价*/
function getComment(){
    $.ajax({
        url:'/Home/Index/getComment',
        dataType:'HTML',
        type:'POST',
        success:function(msgObj){
            $("#comment_index").html(msgObj);
            return false;
        }
    });
}

	

