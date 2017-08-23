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
    $(".procon_r").fixedBox({
        'setEvent': 'scroll',
        'id': 2,
        'left': 0,
        boxT: $(".procon").offset().top,
        top: 0,
        zIndex: 99
    });
    //商品详细标签切换
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

    //$("#item_num").blur(function(){
    //    var max = $("#pdt_stock").val();
    //    if(max ==''){
    //        $(this).val(0);
    //        return false;
    //    }max = parseInt(max);
    //    var num = this.value;
    //    if(isNaN(num) && max>0){
    //        $(this).val(1);
    //    }else if(max<=0){
    //        $(this).val(0);
    //    }else if(!isNaN(num) && num>0 && num<max){
    //        $(this).val(num);
    //    }else if(!isNaN(num) && num>0 && num>max){
    //        $(this).val(max);
    //    }else if(!isNaN(num) && num<0){
    //        $(this).val(1);
    //    }
    //});

    var surplusNums = $("#surplus").html();
    if(0 == surplusNums){
        $("#addToOrder").attr("class", "notSpike");
    }
    $('#addToOrder').click(function(){
        var res = allSpecSelectedCheck('on');
        if(res[0] == false) {
            $.ThinkBox.error('请选择要购买的商品规格！');return false;
        }
        var pdt_id = $('#pdt_id').val();
        var g_id = $('#g_id').val();
        var sp_id = $('#sp_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var delivery = $("#delivery").val();
		var is_spike = $("#is_spike").val();
		if(is_spike == 1){
			showAlert(false,"您已秒杀过该商品！");
			return false;
		}
        if(delivery == 1){
            var cr_id = parseInt($("#cr_ids").val());
            if(isNaN(cr_id)){
                showAlert(false,"请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            $.ThinkBox.error("请重新选择库存，库存大于零");
            return;
        }
        if (num < 1){
            $.ThinkBox.error("请重新选择库存，库存大于零");
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
        if (pdt_id == ""){
            $.ThinkBox.error("库存不足或商品信息不存在");
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
        if("{$Think.session.Members}" == ''){
            $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
                $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
            },'html');
            return false;
        }
        if (data != ''){
//                data = data + '&skip=1';
            $.post('/Home/Cart/doAdd',data,function(dataMsg){
                if(dataMsg.status){
                    $.ThinkBox.success("正在跳转……");
                    location.href='/Ucenter/Orders/pageSpikeAdd';
                }else{
                    $.ThinkBox.error(dataMsg.msg);
                }
            },'json');
        }
    });
});

//function countNum(i){
//    var _this = $("#item_num");
//    var num=parseInt(_this.val());
//    var max = $("#pdt_stock").val();
//    if(max ==''){
//        return false;
//    }
//    max = parseInt(max);
//    num=num+i;
//    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
//    _this.val(num);
//}
//选择组合商品规格
var MixPdtStock = 0;
function showSelect(obj){
    var _this = jQuery(obj);
    var item_id =  $("#gid").val();
    var name = '';
    var cr_id = jQuery('#cr_ids').val();
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
                        $("#showMarketPrice").html('<del><i>&yen;</i>'+ parseFloat(vale[3]).toFixed(2) + '</del>');
                        $("#savePrice").html( '<em>'+ (parseFloat(vale[3] - vale[2]).toFixed(2)) + '</em>');
                        $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
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
        type:'GET',
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
        type:'POST',
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