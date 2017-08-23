/**
 * 商品详情页
 * add by zhangjiasuo
 * date 2015-05-14 13:45:30
 */
//低于MixPdtStock库存显示无货
var MixPdtStock = 0;
function showSelect(obj){
    var _this = jQuery(obj);
    var item_id = $("#gid").val();
    var name = '';
    var cr_id = jQuery('#cr_ids').val();
	var open_stock = $("#open_stock").val();
	var stock_num = $("#stock_num").val();
	var stock_level = $("#stock_level").val();
    if(parseInt(cr_id) <= 0){
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    }
    if (_this && typeof _this == 'object'){
        name = _this.attr('name');
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    }
    var _item_id = jQuery('#' + item_id);
    if (_this.hasClass('on')){
        _this.removeClass("on");
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    } else{
        _this.siblings().removeClass("on");
        _this.addClass("on");
        var rsize = "";
        var showvalue = "";
        var _parent_color = jQuery("#sku" + item_id + '_1').find('a.on');
        var _parent_size = jQuery("#sku" + item_id + '_2').find('a.on');
        var color_len = _parent_color.length;
        var size_len = _parent_size.length;
        if (size_len > 0 && color_len > 0){
            $("#propError").html("");
            var color = "", size = "";
            color = _parent_color.attr('name');
            size = _parent_size.attr('name');
            if (color != '' && size != ''){
                var info = size + ";" + color;
                showvalue = arr[info]?arr[info]:"";
                var vale = showvalue.split("|");
                if (vale.length > 0){
                    if (vale[0]){
                        $("#pdt_id").val(vale[0]);
                    }
                    if(open_stock == 1 && stock_level !== ''){
						if(parseInt(vale[1]) < stock_num && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='color:red'>供货紧张</strong>");
						}else if(parseInt(vale[1]) > stock_num){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='color:green'>充足</strong>");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("<strong style='color:red'>缺货</strong>");
						}
					}else{
						if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余"+vale[1]+"件，下单后立即发货");
						}else if(parseInt(vale[1]) > 30){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
						}
					}
                    if($("#item_num").val() > vale[1]){
                        $("#item_num").val(vale[1])
                    }
                    if (vale[2]){
                        $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                        $("#showMarketPrice").html(parseFloat(vale[3]).toFixed(2));
                        $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                        $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                    }
                }
            }
        } else{
            var _parent_li = _this.parent().parent().find('a.on');
            rsize = _parent_li.attr('name');
            
            if (rsize != ""){
                var info = rsize;
                showvalue = arr[info];
                if (showvalue != undefined){
                    var vale = showvalue.split("|");
                    if (vale.length > 0){
                        if (vale[0]){
                            $("#pdt_id").val(vale[0]);
                        }
                        if(open_stock == 1 && stock_level !== ''){
							if(parseInt(vale[1]) < stock_num && parseInt(vale[1])-MixPdtStock>0){
								$("#pdt_stock").val(vale[1]);
								if($("#item_num").val() <= 0){
									$("#item_num").val(1);
								}
								$("#showNum").html("<strong style='color:red'>供货紧张</strong>");
							}else if(parseInt(vale[1]) > stock_num){
								$("#pdt_stock").val(vale[1]);
								if($("#item_num").val() <= 0){
									$("#item_num").val(1);
								}
								$("#showNum").html("<strong style='color:green'>充足</strong>");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='color:red'>缺货</strong>");
							}
						}else{
							if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余"+vale[1]+"件，下单后立即发货");
							}else if(parseInt(vale[1]) > 30){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
							}
						}
                        if($("#item_num").val() > vale[1]){
                        
                            $("#item_num").val(vale[1]);
                        }
                        if (vale[2]){
                            $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                            $("#showMarketPrice").html(parseFloat(vale[3]).toFixed(2));
                            $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                            $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                        }
                    }else{
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                    }
                }else{
                    $("#pdt_stock").val(0);
                    $("#item_num").val(0);
                    $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                }
            }
        }
    }
}
//选择组合商品规格
function selectGoods(obj){
    var color = $(obj).attr('name');
    var _thisclass = $(obj).attr('class');
    var _this = jQuery(obj);
    if(_this.hasClass("on")){
        return false;
    }
    _this.siblings().removeClass("on");
    _this.addClass("on");
    var slips = $(obj).parent().attr('slip');
    var this_spec_name = '';
    $("dd[slip='"+slips+"']").find('a').each(function(){
        if($(this).hasClass('on')){
            this_spec_name += $(this).parent().attr('slip')+":"+$(this).attr('name')+';';
        }
    });
    this_spec_name = this_spec_name.substring(0,(this_spec_name.length-1));
    if(goods_url[this_spec_name] != null){
        location.href = goods_url[this_spec_name];
    }
}

