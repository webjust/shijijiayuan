/**
 * 商品详情页
 * add by zhangjiasuo
 * date 2015-05-19 11:45:30
 */
 
//立即购买  单规格加入购物车
function addGoodsProductsCart(authid,gid,skip){
	var Think_Session_m_name = $("#Think_session_Members_m_name").html();
	if(Think_Session_m_name == '登录'){
        $.ThinkBox.error("抱歉，您还没有登录,请先登录");        
		setTimeout(function(){
			var url='/Home/User/login';
            location.href= url +"?requsetUrl="+ window.location.href;
        },2000);
	}else{
		var pdt_id = $('#pdt_id_'+gid).val();
		var pdt_stock = parseInt($('#pdt_stock_'+gid).val());
		var num = parseInt($('#item_num_'+gid).val());
		
		if(authid != '1'){
			$.ThinkBox.error("您不能购买该商品");
			return false;
		}
		if (num < 1){
			var mgs = $('#stock_error_1').val();
			$.ThinkBox.error(mgs);
			return false;
		}
		if (pdt_id == ""){
			var mgs = $('#stock_error_4').val();
			$.ThinkBox.error(mgs);
			return false;
		}
		//发送ajax请求
		$.post('/Home/Cart/doAdd', {"type":"item","pdt_id":pdt_id,"pdt_stock":pdt_stock,"num":num,"skip":skip}, function(dataMsg){
			if(dataMsg.status == '1'){	
				//立刻购买
				if(skip == '1'){
					location.href='/Ucenter/Orders/pageAdd/pid/'+pdt_id;
				}else{
					$.ThinkBox.success(dataMsg.info);			
					ajaxLoadShoppingCart(1);			
					return false;					
				}
			}else{
				$.ThinkBox.error(dataMsg.info);
				return false;
			}
		}, 'json');
	}
}

//立即购买 多规格加入购物车
function addGoodsCart(gid,authid,skip){
	var Think_Session_m_name = $("#Think_session_Members_m_name").html(); 
	if(Think_Session_m_name == '登录'){
        $.ThinkBox.error("抱歉，您还没有登录,请先登录");        
		setTimeout(function(){
            location.href="{:U('Home/User/login')}"+"?requsetUrl="+ window.location.href;
        },2000);
	}else{   
        if(parseInt(gid) <= 0){
            $.ThinkBox.error("商品不存在或者已经被下架");
            return false;
        }
        if(authid != '1'){
            $.ThinkBox.error("您不能购买该商品");
            return false;
        }
        $.ajax({
            url:'/Home/Cart/getAddGoodsCart',
            cache:false,
            dataType:'HTML',
            data:{gid:gid,skip:skip},
            type:"POST",
            success:function(msgObj){
                var box = $.ThinkBox(msgObj, {'title' : '加入购物车','width':'448px','drag' : true,'unload':true});
            }
        });
    }
}

//加入购物车 多规格商品
function addGoodsCartElse(gid,authid){  
    if(parseInt(gid) <= 0){
        $.ThinkBox.error("商品不存在或者已经被下架");
        return false;
    }
    if(authid != '1'){
        $.ThinkBox.error("您不能购买该商品");
        return false;
    }
    $.ajax({
        url:'/Home/Cart/getAddGoodsCart',
        cache:false,
        dataType:'HTML',
        data:{gid:gid},
        type:"POST",
        success:function(msgObj){
            var box = $.ThinkBox(msgObj, {'title' : '加入购物车','width':'448px','drag' : true,'unload':true});
        }
    });   
}

//加入购物车 单规格商品
function addGoodsProductsCartElse(authid,gid){
    var pdt_id = $('#pdt_id_'+gid).val();
    var pdt_stock = parseInt($('#pdt_stock_'+gid).val());
    var num = parseInt($('#item_num_'+gid).val());
    
    if(authid != '1'){
        $.ThinkBox.error("您不能购买该商品");
        return false;
    }
    if (num < 1){
        console.log(num);
        $.ThinkBox.error("{$Think.lang.STOCK_ERROR_1}");
        return false;
    }
    if (pdt_id == ""){
        $.ThinkBox.error("{$Think.lang.STOCK_ERROR_4}");
        return false;
    }
    //发送ajax请求
    $.post('/Home/Cart/doAdd', {"type":"item","pdt_id":pdt_id,"pdt_stock":pdt_stock,"num":num}, function(dataMsg){
        if(dataMsg.status == '1'){			
            $.ThinkBox.success(dataMsg.info);		
            ajaxLoadShoppingCart(1);			
            return false;
        }else{
            $.ThinkBox.error(dataMsg.info);
            return false;
        }
    }, 'json');
}

$(document).ready(function($){
	$('.clickThisTab').click(function(){
		var cid = $('#cid').val();
		var bid = $('#bid').val();
		var is_new = $('#is_new').val();
		var is_hot = $('#is_hot').val();
		var startPrice = $('#startPrice').val();
		var endPrice = $('#endPrice').val();
		var t = $(this).attr('t');
		var url = "/Home/Products/Index/?"+serializeObject({'cid':cid,'bid':bid,'startPrice':startPrice,'endPrice':endPrice,'new':is_new,'hot':is_hot,'order':t});
		location.href = url;
	});
	$("#submitPrice").click(function(){
		var cid = $('#cid').val();
		var bid = $('#bid').val();
		var is_new = $('#is_new').val();
		var is_hot = $('#is_hot').val();
		var startPrice = $('#startPrice').val();
		var endPrice = $('#endPrice').val();
		if(startPrice >= 0 && endPrice > 0){
			if( parseFloat(endPrice) >= parseFloat(startPrice)){
				var url = "/Home/Products/Index/?"+serializeObject({'cid':cid,'bid':bid,'startPrice':startPrice,'endPrice':endPrice,'new':is_new,'hot':is_hot});
				location.href = url;
				return true;
			}else{
				return false;
			}
		}
	});
	
	serializeObject = function(obj) {
	  var str = [];
	  for(var p in obj)
		 str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
	  return str.join("&");
	}
});







