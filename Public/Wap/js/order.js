$(document).ready(function(){
	logistic_display();
});
    //选择物流公司
function checkLogistic(vals){
    var bonus = $("#bonus_input").val();
    var cards = $("#cards_input").val();
    var jlb = $("#jlb_input").val();
    var point = $("#point_input").val();
    var csn = $("#coupon_num").val();
    var bonus_t = $("#bonus_total").text();
    var cards_t = $("#cards_total").text();
    var jlb_t = $("#jlb_total").text();
    var point_t = $("#point_total").text();
    var lt_id = $(":radio[name=lt_id][checked]").val();
    if(vals != "") lt_id = vals;
    var pids = $("#goods_pids").val();
    var gids = new Array();
    $("input[name='gid']").each(function(i){
        gids[i] = $(this).val();
    });
    if(gids == ''){
        $.ThinkBox.error("请检查商品是否存在");
        return false;
    }
    var url = '/Wap/Ucenter/ChangeLogistic/';
    $.post(url,{'csn':csn,'bonus':bonus,'cards':cards,'jlb':jlb,'point':point,'lt_id':lt_id,'pids':pids,'type':10,'gid':gids},function(jsonData){
    //$.post(url,{'lt_id':val,'pids':$("#goods_pids").val()},function(jsonData){
        if(jsonData.success){
            var goods_all_price = (parseFloat($("#goods_all_price").val())).toFixed(2);
            var logistic_money = (parseFloat(jsonData.logistic_price)).toFixed(2);
            $("#o_cost_freight").val(logistic_money);
            //var promotion_price = (parseFloat($("#all_orders_promotion_price"))).toFixed(2);
            var coupon_price = 0;
            var logistic_delivery=jsonData.logistic_delivery;
            var pc_position=jsonData.pc_position;
            var coupon_price = (parseFloat($("#used_coupon_price").val())).toFixed(2);
            $("#logistic_price").html('￥'+logistic_money);
            //$("#promotion_price").html('￥'+promotion_price);
            //$("#all_orders_promotion_price").attr('value',parseFloat(jsonData.promotion_price));
            var all_orders_price = (parseFloat(goods_all_price)+parseFloat(logistic_money)-coupon_price).toFixed(2);
            $("#all_orders_price").html('￥'+all_orders_price);
            if ( $("#is_zt").length > 0 ) {
                if(jsonData.lc_abbreviation_name =='ZT'){
                    $("#is_zt_display").css({'display':'none'});
                    $("#is_zt").attr('value',1);
                }else{
                    $("#is_zt_display").css({'display':''});
                    $("#is_zt").attr('value',0);
                }
            }
            if(logistic_delivery == true && pc_position == 1){
                $("#o_payment6").parent().parent().remove();
                var showHtml='<p><label><input type="radio" checked="checked"  value="6" name="o_payment" id="o_payment" >';
                    showHtml +='<input type="hidden" value="'+pay_name+'" id="o_payment6" name="o_payment6">'+pay_name+'</p></label>';
                showHtml +=$("#payment_list").html();
                $("#payment_list").children().remove();
                $("#payment_list").append(showHtml);
            }else if(logistic_delivery == true && pc_position != 1){
                var o_payment_checked = $('input:radio[name="o_payment"]:checked').val();
                $("#o_payment6").parent().parent().remove();
                var showHtml='<p><label><input type="radio" value="6" name="o_payment" id="o_payment" ';
                if(o_payment_checked=='6'){
                    showHtml +=' checked="checked" ';
                }
                showHtml +='>';
                showHtml +='<input type="hidden" value="'+pay_name+'" id="o_payment6" name="o_payment6">'+pay_name+'</p></label>';
                $("#payment_list").append(showHtml);
            }else{
                var showHtml=$("#o_payment6").parent().html();
                if(showHtml!=''){
                    $("#o_payment6").parent().parent().remove();
                    $('input:radio[name="o_payment"]')[0].checked=true;
                }
            }

            //+++++++++++++++++更新订单总金额++++++++++++++++++
            var all_orders_price = (parseFloat(jsonData.all_price)).toFixed(2);
            var points = jsonData.points;
            var bonus_price = (parseFloat(jsonData.bonus_price)).toFixed(2);
            var cards_price = (parseFloat(jsonData.cards_price)).toFixed(2);
            var jlb_price = (parseFloat(jsonData.jlb_price)).toFixed(2);
            var point_price = (parseFloat(jsonData.point_price)).toFixed(2);
            var coupon_price = (parseFloat(jsonData.coupon_price)).toFixed(2);
            if(all_orders_price < 0){
                all_orders_price = '0.00';
            }
            $("#points_label").html(points+'分');
            $("#coupon_label").html('￥'+coupon_price);
            //$("#promotion_price").html(coupon_price);
            $("#bonus_label").html(bonus_price);
            $("#cards_label").html(cards_price);
            $("#jlb_label").html(jlb_price);
            $("#point_label").html(point_price);
            $("#all_orders_price").html('￥'+all_orders_price);
            if(jsonData.reward_point){
                $('#points_reward').html(jsonData.reward_point);
                $('#points_rewards').html(jsonData.reward_point);
            }
        }
    },'json');
}
function checkBulkLogistic(obj){
    var url = '/Wap/Orders/checkBulkLogistic/';
    $.post(url,{'lt_id':obj},function(jsonData){
        if(jsonData.status){
            var logistic_money = (parseFloat(jsonData.logistic_price)).toFixed(2);
            var promotion_price = (parseFloat(jsonData.promotion_price)).toFixed(2);
            var gp_price = (parseFloat(jsonData.gp_price)).toFixed(2);
            var logistic_delivery=jsonData.logistic_delivery;
            $("#o_cost_freight").val(logistic_money);
            $("#bulk_price").html('<i class="price" >￥</i>'+gp_price);
            $("#logistic_price").html('<i class="price" >￥</i>'+logistic_money);
            var all_orders_price = (parseFloat(jsonData.all_price)+parseFloat(logistic_money)-parseFloat(promotion_price)).toFixed(2);
            if(all_orders_price < 0 ){
                all_orders_price = '0.00';
            }
            $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
            $("#o_payment6").parent().remove();
        }           
    },'json');
};
function checkSpikeLogistic(obj){
    var url = '/Wap/Orders/checkSpikeLogistic/';
    $.post(url,{'lt_id':obj},function(jsonData){
        if(jsonData.status){
            var logistic_money = (parseFloat(jsonData.logistic_price)).toFixed(2);
            var promotion_price = (parseFloat(jsonData.promotion_price)).toFixed(2);
            var sp_price = (parseFloat(jsonData.sp_price)).toFixed(2);
            var coupon_price = 0;
            var logistic_delivery=jsonData.logistic_delivery;
            $("#o_cost_freight").val(logistic_money);
            $("#bulk_price").html('<i class="price" >￥</i>'+sp_price);
            $("#logistic_price").html('<i class="price" >￥</i>'+logistic_money);
          //  $("#promotion_price").html('<i class="price" >￥</i>'+promotion_price);
           // $("#all_orders_promotion_price").attr('value',parseFloat(jsonData.promotion_price));
            var all_orders_price = (parseFloat(jsonData.all_price)+parseFloat(logistic_money)-parseFloat(promotion_price)).toFixed(2);
            if(all_orders_price < 0 ){
                all_orders_price = '0.00';
            }
            $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
          //  $("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
          //  if(logistic_delivery==true ){
               // $("#o_payment6").parent().remove();
               // var showHtml='<dd><input type="radio" value="6" name="o_payment" id="o_payment" onclick="payradio($(this))" validate="{ required:true}"> ';
                 //   showHtml +='<input type="hidden" value="'+pay_name+'" id="o_payment6" name="o_payment6"> ';
                 //   showHtml +='<label for="zhifu">'+pay_name+'</label> <span>'+pay_name+'</span></dd>';  
               // $("#payment_list").append(showHtml);
           // }else{
               // var showHtml=$("#o_payment6").parent().html();
               // if(showHtml!=''){
                    $("#o_payment6").parent().remove();
               // }
           // }
        }           
    },'json');
};