//商品数量更改
function countNum(i){
	//lin start
	var pdt_id = $('#pdt_id').val();
	if(pdt_id==0){$.ThinkBox.error("请选择商品型号");return false;}
	//end
	var core = $(".sku_products").html();
	var item_id = $('#gid').val();
	var sub = $("#sku"+item_id+'_1').find("a").first().attr('class');
	var align =0;
	//var min_num_per = $("#item_num").data('min');
	//if( min_num_per > 0 &&  IS_ON_MULTIPLE == 1) i = i * min_num_per;
	if($("#sku"+item_id+'_2').length) {
		align= $("#sku"+item_id+'_2').find("a").first().attr('class');
	}
	if(core == undefined){
		var _this = $(".detChoose input[name='num']");
		var num=parseInt(_this.val());
		var max = $("#pdt_stock").val();
		if(max ==''){
			return false;
		}
		max = parseInt(max);
		num=num+i;	
		if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
		_this.val(num);
	}else{
		if(sub == undefined){
			$.ThinkBox.error("请选择商品信息");
		}else if(align == undefined){
			$.ThinkBox.error("请选择商品完整信息");
		}
		
		var _this = $(".detChoose input[name='num']");
		var num=parseInt(_this.val());
		var max = $("#pdt_stock").val();
		if(max ==''){
			return false;
		}
		max = parseInt(max);
		num=num+i;	
		if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
		_this.val(num);
	}
}

