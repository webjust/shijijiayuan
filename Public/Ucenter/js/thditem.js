/**
 * 商品铺货js
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 * @date 2012-11-06
 */  
$(document).ready(function() {
        //全选
        $('#select_all').click(function() {
            if ($(this).attr('checked') == 'checked') {
                $("input:checkbox[name='thd_item_id[]']").attr('checked', 'checked');
                $('#select_all').attr('checked', 'checked');
            } else {
                $("input:checkbox[name='thd_item_id[]']").removeAttr('checked');
                $('#select_all').removeAttr('checked');
            }
        });
        //铺货提交
        $("#submit_btn").click(function(){
            var shop = $("#shop").val();
            sw = false;
            $("input:checkbox[name='thd_item_id[]']").each(function(){
                if($(this).attr('checked')=='checked'){
                    sw = true;
                }
            });
            if(shop == 0){
                showAlert(false,'请选择店铺');
                return false;
            }
            if(sw){
                $('#submit_btn').attr({disabled:true}).attr("value","数据上传中");
                var str = '';
                $("input:checkbox[name='thd_item_id[]']:checked").each(function(){
                    str = str+$(this).val()+','
                });
                var url = '/Ucenter/Distribution/ajaxUploadItem';
                var has_freight = $("#has_freight").val();
                var has_invoice = $("#has_invoice").val();
                var rebate_point = $("#rebate_point").val();
                $.post(url,{
                                thd_item_id:str,
                                shop:shop,
                                has_freight:has_freight,
                                has_invoice:has_invoice,
                                rebate_point:rebate_point
                            },function(data){
                                if(data.result){
                                    $("#distirbution_result").html(data.message).dialog(
									{width:450,height:240,title:'同步淘宝商品'}
									);
                                }else{
                                    $("#distirbution_result").html(data.message).dialog("open");
                                }
                                $('#submit_btn').attr({disabled:false}).attr("value","铺货商品");
                            },'json');
            }else{
                showAlert(false,'请选择商品');
                return false;
            }
        });
    });
//全选 0 - 取消全选 1 - 已铺过 2 - 未铺过 3
function selectAll(i){
	switch(i){
		case 0 :
			$("input[name='thd_item_id[]']").attr('checked','checked');
			$("#select_all").attr('checked','checked');
			break;
		case 1 :
			$("input[name='thd_item_id[]']").removeAttr('checked');
			$("#select_all").removeAttr('checked');
			break;
		case 2 :
			var shop = $("#shop").val();
			if(shop == 0){
				showAlert(false,'请选择店铺');
				return false;
			}
			$("input[name='thd_item_id[]']").removeAttr('checked');
			$("#select_all").removeAttr('checked');
			$(".history").each(function(){
				if($(this).find('span').html()=='已铺货'){
					$(this).prevAll().find("input[name='thd_item_id[]']").attr('checked','checked');
				}
			});
			break;
		case 3 :
			var shop = $("#shop").val();
			if(shop == 0){
				showAlert(false,'请选择店铺');
				return false;
			}
			$("input[name='thd_item_id[]']").removeAttr('checked');
			$("#select_all").removeAttr('checked');
			$(".history").each(function(){
				if($(this).find('span').html()=='未铺货'){
					$(this).prevAll().find("input[name='thd_item_id[]']").attr('checked','checked');
				}
			});
			break;
	}
	return;
}
//铺货状态
function changeStatus(shopID,itemID){
	str = $(".history[itemId='"+itemID+"']").find(".topHistory[shopId='"+shopID+"']").val();
	if(str){
		return ["已铺货",str];
	}else{
		return ["未铺货",''];
	}
}