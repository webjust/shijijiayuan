$(document).ready(function(){
    $('#example3').etalage({
        thumb_image_width: 415,
        thumb_image_height: 415,
        source_image_width: 900,
        source_image_height: 900,
        zoom_area_width: 450,
        zoom_area_height: 450,
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

    //商品详细标签切换
    tagChange({
        tagObj:$('.tagarea li'),
        tagCon:$('.tagCon .ever'),
        et:'click',
        currentClass:'on'

    });

    $("#item_num").blur(function(){
        var max = $("#pdt_stock").val();
        if(max ==''){
            $(this).val(0);
            return false;
        }max = parseInt(max);
        var num = this.value;
        if(isNaN(num) && max>0){
            $(this).val(1);
        }else if(max<=0){
            $(this).val(0);
        }else if(!isNaN(num) && num>0 && num<max){
            $(this).val(num);
        }else if(!isNaN(num) && num>0 && num>max){
            $(this).val(max);
        }else if(!isNaN(num) && num<0){
            $(this).val(1);
        }
    });

    var surplusNums = $("#surplus").html();
    if(0 == surplusNums){
        $("#addToOrder").attr("class", "notSpike");
    }
    $('#addToOrder').click(function(){
        var pdt_id = $('#pdt_id').val();
        var g_id = $('#g_id').val();
        var integral_id = $('#integral_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var delivery = $("#delivery").val();
        var valid_point = $("#valid_point").val();
        var integral_need = $("#integral_need").val();

        if(delivery == 1){
            var cr_id = parseInt($("#cr_ids").val());
            if(isNaN(cr_id)){
                showAlert(false,"请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            showAlert(false,$("#stock_error_1").val());
            return;
        }
        if (num < 1){
            showAlert(false,$("#stock_error_1").val());
            return;
        }
        if (pdt_id == ""){
            showAlert(false,$("#stock_error_4").val());
            return;
        }

        //发送ajax请求
//        var data = $('#goodsForm').serialize();
        var data = new Object();
        data['cart'] = {};
        data['cart']['pdt_id'] = pdt_id;
        data['cart']['g_id'] = g_id;
        data['cart']['integral_id'] = integral_id;
        data['cart']['num'] = num;
        data['type'] = 'integral';
        if($('#member').val() == ''){
            $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
                $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
            },'html');
            return false;
        }
        if(parseInt(valid_point) < parseInt(integral_need)){
            $.ThinkBox.success("您的积分不足，不可以兑换！");return;
        }
        if (data != ''){
//                data = data + '&skip=1';
            $.post('/Home/Cart/doAdd',data,function(dataMsg){
                if(dataMsg.status){
                    $.ThinkBox.success("正在跳转……");
                    location.href='/Ucenter/Orders/pageIntegralAdd';
                }else{
                    $.ThinkBox.error(dataMsg.info);
                }
            },'json');
        }
    });
});

function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val());
    var max = $("#pdt_stock").val();
    if(max ==''){
        return false;
    }
    max = parseInt(max);
    num=num+i;
    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
    _this.val(num);
}
//选择组合商品规格
var MixPdtStock = 0;
function showSelect(obj){
    var _this = jQuery(obj);
    var item_id =  $("#gid").val();
    var name = '';
    var cr_id = jQuery('#cr_ids').val();
    var money = $('#integral_price').val();
    if(parseInt(cr_id) <= 0){
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    }
    if (_this && typeof _this == 'object'){
        name = _this.attr('name');
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    }
    var _item_id = jQuery('#' + item_id);
    if (_this.hasClass('on')){
        _this.removeClass("on");
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $("#showError").html = "请勾选您要的商品信息";
    } else{
        _this.siblings().removeClass("on");
        _this.addClass("on");
        var rsize = "";
        var showvalue = "";
        var _parent_color = jQuery("#sku" + item_id + '_1').find('a.on');
        var _parent_size = jQuery("#sku" + item_id + '_2').find('a.on');
        var color_len = _parent_color.length;
        var size_len = _parent_size.length;

        if (size_len > 0 && color_len > 0){
            $("#propError").html("");
            var color = "", size = "";
            color = _parent_color.attr('name');
            size = _parent_size.attr('name');
            if (color != '' && size != ''){
                var info = size + ";" + color;
                showvalue = arr[info]?arr[info]:"";
                var vale = showvalue.split("|");
                /*
                *vale[0] pdt_id
                 * vale[1] pdt_stock
                 * vale[2] 商城价格
                 * vale[3] 市场价格
                */
                if (vale.length > 0){
                    if (vale[0]){
                        $("#pdt_id").val(vale[0]);
                    }
                    if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
                        $("#pdt_stock").val(vale[1]);
                        if($("#item_num").val() <= 0){
                            $("#item_num").val(1);
                        }
                        $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，剩余"+vale[1]+"件");
                    }else if(parseInt(vale[1]) > 30){
                        $("#pdt_stock").val(vale[1]);
                        if($("#item_num").val() <= 0){
                            $("#item_num").val(1);
                        }
                        //$("#showNum").html("<strong style='font-size:14px;'>有货</strong>");
                        $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，剩余"+vale[1]+"件");
                    }else if(parseInt(vale[1])-MixPdtStock <= 0){
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>");
                    }
                    if($("#item_num").val() > vale[1]){
                        $("#item_num").val(vale[1])
                    }
                    if (vale[2]){
                        //$("#showPrice").html("<strong><i>&yen;</i>"+ parseFloat(vale[2]).toFixed(2) + '</strong>');
                        //$("#showMarketPrice").html('<del><i>&yen;</i>'+ parseFloat(vale[3]).toFixed(2) + '</del>');
                        //$("#savePrice").html( '<em>'+ (parseFloat(vale[3] - vale[2]).toFixed(2)) + '</em>');
                        //$("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                        $("#showMarketPrice").html('<del><i>&yen;</i>'+ parseFloat(vale[2]).toFixed(2) + '</del>');
                        $("#savePrice").html( '<em>'+ (parseFloat(vale[2] - money).toFixed(2)) + '</em>');
                        $("#discountPrice").html(parseFloat(((money/vale[2])*10).toFixed(2)));
                    }
                }
            }
        } else{
            var _parent_li = _this.parent().parent().find('a.on');
            rsize = _parent_li.attr('name');

            if (rsize != ""){
                var info = rsize;
                showvalue = arr[info];
                if (showvalue != undefined){
                    var vale = showvalue.split("|");
                    if (vale.length > 0){
                        if (vale[0]){
                            $("#pdt_id").val(vale[0]);
                        }
                        if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
                            $("#pdt_stock").val(vale[1]);
                            $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，剩余"+vale[1]+"件");
                        }else if(parseInt(vale[1]) > 30){
                            $("#pdt_stock").val(vale[1]);
                            //$("#showNum").html("<strong style='font-size:14px;'>有货</strong>");
                            $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，剩余"+vale[1]+"件");
                        }else if(parseInt(vale[1])-MixPdtStock <= 0){
                            $("#pdt_stock").val(0);
                            $("#item_num").val(0);
                            $("#showNum").html("<strong style='font-size:14px;'>无货</strong>");
                        }
                        if($("#item_num").val() > vale[1]){

                            $("#item_num").val(vale[1]);
                        }
                        if (vale[2]){
                            //$("#showPrice").html("<strong><i>&yen;</i>"+ parseFloat(vale[2]).toFixed(2) + '</strong>');
                            $("#showMarketPrice").html('<del><i>&yen;</i>'+ parseFloat(vale[3]).toFixed(2) + '</del>');
                            $("#savePrice").html( '<em>'+ (parseFloat(vale[3] - vale[2]).toFixed(2)) + '</em>');
                            $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                        }
                    }else{
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>");
                    }
                }else{
                    $("#pdt_stock").val(0);
                    $("#item_num").val(0);
                    $("#showNum").html("<strong style='font-size:14px;'>无货</strong>");
                }
            }
        }
    }
}

