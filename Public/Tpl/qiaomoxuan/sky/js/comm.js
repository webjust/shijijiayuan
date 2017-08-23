// JavaScript Document
// JavaScript Document
//标签切换


function ajaxLoadCouponList(){
	$.post('/Home/Coupon/index',{},function(htmlObj){
		$("#cou").html(htmlObj);
	},'html');
}

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
$(function(){
	menuInit();
	});
function menuInit(){
	var leftm = $('#left_menus').find('li'),
		rightm = $('.procon_r').find('li');
	leftm.on('click',function(){clkLeftMeun(this);});
	rightm.each(function(i,tiem){if(i>=0 && i<5){ $(tiem).off('click').on('click',function(){clkRightMenu(this);});}});
	function clkLeftMeun(ths){
		$('.procon_r').find('li').removeClass('cur')
		.end().find('a[href="'+$(ths).find('a').attr('href')+'"]').parent().addClass('cur');
	}
	function clkRightMenu(ths){
		rightm.removeClass('cur');
		$(ths).addClass('cur');
		var tar = $(ths).find('a').attr('href');
		leftm.removeClass('on').each(function(i,item){
			if($(item).children('a').attr('href') == tar){
				$(item).addClass('on');
				$('.tagCon').find('.ever').hide().each(function(a,it){if(a == i){ $(it).show();}});
			}
		});
	}
}
$(function(){
	var head=$(".headOne").outerHeight();
	var headSearch=$(".headerSearch").outerHeight();
	var wrap=$(".wrap");
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
// JavaScript Document

$(function() {
    $("#online li").each(function () {
        var that = $(this);
        var dtl = that.find('.detail');
        //that.find('.ToContact').show();
        that.find('.triggerBtn,.closeContact').click(function () {
			var _this = $(this);
			if(_this.attr("class").indexOf('couponX') >= 0 ){
				ajaxLoadCouponList();
			}
            that.siblings().find('.detail').hide()
            if (dtl.length) {
                if (dtl.is(":hidden")) {
                    dtl.animate({speed: 300, width: "show"});
                    that.addClass('red').siblings().removeClass('red');
                } else {
                    dtl.animate({speed: 300, width: "hide"});
                    that.removeClass('red').siblings().removeClass('red');
                }
            }
        });
    });
});





