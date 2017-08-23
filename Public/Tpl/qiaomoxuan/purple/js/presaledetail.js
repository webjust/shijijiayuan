//商品详情页商品图片展示
$('#example3').etalage({
    thumb_image_width: 328,
    thumb_image_height: 328,
    source_image_width: 900,
    source_image_height: 900,
    zoom_area_width: 500,
    zoom_area_height: 500,
    zoom_area_distance: 5,
    small_thumbs: 5,
    smallthumb_inactive_opacity: 0.5,
    smallthumbs_position: 'top',
    show_icon: true,
    icon_offset: 20,
    autoplay: false,
    keyboard: false,
    zoom_easing: false
    });
var arr = new Object();
//将商品库存信息存在js数组里
    <foreach name = 'good_info.skus' item = 'vosku'>
    arr["{$vosku.skuName}"] = "{$vosku.pdt_id}|{$p_number}|{$vosku.pdt_sale_price}|{$vosku.pdt_market_price}";
    </foreach>
//更改数量
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val()),max=parseInt(_this.attr('max'));
    num=num+i;
    if((num<=0)||(num>max)||(num>999)){return false;}
_this.val(num);
}
//选择商品规格
function showSelect(obj){
    var _this = $(obj);
    var item_id = "{$ary_request.p_id}";
var name = '';
var yprice = "{$gp_price}";
var _item_id = jQuery('#' + item_id);
if (_this.hasClass('on')){
    _this.removeClass("on");
    $("#pdt_stock").val("");
    $("#pdt_id").val("");
    $("#showNum").html = "";
    $.ThinkBox.error("请勾选您要的商品信息");
    } else{
    _this.siblings().removeClass("on");
    _this.addClass("on");
    var rsize = "";
    var showvalue = "";
    var _parent_color = jQuery("#sku" + item_id + '_1').find('a.on');
    console.log(jQuery("#sku" + item_id + '_1'));
    var _parent_size = jQuery("#sku" + item_id + '_2').find('a.on');
    var color_len = _parent_color.length;
    var size_len = _parent_size.length;
    }
if (size_len > 0 && color_len > 0){
    $("#propError").html("");
    var color = "", size = "";
    color = _parent_color.attr('name');
    size = _parent_size.attr('name');
    if (color != '' && size != ''){
    var info = size + ";" + color;
    showvalue = arr[info]?arr[info]:"";
    var vale = showvalue.split("|");
    if (vale.length > 0){
    if (vale[0]){
    $("#pdt_id").val(vale[0]);
    }
if (vale[1]){

    $("#pdt_stock").val(vale[1]);
    $("#showNum").html("件（库存" + vale[1] + "件）");
    } else{
    $("#showNum").html("库存已不足0件");
    }
if (vale[2]){
    $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
    $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
    $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
    $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
    }
}
}

}else{
    var _parent_li = _this.parent().parent().find('a.on');
    rsize = _parent_li.attr('name');

    if (rsize != ""){
    var info = rsize;
    showvalue = arr[info];
    }
if (showvalue != undefined){
    var vale = showvalue.split("|");
    if (vale.length > 0){
    if (vale[0]){
    $("#pdt_id").val(vale[0]);
    }
if (vale[1]){
    $("#pdt_stock").val(vale[1]);
    $("#showNum").html("库存还剩" + vale[1] + "件");
    } else{
    $("#showNum").html("库存已不足0件");
    }
if (vale[2]){
    $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
    $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
    $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
    $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
    }
}
}

}
}
function blurSelectNum(){
    var _this = $("#item_num");
    var max = parseInt(_this.attr('max'));
    var ereg_rule=/^\+?[1-9][0-9]*$/;
    if(!ereg_rule.test(_this.val())){
    _this.val(1);
    }else{
    if(_this.val()>max){
    _this.val(max);
    }
}
}

function addToOrder(i){
    var pdt_id = $('#pdt_id').val();
    var p_id = "{$p_id}";
var num = parseInt($('#item_num').val());
var max = parseInt($('#item_num').attr('max'));
if(pdt_id == ''){
    $.ThinkBox.error("{$Think.lang.STOCK_ERROR_4}");return false;
}
if (isNaN(num)){
    $.ThinkBox.error("{$Think.lang.STOCK_ERROR_1}");return false;
}
if (num < 1){
    $.ThinkBox.error("购买数量不能小于1");return false;
    }
if(num > max){
    $.ThinkBox.error("您最多还能预售"+max+"件");return false;
    }
var data = new Object();
data['cart'] = {};
data['cart']['pdt_id'] = pdt_id;
data['cart']['p_id'] = p_id;
data['cart']['num'] = num;
data['type'] = 'presale';
if(i==2){
    data['cart']['is_deposit'] = 1;
    }
if("{$Think.session.Members}" == ''){
    $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
    $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
},'html');
return false;
}
$.post('/Home/Cart/doAdd',data,function(dataMsg){
    if(dataMsg.status == 1){
    location.href = dataMsg.url;
    }else{
    $.ThinkBox.error('您不能预售该商品或商品已下架');
    }
},'json');
}


    $('.classiCon').css('display','none');
    $('.allSpan').mouseover(function(){
        $('.classiCon').css('display','block');
        });
    $('.allClassi').mouseleave(function(){
        $('.classiCon').css('display','none');
        });
