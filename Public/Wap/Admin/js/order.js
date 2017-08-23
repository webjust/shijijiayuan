
/***
   *批量同步
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-05-16
   */
    function ajaxDoAddBatErpOrder(){
        var ary_oid = new Array();
        $(".tbList input:checked[class='checkSon']").each(function(){
            ary_oid.push(this.value);
        });
        oid = ary_oid;
        oid = oid.join(",");
        if(oid == ''){
            $("#J_ajax_loading").addClass('ajax_error').html("请选择需要同步的订单！").show().fadeOut(5000);
            return false;
        }
        $.ajax({
			url:'/Admin/Orders/sysBatOrders',
            cache:false,
            dateType:'json',
            type:'POST',
            data:{oid:oid},
            beforeSend:function(){
                $("#J_ajax_loading").stop().removeClass('ajax_error').addClass('ajax_loading').html("提交请求中，请稍候...").show();
            },
            error:function(){
                $("#J_ajax_loading").addClass('ajax_error').html("AJAX请求发生错误！").show().fadeOut(5000);
            },
            success:function(msgObj){
                if(msgObj.status == '1'){
                    $.each(guid,function(index,value){
                        $("#guid_"+value).html("<span style='color:green;'>已同步</span>");
                    });
                    $("#J_ajax_loading").addClass('ajax_success').html(msgObj.info).show().fadeOut(5000);
                }else{
                    $("#J_ajax_loading").addClass('ajax_error').html(msgObj.info).show().fadeOut(5000);
                }
            }
        });
    }
    
  /***
   *省市联动
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-05-16
   */    
    function selectCityRegion(obj, item, default_value) {
        var value = obj.value;
        if(!value){
            value = obj;
        }
        var url = '/Admin/Delivery/getCityRegion/';
        $('#'+item).load(url, {
            'parent': value,
            'default_value':default_value
        }, function(){
            if('' != default_value) {
                this.value = default_value;
            }
        });
    }
    function initSelectCityRegion() {
        $('#city').html('<option value="0">请选择</option>');
        $('#region').html('<option value="0">请选择</option>');
    }
    //更改支付方式
    function changePayment(obj){
    	var pc_fee = $("#payment_list option:selected").attr('pc_fee');
    	var old_pc_fee = parseFloat($("#o_cost_payment").val());
    	var showAllPricce = parseFloat($("#showAllPricce").html());
    	$("#o_cost_payment").val(pc_fee);
    	$("#showAllPricce").html(pc_fee-old_pc_fee+showAllPricce);	
    }
    //选择物流公司
    function checkLogistic(obj){
    	var loginsicPrice = parseFloat($("#logistic_price_"+obj).html());
    	var old_all_price = parseFloat($("#old_all_price").val());
    	var old_cost_freight = parseFloat($("#old_cost_freight").val());
    	$("#old_cost_freight").val(loginsicPrice);
    	$("#cost_freight").val(loginsicPrice);
    	$("#showAllPricce").html(loginsicPrice-old_cost_freight+old_all_price);
    };   
    //已付款订单选择物流公司
    function checkLogistic1(obj){
    	var loginsicPrice = parseFloat($("#logistic_price_"+obj).html());
    	var old_all_price = parseFloat($("#old_all_price").val());
    	var old_cost_freight = parseFloat($("#old_cost_freight").val());
    	//邮费差价
    	var o_diff_freight = parseFloat(loginsicPrice)-parseFloat(old_cost_freight);
    	$("#o_diff_freight").val(o_diff_freight);
    	$("#o_diff_freight_show").html(o_diff_freight);
    };   
    //执行添加组合商品
    function submitFrom(){
    	var is_edit = $("#is_edit").val();
    	if(is_edit == 0){
    		showAlert(false,'请先点击计算价格后再点击提交');
    		location.href="#getPrices";
    		return false;
    	}
    	var r=confirm("您确定提交更改吗？更改后不可返回！")
    	if (r==true)
    	{
    		$("#orderForm").submit();
    	}
    	else
    	{
    		return;
    	}
    }
    //回车触发搜索商品货号
    function EnterPress(e){ //传入 event 
        var e = e || window.event; 
        if(e.keyCode == 13){
            var pdt_sn = $("#pdt_sn").val();
            if(pdt_sn==''){
                showAlert(false,'商品货号不能为空！');return false;
            }
            ajaxSelectProducts(pdt_sn);
        } 
    }

    //删除货品
    function deleteProduct(obj){
        if(confirm('确定删除？')){
            $(obj).parent().parent().remove();
            //searchGoods();

        		var data = $("#orderForm").serialize(); 
                var url = "/Admin/Orders/computePrice/";
                $.post(url,data,function(jsonData){
                    if(jsonData.status){
                      var freight = $('#old_cost_freight').val();
           			  $("#o_goods_all_price").val(parseFloat(jsonData.o_goods_all_price));
         			  $("#pre_price").val(parseFloat(jsonData.o_discount));
         			  $("#showAllPricce").html(parseFloat(jsonData.o_goods_all_price)+parseFloat(freight));
         			  if(jsonData.promotion.length>0){
         				 var promotions = jsonData.promotion;
         				 var sp_detail = '';
        				 $.each(promotions, function(key, promotion) {
        					 if(promotion.gifts){
            					 if(promotion.gifts.length<=0){
            						
            					 }else{
        							 sp_detail +="赠品为：";
        							 $.each(promotion.gifts, function(key, val) {
        								 sp_detail +=val.g_name+';';
        							 });
            					 }
        					 }else{
        						 sp_detail +=promotion.pmn_name+';';
        					 }
        				 });
        				 $("#sp_detail").html(sp_detail);
        				 $("#showPromotion").css('display','');
         			  }
                    }else{
                    	showAlert(false,'计算价格失败');
                    }
                },'json');
        }
    }

    //根据pdt_sn获取货品详情信息，并将它显示在货品列表
    function ajaxSelectProducts(pdt_sn){
    	var o_id = $('#o_id').val();
        $.ajax({
                url:"/Admin/Goods/searchOrdersPdtInfo",
                type:'POST',
                dateType:'json',
                data:{'pdt_sn':pdt_sn,'o_id':o_id},
                success:function(msg){
                    if(msg.status == 'error'){
                        showAlert(false,msg.msg);return false;
                    }else{
                        var i = 0;
                        $(".pro_pdt_sn").each(function(){
                            if(this.value == pdt_sn){
                                i++;
                           }
                        });
                        if(i == 0){
                            $("#product_info").append(msg);
                        }
                    }
                }
            });
    }
    //使用优惠券
    function doCoupon(){
        var url = '/Admin/Orders/doCoupon';
        var csn = $("#coupon_input").val();
        var o_id = $("#o_id").val();
        if(csn == ''){
            showAlert(false,'','优惠券不能为空');
            return false;
        }
        $.post(url,{'csn':csn,'o_id':o_id},function(data){
            if(data.success == 1){
            	location.reload();
            }else {
                  showAlert(false,'',data.errMsg);
                  return false;
            }
        },'json');
    }
