$(document).ready(function(){
    $("a.addNums").each(function(){
        addNum($(this),"no");
    });

    //全选切换
    $("#allCho").click(function(){
        if(this.checked){
            $(".all_check").attr('checked',true);
        }else{
            $(".all_check").attr('checked',false);
        }
    });
    
    //C2C订单删除
    $(".p_del").live('click',function(){
        var is_del = parseInt($(this).attr("is_del"));
        var obj = $(this);
        var str = $(this).attr("thtml");
        if(confirm("确定"+str+"操作")){
            var to_id = $(this).prev().attr("to_id");
            var tt_id = $(this).prev().attr("tt_id");
            var num_iid = $(this).prev().attr("num_iid");
            var o_del = $(this).prev().attr("o_del");
            var val = $(this).attr("val");
            var url = '/Ucenter/Trdorders/saveTrdordersDel';
            $.ajax({
                url:url,
                cache:false,
                dataType:'json',
                type:'POST',
                data:{
                    'to_id':to_id,
                    'tt_id':tt_id,
                    'num_iid':num_iid,
                    'o_del':o_del,
                    'status':val
                },
                success:function(msgObj){
                    if(msgObj.success == '1'){
                        window.location.reload();
                        showAlert(true,'删除成功');
                        return false;
                    }else{
                        showAlert(false,'删除失败，请重试');
                        return false;
                    }
                }
            });
        }
    })
    
    /**
    * C2C订单匹配
    * @author Terry<wanghui@guanyisoft.com>
    * @update 2013.1.6
    */ 
    $(".order_config").click(function(){
        var is_del = $(this).next("a").attr("is_del");
        if(is_del == 0){
            $("#tip_products").dialog({
                width:800,
                height:600,
                modal:true,
                closeOnEscape:'false',
                title:'商品匹配'
            });
            var strHtml = '';
            var order_tt_id = $(this).attr("for");
            var order_id = $(this).attr("order_id");
            var order_to_id = $(this).attr("to_id");
            var num_iid = $(this).attr("num_iid");
            strHtml += '<input type="hidden" value="'+order_tt_id+'" name="order_tt_id" class="class_order_tt_id"/>';
            strHtml += '<input type="hidden" value="'+order_id+'" name="tt_id" class="tt_id" class="tt_id" />';
            strHtml += '<input type="hidden" value="'+order_to_id+'" name="to_id" class="to_id" class="to_id"/>';
            strHtml += '<input type="hidden" value="'+num_iid+'" name="num_iid" class="num_iid" class="num_iid"/>';
            $("#trdorder_tt_id").html(strHtml);
            return false;
        } else if(is_del == 1){
            showAlert(false,'该货品已经删除');
        }else{
            showAlert(false,'非法参数');
        }
    });
    
    //搜索商品数据
    $("#p_search").live('click',function(){
        var pdt_sn = $("#pdt_sn").val();
        var g_name  = $("#g_name").val();
        var gc_id   = $("#gcid").find("option:selected").val();
        var order_tt_id = $(".class_order_tt_id").val();
        var g_sn	= $("#g_sn").val();
        var order_to_id = $(".to_id").val();
        var tt_id = $(".tt_id").val();
        var num_iid = $(".num_iid").val();
        var o_type = $(".order_type").val();
        //alert(gc_id);return false;
        //var data = $('#products').serialize();
        var url = '/Ucenter/Trdorders/pageProducts';
        //ajaxReturn('/Ucenter/Trdorders/pageProducts',data,'post');
        $.ajax({
            url:url,
            cache:false,
            dataType:'TEXT',
            type:'GET',
            data:{
                'pdt_sn':pdt_sn,
                'g_name':g_name,
                'gcid':gc_id,
                'tt_id':tt_id,
                'num_iid':num_iid,
                'order_tt_id':order_tt_id,
                'g_sn':g_sn,
                'order_to_id':order_to_id,
                'o_type':o_type
            },
            success:function(msgObj){
                $("#products_data").html(msgObj);
            }
        });
    });

    $('.pdtSwitch').live('click',function(){
        
        var sw = $(this).parents('table').find('tbody tr:eq(4)').css('display');
        if('none'==sw){
            $(this).find('span').removeClass('open').addClass('close');
            $(this).parents('table').find('tbody tr:gt(3)').fadeIn('fast');
        }else{
            $(this).find('span').removeClass('close').addClass('open');
            $(this).parents('table').find('tbody tr:gt(3)').fadeOut('fast');
        }
    });
    
    $('.pdtSwitchs').live('click',function(){
        
        var sw = $(this).prev('.list_orders').children('td').children("table").find('tbody tr:eq(2)').css('display');
        if('none'==sw){
            $(this).find('span').removeClass('open').addClass('close');
            $(this).prev('.list_orders').children('td').children("table").find('tbody tr:gt(1)').fadeIn('fast');
        }else{
            $(this).find('span').removeClass('close').addClass('open');
            $(this).prev('.list_orders').children('td').children("table").find('tbody tr:gt(1)').fadeOut('fast');
        }
    });
    
    //减少购买量
    $(".reduceNums").live('click',function(){
		var obj = $(this);
		reduceNum(obj);
		//物流配送配置
		var log_conf = $("#logistics_"+tt_id).attr('conf');
		//物流公司
		var logistics = $("#logistics_"+tt_id).attr('lt_id');
		if(0 != logistics && "" != log_conf){
			changeLogis(obj,"reduceNums");
		}
        //$("#countPrice").html(tprice);
        return false;
    });
	function reduceNum(obj,inputVal) {
		var tt_id = $(obj).next().attr("tt_id");
        var stock = $(obj).next("input").attr("stock");                //货品库存
        var gprice = $(obj).next("input").attr("gprice");              //货品单价
        var pdt_weight = $(obj).next("input").attr("pdt_weight");
        var tt_id = parseInt($(obj).next("input").attr("tt_id"));      //第三方订单ID
        var num = parseInt($(obj).next("input").val());                 //购买数量
        var currentPrice = 0;                   //当前商品总价
        var finalPrice = 0;                     //最终商品总价
        var totalPrice = 0;                       //当前购买所有商品总价格
        var tprice = 0;                         //商品总价
        //当前商品总价  = 商品购买数量*商品单价
        currentPrice = (num * parseFloat(gprice)).toFixed(2);
        if(num > 1 && !isNaN(num)){
            var currentPrice = parseFloat($("#price_"+tt_id).attr("gprice"));
            tprice = ((parseFloat(currentPrice)-parseFloat(gprice))).toFixed(2);
            $("#price_"+tt_id).attr("gprice",tprice);
            $("#price_"+tt_id).html(tprice);
        }
        num --;   
        if(num<1 || isNaN(num) || parseInt(stock)<parseInt(num)){
            num = 1;
        }
		//总重量
		pdt_weight_total = (num * pdt_weight).toFixed(3);
        finalPrice = (num * parseFloat(gprice)).toFixed(2);    
        $(obj).next("input").val(num);
		$(obj).parent().parent().next().find(".inputNum").val(num);
		$(obj).parents().next().next(".cgPrice").html(finalPrice);
		$(obj).parent().prev().find(".pdt_weight").attr("pdt_weight_couts",pdt_weight_total).html("重量：" + pdt_weight_total);
		return false;
	}
    //弹框减少数量
    $("a.toreduce_nums").live("click", function(){
        var stock = $(this).next("input").attr("stock");                //货品库存
        var num = parseInt($(this).next("input").val());                 //购买数量
        if(0 != num){
            num --;
            if(num<0 || isNaN(num) || parseInt(stock)<parseInt(num)){
                num = 1;
            }
            $(this).next("input").val(num);
        }
    });
    
    
    //增加购买量
    $("a.addNums").live('click',function(){
        var obj = $(this);
        var tt_id = obj.prev().attr("tt_id");
        //物流配送配置
        var log_conf = $("#logistics_"+tt_id).attr('conf');
        //物流公司
        var logistics = $("#logistics_"+tt_id).attr('lt_id');
        addNum(obj,"add");
        if(0 != logistics && "" != log_conf){
            obj.removeClass("addNums");
            changeLogis(obj,"addNums");
        }
    });
    //弹框 增加购买量
    $("a.toadd_nums").live("click", function(){
        addNum($(this),"add");
    });
    
    //商品匹配无刷新分页
    $("#products_data .fenye a").live('click',function(){
        var href = $(this).attr('href');
        $.ajax({
            url:href,
            cache:false,
            dataType:'TEXT',
            type:'GET',
            data:{},
            success:function(msgObj){
                $("#products_data").html(msgObj);
                
            }
        });
        return false;
    });
    
    //提交匹配结果
    $("#productCart").live('click',function(){
        var arrayObj = new Array();
        var product_id = $("#productSelectForm .pageQuickDTwo .pQuickDTwoConRight .products_class input");
        var b2b_pdt_sn_info = {};
        b2b_pdt_sn_info['pdt_id'] = {};
        b2b_pdt_sn_info['pdt_sn'] = {};
        $.each(product_id,function(i,goods){
            var value = $(goods).val();
            var pdt_stock = $(goods).attr("stock");
            var pdt_sn = $(goods).attr("pdt_sn");
            var pdt_id = $(goods).attr("pdt_id");
            var g_id = $(goods).attr("g_id");
            var pdt_spec_value = $(goods).attr("pdt_spec_value");
            var g_name = $(goods).attr("g_name");
            var g_picture = $(goods).attr("g_picture");
            var g_sn = $(goods).attr("g_sn");
            var pdt_sale_price = $(goods).attr("pdt_sale_price");
            var price = $(goods).attr("pdt_price");
            var pdt_weight = $(goods).attr('pdt_weight');
            if(value > 0 && value != ''){
                b2b_pdt_sn_info['pdt_id'][pdt_id]= value;
                b2b_pdt_sn_info['pdt_sn'][pdt_sn]= value;
                var Str = value+','+pdt_stock+','+g_name+','+pdt_id+','+g_id+','+pdt_sale_price+','+price+','+pdt_sn+','+g_sn+','+g_picture+','+g_name+','+pdt_spec_value+','+pdt_weight;
                var aryStr = Str.split(",");
                arrayObj.push(aryStr);					
            }
        });
        var order_id = $("#order_id").val();
        var tt_id = $("#order_id").attr("tt_id");
        var num_iid = $("#order_id").attr("num_iid");
        var o_type = $("#order_id").attr("o_type");
        var url = '/Ucenter/Trdorders/saveTrdMatchedProductsToDb';
        $.ajax({
            url:url,
            cache:false,
            dataType:'JSON',
            data:{
                'num_iid':num_iid,
                'b2b_pdt_sn_info':b2b_pdt_sn_info,
                'tt_id':tt_id,
                'to_id':order_id,
                'o_type':o_type
            },
            type:'POST',
            success:function(msgObj){
                if(msgObj.success == 1){
                    showAlert(true,msgObj.msg);
//                    $.ThinkBox.success("匹配成功");
                    window.location.reload();
                    return false;
                }else{
//                    $.ThinkBox.error(msgObj.msg);
                    showAlert(false,msgObj.msg);
//                    showAlert(false,msgObj.msg);
                    return false;
                }
            }
        });
        
    });
    
    //C2C匹配取消
    $(".cancel").live('click',function(){
        $("#tip_products").dialog('destroy');
        $('#pro_diglog').append($('#tip_products'));
        window.location.reload();
        return false;
    });
    
    //标记处理
    $(".tt_status").die().click(function(){
        var tt_id = $(this).parent().attr("tt_id");
		var tt_status = $(this).parent().attr("tt_status");
		var tsid = $(this).parent().attr("tsid");
		var source_id = $(this).parent().attr("source_id");
		if(tt_status==1){
			tt_status=1;
		}else{
			tt_status=0;
		}
        
		var url = '/Ucenter/Trdorders/saveTrdordersOrderHandle';
		ajaxReturn(url,{
			'tt_id':tt_id,
			'tt_status':tt_status,
			'source_id':source_id,
			'tsid':tsid
		},'post');
        
    });
    
    //批量处理标记
    $("#batchHandle").click(function(){
        if(confirm("确定批量处理标记吗?")){
        }else{
            return false;
        }
        var tt_id = '';
		var tt_status ='';
		var tsid ='';
        $(".all_check").each(function(){
            if(this.checked){
                tt_id += $(this).parent().attr("tt_id") + ',';
				tt_status = $(this).parent().attr("tt_status");
				tsid = $(this).parent().attr("tsid");
				source_id = $(this).parent().attr("source_id");
            }
        });
		if(tt_status==1){
			tt_status=1;
		}else{
			tt_status=0;
		}
        if(tt_id == ''){
            showAlert(false,'请先选中需要操作的订单,然后再进行批量处理');
            return false;
        }
        var url = '/Ucenter/Trdorders/saveBatchTrdordersOrderHandle';
        ajaxReturn(url,{
            'tt_id':tt_id,
			'tt_status':tt_status,
			'source_id':source_id,
			'tsid':tsid
        },'post');
    });
    
    //配置物流公司信息
    $(".logistics").live('click',function(){
        var tt_id = $(this).attr("tt_id");
        var province = $(this).attr('province');
        var city = $(this).attr('city');
        var district = $(this).attr('district');
        var adderss	 = $(this).attr('address');
        matchLogisticsCompanys(province, city, district, tt_id, adderss);
    });
	
	//选用匹配物流 判断是否勾选订单以及是否已经匹配商品
	$("#batchmatchlogistics").click(function(){
		if($(".all_check").is(":checked")){
			$("#showselectlogistics").show();
		}else{
			showAlert(false, "请勾选订单！");
			return false;
		}
	});
	$(".all_check").live("click",function(){
		var tt_id = $(this).attr("tt_id");
		if(!$(".all_check").is(":checked")){
			$("#showselectlogistics").hide();
		}
	});
	$("#allCho").click(function(){
		if(!$(this).is(":checked")){
			$("#showselectlogistics").hide();
		}
	});

    //选择物流公司
    $("#submitLog").live('click',function(){
        var log_weight = 0;
        var str_name = '';
        var tt_id = '';
        var lt_id = 0;
        var conf = '';
        var log_free = 0;
        var free = 0;
        $(".selectLog").each(function(){
            if(this.checked){
                log_weight = $(this).attr("weight");
                str_name = $(this).attr("lc_name");
                tt_id = $(this).attr("tt_id");
                lt_id = $(this).attr("lt_id");
                free = $(this).attr("total_freight_cost");
                conf = $(this).attr("conf");
            }
        });
		if(0 != lt_id){
			$("#tr_"+tt_id).attr("checked", true);
		}
        $("#configdilivery").dialog('destroy');
        $('#pro_diglog').append($('#configdilivery'));
        log_weight = (parseFloat(log_weight)).toFixed(2);
        log_free = (parseFloat(free)).toFixed(2);
        $("#free_"+tt_id).html(log_weight);
        $("#logistics_"+tt_id).attr('logistics','1');
        $("#logistics_"+tt_id).attr('conf',conf);
        $("#logistics_"+tt_id).attr('lt_id',lt_id);
        Free(tt_id,log_free);
        $("#logistics_"+tt_id).html(str_name);
    });
    
    //单张下单
    $(".Leaflets_orders").live('click',function(){
        var tt_id = $(this).parent().attr('tt_id');
        var ts_id = $(this).attr("ts_id");
        var pf = $(this).attr("pf");
        var match = 0;
        var ismatch = 0;
        var delmatch = 0;
        //获取订单中货品个数
        $("#tableNei_"+tt_id).each(function(){
            match = parseInt($(this).find(".match").length);
            ismatch = parseInt($(this).find(".ismatch").length);
            delmatch = parseInt($(this).find(".delmatch").length);
        });
//        alert(ismatch);return false;
        if(match == delmatch){
            showAlert(false,"此订单中商品已被全部删除，不可下单");
            return false;
        }
//        alert(ismatch);return false;
        if(delmatch == 0 && match > ismatch){
            showAlert(false,"订单还存在未匹配的商品");
            return false;
        }
        //提交订单到购物车
        var cart	= {};
        $("#tableNei_"+tt_id+" a.yMatched").each(function(){
            var toi_id = $(this).attr("toi_id");
            var className = $("#toids_"+toi_id+" .ismatch .cartProName").find("a").attr("class");
            if(className == 'checked'){ 
                $("#tableNei_"+tt_id+" #toids_"+toi_id+" td .tableNei .inmatched .inputNum").each(function(){
                    var num_iid	= $(this).attr('num_iid');
                    var pdt_sn	= $(this).attr('pdt_sn');
                    var pdt_id = $(this).attr('pdt_id');
					var toi_id = $(this).attr('toi_id');
                    cart[pdt_id]	= {};
                    cart[pdt_id]['num']		= $(this).val();
                    cart[pdt_id]['pdt_id']	= pdt_id;
                    cart[pdt_id]['type']	= 0;
                    cart[pdt_id]['tt_id']	= tt_id;
					cart[pdt_id]['toi_id']	= toi_id;
                });
            }
        });
        if(ismatch > 0){
            $.ajax({
                url:'/Ucenter/Trdorders/addthdCart',
                cache:false,
                dataType:'json',
                type:'POST',
                data:{
                    'pf':pf,
                    'ts_id':ts_id,
                    'cart':cart
                },
                success:function(msgObj){
                    if(msgObj.success == '1'){
                        window.location.href = '/Ucenter/Trdorders/thdCartList';
                        return false;
                    }else{
                        showAlert(false,msgObj.msg);
                        return false;
                    }
                }
            });
        }else{
            showAlert(false,"订单还存在未匹配的商品");
            return false;
        }
    });
    
    //批量下单
    $("#batchTrdorders").click(function(){
        var tt_id = '';
        $(".all_check").each(function(){
            if(this.checked){
                tt_id += $(this).parent().attr("tt_id") + ',';
            }
        });
        if(tt_id == ''){
            showAlert(false,'请选择订单');
            return false;
        }
        $("#payment_selected").dialog({
            width:789,
            height:'auto',
            modal:true,
            title:'选择支付方式',
            closeOnEscape:'false',
            close:function (){
                $("#payment_selected").dialog('destroy');
                $('#pro_diglog').append($('#payment_selected'));
            },
            buttons:{
                '确定':function(){
                    $("#payment_selected").dialog('destroy');
                    $('#pro_diglog').append($('#tip_div'));
                    batchOrder();
                },
                "取消": function() {
                    $("#payment_selected").dialog('destroy');
                    $('#pro_diglog').append($('#payment_selected'));
                }
            }
        });
    });
    
    //拍拍授权登陆
    $("#paipai_login").die().click(function(){
        $("#div_paipai_session_login").dialog({
            height:370,
            width:500,
            modal:true,
            resizable:false,
            title:'提示：填写 拍拍平台 各种信息',
            closeOnEscape:'false',
            close:function (){
                $("#div_paipai_session_login").dialog('destroy');
                $('#pro_diglog').append($('#div_paipai_session_login'));
            }
        });
    });
    
    //提交PAIPAI授权
    $('.spanWrong').hide();
    $('#form_div_paipai_session_login').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            for (var name in errors) {
                //alert(errors[name]);
                $('#' + name).parent('td').children('span.spanWrong').show();
                $('#' + name).parent('td').children('span.spanWrong').html(errors[name]);
            }

            return false;
        },
        onkeyup: false,
        onfocusout: false
    });
    $("#ajax_doAddd").click(function(){
        $('.spanWrong').html('');
        $('.spanWrong').hide();
        $('.tipmsg').html('');
        $('.tipmsg').hide();
        var res = $('#form_div_paipai_session_login').valid();
        if(res){
            var url = '/Ucenter/Trdorders/setPaiPaiInfo';
            var pf = $("#pf").val();
            var uin = $("#uin").val();
            var spid = $("#appoid").val();
            var token = $("#token").val();
            var seckey = $("#seckey").val();
            ajaxReturn(url,{
                'pf':pf,
                'uin':uin,
                'spid':spid,
                'token':token,
                'seckey':seckey
            },'post');
        }
    });
	
	//全选 取消全选
    $('#select_all1 , #select_all2').click(function(){
        if($(this).attr('checked')=='checked'){
            $("input:checkbox[name='pid[]']").attr('checked','checked');
            $('#select_all1 , #select_all2').attr('checked','checked');
        }else{
            $("input:checkbox[name='pid[]']").removeAttr('checked');
            $('#select_all1 , #select_all2').removeAttr('checked');
        }
    });
	loading();
	//第三方平台单张下单
    $("#thd_order").live('click',function(){
        var tt_id = $(this).parent().attr('tt_id');
        var ts_id = $(this).attr("ts_id");
        var pf = $(this).attr("pf");
        var match = 0;
        var ismatch = 0;
        var delmatch = 0;
        //获取订单中货品个数
        $("#tableNei_"+tt_id).each(function(){
            match = parseInt($(this).find(".match").length);
            ismatch = parseInt($(this).find(".ismatch").length);
            delmatch = parseInt($(this).find(".delmatch").length);
        });

        if(match == delmatch){
            showAlert(false,"此订单中商品已被全部删除，不可下单");
            return false;
        }
        if(ismatch == 0){
            showAlert(false,"订单还存在未匹配的商品");
            return false;
        }
        //提交订单到购物车
        var cart	= {};
        $("#tableNei_"+tt_id+" a.yMatched").each(function(){
            var toi_id = $(this).attr("toi_id");
            var className = $("#toids_"+toi_id+" .ismatch .cartProName").find("a").attr("class");
            if(className == 'checked'){ 
                $("#tableNei_"+tt_id+" #toids_"+toi_id+" td .tableNei .inmatched .inputNum").each(function(){
                    var num_iid	= $(this).attr('num_iid');
                    var pdt_sn	= $(this).attr('pdt_sn');
                    var pdt_id = $(this).attr('pdt_id');
                    cart[pdt_id]	= {};
                    cart[pdt_id]['num']		= $(this).val();
                    cart[pdt_id]['pdt_id']	= pdt_id;
                    cart[pdt_id]['type']	= 0;
                    cart[pdt_id]['tt_id']	= tt_id;
                });
            }
        });
        if(ismatch > 0){
            $.ajax({
                url:'/Ucenter/Trdorders/addthdCart',
                cache:false,
                dataType:'json',
                type:'POST',
                data:{
                    'pf':pf,
                    'ts_id':ts_id,
                    'cart':cart
                },
                success:function(msgObj){
                    if(msgObj.success == '1'){
                        window.location.href = '/Ucenter/Trdorders/thdCartList';
                        return false;
                    }else{
                        showAlert(false,msgObj.msg);
                        return false;
                    }
                }
            });
        }else{
            showAlert(false,"订单还存在未匹配的商品");
            return false;
        }
    });
    //第三方平台购物车修改商品数量
    $(".inputNum").blur(function(){
        var obj= $(this);
        //货品的id
        var pdt_id = obj.attr('pdt_id');
		//货品的商品类型
		var good_type = obj.attr('good_type');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
		//获取修改后的购买数量
		var nums = parseInt(obj.attr("value"));
		//普通商品
		if(good_type==0){
			//获取该货品原来的购买数量
			var old_nums = obj.next().val();
			//货品原价
			var pdt_sale_price = $("#pdt_price"+pdt_id).val();
			//会员购买价
			var  cai_price =  $("#xiao_price"+pdt_id).attr("value");
			//商品的总价
			var all_pdt_price = $("#all_pdt_price").val();
			//折扣总价
			var all_price_dis =$("#all_price_dis").val();
			//当前购物车商品数量
			var cart_num =$("#current_num").attr('value');
            if(nums > stock){
                nums = stock;
                obj.val(nums);
            }
			//小计
			var smail_price  =  (parseInt(nums) * cai_price).toFixed(2);
			obj.parent().parent().next().next().html("<i class='price'>￥</i>  "+smail_price);
			//默认是1
			if(nums <=0 || nums > stock){
				obj.attr("value",1);
				obj.parent().next().next().html("<i class='price'>￥</i>  "+(1 * cai_price));
				obj.next().val(1);
				return false;
			}
			//修改后的购买量的差
			var dif_nums = parseInt(nums-old_nums);
			var new_cart_num =parseInt(cart_num) + parseInt(dif_nums);
			//修改之后的商品总价
			var modified_nums_price = 0;
			//修改后的商品的总计
			var total_nums_price = 0;
			//修改之后的商品的差价
			var modified_ori_price = pdt_sale_price * (Math.abs(dif_nums));
			//修改后的会员的的差价
			var mem_spread_price = cai_price * (Math.abs(dif_nums));
			if(dif_nums < 0){
				modified_nums_price =  (all_pdt_price - modified_ori_price).toFixed(2);
				total_nums_price    =  (all_price_dis - mem_spread_price).toFixed(2);
			}else{
				modified_nums_price =  (parseFloat(all_pdt_price) + parseFloat(modified_ori_price)).toFixed(2);
				total_nums_price    =  (parseFloat(all_price_dis) + parseFloat(mem_spread_price)).toFixed(2);
			}
			//把修改后的购买量放入hidden中
			obj.next().val(nums);
			//把修改后的商品总价显示在页面中 商品的总价
			$("#pdt_price").html("<i class='price'>￥</i>  "+modified_nums_price);
            
			$("#all_pdt_price").val(modified_nums_price);
			//会员总计
			$("#strong_all_price").html("<i class='price'>￥</i> "+ total_nums_price);
			$("#all_price_dis").val(total_nums_price);
            $("#all_pdt_price").attr("pdt_price",modified_nums_price);
			$("#top_all_price").html(total_nums_price);
			//会员的优惠总计
			var mem_pre_price = (modified_nums_price - total_nums_price).toFixed(2);
			$("#label_pre_price").html("<i class='price'>￥</i>"+ mem_pre_price);
			doEdit(obj,pdt_id,nums,modified_nums_price,mem_pre_price,good_type);
			loading();
		}
		else{
		    doEdit(obj,pdt_id,nums,0,0,good_type);
			loading();
		}
    });
	//第三方平台购物车修改商品数量
    $('.add').click(function(){
        var obj= $(this);
        var type = obj.attr('type');
		var good_type = obj.parent().find("input:first").attr('good_type');
		
        //货品的id
        var pdt_id = obj.attr('pdt_id');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //开始的数量
        var  pr_num =obj.prev().attr('value');
        //会员的价格

        //点击减的数量
        var  jian_num = obj.next().attr('value');
        //现在在数量
        var pr_num_new = null;
		
        if(type == 1){
            pr_num_new = parseInt(jian_num)-1;
            if(good_type != 1) obj.next().next().val(pr_num_new);
        }else{
            pr_num_new = parseInt(pr_num) + parseInt(1);
            if(good_type != 1) obj.prev().val(pr_num_new);
        }

        //当前的货品原销售价的总价
        var all_pdt_price = $("#all_pdt_price").attr('pdt_price');
        //货品原价
        var pdt_sale_price = $("#pdt_price"+pdt_id).val();
        //会员价
        var m_price = obj.parent().parent().prev().attr("value");
        //采购价
        var  smail_price =  $("#xiao_price"+pdt_id).attr("value");
        //优惠价
        var pre_price = $("#pre_price").val();
        //折扣总价
        var all_price_dis =$("#all_price_dis").val();
		if(good_type == 1){
		   if(type == 1){
		       if(pr_num_new > 0){
				  doEdit(obj,pdt_id,pr_num_new,0,0,good_type,type);
				  loading();
			   }
			   else{
                  return false;
               } 
		   }
		   else{
				  doEdit(obj,pdt_id,pr_num_new,0,0,good_type,type);
				  loading();
		   }
		}
		else{
          if(type == 1){
            if(pr_num_new > 0){
                obj.next().attr("value",pr_num_new);
                //商品总价
                var all_price = (parseFloat(all_pdt_price) - parseFloat(pdt_sale_price)).toFixed(2);
                
                $("#pdt_price").html("<i class='price'>￥</i>  "+all_price);
                $("#all_pdt_price").val(all_price);
                $("#all_pdt_price").attr("pdt_price",all_price);
                $("#top_all_price").html(all_price);
                loading();
                //小计总价
                var all_smail_price= ((pr_num_new) * (smail_price)).toFixed(2);
                //当前货品的小计
                obj.parent().parent().next().next().html("<i class='price'>￥</i>  "+all_smail_price);
                //货品的折扣总价
                var discount =  ((parseFloat(all_price_dis) - parseFloat(m_price)).toFixed(2));
                $("#strong_all_price").html("<i class='price'>￥</i> "+ discount);
                $("#all_price_dis").val(discount);
                //优惠的总价
                var all_dis = (all_price - discount).toFixed(2);
                $("#label_pre_price").html("<i class='price'>￥</i>"+all_dis);
                var  pdt_num =obj.next().attr('value');
                doEdit(obj,pdt_id,pdt_num,all_price,all_dis,good_type);
                loading();
              }else{
                return false;
              }  
          }else{
            if(pr_num_new <= stock){
                //赋值数量
                obj.prev().prev().attr("value",pr_num_new);
                //小计总价
                var all_smail_price= ((pr_num_new) * (smail_price)).toFixed(2);
                //商品总价
                var all_price = (parseFloat(all_pdt_price) + parseFloat(pdt_sale_price)).toFixed(2);
                $("#pdt_price").html("<i class='price'>￥</i>  "+ all_price);
                $("#all_pdt_price").val(all_price);
                $("#all_pdt_price").attr("pdt_price",all_price);
                $("#top_all_price").html(all_price);
                loading();
                //当前货品的小计
                obj.parent().parent().next().next().html("<i class='price'>￥</i>  "+all_smail_price);
                //货品的折扣总价
                var discount =  ((parseFloat(all_price_dis) + parseFloat(m_price)).toFixed(2));
                $("#strong_all_price").html("<i class='price'>￥</i> "+ discount);
                $("#all_price_dis").val(discount);
                //优惠的总价
                var all_dis = (all_price - discount).toFixed(2);
                $("#label_pre_price").html("<i class='price'>￥</i>"+all_dis);
                var pdt_num =obj.prev().prev().attr('value');
                doEdit(obj,pdt_id,pdt_num,all_price,all_dis,good_type);
                loading();
            }else{
                return false;
            }
          }
	   }
    });
	//提交匹配结果
    $("#productMatch").live('click',function(){
		$("#tip_products").dialog('destroy');
        var arrayObj = new Array();
        var product_id = $("#productSelectForm .pageQuickDTwo .pQuickDTwoConRight .products_class input");
        var b2b_pdt_sn_info = {};
        b2b_pdt_sn_info['pdt_id'] = {};
        b2b_pdt_sn_info['pdt_sn'] = {};
        $.each(product_id,function(i,goods){
            var value = $(goods).val();
            var pdt_stock = $(goods).attr("stock");
            var pdt_sn = $(goods).attr("pdt_sn");
            var pdt_id = $(goods).attr("pdt_id");
            var g_id = $(goods).attr("g_id");
            var pdt_spec_value = $(goods).attr("pdt_spec_value");
            var g_name = $(goods).attr("g_name");
            var g_picture = $(goods).attr("g_picture");
            var g_sn = $(goods).attr("g_sn");
            var pdt_sale_price = $(goods).attr("pdt_sale_price");
            var price = $(goods).attr("pdt_price");
            var pdt_weight = $(goods).attr('pdt_weight');
            if(value > 0 && value != ''){
                b2b_pdt_sn_info['pdt_id'][pdt_id]= value;
                b2b_pdt_sn_info['pdt_sn'][pdt_sn]= value;
                var Str = value+','+pdt_stock+','+g_name+','+pdt_id+','+g_id+','+pdt_sale_price+','+price+','+pdt_sn+','+g_sn+','+g_picture+','+g_name+','+pdt_spec_value+','+pdt_weight;
                var aryStr = Str.split(",");
                arrayObj.push(aryStr);					
            }
        });
        var order_id = $("#order_id").val();
        var tt_id = $("#order_id").attr("tt_id");
        var num_iid = $("#order_id").attr("num_iid");
        var url = '/Ucenter/Trdorders/saveTrdMatch';
        $.ajax({
            url:url,
            cache:false,
            dataType:'JSON',
            data:{
                'num_iid':num_iid,
                'b2b_pdt_sn_info':b2b_pdt_sn_info,
                'tt_id':tt_id,
                'to_id':order_id
            },
            type:'POST',
            success:function(msgObj){
                if(msgObj.success == 1){
					window.parent.location.reload();
                    return false;
                }else{
                    showAlert(false,msgObj.msg);
                    return false;
                }
            }
        });
        
    });

	/***
     *标记处理
     *@author zhangjiasuo
     *@date 2013-09-11
     */
    $("#mark_handle").die().click(function(){
        var tt_id = $(this).parent().attr("tt_id");
		var shop_id = $(this).attr("ts_id");
        if(confirm("处理完成后，该订单将不予显示，确定该操作？")){
            var url = '/Ucenter/Trdorders/DoMarkHandle';
            ajaxReturn(url,{
                'tt_id':tt_id,
				'shop_id':shop_id
            },'post');
        }
    });
    
	/***
     *批量处理标记
     *@author zhangjiasuo
     *@date 2013-09-11
     */
    $("#batchMarkHandle").click(function(){
        var tt_id = '';
		var shop_id = $(this).attr("ts_id");
        $(".all_check").each(function(){
            if(this.checked){
                tt_id += $(this).parent().attr("tt_id") + ',';
            }
        });
        if(tt_id == ''){
            showAlert(false,'请先选中需要操作的订单,然后再进行批量处理');
            return false;
        }
        var url = '/Ucenter/Trdorders/DoBatchMarkHandle';
        ajaxReturn(url,{
            'tt_id':tt_id,
			'shop_id':shop_id
        },'post');
    });
	/***
     *购物车状态
     *@author zhangjiasuo
     *@date 2013-04-19
     */
	function loading(){
		var rowCount = $("#cart_tbody tr").length;
		open(rowCount);
		if(rowCount>50){
			rowCount=50;
		}
		if(rowCount <= 0){
			rowCount=1;
		}
		$("#new_current_num").html(rowCount);
		$("#current_num").attr('value',rowCount);
		var num =$("#current_num").attr('value');
		
		var w = Math.ceil((rowCount / 50) * 118);
		var innerHtmls = '<div style="min-width:118px; width:auto; min-height:5px; height:auto; border:1px solid silver; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;">';
			innerHtmls += '<div id="loading" style="height:8px; background-color:#FF9B2E; border-radius: 2px; -moz-border-radius: 2px; -webkit-border-radius: 2px;"></div></div>';
		$('#allerp').html(innerHtmls);
		$("#loading").css("width",w+'px');
	};
	/***
     *关闭购物车快满的提示
     *@author zhangjiasuo
     *@date 2013-04-17
     */
    $('.close').click(function(){
        $("#cart_full").css('display','none');
    });
    
    /***
     *开启购物车快满的提示
     *@author zhangjiasuo
     *@date 2013-04-18
     */
    function open(num){
        if(num>=40){
            $("#cart_full").css('display','block');
        }else{
            $("#cart_full").css('display','none');
        }
    };
	//搜索商品数据
    $("#search_list").live('click',function(){
        var pdt_sn = $("#pdt_sn").val();
        var g_name  = $("#g_name").val();
        var gc_id   = $("#gcid").find("option:selected").val();
        var order_tt_id = $(".class_order_tt_id").val();
        var g_sn	= $("#g_sn").val();
        var order_to_id = $(".to_id").val();
        var tt_id = $(".tt_id").val();
        var num_iid = $(".num_iid").val();
        var url = '/Ucenter/Trdorders/ProductsListpage';
        $.ajax({
            url:url,
            cache:false,
            dataType:'TEXT',
            type:'GET',
            data:{
                'pdt_sn':pdt_sn,
                'g_name':g_name,
                'gcid':gc_id,
                'tt_id':tt_id,
                'num_iid':num_iid,
                'order_tt_id':order_tt_id,
                'g_sn':g_sn,
                'order_to_id':order_to_id
            },
            success:function(msgObj){
                $("#products_data").html(msgObj);
            }
        });
    });
	/***
     *市变化物流公司联动变化
     *@author zhangjiasuo
     *@date 2013-09-13
     */
	$("#city").change(function(){
		var region = $(this).val();
        var pdt_data = $(".cartPdt");
        var order_data = {};
        $.each(pdt_data, function(i, dom){
            order_data[i] = $(dom).attr("pdt_id");
        });
        order_data['cr_id'] = region; 
        $("#logistic_dl").html('正在计算配送方式...');
        var url = '/Ucenter/Trdorders/getLogisticType/';
		$.post(url, order_data
        ,function(jsonObj){
            if(jsonObj == ''){
            	$("#logistic_dl").html('请先选择收货地区');  
            }else{
            	$("#logistic_dl").html(jsonObj);
                var lt_id = $("input[name='lt_id']:checked").val();
                thdcheckLogistic(lt_id);
            }
        },'text');
	});
	/***
     *区变化物流公司联动变化
     *@author zhangjiasuo
     *@date 2013-09-13
     */
	$("#region").change(function(){
		var region = $(this).val();
        var pdt_data = $(".cartPdt");
        var order_data = {};
        $.each(pdt_data, function(i, dom){
            order_data[i] = $(dom).attr("pdt_id");
        });
        order_data['cr_id'] = region; 
        $("#logistic_dl").html('正在计算配送方式...');
        var url = '/Ucenter/Trdorders/getLogisticType/';
		$.post(url, order_data
        ,function(jsonObj){
            if(jsonObj == ''){
            	$("#logistic_dl").html('请先选择收货地区');  
            }else{
            	$("#logistic_dl").html(jsonObj);
                var lt_id = $("input[name='lt_id']:checked").val();
                thdcheckLogistic(lt_id);
            }
        },'text');
	});

    $(".orders_config").click(function(){
        $("#tip_products").dialog({
            width:800,
            height:600,
            modal:true,
            closeOnEscape:'false',
            title:'商品匹配'
        });
        var strHtml = '';
        var order_tt_id = $(this).attr("for");
        var order_id = $(this).attr("order_id");
        var order_to_id = $(this).attr("to_id");
        var otype = $(this).attr("add_goods");
        strHtml += '<input type="hidden" value="'+order_tt_id+'" name="order_tt_id" class="class_order_tt_id"/>';
        strHtml += '<input type="hidden" value="'+order_id+'" name="tt_id" class="tt_id" class="tt_id" />';
        strHtml += '<input type="hidden" value="'+order_to_id+'" name="to_id" class="to_id" class="to_id"/>';
        strHtml += '<input type="hidden" value="'+otype+'" name="order_type" class="order_type" class="order_type"/>';
        $("#trdorder_tt_id").html(strHtml);
        return false;
    });
	
})

