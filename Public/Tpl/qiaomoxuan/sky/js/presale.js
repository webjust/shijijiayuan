$(document).ready(function(){
    //商品详情页商品图片展示
    $('#example3').etalage({
        thumb_image_width: 415,
        thumb_image_height: 415,
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
    tagChange({
        tagObj:$('.tagarea li'),
        tagCon:$('.tagCon .ever'),
        et:'click',
        currentClass:'on'

    });
    tagChange({
        tagObj:$('.procon_r li'),
        tagCon:$('.tagCon .ever'),
        et:'click',
        currentClass:'cur'

    });
    //商品详细标签导航
    $(".tagarea").fixedBox({
        'setEvent': 'scroll',
        'id': 2,
        'left': 0,
        boxT: $(".procon").offset().top,
        top: 0,
        zIndex: 99999
    });

    $(".procon_r").fixedBox({
        'setEvent': 'scroll',
        'id': 2,
        'left': 0,
        boxT: $(".procon").offset().top,
        top: 0,
        zIndex: 99
    });


});


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
    var item_id = window.p_id;
    var item_num = $('#item_num').val();
    //console.log(item_id);return false;
    var name = '';
    var _item_id = jQuery('#' + item_id);
    if (_this.hasClass('on')){
        _this.removeClass("on");
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#surplus").html("0件");
        $.ThinkBox.error("请勾选您要的商品信息");
    } else{
        _this.siblings().removeClass("on");
        _this.addClass("on");
        var rsize = "";
        var showvalue = "";
        var _parent_color = jQuery("#sku" + item_id + '_1').find('a.on');
        //console.log(jQuery("#sku" + item_id + '_1'));
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
            //console.log(arr);
            //console.log(info);
            console.log(arr[info]);
            showvalue = window.arr[info]? window.arr[info]:"";
            var vale = showvalue.split("|");
            console.log(vale.length);
            if (vale.length > 1){
                $("#pdt_id").val(vale[0]);
                if (vale[1]){
                    $("#pdt_stock").val(vale[1]);
                    $("#surplus").html(vale[1] + "件");
                } else{
                    $("#surplus").html("0件");
                }
                if(item_num == 0) {
                    $('#item_num').val(1);
                }
                if (vale[2]){
                    //$("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                    //$("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                    //$("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                    //$("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                }
            }else{
                $("#pdt_id").val('');
                $("#pdt_stock").val('');
                $('#item_num').val(0);
                $("#surplus").html("0件");
            }
        }

    }else{
        var _parent_li = _this.parent().parent().find('a.on');
        rsize = _parent_li.attr('name');

        if (rsize != ""){
            var info = rsize;
            showvalue = window.arr[info];
        }
        if (showvalue != undefined){
            var vale = showvalue.split("|");
            if (vale.length > 1){
                $("#pdt_id").val(vale[0]);
                if (vale[1]){
                    $("#pdt_stock").val(vale[1]);
                    $("#surplus").html( vale[1] + "件");
                } else{
                    $("#surplus").html("0件");
                }
                if(item_num == 0) {
                    $('#item_num').val(1);
                }
                if (vale[2]){
                    //$("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                    //$("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                    //$("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                    //$("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                }
            }else{
                $("#pdt_id").val('');
                $("#pdt_stock").val('');
                $('#item_num').val(0);
                $("#surplus").html("0件");
            }
        }

    }
}

function addToOrder(i){
    var res = allSpecSelectedCheck('on');
    if(res[0] == false) {
        $.ThinkBox.error('请选择要购买的商品规格！');return false;
    }
    var pdt_id = $('#pdt_id').val();
    var num = parseInt($('#item_num').val());
    var max = parseInt($('#item_num').attr('max'));
    if(pdt_id == ''){
        $.ThinkBox.error("请选择商品规格，或您当前选择的规格商品不存在！");return false;
    }
    if (isNaN(num)){
        $('#item_num').val(0);
        $.ThinkBox.error("请输入正确的购买数量！");return false;
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
    data['cart']['p_id'] = window.p_id;
    data['cart']['num'] = num;
    data['type'] = 'presale';
    if(i==2){
        data['cart']['is_deposit'] = 1;
    }
    if(window.m_id == ''){
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


