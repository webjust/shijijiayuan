// JavaScript Document
$(function() { /**********详细页js开始***************/
/*
	$(".Choice .color a,.dpConfirm .selectType dl dd").click(function() {
		$(this).siblings().removeClass("on");
		$(this).addClass("on");
	})
	$(".Choice .Size a").click(function() {
		$(this).siblings().removeClass("on");
		$(this).addClass("on");
	})
	*/
	$(".detailList").ready(function() {
		var aa = $(this).height();
	})

})

$(function() {
	$(".nav-item .mainmenu").click(function() {
		$(this).siblings(".submenu").toggle();
	})
	$(".proListT-pro-buy").click(function() {
		$(".addcart").show(300);
		$("#right-icon .icon").addClass("s0")
	})
	/*
	$(".dpCommon .orderDR span.select").click(function() {
		//$(".dpConfirm").show();
	})*/
	$(".closeit,.confirm_addr").click(function() {
		$(this).parent().parent().parent().parent().animate({
			height: 'hide',
			speed: 300
		});
	})
	$(".addmessage").click(function() {
		$(this).parent().parent().parent().parent().parent().hide();
		$(".addressEdit1").show(300);

	})
	$(".addressEdit .default").click(function() {
		$(".by_confirm").hide();
	})
	$(".tranfer").click(function() {
		$(".fpNew").hide();
	})
	$(".fp").click(function() {
		$(".fpNew").show();
	})
	$(".orderEdit .scMeCon").click(function() {
		$(".addressEdit").show(300);

		$("#right-icon .icon").addClass("s0")
	})
	/*
	$(".dpConfirm .sku-layout .content-foot .f_right .btn.btn-orange-dark").click(function() {
		$(this).parent().parent().parent().parent().hide(300);
		var aa = $(".dpConfirm .selectType dl dd.on").text();
		$(".dpCommon .orderDR span.select").removeClass("select").addClass("selected");
		$(".dpCommon .orderDR span.selected").html("已选择" + aa);
	})*/
})