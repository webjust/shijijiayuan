/**
 * Created by Nick on 2015/8/14.
 */
//实例化编辑器
UE.getEditor('editor');
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
            $("#p_pic").val(image_path);
            $("#show_pic").attr({src:image_path});

        }
    });

});

function upImage() {
    dialog.open();
}

//新增
function addTieredPrice(){
    var discount_hide = $("#price_discount_hide");
    var discount_rate = $("#price_discount_rate_hide");
    var dicount_type = $(':radio[name="p_tiered_pricing_type"]:checked').val();
    //console.log(dicount_type);
    if(dicount_type == 1) {
        var tr_html = discount_hide.clone().removeAttr('id');
    }else {
        var tr_html = discount_rate.clone().removeAttr('id');
    }
    tr_html.insertBefore(discount_hide).show();
    tr_html.find('input.nums').attr('name','nums[]');
    tr_html.find('input.prices').attr('name','prices[]');
    //console.log(tr_html.html());
}
/**
 * 变更价格阶梯减免类型
 * @param dom
 */
function changeDiscountType(dom) {
    var discount_type = $(dom).val();
    //console.log(discount_type);
    var tr_show = $('.price_discount_init[data-type="'+discount_type+'"]').show();
    tr_show.find('input').attr('name', 'p_price');
    tr_show.siblings('.price_discount_init').hide().find('input').attr('name', '');
    $('.tiered_price_config:visible').remove();
}

$("document").ready(function(){
    //区域选择
    $(".rule-chooser-trigger").click(function(){
        var show_cat = $("#shopMulti_cat").css('display');
        if($("#shopMulti_cat").css('display') == 'block'){
            $("#shopMulti_cat").css("display","none");
        }else{
            $("#shopMulti_cat").css("display","block");
        }
    });
    //搜索商品
    $("#related_goods_form_search_info").click(function(){
        var request_url = "/Admin/Goods/adminSearchGoods?";
        $(".related_goods_form_info").each(function(){
            request_url += $(this).attr('name') + '=' + encodeURIComponent($(this).val()) + '&';
        });
        $.ajax({
            url:request_url,
            data:{},
            success:function(htmlObj){
                var htmls_options = "<option value='0'>请先下拉选择参与预售活动的商品</option>";
                for (var x in htmlObj){
                    var goods = htmlObj[x];
                    htmls_options += '<option pic="' + goods.g_picture+ '" ' + 'price="' + goods.g_price+ '" value="' +  goods.g_id + '" g_name="'+ goods.g_name + '">' + goods.g_name+',价格：'+ goods.g_price+ '</option>';
                }
                $("#g_related_goods_ids_selected_info").html(htmls_options);
            },
            type:'GET',
            timeout:30000,
            dataType:'json'
        });
    });

    //删除
    $(".deletePrice").live("click",function(){
        var tr_html = this.parentNode.parentNode;
        tr_html.parentNode.removeChild(tr_html);
    });


    $("#delPic").click(function(){
        var url = $(this).attr("url");
        $.ajax({
            url:url,
            cache:false,
            dataType:'json',
            type:'POST',
            success:function(msgObj){
                $("#show_pic").css("display","none");
                $("#p_pic").val('');
            },
            error:function(msgObj){
                alert('删除失败');
            }
        });
    });

    //提交表单数据
    if($("#presale_add").length) {
        $('#presale_add').validate();
        $("#presale_add").submit(presaleDoAdd);
    }
    if($("#presale_edit").length) {
        $('#presale_edit').validate();
        $("#presale_edit").submit(presaleDoEdit);
    }
});

/**
 * 检查预售价
 */
function checkPresalePrice(dom){
    var item_price = parseFloat($("#item_price").val()).toFixed(2);
    var p_price = parseFloat($("#p_price").val()).toFixed(2);
    if(p_price-item_price>0){
        showAlert(false,'预售初始价不能大于商品销售价');
    }
}

