$(document).ready(function($){
	//立即购买
	$('#addToOrder').click(function(){
		var res = allSpecSelectedCheck('on');
		if(res[0] == false) {
			$.ThinkBox.error('请选择要购买的商品规格！');return false;
		}
		var pdt_id = $('#pdt_id').val();
		var pdt_stock = parseInt($('#pdt_stock').val());
		var num = parseInt($('#item_num').val());
		var is_global_stock = $('#is_global_stock').val();
		var mid = $('#m_id').val();
		if(is_global_stock == '1'){
			var cr_id = parseInt($("#cr_ids").val());
			var cr_name = $('.province').html();
			if(isNaN(cr_id) || cr_name =='请选择配送区域'){
				//showAlert(false,"请选择配送区域");
				alert("请选择配送区域");

				return false;
			}
		}

		if (isNaN(num)){
			alert(nullNum);
			return false ;
		}
		if (num < 1){
			alert(nullNum);
			return false ;
		}
		if (pdt_stock < 1){
			alert(notEnough);
			return false ;
		}
		if (num > pdt_stock){
			alert(reselection);
			return false;
		}
		if (pdt_id == ""){
			alert(nonExistent);
			return  false;
		}

		//发送ajax请求
		$("#way_type").val('1');
		var data = $('#goodsForm').serialize();
		if (data != ''){
			data = data + '&skip=1';
			if(mid == 0){
				data = data + '&way_type=1';
			}
			$.post('/Wap/Cart/doAdd',data,function(dataMsg){
				if(dataMsg.status){
					$("#submitSkipPid").val(pdt_id);
					$("#submitSkiptype").val('0');
					if(mid ==''){
						var skipdata = $("#submitSkipFrom").serialize();
						$.cookie("skipdata",skipdata,{ expires: 7 ,secure:false,path:'/Wap/User/'});
					}
					$("#submitSkipFrom").submit();
				}else{
					$.ThinkBox.error(dataMsg.info);
				}
			},'json');
		}
	});

	$("#item_num").blur(function(){
        var max = $("#pdt_stock").val();
        if(max ==''){
            $(this).val(0);
            return false;
        }max = parseInt(max);
        var num = this.value;
        if(isNaN(num) && max>0){
            $(this).val(1);
        }else if(max<=0){
            $(this).val(0);
        }else if(!isNaN(num) && num>0 && num<max){
            $(this).val(num);
        }else if(!isNaN(num) && num>0 && num>max){
            $(this).val(max);
        }else if(!isNaN(num) && num<0){
            $(this).val(1);
        }
    });

	//添加咨询
	$("#addAdvice").click(function(){
		var question_content = $("#question_content").val();
		if(question_content == ''){
			alert('咨询内容不能为空');return false;
		}
		var gid = $("#filter_gid").val();
		var mid = $("#mid");
		var m_name = $("#m_name");
		var url = '/Wap/Products/doGoodsAdvice';
		$.post(url,{'gid':gid,'mid':mid,'question_content':question_content,'type':1,'question_title':'提问'},function(msgObj){
			if(msgObj.status == '1'){
				alert(msgObj.info);
				var _mvq = window._mvq || [];window._mvq = _mvq;
				_mvq.push(['$setAccount', 'm-24416-0']);

				_mvq.push(['$setGeneral', 'consult', '', /*用户名*/ m_name, /*用户id*/ mid]);
				_mvq.push(['$logConversion']);
				//getGoodsAdvice(gid,1);
				return false;;
			}else{
				alert(msgObj.info);
				return;
			}
		},'json')
	});
});

//获取评论列表
function getCommentPage(gid,p){
    $.ajax({
        url:'/Wap/Comment/getCommentPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
			p:p
        },
        success:function(msgObj){
            $("#goods_comments").html(msgObj);
            return false;
        }
    }); 
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
	console.log(num);
	console.log(max);
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
		initData();
    }
}

