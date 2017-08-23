/**
 * 商品详情页
 * add by zhangjiasuo
 * date 2015-05-14 13:45:30
 */
//低于MixPdtStock库存显示无货
var MixPdtStock = 0;
function showSelect(obj){
    var _this = jQuery(obj);
    _this.siblings().removeClass("cur");
    _this.addClass("cur");
    var item_id = $("#gid").val();
    var name = '';
    var cr_id = jQuery('#cr_ids').val();
	var open_stock = $("#open_stock").val();
	var stock_num = $("#stock_num").val();
	var stock_level = $("#stock_level").val();
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
							$("#showNum").html("(库存：<strong style='color:red'>供货紧张</strong>)");
						}else if(parseInt(vale[1]) > stock_num){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("(库存：<strong style='color:green'>充足</strong>)");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("(库存：<strong style='color:red'>缺货</strong>)");
						}
					}else{
						if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("(库存："+vale[1] +"件)");
						}else if(parseInt(vale[1]) > 30){
							$("#pdt_stock").val(vale[1]);
							if($("#item_num").val() <= 0){
								$("#item_num").val(1);
							}
							$("#showNum").html("(库存："+vale[1] +"件)");
						}else if(parseInt(vale[1])-MixPdtStock <= 0){
							$("#pdt_stock").val(0);
							$("#item_num").val(0);
							$("#showNum").html("(库存：0件)");
						}
					}
                    if($("#item_num").val() > vale[1]){
                        $("#item_num").val(vale[1])
                    }
                    if (vale[2]){
                        $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                        $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                        $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
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
                            $("#showNum").html("(库存:<strong style='color:red'>供货紧张</strong>)");
							}else if(parseInt(vale[1]) > stock_num){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("(库存:<strong style='color:green'>充足</strong>)");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("(库存:<strong style='color:red'>缺货</strong>)");
							}
						}else{
							if(parseInt(vale[1]) < 30 && parseInt(vale[1])-MixPdtStock>0){
                            $("#pdt_stock").val(vale[1]);
                            $("#showNum").html("(库存:"+vale[1] +"件)");
							}else if(parseInt(vale[1]) > 30){
								$("#pdt_stock").val(vale[1]);
								$("#showNum").html("(库存:"+vale[1] +"件)");
							}else if(parseInt(vale[1])-MixPdtStock <= 0){
								$("#pdt_stock").val(0);
								$("#item_num").val(0);
								$("#showNum").html("(库存:0件)");
							}
						}
                        
                        if($("#item_num").val() > vale[1]){

                            $("#item_num").val(vale[1]);
                        }
                        if (vale[2]){
                            $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                            $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                            $("#savePrice").html(parseFloat(vale[3] - vale[2]).toFixed(2));
                            $("#discountPrice").html(parseFloat(((vale[2]/vale[3])*10).toFixed(2)));
                        }
                    }else{
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("(库存:0件)");
                    }
                }else{
                    $("#pdt_stock").val(0);
                    $("#item_num").val(0);
                    $("#showNum").html("(库存:0件)");
                }
            }
        }
    }
}
//选择组合商品规格
function selectGoods(obj){
    var color = $(obj).attr('name');
    var _thisclass = $(obj).attr('class');
    var _this = jQuery(obj);
    if(_this.hasClass("on")){
        return false;
    }
    _this.siblings().removeClass("on");
    _this.addClass("on");
    var slips = $(obj).parent().attr('slip');
    var this_spec_name = '';
    $("dd[slip='"+slips+"']").find('a').each(function(){
        if($(this).hasClass('on')){
            this_spec_name += $(this).parent().attr('slip')+":"+$(this).attr('name')+';';
        }
    });
    this_spec_name = this_spec_name.substring(0,(this_spec_name.length-1));
    if(goods_url[this_spec_name] != null){
        location.href = goods_url[this_spec_name];
    }
}

//商品数量更改
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val());
    var max = $("#pdt_stock").val();
    //var min_num_per = $("#item_num").data('min');
    //if( min_num_per > 0 && IS_ON_MULTIPLE == 1 )  i = i * min_num_per;
    if(max ==''){
        return false;
    }
    max = parseInt(max);
    num=num+i;
    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
    _this.val(num);
}