$(document).ready(function(){  
	$(".updateA").toggle(function(){
		$(".updateDiv").show();
	},function(){
		$(".updateDiv").hide();
	})
    //选择物流费用
	$("#old_cost_freight").change(function(){
		var last_freight = parseFloat($("#last_freight").val());
		var cost_freight = parseFloat($("#cost_freight").val());
    	var old_all_price = parseFloat($("#showAllPricce").html());
    	var old_cost_freight = parseFloat($("#old_cost_freight").val());
		$("#showAllPricce").html(parseFloat(old_cost_freight-last_freight+old_all_price).toFixed(2));
		$("#last_freight").val(old_cost_freight);
	});
	//区域变化	
	$("#region1").change(function(){
		var region = $(this).val();
	    var o_id = "{$ary_orders.o_id}";
        $("#logistic_dl").html('正在计算配送方式...');
        var url = '/Admin/Orders/getLogisticType/';
        $.post(url,{'o_id':o_id,'cr_id':region}
        ,function(jsonObj){
            if(jsonObj == ''){
            	$("#logistic_dl").html('请先选择收货地区');  
            }else{
            	$("#logistic_dl").html(jsonObj);
            }
        },'text');
	});
	$("#city").change(function(){
		var region = $(this).val();
	    var o_id = "{$ary_orders.o_id}";
        $("#logistic_dl").html('正在计算配送方式...');
        var url = '/Admin/Orders/getLogisticType/';
        $.post(url,{'o_id':o_id,'cr_id':region}
        ,function(jsonObj){
            if(jsonObj == ''){
            	$("#logistic_dl").html('请先选择收货地区');  
            }else{
            	$("#logistic_dl").html(jsonObj);
            }
        },'text');
	});
	$("#addGoods").click(function(){
	    $('#goodsSelect').dialog('open');
	});
	//计算价格
	$('#getPrices').live("click",function(){
		var data = $("#orderForm").serialize(); 
        var url = "/Admin/Orders/computePrice/";
        $.post(url,data,function(jsonData){
            if(true){
              $('#is_edit').val('1');
 			  $(".edigt").html(jsonData);
// 			  if(jsonData.promotion.length>0){
//// 				 var promotions = jsonData.promotion;
//// 				 var sp_detail = '';
////				 $.each(promotions, function(key, promotion) {
////					 if(promotion.gifts){
////						 if(promotion.gifts.length<=0){
////						 }else{
////							 //sp_detail +=promotion.pmn_name+';';
////							 sp_detail +="赠品为：";
////							 $.each(promotion.gifts, function(key, val) {
////								 sp_detail +=val.g_name;
////							 });
////						 }
////					 }else{
////						 sp_detail +=promotion.pmn_name+';';
////					 }
////				 });
////				 $("#sp_detail").html(sp_detail);
////				 $("#showPromotion").css('display','');
// 			  }
            }else{
            	showAlert(false,'计算价格失败');
            }
        },'html');
	});	
	//发票信息
	$(".delInvoice").click(function(){
            var vclass = $(this).attr("vclass");
            //发票抬头
            var invoice_head = $("."+vclass+" input[name='invoice_head']:checked").val();
            var invoice_content = $("."+vclass+" input[id='invoice_content']").val();
            var str_invoice_head = '';
            var invoice_type = $("input[name='invoice_type']:checked").val();
            if(invoice_type == '1'){
                if(invoice_head == '1'){
                    str_invoice_head = $("#invoice_people").val();
                }else if(invoice_head == '2'){
                    str_invoice_head = $("#invoice_name").val();
                }
            }else{
                str_invoice_head = $('#invoice_names').val();
            }
            
            $("input[name='old_invoice_head']").val(str_invoice_head);
            $("input[name='old_invoice_content']").val(invoice_content);
	    $('.updateDiv').css('display','none');
	});
//	$('#modifyNormalSku').change(function(){
//		var pdt_id =$(this).children('option:selected').val();//这就是selected的值
//		var o_id = "{$ary_orders.o_id}";
//		var m_id = "{$ary_orders.m_id}";
//		var url = '/Admin/Orders/modifyNormalSku';
//		if(confirm('您确定修改吗？')){
//	        $.post(url,{'pdt_id':pdt_id,'type':0,'o_id':o_id,'m_id':m_id}
//	        ,function(jsonObj){
//	            if(jsonObj == ''){
//	            	alert('修改失败'); 
//	            }else{
//	            	alert(jsonObj.message);
//	            }
//	        },'json');
//		}
//	}) 
    
    
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
    //删除商品信息
    $('.delItem').live("click",function(){
        if(confirm('确定删除吗？')){
        	var obj = $(this);
        	var type=obj.attr('item_type');
        	var design_id = obj.attr('design_id');
        	var o_id =  obj.attr('o_id');
        	var url = '/Admin/Orders/delItems/';
            if(o_id == ''){
                showAlert(false,'','订单号不能为空');
                return false;
            }
            $.post(url,{'type':type,'o_id':o_id,'design_id':design_id},function(data){
                if(data.success == 1){
                	showAlert(true,'商品删除成功');
                	location.reload();
                }else {
                      showAlert(false,'',data.errMsg);
                      return false;
                }
            },'json');
        }
    })
    
    $('.add').live("click",function(){
        var obj= $(this);
        //调整类型
        var type = obj.attr('type');
        //商品类型
		var good_type = obj.parent().find("input:first").attr('good_type');
        //货品的id
        var pdt_id = obj.attr('pdt_id');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //开始的数量
        var  pr_num =obj.prev().attr('value');
        //会员的价格
        var m_price = obj.attr("m_price");
        //点击减的数量
        var  jian_num = obj.next().attr('value');
        //现在在数量
        var pr_num_new = null;
        if(type == 1){
            pr_num_new = parseInt(jian_num)-1;
            if(pr_num_new<1){
            	pr_num_new = 1;
            }
            obj.next().val(pr_num_new);
        }else{
            pr_num_new = parseInt(pr_num) + parseInt(1);
            obj.prev().val(pr_num_new);
        }
        //当前的货品原销售价的总价
        var all_pdt_price = $("#o_goods_all_price").val();
        //货品原价
        var pdt_sale_price = obj.attr("pdt_sale_price");
        //优惠价
        var pre_price = $("#pre_price").val();
        //小计总价
        var pdt_price = parseFloat($("#total_"+pdt_id+"_"+good_type).html());
        //订单总金额
        var all_price_dis = parseFloat($("#showAllPricce").html());
        var m_id = $("#m_id").val();
        $.post("/Admin/Orders/doEditNum",{
            "pdt_id":pdt_id,
            "pdt_nums":pr_num_new,
			"good_type":good_type,
			"all_price":0,
            "all_dis":0,
            "m_id":m_id
        },function(result){
        	if(result.status==false){
        		alert(result.message);
        	}else{
     		   if(good_type == 0){
     			  var nowPrice = $("#rp_"+pdt_id+"_"+good_type).val();
     			  var nowNum = pr_num_new;
     			  if(parseFloat(nowPrice)>0){
     				 $("#sp_"+pdt_id+"_"+good_type).html(result.data.pdt_sale_price);
     				 obj.attr('pdt_sale_price',result.data.pdt_sale_price);
     				 $("#op_"+pdt_id+"_"+good_type).html(nowPrice);
     				 var total_good_price = (parseFloat(nowPrice)*nowNum).toFixed(3);
     				 $("#total_"+pdt_id+"_"+good_type).html(total_good_price);
        			 $("#o_goods_all_price").val(parseFloat(all_pdt_price)-parseFloat(pdt_price)+parseFloat(total_good_price));
         			 $("#showAllPricce").html(parseFloat(all_price_dis)-parseFloat(pdt_price)+parseFloat(total_good_price));    				 
     			  }else{
     				  var good_price = parseFloat(all_pdt_price)-parseFloat(pdt_price)+parseFloat(result.data.pdt_momery);
         			  $("#sp_"+pdt_id+"_"+good_type).html(result.data.pdt_sale_price);
         			  obj.attr('pdt_sale_price',result.data.pdt_sale_price);
         			  $("#op_"+pdt_id+"_"+good_type).html(result.data.pdt_price);
         			  $("#total_"+pdt_id+"_"+good_type).html(result.data.pdt_momery);
         			  $("#o_goods_all_price").val(good_price.toFixed(3));
         			  $("#pre_price").val(parseFloat(pre_price)+parseFloat(result.data.pdt_preferential));
         			  $("#showAllPricce").html(parseFloat(all_price_dis)-parseFloat(pdt_price)+parseFloat(result.data.pdt_momery));     				  
     			  }
    		   }        		
        	}
        },'json');
    })
    //价格变化
    $('.textbox1').live("blur",function(){
		 var obj= $(this);
		 var pdt_id = $(this).attr('pdt_id');
		 //当前的货品原销售价的总价
	    var all_pdt_price = $("#o_goods_all_price").val();
	    //货品原价
	    var pdt_sale_price = obj.attr("pdt_sale_price");
	    //优惠价
	    var pre_price = $("#pre_price").val();
	    //小计总价
	    var pdt_price = parseFloat($("#total_"+pdt_id+"_0").html());
	    //订单总金额
	    var all_price_dis = parseFloat($("#showAllPricce").html());
	    var num = parseFloat(obj.attr('value'));
	    if(isNaN(num)){
	    	num = 1;
	    	obj.attr('value','1')
//	    	alert('请输入数字格式数字大约0小于1');
//	    	return false;
	    }
	    if(num<=0 || num>1){
	    	alert('请输入数字格式数字大约0小于1');
	    	return false;
	    }
		  //商品数量
		  var itemnum = $("#itemnum_"+pdt_id+"_0").val();
		  //销售价
	      var  sale_price = (parseFloat(pdt_sale_price)*num).toFixed(3);
	      var total_price = (parseFloat(pdt_sale_price)*num*itemnum).toFixed(3);
	      $("#rp_"+pdt_id+"_0").val(sale_price);
		  $("#op_"+pdt_id+"_0").html(sale_price);
		  $("#total_"+pdt_id+"_0").html(parseFloat(total_price));
//		  $("#o_goods_all_price").val(parseFloat(all_pdt_price)-parseFloat(pdt_price)+parseFloat(total_price));
//		  $("#showAllPricce").html(parseFloat(all_price_dis)-parseFloat(pdt_price)+parseFloat(total_price));
	 }
	);
    //价格变化
    $('.textbox2').live("blur",function(){
		 var obj= $(this);
		 var pdt_id = $(this).attr('pdt_id');
		 //当前的货品原销售价的总价
	    var all_pdt_price = $("#o_goods_all_price").val();
	    //货品原价
	    var pdt_sale_price = obj.attr("pdt_sale_price");
	    //优惠价
	    var pre_price = $("#pre_price").val();
	    //小计总价
	    var pdt_price = parseFloat($("#total_"+pdt_id+"_0").html());
	    //订单总金额
	    var all_price_dis = parseFloat($("#showAllPricce").html());
		  //商品数量
		  var itemnum = $("#itemnum_"+pdt_id+"_0").val();
		  //销售价
	      var sale_price = $("#rp_"+pdt_id+"_0").val();
	      var total_price = (sale_price*itemnum).toFixed(3);
	      $("#rp_"+pdt_id+"_0").val(sale_price);
		  $("#op_"+pdt_id+"_0").html(sale_price);
		  $("#total_"+pdt_id+"_0").html(parseFloat(total_price));
		  var total_good_price = parseFloat(all_pdt_price)-parseFloat(pdt_price)+parseFloat(total_price);
		  var all_price = parseFloat(all_price_dis)-parseFloat(pdt_price)+parseFloat(total_price);
		  $("#o_goods_all_price").val(total_good_price.toFixed(3));
		  $("#showAllPricce").html(all_price.toFixed(3));
	 }
	);
    $('#goodsSelect').dialog({
        resizable:false,
        autoOpen: false,
        modal: true,
        width: 'auto',
        position: [220,85],
        buttons: {
            '确认': function() {
                $("input[name='gs_pdt_sn']:checked").each(function(){
                    ajaxSelectProducts(this.value);
                });
                $(this).dialog( "close" );
            },
            '关闭': function() {
                $( this ).dialog( "close" );
            }
        }
    });
    $("#addGoods").click(function(){
        $('#goodsSelect').dialog('open');
    });
});   