// 商品详情放大镜
$(document).ready(function($){
    $('#example3').etalage({
        thumb_image_width: 340,
        thumb_image_height: 340,
        source_image_width: 900,
        source_image_height: 900,
        zoom_area_width: 500,
        zoom_area_height:400,
        zoom_area_distance: 5,
        small_thumbs: 5,
        smallthumb_inactive_opacity: 0.5,
        smallthumbs_position: 'top',
        show_icon: true,
        icon_offset: 20,
        autoplay: false,
        keyboard: false,
        zoom_easing: false
    });
	
	//立即购买	
	$('#addToOrder').click(function(){
		//wjw
		//setCookie('redirect_uri',window.location.href);
		//alert(getCookie('redirect_uri'));
		//
		var pdt_id = $('#pdt_id').val();
		//lin start
		if(pdt_id==0){$.ThinkBox.error("请选择商品型号");return false;}
		//end
		var pdt_stock = parseInt($('#pdt_stock').val());
		var num = parseInt($('#item_num').val());
		var is_global_stock = $('#is_global_stock').val();
		var error_1 = $("#error_1").val();
		var error_2 = $("#error_2").val();
		var error_3 = $("#error_3").val();
		var error_4 = $("#error_4").val();
		var no_login = $("#no_login").val();
		if(is_global_stock == '1'){
			var cr_id = parseInt($("#cr_ids").val());
			var cr_name = $('.province').html();
			if(isNaN(cr_id) || cr_name =='请选择配送区域'){
				$.ThinkBox.error("请选择配送区域");
				return;
			}
		}
		if (isNaN(num)){
			$.ThinkBox.error(error_1);
			return;
		}
		if (num < 1){
			$.ThinkBox.error(error_1);
			return;
		}
		if (pdt_stock < 1){
			$.ThinkBox.error(error_4);
			return;
		}
		if (num > pdt_stock){
			$.ThinkBox.error(error_3);
			return;
		}
		if (pdt_id == ""){
			$.ThinkBox.error(error_4);
			return;
		}
		//发送ajax请求
		$("#way_type").val('1');
		var data = $('#goodsForm').serialize();
		if( no_login == ''){
            $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
				$.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
            },'html');
            return false;
            $.ThinkBox.error("抱歉，您还没有登录,请先登录");
            setTimeout(function(){
				var url='/Home/User/login';
	            location.href= url +"?redirect_uri="+ window.location.href;
	        },2000);
	        return false;
		}
		//alert('a');
		$.post('/Home/Cart/doAdd',data,function(dataMsg){
			if(dataMsg.status){
				$("#submitSkipItemPid").val(pdt_id);
				$("#submitSkipItemtype").val('0');
				$.ThinkBox.success(dataMsg.info);
				$("#submitSkipFrom").submit();
			}else{
				$.ThinkBox.error(dataMsg.info);
			}
		},'json');
	});

	//加入购物车
	$('#addToCart').click(function(){
		var pdt_id = $('#pdt_id').val();
		var is_global_stock = $('#is_global_stock').val();
		var pdt_stock = parseInt($('#pdt_stock').val());
		var num = parseInt($('#item_num').val());
		var error_1 = $("#error_1").val();
		var error_2 = $("#error_2").val();
		var error_3 = $("#error_3").val();
		var error_4 = $("#error_4").val();
		if(is_global_stock == '1'){
			var cr_id = parseInt($("#cr_ids").val());
			var cr_name = $('.province').html();
			if(isNaN(cr_id) || cr_name =='请选择配送区域'){
				$.ThinkBox.error("请选择配送区域");
				return;
			}
		}
		if (isNaN(num)){
			$.ThinkBox.error(error_1);
			return;
		}
		if (num < 1){
			$.ThinkBox.error(error_1);
			return;
		}
		if (pdt_stock < 1){
			$.ThinkBox.error(error_2);
			return;
		}

		if (num > pdt_stock){
			$.ThinkBox.error(error_3);
			return;
		}
		if (pdt_id == "" || pdt_stock == ""){
			$.ThinkBox.error(error_4);
			return;
		}
		if (pdt_id == ""){
			$.ThinkBox.error(error_4);
			return;
		}
		//发送ajax请求
		$("#way_type").val('0');
		var data = $('#goodsForm').serialize();
		console.log(data);
			if (data != ''){
				$.post('/Home/Cart/doAdd',data, function(dataMsg){
					if(dataMsg.status){
						$.ThinkBox.success(dataMsg.info);
					}else{
						$.ThinkBox.error(dataMsg.info);
					}
					ajaxLoadShoppingCart(1);
				}, 'json');
		}
	});

	//加入收藏夹
	$('#addToCollect').click(function(){
		var pdt_id = $('#pdt_id').val();
		var m_id = $('#m_id').val();
		var error_4 = $("#error_4").val();
		var pdt_stock = parseInt($('#pdt_stock').val());
		var num = parseInt($('#item_num').val());
		var no_login = $("#no_login").val();
		var error_4 = $("#error_4").val();
		if (m_id == ""){
			showAlert(false, no_login);
			return;
		}
		if (pdt_id == ""){
			showAlert(false,error_4);
			return;
		}
		var data = {
			type:'item',
			pid:pdt_id
		};
		if (data != ''){
			ajaxReturn('/Ucenter/Collect/doAddCollect', data, 'post');
		}

	});
});

function blurSelectNum(){
	var _this = $("#item_num");
	var max = parseInt(_this.attr('max'));
	var min_per_num = _this.data("min");
	var ereg_rule=/^\+?[1-9][0-9]*$/;
	if(!ereg_rule.test(_this.val())){
		_this.val(1);
	}else{
		if(min_per_num > 0){
			if(_this.val() % min_per_num != 0){
				$.ThinkBox.error("请填写"+ min_per_num +"的倍数！");
				_this.val(_this.data("current"));
				return false;
			}
		}
		if(_this.val()>max){
			_this.val(max);
		}
	}
}








