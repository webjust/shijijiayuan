$(document).ready(function(){
    $.metadata.setType("attr","validate");

    //添加常用收货地址
    $("#addCommonlyAddr").click(function(){
        //$(this).fadeOut('slow'); 
		$(".closedD").css({'display':''});
        $("#updateAddress").css({'display':'block'});
        var HD = $(this).attr('HD');
        var url = '/Ucenter/Address/addAddrPage/';
        $.post(url,{HD:HD},function(data){
            $('#updateAddress').html(data);
        });
    });
    $(function(){
        $(".blue").hover(function(){
            $(this).css({
                "color":"white",
                "background-color":"#0F89C4",
                "text-decoration":"none"
            })
        },function(){
            $(this).css({
                "color":"white",
                "background-color":"#FF3E79"
            })
        })
    
        $(".brown").hover(function(){
            $(this).css({
                "color":"white",
                "background-color":"#EF8D22",
                "text-decoration":"none"
            })
        },function(){
            $(this).css({
                "color":"white",
                "background-color":"#ff9b2e"
            })
        })
    })
    //订单价格
    var  int_all_price= $("#goods_all_price").val();
    if(int_all_price==''){
        int_all_price=0;
    }
    //如果无促销，促销金额写为0，否则页面显示NaN
    var  all_orders_promotion_price= $("#all_orders_promotion_price").val();
    if(all_orders_promotion_price==''){
        all_orders_promotion_price=0;
    }
   //刷新时 保持运费不为零
   var s2 = $("input[name='lt_id']:checked").val();

   var logistic_money = 0;
  
   if(s2 !=null ){
        logistic_money = (parseFloat($("#logistic_price_"+s2).html())).toFixed(2);
   }
    var all_orders_price = (parseFloat(int_all_price)).toFixed(2);  
    
    $("#logistic_price").html('<i class="price" >￥</i>'+logistic_money);
    var total_price = parseFloat(all_orders_price)+parseFloat(logistic_money);
    if(total_price < 0 ){
        total_price = '0.00';
    }
    $("#all_orders_price").html('<strong><i class="price">￥</i>'+(total_price).toFixed(2));
    $("input[name='o_payment']").click(function(){
        var pay_id = $(this).val();
        var p_name = $("#o_payment"+pay_id).val();
        $("#payment_name").show().html('付款方式：'+p_name);
        $("#payment_list").hide();
        $("#upA").attr('status','open').html('选取其他支付方式');
    });

    $("#coupon_input").blur(function(){
        var csn = $(this).val();
        if(csn !== '' && csn !== undefined && csn !== null){
            doPromotions(0);
        }
    });
    $("#bonus_input").blur(function(){
        var bonus = $(this).val();
        if(bonus !== '' && bonus !== undefined && bonus !== null){
            doPromotions(1);
        }
    });
    $("#cards_input").blur(function(){
        var cards = $(this).val();
        if(cards !== '' && cards !== undefined && cards !== null){
            doPromotions(2);
        }
    });
    $("#jlb_input").blur(function(){
        var jlb = $(this).val();
        if(jlb !== '' && jlb !== undefined && jlb !== null){
            doPromotions(3);
        }
    });
    $("#point_input").blur(function(){
        var point = $(this).val();
        if(point !== '' && point !== undefined && point !== null){
            doPromotions(4);
        }
    });
});
//选择普通订单收货地址
function checkAddr(obj){
    if (confirm("更换地址后，您需要重新确认订单信息")){
            $("#updateAddress").css({'display':'none'});
            $("input[name='ra_id']").removeAttr('checked');
            if(obj.attr('name') != 'ra_id'){
                obj.prev().attr('checked','checked');
            }else{
                obj.attr('checked','checked');
            }
            var ra_id = obj.attr('ra_id');
            var cr_id = obj.attr('cr_id');
            var url = '/Ucenter/Orders/getLogisticType/';
            $.post(url,{
                'ra_id':ra_id,
                'cr_id':cr_id,
                'pids':$("#goods_pids").val()
            },function(jsonObj){
                $("#logistic_dl").html(jsonObj);
            },'text');
    }
}

//选择团购订单收货地址
function checkBulkAddr(obj,urlObj){
    if (confirm("更换地址后，您需要重新确认订单信息")){
            $("#updateAddress").css({'display':'none'});
            $("input[name='ra_id']").removeAttr('checked');
            if(obj.attr('name') != 'ra_id'){
                obj.prev().attr('checked','checked');
            }else{
                obj.attr('checked','checked');
            }
            var ra_id = obj.attr('ra_id');
            var cr_id = obj.attr('cr_id');
            var url = '/Ucenter/Orders/'+urlObj+'/';
            $.post(url,{
                'ra_id':ra_id,
                'cr_id':cr_id
            },function(jsonObj){
                
                $("#logistic_dl").html(jsonObj);
            },'text');
    }
}


