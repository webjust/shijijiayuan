/**
 * Created by Nick on 2015/11/18.
 */
//实例化编辑器
UE.getEditor('editor');
//实例化编辑器
UE.getEditor('mobile_editor');
var dialog;
var editor = new UE.ui.Editor({
    imageRealPath:"editor"
});
editor.render("myEditor");
editor.ready(function(){
    editor.hide()
    dialog = editor.getDialog("insertimage");
    editor.addListener('beforeInsertImage',function(t, arg){
        for(var i in arg){
            var image_path = arg[i]['src'];
            $("#sp_pic").val(image_path);
            $("#show_pic").attr({src:image_path});

        }
    });

});
function upImage() {
    dialog.open();
}

$(document).ready(function() {
    $("#spike_add").validate();
    $(".rule-chooser-trigger").click(function() {
        var show_cat = $("#shopMulti_cat").css('display');
        if ($("#shopMulti_cat").css('display') == 'block') {
            $("#shopMulti_cat").css("display", "none");
        } else {
            $("#shopMulti_cat").css("display", "block");
        }
    });

    $("#related_goods_form_search_info").click(function(){
        var request_url = "/Admin/Goods/adminSearchGoods?";
        $(".related_goods_form_info").each(function(){
            request_url += $(this).attr('name') + '=' + encodeURIComponent($(this).val()) + '&';
        });
        $.ajax({
            url:request_url,
            data:{},
            success:function(htmlObj){
                var htmls_options = "<option value='0'>请先下拉选择参与秒杀活动的商品</option>";
                for (var x in htmlObj){
                    var goods = htmlObj[x];
                    htmls_options += '<option pic="' + goods.g_picture+ '" ' + 'price="' + goods.g_price+ '" value="' +  goods.g_id + '">' + goods.g_name+',价格：'+ goods.g_price+ '</option>';
                }
                $("#g_related_goods_ids_selected_info").html(htmls_options);
            },
            type:'GET',
            timeout:30000,
            dataType:'json'
        });
    });

});

//显示图片
function showPic(dom){
    var pic = $("#g_related_goods_ids_selected_info").find("option:selected").attr('pic');
    $("#item_price").val($("#g_related_goods_ids_selected_info").find("option:selected").attr('price'));
    $("#sp_pic").val(pic);
    $("#show_pic").attr("src",pic);
    changeSpikeGoods(dom);
}

/**
 * 变更预售商品
 * @param dom
 */
function changeSpikeGoods(dom) {
    var goods_id = $(dom).val();
    if(goods_id != '') {
        $.ajax({
            url: '/Admin/Presale/getGoodsTr',
            type: 'post',
            dataType: 'json',
            data: {g_id: goods_id},
            success: function (json) {
                if(json.status) {
                    $('#getGoodsTr').html(json.data);
                }else {
                    $.ThinkBox.error(json.message);
                }
                return false;
            }
        });
    }
}
/**
 * 变更价格阶梯减免类型
 * @param dom
 */
function changeDiscountType(dom) {
    var discount_type = $(dom).val();
    //console.log(discount_type);
    var tr_show = $('.price_discount_init[data-type="'+discount_type+'"]').show();
    tr_show.find('input').attr('name', 'sp_price');
    tr_show.siblings('.price_discount_init').hide().find('input').attr('name', '');
    $('.tiered_price_config:visible').remove();
}

/**
 * 检查预售价
 */
function checkSpikePrice(dom){
    var item_price = parseFloat($("#item_price").val()).toFixed(2);
    var sp_price = parseFloat($(dom).val()).toFixed(2);
    var sp_price_type = $('[name="sp_tiered_pricing_type"]:checked').val();
    switch (sp_price_type) {
        case 1:
            if(sp_price - item_price>0){
                showAlert(false,'秒杀优惠价不能大于商品销售价');
            }
            break;
        case 2:
            if(sp_price > 1 || sp_price <= 0) {
                showAlert(false, '请填写0（不包括）到1（包括）之间的2位小数数字');
            }
            break;
    }

}