// JavaScript Document
//搭配切换
$(function(){
    //放大镜开始
    //$('#example3').etalage({
    //    thumb_image_width: 415,
    //    thumb_image_height: 415,
    //    source_image_width: 900,
    //    source_image_height: 900,
    //    zoom_area_width: 450,
    //    zoom_area_height: 450,
    //    zoom_area_distance: 5,
    //    small_thumbs: 5,
    //    smallthumb_inactive_opacity: 0.5,
    //    smallthumbs_position: 'top',
    //    show_icon: true,
    //    icon_offset: 20,
    //    autoplay: false,
    //    keyboard: false,
    //    zoom_easing: false
    //});
    getRelateGoodsPage( $("#gid").val());
    $(".procon_r").fixedBox({
        'setEvent': 'scroll',
        'id': 2,
        'left': 0,
        boxT: $(".procon").offset().top,
        top: 0,
        zIndex: 99
    });

/*领取优惠券*/
    //$(".click_receive").click(function(){
    //    var obj = $(this),
    //        cname = obj.attr("cname");
    //    $.ajax({
    //        url:'/Home/Coupon/getReceive',
    //        cache:false,
    //        dataType:"json",
    //        type:'POST',
    //        data:{
    //            cname:cname
    //        },
    //        success:function(msgObj){
    //            if(msgObj.status == 1){
    //                //$.ThinkBox.success(msgObj.message);
    //                obj.parent().parent('li').addClass('hReceive');
    //                obj.parent().append('<i></i>');
    //                obj.html('<label>已领用</label>');
    //            }else{
    //                alert(msgObj.message);
    //                $.ThinkBox.error(msgObj.message);
    //            }
    //        }
    //    });
    //});

    $(".pro_peijian").find("li").click(function(){
        var index=$(this).index();
        $(".pro_peijian").find("li").removeClass("pro_peijian_cur");
        $(this).addClass("pro_peijian_cur");
        $(".yh_tab").hide().eq(index).show();
    })

    $(".pro_main_r").find('li').click(function(){
        $(".pro_main_r").find("li").removeClass("kszd_cur");
        $('.pro_info').hide();
        var tabApp = '#'+ $(this).find('a').data('id');
        $(tabApp).show();
        $(this).addClass("kszd_cur");
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
    /*优惠券 js start*/
    var onlineW=$(".online1").width();
    var winW=$(window).width();
    $(".online1").css({"left":(winW-onlineW)/2});
    $(".coupon").click(function(){
        $(".w1200").show();
        }
    )
    /*优惠券js结束*/
    //加入购物车
    //加入购物车
    $('#addToCart').click(function(){
        var pdt_id = $('#pdt_id').val();
        //配送区域选择 暂不开放
        //var is_global_stock = $('#is_global_stock').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var error_1 = $("#error_1").val();
        var error_2 = $("#error_2").val();
        var error_3 = $("#error_3").val();
        var error_4 = $("#error_4").val();
        //配送区域选择 暂不开放
        /*
        if(is_global_stock == '1'){
            var cr_id = parseInt($("#cr_ids").val());
            var cr_name = $('.province').html();
            if(isNaN(cr_id) || cr_name =='请选择配送区域'){
                $.ThinkBox.error("请选择配送区域");
                return;
            }
        }
        */
        if (isNaN(num)){
            $.ThinkBox.error(error_1);
            return;
        }
        if (num < 1){
            $.ThinkBox.error(error_1);
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error(error_2);
            return;
        }

        if (num > pdt_stock){
            $.ThinkBox.error(error_3);
            return;
        }
        if (pdt_id == "" || pdt_stock == ""){
            $.ThinkBox.error(error_4);
            return;
        }
        if (pdt_id == ""){
            $.ThinkBox.error(error_4);
            return;
        }
        //发送ajax请求
        $("#way_type").val('0');
        var data = $('#goodsForm').serialize();
        if (data != ''){
            $.post('/Home/Cart/doAdd', data, function(dataMsg){
                if(dataMsg.status){
                    $.ThinkBox.success(dataMsg.info);
                }else{
                    $.ThinkBox.error(dataMsg.info);
                }
                ajaxLoadShoppingCart(1);
            }, 'json');
        }
    });
    //立即购买
    $('#addToOrder').click(function(){
        var pdt_id = $('#pdt_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var is_global_stock = $('#is_global_stock').val();
        var error_1 = $("#error_1").val();
        var error_2 = $("#error_2").val();
        var error_3 = $("#error_3").val();
        var error_4 = $("#error_4").val();
        var no_login = $("#no_login").val();
        if(is_global_stock == '1'){
            var cr_id = parseInt($("#cr_ids").val());
            var cr_name = $('.province').html();
            if(isNaN(cr_id) || cr_name =='请选择配送区域'){
                $.ThinkBox.error("请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            $.ThinkBox.error(error_1);
            return;
        }
        if (num < 1){
            $.ThinkBox.error(error_1);
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error(error_2);
            return;
        }
        if (num > pdt_stock){
            $.ThinkBox.error(error_3);
            return;
        }
        if (pdt_id == ""){
            $.ThinkBox.error(error_4);
            return;
        }
        //发送ajax请求
        $("#way_type").val('1');
        var data = $('#goodsForm').serialize();
        if( no_login == ''){
            $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
                $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
            },'html');
            return false;
        }
        $.post('/Home/Cart/doAdd',data,function(dataMsg){
            if(dataMsg.status){
                $("#submitSkipItemPid").val(pdt_id);
                $("#submitSkipItemtype").val('0');
                $.ThinkBox.success(dataMsg.info);
                $("#submitSkipFrom").submit();
            }else{
                $.ThinkBox.error(dataMsg.info);
            }
        },'json');
    });
});
//选择组合商品规格
var MixPdtStock = 0;
function showSelect(obj){
    var _this = jQuery(obj);
    var item_id = $("#gid").val();
    var name = '';
    var cr_id = jQuery('#cr_ids').val();
	var open_stock = $("#open_stock").val();
	var stock_num = $("#stock_num").val();
	var stock_level = $("#stock_level").val();

    $("#main_pic").attr("src",_this.find("img:first").attr("src"));
    $("#main_pic").attr("jqimg",_this.find("img:first").attr("src"));
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
                    if(open_stock == 1 && stock_level !== ''){
						if(parseInt(vale[1]) < stock_num && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='color:red'>供货紧张</strong>");
						}else if(parseInt(vale[1]) > stock_num){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='color:green'>充足</strong>");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("<strong style='color:red'>缺货</strong>");
						}
					}else{
						if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余"+vale[1]+"件，下单后立即发货");
						}else if(parseInt(vale[1]) > 30){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
						}
					}
                    if($("#item_num").val() > vale[1]){
                        $("#item_num").val(vale[1])
                    }
                    if (vale[2]){
                        $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                        $("#showMarketPrice").html(parseFloat(vale[3]).toFixed(2));
                        $("#savePrice").html((parseFloat(vale[3] - vale[2]).toFixed(2)));
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
                        if(open_stock == 1 && stock_level !== ''){
							if(parseInt(vale[1]) < stock_num && parseInt(vale[1])-MixPdtStock>0){
								$("#pdt_stock").val(vale[1]);
								if($("#item_num").val() <= 0){
									$("#item_num").val(1);
								}
								$("#showNum").html("<strong style='color:red'>供货紧张</strong>");
							}else if(parseInt(vale[1]) > stock_num){
								$("#pdt_stock").val(vale[1]);
								if($("#item_num").val() <= 0){
									$("#item_num").val(1);
								}
								$("#showNum").html("<strong style='color:green'>充足</strong>");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='color:red'>缺货</strong>");
							}
						}else{
							if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余"+vale[1]+"件，下单后立即发货");
							}else if(parseInt(vale[1]) > 30){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
							}
						}
                        if($("#item_num").val() > vale[1]){

                            $("#item_num").val(vale[1]);
                        }
                        if (vale[2]){
                            $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                            $("#showMarketPrice").html(parseFloat(vale[3]).toFixed(2));
                            $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
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



function setTab(name,cursel,n){
    for(i=1;i<=n;i++){
        var tab=document.getElementById(name+i);
        var con=document.getElementById("con_"+name+"_"+i);
        tab.className=i==cursel?"onHover":"";
        con.style.display=i==cursel?"block":"none";
    }
}
//商品数量更改
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val());
    var max = $("#pdt_stock").val();
    //var min_num_per = $("#item_num").data('min');
    //if( min_num_per > 0 && IS_ON_MULTIPLE == 1) i = i * min_num_per;

    if(max ==''){
        return false;
    }
    max = parseInt(max);
    num=num+i;
    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
    _this.val(num);
}

/**
 * 获取相关商品
 */
function getRelateGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getRelateGoodsPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#relate_goods").html(msgObj);
            return false;
        }
    });
}
/**
 * 获取自由推荐商品
 * @param {type} gid
 * @returns {undefined}
 */