//选择收货地址
function getLogisticType(obj){
    var cr_id = $(obj).val();
	if(!cr_id){
        cr_id = obj;
    }
    var web_type = $('#web_type').val();//确定这个是哪张确认页面，已确定加载那个配送子页面
    if(typeof web_type != 'undefined' && web_type != '' && web_type){
	   if(web_type == 'Trdorders'){
           url = '/Ucenter/Trdorders/getLogisticType/';
	   }
	   else{
	       url = '/Ucenter/Orders/get'+web_type+'LogisticType/';
	   }
    }
    else url = '/Ucenter/Orders/getLogisticType/';
    $.post(url,{
        'cr_id':cr_id,
        'pids':$("#goods_pids").val()
    },function(jsonObj){
        $("#logistic_dl").html(jsonObj);
    },'text');
}

//省市联动
function selectCityRegion(obj, item, default_value) {
    var value = obj.value;
    if(!value){
        value = obj;
    }
    var url = '/Ucenter/Address/getCityRegion/';
    $('#'+item).load(url, {
        'parent': value,
        'default_value':default_value
    }, function(){
        if('' != default_value) {
            this.value = default_value;
        }
    });
}
/********************************************************************/
//淘宝拍拍贴收货地址省市联动
function selectCityRegion_new(value, item, city_value,region_value) {
    
    if(typeof value == 'undefined' || value == ''){
        return false;
    }
    var url = '/Ucenter/Address/getCityRegion/';
	/**************************************************/
	$.ajax({
            url:url,
            dataType:"html",
            type:"post",
			async:false,
            data:{
                'parent': value
            },
            success:function(htm){
			    $('#'+item).html(htm);
                $("#city option").each(function(){
				    var _this_city = $(this);
                    if(_this_city.text() == city_value){
                        _this_city.attr("selected",true);
						var city_init_value = _this_city.val();
                        selectRegion(city_init_value, 'region',region_value);
                      
                    }
                });
              
            }
        });
	
}

 //获取所有的地区列表
function selectRegion(value, item, region_value) {
           
             if(typeof value == 'undefined' || value == ''){
                 return false;
            }
         // var _this = $(this);
          //_this.ShowMask();
		  var url = '/Ucenter/Address/getCityRegion/';
          $.ajax({
            url:url,
            async: false,
            dataType:'html',
            type:'post',
            cache:false,
            data:{
                'parent':value
            },
            success:function(htm){
                
                $('#'+item).html(htm);
				$("#region option").each(function(){
				    var _this_region = $(this);
                    if(_this_region.text() == region_value){
                        _this_region.attr("selected",true);
                        var region_init_value = _this_region.val();
                       	getLogisticType(region_init_value);
                    }
                });
			}
          });
            // _this.HideMask();
          
 }


 //获取所有的市列表
function selectCity(obj, item, default_value,city,rregion) {
            var value = obj.value;
            if(!value){
                value = obj;
            }
            var url = '{!$WEBENTRY!}/ucenter/setting/?act=getCityRegion';
            $('#'+item).load(url, {'parent': value}, function(){
                if('' != default_value) {
                    this.value = default_value;
                }
                $("#rcity option").each(function(){
                    if($(this).text() == city){
                        $(this).attr("selected",true);
                        selectRegion(this, 'rregion','',rregion);
                    }
                });
                 // alert(15);
                if($("#rregion").val() >0 ){
                    
                    var add	= $("#rregion");
                }else if($("#rcity").val() >0){
                  
                    var add	= $("#rcity");
                }else if($("#rprovince").val() >0){
                    
                    var add	= $("#rprovince");
                }else{
                    var add	= 0;
                }
                //alert(add);
                if(add != 0){
                    changeLogisticsList(add);
                }
            });
 }
 

/********************************************************************/

function initSelectCityRegion() {
    $('#city').html('<option value="0">请选择</option>');
    $('#region').html('<option value="0">请选择</option>');
}

