

//点击规格属性时触发，变更规格选中项，
//并调用getPdtBySpecSelect方法重新获取货品信息
function specSelect(dom, on_class) {
    if(!on_class) on_class = 'on';
    var _class_on = $(dom).hasClass(on_class);
    if(_class_on == true) {
        $(dom).removeClass(on_class);
    }else{
        $(dom).parent().siblings().children('a').removeClass("on");
        $(dom).addClass(on_class);
        $(dom).parent().siblings().children('a.'+on_class).each(function(){
            $(this).removeClass(on_class);
        });
    }
    //var _this = jQuery(dom);
    //$("#main_pic").length && $("#main_pic").attr("src",_this.find("img:first").attr("src"));
    //$("#main_pic").length && $("#main_pic").attr("jqimg",_this.find("img:first").attr("src"));

    getPdtBySpecSelect(on_class);
}

//根据当前规格的选中状态获取对应的货品信息
function getPdtBySpecSelect(on_class) {
    if(!on_class) on_class = 'on';
    //检测是否所有的销售属性都有选中
    var _select_result = allSpecSelectedCheck(on_class);
    var _select_all = _select_result[0];
    var _gsd_arr = _select_result[1];
    //console.log(_gsd_arr);
    if(_select_all) {
        if(_gsd_arr.length > 0) {
            var _gsd_joint_with_underline = _gsd_arr.join('_');
            var _pdt = json_goods_pdts[_gsd_joint_with_underline];
            //if(IS_ON_MULTIPLE == 1){
            //    $("#item_num").data("min",_pdt.pdt_min_num);
            //    $("#item_num").data("current",_pdt.pdt_min_num);
            //    $("#item_num").val(_pdt.pdt_min_num);
            //}
            if (_pdt && _pdt.g_id) {
                var _pdt_stock = _pdt.pdt_stock;
                var _pdt_stock_show = _pdt.pdt_stock;
                if (fuzzy_stock_open > 0 && fuzzy_stock_level > 0) {
                    if (_pdt_stock <= 0) {
                        _pdt_stock_show = '缺货';
                    } else if (_pdt_stock <= warning_stock_num) {
                        _pdt_stock_show = '供货紧张';
                    } else {
                        _pdt_stock_show = '充足';
                    }
                }
				if (_pdt_stock == 0) {
					$("#item_num").val(0);
				} else if (_pdt_stock < warning_stock_num) {
					if(_pdt.pdt_min_num>1){
						$("#item_num").val(_pdt.pdt_min_num);
						$("#item_num").data("min",_pdt.pdt_min_num);
						$("#item_num").data("current",_pdt.pdt_min_num);
					}else{
						$("#item_num").val(1);
					}
				} else {
					if(_pdt.pdt_min_num>1){
						$("#item_num").val(_pdt.pdt_min_num);
						$("#item_num").data("min",_pdt.pdt_min_num);
						$("#item_num").data("current",_pdt.pdt_min_num);	
					}else{
						$("#item_num").val(1);
					}
				}				
                var _pdt_id = _pdt.pdt_id;
                var _pdt_sale_price = parseFloat(_pdt.pdt_sale_price).toFixed(2);
                var _pdt_market_price = parseFloat(_pdt.pdt_market_price).toFixed(2);
                var _discount_amount = parseFloat(_pdt_market_price - _pdt_sale_price).toFixed(2);
				var sp_price = $("#sp_price").val();
				if(sp_price){
					var _discount = (sp_price / _pdt_market_price * 10).toFixed(2);
				}else{
					var _discount = (_pdt_sale_price / _pdt_market_price * 10).toFixed(2);
				}
                //最大可购买数，默认等于库存数
                var _max_buy_num = _pdt_stock;
                if(max_buy_number != undefined)
                    _max_buy_num = Math.min(max_buy_number, _pdt_stock);
                //console.log(_pdt_id);
                $('#pdt_id').val(_pdt_id);
                $('#pdt_stock').val(_pdt_stock);
                $('#showNum').html(_pdt_stock_show);
                $('#showPrice').length && $('#showPrice').html(_pdt_sale_price);
                $('#surplus').length && $('#surplus').html(_pdt_stock);
                $('#showMarketPrice').length && $('#showMarketPrice').html('&yen;'+ _pdt_market_price);
                $('#savePrice').length && $('#savePrice').html(_discount_amount);
                $('#discountPrice').length && $('#discountPrice').html(_discount);
                $('#canBuyNum').length && $('#canBuyNum').html(_max_buy_num);
                $('#item_num').length && $('#item_num').attr('max', _max_buy_num);
                $('#p_per_number').length && $('#p_per_number').html( _max_buy_num);
                $('#p_number').length && $('#p_number').html( _pdt_stock);
            }
            else {
                $('#pdt_id').val(0);
                $('#pdt_stock').val(0);
                $('#item_num').length && $('#item_num').attr('max', 0);
                $('#p_per_number').length && $('#p_per_number').html( 0);
                $('#p_number').length && $('#p_number').html( 0);
            }
        }
        return false;
    }
    //如果有销售属性没有被选中，或选中的规格商品不存在，
    //则将隐藏域中的货品id和库存设为0，禁止购买
    $('#pdt_id').val(0);
    $('#pdt_stock').val(0);
}

//检测是否所有的销售属性都有选中，返回选中状态，和选中的值
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