function OrdersDownload(obj,tsid){
    //var data = {'data':obj,'ts_id':tsid};
    var url = '/Ucenter/Trdorders/doTaobaoOrdersDownload';
	var order_minDate = $('#order_minDate').val();
	var order_maxDate = $('#order_maxDate').val();
    $.ajax({
        url:url,
        cache:false,
        dataType:'TEXT',
        type:'POST',
        data:{'data':obj,'ts_id':tsid,'min':order_minDate,'max':order_maxDate},
        beforeSend:function(){
            $("#ajax_loading").dialog({
                height:150,
                width:315,
                modal:true,
                title:'提示：努力加载中'
            });
        },
        success:function(msgObj){
            $("#ajax_loading").dialog('close');
            $("#downloag_orders").dialog({
                width:500,
                title:'提示：订单下载',
                modal: true,
                close:function (){
                    $("#downloag_orders").dialog('destroy');
                    //$('#alert_div').append($('#downloag_orders'));
                }
            });
            $("#downloag_orders").html(msgObj);
            setTimeout(function(){
                location.reload();
            }, 2000)
        }
    });
}
//分销是未发货，淘宝已发货时更新
function UpdataOrdersStatus(obj,tsid){
    var url = '/Ucenter/Trdorders/UpdataTaobaoOrdersStatus';
    $.ajax({
        url:url,
        cache:false,
        dataType:'TEXT',
        type:'POST',
        data:{'data':obj,'ts_id':tsid},
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
            $("#downloag_orders").dialog({
                width:500,
                title:'提示：订单下载',
                modal: true,
                close:function (){
                    $("#downloag_orders").dialog('destroy');
                    $('#alert_div').append($('#downloag_orders'));
                }
            });
            $("#downloag_orders").html(msgObj);
        }
    });
}