//更新常用收货地址
function submitFrom(obj,ra_id){
    var res = $('#'+obj).valid();
    var ra_phone = $('#ra_phone').val();
    var ra_mobile_phone = $('#ra_mobile_phone').val();
    if(ra_phone=='' && ra_mobile_phone=='' ){
        var msg='<label class="error" for="ra_phone" generated="true">两者至少写一项</label>';
        $('.gray').html(msg);
        return false;
    }
    if(res){
        var data = $('#'+obj).serialize();
        var url = "/Ucenter/Address/ajaxUpdateAddr/";
        $.post(url,data,function(jsonData){
            if(jsonData.status){
                $('#'+obj).fadeOut('slow'); 
                var showHtml = jsonData.data.ra_name+",";
                showHtml += jsonData.data.address+",";
                showHtml += jsonData.data.ra_detail+",";
                showHtml += jsonData.data.ra_post_code+",";
                showHtml += jsonData.data.ra_phone+",";
                showHtml += jsonData.data.ra_mobile_phone;
                $(".checkAddr[value='"+jsonData.data.ra_id+"']").attr('cr_id',jsonData.data.cr_id);
                $('.checkAddr').each(function(i){
                    if($(this).attr('ra_id') == ra_id && $(this).html()!=''){
                        $(this).html(showHtml);
						var goods_pid = $('#goods_pids').val();
						if(goods_pid == 'bulk'){
							checkBulkAddr($(".checkAddr[value='"+jsonData.data.ra_id+"']"),'getBulkLogisticType');
						}else{
							if(goods_pid == 'spike'){
								checkBulkAddr($(".checkAddr[value='"+jsonData.data.ra_id+"']"),'getSpikeLogisticType');
							}else{
								if(goods_pid == 'presale'){
									checkBulkAddr($(".checkAddr[value='"+jsonData.data.ra_id+"']"),'getPresaleLogisticType');
								}else{
									checkAddr($(".checkAddr[value='"+jsonData.data.ra_id+"']"));
								}
							}
						}
                        return false;
                    }  
                });
            }
        },'json');
    }
}
//收获地址插入
function addersshtml(data,HD,add_type){

    var showifno = data.ra_name+",";
        showifno += data.address+",";
        showifno += data.ra_detail+",";
        showifno += data.ra_post_code+",";
        showifno += data.ra_phone+",";
        showifno += data.ra_mobile_phone;
                    
    var showHtml = '<dd id="row'+data.ra_id+'">';
        if(HD == 'Bulk'){
            showHtml += '<input type="radio" ra_id="'+data.ra_id+'" cr_id="'+data.cr_id+'" name="ra_id" value="'+data.ra_id+'" id="ra_id" onclick="javascript:checkBulkAddr($(this),'+"'getBulkLogisticType'"+');" class="checkAddr" ';
			if(add_type == 1){
			showHtml += ' checked';
			}
			showHtml += '>';
        }else if(HD == 'Spike'){
            showHtml += '<input type="radio" ra_id="'+data.ra_id+'" cr_id="'+data.cr_id+'" name="ra_id" value="'+data.ra_id+'" id="ra_id" onclick="javascript:checkBulkAddr($(this),'+"'getSpikeLogisticType'"+');" class="checkAddr"'; 
			if(add_type == 1){
			showHtml += ' checked';
			}
			showHtml += '>';
        }else if(HD == 'Presale'){
            showHtml += '<input type="radio" ra_id="'+data.ra_id+'" cr_id="'+data.cr_id+'" name="ra_id" value="'+data.ra_id+'" id="ra_id" onclick="javascript:checkBulkAddr($(this),'+"'getPresaleLogisticType'"+');" class="checkAddr" ';
			if(add_type == 1){
			showHtml += ' checked';
			}
			showHtml += '>';
        }else{
            showHtml += '<input type="radio" ra_id="'+data.ra_id+'" cr_id="'+data.cr_id+'" name="ra_id" value="'+data.ra_id+'" id="ra_id" onclick="javascript:checkAddr($(this));" class="checkAddr" ';
			if(add_type == 1){
			showHtml += ' checked';
			}
			showHtml += '>';
        }
        
        showHtml += '<label for="addr" ra_id="'+data.ra_id+'" class="checkAddr">'+showifno+'</label> ';
        showHtml += '<a class="update updateAddress" ra_id="'+data.ra_id+'" onclick="clickReceive($(this));" href="javascript:void(0);">修改</a><i>|</i>';
        showHtml += '<a onclick="addressDelete($(this));" class="del" href="javascript:void(0);"  id="'+data.ra_id+'">删除</a></dd>';
    return showHtml;
}
//新增收货地址
function submitAddress(obj,HD){
    var res = $('#'+obj).valid();
    var ra_phone = $('#ra_phone').val();
    var ra_mobile_phone = $('#ra_mobile_phone').val();
    if(ra_phone=='' && ra_mobile_phone=='' ){
        var msg='<label class="error" for="ra_phone" generated="true">两者至少写一项</label>';
        $('.gray').html(msg);
        return false;
    }
    if(res){
        var data = $('#'+obj).serialize();
        var url = "/Ucenter/Orders/getAddressPage/";
        $.post(url,data,function(jsonData){
            if(jsonData.status){
                $('#fromAddress').fadeOut('slow'); 
                var html=addersshtml(jsonData.data,HD,1);
                if(jsonData.num==1){
                    $('#first_addr_list').append(html);
                }else{
                    $('#more_addr_list').append(html);
                }
                if(HD == 'Bulk'){
                    checkBulkAddr($("input[ra_id='"+jsonData.data.ra_id+"']"),'getBulkLogisticType');
                }else if(HD == 'Spike'){
                    checkBulkAddr($("input[ra_id='"+jsonData.data.ra_id+"']"),'getSpikeLogisticType');
                }else if(HD == 'Presale'){
                    checkBulkAddr($("input[ra_id='"+jsonData.data.ra_id+"']"),'getPresaleLogisticType');
                }
                
            }
        },'json');
    }
}
//修改收货地址
function clickReceive(obj){
    var ra_id = obj.attr('ra_id');
    var url = '/Ucenter/Address/updateAddrPage/';
    $.post(url,{'ra_id':ra_id},function(msg){
        $("#updateAddress").css({'display':'block'});
        $('#updateAddress').html(msg);
    });
}