//保存发票信息
function save_invoice(){
	var is_invoice = $("#is_invoice").val();
	if(is_invoice == 1){
		if($("[name='invoice_type']:checked").length){
			var invoice_str = '';
			var invoice_type = $("[name='invoice_type']:checked").val();
			if(invoice_type == '0'){  //不需要发票
				invoice_str += "<p>不需要发票</p>";
                $("#invoices_val").val(0);//不需要发票
			}else if(invoice_type == '1'){ //普通发票
                $("#invoices_val").val(1);//需要发票
				var invoice_head = $("#pt_fapiao [name='invoice_head']:checked").val();
				if(invoice_head == 1){
					var invoice_head_str = "个人";
					invoice_str += "<p><span>发票抬头："+invoice_head_str+"</span></p>";
					var invoice_people = $("#pt_fapiao [name='invoice_people']").val();
					if(!invoice_people){
						$.ThinkBox.error("请填写个人姓名");
						return;
					}
					invoice_str += "<p><span>个人姓名："+invoice_people+"</span></p>";
				}else{
					var invoice_head_str = "公司";
					invoice_str += "<p><span>发票抬头："+invoice_head_str+"</span></p>";
					var invoice_name = $("#pt_fapiao [name='invoice_name']").val();
					if(!invoice_name){
						$.ThinkBox.error("请填写单位名称");
						return;
					}
					invoice_str += "<p><span>单位名称："+invoice_name+"</span></p>";
				}
				var invoice_content = $("#pt_fapiao [name='invoice_content']:checked").val();
				if(invoice_content !== undefined){
					invoice_str += "<p><span>发票内容："+invoice_content+"</span></p>";
				}
			}else if(invoice_type == '2'){ //增值税发票
                $("#invoices_val").val(1);//需要发票
				var invoice_name = $("input[name='add_invoice_name']").val();
				if(!invoice_name){
					$.ThinkBox.error("请填写单位名称");
					return;
				}
				invoice_str += "<p><span>单位名称："+invoice_name+"</span></p>";
				var invoice_identification_number = $("#zzs_fapiao [name='invoice_identification_number']").val();
				if(!invoice_identification_number){
					$.ThinkBox.error("请填写纳税人识别号");
					return;
				}
				invoice_str += "<p><span>纳税人识别号："+invoice_identification_number+"</span></p>";
				var invoice_address = $("#zzs_fapiao [name='invoice_address']").val();
				if(!invoice_address){
					$.ThinkBox.error("请填写注册地址");
					return;
				}
				invoice_str += "<p><span>注册地址："+invoice_address+"</span></p>";
				var invoice_phone = $("#zzs_fapiao [name='invoice_phone']").val();
				if(!invoice_phone){
					$.ThinkBox.error("请填写注册电话");
					return;
				}
				invoice_str += "<p><span>注册电话："+invoice_phone+"</span></p>";
				var invoice_bank = $("#zzs_fapiao [name='invoice_bank']").val();
				if(!invoice_bank){
					$.ThinkBox.error("请填写开户银行");
					return;
				}
				invoice_str += "<p><span>开户银行："+invoice_bank+"</span></p>";
				var invoice_account = $("#zzs_fapiao [name='invoice_account']").val();
				if(!invoice_account){
					$.ThinkBox.error("请填写银行帐户");
					return;
				}
				invoice_str += "<p><span>银行帐户："+invoice_account+"</span></p>";
				var invoice_content = $("#zzs_fapiao [name='invoice_content']:checked").val();
				
				if(invoice_content !== undefined){
					invoice_str += "<p><span>发票内容："+invoice_content+"</span></p>";
				}
			}
			$("#fp_input_list").hide();
			$("#fp_info_preview").show().find(".invoice_show").html(invoice_str);
		}else{
			$.ThinkBox.error("请选择发票类型");
		}
    }
}

