$(document).ready(function(){
    // 退货数量控制
    function refund_nums_check(obj) {
        var value = parseInt(obj.val());
        var hiddenvalue = parseInt(obj.attr('hiddenvalue'));
        if (isNaN(value)) {
            obj.val(hiddenvalue);
            return false;
        }
        if (value > hiddenvalue) {
            obj.val(hiddenvalue);
        } else {
            obj.val(value);
        }
    }
    $('.refund_products_nums').bind({
        'blur': function() {
            refund_nums_check($(this));
        },
        'change': function() {
            refund_nums_check($(this));
        }
    });

    $('.ckeckAll').click(function(){
        var all_price = 0;
        $('.checkSon').each(function(){
            if($(this).attr("checked")=='checked'){  
				var pdt_id = $(this).attr('pdt_id');
				var pdt_promotion_price = $(this).attr('promotion_price');
				var num = $('#refund_products_'+pdt_id).val();
                var oi_price = parseFloat($(this).attr('oi_price'))*num;
                var pay_price = oi_price-parseFloat(pdt_promotion_price);
                all_price += pay_price;
            }
        });
		//开启退运费
		if ( $("#allow_refund_delivery").length > 0 && all_price > 0) {
			var freight_price = $("#allow_refund_delivery").attr("value");
			all_price += parseFloat(freight_price);
		}
 		$("#max_price").html(all_price.toFixed(3));
        $("#application_money").attr('money',all_price.toFixed(3));
        $("#application_money").val(all_price.toFixed(3));
    });
    $('.checkSon').click(function(){
        var all_price = 0;
        var refund_products = '';
		$('.checkSon').each(function(){
            if($(this).attr("checked")=='checked'){  
				var pdt_id = $(this).attr('pdt_id');
				var pdt_promotion_price = $(this).attr('promotion_price');
				var num = $('#refund_products_'+pdt_id).val();
                var oi_price = parseFloat($(this).attr('oi_price'))*num;
                var pay_price = oi_price-parseFloat(pdt_promotion_price);
                all_price += pay_price;
            }
        });
		//开启退运费
		if ( $("#allow_refund_delivery").length > 0 && all_price > 0) {
			var freight_price = $("#allow_refund_delivery").attr("value");
			all_price += parseFloat(freight_price);
		}
		$("#max_price").html(all_price.toFixed(3));
        $("#application_money").attr('money',all_price.toFixed(3));
        $("#application_money").val(all_price.toFixed(3));
    });
    
   
    //ajax提交数据
     $("#refer").click(function(){
        var num = /^([1-9][0-9]{0,10})|(0)\.?[0-9]{0,3}$/;
        var money = $("#application_money").val();
        var ary_reason =$("#ary_reason").find("option:selected").val();
        var od_logi_no =$("#od_logi_no").val();
		if(ary_reason==''){
			 alert("请选择原因");
             $("#ary_reason").focus();
             return false;
		}
		var ischeck = $("#th_whea01").is(':checked');
		if(ischeck){
			if(od_logi_no==''){
				alert("请输入退货物流单号");
				$("#od_logi_no").focus();
				return false;
			}
		}
		
        if (!num.exec(money)) {
                    alert("请输入正确金额");
				   // $("#dialog_msg").dialog('close');
                    $("#application_money").focus();
                    return false;
        }
        
        var len = $("input[name='checkSon[]']:checked").length;
        if("checked" == $("input[name='th_radio']").attr("checked")){
            if(0 == len){
                alert("请选择要退的货品！");return false;
            }
        }
        var res = $('#aftersale_form').valid();
        if(res){
            $("#aftersale_form").submit();
            //$("#refer").attr({ "disabled": "disabled" });
			/* $.ajax({
				dataType : "json", 
				url:'/Ucenter/Aftersale/doAdd', //提交给哪个执行
				type:'POST',
				data:$('#aftersale_form').serialize(),
				success: function(result){
				    if(result.status==true || result.status==1){
                        alert(result.info);
                        window.location.href = "/Ucenter/Aftersale/pageList";
                    }
                    else {
                        alert(result.info);
                    }
				}, //显示操作提示
				error: function(){alert('请求无响应或超时');} //显示操作提示
			}); */
			//$('#aftersale_form').ajaxSubmit(options);
			return false; //为了不刷新页面,返回false，反正都已经在后台执行完了，没事！
		
			/**
            var ajaxData = $('#aftersale_form').serialize();
            $.ajax({
                url:'/Ucenter/Aftersale/doAdd',
                data:ajaxData,
                success:function(result){
                   // var redirect_url = '/Ucenter/Aftersale/pageList';
                    //showAlert(result.status,result.info,'',redirect_url);
                    if(result.status==true || result.status==1){
                        alert(result.info);
                        window.location.href = "/Ucenter/Aftersale/pageList";
                    }
                    else {
                        alert(result.info);
                    }
                },
                error:function(){
                    alert('请求无响应或超时');
                },
                type:'post',
                dataType:'json',
                cache:false,
                async:false
          });
          **/
          //  ajaxReturn('/Ucenter/Aftersale/doAdd',data,'post');
            //$("#refer").attr({ "disabled": false });
        }
    });
});

