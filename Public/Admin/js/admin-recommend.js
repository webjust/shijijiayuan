$("#related_goods_form_search_info").click(function(){
    var request_url = "/Admin/Goods/adminSearchGoods/?";
    $(".related_goods_form_info").each(function(){
        request_url += $(this).attr('name') + '=' + encodeURIComponent($(this).val()) + '&';
    });
    $.ajax({
        url:request_url,
        data:{},
        success:function(htmlObj){
            var htmls_options = "<option value='0'>请先下拉选择参与搭配的商品</option>";
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
function addRecommend(){
    $('.gname_error').html('');
    var fr_name = $('#fr_name').val();
    var fr_goods_id = $('#g_related_goods_ids_selected_info').val();
    var fr_price = $("#fr_price").val();
    var i=0;
    if(fr_name==''){
        $('#fr_name').next().html('搭配名称不能为空');i++;
    }
    
    if(fr_goods_id == '0'){
        $('#g_related_goods_ids_selected_info').next().html('请先选择商品信息');i++;
    }
    if(fr_price == ''){
        $('#fr_price').next().html('请输入搭配价');i++;
    }
    if(i!=0)return false;
    var startTime=$("#fr_statr_time").val(); 
    var endTime=$("#fr_end_time").val(); 
    var start=new Date(startTime.replace("-", "/").replace("-", "/"));  
    var end=new Date(endTime.replace("-", "/").replace("-", "/")); 
    if(start > end){
        showAlert(false,'出错了','活动开始时间大于活动实效时间时间！');
        return false;
    }
    var fr_original_price = parseFloat($("#fr_original_price").val()).toFixed(2);
    var fr_price = parseFloat($("#fr_price").val()).toFixed(2);
    if(fr_price-fr_original_price>0){
        showAlert(false,'出错了','搭配价不能大于商品销售价');
        return false;
    }
}
//显示图片
function showPic(){
    var pic = $("#g_related_goods_ids_selected_info").find("option:selected").attr('pic');
    $("#fr_original_price").val($("#g_related_goods_ids_selected_info").find("option:selected").attr('price'));
    $("#show_pic").attr("src",pic); 
    $("#fr_goods_picture").val(pic);
}
function checkGname(obj){
    $(obj).val(rtrim(ltrim($(obj).val())));
}
//去左空格;
function ltrim(s){
    return s.replace( /^\s*/, "");
}
//去右空格;
function rtrim(s){
    return s.replace( /\s*$/, "");
}

function checkedStatus(obj){
    var fr_status = $(obj).val();
    if(fr_status == '0'){
        $("#fr_status").val('1');
        $(obj).val('1');
    }else{
        $("#fr_status").val('0');
        $(obj).val('0');
    }
}
    	