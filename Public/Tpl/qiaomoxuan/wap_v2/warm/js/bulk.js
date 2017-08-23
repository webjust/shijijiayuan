
//立即购买
function addToOrder(i){
    var res = allSpecSelectedCheck('on');
    if(res[0] == false) {
        $.ThinkBox.error('请选择要购买的商品规格！');return false;
    }
    var pdt_id = $('#pdt_id').val();
    var gp_id  = $('#gp_id').val();

    var no_login = $("#no_login").val();
    var num = parseInt($('#item_num').val());
    var max = parseInt($('#item_num').attr('max'));
    //alert(pdt_id);die;
    if(pdt_id == ''){
        $.ThinkBox.error(nonExistent);return false;
    }
    if (isNaN(num)){
        $.ThinkBox.error(nullNum);return false;
    }
    if (num < 1){
        $.ThinkBox.error("购买数量不能小于1");return false;
    }
    if(num > max){
        $.ThinkBox.error("您最多还能团购"+max+"件");return false;
    }
    if(m_id == ''){
        location.href="/Wap/User/login?jumpUrl=/Wap/Bulk/detail?sp_id="+gp_id;return false;
    }
    var data = new Object();
    data['cart'] = {};
    data['cart']['pdt_id'] = pdt_id;
    data['cart']['gp_id'] = gp_id;
    data['cart']['num'] = num;
    data['type'] = 'bulk';
    if(i==2){
        data['cart']['is_deposit'] = 1;
    }
    $.post('/Wap/Cart/doAdd',data,function(dataMsg){
        if(dataMsg.status == 1){
            location.href = dataMsg.url;
        }else{
            $.ThinkBox.error(dataMsg.msg);
        }
    },'json');
}
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val());
    var max = _this.attr('max');
    if(max ==''){
        return false;
    }
    max = parseInt(max);
    num=num+i;
    if((num<=0)||(num>max)||(num>999) || max==0 || max ==null){return false;}
    _this.val(num);
    return false;
}

function allSpecSelectedCheck(on_class) {
    var _gsd_arr = [];
    var _select_all = true;
    $('.spec_list').each(function(){
        var _selected_spec = $(this).find('a.'+on_class);
        if(_selected_spec.length) {
            var gsd_id = _selected_spec.attr('data-value');
            _gsd_arr.push(gsd_id);
        }else{
            _select_all = false;
            return false
        }
    });

    return [_select_all, _gsd_arr];
}