//删除常用收获地址
function addressDelete(obj){
    var url = '/Ucenter/Orders/getAddressPage/';
    var ra_id= obj.parent().find('input').attr('value');
    var place_id= obj.parent().parent().parent().find('dl').attr('id');
    if (confirm("确定要删除此地址？")){
        $.post(url,{'ra_id':ra_id,'del':'del'},function(jsonData){
                if(jsonData.status){
                    $("#row"+ra_id).remove(); 
                    if(place_id=='first_addr_list' && jsonData!=''){
                        var htmls=addersshtml(jsonData.data);                     
                        $('#first_addr_list').html(htmls);   
                        var rowCount = $("#more_addr_list dd:first").remove();  
                    }
                }           
        },'json');
    }
}
//订单提交check
function checkOrders(){
    var addr_id = $(":radio[name=ra_id][checked]").val();
    if(addr_id=='other'){
        var res = $('#fromAddress').valid();
        if(res==false){
            return false;
        }
    }
	//验证是否保存收货人信息
    
    var ra_id_radio = $(":radio[name=ra_id]:checked");
    var raid = ra_id_radio.val();
	var sat = ra_id_radio.attr("status");
	if(1 == sat && raid != 'other'){
		showTips(false,'系统提示','您需先保存收货人信息，再提交订单');
		location.hash="addrDetail";
		return false;
	}else{
		if(raid == undefined){
			showTips(false,'系统提示','请先选择收获地址再提交订单');
			location.hash="addrDetail";
			return false;		
		}
	}
    
    var lt_id = $(":radio[name=lt_id][checked]").val();
    
    if(lt_id==undefined){
		location.hash="psDetail";
        showTips(false,'系统提示','您需先保存配送方式及支付方式，再提交订单');
        return false;
    }
    var csn = $("#coupon_input").val();
    if(csn !== '' && csn !== undefined && csn !== null){
        doPromotions(0);
    }
    var bonus = $("#bonus_input").val();
    if(bonus !== '' && bonus !== undefined && bonus !== null){
        doPromotions(1);
    }
    var cards = $("#cards_input").val();
    if(cards !== '' && cards !== undefined && cards !== null){
        doPromotions(2);
    }
    var jlb = $("#jlb_input").val();
    if(jlb !== '' && jlb !== undefined && jlb !== null){
        doPromotions(3);
    }
    var point = $("#point_input").val();
    if(point !== '' && point !== undefined && point !== null){
        doPromotions(4);
    }
    return true;
}
//提交订单
function submitOrders(){
   //alert($("#invoice_name").val());return false;
    var url = '/Ucenter/Orders/doAdd';
    var status=checkOrders();
	var res = $('#orderForm').valid();
    if(status){
      if(res){
            //当前发票的类型
            var invoice_type = $("input[name='invoice_type']:checked").val();
            if(invoice_type == 1){
                $("#zengzhishui").remove();
            }else{
                $("#peopleInvoice").remove();
            }
            if($("#invoices_vals").val() ==1){
                $("#invoices_val").remove();
            }
            //发送ajax请求
			
            var data = $('#orderForm').serialize();
			//临时收货地址表单数据拼接
			var ra_id_radio = $(":radio[name=ra_id]:checked");
			var raid = ra_id_radio.val();
			if(raid == 'other'){
			   
			   var data_form = '&'+$('#fromAddress').serialize();
			   data += data_form;
			}
			var IS_CONFIRM_ORDER = ($('#IS_CONFIRM_ORDER').val() == undefined)?0:$('#IS_CONFIRM_ORDER').val();
			if(IS_CONFIRM_ORDER == 1){
				var isGetOldOrder = 0;
				$.ajax({
					url:'/Ucenter/Orders/isGetOldOrder',
					data:data,
					type:'POST',
					dataType:'json',
					success:function(result){
						//showAlert(result.status,result.info,'',result.url);
						if(result.status == 1) {
							if(confirm('三天内有收货人重名订单，确认提交订单?')){
								orderAdd(url,data);
							}
						}else{
							orderAdd(url,data);
						}
					}
				});		
			}else{
				orderAdd(url,data);
			}
        }  
    }
}
//提交订单
function orderAdd(url,data){
	//showTips(false,'系统提示','正在提交订单，请稍候！');
	$('#submit_order').attr({disabled:true}).attr("value","正在提交订单，请稍候！");
	//ajaxReturn(url,data,'post');
	$.ajax({
		url:url,
		data:data,
		type:'POST',
		dataType:'json',
		success:function(result){
			//showAlert(result.status,result.info,'',result.url);
			if(result.status) {
				$('#submit_order').hide();
				location.href=result.url;
			} else {
				showAlert(result.status,result.info,'',result.url);
				$('#submit_order').attr({disabled:false}).attr("value","提交订单");
			}
		},
		error:function(){
			//alert('请求无响应或超时');
			showTips(false,'系统提示','订单提交失败！');
			$('#submit_order').attr({disabled:false}).attr("value","提交订单");
		}
		
	});
}
//照此单下单
function againOrdres(){
    var data = $("#orders_goods_form").serialize();
    ajaxReturn('/Ucenter/Cart/doAdd',data,'post');
}
//作废订单
/*function invalidOrder(){
    var oid = $("#invalid").attr('o_id');
    var url = '/Ucenter/Orders/ajaxInvalidOrder';
    ajaxReturn(url,{
        'oid':oid
    },'post');
}*/
//付款
function payment(){
    alert(1);
}