//初始化页面数据
function initData() {
	var item_id = $("#gid").val();
	var rsize = "";
	var showvalue = "";
	var _parent_color = jQuery("#sku_" + item_id + '_1').find('a.on');
	var _parent_size = jQuery("#sku_" + item_id + '_2').find('a.on');
	var color_len = _parent_color.length;
	var size_len = _parent_size.length;
	var open_stock = $("#open_stock").val();
	var stock_num = $("#stock_num").val();
	var stock_level = $("#stock_level").val();

	if (size_len > 0 && color_len > 0){
		var color = _parent_color.attr('name');
		var size = _parent_size.attr('name');
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
						$("#showNum").html("库存：<strong style='color:red'>供货紧张</strong>");
					}else if(parseInt(vale[1]) > stock_num){
						$("#pdt_stock").val(vale[1]);
						if($("#item_num").val() <= 0){
							$("#item_num").val(1);
						}
						$("#showNum").html("库存：<strong style='color:green'>充足</strong>");
					}else if(parseInt(vale[1])-MixPdtStock <= 0){
						$("#pdt_stock").val(0);
						$("#item_num").val(0);
						$("#showNum").html("库存：<strong style='color:red'>缺货</strong>");
					}
				}else{
					if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
						$("#pdt_stock").val(vale[1]);
						if($("#item_num").val() <= 0){
							$("#item_num").val(1);
						}
						$("#showNum").html(vale[1]);
					}else if(parseInt(vale[1]) > 30){
						$("#pdt_stock").val(vale[1]);
						if($("#item_num").val() <= 0){
							$("#item_num").val(1);
						}
						$("#showNum").html(vale[1]);
					}else if(parseInt(vale[1])-MixPdtStock <= 0){
						$("#pdt_stock").val(0);
						$("#item_num").val(0);
						$("#showNum").html("库存："+vale[1]+"件");
					}
				}
				if($("#item_num").val() > vale[1]){
					$("#item_num").val(vale[1])
				}
				if (vale[2]){
					$("#showPrice").html(parseFloat(vale[2]).toFixed(2));
					//$("#showMarketPrice").html(parseFloat(vale[3]).toFixed(2));
					//$("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
					//$("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
				}
			}
		}
	}else{
		rsize = _parent_color.attr('name');
		if (rsize != ""){
			var info = rsize;
			showvalue = arr[info];
			if (showvalue != undefined){
				var vale = showvalue.split("|");
				//console.log(vale);
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
							$("#showNum").html("库存：<strong style='font-size:14px;color:red'>供货紧张</strong>");
						}else if(parseInt(vale[1]) > stock_num){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("库存：<strong style='font-size:14px;color:green'>充足</strong>");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("库存：<strong style='font-size:14px;color:red'>缺货</strong>");
						}
					}else{
						if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							$("#showNum").html("库存："+vale[1]+"件");
						}else if(parseInt(vale[1]) > 30){
							$("#pdt_stock").val(vale[1]);
							$("#showNum").html("库存："+vale[1]+"件");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("库存："+vale[1]+"件");
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
					$("#showNum").html(vale[1]);
				}
			}else{
				$("#pdt_stock").val(0);
				$("#item_num").val(0);
				$("#showNum").html(vale[1]);
			}
		}
	}
}
/*********异步加载购物车****/
function ajaxLoadShoppingCart(int_page_num){
	if(!int_page_num){
		int_page_num = 1;
	}
	$.post('/Wap/Cart/mycartAjax',{'int_page_num':int_page_num},function(htmlObj){
		if(htmlObj !='0'){
			$("#global-cart").addClass("s2");
			$("#global-cart").html(htmlObj);
		}
	},'html');
}

/*加入收藏*/
function addToInterests(gid){
	if(parseInt(gid) <= 0){
		alert("商品不存在或者已经被下架");return false;
	}
	$.ajax({
		url:"/Home/Products/doAddGoodsCollect",
		cache:false,
		dataType:"json",
		data:{gid:gid},
		type:"post",
		success:function(msgObj){
			if(msgObj.status == '1'){
				alert("加入收藏成功");
			}else{
				alert(msgObj.info);
			}
		}
	});
}

