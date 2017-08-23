//执行合并支付操作
function mergerPayment() {
	if(!isCheck()){
		showAlert(false, "请勾选订单！");
		return false;
	}
	doMergerPayment();
}
function doMergerPayment(){
	var checkedOrder = $(".checkSon:checked");
	var pay_data = {};
	$.each(checkedOrder, function(i,dom){
		var o_id = $(dom).attr("o_id");
		var o_status =  $(dom).attr("o_status");
		var o_payment = $(dom).attr("o_payment");
		var o_all_price = $(dom).attr("o_all_price");
		var o_pay = $(dom).attr("o_pay");
		var o_pay_status = $(dom).attr("o_pay_status");
		if(1 != o_payment){
			o_payment = 1;
		}
		pay_data[o_id] = {
			"o_id":o_id,
			"o_status":o_status,
			"o_payment":o_payment,
			"o_all_price":o_all_price,
			"o_pay":o_pay
		};
		if(2 == o_status){
			alert("订单"+o_id+"已作废！");
			delete(pay_data[o_id]);
		}else if(4 == o_status){
			alert("订单"+o_id+"已完成！");
			delete(pay_data[o_id]);
		}else if(0 != o_pay_status){
			alert("订单"+o_id+"已支付,或已部分支付！");
			delete(pay_data[o_id]);
		}
	});
	$.ajax({
		url:"/Ucenter/MergerPayment/createMergerOrdersData",
		data:pay_data,
		dataType:"JSON",
		type:"POST",
		success:function(msgObj){
			if(msgObj.status === true){
				window.location.href="/Ucenter/MergerPayment/mergerOrderPage?mp_id="+msgObj.mp_id;
			}else{
				showAlert(false, msgObj.msg);
			}
		}
	});
}
//判断订单是否勾选
function isCheck(){
	if(1 > $(".checkSon:checked").length){
		return false;
	}else{
		return true;
	}
}
//选择未支付和预存款订单
$(function(){
	$(".ckeckAll").live("click", function(){
		var checkedOrdrs = $(".checkSon:checked");
		$.each(checkedOrdrs, function(i, dom){
			var o_status = $(dom).attr("o_status");
			var o_pay_status = $(dom).attr("o_pay_status");
			if(0 != o_pay_status){
				$(dom).attr("checked", false);
			}else if(2 == o_status || 4 == o_status){
				$(dom).attr("checked", false);
			}
		});
	});
});