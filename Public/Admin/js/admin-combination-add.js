function add(obj){
    var i=0;
    $('.product').each(function(){
        i++;
    });
    if(i != 2){
        var clone = $(obj).parent().parent().clone();
        $('.product_info').append(clone);
    }
}
function deleteTr(obj){
    var i=0;
    $('.product').each(function(){
        i++;
    });
    if(confirm('确定删除？') && i!=1){
        $(obj).parent().parent().remove();
    }
    
}
function createSpecifications(){
    $('#spec_goods').html('');
    $('#search_products').html('');
    var html_title = "<td width='300px;' style='border:1px solid gray;'>商品编号</td></tr>";
    var html_content = "<td width='300px;' style='border:1px solid gray' > <input class='medium' type='text' /> <button class='btnA addSpec' type='submit'>新增</button> ";
    html_content += " <button class='btnA removeSpec' type='submit'>删除</button></td></tr>";
    var htc = "<tr style='border:1px solid gray;text-align:center;'>";
    var htv = "<tr style='border:1px solid gray;text-align:center;' class='combinationList'>";
    var j = 0;
    $('.product').each(function(i){
        
        htc += '<td width="150px;" style="border:1px solid gray;" class="st_n">'+$(this).children().children().val()+'</td>';
        var array_tv = $(this).children().next().children().val().split('\n');
        if($(this).children().children().val() == '' && array_tv ==''){
           j++;
        }
        var tc = '<option selected="selected">请选择'+$(this).children().children().val()+'属性</option>';
        for (x in array_tv){
            if(array_tv[x] != ''){
                tc+= '<option value="'+$(this).children().children().val()+':'+array_tv[x]+'">'+array_tv[x]+'</option>';
            }
             
        }
        htv += '<td width="100px;" style="border:1px solid gray;" class="st_k">';
        htv += '<select style="width: auto" class="small">'+tc;
        htv += '</select></td>';
    });
    if(j>0){
        showAlert(false,'属性名称或属性值不能为空');return false;
    }
    htc += html_title;
    htv += html_content;
    $("#spec_goods").append(htc);
    $("#search_products").append(htv);
    $("#search_spec_value").show();
}


$('.addSpec').live('click',function(){
    var clone = $(this).parent().parent().clone();
    $("#search_products").append(clone);
});
$('.removeSpec').live('click',function(){
    $(this).parent().parent().remove();
});

function submitFrom(){
    $('.gname_error').html('');
    var g_name = $("#g_name").val();
    if(g_name == ''){
        $('.gname_error').html('规格组合商品标题不能为空');return false;
    }
    var data = new Object();
    data['com_name'] = g_name;
    data['scg_status'] = $("input[name='scg_status']:checked").val();
    data['list'] = {};
    data['spec_v'] = {};
    data['spec_g'] = {};
    $(".combinationList").each(function(i){
        data['list'][i] = {};
        
        var spc = '';
        $(this).find('.st_k').each(function(){
            spc += $(this).children().val()+',';
            
        });
        spc = spc.substring(0,(spc.length-1));
        data['list'][i]['spec_val'] = spc;
        data['spec_v'][i] = spc;
        data['spec_g'][i] = $(this).children("td:last").children().val();
        data['list'][i]['g_sn'] = $(this).children("td:last").children().val();
        
    });
    $.ajax({
            url:"/Admin/Goods/addCombinationPropertyGoods",
            type:'POST',
            dateType:'json',
            data:data,
            success:function(dataMsg){
                if(dataMsg['status'] == 'success'){
                    showAlert(true,dataMsg['msg'],'成功',{'确认':'/Admin/Goods/combinationPropertyGoodsList'});
                }else{
                    showAlert(false,dataMsg['msg']);
                }
                
            }
    });
}

function checkGname(obj){
    $(obj).val(rtrim(ltrim($(obj).val())));
}
//去左空格;
function ltrim(s){
    return s.replace( /^\s*/, "");
}
//去右空格;
function rtrim(s){
    return s.replace( /\s*$/, "");
}

//编辑
function submitEditFrom(){
    $('.gname_error').html('');
    var g_name = $("#g_name").val();
    if(g_name == ''){
        $('.gname_error').html('规格组合商品标题不能为空');return false;
    }
    var data = new Object();
    data['com_name'] = g_name;
    data['scg_id'] = $("#scg_id").val();
    data['scg_status'] = $("input[name='scg_status']:checked").val();
    data['list'] = {};
    data['spec_v'] = {};
    data['spec_g'] = {};
    $(".combinationList").each(function(i){
        data['list'][i] = {};
        
        var spc = '';
        $(this).find('.st_k').each(function(){
            spc += $(this).children().val()+',';
            
        });
        spc = spc.substring(0,(spc.length-1));
        data['list'][i]['spec_val'] = spc;
        data['spec_v'][i] = spc;
        data['spec_g'][i] = $(this).children("td:last").children().val();
        data['list'][i]['g_sn'] = $(this).children("td:last").children().val();
    });
    $.ajax({
            url:"/Admin/Goods/editCombinationPropertyGoods",
            type:'POST',
            dateType:'json',
            data:data,
            success:function(dataMsg){
                if(dataMsg['status'] == 'success'){
                    showAlert(true,dataMsg['msg'],'成功',{'确认':'/Admin/Goods/combinationPropertyGoodsList'});
                }else{
                    showAlert(false,dataMsg['msg']);
                }
                
            }
    });
}