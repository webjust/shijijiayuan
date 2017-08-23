function removePdtCart(pdt_id,type){
    if(confirm('确定要删除该商品吗？')){
        var url = '/Wap/Cart/doDel';
        $.get(url,{pid:pdt_id,type:type},function(dataMsg){
            window.location.reload();
        },'json');
    }
}
$(function(){
$('.addCartNums').click(function(){
        var obj= $(this);
        var type = obj.attr('type');
        var good_type = obj.attr('good_type');
		
        //货品的id
        var pdt_id = obj.attr('pdt_id');
        
        //货品的库存
        var stock = parseInt(obj.attr('stock'));
        //开始的数量
        var  pr_num =parseInt($("#pdt_num_"+pdt_id).val());
        //点击减的数量
        var  jian_num = pr_num;
        //现在在数量
        var pr_num_new = null;
        if(type == 1){  //减1
            pr_num_new = parseInt(jian_num)-1;
            if(good_type != 1) $("#pdt_num_"+pdt_id).val(pr_num_new);
        }else{    //加1
            pr_num_new = parseInt(pr_num) + parseInt(1);
            if(good_type != 1) $("#pdt_num_"+pdt_id).val(pr_num_new);
        }
        //当前的货品原销售价的总价
       // var all_pdt_price = $("#all_pdt_price").val();
        var all_pdt_price = $("#all_pdt_price").attr('pdt_price');
        //货品原价
        var pdt_sale_price = $("#pdt_sale_price"+pdt_id).val();
        //会员价
        var m_price = 0;
        //采购价
        var smail_price = $("#f_price_"+pdt_id).attr("value");
        //优惠价
        var pre_price = $("#pre_price").val();
        
        //单价节省
        var per_save = $("#per_save_price_"+pdt_id).val();
        //折扣总价
        var all_price_dis =$("#all_price_dis").val();
        var cart_nums = $("#cart_nums").html();
        if(good_type == 1){
           if(type == 1){ //jian
               if(pr_num_new > 0){
                    doEdit(obj,pdt_id,pr_num_new,0,0,good_type,type);
                }else{
                    return false;
                } 
           } else{
                doEdit(obj,pdt_id,pr_num_new,0,0,good_type,type);
           }
        }else{
            
          if(type == 1){  //减1
            if(pr_num_new > 0){
//                $("#cart_nums").html(cart_nums-1);
                $("#pdt_num_"+pdt_id).val(pr_num_new);
                //商品原总价
                var all_price = (parseFloat(all_pdt_price) - parseFloat(smail_price)).toFixed(2);
                
                $("#all_pdt_price").val(all_price);
                $("#all_pdt_price").attr("pdt_price",all_price);
                //小计总价
                var all_smail_price= ((pr_num_new) * (smail_price)).toFixed(2);
                var pdt_save_price = (parseFloat(per_save) * pr_num_new).toFixed(2);
                //当前货品的小计
                $("#pdt_sale_price_"+pdt_id).html("￥"+all_smail_price);
                $("#pdt_save_"+pdt_id).html("￥"+pdt_save_price);
                //货品的折扣总价
                var discount =  ((parseFloat(all_price_dis) - parseFloat(m_price)).toFixed(2));
               // alert(discount);return false;
                $("#all_product_price").html("￥"+all_price);
                $("#strong_all_price").html((parseFloat(all_price) - parseFloat(discount)).toFixed(2));
                $("#all_price_dis").val(discount);
                //优惠的总价
                var all_dis = (all_price - discount).toFixed(2);
                $("#label_pre_price").html("￥"+discount);
                var  pdt_num =$("#pdt_num_"+pdt_id).val();
                doEdit(obj,pdt_id,pdt_num,all_price,all_dis,good_type);
              }else{
                return false;
              }  
          }else{  // 加1
            if(pr_num_new <= stock){
                //$("#cart_nums").html(parseInt(cart_nums)+1);
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
                
                $("#all_pdt_price").val(all_price);
                $("#all_pdt_price").attr("pdt_price",all_price);
                //当前货品的小计
//                obj.parent().parent().prev().children("span").html("￥"+all_smail_price);
                //货品的折扣总价
                var discount =  ((parseFloat(all_price_dis) + parseFloat(m_price)).toFixed(2));
                $("#all_product_price").html("￥"+all_price);
                $("#strong_all_price").html(all_price);
                $("#all_price_dis").val(discount);
                //优惠的总价
                var all_dis = (all_price - discount).toFixed(2);
                $("#label_pre_price").html("￥"+discount);
                var  pdt_num =$("#pdt_num_"+pdt_id).val();
                doEdit(obj,pdt_id,pdt_num,all_price,all_dis,good_type);
            }else{
                return false;
            }
          }
        }
    });
});
    function doEdit(obj,pdt_id,pdt_nums,all_prices,all_dis,good_type,type){
        
        $.post("/Wap/Cart/doEdit",{
            "pdt_id":pdt_id,
            "pdt_nums":pdt_nums,
            "good_type":good_type,
            "all_price":all_prices,
            "all_dis":all_dis
        },
        function(data){
            //更新消耗积分
            if(good_type == 1){
                if(data.stauts == true){
                    $('#li_i_consume_point').html(total_consume_point);
                    $('#old_nums_'+pdt_id+'_'+good_type).val(pdt_nums);
                }
                else{
                    var old_nums =  $('#old_nums_'+pdt_id+'_'+good_type).val() || 0;
                    $('#nums_'+pdt_id+'_'+good_type).val(old_nums);
                    $("#jf_msg").fadeIn('slow');
               }
            }
            else if(good_type == 0){
           
                var all_price = $('#top_all_price').html() || 0,
                consumed_ratio = $('#consumed_ratio').val() || 0;
					
                if(data.promotion_result_name != null){
                    $('#promition_rule_name').html(data.promotion_result_name);
                    $('#promition_rule_name').fadeIn('slow');
                }else{
                    $('#promition_rule_name').fadeOut('slow');
                    $('#promition_rule_name').html('');
                }
                //订单促销
                $("#label_pre_price").html("￥"+(parseFloat(data.pre_price).toFixed(2)));
                $("#strong_all_price").html((data.price).toFixed(2));
            }
		   
		 
        },'json');
    }