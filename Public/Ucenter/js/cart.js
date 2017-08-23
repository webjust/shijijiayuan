$(document).ready(function() {
    var IS_ON_MULTIPLE = $("#is_on_mulitiple").val();

	var is_auto_cart = $('#is_auto_cart').val();
	if(is_auto_cart == '1'){
		$("input:checkbox[name='pid[]']").attr('checked', 'checked');
		$('#select_all1 , #select_all2').attr('checked', 'checked');
		checkThisGood('o');
	}else{
		checkThisGood('n');
	}
    //全选 取消全选
    $('#select_all1 , #select_all2').click(function() {
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
        //数量修改最小单位
        var min_per_num = obj.data("min");

        if( IS_ON_MULTIPLE == 1 &&  min_per_num > 0 && nums % min_per_num != 0 ) {
            $.ThinkBox.error('请填写'+ min_per_num +"的倍数");
            obj.val(obj.data('current'));
            return false;
        }
		if(nums > stock){
			nums = stock;
            obj.attr('value',nums);
		}
        if(nums < 0 || isNaN(nums)){
            nums = 1;
            obj.attr('value',nums);
        }
        //普通商品
        if (good_type == 0) {
            //获取该货品原来的购买数量
            var old_nums = obj.next().val();
            //货品原价
            var pdt_sale_price = $("#pdt_price" + pdt_id).val();
            //会员购买价
            var cai_price = $("#xiao_price" + pdt_id).attr("value");
            //商品的总价
            var all_pdt_price = $("#all_pdt_price").val();
            //折扣总价
            var all_price_dis = $("#all_price_dis").val();
            //alert(all_price_dis);return;
            //当前购物车商品数量
            var cart_num = $("#current_num").attr('value');
            if (nums > stock) {
                nums = stock;
                obj.val(nums);
            }
            /* if(nums >= 50){
             nums =50 - parseInt(cart_num);
             obj.attr("value",nums);
             } */
            //小计
            var smail_price = (parseInt(nums) * cai_price).toFixed(2);
            if(isNaN(smail_price)) smail_price = 0;
			if ( $("#tax_rate" + pdt_id).length > 0 ) {
				var tax_rate = $("#tax_rate" + pdt_id).attr("value");
				var tax_rate_price = ((tax_rate) * (smail_price)).toFixed(2);
				obj.parent().parent().next().next().html("<i class='price'>￥</i>" + tax_rate_price);
				obj.parent().parent().next().next().next().html("<i class='price'>￥</i>" + smail_price);
			}else{
				obj.parent().parent().next().next().html("<i class='price'>￥</i>  " + smail_price);
			}
            
            //默认是1
            if (nums <= 0 || nums > stock) {
                obj.attr("value", 1);
				if ( $("#tax_rate" + pdt_id).length > 0 ) {
					var tax_rate = $("#tax_rate" + pdt_id).attr("value");
					var tax_rate_price = ((tax_rate) * (1 * cai_price)).toFixed(2);
					obj.parent().parent().next().next().html("<i class='price'>￥</i>" + tax_rate_price);
					obj.parent().parent().next().next().next().html("<i class='price'>￥</i>" + (1 * cai_price).toFixed(2));
				}else{
					obj.parent().parent().next().next().html("<i class='price'>￥</i>  " + (1 * cai_price).toFixed(2));
				}
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
			//把修改后的购买量放入hidden中
            obj.next().val(nums);
            //把修改后的商品总价显示在页面中 商品的总价
            // alert(modified_nums_price); alert(total_nums_price); return false;
            //$("#pdt_price").html("<i class='price'>￥</i>  " + modified_nums_price);

            //$("#all_pdt_price").val(modified_nums_price);
            doEdit(obj, pdt_id, nums, modified_nums_price, mem_pre_price, good_type);
            
            //会员总计
           // $("#strong_all_price").html("<i class='price'>￥</i> " + total_nums_price);
           // $("#all_price_dis").val(total_nums_price);
            //$("#all_pdt_price").attr("pdt_price", modified_nums_price);
           // $("#top_all_price").html(total_nums_price);
            //会员的优惠总计
            var mem_pre_price = (modified_nums_price - total_nums_price).toFixed(2);
            //$("#label_pre_price").html("<i class='price'>￥</i>" + mem_pre_price);
            //checkThisGood('n');
        }
        else {
            doEdit(obj, pdt_id, nums, 0, 0, good_type);
            //checkThisGood('n');
        }
    });

    $('.addNums').live('click',function(){
        var obj = $(this);
        var type = obj.attr('type');
        var min = obj.attr('min_num');
        var good_type = obj.parent().find("input:first").attr('good_type');
        var min_per_num =obj.data("min");

        //货品的id
        var pdt_id = obj.attr('pdt_id');
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //开始的数量 加
        var pr_num = obj.prev().prev().attr('value');
        //会员的价格
        // var m_price = obj.attr("pdt_sale_price");
        // alert(m_price); return false;
        //点击减的数量 减
        var jian_num = obj.next().attr('value');

        //现在在数量
        var pr_num_new = null;

        if (type == 1) {//减
            if(min_per_num > 0 && IS_ON_MULTIPLE == 1) {
                if(jian_num < min_per_num || jian_num % min_per_num != 0 ){
                    pr_num_new = min_per_num;
                }else{
                    pr_num_new = parseInt(jian_num) - 1 * min_per_num;
                }
            }else{
                pr_num_new = parseInt(jian_num) - 1;
            }
            if (good_type != 1)
                obj.next().next().val(pr_num_new);
        } else {//加
            if(min_per_num > 0 && IS_ON_MULTIPLE == 1) {
                if(pr_num < min_per_num || pr_num % min_per_num != 0){
                    pr_num_new = min_per_num;
                }else{
                    pr_num_new = parseInt(pr_num) + parseInt(1) * min_per_num;
                }
            }else{
                pr_num_new = parseInt(pr_num) + parseInt(1);
            }
			if(pr_num_new > stock){
				return false;
			}
            if (good_type != 1)
                obj.prev().val(pr_num_new);
        }
        min = parseInt(min);
        if(pr_num_new<min && min!=0){
            $.ThinkBox.error("不能小于"+min+"件！");
            return false;
        }

        //当前的货品原销售价的总价
        // var all_pdt_price = $("#all_pdt_price").val();
        var all_pdt_price = $("#all_pdt_price").attr('pdt_price');
        //货品原价
        var pdt_sale_price = $("#pdt_price" + pdt_id).val();
        //会员价
        var m_price = obj.parent().parent().prev().attr("value");
        //采购价
        var smail_price = $("#xiao_price" + pdt_id).attr("value");
        //优惠价
        var pre_price = $("#pre_price").val();
        //折扣总价
        var all_price_dis = $("#all_price_dis").val();
        if (good_type == 1) {
            if (type == 1) {
                if (pr_num_new > 0) {
                    //obj.next().attr("value",pr_num_new);
                    //  var  pdt_num =obj.next().attr('value');
                    doEdit(obj, pdt_id, pr_num_new, 0, 0, good_type, type);
                    //checkThisGood('n');
                }
                else {
                    return false;
                }
            }
            else {
                //obj.next().attr("value",pr_num_new);
                doEdit(obj, pdt_id, pr_num_new, 0, 0, good_type, type);
                //checkThisGood('n');
            }
        }
        else {
            if (type == 1) {
                if (pr_num_new > 0) {
                    obj.next().attr("value", pr_num_new);
                    /* //货品没有打折的价格
                     var all_pdt_sale_price = ((pr_num_new) * (pdt_sale_price)).toFixed(2);
                     //新的销售价的金额
                     var new_pdt_sale_price = (pdt_sale_price) * (pr_num_new);>*/
                    //商品总价
                    var all_price = (parseFloat(all_pdt_price) - parseFloat(pdt_sale_price)).toFixed(2);

                    //$("#pdt_price").html("<i class='price'>￥</i>  " + all_price);
                    $("#all_pdt_price").val(all_price);
                    $("#all_pdt_price").attr("pdt_price", all_price);
                    $("#top_all_price").html(all_price);
                    //小计总价
                    var all_smail_price = ((pr_num_new) * (smail_price)).toFixed(2);
                    //当前货品的小计
                    
					if ( $("#tax_rate" + pdt_id).length > 0 ) {
						var tax_rate = $("#tax_rate" + pdt_id).attr("value");
						var tax_rate_price = ((tax_rate) * (all_smail_price)).toFixed(2);
						obj.parent().parent().next().next().html("<i class='price'>￥</i>" + tax_rate_price);
						//obj.parent().parent().next().next().next().html("<i class='price'>￥</i>  " + all_smail_price);
					}else{
						obj.parent().parent().next().next().html("<i class='price'>￥</i>  " + all_smail_price);
					}
                    //货品的折扣总价
                    var discount = ((parseFloat(all_price_dis) - parseFloat(m_price)).toFixed(2));
                    //$("#strong_all_price").html("<i class='price'>￥</i> " + discount);
                    $("#all_price_dis").val(discount);
                    //优惠的总价
                    var all_dis = (all_price - discount).toFixed(2);
                    //$("#label_pre_price").html("<i class='price'>￥</i>" + all_dis);
                    var pdt_num = obj.next().attr('value');
                    doEdit(obj, pdt_id, pdt_num, all_price, all_dis, good_type);
                    //checkThisGood('n');
                } else {
                    return false;
                }
            } else {
                if (pr_num_new <= stock) {
                    //赋值数量
                    obj.prev().prev().attr("value", pr_num_new);
                    /*//货品没有打折的价格
                     var all_pdt_sale_price = ((pr_num_new) * (pdt_sale_price)).toFixed(2);
                     //新的销售价的金额
                     var new_pdt_sale_price = (pdt_sale_price) * (pr_num_new);*/
                    //小计总价
                    var all_smail_price = ((pr_num_new) * (smail_price)).toFixed(2);
//                alert(pdt_id);return false;
                    //商品总价
                    var all_price = (parseFloat(all_pdt_price) + parseFloat(pdt_sale_price)).toFixed(2);
                    //$("#pdt_price").html("<i class='price'>￥</i>  " + all_price);
                    $("#all_pdt_price").val(all_price);
                    $("#all_pdt_price").attr("pdt_price", all_price);
                    $("#top_all_price").html(all_price);
                    //当前货品的小计
                    
					if ( $("#tax_rate" + pdt_id).length > 0 ) {
						var tax_rate = $("#tax_rate" + pdt_id).attr("value");
						var tax_rate_price = ((tax_rate) * (all_smail_price)).toFixed(2);
						obj.parent().parent().next().next().html("<i class='price'>￥</i>" + tax_rate_price);
						//obj.parent().parent().next().next().next().html("<i class='price'>￥</i> " + all_smail_price);
					}else{
						obj.parent().parent().next().next().html("<i class='price'>￥</i> " + all_smail_price);
					}
                    //货品的折扣总价
                    var discount = ((parseFloat(all_price_dis) + parseFloat(m_price)).toFixed(2));
                    //$("#strong_all_price").html("<i class='price'>￥</i> " + discount);
                    $("#all_price_dis").val(discount);
                    //优惠的总价
                    var all_dis = (all_price - discount).toFixed(2);
                   // $("#label_pre_price").html("<i class='price'>￥</i>" + all_dis);
                    var pdt_num = obj.prev().prev().attr('value');
                    doEdit(obj, pdt_id, pdt_num, all_price, all_dis, good_type);
                    //checkThisGood('n');
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
                ajaxReturn('/Ucenter/Cart/Fastcart', data, 'post');
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

        $.post("/Home/Cart/doEdit", {
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
				checkThisGood('n');
            }

            else if (good_type == 0) {
                //订单促销
               // $("#label_pre_price").html("<i class='price'>￥</i>" + (data.pre_price));
                //$("#strong_all_price").html("<i class='price'>￥</i>" + data.price);
              //  obj.parent().parent().next().next().next().html("<i class='price'>￥</i>  " + data.promotion_price);
                var promotion_result_name = data.promotion_result_name;
                var promotion_names = data.promotion_names;
                if(promotion_result_name && promotion_names){
                    $("#xiao_price"+pdt_id).html("<i class='price'>￥</i>" + (data.promotion_price));
                    $("#proname"+pdt_id).html('<p style="width: 65px; background: #FA890F; margin: 0px auto; color: white; font-style: normal;"><a href="javascript:void(0);" style="color:#fff;white-space:nowrap;overflow:hidden;" title="'+data.promotion_result_name+'">'+data.promotion_result_name+'</a></p><p style="width: 65px; background: #FA890F; margin: 0px auto; color: white; font-style: normal;margin-top:2px;"><a href="javascript:void(0);" style="color:#fff;white-space:nowrap;overflow:hidden;" title="'+data.promotion_names+'">'+data.promotion_names+'</a></p>');
                }else{
                    if(promotion_result_name){
                        $("#proname"+pdt_id).html('<p style="width: 65px; background: #FA890F; margin: 0px auto; color: white; font-style: normal;"><a href="javascript:void(0);" style="color:#fff;white-space:nowrap;overflow:hidden;" title="'+data.promotion_result_name+'">'+data.promotion_result_name+'</a></p>');
                    }else if(promotion_names){
                        $("#proname"+pdt_id).html('<p style="width: 65px; background: #FA890F; margin: 0px auto; color: white; font-style: normal;"><a href="javascript:void(0);" style="color:#fff;white-space:nowrap;overflow:hidden;" title="'+data.promotion_names+'">'+data.promotion_names+'</a></p>');
                    }else{
                        $("#proname"+pdt_id).html("无优惠");
                    }

                }
				//税额计算
				if ( $("#tax_rate" + pdt_id).length > 0 ) {
					var g_tax_rate= parseFloat(data.tax_price).toFixed(2);
					$("#tax_rate"+pdt_id).html("<i class='price'>￥</i>" + (g_tax_rate));
				}
               // var count_price = (parseFloat(data.price) + parseFloat(data.pre_price)).toFixed(2);
                //$("#pdt_price").html("<i class='price'>￥</i>  " + count_price);
                //var all_price = $('#top_all_price').html() || 0,
                        //consumed_ratio = $('#consumed_ratio').val() || 0;

                //$('#li_i_reward_point').html(Math.round(all_price * consumed_ratio));
                if (data.promotion_result_name != null) {
                    $('#promition_rule_name').html(data.promotion_result_name);
                    $('#promition_rule_name').fadeIn('slow');
                } else {
                    $('#promition_rule_name').fadeOut('slow');
                    $('#promition_rule_name').html('');
                }
				del_gifts_tr();
				if (data.cart_gifts_data != null) {
                    add_gifts_tr(data.cart_gifts_data);					
				}
				checkThisGood('n');
            }
        }, 'json');
    }

    /***
     *物车选购数量变化赠品动态添加
     *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     *@date 2013-07-02
     */

    function add_gifts_tr(cart_gifts_data) {
        var tr_html = '';
        $.each(cart_gifts_data, function(idx, items) { //alert(items.pdt_id);
            tr_html += "<tr class='giftscartPdt'>";
            tr_html += "<td width='40'><input type='hidden' name='type[]' value=" + items.type + "> </td>";
            tr_html += "<td width='82' valign='top'><div class='cartProPic'><a href='#'>";
            tr_html += "<img width='68' height='68' src=" + items.g_picture + ">";
            tr_html += "<input type='hidden' value=" + items.pdt_sale_price + " id=" + 'pdt_price' + items.pdt_id + "></a></div></td>";
            tr_html += "<td width='332' align='left'><div class='cartProName'><a href='javascript:void(1)'>" + items.g_name + "</a>";
            tr_html += "<span>商品编码：" + items.pdt_sn + "</span>";
            if (items.pdt_spec == '') {
                tr_html += "<span>规格：</span></div></td>";
            } else {
                tr_html += "<span>规格：" + items.pdt_spec + "</span></div></td>";
            }
            tr_html += "<td width='81' value=" + items.f_price + "><i class='price'>￥</i>" + items.pdt_sale_price + "<br></td>";
            tr_html += "<td width='122'><p><span class='brownblock marTop5'>库存" + items.pdt_stock + "</span></p></td>";
            tr_html += "<td width='108'>赠品</td>";
            tr_html += "<td width='97' id=" + 'xiao_price' + items.pdt_id + " value=" + items.pdt_sale_price + " >";
            tr_html += "<i class='price'>￥</i>" + items.pdt_sale_price + "</td><td></td>";
            tr_html += "</tr>";
        });
        $("tbody").append(tr_html);
    }

    /***
     *物车选购数量变化赠品动态删除
     *@author zhangjiasuo<zhangjiasuo@guanyisoft.com>
     *@date 2013-07-02
     */

    function del_gifts_tr() {
        $("[class=giftscartPdt]").each(function() {
            $(this).remove();
        });
    }

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
        var url = "/Ucenter/Products/doCheckPdtsn";
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


    function checkThisGood(type){
        var url = "/Ucenter/Cart/checkCartGoods/";
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
        $.post(url,{pid:pid},function(dataMsg){
            $("#li_i_reward_point").html(dataMsg.reward_point);
            $("#li_i_consume_point").html(dataMsg.consume_point);
            if(typeof dataMsg.all_price != 'undefined') $("#strong_all_price").html("<i class='price'>&yen;</i>"+dataMsg.all_price);
            if(typeof dataMsg.all_pdt_price != 'undefined') $("#pdt_price").html("<i class='price'>&yen;</i>"+dataMsg.all_pdt_price);
            if(typeof dataMsg.pre_price != 'undefined') $("#label_pre_price").html("<i class='price'>&yen;</i>"+dataMsg.pre_price);
            $("a[pdt_sale_price]").each(function(){
                $(this).addClass('addNums');
            });
            loading();
        },'json');

       // alert(type);
    }

});