//商品详情页 评论
function CommentPage(gid,page,type){
    $.ajax({
        url:'/Home/Comment/getCommentPage',
        dataType:'HTML',
        type:'POST',
        data:{
            gid:gid,
            p:page,
            type:type
        },
        success:function(msgObj){
            $("#recomment").html(msgObj);
            return false;
        }
    });
}

//获得购买记录 gid 商品id num 显示条数 p 第几页
function getBuyRecordPage(gid,num,p){
    $.ajax({
        url:'/Home/Products/getBuyRecordPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            num:num,
            p:p
        },
        success:function(msgObj){
            $("#buyrecord").html(msgObj);
            return false;
        }
    });
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
//标签切换
function tagChange(opts) {
    var opts = opts ? opts :{};
    var dftIdx = opts.defaultIdx ? opts.defaultIdx : '0' ;
    var curCls = opts.currentClass ? opts.currentClass : 'reg' ;
    var evt = opts.et ? opts.et : 'click' ;
    var tagObj = opts.tagObj;
    var tagCon = opts.tagCon;
    tagObj.eq(dftIdx).addClass(curCls).siblings().removeClass(curCls);
    tagCon.eq(dftIdx).show().siblings().hide();


    if(evt == 'click') {
        tagObj[evt](function(){
            var idx = $(this).index();
            $(this).addClass(curCls).siblings().removeClass(curCls);
            tagCon.eq(idx).show().siblings().hide();
        })
    }

}