function validateBeforeSubmit(form_id) {
    var p_title = $('#p_title').val();
    if(p_title.length>250 || p_title.length<=0){
        showAlert(false,'预售标题必须输入且不能大于250个字符');
        return false;
    }
    var type = $('#good_type').val();
    if(type == '1'){
        var g_id = $('#g_related_goods_ids_selected_info').val();
        if(g_id == '0'){
            showAlert(false,'请先选择商品信息');
            return false;
        }
    }
    var startTime=$("#p_start_time").val();
    var endTime=$("#p_end_time").val();
    if(!startTime || !endTime) {
        showAlert(false,'请输入活动开始时间和活动结束时间！');
        return false;
    }
    var start=new Date(startTime.replace("-", "/").replace("-", "/"));
    var end=new Date(endTime.replace("-", "/").replace("-", "/"));
    if(start > end){
        showAlert(false,'活动开始时间不能大于活动结束时间！');
        return false;
    }
    if($("#is_deposit:checked").length){
        var deposit_price = parseFloat($('#p_deposit_price').val() ? $('#p_deposit_price').val()  : 0);
        if(isNaN(deposit_price) || deposit_price <= 0){
            showAlert(false,'启用定金后定金金额必须输入，请输入大于0小于预售总金额的数字！');
            return false;
        }

        var startTime1=$("#p_overdue_start_time").val();
        var endTime1=$("#p_overdue_end_time").val();
        //console.log(startTime1);
        //console.log(endTime1);
        if(!startTime1 || !endTime1) {
            showAlert(false,'请输入补交余款开始时间和补交余款结束时间！');
            return false;
        }
        var start=new Date(startTime1.replace("-", "/").replace("-", "/"));
        var end=new Date(endTime1.replace("-", "/").replace("-", "/"));
        if(!start || !end) {
            showAlert(false,'请输入补交余款开始时间和活动结束时间！');
            return false;
        }else if(start > end){
            showAlert(false,'补交余款开始时间不能大于活动结束时间！');
            return false;
        }
    };

    var gp_number = $('#p_number').val();
    gp_number = gp_number || 0;
    if(isNaN(gp_number) || gp_number<0){
        showAlert(false,'请输入合法的预售数量！');
        return false;
    }
    var gp_per_number = $('#p_per_number').val();
    gp_per_number = gp_per_number || 0;
    if(isNaN(gp_per_number) || gp_per_number<0){
        showAlert(false,'请输入合法的限购数量！');
        return false;
    }
    var res = $('#'+form_id).valid();
    if(!res) return false;

    var item_price = parseFloat($("#item_price").val()).toFixed(2);
    var p_price = parseFloat($('input[name="p_price"]:visible').val()).toFixed(2);
    if(p_price - item_price>0){
        showAlert(false,'预售金额不能小于0');
        return false;
    }
    var tiered_pricing_type = $(':radio[name="p_tiered_pricing_type"]:checked').val();
    var tiered_price_check = true;
    if($('[name="nums\[\]"]:visible').length) {
        $('[name="nums\[\]"]:visible').each(function () {
            var num = parseInt($(this).val() ? $(this).val() : 0);
            //console.log(num);
            if(num <= 0 ) {
                showAlert(false,'价格阶梯销售数量不能小于0');
                tiered_price_check = false;
            }
        });
    }
    if(tiered_pricing_type == 1) {
        if($('[name="prices\[\]"]:visible').length) {
            $('[name="prices\[\]"]:visible').each(function () {
                var tiered_price = parseFloat($(this).val() ? $(this).val() : 0);
                //console.log(tiered_price);
                if(tiered_price < 0) {
                    showAlert(false,'预售金额不能大于销售金额');
                    tiered_price_check = false;
                }else if(tiered_price > item_price) {
                    showAlert(false,'预售金额不能小于0');
                    tiered_price_check = false;
                }
            });
        }
    }else {
        if($('[name="prices\[\]"]:visible').length) {
            $('[name="prices\[\]"]:visible').each(function () {
                var tiered_price = parseFloat($(this).val() ? $(this).val() : 0);
                //console.log(tiered_price);
                if(tiered_price > 1) {
                    showAlert(false,'预售折扣不能大于1');
                    tiered_price_check = false;
                }else if(tiered_price < 0) {
                    showAlert(false,'预售折扣不能小于0');
                    tiered_price_check = false;
                }
            });
        }
    }
    if(!tiered_price_check) return false;

    return true;
}
/**
 * 新增预售
 */
function presaleDoAdd(){
    //表单数据合法性验证
    var check_res = validateBeforeSubmit('presale_add');
    if(!check_res) return false;
    $('#btnB').removeClass('btnA').addClass('btnG').attr('disabled', true);
    $('#loading').show();
    var formdata = $("#presale_add").serialize();
	var p_desc = UE.getEditor('editor').getContent();
	if(p_desc){
		formdata = formdata+'&p_desc='+p_desc;
	}		
    $.ajax({
        url:"/Admin/Presale/doAdd",
        type:"POST",
        data:formdata,
        dataType:"json",
        success:function(msgObj){
            //console.log(msgObj);
            if(msgObj.status == 1){
                window.location.href = "/Admin/Presale/pageList";
            }else{
                $('#btnB').removeClass('btnG').addClass('btnA').attr('disabled', false);
                $('#loading').hide();
                showAlert(false,msgObj.info);
            }
        },
        error: function(msgObj) {
            //console.log(msgObj);
            $('#btnB').removeClass('btnG').addClass('btnA').attr('disabled', false);
            $('#loading').hide();
            showAlert(false,'新增预售商品失败，请稍后重试。。。');
        }
    });

    return false;
}

function presaleDoEdit() {

    //表单数据合法性验证
    var check_res = validateBeforeSubmit('presale_edit');
    if(!check_res) return false;
    $('#btnB').removeClass('btnA').addClass('btnG').attr('disabled', true);
    $('#loading').show();
    var formdata = $("#presale_edit").serialize();
	var p_desc = UE.getEditor('editor').getContent();
	if(p_desc){
		formdata = formdata+'&p_desc='+p_desc;
	}		
    $.ajax({
        url:"/Admin/Presale/doEdit",
        type:"POST",
        data:formdata,
        dataType:"json",
        success:function(msgObj){
            //console.log(msgObj);
            if(msgObj.status == 1){
                showAlert(true,msgObj.info);
                //window.location.href = "/Admin/Presale/pageList";
            }else{
                showAlert(false,msgObj.info);
            }
            $('#btnB').removeClass('btnG').addClass('btnA').attr('disabled', false);
            $('#loading').hide();
        },
        error: function(msgObj) {
            //console.log(msgObj);
            $('#btnB').removeClass('btnG').addClass('btnA').attr('disabled', false);
            $('#loading').hide();
            showAlert(false,'保存预售商品失败，请稍后重试。。。');
        }
    });

    return false;
}
//显示图片
function showPic(obj){
    var pic = $("#g_related_goods_ids_selected_info").find("option:selected").attr('pic');
    var goods_name = $("#g_related_goods_ids_selected_info").find("option:selected").attr('g_name');
    var normal_price = $("#g_related_goods_ids_selected_info").find("option:selected").attr('price');
    $("#p_title").val(goods_name);
    $("#item_price").val(normal_price);
    $("#p_pic").val(pic);
    $("#show_pic").attr("src",pic);
    $('#normal_sale_price').text(normal_price);
    changePresaleGoods(obj);
}
/**
 * 变更预售商品
 * @param dom
 */
function changePresaleGoods(dom) {
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
