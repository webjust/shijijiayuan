
$(document).ready(function($) {
    $("#item_num").blur(function () {
        var max = $("#pdt_stock").val();
        if (max == '') {
            $(this).val(0);
            return false;
        }
        max = parseInt(max);
        var num = this.value;
        if (isNaN(num) && max > 0) {
            $(this).val(1);
        } else if (max <= 0) {
            $(this).val(0);
        } else if (!isNaN(num) && num > 0 && num < max) {
            $(this).val(num);
        } else if (!isNaN(num) && num > 0 && num > max) {
            $(this).val(max);
        } else if (!isNaN(num) && num < 0) {
            $(this).val(1);
        }
    });

    var surplusNums = $("#surplus").html();
    if(0 == surplusNums){
        $("#addToOrder").attr("class", "notSpike");
        $("#addToOrder").html('已售完');
    }
    $('#addToOrder').click( function(){
        var res = allSpecSelectedCheck('on');
        if(res[0] == false) {
            $.ThinkBox.error('请选择要购买的商品规格！');return false;
        }
        var pdt_id = $('#pdt_id').val();
        var g_id = $('#gid').val();
        var sp_id = $('#sp_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());

        var is_global_stock = $('#is_global_stock').val();
        var is_spike = $("#is_spike").val();
        if(is_spike == 1){
            showAlert(false,"您已秒杀过该商品！");
            return false;
        }
        if(is_global_stock == '1'){
            var cr_id = parseInt($("#cr_ids").val());
            var cr_name = $('.province').html();
            if(isNaN(cr_id) || cr_name =='请选择配送区域'){
                $.ThinkBox.error("请选择配送区域");
                return false;
            }
        }

        if (isNaN(num)){
            showAlert(false,"请重新选择库存，库存大于零");
            return;
        }
        if (num < 1){
            showAlert(false,"请重新选择库存，库存大于零");
            return;
        }
        if (pdt_id == ""){
            showAlert(false,"库存不足或商品信息不存在");
            return;
        }
        if (num > 1){
            $.ThinkBox.error("秒杀商品限购1件");
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error("您选择货品的库存已不足");
            return;
        }
        if (num > pdt_stock){
            $.ThinkBox.error("请重新选择库存，库存大于可用库存");
            return;
        }

        //发送ajax请求
        //        var data = $('#goodsForm').serialize();
        var data = new Object();
        data['cart'] = {};
        data['cart']['pdt_id'] = pdt_id;
        data['cart']['g_id'] = g_id;
        data['cart']['sp_id'] = sp_id;
        data['cart']['num'] = num;
        data['type'] = 'spike';
        var m_id = $('#m_id').val();
        console.log(m_id);
        if(m_id == ''){
            location.href="/Wap/User/login?jumpUrl=/Wap/Spike/detail?sp_id="+sp_id;return false;
        }
        if (data != ''){
            $.ajax({
                url:'/Wap/Cart/doAdd',
                dataType:'json',
                type:'POST',
                data: data,
                success:function(dataMsg){
                    if(dataMsg.status){
                        $.ThinkBox.success("正在跳转……");
                        location.href='/Wap/Orders/pageSpikeAdd';
                        return false;
                    }else{
                        $.ThinkBox.error(dataMsg.msg);
                    }
                },
                error:function(){
                    alert('请求失败！');
                }
            });

        }
    });

});
function allSpecSelectedCheck(on_class) {
    var _gsd_arr = [];
    var _select_all = true;
    $('.spec_list').each(function(){
        var _selected_spec = $(this).find('a.'+on_class);
        if(_selected_spec.length) {
            var gsd_id = _selected_spec.attr('data-value');
            _gsd_arr.push(gsd_id);
        }else{
            _select_all = false;
            return false
        }
    });

    return [_select_all, _gsd_arr];
}







