function removePdtCart(pdt_id,type){
    if(confirm('确定要删除该商品吗？')){
        var url = '/Wap/Cart/doDel';
        $.get(url,{pid:pdt_id,type:type},function(dataMsg){
            window.location.reload();
        },'json');
    }
}
$(document).ready(function() {
	var is_auto_cart = $('#is_auto_cart').val();
	if(is_auto_cart == '1'){
		$("input:checkbox[name='pid[]']").attr('checked', 'checked');
		$('#select_all1 , #select_all2').attr('checked', 'checked');
		checkThisGood('o');
	}else{
		checkThisGood('n');
	}
    //全选 取消全选
    $('#select_all2').click(function() {
        if ($(this).attr('checked') == 'checked') {
            $("input:checkbox[name='pid[]']").attr('checked', 'checked');
            $('#select_all1 , #select_all2').attr('checked', 'checked');
            checkThisGood('o');
        } else {
            $("input:checkbox[name='pid[]']").removeAttr('checked');
            $('#select_all1 , #select_all2').removeAttr('checked');
            checkThisGood('n');
        }
    });

    //删除购物车商品
    /*
     $("#delSelected").click(function(){
     if(confirm('')){
     var data = $('#cartForm').serialize();
     ajaxReturn('/Ucenter/Cart/doDel',data);
     }
     });
     */
    //修改商品数量
    $(".inputNum").blur(function() {
        var obj = $(this);
        //货品的id
        var pdt_id = obj.attr('pdt_id');
        //货品的商品类型
        var good_type = obj.attr('good_type');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //获取修改后的购买数量
        var nums = parseInt(obj.attr("value"));
        if(nums < 0 || isNaN(nums)){
            nums = 1;
            obj.attr('value',nums);
        }
        //普通商品
        if (good_type == 0) {
            //获取该货品原来的购买数量
            var old_nums = $("#pdt_num_old_" + pdt_id).val();
            //货品原价
            var pdt_sale_price = $("#pdt_sale_price"+pdt_id).val();
            //采购价
            var smail_price = $("#f_price_" + pdt_id).attr("value");
            //会员购买价
            var cai_price = $("#xiao_price" + pdt_id).attr("value");
            //节省小计
            var per_save = $("#per_save_price_"+pdt_id).val();
            //商品的总价
            var all_pdt_price = $("#all_pdt_price").val();
            //折扣总价
            var all_price_dis = $("#all_price_dis").val();
            //当前购物车商品数量
            var cart_num = $("#current_num").attr('value');
            if (nums > stock) {
                nums = stock;
                obj.val(nums);
            }
            //小计
            var all_smail_price= ((nums) * (smail_price)).toFixed(2);
            if(isNaN(smail_price)) smail_price = 0;
            $("#pdt_sale_price_"+pdt_id).html("￥"+all_smail_price);
            //节省小计
            var pdt_save_price = (parseFloat(per_save) * nums).toFixed(2);
            $("#pdt_save_"+pdt_id).html("￥"+pdt_save_price);
            //默认是1
            if (nums <= 0 || nums > stock) {
                obj.attr("value", 1);
                obj.parent().next().next().html("<i class='price'>￥</i>  " + (1 * cai_price));
                obj.next().val(1);
                return false;
            }
            //修改后的购买量的差
            var dif_nums = parseInt(nums - old_nums);
            var new_cart_num = parseInt(cart_num) + parseInt(dif_nums);
            //修改之后的商品总价
            var modified_nums_price = 0;
            //修改后的商品的总计
            var total_nums_price = 0;
            //修改之后的商品的差价
            var modified_ori_price = pdt_sale_price * (Math.abs(dif_nums));
            //修改后的会员的的差价
            var mem_spread_price = cai_price * (Math.abs(dif_nums));
            //alert(all_pdt_price); alert(modified_ori_price); return false;
            if (dif_nums < 0) {
                modified_nums_price = (all_pdt_price - modified_ori_price).toFixed(2);
                total_nums_price = (all_price_dis - mem_spread_price).toFixed(2);

            } else {
                modified_nums_price = (parseFloat(all_pdt_price) + parseFloat(modified_ori_price)).toFixed(2);
                total_nums_price = (parseFloat(all_price_dis) + parseFloat(mem_spread_price)).toFixed(2);
            }
            
            doEdit(obj, pdt_id, nums, modified_nums_price, mem_pre_price, good_type);
            //把修改后的购买量放入hidden中
            obj.next().val(nums);

            $("#all_pdt_price").val(modified_nums_price);
            //会员的优惠总计
            var mem_pre_price = (modified_nums_price - total_nums_price).toFixed(2);
            checkThisGood('n',pdt_id,nums);
        } else {
            doEdit(obj, pdt_id, nums, 0, 0, good_type);
            checkThisGood('n',pdt_id,nums);
        }
    });
    
    $('.addNums').live('click',function(){
        var obj = $(this);
        var type = obj.attr('type');
        var good_type = obj.attr('good_type');

        //货品的id
        var pdt_id = obj.attr('pdt_id');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //开始的数量
        var pr_num =parseInt($("#pdt_num_"+pdt_id).val());
        //会员的价格
        // var m_price = obj.attr("pdt_sale_price");
        // alert(m_price); return false;
        //点击减的数量
        var jian_num = pr_num;
        //现在在数量
        var pr_num_new = null;

        if (type == 1) {
            pr_num_new = parseInt(jian_num) - 1;
        } else {
            pr_num_new = parseInt(pr_num) + parseInt(1);
        }

        //当前的货品原销售价的总价
        // var all_pdt_price = $("#all_pdt_price").val();
        var all_pdt_price = $("#all_pdt_price").attr('pdt_price');
        //货品原价
        var pdt_sale_price = $("#pdt_sale_price" + pdt_id).val();
        //会员价
        var m_price = 0;
        //采购价
        var smail_price = $("#f_price_" + pdt_id).attr("value");
        //优惠价
        var pre_price = $("#pre_price").val();
        //单价节省
        var per_save = $("#per_save_price_"+pdt_id).val();
        //折扣总价
        var all_price_dis = $("#all_price_dis").val();
        var cart_nums = $("#cart_nums").html();
        if (good_type == 1) {
            if (type == 1) {
                if (pr_num_new > 0) {
                    doEdit(obj, pdt_id, pr_num_new, 0, 0, good_type, type);
                    checkThisGood('n',pdt_id,pr_num_new);
                }
                else {
                    return false;
                }
            }
            else {
                //obj.next().attr("value",pr_num_new);
                doEdit(obj, pdt_id, pr_num_new, 0, 0, good_type, type);
                checkThisGood('n',pdt_id,pr_num_new);
            }
        } else {
            $("#pdt_num_old_" + pdt_id).val(pr_num_new);
            if (type == 1) {
                if (pr_num_new > 0) {
                    $("#pdt_num_"+pdt_id).val(pr_num_new);
                    //商品原总价
                    var all_price = (parseFloat(all_pdt_price) - parseFloat(smail_price)).toFixed(2);
                    
                    $("#all_pdt_price").val(all_price);
                    $("#all_pdt_price").attr("pdt_price",all_price);
                    //小计总价
                    var all_smail_price= ((pr_num_new) * (smail_price)).toFixed(2);
                    $("#pdt_sale_price_"+pdt_id).html("￥"+all_smail_price);
                    //节省小计
                    var pdt_save_price = (parseFloat(per_save) * pr_num_new).toFixed(2);
                    $("#pdt_save_"+pdt_id).html("￥"+pdt_save_price);
                    //货品的折扣总价
                    var discount =  ((parseFloat(all_price_dis) - parseFloat(m_price)).toFixed(2));
                    var all_dis = (all_price - discount).toFixed(2);
                    var  pdt_num =$("#pdt_num_old_"+pdt_id).val();
                    doEdit(obj, pdt_id, pdt_num, all_price, all_dis, good_type);
                    checkThisGood('n',pdt_id,pdt_num);
                } else {
                    return false;
                }
            } else {
                if (pr_num_new <= stock) {
                    $("#pdt_num_"+pdt_id).val(pr_num_new);
                    //新的销售价的金额
                    /* var new_pdt_subtotal = ((pr_num_new) * (pdt_sale_price)).toFixed(2);
                    var new_pdt_sale_price = (pdt_sale_price) * (pr_num_new); */
                    //小计总价
                    var all_smail_price= ((pr_num_new) * (smail_price)).toFixed(2);
                    $("#pdt_sale_price_"+pdt_id).html("￥"+all_smail_price);
                    //节省小计
                    var pdt_save_price = (parseFloat(per_save) * pr_num_new).toFixed(2);
                    $("#pdt_save_"+pdt_id).html("￥"+pdt_save_price);
                    //商品总价
                    var all_price = (parseFloat(all_pdt_price) + parseFloat(smail_price)).toFixed(2);
                    
                    //货品的折扣总价
                    var discount =  ((parseFloat(all_price_dis) + parseFloat(m_price)).toFixed(2));
                    var all_dis = (all_price - discount).toFixed(2);
                    var  pdt_num =$("#pdt_num_old_"+pdt_id).val();
                    doEdit(obj, pdt_id, pdt_num, all_price, all_dis, good_type);
                    checkThisGood('n',pdt_id,pdt_num);
                } else {
					$.ThinkBox.error("已达到库存最大值！");
                    return false;
                }
            }
        }
    })
    /***
     *关闭购物车快满的提示
     *@author zhangjiasuo
     *@date 2013-04-17
     */
    $('.close').click(function() {
        $("#cart_full").css('display', 'none');
    })

    /***
     *开启购物车快满的提示
     *@author zhangjiasuo
     *@date 2013-04-18
     */
    function open(num) {
        if (num >= 40) {
            $("#cart_full").css('display', 'block');
        } else {
            $("#cart_full").css('display', 'none');
        }
    }

    /***
     *按货品编号加入购物车
     *@author zhangjiasuo
     *@date 2013-04-18
     */
    $('#FastSubmit').click(function() {
        $.blockUI();
        setTimeout(function() {
            $.unblockUI();
            var num = $("#fast_num").val();
            var pdt_sn = $("#pdt_sn").val();
            if (pdt_sn == "") {
                showAlert(false, '请输入货品编号');
                return false;
            }
            if (!isNaN(num) && num > 0) {
                $.unblockUI();
                //发送ajax请求
                var data = $('#Fastcart').serialize();
                ajaxReturn('/Wap/Cart/Fastcart', data, 'post');
            } else {
                showAlert(false, '请输入购买数量');
                return false;
            }
        }, 2000);
    });
    /***
     *按货品编号加入购物车
     *@author zhangjiasuo
     *@date 2013-04-18
     */
    $('#HomeFastSubmit').click(function() {
        $.blockUI();
        setTimeout(function() {
            $.unblockUI();
            var num = $("#fast_num").val();
            var pdt_sn = $("#pdt_sn").val();
            if (pdt_sn == "") {
                alert("请输入货品编号");
                return false;
            }
            if (!isNaN(num) && num > 0) {
                $.unblockUI();
                //发送ajax请求
                var data = $('#HomeFastSubmit').serialize();
                ajaxReturn('/Home/Cart/Fastcart', data, 'post');
            } else {
                alert("请输入购买数量");
                return false;
            }
        }, 2000);
    });
    /***
     *购物车状态
     *@author zhangjiasuo
     *@date 2013-04-19
     */
    function loading() {
        //var rowCount = $("#cart_tbody tr").length;
        var rowCount = $("input[name='pid[]']:checked").length;
        open(rowCount);
        if (rowCount > 50) {
            rowCount = 50;
        }
        if (rowCount <= 0) {
            rowCount = 0;
        }
        $("#new_current_num").html(rowCount);
        $("#current_num").attr('value', rowCount);
        var num = $("#current_num").attr('value');

        var w = Math.ceil((rowCount / 50) * 118);
        var innerHtmls = '<div style="min-width:118px; width:auto; min-height:5px; height:auto; border:1px solid silver; border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;">';
        innerHtmls += '<div id="loading" style="height:8px; background-color:#FF9B2E; border-radius: 2px; -moz-border-radius: 2px; -webkit-border-radius: 2px;"></div></div>';
        $('#allerp').html(innerHtmls);
        $("#loading").css("width", w + 'px');
    }

    function doEdit(obj, pdt_id, pdt_nums, all_prices, all_dis, good_type, type) {

        $.post("/Wap/Cart/doEdit", {
            "pdt_id": pdt_id,
            "pdt_nums": pdt_nums,
            "good_type": good_type,
            "all_price": all_prices,
            "all_dis": all_dis
        }, function(data) {
        	if(data.status == false){
        		showAlert(false,data.message);return;
        	}
            //更新消耗积分
            if (good_type == 1) {
                if (data.stauts == true) {
                    if (typeof type != 'undefined' && type)
                        $('#nums_' + pdt_id + '_' + good_type).val(pdt_nums);
                    var single_point = $('#single_point_' + pdt_id + '_' + good_type).html();
                    var point = parseInt(single_point) || 0;//单个商品花费积分
                    var singlepoints = parseInt(point * pdt_nums);
                    $('#totalpoint_' + pdt_id + '_' + good_type).html(singlepoints);
                    var cart_tr = $('#cart_tbody tr');
                    var total_consume_point = 0;
                    if (cart_tr.length > 0) {

                        cart_tr.each(function() {
                            var _this = $(this);
                            var _inputNum = _this.find("input.inputNum"),
                                    good_type = _inputNum.attr('good_type');

                            if (good_type == 1) {
                                var num = _inputNum.val() || 0,
                                        single_pdt_id = _inputNum.attr('pdt_id'),
                                        point = _this.find('#single_point_' + single_pdt_id + '_' + good_type).html() || 0;
                                total_consume_point += parseInt(num * point);
                            }
                        });
                    }
                    //$('#li_i_consume_point').html(total_consume_point);
                    $('#old_nums_' + pdt_id + '_' + good_type).val(pdt_nums);
                }
                else {
                    var old_nums = $('#old_nums_' + pdt_id + '_' + good_type).val() || 0;
                    $('#nums_' + pdt_id + '_' + good_type).val(old_nums);
                    $("#jf_msg").fadeIn('slow');
                }
            } else if (good_type == 0) {
                //订单促销
               // $("#label_pre_price").html("<i class='price'>￥</i>" + (data.pre_price));
                //$("#strong_all_price").html("<i class='price'>￥</i>" + data.price);
                var promotion_result_name = data.promotion_result_name;
                var promotion_names = data.promotion_names;
                if(promotion_result_name && promotion_names){
                    $("#xiao_price"+pdt_id).html("<i class='price'>￥</i>" + (data.promotion_price));
                    $("#proname"+pdt_id).html(data.promotion_result_name+','+promotion_names);
                }else{
                    if(promotion_result_name){
                        $("#proname"+pdt_id).html(promotion_result_name)
                    }else if(promotion_names){
                        $("#proname"+pdt_id).html(promotion_names)
                    }else{
                        $("#proname"+pdt_id).html("无优惠");
                    }
                    
                }
				del_gifts_tr();
				if (data.cart_gifts_data != null) {
                    add_gifts_tr(data.cart_gifts_data);					
				}
            }
        }, 'json');
    }

    /***
     *物车选购数量变化赠品动态添加
     *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     *@date 2013-07-02
     */
/**
    function add_gifts_tr(cart_gifts_data) {
        var tr_html = '';
        $.each(cart_gifts_data, function(idx, items) { //alert(items.pdt_id);
			tr_html += "<tr class='giftscartPdt'>";
            tr_html += "<td width='100'><div class='scListL'>";
			tr_html += "<a href='/Wap/Products/detail?gid="+items.g_id+"'>";
            tr_html += "<img width='100px' height='100px' src=" + items.g_picture + "></a>";
            tr_html += "<input type='hidden' value=" + items.pdt_sale_price + " id=" + 'pdt_price' + items.pdt_id + "></div></td>";
			tr_html += "<td><div class='scListR'>"+items.g_name+"<p class='clearfix'><label>数量:</label>"+items.pdt_nums+'</p>';
			tr_html += "<p>价格:<span class='red' >&yen;"+items.pdt_momery.toFixed(2)+"</span></p>"
			tr_html += "<p>促销:<span class='green'>赠品</span></p>";
			tr_html += "<a href='javascript:void(0);' onclick='addToInterests("+items.g_id+");' class='tag1'></a></div></td></tr>";				
        });
        $("tbody").append(tr_html);
    }
**/
    /***
     *物车选购数量变化赠品动态删除
     *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     *@date 2013-07-02
     */
/**
    function del_gifts_tr() {
        $("[class=giftscartPdt]").each(function() {
            $(this).remove();
        });
    }
**/
    /***
     *快速购物车选购数量变化
     *@author zhangjiasuo
     *@date 2013-04-25
     */
    $('#f_down').click(function() {
        var nums = $("#fast_num").val();
        var new_num = parseInt(nums) - parseInt(1);
        if (new_num <= 0) {
            new_num = 0;
        }
        $("#fast_num").attr("value", new_num);
    });
    $('#f_up').click(function() {
        var nums = 0;
        var nums = $("#fast_num").val();
        var new_num = parseInt(nums) + parseInt(1);
        $("#fast_num").attr("value", new_num);
    });

    /* 
     *快速加入购物车货品编码框失去焦点时进行ajax验证 
     *@author zhangjiasuo
     *@date 2013-05-07
     */
    $('#pdt_sn').live("blur", function() {
        var obj = $(this);
        var pdtsn = $(this).val();
        var url = "/Wap/Products/doCheckPdtsn";
        if (pdtsn != '') {
            $.get(url, {
                'pdtsn': pdtsn
            }, function(info) {
                if (info.result) {
                    $('#FastSubmit').attr('disabled', false);
                } else {
                    showAlert(false, '你输入货品编号不存在');
                    return false;
                }
            }, 'json');
        }
    });
    
    $("input[name='pid[]']").click(function(){
        checkThisGood('n');
    });
    
    
   function checkThisGood(type,pdt_id,pdt_nums){
        var url = "/Wap/Cart/checkCartGoods";
        var pid = '';
        if(type == 'o'){
            pid = 'all'
        }else{
            $("input[name='pid[]']:checked").each(function(){
                pid += this.value+',';
            });
            pid = pid.substring(0,(pid.length-1));
        }
        $(".addNums").unbind("click");
        $("a[pdt_sale_price]").each(function(){
            $(this).removeClass('addNums');
        });
        $.post(url,{pid:pid,"pdt_id": pdt_id,"pdt_nums": pdt_nums},function(dataMsg){
            $("#li_i_reward_point").html(dataMsg.reward_point+'分');
            if(typeof dataMsg.all_price != 'undefined') $("#strong_all_price").html(dataMsg.all_price);
            if(typeof dataMsg.all_pdt_price != 'undefined') $("#all_product_price").html("&yen;"+dataMsg.all_pdt_price);
            if(typeof dataMsg.pre_price != 'undefined') $("#label_pre_price").html("&yen;"+dataMsg.pre_price);
            $("a[pdt_sale_price]").each(function(){
                $(this).addClass('addNums');
            });
        },'json');
    }

});
