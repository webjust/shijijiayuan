/**
 * 选货页面js
 * @author zuo <zuojianghua@guanyisoft.com>
 * @date 2012-12-25
 */
 
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

$(document).ready(function(){
    //货品规格的显示和隐藏
    $('.standard').css('cursor','pointer');
    $('.standard').hover(function(){
        $(this).attr('class','standard01');
    }, function(){
        $(this).attr('class','standard');
    });

    //展开和关闭超过四行以外的货品
    $('.pdtSwitch').css('cursor','pointer');
    $('.pdtSwitch').click(function(){
        var sw = $(this).parents('table').find('tbody tr:eq(4)').css('display');
        if('none'==sw){
            $(this).find('span').removeClass('open').addClass('close');
            $(this).parents('table').find('tbody tr:gt(3)').fadeIn('fast');
        }else{
            $(this).find('span').removeClass('close').addClass('open');
            $(this).parents('table').find('tbody tr:gt(3)').fadeOut('fast');
        }
    });

    //选货加减按钮
    $('.reduce').click(function(){
        var obj = $(this).attr('for');
        var num =parseInt($("#"+obj).val()) ;
        var per_min = parseInt($(this).siblings('.inputNum').attr("min_num") );
        if(IS_ON_MULTIPLE == 1 &&  per_min != 0) {//启用最小倍数
            if(num % per_min != 0 ){
                num = per_min;
            }else{
                num = num -  per_min;
            }
        }else{
            num --;
        }
        if(num<0 || isNaN(num)){
            num = 0;
        }
        $("#"+obj).val(num);
    });

    $('.add').click(function(){
        var max = $(this).attr('max');
        var obj = $(this).attr('for');
        var num = parseInt($("#"+obj).val());
        var g_pre_sale_status  = $("#g_pre_sale_status").val();//预售状态
        var per_min = parseInt($(this).siblings('.inputNum').attr("min_num"));
        if(IS_ON_MULTIPLE == 1 &&  per_min != 0){//启用最小倍数
            if(num % per_min != 0 ){
                num = per_min;
            }else{
                num = num + per_min;
            }
        }else{
            num ++;
        }
        if(g_pre_sale_status != 1){
             if(num>max || isNaN(num)){
                num = max;
            }
        }
       
        $("#"+obj).val(num);
    });
    
    /**
     * 光标移开验证库存
     *
     */
    $(".inputNum").focusout(function(){
        var ereg_rule = /^\+?[1-9][0-9]*$/;
        var maxNum = parseInt($(this).next().attr("max"));
        if($(this).val()!=''){
            if(!ereg_rule.test($(this).val())){
                $(this).val('0');
            }
            var order_num = parseInt($(this).val());
            var per_min = parseInt($(this).attr("min_num") );
            if(IS_ON_MULTIPLE == 1 &&  per_min != 0 && order_num % per_min != 0) {//启用最小倍数
                alert("请输入"+ per_min +"的倍数！" );
                if(!isNaN($(this).data('current'))){
                    $(this).val($(this).data('current'));
                }else{
                    $(this).val(per_min);
                }
            }
            if(order_num > maxNum){
                $(this).val(maxNum);
            }
        }
    });
	
	/***
     *更多按钮
     *@author zhangjiasuo
     *@date 2013-04-18
     */
    $('#more').click(function(){
        var url = "/Ucenter/Products/pageList";
		$.get(url,{
                'more':1
            },function(info){
                if(info.result){
				    var brand_obj= $("#brand_box1").css('display');
					var cate_obj= $("#cate_box1").css('display');
					if(brand_obj=='block'){
						$("#brand_box1").css('display','none');
						$("#brand_box2").css('display','block');
					}else{
						$("#brand_box1").css('display','block');
						$("#brand_box2").css('display','none');
					}
					
					if(brand_obj=='block'){
						$("#cate_box1").css('display','none');
						$("#cate_box2").css('display','block');
					}else{
						$("#cate_box1").css('display','block');
						$("#cate_box2").css('display','none');
					}
                }
            },'json');
    });
	
	/***
     *仅显示有货
     *@author zhangjiasuo
     *@date 2013-05-15
     */
    $('#ck').click(function(){
		var url = $('#ck').attr('url');
		if($("input[name='stock']").attr("checked")){
			var url =url +'/stock/1';
		}else{
			var url =url +'/stock/0';
		}
		$.get(url,{
                'ajax':1
            },function(info){
                if(info.result){
					location.href =url;
                }
            },'json');
    });
    
	/***
     *组合商品
     *@author zhangjiasuo
     *@date 2013-05-15
     */
    $('#ck1').click(function(){
		var url = $('#ck1').attr('url');
		if($("input[name='combo']").attr("checked")){
			var url =url +'/combo/1';
		}else{
			var url =url +'/combo/0';
		}
		$.get(url,{
                'ajax':1
            },function(info){
                if(info.result){
					location.href =url;
                }
            },'json');
    });
    //选货时候的验证
    $(document).ready(function(){
        $.metadata.setType("attr","validate");
    });
    
    $('#goodsSelectForm').validate({
        showErrors: function(errors){
            //console.log(errors);
            msg = '';
            for ( var name in errors ) {
                msg = errors[name] + '<br>';
                $("input[name='"+name+"']").addClass('formError').focus(function(){
                    $(this).removeClass('formError');
                });
            }
            if(msg){
                showAlert(false,'提交失败',msg);
            }
            return ;
        },
        onkeyup: false,
        onfocusout:false
    });

    //提交到购物车
    $('#addToCart').click(function(){
        var status =false;
        var res = $('#goodsSelectForm').valid();
        if(res){
            $(".inputNum").each(function(index) {
                var value=$(this).val();
                var min=$(this).attr("min_num");
                value = parseInt(value);
                min = parseInt(min);
                if(value > 0){
                    if(value < min ){
                        $.ThinkBox.error('货品列表商品数量没有达到购买的数量，请修改后再试！');
                        return false;
                    }
                   status=true; 
                }
            });
            //发送ajax请求
            var data = $('#goodsSelectForm').serialize();
            if(data !='' && status){
                $.ajax({
                    url:'/Ucenter/Cart/doAdd',
                    data:data,
                    success:function(result){
                        showAlert(result.status,result.info,'',{'继续购物':'/Ucenter/Products/pageList','查看购物车':'/Ucenter/Cart/pageList'});
                    },
                    error:function(){
                        alert('请求无响应或超时');
                    },
                    type:'POST',
                    dataType:'json'
                });
            }  
        }
    });

    //提交到立刻付款（下单）
    $('#addToOrder').bind({'click':addToOrder});
    function addToOrder(){
        //$('#addToOrder').unbind();
        var status =false;
        var res = $('#goodsSelectForm').valid();
        if(res){
            $(".inputNum").each(function(index) {
                var value=$(this).val();
                var min=$(this).attr("min_num");
                min = parseInt(min);
                if(value > 0){
                    if(value < min){
                       $.ThinkBox.error('货品列表商品数量没有达到购买的数量，请修改后再试！');
                        return false;
                    }
                   status=true; 
                }
            });
            //发送ajax请求
            var data = $('#goodsSelectForm').serialize();
            if(data !=  '' && status){
                //跳过购物车页面直接支付
                data = data + '&skip=1';
                $.ajax({
                    url:'/Ucenter/Cart/doAdd',
                    data:data,
                    success:function(result){
                        $('input[name^=cart]').each(function(){
                            if($(this).val() == 0){
                                $(this).parent().find('input[name^=pid]').val('');
                            }
                        });
                        $('#goodsSelectForm').attr('action','/Ucenter/Orders/pageAdd').submit();
                        // showAlert(result.status,result.info,'',{'继续购物':'/Ucenter/Products/pageList','查看购物车':'/Ucenter/Cart/pageList'});
                    },
                    error:function(){
                        alert('请求无响应或超时');
                    },
                    type:'POST',
                    dataType:'json'
                });
            }   
        }
    }

    /* 按商家编码下单表单验证 */
    $('#goodsQuickForm').validate();
    /*
    $('#goodsQuickForm').validate({
        showErrors: function(errors){
            //console.log(errors);
            msg = '';
            for ( var name in errors ) {
                msg = errors[name] + '。';
                $("input[name='"+name+"']").addClass('formError').focus(function(){
                    $(this).removeClass('formError');
                });
            }
            if(msg){
                showAlert(false,'提交失败',msg);
            }
            return ;
        },
        onkeyup: false,
        onfocusout: true
    });
    */
    /* 商家编码框失去焦点时进行ajax验证 */
    $('.pdtsn').live("blur",function(){
        var obj = $(this);
        var pdtsn = $(this).val();
        var url = "/Ucenter/Products/doCheckPdtsn";
        if(pdtsn != ''){
            $.get(url,{
                'pdtsn':pdtsn
            },function(info){
                if(info.result){
                    obj.parents('tr').next().find('td').html(info.msg);
                    obj.parent().next().find('input').attr('disabled',false);
                    $('#goodsQuickSubmit').attr('disabled',false);
                    obj.parent().next().find('input').attr('name','cart['+info.data+']');
                    obj.parent().next().find('input').attr('validate',"{ required:true,number:true,digits:true,range:[0,"+info.max+"],messages:{ number:'输入的格式不正确',digits:'输入的格式不正确',range:'输入的数量超出库存允许范围'}}");
                    obj.attr('pdt-id',info.data);
                    if(IS_ON_MULTIPLE == 1 &&  info.pdt_min_num > 0){//最小下单数 倍数
                        obj.parent().next().children(".QuickOrder").val(info.pdt_min_num);
                        obj.parent().next().children(".QuickOrder").data("min",info.pdt_min_num);
                        obj.parent().next().children(".QuickOrder").data("init",info.pdt_min_num);
                    }
                }else{
                    obj.attr('pdt-id',0);
                    obj.parent().next().find('input').attr('disabled',true);
                    obj.parents('tr').next().find('td').html('');
                    obj.parents('tr').next().find('td').html(info.msg);
                    $('#goodsQuickSubmit').attr('disabled',true);
                }
            },'json');
        }else{
            var html='';
            obj.parents('tr').next().find('td').html(html);
            obj.parent().next().find('input').attr('disabled',true);
            $('#goodsQuickSubmit').attr('disabled',true);
        }
    });
    /*快速订货最小数量*/
    $('.QuickOrder').live("blur",function(){
        var obj = $(this);
        var num = parseInt(obj.val());
        if(IS_ON_MULTIPLE == 1 &&  obj.data("min") > 0){
            if(num % obj.data("min") != 0){
                alert("请填写" + obj.data("min") +"的倍数！");
                obj.val(obj.data("init"));
                return;
            }else{
                obj.data("init",num);
            }
        }
    });
    /* 按商家编号下单表单提交 */
    $('#goodsQuickSubmit').click(function(){
        $.blockUI();
        //setTimeout($.unblockUI, 2000);
        setTimeout(function(){
            $.unblockUI();
            var res = false;
            res = $('#goodsQuickForm').valid();
            if(res){
                $.unblockUI();
                //发送ajax请求
                var data = $('#goodsQuickForm').serialize();
                $.ajax({
                    url:'/Ucenter/Cart/doAdd',
                    data:data,
                    success:function(result){
                        showAlert(result.status,result.info,'',{'继续购物':'/Ucenter/Products/pageQuick','查看购物车':'/Ucenter/Cart/pageList'});
                    },
                    error:function(){
                        alert('请求无响应或超时');
                    },
                    type:'POST',
                    dataType:'json'
                });
            }
        },2000);
    });

    /* 按商家编号下单 新增一行*/
    $('#newMore').click(function(){
        var str = $('#insertDiv table').html();
        $('#tbQuickForm').append(str);
    });
    $("#removeMore").click(function(){
        if($("#tbQuickForm").find('tbody').length > 1){
            $("#tbQuickForm").find('tbody:last').remove();
        }else{
            showAlert(false,'不能再删啦！');
        }
    });

});