//编辑发票信息
function edit_invoice(){
    $("#fp_input_list").show();
    $("#fp_info_preview").hide();
}
    
function submitOrders(){
    var url = '/Wap/Orders/doAdd';
	var is_invoice = $("#is_invoice").val();
    var lt_id = $(":radio[name=lt_id][checked]").val();
    if ( $("#is_zt").length > 0 ) {//自提
		var logistic_type_val = $(' input[name="logistic_type"]:checked ').val();
		if(logistic_type_val =='2'){
			var lt_id = $('#zt_logistic').val();
		}
	}
    if(lt_id==undefined){
        $.ThinkBox.error("您需先保存配送方式及支付方式，再提交订单");
        return;
    }
    if(!($(':checked[name="o_payment"]').length)){
        $.ThinkBox.error("请选择支付方式");
        return;
    }
	if(is_invoice == 1){
		if(!($(':checked[name="invoice_type"]').length)){
			$.ThinkBox.error("请选择发票类型");
			return;
		}
    }
	if ( $("#is_zt").length > 0 ) {
		var  is_zt = $("#is_zt").attr("value");
		if(is_zt =='1'){
			var o_receiver_time = $("#o_receiver_time" ).attr("value");
			if(o_receiver_time ==''){
				$.ThinkBox.error("请选择自提时间");
				return;
			}
		}
	}
    if(checkOrders()){
        $("#addOrder").attr('action',url);
        $("#addOrder").submit();
    }
}
function checkOrders(){
    var invoice_check = $("#fp_input_list").css('display');
    if(invoice_check == 'block'){
        $.ThinkBox.error("请保存发票信息");
        return false;
    }
    return true;
}

