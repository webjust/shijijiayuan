// JavaScript Document
/* CSS Document */
 /*------------------------------------------
  *
  * 必迈
  * js文件
  * Developed By: 李敏
  * 版本号：2015-8-10
  ----------------------------------------------*/
/* start reset */ 
$(function(){
	$(".weixin a").hover(function(){
		$(".code").toggle();})
		$(".searchbox").click(function(){
			$(this).addClass("searchbox1",600);})
})
$(function(){
if(!placeholderSupport()){   // 判断浏览器是否支持 placeholder
    $('[placeholder]').focus(function() {
        var input = $(this);
        if (input.val() == input.attr('placeholder')) {
            input.val('');
            input.removeClass('placeholder');
        }
    }).blur(function() {
        var input = $(this);
        if (input.val() == '' || input.val() == input.attr('placeholder')) {
            input.addClass('placeholder');
            input.val(input.attr('placeholder'));
        }
    }).blur();
};
/**********详细页js开始***************/

$(".Choice .color a ").click(function(){
	$(".Choice .color a ").removeClass("on");
	$(this).addClass("on");
	})
	$(".det_right .Choice .Size a").click(function(){
	$(".det_right .Choice .Size a").removeClass("on");
	$(this).addClass("on");
	})
	var total=$(".cm em").text();
	var index=$(".gray").index();
	 for(var i=0;i<index;i++){
	 var aa=$(".countNum").eq(i).find("em").text();
	 $(".gray").eq(i).find(".purple").css("width",(aa/total)*225+"px");
}
	 
/**********详细页js结束***************/
})
function placeholderSupport() {
    return 'placeholder' in document.createElement('input');
}




