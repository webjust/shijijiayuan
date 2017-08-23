$(document).ready(function(){
    //全选切换
    $("#allCho").click(function(){
        if(this.checked){
            $(".all_check").attr('checked',true);
        }else{
            $(".all_check").attr('checked',false);
        }
    });
    
    $(".synTrddeliver").click(function(){
        var tt_id = $(this).attr("tt_id");
        var ts_id = $(this).attr("ts_id");
        var o_id = $(this).attr("o_id");
        var type = $("#deliveryType").val();
        //alert(type);return false;
        var url = '/Ucenter/Trddeliver/synDeliveryOrderToTrd';
        $.ajax({
            url:url,
            cache:false,
            dataType:'json',
            type:'POST',
            data:{'tt_id':tt_id,'o_id':o_id,'ts_id':ts_id,'type':type},
            beforeSend:function(){
                $("#ajax_loading").dialog({
                    height:150,
                    width:315,
                    modal:true,
                    title:'提示：努力加载中',
                    closeOnEscape:'false',
                    close:function (){
                        $("#ajax_loading").dialog('destroy');
                        $('#pro_diglog').append($('#ajax_loading'));
                    }
                });
            },
            success:function(msgObj){
                if(msgObj.success == '1'){
                    $("#ajax_loading").dialog('destroy');
                        $('#pro_diglog').append($('#ajax_loading'));
                    showAlert(true,'发货成功');
                    return false;
                }else{
                    $("#ajax_loading").dialog('destroy');
                    $('#pro_diglog').append($('#ajax_loading'));
                    showAlert(false,msgObj.msg);
                    return false;
                }
                
            }
        });
    });
	//标记一出来
    $(".haveTrddeliver").click(function(){
		if(confirm("您确定您要标记为已处理吗，标记已处理之后您的订单将无法进行发货处理")){
			var o_id = $(this).attr("o_id");
			var url = '/Ucenter/Trddeliver/doDeliveryOrderToSuccess';
			$.ajax({
				url:url,
				cache:false,
				dataType:'json',
				type:'POST',
				data:{'o_id':o_id},
				success:function(msgObj){
					if(msgObj.success == '1'){
						showAlert(true,msgObj.msg);
						location.reload();
						return false;
					}else{
						showAlert(false,msgObj.msg);
						return false;
					} 
				}
			});
		}
    });   
    
});

function Close(){
    $("#ajax_loading").dialog('destroy');
    $('#pro_diglog').append($('#ajax_loading'));
}

//批量发货
function batchTrddeliver(){
    var order	= {};
    var iid = 0;
    $(".trdorder tr td input[name='all_check']:checked").each(function(){
        var oid = $(this).attr("o_id");
        order[iid] = {};
        order[iid]['tt_id'] = $(this).attr("tt_id");
        order[iid]['o_id'] = $(this).attr("o_id");
        order[iid]['ts_id'] = $(this).attr("ts_id");
        order[iid]['type'] = $("#deliveryType").val();
        iid++;
    });
    if(iid == 0){
        showAlert(false,"请先选择需要发货的订单");
        return false;
    }
    $("#ajax_loading").dialog({
        height:150,
        width:315,
        modal:true,
        title:'提示：努力加载中',
        closeOnEscape:'false',
        close:function (){
            $("#ajax_loading").dialog('destroy');
            $('#pro_diglog').append($('#ajax_loading'));
        }
    });
    autoTrddeliver(order,0,iid-1);
}

function autoTrddeliver(order,i,count){
    if( i> count) {
        $("#ajax_loading").append('<div><table><tr><td width="100"><button type="button" onclick="window.history.go(0);" class="blue" style="color: rgb(255, 255, 255); background-color: rgb(23, 144, 203); text-decoration: none;">完 成</button></td><td><button type="button" class="blue" style="color: rgb(255, 255, 255); background-color: rgb(23, 144, 203); text-decoration: none;" onclick="Close();">关 闭</button></td></tr></table></div>');
        $("#ajax_loading #ajaxsenddiv_loading").html('提交完成！');
		window.location.reload();
        return false;
    }
    var tt_id = order[i]['tt_id'];
    var url = '/Ucenter/Trddeliver/batchTrddeliver';
    $.ajax({
        url:url,
        cache:false,
        dataType:'json',
        type:'POST',
        data:{'order':order},
        beforeSend:function(){
            $("#ajax_loading").append('<div id="ajaxsend_'+tt_id+'"><br>订单'+tt_id+'正在提交...</div>');
        },
        success:function(msgObj){
            if(msgObj.success == '1'){
                $("#ajax_loading #ajaxsend_"+tt_id).html('<br><div>订单'+tt_id+'<span style="color:green;">发货成功！</span></div>');
            }else{
                $("#ajax_loading #ajaxsend_"+tt_id).html('<br><div><span style="color:red;">'+msgObj.msg+'</span></div>');
            }
            i++;
            autoTrddeliver(order,i,count);
        }
    });
}

function DateTime(obj,id){
    var startDateTextBox = $("#"+obj);
    var endDateTextBox = $('#'+id);
    startDateTextBox.datetimepicker({ 
        changeMonth: true,
        minDate: new Date(1940, 1 - 1, 1),
        yearRange: '1940:+5',
        changeYear: true,
        onClose: function(dateText, inst) {
            if (endDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    endDateTextBox.datetimepicker('setDate', testStartDate);
            }
            else {
                endDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            endDateTextBox.datetimepicker('option', 'minDate', startDateTextBox.datetimepicker('getDate') );
        }
    });
    endDateTextBox.datetimepicker({ 
        changeMonth: true,
        changeYear: true,
        onClose: function(dateText, inst) {
            if (startDateTextBox.val() != '') {
                var testStartDate = startDateTextBox.datetimepicker('getDate');
                var testEndDate = endDateTextBox.datetimepicker('getDate');
                if (testStartDate > testEndDate)
                    startDateTextBox.datetimepicker('setDate', testEndDate);
            }
            else {
                startDateTextBox.val(dateText);
            }
        },
        onSelect: function (selectedDateTime){
            startDateTextBox.datetimepicker('option', 'maxDate', endDateTextBox.datetimepicker('getDate') );
        }
    }); 
}