function myCouponShow(){
//    $("#my_coupon").html();
    var box = $.ThinkBox.alert($("#my_coupon").html(),{"title":"我的优惠券"});
    $(".ThinkBox-wrapper .ok").click(function(){
        box.hide(true);
    });
    $("#my_coupon_ul li").click(function(){
        box.hide(true);
    });
    return false;
}
/*
function activeCoupin(){
	var url = '/Wap/Orders/activeCoupon';
	var num = $('#coupon_num').val();
	$.post(url,{'csn':num},function(data){
            if(data.success == 1){
                var csn = data.data.c_sn;
                var c_id = data.data.c_id;
                doCoupin(csn,c_id);
                return false;
            }else {
                $.ThinkBox.error(data.errMsg);
                return false;
            }
	},'json');
  }*/
    //使用促销
    function doPromotions(type ,is_auto){
		var url = '/Wap/Orders/doPromotions';
        var bonus = $("#bonus_input").val();
        var cards = $("#cards_input").val();
        var jlb = $("#jlb_input").val();
        var point = $("#point_input").val();
        var csn = $("#coupon_num").val();
        var bonus_t = $("#bonus_total").text();
        var cards_t = $("#cards_total").text();
        var jlb_t = $("#jlb_total").text();
        var point_t = $("#point_total").text();
        var lt_id = $(":radio[name=lt_id][checked]").val();
        var pids = $("#goods_pids").val();
        var is_low_consumed = $("#is_low_consumed").val();
        var low_consumed_points = $("#low_consumed_points").val();
		var gids = new Array();
		//alert(point);
		$("input[name='gid']").each(function(i){
			gids[i] = $(this).val();
		});
		if(gids == ''){
			$.ThinkBox.error("请检查商品是否存在");
			return false;
		}
        var label_str = 'coupon';
        //if(csn == ''){
			//$.ThinkBox.error("优惠券不能为空");
        //    return false;
        //}
		var point_num = point/low_consumed_points;
		//是否为正整数
		var re = /^[0-9]*[1-9][0-9]*$/ ;
		var is_num = re.test(point_num);
        if(type == '4'){//使用积分
            if(is_low_consumed == 1){
                if(!is_num){
                    alert('积分请输入大于等于'+low_consumed_points+'的整数'+'，且为'+low_consumed_points+'的整数倍');
                    $("#point_input").val(0);
                    return false;
                }
            }
        }

        $.post(url,{'csn':csn,'bonus':bonus,'cards':cards,'jlb':jlb,'point':point,'lt_id':lt_id,'pids':pids,'type':type,'gid':gids},function(data){
            if(data.success == 1){
                //$('#submit_order').attr({disabled:false}).attr("value","提交订单");
                $("#msg"+type).css({'display':''});
                $("#msg"+type).html('');
                $("#msg"+type).html(data.sucMsg);
                $("#msg"+type).css({'color':'#006000'});
                var all_orders_price = (parseFloat(data.all_price)).toFixed(2);
                var points = data.points;
                var bonus_price = (parseFloat(data.bonus_price)).toFixed(2);
                var cards_price = (parseFloat(data.cards_price)).toFixed(2);
                var jlb_price = (parseFloat(data.jlb_price)).toFixed(2);
                var point_price = (parseFloat(data.point_price)).toFixed(2);
                var coupon_price = (parseFloat(data.coupon_price)).toFixed(2);
                if(all_orders_price < 0){
                    all_orders_price = '0.00';
                }
                $("#points_label").html(points+'分');
				$("#coupon_label").html('￥'+coupon_price);
                //$("#promotion_price").html(coupon_price);
                $("#bonus_label").html(bonus_price);
                $("#cards_label").html(cards_price);
                $("#jlb_label").html(jlb_price);
                $("#point_label").html(point_price);
                $("#all_orders_price").html('￥'+all_orders_price);
				if(data.reward_point){
					$('#points_reward').html(data.reward_point);
                    $('#points_rewards').html(data.reward_point);
				}
            }else {
                var all_orders_price = (parseFloat(data.all_price)).toFixed(2);
                $("#msg"+type).css({'display':'none'});
                $("#"+label_str+"_label").html(0.00);
                if(all_orders_price < 0){
                    all_orders_price = '0.00';
                }
                $("#all_orders_price").html(all_orders_price);
                //$('#submit_order').attr({disabled:true}).attr("value","抵扣金额超过商品金额");
                $('#jlb_input').val(0);
                $('#point_input').val(0);
                //$('#coupon_input').val();
                $('#bonus_input').val(0);
                $('#cards_input').val(0);
                if(is_auto != 1){
                    $.ThinkBox.error(data.errMsg);
                }
				$("input[name='coupon_input']").val('');
                return false;
            }
        },'json');
    }
  
  //照此单下单
