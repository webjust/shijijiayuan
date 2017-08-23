
function setGroupbuyTime(times,showDay,showHouse,showFen,showMiao,now_time,buy_type,buy_status,gpEndTime,listId,gid){
    var day_elem = $("#showGroupTime"+listId).find('.'+showDay);
    var hour_elem = $("#showGroupTime"+listId).find('.'+showHouse);
    var minute_elem = $("#showGroupTime"+listId).find('.'+showFen);
    var second_elem = $("#showGroupTime"+listId).find('.'+showMiao);

    var reg = new RegExp("-","g");
    var timeStr = times.replace(reg,"/");
    var nowtimeStr = now_time.replace(reg,"/");
    var timeStr = new Date(timeStr);
    var nowtimeStr = new Date(nowtimeStr);

    var end_time = timeStr.getTime(),//月份是实际月份-1
        sys_second = (end_time-nowtimeStr.getTime())/1000;

    var timer = setInterval(function(){
        if (sys_second > 0) {
            sys_second -= 1;
            var day = Math.floor((sys_second / 3600) / 24);
            var hour = Math.floor((sys_second / 3600)-(day * 24));
            var minute = Math.floor((sys_second / 60) % 60);
            var second = Math.floor(sys_second % 60);
            //day_elem && $(day_elem).text(day);//计算天
            $(day_elem).html(day);
            $(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
            $(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
            $(second_elem).text(second<10?"0"+second:second);//计算秒杀
        } else {
            if(buy_type == 'miaos'){
                if(buy_status == 2){
                    $('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" class="goBuy " disabled gid="{$detail.gid}" >秒杀结束</a>');
                    $('#showGroupTime'+listId).html('<span><abbr>此秒杀已结束</abbr></span>');
                } else if(buy_status == 1){
                    $('#showGrouupbuy'+listId).html('<a id="addToOrder" class="goBuy addToOrder" gid="{$detail.gid}" href="javascript:void(0);">立即抢购</a>');
                    setGroupbuyTime(gpEndTime,'day','hours','minutes','seconds',times,'miaos',2,'',listId,gid);
                }
            }
            else if(buy_type == 'tuan'){
                if(buy_status == 2){
                    $('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" class="pastbuy goTobuy">该团购已结束</a>');
                    $('#showGroupTime'+listId).html('<span><abbr>此团购已结束</abbr></span>');
                } else if(buy_status == 1){
                    $('#showGrouupbuy'+listId).html('<a href="javascript:void(0);" onclick="addToOrder(2);" class="goTobuy" >立即抢购</a>');
                    setGroupbuyTime(gpEndTime,'day','hours','minutes','seconds',times,'tuan',2,'',listId,gid);
                }
            }
            clearInterval(timer);
        }
    }, 1000);
}

//获取评论列表
function getCommentPage(gid){
	$.ajax({
		url:'/Wap/Comment/getCommentPage',
		dataType:'HTML',
		type:'GET',
		data:{
			gid:gid
		},
		success:function(msgObj){
			$("#goods_comments").html(msgObj);
			return false;
		}
	});
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

//获取咨询列表
function getAdvicePage(gid){
	$.ajax({
		url:'/Wap/Products/getGoodsAdvice',
		dataType:'HTML',
		type:'GET',
		data:{
			g_id:gid
		},
		success:function(msgObj){
			$("#goods_advice").html(msgObj);
			return false;
		}
	});
}

function getRelateGoodsPage(gid){
	$.ajax({
		url:'/Wap/Products/getRelateGoodsPage',
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

/**
 * 根据指定时间显示动态倒计时效果
 *
 * @param times 指定时间年月日 格式 Y-m-d H:i:s
 * @param 显示时间的id 顺序为 天->小时->分->秒
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 */
function countDown(time,id,status,nowtime,endtime,tuangouOver){
	var day_elem = $("#timebox"+id).find('#day');
	var hour_elem = $("#timebox"+id).find('#hour');
	var minute_elem = $("#timebox"+id).find('#minute');
	var second_elem = $("#timebox"+id).find('#second');

	var reg = new RegExp("-","g");
	var timeStr = time.replace(reg,"/");
	var nowtimeStr = nowtime.replace(reg,"/");
	var timeStr = new Date(timeStr);
	var nowtimeStr = new Date(nowtimeStr);

	var end_time = timeStr.getTime(),//月份是实际月份-1
		sys_second = (end_time-nowtimeStr.getTime())/1000;
	var timer = setInterval(function(){
		if (sys_second > 0) {
			sys_second -= 1;
			var day = Math.floor((sys_second / 3600) / 24);
			var hour = Math.floor((sys_second / 3600));
			var minute = Math.floor((sys_second / 60) % 60);
			var second = Math.floor(sys_second % 60);
			day_elem && $(day_elem).text(day);//计算天
			$(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
			$(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
			$(second_elem).text(second<10?"0"+second:second);//计算秒杀
		} else {
			if(status == 1){
				$("#miaosha"+id).html("<a class='none'>已结束</a>");
				$(".tuangouOver").toggle();
			} else if(status == 2){
				$("#miaosha"+id).html('<a href="javascript:void(0);" onclick="addToOrder(1);" class="addCart">加入购物车</a>');
				$("#colockbox"+id).find('.changemsg').text('距结束');
				$(".tuangouOver").toggle();
				countDown(endtime,id,1,time);
			}
			clearInterval(timer);
		}
	}, 1000);
}//倒计时


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
						//$("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
						//$("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
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


function getDetailSkus(item_id, item_type){
	$.ajax({
		url:'/Wap/Products/ajaxGetSku',
		dataType:'HTML',
		type:'POST',
		data:{
			item_id:item_id,
			item_type:item_type
		},
		success:function(msgObj){
			$("#showDetailSkus").html(msgObj);
			return false;
		}
	});
}