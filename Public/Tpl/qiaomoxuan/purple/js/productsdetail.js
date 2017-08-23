/**
 * 商品详情页
 * add by zhangjiasuo
 * date 2015-05-14 13:45:30
 */
	$(document).ready(function($){
				
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
			//console.log(data);
			if (data != ''){
				$.post('/Home/Cart/doAdd', data, function(dataMsg){
					if(dataMsg.status){
						$.ThinkBox.success(dataMsg.info);
					}else{
						$.ThinkBox.error(dataMsg.info);
					}
					ajaxLoadShoppingCartNum(1);
				}, 'json');
			}
		});
		
		//立即购买	
		$('#addToOrder').click(function(){
			var pdt_id = $('#pdt_id').val();
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
				$.ThinkBox.error(error_2);
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
				var ref_url = window.location.href;
				window.location.href='/Home/User/login?requsetUrl='+ encodeURIComponent(ref_url);
				return;
				$.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
					$.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
				},'html');
				return false;
			}
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
	});
	
	function setTabs(name, cursel, n){
		for (i = 1; i <= n; i++){
			var tab = document.getElementById(name + i);
			var con = document.getElementById("con_" + name + "_" + i);
			tab.className = i == cursel?"onHover":"";
			con.style.display = i == cursel?"block":"none";
		}
	};
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
					$.ThinkBox.success("加入收藏成功");
				}else{
					$.ThinkBox.error(msgObj.info);
				}
			}
		});
	}
	//选择组合商品规格
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
		//console.log(color_len);
		//console.log(size_len);
		if (size_len > 0 && color_len > 0){
			var color = _parent_color.attr('name');
			var size = _parent_size.attr('name');
			if (color != '' && size != ''){
				var info = size + ";" + color;
				//console.log(info);
				showvalue = arr[info]?arr[info]:"";
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
		}else{
			rsize = _parent_color.attr('name');
			if (rsize != ""){
				var info = rsize;
				showvalue = arr[info];
				if (showvalue != undefined){
					var vale = showvalue.split("|");
					console.log(vale);
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
	//商品数量更改
	function countNum(i){
		var _this = $("#item_num");
		var num=parseInt(_this.val());
		var max = $("#pdt_stock").val();
		//var min_num_per = $("#item_num").data('min');
        //
		//if( min_num_per > 0 && IS_ON_MULTIPLE == 1) i = i * min_num_per;
		if(max ==''){
			return false;
		}
		max = parseInt(max);
		num=num+i;
		if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
		_this.val(num);
	}

function getBuyRecordPage(gid,num){
    $.ajax({
        url:'/Home/Products/getBuyRecordPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            p:num
        },
        success:function(msgObj){
            $("#con_tabs_3").html(msgObj);
            return false;
        }
    });
}
function getCommentPage(gid,page){
    $.ajax({
        url:'/Home/Comment/getCommentPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            p:page
        },
        success:function(msgObj){

            $("#con_tabs_2").html(msgObj);

            return false;
        }
    });
}
	//相关商品
	function getRelateGoodsPage(page_size){
		var gid = $("#gid").val();
		$.ajax({
			url:'/Home/Products/getRelateGoodsPage',
			dataType:'HTML',
			type:'GET',
			data:{
				gid:gid,
				page_size: page_size
			},
			success:function(msgObj){
				$("#relate_goods").html(msgObj);
				return false;
			}
		}); 
	}

function getCollGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getCollGoodsPage',
        dataType:'HTML',
        type:'POST',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#coll_goods").html(msgObj);
            return false;
        }
    });
}
	//获取咨询列表
	function getGoodsAdvice(gid, page, dom_id){
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
				$("#"+dom_id).html(msgObj);
				return false;
			}
		});
	}

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