/** ********* 富文本编辑器和文件管理器集成 --开始 ********** */
var editor = new UE.ui.Editor({
	imageRealPath:"images"
});

editor.render("myEditor");
var dialog_img;
var dialog_file;
var dom;
editor.ready(function(){
	editor.hide()
	dialog_img = editor.getDialog("insertimage");
    dialog_file = editor.getDialog("attachment");
	editor.addListener('beforeInsertImage',function(t, arg){
	  // alert(arg[0]['src']);
	   $("#extend_field_"+dom).val(arg[0]['src']);

	});
    editor.addListener('afterUpfile',function(t, arg){
//          alert(arg[0]['url']);
            $("#extend_field_"+dom).val(arg[0]['url']);
    });
	
});



function upImage(dom_id) {
        dom = dom_id;
        dialog_img.open();
}
function upFile(dom_id){
        dom = dom_id;
        dialog_file.open();
}
/** ******** 富文本编辑器和文件管理器集成 --结束 ********* */


//减去商品数量
function reduce(pdt_id){
    var pdt_num = $("#refund_products_"+pdt_id).val();
    var pdt_hidden_num = parseInt($("#refund_products_"+pdt_id).attr('hiddenvalue'));
    var f_pirce = $("#refund_products_"+pdt_id).attr('fprice')/parseInt(pdt_hidden_num);
    var application_money = $("#application_money").val()
    var pdt_temp_num = pdt_num-1;
    if(pdt_temp_num < 0){
        pdt_temp_num = 0;
        f_pirce = 0;
    }
    $("#refund_products_"+pdt_id).val(pdt_temp_num);
    $("#f_price_"+pdt_id).html('<i class="price">￥</i>'+(f_pirce*pdt_temp_num).toFixed(3));
    $("#checkSon_"+pdt_id).attr('oi_price',f_pirce*pdt_temp_num);
    if($("#checkSon_"+pdt_id).attr("checked")=='checked'){
        
        $("#application_money").val((application_money-f_pirce).toFixed(3));
    }
}
//增加商品数量
function add(pdt_id){
    var pdt_hidden_num = parseInt($("#refund_products_"+pdt_id).attr('hiddenvalue'));
    var f_pirce = $("#refund_products_"+pdt_id).attr('fprice')/parseInt(pdt_hidden_num);
    var pdt_num = $("#refund_products_"+pdt_id).val();
    var application_money = $("#application_money").val();
    var pdt_temp_num = parseInt(pdt_num)+parseInt(1);
    if(pdt_temp_num > pdt_hidden_num){
        pdt_temp_num = pdt_num;
        //alert(1);
       // f_pirce = 0;
    }
    $("#refund_products_"+pdt_id).val(pdt_temp_num);
    $("#f_price_"+pdt_id).html('<i class="price">￥</i>'+(f_pirce*pdt_temp_num).toFixed(3));
     $("#checkSon_"+pdt_id).attr('oi_price',f_pirce*pdt_temp_num);
    if($("#checkSon_"+pdt_id).attr("checked")=='checked'){
        //alert(parseInt(f_pirce)+parseInt(application_money));
        $("#application_money").val((parseInt(f_pirce)+parseInt(application_money)).toFixed(3));
    }
}