function changeLogisticsList(obj){
    var address_id	= $(obj).val();
    var goods_info = {};
    goods_info['pdt_num'] = {};
    goods_info['pdt_weight'] = {};
    var tt_id = $("#goods_all_price").attr("tt_id");
    var pdt_num =$("#goods_all_price").attr("pdt_num");
    var pdt_price =$("#goods_all_price").attr("pdt_price");
    var pdt_weight = $("#goods_all_price").attr("pdt_weight");
    goods_info['pdt_num'] = pdt_num;
    goods_info['pdt_price'] = pdt_price;
    goods_info['pdt_weight'] = pdt_weight;
    var url = '/Ucenter/Trdorders/getAvailableLogisticsList';
    $.ajax({
        url:url,
        cache:false,
        dataType:'TEXT',
        type:'POST',
        data:{
            'address_id': address_id,
            'goods_info':goods_info,
            'tt_id':tt_id
        },
        beforeSend:function(){
            $("#ajax_loading").dialog({
                height:150,
                width:315,
                modal:true,
                title:'提示：努力加载中',
                closeOnEscape:'false',
                close:function (){
                    $("#ajax_loading").dialog('destroy');
                    $('#pro_diglog').append($('#ajax_loading'));
                }
            });
        },
        success:function(msgObj){
            $("#ajax_loading").dialog('close');
            $("#logistic_dl").html(msgObj);
        }
    });
}