function getCollGoodsPage(gid){
    $.ajax({
        url:'/Home/Products/getCollGoodsPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid
        },
        success:function(msgObj){
            $("#coll_goods").html(msgObj);
            return false;
        }
    });
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

//获取咨询列表
function getGoodsAdvice(gid, page){
    $.ajax({
        url:'/Home/Products/getGoodsAdvice',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            page:page
        },
        success:function(msgObj){
            $("#advice").html(msgObj);
            return false;
        }
    });
}

//获得优惠券
function getcoupon(){
    $.ajax({
        url:'/Home/Coupon/index',
        dataType:'HTML',
        type:'POST',
        success:function(msgObj){
            $("#coupon").html(msgObj);
            return false;
        }
    });
}

/*加入收藏*/
function addToInterests(gid){
    if(parseInt(gid) <= 0){
        alert("商品不存在或者已经被下架");return false;
    }
    $.ajax({
        url:"/Home/Products/doAddGoodsCollect",
        cache:false,
        dataType:"json",
        data:{gid:gid},
        type:"post",
        success:function(msgObj){
            if(msgObj.status == '1'){
                $.ThinkBox.success("加入收藏成功");
            }else{
                $.ThinkBox.error(msgObj.info);
            }
        }
    });


}

function blurSelectNum(){
    var _this = $("#item_num");
    var max = parseInt(_this.attr('max'));
    var min_per_num = _this.data("min");
    var ereg_rule=/^\+?[1-9][0-9]*$/;
    if(!ereg_rule.test(_this.val())){
        _this.val(1);
    }else{
        if(min_per_num > 0){
            if(_this.val() % min_per_num != 0){
                $.ThinkBox.error("请填写"+ min_per_num +"的倍数！");
                _this.val(_this.data("current"));
                return false;
            }
        }
        if(_this.val()>max){
            _this.val(max);
        }
    }
}

/*优惠券隐藏*/
//function hidecoupon(obj){
//    var _this = jQuery(obj);
//    _this.parent().parent().parent().css("display","none");
//}