// 商品详情放大镜
$(document).ready(function($){
    //$('#example3').etalage({
    //    thumb_image_width: 450,
    //    thumb_image_height: 450,
    //    source_image_width: 900,
    //    source_image_height: 900,
    //    zoom_area_width: 500,
    //    zoom_area_height:400,
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
    //立即购买
    $('#addToOrder').click(function(){
        var pdt_id = $('#pdt_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var is_global_stock = $('#is_global_stock').val();
        var no_login = $("#no_login").val();
        if(is_global_stock == '1'){
            var cr_id = parseInt($("#cr_ids").val());
            var cr_name = $('.province').html();
            if(isNaN(cr_id) || cr_name =='请选择配送区域'){
                //showAlert(false,"请选择配送区域");
                $.ThinkBox.error("请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            $.ThinkBox.error( $("#stock_error_1").val());
            return;
        }
        if (num < 1){
            $.ThinkBox.error($("#stock_error_1").val());
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error($("#stock_error_2").val());
            return;
        }
        if (num > pdt_stock){
            $.ThinkBox.error($("#stock_error_3").val());
            return;
        }
        if (pdt_id == ""){
            $.ThinkBox.error($("#stock_error_4").val());
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

	//加入购物车
    $('#addToCart').click(function(){
        var pdt_id = $('#pdt_id').val();
        var is_global_stock = $('#is_global_stock').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        if(is_global_stock == '1'){
            var cr_id = parseInt($("#cr_ids").val());
            var cr_name = $('.province').html();
            if(isNaN(cr_id) || cr_name =='请选择配送区域'){
                $.ThinkBox.error("请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            $.ThinkBox.error($("#stock_error_1").val());
            return;
        }
        if (num < 1){
            $.ThinkBox.error($("#stock_error_1").val());
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error($("#stock_error_2").val());
            return;
        }

        if (num > pdt_stock){
            $.ThinkBox.error($("#stock_error_3").val());
            return;
        }
        if (pdt_id == "" || pdt_stock == ""){
            $.ThinkBox.error($("#stock_error_4").val());
            return;
        }
        if (pdt_id == ""){
            $.ThinkBox.error($("#stock_error_4").val());
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

});
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
                alert("加入收藏成功");
            }else{
                alert(msgObj.info);
            }
        }
    });   
}

function selectCitys(id,name,tab){
    if(id <= 0){
        alert("配送区域不能为空");return false;
    }
    var item_id = $("#gid").val();
    $.ajax({
        url:'{:U("/Home/Products/selectCitys")}',
        cache:false,
        dataType:"HTML",
        data:{cr_id:id,tab:tab,g_id:item_id},
        type:"POST",
        success:function(msgObj) {
            var itab = parseInt(tab) + 1;
            var str = '';
            var itabs = parseInt(itab) + 1;
            var tabs = parseInt(itabs) + 1;
            $("#proCity" + itab).html("请选择");
            $("#proCity" + itabs).html("请选择");
            $("#proCity" + tabs).html("请选择");
            $("#proCity" + tab).html(name);
            $("#cr_ids").val(id);
            $("#con_proCity_" + itab).html(msgObj);

            if (tab == '4') {

                var proCity2 = $("#proCity2").html();
                var proCity3 = $("#proCity3").html();
                var proCity4 = $("#proCity4").html();
                $(".province").css("borderBottom", "1px solid #D7D7D7");
                $(".province").html(proCity2 + proCity3 + proCity4);
                $(".proCity").hide();
            } else {
                if (!isNaN(msgObj)) {
                    var proCity2 = $("#proCity2").html();
                    var proCity3 = $("#proCity3").html();
                    var proCity4 = $("#proCity4").hide();
                    $(".province").css("borderBottom", "1px solid #D7D7D7");
                    $(".province").html(proCity2 + proCity3);
                    $(".proCity").hide();
                } else {
                    $("#proCity" + tab).removeClass("onHover");
                    $("#proCity" + itab).addClass("onHover");
                    $("#con_proCity_" + tab).hide();
                    $("#con_proCity_" + itab).show();
                }
                //$("#proCity"+tab).removeClass("onHover");
                //$("#proCity"+itab).addClass("onHover");
                //$("#con_proCity_"+tab).hide();
                //$("#con_proCity_"+itab).show();
            }
            var is_skuNames = $("#detail_skuNames").val();
            if (is_skuNames == 1) {
                var reg = /\{/;
                if (reg.test(msgObj)) {
                    eval("var json =" + msgObj);
                    arr = json;
                    //console.log(json);
                }
                if ($('.yanse dd a').length == 1 && $('.yanse dd a').hasClass('on')) {
                    if (parseInt(msgObj) <= 0) {
                        $("#item_num").val(0);
                        $("#pdt_stock").val(0);
                        $("#pdt_id").val('');
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                        arr = {};
                        return false;
                    }
                    var _this = $('.yanse dd a');
                    var item_id = "{$ary_request.gid}";
                    var _parent_li = _this.parent().parent().find('a.on');
                    rsize = _parent_li.attr('name');
                    if (rsize != "") {
                        var info = rsize;

                        showvalue = arr[info];
                        if (showvalue != undefined) {
                            var vale = showvalue.split("|");
                            if (vale.length > 0) {
                                if (vale[0]) {
                                    $("#pdt_id").val(vale[0]);
                                }
                                if (vale[1] < 30 && vale[1] - MixPdtStock > 0) {
                                    $("#pdt_stock").val(vale[1]);
                                    $("#item_num").val(1);
                                    $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余" + vale[1] + "件，下单后立即发货");
                                } else if (vale[1] > 30) {

                                    $("#pdt_stock").val(vale[1]);
                                    $("#item_num").val(1);
                                    $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
                                } else if (vale[1] - MixPdtStock <= 0) {
                                    $("#pdt_stock").val(0);
                                    $("#item_num").val(0);
                                    $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                                }
                                if (vale[2]) {
                                    //一个规格时有商品一口价商城价显示一口价
                                    var _parent_color_length = jQuery("#sku" + item_id + '_1').find('a.on').length;
                                    var _parent_size_length = jQuery("#sku" + item_id + '_2').find('a.on').length;
                                    if (_parent_color_length == 1 || _parent_size_length == 1) {
                                        $("#showPrice").html(parseFloat("{$detail.gprice}").toFixed(2));
                                    } else {
                                        $("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                                    }
                                    //$("#showPrice").html(parseFloat(vale[2]).toFixed(2));
                                    $("#showMarketPirice").html(parseFloat(vale[3]).toFixed(2));
                                    var savePrice = parseFloat(vale[3]) - parseFloat(vale[2]);
                                    $("#savePrice").html(savePrice);
                                }
                            }
                        }
                    }
                } else {
                    $('.yanse dd a').removeClass("on");
                    $("#pdt_id").val("");
                    $("#pdt_stock").val(0);
                    if (!isNaN(msgObj)) {
                        $("#item_num").val('0');
                        $("#item_num").attr("max", 0);
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                        $(".qg").html("<a href='javascript:void(0);' style='background:#888888'>抢购</a>");
                        arr = {};
                    } else {
                        $("#item_num").val('1');
                        $("#showNum").html("<strong style='font-size:14px;'>有货</strong>");
                        $(".qg").html("<a id='addToCart' onclick='addToCart()' href='javascript:void(0);'>抢购</a>");
                    }
                }
            }else if(is_skuNames == 0){

                if (parseInt(msgObj) <= 0) {
                    $("#item_num").val(parseInt(msgObj));
                }

                $("#pdt_stock").val(msgObj);
                $("#item_num").attr("max", msgObj);
                if (parseInt(msgObj) <= MixPdtStock) {
                    $("#pdt_stock").val(0);
                    $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                    arr = {};
                } else {
                    var pdt_stock_tmp = parseInt(msgObj);
                    if (pdt_stock_tmp < 30 && pdt_stock_tmp - MixPdtStock > 0) {
                        $("#pdt_stock").val(pdt_stock_tmp);
                        $("#item_num").val(1);
                        $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，仅剩余" + pdt_stock_tmp + "件，下单后立即发货");
                    } else if (pdt_stock_tmp > 30) {
                        $("#pdt_stock").val(pdt_stock_tmp);
                        $("#item_num").val(1);
                        $("#showNum").html("<strong style='font-size:14px;'>有货</strong>，下单后立即发货");
                    } else if (pdt_stock_tmp - MixPdtStock <= 0) {
                        $("#pdt_stock").val(0);
                        $("#item_num").val(0);
                        $("#showNum").html("<strong style='font-size:14px;'>无货</strong>，此商品暂时售完");
                        arr = {};
                    }
                }
            }
        }
    });
}

var target = ["xixi-01","xixi-02","xixi-03","xixi-04","xixi-05","xixi-06","xixi-07"];
//导航滑过下拉
function hoverFunc(obj, className) {
    obj.hover(
        function () {
            $(this).addClass(className);
        }, function () {
            $(this).removeClass(className);
        }
    )
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
            $("#con_tabAbp_3").html(msgObj);
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
            $("#con_tabAbp_2").html(msgObj);
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
            $("#con_tabAbp_4").html(msgObj);
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



//商品详细标签切换
//tagChange({
//    tagObj:$('.tagarea li'),
//    tagCon:$('.tagCon .ever'),
//    currentClass:'on'
//
//})
//tagChange({
//    tagObj:$('.evaluation li'),
//    tagCon:$('.CommentAll .Comment'),
//    currentClass:'cur'
//})


// JavaScript Document
// JavaScript Document