//提交订单
function submitTrdOrders(){
    var url = '/Ucenter/Trdorders/doAddTrdorders';
    var res = $('#orderForm').valid();
    if(res){
        //发送ajax请求
        var data = $('#orderForm').serialize();
        //console.log(data);
        ajaxReturn(url,data,'post');
    //$.post('/Ucenter/Cart/doAdd', data, function(json){alert(json)});
    }
}
 //产看优惠券
  function lookCoupon(){
	  var pdt_id = {};
	  var cartPdt = $(".shopList .table01 .cartPdt");
	  $.each(cartPdt, function(i, dom){
		pdt_id[i] = $(dom).attr("pdt_id");
	  });
      var url = '/Ucenter/Orders/myCouponPage';
      $.post(url,pdt_id,function(html){
          $('#coupon').dialog({
                            height:300,
                            width:400,
                            resizable:false,
                            autoOpen: false,
                            modal: true,
                            buttons: { 
                                '确定': function() {
                                    $( this ).dialog( "close" );
                                    $('#coupon').hide(); 
                                }
                            }
                        });
                         $('#coupon').dialog('open');
                         $('#coupon').html(html);
      },'text')
  }
  //选择优惠券
  function checkCoupon(csn,coupon_price){
      $("#coupon_input").val(csn);
      //$("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
  }

  /***支付方式  修改按钮
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-04-20
   */
    function payments(obj){
        var radio_id = $(":radio[name=o_payment][checked]").val();
        var payment_type = $("#o_payment"+radio_id).val();
        var status =$("#upA").attr("status");
        if(status=='open'){
            $("#payment_name").html("付款方式:  " + payment_type);
            $("#payment_list").show();
            $("#payment_name").hide();
    		$("#upA").html("关闭");
            $("#upA").attr("status","close");   
        }
    }
    function payradio(obj) {
        var radio_id = obj.val();
        var payment_type = $("#o_payment"+radio_id).val();
        $("#payment_name").html("付款方式:  " + payment_type);
        $("#payment_list").hide();
        $("#payment_name").show();
        $("#upA").html("修改");
        $("#upA").attr("status","open");
    }

    /***订单成功选择其他支付方式
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-04-23
   */
    function suc_payments(obj){
        var radio_id = $(":radio[name=o_payment][checked]").val();
        var payment_type = $("#o_payment"+radio_id).val();
        var status =$("#upA").attr("status");
        if(status=='open'){
            $("#payment_list").show();
            $("#payment_name").hide();
    		$("#upA").html("确定选择支付方式");
            $("#upA").attr("status","close");   
        }else{
            $("#payment_name").html("选择其他支付方式:  " + '<a href="#"><b>' + payment_type +'</b></a>');
            $("#payment_list").hide();
            $("#payment_name").show();
    		$("#upA").html("选择其他支付方式");
            $("#new_payment_id").attr("value",radio_id);  
            $("#upA").attr("status","open");   
        }
    }
    /***订单成功更新其他支付方式时触发事件
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-04-23
   */
    $(function(){
        $("input[type='radio']").click(function(){
            var radio_id = $(":radio[name=o_payment][checked]").val();
            $("#new_payment_id").attr("value",radio_id);
        });
        $("input[type='radio']").click(function(){//change invoice
            var radio_id = $(":radio[name=is_default][checked]").val();
            var url = '/Ucenter/Orders/ChangeInvoice';
            $.get(url, { id: radio_id} );
        });
        $("input[type='radio']").click(function(){//change invoice
            var radio_id = $(":radio[name=invoice_head][checked]").val();
            if(radio_id==1){
                $("#invoice_name_tr").hide();
            }else{
                $("#invoice_name_tr").show();
            }
        });
        
    });
    //订单支付
    function paymentOrders(i){
    	$('#hideButton').css("display", "none");
    	$('#dingjinPay').css("display", "none");
    	var p_id = $('#new_payment_id').val();
    	var p_name = $('#o_payment'+p_id).val();
    	if(p_name == '线下支付' ){
    	        var o_id = $('#oid').val();
    	        var url = '/Ucenter/Orders/setPage';
    	        $.post(url,{'o_id':o_id,'p_id':p_id},function(html){
					$('#hideButton').css("display", "");
					$('#dingjinPay').css("display", "");
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
  
    	}else{
			var o_id = $('#oid').val();
			var is_pay_send = $('#is_pay_send').val();
			var url = '/Ucenter/Orders/paymentPage';
			var res = $('#successorderForm').valid();
			$("#typeStat").val(i);
			if(res){
				if(is_pay_send == '1' && p_id == '1'){
					$('#hideButton').css("display", "");
					$('#dingjinPay').css("display", "");
					var o_id = $('#oid').val();
					var url = '/Ucenter/Orders/setSendPage';
					$.post(url,{'o_id':o_id,'p_id':p_id},function(html){
						$('#hideButton').css("display", "");
						$('#dingjinPay').css("display", "");
						$('#children1').dialog({
							height:185,
							width:340,
							resizable:false,
							autoOpen: false,
							modal: true,
							buttons: { 
								'确定':function(){
									validateSms(o_id,$( this ));
							},
								'取消': function() {
									$( this ).dialog( "close" );
									$('#children1').hide();
								}
							}
						});
						 $('#children1').dialog('open');
						 $('#children1').html(html);
					   },'html');								
				}else{
					//发送ajax请求
					document.successorderForm.submit();
				}
			}  
    	}
    }
    
    //添加销货收款单
    function addVoucher(o_id,obj){
       var url = "/Ucenter/Orders/addVoucher";
       var sr_remitter =$('#sr_remitter').val();
       var sr_bank =$('#sr_bank').val();
       var to_post_balance =$('#to_post_balance').val();
       var sr_bank_sn = $('#sr_bank_sn').val();
       var sr_remark = $('#sr_remark').val();
       var sr_remit_time = $('#sr_remit_time').val();
       if(to_post_balance == "" || sr_bank_sn == "" ){
    	   showAlert(false,'出错了','汇款金额、流水号必填');
       }
       $.post(url, {'o_id':o_id,'sr_remitter':sr_remitter,'sr_remit_time':sr_remit_time,'sr_remark':sr_remark,'sr_bank':sr_bank,'to_post_balance':to_post_balance,'sr_bank_sn':sr_bank_sn}, function(msgObj){
           if(msgObj.status == '1'){
        	   showAlert(true,msgObj.info);
        	   setTimeout(location.href="/Ucenter/Orders/pageList", 3000 );
           }else{
               showAlert(false,'出错了',msgObj.info);
               return false;
           }
               
       }, 'json');
   }
     //短信验证
    function validateSms(o_id,obj){
	   var url = "/Ucenter/Orders/validateSmsCode";
	   var m_mobile_code =$('#m_mobile_code').val();
	   if(m_mobile_code == ""){
			alert('请先输入验证码');return;
			//showAlert(false,'出错了','请先输入验证码');
	   }
	   $.post(url, {'o_id':o_id,'m_mobile_code':m_mobile_code}, function(msgObj){
		   if(msgObj.status == '1'){
			   alert(msgObj.info);
			   //showAlert(true,msgObj.info);
			   document.successorderForm.submit();
		   }else{
				alert(msgObj.info);
				//showAlert(false,'出错了',msgObj.info);
			   return false;
		   } 
	   }, 'json');	
   }
   
    //暂不要发票
    function no_invoice(){
        var htmls='不开发票';
        $("#invoices_val").val("0");
        $("#invoice_hide").show();
        $("#invoice_show").hide();
        $('#invoice_hide').html(htmls);
    }
    //保存发票信息
    function save_invoice(){
        var invoice_type_id = $(":radio[name=invoice_type][checked]").val();
        var invoice_head_id = $(":radio[name=invoice_head][checked]").val();
        var invoice_content = $(":radio[name=invoice_content][checked]").val();
        var invoice_name = $("#invoice_name").val();
        var invoice_people = $("#invoice_people").val();
        if(invoice_type_id==undefined || invoice_head_id==undefined){
            showAlert(false,'出错了','请设置发票基本信息!');
            return false;
        }else{
            if(invoice_type_id==2){
                var invoice_type='增值税发票';
            }else{
                var invoice_type='普通发票';
            }
            
            if(invoice_head_id==1){
                if(invoice_people == ''){
                    showAlert(false,'出错了','个人姓名不能为空!');
                    return false;
                }
                var invoice_head='（个人）'+invoice_people;
                var show_invoice_content=invoice_content;
            }else{
                if(invoice_name==''){
                    showAlert(false,'出错了','单位名称不能为空!');
                    return false;
                }else{
                    var invoice_head = "（单位）"+invoice_name;
                    var show_invoice_content=invoice_name+'('+invoice_content+')';
                }
            }
//            var htmls='发票类型：'+invoice_type +'<br>'+'发票抬头：'+invoice_head+'<br>'+'发票内容：'+show_invoice_content; 
            if(invoice_content == undefined){
                var htmls='发票类型：'+invoice_type +'<br>'+'发票抬头：'+invoice_head+'<br>';
            }else{
                var htmls='发票类型：'+invoice_type +'<br>'+'发票抬头：'+invoice_head+'<br>'+'发票内容：'+show_invoice_content+'<input name="invoices_val" type="hidden" id="invoices_vals" value="1">';
            }
        }
        $("#invoices_vals").remove();
        $("#invoices_val").val("1");
        $("#invoice_hide").show();
        $("#invoice_show").hide();
        $('#invoice_hide').html(htmls);
    }
    
    
    
    //添加至常用发票信息
    function add_invoice(){
//        var invoice_type_id = $(":radio[name=invoice_type][checked]").val();
        var invoice_type_id = $("input[name='invoice_type']:checked").val();
        var invoice_head_id = $(":radio[name=invoice_head][checked]").val();
        var invoice_content = $(":radio[name=invoice_content][checked]").val();
        var invoice_name = $("#invoice_name").val();
        var invoice_people = $("#invoice_people").val();
        var is_default = $("")
        if(invoice_head_id == '1' && invoice_people == ''){
            showAlert(false,'出错了','请个人姓名不能为空');
            return false;
        }
        if(invoice_head_id == '2' && invoice_name == ''){
            showAlert(false,'出错了','请单位名称不能为空');
            return false;
        }
        if(invoice_type_id==undefined){
            showAlert(false,'出错了','请设置发票基本信息!');
            return false;
        }else{
            if(invoice_type_id==2){
                var invoice_type='增值税发票';
            }else{
                var invoice_type='普通发票';
            }
            if(invoice_head_id==1){
                var invoice_head='(个人)'+invoice_people;
                var show_invoice_content=invoice_content;
                var _default = $("#invoice_people").parent().parent().parent().next().children().next().children().attr('checked');
            }else{
                if(invoice_name==''){
                   showAlert(false,'出错了','单位名称不能为空!'); 
                   return false;
                }else{
                    var invoice_head='(单位)'+invoice_name;
                    var show_invoice_content=invoice_name+'('+invoice_content+')';
                    var _default = $("#invoice_name").parent().parent().parent().next().children().next().children().attr('checked');
                }
            }
        }
        if(invoice_content == undefined){
            var htmls='发票类型：'+invoice_type +'<br>'+'发票抬头：'+invoice_head+'<br>';
        }else{
            var htmls='发票类型：'+invoice_type +'<br>'+'发票抬头：'+invoice_head+'<br>'+'发票内容：'+show_invoice_content+'<input name="invoices_val" type="hidden" id="invoices_vals" value="1">';
        }
        if(_default == 'checked'){
            var is_default = 1;
        }else{
            var is_default = 0;
        }
        
        $("#invoice_hide").show();
        $("#invoice_show").hide();
        $('#invoice_hide').html(htmls);
        var url = '/Ucenter/Orders/AddInvoice';
        $.get(url, { type: invoice_type_id,invoice_people:invoice_people, head: invoice_head_id,name:invoice_name, content: invoice_content,is_default:is_default} );
    }
  /***延迟发货
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-05-22
   */
    function Delay(){
        var status = $("input[name='receiver_time']").attr("checked"); 
        if(status=='checked'){
            if (confirm("确定要延迟发货？")){
                $("#o_status").val(3);
            }
        }else{
            $("#o_status").val(1);
        }
    }
$(document).ready(function(){
    //普通发票 和 增值税发票
    var rada = $("#rada");
    var rada02 = $("#rada02");
    rada.click(function(){
            if(rada.is(":checked")){
                    $(".hdT01").show();
                    $(".hdT02").hide();
            }
    })
    rada02.click(function(){
            if(rada02.is(":checked")){
                    $(".hdT01").hide();
                    $(".hdT02").show();
            }
    })
    
    //个人、单位
    var radp = $("#radp");
    var radp02 = $("#radp02");
    radp.click(function(){
            if(radp.is(":checked")){
                    $("table.personalT").show();
                    $(".unitT").hide();
            }
    })
    radp02.click(function(){
            if(radp02.is(":checked")){
                    $(".unitT").show();
                    $("table.personalT").hide();
            }
    })

});

/**
 * 调用快递100接口查看物流跟踪信息
 *
 * @author Mithern<sunguangxu@guanyisoft.com>
 * @version 1.0
 * @date 2013-08-08
 */
function showPostTrack(od_id){
	//自动取出当前系统的根目录
	var request_uri = __ROOT + 'Ucenter/Orders/getOrdersPostTrack';
	$.post(request_uri,{od_id:od_id},function(htmlObj){
		$("#postTrackInfo").html(htmlObj);
		$("#postTrackInfo").parent("tr").show();
	},'html');
}

//使用促销
function doPromotions(type){
  var url = '/Ucenter/Orders/doPromotions';
  var bonus = $("#bonus_input").val();
  var cards = $("#cards_input").val();
  var jlb = $("#jlb_input").val();
  var point = $("#point_input").val();
  var csn = $("#coupon_input").val();
  var bonus_t = $("#bonus_total").text();
  var cards_t = $("#cards_total").text();
  var jlb_t = $("#jlb_total").text();
  var point_t = $("#point_total").text();
  var lt_id = $(":radio[name=lt_id][checked]").val();
  var pids = $("#goods_pids").val();
  var label_str = 'coupon';
  if(type == 0){
      if(csn == ''){
          showAlert(false,'','优惠券不能为空');
          return false;
      }
  }else if(type == 1){
      label_str = 'bonus';
      if(bonus == ''){
          showAlert(false,'','红包金额不能为空');
          return false;
      }
      if(parseFloat(bonus) > parseFloat(bonus_t)){
          $("#bonus_input").val(bonus);
          showAlert(false,'','红包金额不能大于用户可用金额');
          return false;
      }else if(parseFloat(bonus) == 0){
          $("#bonus_input").val("");
      }else if(parseFloat(bonus) < 0 ){
          $("#bonus_input").val("");
          showAlert(false,'','红包金额不能小于等于0');
          return false;
      }
  }else if(type == 2){
      label_str = 'cards';
      if(cards == ''){
          showAlert(false,'','储值卡金额不能为空');
          return false;
      }
      if(parseFloat(cards) > parseFloat(cards_t)){
          $("#cards_input").val(cards);
          showAlert(false,'','储值卡金额不能大于用户可用金额');
          return false;
      }else if(parseFloat(cards) == 0){
          $("#cards_input").val("");
      }else if(parseFloat(cards) < 0 ){
          $("#cards_input").val("");
          showAlert(false,'','储值卡金额不能小于等于0');
          return false;
      }
  }else if(type == 3){
      label_str = 'jlb';
      if(jlb == ''){
          showAlert(false,'','金币金额不能为空');
          return false;
      }
      if(parseFloat(jlb) > parseFloat(jlb_t)){
          $("#jlb_input").val(jlb);
          showAlert(false,'','金币金额不能大于用户可用金额');
          return false;
      }else if(parseFloat(jlb) == 0){
          $("#jlb_input").val("");
      }else if(parseFloat(jlb) < 0 ){
          $("#jlb_input").val("");
          showAlert(false,'','金币金额不能小于等于0');
          return false;
      }
  }else if(type == 4){
      label_str = 'point';
      if(point == ''){
          showAlert(false,'','积分不能为空');
          return false;
      }
      if(parseFloat(point) > parseFloat(point_t)){
          $("#point_input").val(point);
          showAlert(false,'','不能大于用户可用积分');
          return false;
      }else if(parseFloat(point) == 0){
          $("#point_input").val("");
      }else if(parseFloat(point) < 0 ){
          $("#point_input").val("");
          showAlert(false,'','积分不能小于0');
          return false;
      }
  }
  $.post(url,{'csn':csn,'bonus':bonus,'cards':cards,'jlb':jlb,'point':point,'lt_id':lt_id,'pids':pids,'type':type},function(data){
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
         $("#points_label").html('<i class="price"></i>'+points+'分');
         $("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
         $("#bonus_label").html('<i class="price">￥</i>'+bonus_price);
         $("#cards_label").html('<i class="price">￥</i>'+cards_price);
         $("#jlb_label").html(jlb_price);
         $("#point_label").html('<i class="price">￥</i>'+point_price);
         $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
      }else {
            var all_orders_price = (parseFloat(data.all_price)).toFixed(2);
            $("#msg"+type).css({'display':'none'});
            $("#"+label_str+"_label").html('<i class="price">￥</i>0.00');
            if(all_orders_price < 0){
                all_orders_price = '0.00';
            }
            $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
			//$('#submit_order').attr({disabled:true}).attr("value","抵扣金额超过商品金额");
			$('#jlb_input').val(0);
			$('#point_input').val(0);
			//$('#coupon_input').val();
			$('#bonus_input').val(0);
			$('#cards_input').val(0);
			showAlert(false,'',data.errMsg);
            return false;
      }
  },'json');
}