function DateTime(obj,id){
    var startDateTextBox = $("#"+obj);
    var endDateTextBox = $('#'+id);
    startDateTextBox.datetimepicker({ 
        changeMonth: true,
        minDate: new Date(1940, 1 - 1, 1),
        yearRange: '1940:+5',
        changeYear: true,
        onClose: function(dateText, inst) {
            if (endDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    endDateTextBox.datetimepicker('setDate', testStartDate);
            }
            else {
                endDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            endDateTextBox.datetimepicker('option', 'minDate', startDateTextBox.datetimepicker('getDate') );
        }
    });
    endDateTextBox.datetimepicker({ 
        changeMonth: true,
        changeYear: true,
        onClose: function(dateText, inst) {
            if (startDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    startDateTextBox.datetimepicker('setDate', testEndDate);
            }
            else {
                startDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            startDateTextBox.datetimepicker('option', 'maxDate', endDateTextBox.datetimepicker('getDate') );
        }
    }); 
}

function matchLogisticsCompanys(province, city, district, tt_id, adderss){
    var match = 0;
    var ismatch = 0;
    var delmatch = 0;
    //获取订单中货品个数
    $("#tableNei_"+tt_id).each(function(){
		match = parseInt($(this).find(".match").length);
		ismatch = parseInt($(this).find(".ismatch").length);
		delmatch = parseInt($(this).find(".delmatch").length);
	});
    if(match == delmatch){
        showAlert(false,"此订单中商品已被全部删除，不可匹配");
        return false;
    }
	if(!filter(tt_id)){
		showAlert(false, "此订单还没匹配商品！");
		return false;
	}
    var city_id = $('#city').val();
    var region_id = $('#region').val();
    var totalNum = 0;            //购买总数量
    var totalWeight = 0.00;         //商品总重量
    var goods_info = {};
    goods_info['pdt_num'] = {};
    goods_info['pdt_weight'] = {};
    var address_id = 0;
    if(region_id && region_id > 0) {
        address_id = region_id;
    }else if(city_id){
        address_id = city_id;
    }
    $("#tableNei_"+tt_id+" tr").each(function(){
	if ($(this).is(".list_orders")){
		var weight = parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
		var num = parseInt($(this).find(".inputNum").val());
		if(isNaN(weight)){
		   weight = 0.00;
		}	
		if(isNaN(num)){
		   num = 0;
		}		
        totalNum += num;
		totalWeight +=parseFloat(weight) * parseInt(num);
        //totalWeight += parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
	}
    });
    var totalPrice = $("#price_"+tt_id).attr("gprice");
    goods_info['pdt_num'] = totalNum;
    goods_info['pdt_price'] = totalPrice;
    goods_info['pdt_weight'] = totalWeight.toFixed(2);
    var url = '/Ucenter/Trdorders/getAvailableLogisticsList';
    $.ajax({
        url:url,
        cache:false,
        dataType:'TEXT',
        type:'POST',
        data:{
            'province':province,
            'city':city,
            'district':district,
            'address_id': address_id,
            'goods_info':goods_info,
            'autoTrd':'1',
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
            $("#configdilivery").dialog({
                width:845,
                title:'提示：匹配物流公司',
                modal: true,
                close:function (){
                    $("#configdilivery").dialog('destroy');
					$('#pro_diglog').append($('#configdilivery'));
                    //$('#alert_div').append($('#configdilivery'));
                }
            });
            $("#configdilivery").html(msgObj);
        }
    });
}

function selectCityRegion(obj, item, default_value) {
    var value = obj.value;
    if (!value) {
        value = obj;
    }
    if (value == 0) {
        $('#region').html('<option value="0">请选择</option>');
        return false;
    }
    var url = '/Ucenter/Address/getCityRegion';
    $('#' + item).load(url, {
        'parent': value, 
        'item': item,
		'val': default_value
    }, function(html) {
        if ('' != default_value) {
            this.value = default_value;
        }
        if(html == '') {
            $(this).css({
                'validate': '',
                'display':'none'
            });
        }else{
            $(this).css({
                'validate': '{required:true}',
                'display':'inline'
            });
        }

    });
}
function initSelectCityRegion() {
    $('#city').html('<option value="0">请选择</option>');
    $('#region').html('<option value="0">请选择</option>');
}

//确定批量支付
function batchOrder(){
    var trdOrder	= {};
    var products	= [];
    var sign	= 1;
    var trdid	= 0;
    var payment	= 0;
    //获取支付方式
    $("#payment_selected .payment_cfg").each(function(){
        if($(this).is(":checked")) {
            payment = $(this).val();
            return false;
        }
    });
    if(0 == payment){
        showAlert(false,'请选择支付方式！');
        return false;
    }
    $(".orderBody .list_li .orderPnei input[name='all_check']:checked").each(function(){
        //来源单号
        var tt_id = $(this).attr("tt_id");
        trdOrder[trdid]	= {};
        //alert(tt_id);
        trdOrder[trdid][tt_id]	= {};
        //物流公司
        var logistics	= $("#logistics_"+tt_id).attr('lt_id');
        trdOrder[trdid][tt_id]['logistics']	= parseInt(logistics);
        //物流配送配置
        var log_conf	= $("#logistics_"+tt_id).attr('conf');
        var o_remark    = $("#message_"+tt_id).html();
        trdOrder[trdid][tt_id]['o_remark']	= o_remark;
        trdOrder[trdid][tt_id]['log_conf']	= log_conf;
        //本条订单里面商品列表的长度
        var trd_length = $("#tableNei_"+tt_id+" .match").length;
        //本条订单里面已经匹配的商品数组的长度
        var trd_length_matched = $("#tableNei_"+tt_id+" .ismatch").length;
        //本条订单里面已经删除的商品数组的长度
        var trd_length_deleted = $("#tableNei_"+tt_id+" .delmatch").length;
        if(parseInt(trd_length) == parseInt(trd_length_deleted)){
            sign = 0;
            showAlert(false,"订单"+tt_id+"中有商品已被删除");
            return false;
        }
        //alert(trd_length_deleted); return false;
        if(parseInt(trd_length) <= (parseInt(trd_length_matched) + parseInt(trd_length_deleted))){
            $("#tableNei_"+tt_id+" .inmatched .inputNum").each(function(){
                var trd_input	= $(this);
                if(trd_input.attr('name') == 'nums'){
                    var trd_class = $("#tableNei_"+tt_id+" .delmatch");
                    //获取商品是否被删除
                    var trd_val = trd_class.attr("is_del");
                    //淘宝货品号
                    var num_iid	= trd_input.attr('num_iid');
                    if(typeof(trdOrder[trdid][tt_id][num_iid]) != 'object') {
                        trdOrder[trdid][tt_id][num_iid]	= {};
                    }
                    //b2b货品号
                    var pdt_sn	= trd_input.attr('pdt_sn');
                    var nums	= trd_input.val();
                    //订单明细表里存放的货品加属性名称
                    var oi_g_name	= trd_input.attr('g_name');
                    //货品对应的商品
                    var g_id		= trd_input.attr('g_id');
                    //货品价格
                    var pdt_sale_price	= trd_input.attr('gprice');
                    //alert(pdt_sale_price*nums);
                    //货品id
                    var pdt_id		= trd_input.attr('pdt_id');
                    //物流公司标记
                    var dt_id		= $("#logistics_"+tt_id).attr('lt_id');
                    //货品重量
                    var pdt_weight	= trd_input.attr('pdt_weight');
					//获取第三方订单明细ID
					var toi_id		= trd_input.attr('toi_id');
                    if(parseInt(trd_val) == 1){
                        //sign = 0;
                        //showAlert(false,"订单"+tt_id+"中有商品已被删除");
                        //return false;
                    }
                    if(parseInt(nums) < 1){
                        //alert(nums);
                        sign	= 0;
                        showAlert(false,"商品数量不合法！");
                        return false;
                    }
                    if(parseInt(logistics) == 0){
                        sign	= 0;
                        showAlert(false,'订单：'+tt_id+' 物流方式未配置！');
                        return false;
                    }
                    if(oi_g_name.length <= 0){
                        sign = 0; 
                        showAlert(false,"订单"+tt_id+"尚未匹配");
                        return false;
                    }
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]	= {};
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['num_iid']		= num_iid;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['pdt_sn']		= pdt_sn;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['nums']			= nums;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['oi_g_name']	= oi_g_name;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['g_id']			= g_id;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['pdt_id']		= pdt_id;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['dt_id']		= dt_id;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['pdt_sale_price']	= pdt_sale_price;
                    trdOrder[trdid][tt_id][num_iid][pdt_sn]['pdt_weight']	= pdt_weight;
					trdOrder[trdid][tt_id][num_iid][pdt_sn]['toi_id']	= toi_id;
                    trdOrder[trdid]['tt_id']	= tt_id;
                }
            });
        }else{
            sign = 0; 
            showAlert(false,"订单"+tt_id+"尚未匹配");
            return false;
        }
        trdid++;
        if(sign	== 0) {
            return false;
        }
    });
    if(sign	== 0) {
        return false;
    }
    //检测是否选中了订单
    for(var i=0 in trdOrder){
        i++;
    }
    if(i == 0){
        showAlert(false,"请选择需要提交的订单");
        return false;
    }
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
    autoTrdOrder(trdOrder,0,trdid-1,payment);
}

function Close(){
    $("#ajax_loading").dialog('destroy');
    $('#pro_diglog').append($('#ajax_loading'));
}

function autoTrdOrder(trdOrder,i,count,payment){
    if( i> count) {
        $("#ajax_loading").append('<div><table><tr><td width="100"><button type="button" onclick="window.history.go(0);" class="blue" style="color: rgb(255, 255, 255); background-color: rgb(23, 144, 203); text-decoration: none;">完 成</button></td><td><button type="button" class="blue" style="color: rgb(255, 255, 255); background-color: rgb(23, 144, 203); text-decoration: none;" onclick="Close();">关 闭</button></td></tr></table></div>');
        $("#ajax_loading #ajaxsenddiv_loading").html('提交完成！');
		window.location.reload();
        return false;
    }
    var tt_id = trdOrder[i]['tt_id'];
    delete(trdOrder[i]['tt_id']);
    $.ajax({
        url:'/Ucenter/Trdorders/batchAddTrdOrder',
        dataType:'json',
        type:'POST',
        cache:false,
        data:
        {
            'order':trdOrder[i],
            'payment':payment
        },
        beforeSend:function(){
            $("#ajax_loading").append('<div id="ajaxsend_'+tt_id+'"><br>订单'+tt_id+'正在提交...</div>');
        },
        success:function(json){
            if(json.success == 1){
                $("#ajax_loading #ajaxsend_"+tt_id).html('<br><div>订单'+tt_id+'<span style="color:green;">提交成功！</span></div>');
                //window.location.reload();
            }else{
                $("#ajax_loading #ajaxsend_"+tt_id).html('<br><div><span style="color:red;">'+json.msg+'</span></div>');
            }
            i++;
            autoTrdOrder(trdOrder,i,count,payment);
        }
    });	
}

function doEdit(obj,pdt_id,pdt_nums,all_prices,all_dis,good_type,type){
	var a_toadd = obj.next();
	addNum(a_toadd,"input");
	//物流配送配置
	var log_conf = $("#logistics_"+tt_id).attr('conf');
	//物流公司
	var logistics = $("#logistics_"+tt_id).attr('lt_id');
	if(0 != logistics && "" != log_conf){
		changeLogis(obj);
	}
	$.post("/Ucenter/Trdorders/doEdit",{
		"pdt_id":pdt_id,
		"pdt_nums":pdt_nums,
		"good_type":good_type,
		"all_price":all_prices,
		"all_dis":all_dis
	},function(data){
		//更新消耗积分
		if(good_type == 1){
			if(data.stauts == true){
			     if(typeof type!='undefined' && type) $('#nums_'+pdt_id+'_'+good_type).val(pdt_nums);
			     var single_point = $('#single_point_'+pdt_id+'_'+good_type).html();
				 var point = parseInt(single_point) || 0;//单个商品花费积分
				 var singlepoints = parseInt(point*pdt_nums);
				 $('#totalpoint_'+pdt_id+'_'+good_type).html(singlepoints);
				 var cart_tr = $('#cart_tbody tr');
				 var total_consume_point = 0;
				 if(cart_tr.length>0){
				    cart_tr.each(function(){
					     var _this = $(this);
						 var _inputNum = _this.find("input.inputNum"),
							  good_type = _inputNum.attr('good_type');
							
						if(good_type == 1){
						    var num = _inputNum.val() || 0,
							    single_pdt_id = _inputNum.attr('pdt_id'),
							    point = _this.find('#single_point_'+single_pdt_id+'_'+good_type).html() || 0;
								total_consume_point += parseInt(num*point);
						}
					});
				 }
				 $('#li_i_consume_point').html(total_consume_point);
                 $('#old_nums_'+pdt_id+'_'+good_type).val(pdt_nums);
			  }else{
                 var old_nums =  $('#old_nums_'+pdt_id+'_'+good_type).val() || 0;
                 $('#nums_'+pdt_id+'_'+good_type).val(old_nums);
				 $("#jf_msg").fadeIn('slow');
             }
		} 
	   else if(good_type == 0){
			var all_price = $('#top_all_price').html() || 0,
				consumed_ratio = $('#consumed_ratio').val() || 0;
			$('#li_i_reward_point').html(Math.round(all_price*consumed_ratio));
			if(data.promotion_result_name != null){
				$('#promition_rule_name').html(data.promotion_result_name);
				$('#promition_rule_name').fadeIn('slow');
				del_gifts_tr();
				add_gifts_tr(data.cart_gifts_data);
			}else{
				$('#promition_rule_name').fadeOut('slow');
				$('#promition_rule_name').html('');
				del_gifts_tr();
			}
			
			//订单促销
			$("#label_pre_price").html("<i class='price'>￥</i>"+(data.pre_price));
			$("#strong_all_price").html("<i class='price'>￥</i>"+data.price);
	   }
    },'json');
};

/***
 *物车选购数量变化赠品动态添加
 *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
 *@date 2013-07-02
 */

    function add_gifts_tr(cart_gifts_data) {
        var tr_html = '';
        $.each(cart_gifts_data, function(idx, items) { //alert(items.pdt_id);
            tr_html += "<tr class='giftscartPdt'>";
            tr_html += "<td width='40'><input type='hidden' name='type[]' value=" + items.type + "> </td>";
            tr_html += "<td width='82' valign='top'><div class='cartProPic'><a href='#'>";
            tr_html += "<img width='68' height='68' src=" + items.g_picture + ">";
            tr_html += "<input type='hidden' value=" + items.pdt_sale_price + " id=" + 'pdt_price' + items.pdt_id + "></a></div></td>";
            tr_html += "<td width='332' align='left'><div class='cartProName'><a href='javascript:void(1)'>" + items.g_name + "</a>";
            tr_html += "<span>商品编码：" + items.pdt_sn + "</span>";
            if (items.pdt_spec == '') {
                tr_html += "<span>规格：</span></div></td>";
            } else {
                tr_html += "<span>规格：" + items.pdt_spec + "</span></div></td>";
            }
            tr_html += "<td width='81' value=" + items.f_price + "><i class='price'>￥</i>" + items.pdt_sale_price + "<br></td>";
            tr_html += "<td width='122'><p><span class='brownblock marTop5'>库存" + items.pdt_stock + "</span></p></td>";
            tr_html += "<td width='108'>赠品</td>";
            tr_html += "<td width='97' id=" + 'xiao_price' + items.pdt_id + " value=" + items.pdt_sale_price + " >";
            tr_html += "<i class='price'>￥</i>" + items.pdt_sale_price + "</td><td></td>";
            tr_html += "</tr>";
        });
        $("tbody").append(tr_html);
    }

    /***
     *物车选购数量变化赠品动态删除
     *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     *@date 2013-07-02
     */

    function del_gifts_tr() {
        $("[class=giftscartPdt]").each(function() {
            $(this).remove();
        });
    }

/***
 *选择物流公司
 *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
 *@date 2013-07-02
 */
  
//选择物流公司
function thdcheckLogistic(obj){
    var url = '/Ucenter/Trdorders/ChangeLogistic/';
    $.post(url,{'lt_id':obj},function(jsonData){
            if(jsonData.status){
                var logistic_money = (parseFloat(jsonData.logistic_price)).toFixed(2);
                var promotion_price = (parseFloat(jsonData.promotion_price)).toFixed(2);
                var coupon_price = 0;
                var logistic_delivery=jsonData.logistic_delivery;
                var total_good_price = (parseFloat(jsonData.goods_total_sale_price)).toFixed(2);
                $('#logistic_price_'+obj).html(parseInt(logistic_money));
                $("#logistic_price").html('<i class="price" >￥</i>'+logistic_money);
                $("#promotion_price").html('<i class="price" >￥</i>'+promotion_price);
                $("#all_orders_promotion_price").attr('value',parseFloat(jsonData.promotion_price));
                //var all_orders_price = (parseFloat(jsonData.all_price)+parseFloat(logistic_money)-parseFloat(promotion_price)).toFixed(2);
                var all_orders_price = (parseFloat(jsonData.all_price)+parseFloat(logistic_money)).toFixed(2);
                if(all_orders_price < 0 ){
                    all_orders_price = '0.00';
                }
                $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
                $("#total_good_price").html(total_good_price);
                $("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
                if(logistic_delivery==true ){
                    $("#o_payment6").parent().remove();
                    var showHtml='<dd><input type="radio" onclick="payradio($(this))" value="6" name="o_payment" id="o_payment" validate="{ required:true}">';
                        showHtml +='<input type="hidden" value="货到付款" id="o_payment6" name="o_payment6">';
                        showHtml +='<label for="zhifu">货到付款</label><span>&nbsp;&nbsp;货到付款</span></dd>';  
                    $("#payment_list").append(showHtml);
                }else{
                    var showHtml=$("#o_payment6").parent().html();
                    if(showHtml!=''){
                        $("#o_payment6").parent().remove();
                    }
                }
            }           
    },'json');
};

//提交订单
function submitThdOrders(){
	$('#submit_order').attr('disabled','disabled');
    var url = '/Ucenter/Trdorders/thddoAdd';
    var res = $('#orderForm').valid();
    if(res){
        var invoice_check = $("#invoice_show").css('display');
        if(invoice_check == 'block'){
			$('#submit_order').attr('disabled',false);
            showAlert(false,"请将发票信息填写完整后再提交订单");
            return false;
        }
		var ra_phone = $('#ra_phone').val();
		var ra_mobile_phone = $('#ra_mobile_phone').val();
		if(ra_phone=='' && ra_mobile_phone=='' ){
			var msg='<label class="error" for="ra_phone" generated="true">手机和固定电话两者至少写一项</label>';
			$('.gray').html(msg);
			$('#submit_order').attr('disabled',false);
			return false;
		}
        //发送ajax请求
        var data = $('#orderForm').serialize();
        ajaxReturn(url,data,'post');
    }
}

//编辑收货地址
function editAddress(to_id) {
	var editAddress_to_id = $("#editAddress_"+to_id);
	var shop_id = $("#shop_id").val();
	var tt_id = editAddress_to_id.attr("tt_id");
	$.ajax({
		url: '/Ucenter/Trdorders/thdAddressPage/',
		data:{"to_oid":tt_id},
		dataType:"HTML",
		type:"POST",
		cache: false,
		beforeSend:function(){
            $("#ajax_loading").dialog({
                height:150,
                width:315,
                modal:true,
                title:'提示：努力加载中'
            });
        },
		success:function(msg){
			$("#ajax_loading").dialog("close");
			editAddress_to_id.html(msg);
			var address = $("#receive_address_"+to_id).html();
			if("" != editAddress_to_id.find("textarea").val()){
				address = editAddress_to_id.find("textarea").val();
			}
			editAddress_to_id.find("textarea").val(address);
			$("#editAddress_"+to_id).dialog({
				width:600,
				height:'auto',
				modal:true,
				title:'编辑收货地址',
				closeOnEscape:'false',
				close:function (){
					$(this).dialog("destroy");
				},
				buttons:{
					'确定':function(){
						$(this).dialog("destroy");
						var address = editAddress_to_id.find("textarea").val();
						var to_receiver_province = $("#editAddress_"+to_id+" #province_"+tt_id+" option:selected").html();
						var to_receiver_city = $("#editAddress_"+to_id+" #city_"+tt_id+" option:selected").html();
						var to_receiver_district = $("#editAddress_"+to_id+" #region_"+tt_id+" option:selected").html();
						if(to_receiver_province == '请选择' || to_receiver_city=='请选择'){
							showAlert(false,'收货地址还未加载出来,请在收货地址加载之后再处理');return;
							//alert('收货地址还未加载出来,稍等一会');return;
						}
						var detail_address = to_receiver_province + to_receiver_city + to_receiver_district + address;
						$("#receive_address_"+to_id).html(detail_address);
						$.ajax({
							url:"/Ucenter/Trdorders/editReceiveAddress",
							data:{"tsid":shop_id,"to_id":to_id,"to_receiver_province":to_receiver_province,"to_receiver_city":to_receiver_city,"to_receiver_district":to_receiver_district,"to_receiver_address":address},
							type:"POST",
							dataType:"JSON",
							success:function(msgObj){
								if(msgObj.status === true){
									showAlert(true, msgObj.msg);
									$("#logistics_"+tt_id).html("为选择请先进行配置").attr("style","");
									$("#confLogis_"+tt_id).show();
									$("#fill_"+tt_id).hide();
								}else{
									showAlert(false, msgObj.msg);
								}
							}
						});
					},
					"取消": function() {
						$(this).dialog("destroy");
					}
				}
			});
		}
	});
	
}

//修改卖家备注
function flag(to_id, obj) {
	$("#to_seller_memo_" + to_id).dialog({
		width:330,
		height:'auto',
		modal:true,
		title:'修改卖家备注',
		closeOnEscape:'false',
		buttons:{
			"确定":function(){
				var objElement = $("#to_seller_memo_" + to_id);
				var to_oid = $(obj).attr("tt_id");
				var ts_id = $(obj).attr("ts_id");
				var src = $(obj).attr("src");
				var remark = objElement.find("textarea").val();
				var selectFlag =objElement.find("input[name='flag_type"+to_id+"']:checked").val();
				var res = src.replace(/\d/, selectFlag);
					$(obj).attr("src",res);
				var url = '/Ucenter/Trdorders/updateMemo';
				var data = {to_oid:to_oid,memo:remark,seller_flag:selectFlag,ts_id:ts_id,data:'taobao'};
				myAjaxReturn(url, data, function(result){
					if(result.status === true){
						showAlert(true, result.msg);
                        $("#seller_memo_show_" + to_id).html(remark);
					}else{
						showAlert(false, result.msg);
					}
				});
				$(this).dialog("close");
			},
			"取消": function() {
				$(this).dialog("close");
			}
		}
	});
}

$(document).ready(function(){
	//批量修改订单旗帜
	$("#batchModifyFlag").click(function(){
		if($("#allCho").is(":checked")){
			$(".all_check").attr('checked',true);
		}
		$("#selectFlag").dialog({
			width:310,
			height:'auto',
			modal:true,
			title:'批量修改旗帜',
			closeOnEscape:'false',
			buttons:{
				'确定':function(){
					if(!$("input[name='selectFlag']").is(":checked")){
						showAlert(false, "请选择旗帜！");
						return false;
					}
					var arr = [];
					var flag_val = $("input[name='selectFlag']:checked").val();
					$("input[name='all_check']:checked").each(function(i){
						var imgElement = $(this).siblings("label").children("img");
						var img = $(this).siblings("label").children("img").attr("src");
						var res = img.replace(/\d/, flag_val);
						imgElement.attr("src",res);
						arr[i] = $(this).attr("tt_id");
					});
					$(this).dialog("close");
					myAjaxReturn('/Ucenter/Trdorders/batchEditFlag', {"to_oid":arr,"to_temp_seller_flag":flag_val}, function(result){
						if(result.status === true){
							showAlert(true, "批量修改旗帜成功！");
						}else{
							showAlert(false, "批量修改旗帜失败！");
						}
					});
				},
				"取消":function() {
					$(this).dialog("close");
				}
			}
		});
	});
	//旗帜同步到淘宝
	$("#SynchronousFlag").click(function(){
		var shop_id = $("#shop_id").val();
		var arr = [];
		//截取旗帜索引
		var flag_img = $("input[name='all_check']:checked").siblings("label").children("img").attr("src");
		var img_length = flag_img.length;
		var position = img_length-5;
		//var flag_val = flag_img.charAt(position);
		var flag_val = [];
		$("input[name='all_check']:checked").each(function(i){
			arr[i] = $(this).attr("tt_id");
			flag_val[i] = $(this).siblings("label").children("img").attr("seller_flag");
		});
		if(0 == arr.length){
			showAlert(false, "请勾选要同步的订单！");
			return false;
		}
		if(confirm("确定要同步旗帜到淘宝？")){
			myAjaxReturn('/Ucenter/Trdorders/updateMemo', {"to_oid":arr,"data":"taobao","ts_id":shop_id,"seller_flag":flag_val,"identify":"SynchronousFlag"},function(result){
				if(result.status === true){
					showAlert(true, "同步旗帜成功！");
				}else{
					showAlert(false, "同步旗帜失败！");
				}
			});
		}
	});
});

//核对是否勾选订单
function selectFlag() {
	if(!$(".all_check").is(":checked")){
		showAlert(false, "请勾选订单！");
		return false;
	}
}

//异步请求函数封装
var myAjaxReturn = function(url, data, callback, type) {
	$.ajax({
		url:url,
		data:data,
		dataType:"JSON",
		type: arguments.length > 3 ? type : "POST",
		success: function(result) {
			callback(result);
		}
	});
}

//批量配置物流公司
var lc_id;	//配送公司ID
var tt_id;	//交易编号ID
function batchmatchlogistics(){
	if($("#showselectlogistics option:selected").val() == 0){
		return false;
	}else{
		//首先把物流总费用清空
		var countfree = 0;
		$("#countFree").html(parseFloat(countfree).toFixed(2));
		//过滤掉没有匹配的商品
		$(".all_check:checked").each(function(){
			var tt_id = $(this).attr("tt_id");
			if(!filter(tt_id)){
				$(this).attr("checked",false);
			}
		});
	}
	lc_id = $("#showselectlogistics option:selected").val();
	$("input[name='all_check']:checked").each(function(){
		tt_id = $(this).attr("tt_id");
        var province = $(this).attr('province');
        var city = $(this).attr('city');
        var district = $(this).attr('district');
        var adderss	 = $(this).attr('address');
        batchmatchLogisticsCompanys(province, city, district, tt_id, adderss, lc_id);
	});
}
//批量匹配物流公司是否符合配送区域
function batchmatchLogisticsCompanys(province, city, district, tt_id, adderss, lc_id){
    var match = 0;
    var ismatch = 0;
    var delmatch = 0;
    //获取订单中货品个数
    $("#tableNei_"+tt_id).each(function(){
        match = parseInt($(this).find(".match").length);
        ismatch = parseInt($(this).find(".ismatch").length);
        delmatch = parseInt($(this).find(".delmatch").length);
    });
    if(match == delmatch){
        showAlert(false,"此订单中商品已被全部删除，不可匹配");
        return false;
    }
    var city_id = $('#city').val();
    var region_id = $('#region').val();
    var totalNum = 0;            //购买总数量
    var totalWeight = 0.00;         //商品总重量
    var goods_info = {};
    goods_info['pdt_num'] = {};
    goods_info['pdt_weight'] = {};
    var address_id = 0;
    if(region_id && region_id > 0) {
        address_id = region_id;
    }else if(city_id){
        address_id = city_id;
    }
	/**
    $("#tableNei_"+tt_id).each(function(){
		alert(1);
        totalNum += parseInt($(this).find(".inputNum").val());
        totalWeight += parseFloat($(this).find(".pdt_weight").attr("pdt_weight_couts"));
        if(isNaN(totalWeight)){
            totalWeight = 0.00;
        }
    });
	**/
	$("#tableNei_"+tt_id+" tr").each(function(){
		if ($(this).is(".list_orders")){
			var weight = parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
			var num = parseInt($(this).find(".inputNum").val());
			if(isNaN(weight)){
			   weight = 0.00;
			}	
			if(isNaN(num)){
			   num = 0;
			}		
			totalNum += num;
			totalWeight +=parseFloat(weight) * parseInt(num);
			//totalWeight += parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
			if(isNaN(totalWeight)){
				totalWeight = 0.00;
			}
		}
    });
    var totalPrice = $("#price_"+tt_id).html();
    goods_info['pdt_num'] = totalNum;
    goods_info['pdt_price'] = totalPrice;
    goods_info['pdt_weight'] = totalWeight.toFixed(2);
    var url = '/Ucenter/Trdorders/batchGetAvailableLogisticsList';
    $.ajax({
        url:url,
        cache:false,
        dataType:'HTML',
        type:'POST',
        data:{
            'province':province,
            'city':city,
            'district':district,
            'address_id': address_id,
            'goods_info':goods_info,
            'autoTrd':'1',
            'tt_id':tt_id,
			'lc_id':lc_id
        },
        success:function(msgObj){
			if(msgObj == 'error'){
				//之前匹配的物流公司清除
			    $("#logistics_"+tt_id).attr('lt_id',0);
				//匹配的描述更改
				$("#logistics_"+tt_id).html("物流匹配失败！");
				$("#logistics_"+tt_id).css("color", "red");
				$("#confLogis_"+tt_id).show();
				$("#fill_"+tt_id).hide();
				//匹配失败后价格清除
				var str = 0;
				$("#free_"+tt_id).html(str.toFixed(2));
				return false;
			}else{
				$("#batchexecmatch").html(msgObj);
				$("#logistics_"+tt_id).css("color", "#1990CA");
                var lt_id = $('#batchexecmatch').find('.selectLog').attr('lt_id');
				execbatchmatch(lt_id,tt_id);
			}			
        }
    });
}
//执行批量匹配物流
function execbatchmatch(lc_id,tt_id) {
	//var log_weight = 0;
	var str_name = '';
	var conf = '';
	var log_free = 0;
	var free = 0;
	//log_weight = $(".selectLog").attr("weight");
	str_name = $(".selectLog").attr("lc_name");
	free = $(".selectLog").attr("total_freight_cost");
	conf = $(".selectLog").attr("conf");
	//log_weight = (parseFloat(log_weight)).toFixed(2);
	log_free = parseFloat(free).toFixed(2);
	$("#free_"+tt_id).html(free);
	$("#logistics_"+tt_id).attr('logistics','1');
	$("#logistics_"+tt_id).attr('conf',conf);
	$("#logistics_"+tt_id).attr('lt_id',lc_id);
	$("#logistics_"+tt_id).html(str_name);
	$("#logistics_"+tt_id).siblings("#confLogis_"+tt_id).hide();
	$("#logistics_"+tt_id).siblings("#fill_"+tt_id).show();
	Free(tt_id,log_free);
}
//计算物流总费用
function Free(tt_id,free){
	var countFree = $("#countFree").html();
	countFree = (parseFloat(countFree)).toFixed(2);
	free = (parseFloat(free)).toFixed(2);
	$("#free_"+tt_id).html(free);
	var free_price = (parseFloat(countFree) + parseFloat(free)).toFixed(2);
	$("#countFree").html(free_price);
}

//过滤没有匹配的商品公共函数
function filter(tt_id) {
	//本条订单里面商品列表的长度
	var trd_length = $("#tableNei_"+tt_id+" .match").length;
	//本条订单里面已经匹配的商品数组的长度
	var trd_length_matched = $("#tableNei_"+tt_id+" .ismatch").length;
	//本条订单里面已经删除的商品数组的长度
	var trd_length_deleted = $("#tableNei_"+tt_id+" .delmatch").length;
	if(parseInt(trd_length)-parseInt(trd_length_deleted) > parseInt(trd_length_matched)){
		return false;
	}else{
		return true;
	}
}

function addNum(obj,inputVal) {
	var gprice = $(obj).prev("input").attr("gprice");              //货品单价
	var toi_id = $(obj).prev("input").attr("toi_id");
	var pdt_weight = $(obj).prev("input").attr("pdt_weight");
	var tt_id = $(obj).prev("input").attr("tt_id");
	var stock = $(obj).prev("input").attr("stock");
	var num = $(obj).prev("input").val();
	var currentPrice = 0;                   //当前商品总价
	var finalPrice = 0;                     //最终商品总价
	var totalPrice = 0;                       //当前购买所有商品总价格
	var tprice = 0;                         //商品总价
	//当前商品总价  = 商品购买数量*商品单价
	currentPrice = (num * parseFloat(gprice)).toFixed(2);
    if("add" == inputVal){
        if(parseInt(num) < parseInt(stock) && !isNaN(num)){
            totalPrice = parseFloat($("#price_"+tt_id).attr("gprice"));
            tprice = ((parseFloat(totalPrice)+parseFloat(gprice))).toFixed(2);
            $("#price_"+tt_id).attr("gprice",tprice);
            $("#price_"+tt_id).html(tprice);
        }
        
    }
	if("input" == inputVal || "no" == inputVal){
		num;
	}else{
		num ++;
	}
	if(num <= 0 || isNaN(num)){
		num = 1;
	}
	if(parseInt(stock)<parseInt(num)){
		num = stock;
	}
	 //总重量
	pdt_weight_total = (num * pdt_weight).toFixed(3);
	finalPrice = (num * parseFloat(gprice)).toFixed(2);
	$(obj).prev("input").val(num);
   // $("#tableNei_"+tt_id+" #toids_"+toi_id+" td .tableNei .inmatched .inputNum").val(num);
	/*计算商品总价  隐藏*/
	if("input" == inputVal || "no" == inputVal){
		var last_good_price = $(obj).parents().next().next(".cgPrice").html();
		var last_total_price = $("#price_"+tt_id).attr("gprice");
		var tmp_price = parseFloat(finalPrice)-parseFloat(last_good_price);
		var now_total_price = parseFloat(last_total_price)+parseFloat(tmp_price);
		$("#price_"+tt_id).attr('gprice',now_total_price);
		$("#price_"+tt_id).html(now_total_price);
	}	
	/*var totalPriceNum = 0;
	var countPrice = parseFloat($("#price_"+tt_id).attr("gprice"));
	totalPriceNum = ((parseFloat((countPrice)))+(parseFloat(finalPrice)-parseFloat(currentPrice))).toFixed(2);
	$(".list_li").each(function(){
		var price = $(this).find(".price_label").attr("gprice");
		totalPrice += parseFloat(price);
	});
	if(totalPrice < currentPrice){
		showAlert(false,'价格存在问题');
		return false;
	}
	$("#price_"+tt_id).attr("gprice",totalPriceNum);*/
	//$("#price_"+tt_id).html(finalPrice);
	//tprice = ((parseFloat((totalPrice)))+(parseFloat(finalPrice)-parseFloat(currentPrice))).toFixed(2);
	$(obj).parents().next().next(".cgPrice").html(finalPrice);
	$(obj).parent().prev().find(".pdt_weight").attr("pdt_weight_couts",pdt_weight_total).html("重量：" + pdt_weight_total);
	//计算商品总价 隐藏掉
	//$("#countPrice").html(tprice);
	return false;
}

function changeLogis(obj,type) {
	var tt_id = 0;
	if("toadd" == obj.attr("class")){
		tt_id = obj.prev().attr("tt_id");
	}else if("toreduce" == obj.attr("class")){
		tt_id = obj.next().attr("tt_id");
	}else if("inputNum" == obj.attr("class")){
		tt_id = obj.attr("tt_id");
	}
	//来源单号
	trdOrder = {};
	trdOrder[tt_id]	= {};
	//物流配送配置
	var log_conf = $("#logistics_"+tt_id).attr('conf');
	//物流公司
	var logistics = $("#logistics_"+tt_id).attr('lt_id');
	if(0 != logistics){
		if("" != log_conf){
			/**
			var weight = 0;
			var pdt_num = 0;
            var pdt_price = 0;
			$("#tableNei_"+tt_id+" .inmatched .inputNum").each(function(){
				var pdt_weight = $(this).parent().prev().find(".pdt_weight").html();
					weight = pdt_weight.slice(3, pdt_weight.length-1);
					pdt_num	= $(this).val();
                    pdt_price = $(this).parent().siblings().last().html();
			})
			**/
			var totalNum = 0;            //购买总数量
			var totalWeight = 0.00;         //商品总重量
			$("#tableNei_"+tt_id+" tr").each(function(){
			if ($(this).is(".list_orders")){
				var weight = parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
				var num = parseInt($(this).find(".inputNum").val());
				if(isNaN(weight)){
				   weight = 0.00;
				}	
				if(isNaN(num)){
				   num = 0;
				}		
				totalNum += num;
				totalWeight +=parseFloat(weight) * parseInt(num);
				//totalWeight += parseFloat($(this).find(".pdt_weight").attr("pdt_weight"));
			}
			});
			var totalPrice = $("#price_"+tt_id).attr("gprice");
			trdOrder[tt_id]['logistics'] = parseInt(logistics);
			trdOrder[tt_id]['log_conf']	= log_conf;
			trdOrder[tt_id]['pdt_weight'] = totalWeight.toFixed(2);
			trdOrder[tt_id]['pdt_num'] = totalNum;
            trdOrder[tt_id]['pdt_price'] = totalPrice
			//trdOrder[tt_id]['pdt_sale_price'] = pdt_sale_price;
			var url = "/Ucenter/Trdorders/getLogisFree";
			myAjaxReturn(url, trdOrder, function(msgObj){
				if(msgObj.success == 1){
					if("addNums" == type){
						obj.addClass("addNums");
					}else if("reduceNums" == type){
						obj.addClass("reduceNums");
					}
					var num = parseInt(msgObj.data);
					$("#free_"+tt_id).html(num.toFixed(2));
				}
			});
		}
	}
}

//使用优惠券
function trdDoCoupon(){
  var url = '/Ucenter/Trdorders/trdDoCoupon';
  var csn = $("#coupon_input").val();
  var lt_id = $(":radio[name=lt_id][checked]").val();
  if(csn == ''){
      showAlert(false,'','优惠券不能为空');
      return false;
  }
  $.post(url,{'csn':csn,'lt_id':lt_id},function(data){
      if(data.success == 1){
         $("#msg").css({'display':''});
         $("#msg").html('');
         $("#msg").html(data.sucMsg);
         $("#msg").css({'color':'#006000'});
         var int_all_price = (parseFloat(data.all_price)).toFixed(2);
         var coupon_price = (parseFloat(data.coupon_price)).toFixed(2);
         var logistic_price = (parseFloat(data.logistic_price)).toFixed(2);
         var promotion_price = (parseFloat(data.promotion_price)).toFixed(2);
//             if(parseFloat(coupon_price)>parseFloat(int_all_price)){
//                 showAlert(false,'','优惠券金额大于订单金额！');
//                 return false;
//             }

         var int_order_price = (parseFloat(int_all_price)-parseFloat(coupon_price)).toFixed(2);
         if(parseFloat(int_order_price)<=0){
            int_order_price =0;
         }
         var all_orders_price = (parseFloat(int_order_price)+parseFloat(logistic_price)).toFixed(2);
         if(all_orders_price < 0){
             all_orders_price = '0.00';
         }
         $("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
         $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
      }else {
            var all_orders_price = (parseFloat(data.all_price)+parseFloat(data.logistic_price)).toFixed(2);
            var coupon_price = 0;
            $("#msg").css({'display':'none'});
            $("#coupon_label").html('<i class="price">￥</i>'+coupon_price);
            if(all_orders_price < 0){
                all_orders_price = '0.00';
            }
            $("#all_orders_price").html('<strong><i class="price">￥</i>'+all_orders_price);
            showAlert(false,'',data.errMsg);
            return false;
      }
  },'json');
}