function againOrdres(){
    var data = $("#orders_goods_form").serialize();
    ajaxReturn('/Wap/Cart/doBulkAdd',data,'post');
    return;
    $("#orders_goods_form").attr("action",'/Wap/Cart/doAdd').submit();
}
//设为默认收货地址
function saveAsDefault(domEl){
    if($(domEl).hasClass('moren')){
        return;
    }
    var ra_id = $(domEl).attr('ra_id');
    var url = "/Wap/ReceiveAddress/saveAsDefault";
    $.get(url,'ra_id='+ra_id,function(jsonData){
        if(jsonData.status == 1){
            $.ThinkBox.success("设置默认地址成功");
            $(".moren").removeClass("moren").html("设为默认");
            $(domEl).addClass("moren").html("默认地址");
        }else{
            $.ThinkBox.error(jsonData.data.info);
        }
    },"json");
}

//订单支付
function paymentOrders(i){
    $('#hideButton').css("display", "none");
    var p_id = $('[name="o_payment"]:checked').val();
    var p_name = $('#o_payment'+p_id).val();
    if(p_name == '线下支付' ){
            var o_id = $('#oid').val();
            var url = '/Wap/Orders/setPage';
            $.post(url,{'o_id':o_id,'p_id':p_id},function(html){
                $('#children').dialog({
                                height:385,
                                width:340,
                                resizable:false,
                                autoOpen: false,
                                modal: true,
                                buttons: { 
                                    '确定':function(){
                                    addVoucher(o_id,$( this ));
                                },
                                    '取消': function() {
                                        $( this ).dialog( "close" );
                                        $('#children').hide();
                                    }
                                }
                            });
                             $('#children').dialog('open');
                             $('#children').html(html);
               },'html');

    }
    else{
        $("#typeStat").val(i);
        var url = '/Wap/Ucenter/paymentPage';
        var res = $('#successorderForm').valid();
        if(res){
            //发送ajax请求
            $("#successorderForm").submit();
        }  		
    }
}

/*********************************
 * add by zhangjiasuo
 * date 2015-09-12
 * 门店自提
 *********************************/ 
function logistic_display(){
	if ( $("#is_zt").length > 0 ) {
		var logistic_type_val = $(' input[name="logistic_type"]:checked ').val();
		var zt_logistic_val = $('#zt_logistic').val();
		$("#logistic_type_title").html("快递方式");
		if(logistic_type_val =='2'){
			$("#is_zt_display").css({'display':'none'});
			$("#logistic_display").css({'display':'none'});
			$("#is_zt").attr('value',1);
			//新增收货地址选项
			$("#region_display").css({'display':'none'});
			$("#detail_display").css({'display':'none'});
			$("#phone_display").css({'display':'none'});
			$("#zipcode_display").css({'display':'none'});
			$("#shop_type_display").css({'display':'none'});
			$("#address_consignee_display").css({'display':'none'});
			
			//自提 收货地址的url
			var zt_url = $('#zt_url').attr("href");
			var tmp_zt_url = zt_url + '/zt/'+1;
			$('#zt_url').attr('href',tmp_zt_url); 
			
			//默认选中自提配送方式
			$("#lt_id"+zt_logistic_val).attr("checked",true);
			checkLogistic(zt_logistic_val);
			
		}else{
			$("#is_zt_display").css({'display':''});
			$("#logistic_display").css({'display':''});
			$("#is_zt").attr('value',0);
			//新增收货地址选项
			$("#region_display").css({'display':''});
			$("#detail_display").css({'display':''});
			$("#phone_display").css({'display':''});
			$("#zipcode_display").css({'display':''});
			$("#shop_type_display").css({'display':''});
			$("#address_consignee_display").css({'display':''});
			
			//默认不选中自提配送方式
			$("#lt_id"+zt_logistic_val).attr("checked",false);
			$("#logistic"+zt_logistic_val).css({'display':'none'});
			
			//自提 收货地址的url
			if ( $("#zt_url").length > 0 ) {
				var zt_url = $('#zt_url').attr("href");
				var tmp_zt_url  = zt_url.replace('/zt/1', " ");
				$('#zt_url').attr('href',tmp_zt_url); 
			}
		}
	}
}
