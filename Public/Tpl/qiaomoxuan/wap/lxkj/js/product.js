function addToCartCheck(){
    
    var pdt_id = $('#pdt_id').val();
    var is_global_stock = $('#is_global_stock').val();
    var pdt_stock = parseInt($('#pdt_stock').val());
    var num = parseInt($('#item_num').val());
    if(is_global_stock == '1'){
	    var cr_id = parseInt($("#cr_ids").val());
	    var cr_name = $('.province').html();
	    if(isNaN(cr_id) || cr_name =='请选择配送区域'){
	        $.ThinkBox.error("请选择配送区域");
	        return false;
	    }
    }
    
    if (isNaN(num)){
   
    	$.ThinkBox.error(nullNum);
        return false;
    }
    if (num < 1){
    	$.ThinkBox.error(nullNum);
        return false;
    }
    if (pdt_stock < 1){
        $.ThinkBox.error(notEnough);
        return false;
    }

    if (num > pdt_stock){
        $.ThinkBox.error(reselection);
        return false;
    }
    if (pdt_id == "" || pdt_stock == ""){
        $.ThinkBox.error(nonExistent);
        return false;
    }
    if (pdt_id == ""){
        $.ThinkBox.error(nonExistent);
        return false;
    }
    return true;
}
function addToCart(){
    if(!addToCartCheck()){
        return;
    }
    //发送ajax请求
	$("#way_type").val('0');
    var data = $('#goodsForm').serialize();
    
        if (data != ''){
            $.post('/Wap/Cart/doAdd', data, function(dataMsg){
                if(dataMsg.status){
                    $.ThinkBox.success(dataMsg.info);
                }else{
                    $.ThinkBox.error(dataMsg.info);
                }
                
            }, 'json');
    }
    checkCartprice_diy();  //更改底部的购物车数量
}

function buyNow(){
    if(!addToCartCheck()){
        return;
    }
    //发送ajax请求
    var data = $('#goodsForm').serialize();
        if (data != ''){
            data = data + '&skip=1';
            $.post('/Wap/Cart/doAdd',data,function(dataMsg){
                if(dataMsg.status){
                    $("#addOrderPid").val($('#pdt_id').val());
                    $("#addOrdertype").val('0');
                    $.ThinkBox.success(dataMsg.info);
                    $("#orderAddFrom").submit();
                }else{
                    $.ThinkBox.error(dataMsg.info);
                }
            },'json');
    }

}

//加入收藏夹
function addToCollect(){

    var pdt_id = $('#pdt_id').val();
    var m_id = $('#m_id').val();
    var pdt_stock = parseInt($('#pdt_stock').val());
    var num = parseInt($('#item_num').val());
    if (m_id == ""){
        showAlert(false, notLogin);
        return;
    }
    if (pdt_id == ""){
        showAlert(false, nonxEistent);
        return;
    }
    var data = {
        type:'item',
        pid:pdt_id
    };
    if (data != ''){
        ajaxReturn('/Ucenter/Collect/doAddCollect', data, 'post');
    }

}

//商品数量更改
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val());
    var max = $("#pdt_stock").val();
    if(max ==''){
        return false;
    }
    max = parseInt(max);
    num=num+i;
    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
    _this.val(num);
    return false;
}

//选择规格
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
							$("#showNum").html("仅剩余"+vale[1]+"件");
						}else if(parseInt(vale[1]) > 30){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='font-size:12px;'>有货</strong>");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("<strong style='font-size:12px;'>无货</strong>");
						}
                    }
                    if($("#item_num").val() > vale[1]){
                        $("#item_num").val(vale[1])
                    }
                    if (vale[2]){
                        $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                        $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
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
								$("#showNum").html("<strong style='font-size:12px;color:red'>紧张</strong>");
							}else if(parseInt(vale[1]) > stock_num){
								$("#pdt_stock").val(vale[1]);
								if($("#item_num").val() <= 0){
									$("#item_num").val(1);
								}
								$("#showNum").html("<strong style='font-size:12px;color:green'>充足</strong>");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='font-size:12px;color:red'>缺货</strong>");
							}
						}else{
							if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("剩余"+vale[1]+"件");
							}else if(parseInt(vale[1]) > 30){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("<strong style='font-size:12px;'>有货</strong>");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='font-size:12px;'>无货</strong>");
							}
                        }
                        if($("#item_num").val() > vale[1]){

                            $("#item_num").val(vale[1]);
                        }
                        if (vale[2]){
                            $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                            $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                            $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                            $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                        }
                    }else{
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("<strong style='font-size:12px;'>无货</strong>");
                    }
                }else{
                    $("#pdt_stock").val(0);
                    $("#item_num").val(0);
                    $("#showNum").html("<strong style='font-size:12px;'>无货</strong>");
                }
            }
        }
    }
}
/**
 * 获取自由推荐商品
 * @param {type} gid
 * @returns {undefined}
 */
function getCollGoodsPage(gid){
    $.ajax({
        url:'/Wap/Products/getCollGoodsPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#coll_goods").html(msgObj);
            return false;
        }
    }); 
}

function getRelateGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getRelateGoodsPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#relate_goods").html(msgObj);
            return false;
        }
    }); 
}

function getGoodsAdvice(gid,page){
    $.ajax({
        url:'/Home/Products/getGoodsAdvice',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            page:page
        },
        success:function(msgObj){
            
            $("#question_title").val('');
            $("#question_content").val('');
            $("#con_tabAbp_3").html(msgObj);
            return false;
        }
    }); 
}