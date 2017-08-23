$(document).ready(function(){
  /***
   *单个同步
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-05-16
   */
	$(".synOneGoods").click(function(){
		var url = $(this).attr("data-uri");
		var type = $(this).attr("type");
		if(type !== '' && type == '1'){
            var is_tp = $(this).attr("data-tp");
            var is_fx = $(this).attr("data-fx");
            //下架商品不允许同步
            if(is_tp ==1 || (is_tp == 1 && is_fx==2)) {
				$("#J_ajax_loading").addClass('ajax_error').html("已下架商品不能同步！").show().fadeOut(5000);
                return false;
            }
            var spdm = $(this).attr("val");
        }else{
            var spdm = $("#spdm").val();
        }
		if(spdm == ''){
            $("#J_ajax_loading").addClass('ajax_error').html("货号不能为空！").show().fadeOut(5000);
            return false;
        }
		$.ajax({
            url:url,
            cache:false,
            dataType:"json",
            data: {spdm:spdm},
            type:"POST",
            beforeSend:function(){
                $("#J_ajax_loading").stop().removeClass('ajax_error').addClass('ajax_loading').html("提交请求中，请稍候...").show();
            },
            error:function(){
                $("#J_ajax_loading").addClass('ajax_error').html("AJAX请求发生错误！").show().fadeOut(5000);
            },
            success:function(msgObj){
                $("#J_ajax_loading").hide();
                if(msgObj.status == '1'){
                    $(".list_"+spdm).html("<span style='color:green;'>已同步</span>");
                    $("#J_ajax_loading").addClass('ajax_success').html(msgObj.info).show().fadeOut(5000);
                }else{
                    $("#J_ajax_loading").addClass('ajax_error').html(msgObj.info).show().fadeOut(5000);
                }
            }
        });
	});
});  
