
function checkprice(){
    var price1 = $('#price1').val();
    var price2 = $("#price2").val();
    if(price1 >= price2 ){
        alert("价格区间填写错误，请重新填写！");
        $('.box').attr("disabled",true);
    }else{
        $('#startPrice').val(price1);
        $('#endPrice').val(price2);
        $('.box').removeAttr("disabled");
    }
}

$(document).ready(function($){
    $('.clickThisTab').click(function(){
        var objDat = new Object();
        var c = $(this).attr('c');
        var t = $(this).attr('t');
        var k = $(this).attr('k');
        var cid = $('#cid').val();
        var bid = $('#bid').val();
        var tid = $('#tid').val();
        var is_new = $('#is_new').val();
        var is_hot = $('#is_hot').val();
        var startPrice = $('#startPrice').val();
        var endPrice = $('#endPrice').val();
        var path = $('#path').val();

        if(c == 'hot'){
            objDat['price'] = $(this).next().attr('t');
            if(objDat['price'] == 'price'){
                objDat['price_col'] = "brownBot";
            }else{
                objDat['price_col'] = "";
            }
            objDat['new'] = $(this).next().next().attr('t');
            if(objDat['new'] == 'new'){
                objDat['new_col'] = "brownBot";
            }else{
                objDat['new_col'] = "";
            }

        }else if(c == 'price'){
            objDat['hot'] = $(this).prev().attr('t');
            if(objDat['hot'] == 'hot'){
                objDat['hot_col'] = "brownBot";
            }else{
                objDat['hot_col'] = "";
            }
            objDat['new'] = $(this).next().attr('t');
            if(objDat['new'] == 'new'){
                objDat['new_col'] = "brownBot";
            }else{
                objDat['new_col'] = "";
            }
        }else{
            objDat['price'] = $(this).prev().attr('t');
            if(objDat['price'] == 'price'){
                objDat['price_col'] = "brownBot";
            }else{
                objDat['price_col'] = "";
            }
            objDat['hot'] = $(this).prev().prev().attr('t');
            if(objDat['hot'] == 'hot'){
                objDat['hot_col'] = "brownBot";
            }else{
                objDat['hot_col'] = "";
            }
        }
        if(k == 't'){
            if(t == 'price'){
                t='_price';
            }else if(t == '_price'){
                t='price';
            }
            if(t == 'gcom'){
                t='_gcom';
            }else if(t == '_gcom'){
                t='gcom';
            }
            if(t == '_hot'){
                t='hot';
            }else if(t == 'hot'){
                t='_hot';
            }
        }
        var url = "/Home/Products/Index/?"+serializeObject({'cid':cid,'bid':bid,'path':path,'tid':tid,'startPrice':startPrice,'endPrice':endPrice,'order':t})+"&"+serializeObject(objDat);
        location.href = url;
    });
    //价格区间查询
    $("#submitPrice").click(function(){
        checkprice();
        var cid = $('#cid').val();
        var bid = $('#bid').val();
        var is_new = $('#is_new').val();
        var is_hot = $('#is_hot').val();
        var startPrice = $('#startPrice').val();
        var endPrice = $('#endPrice').val();
        if(startPrice >= 0 && endPrice > 0){
            if( parseFloat(endPrice) >= parseFloat(startPrice)){
                var url = "/Home/Products/Index/?"+serializeObject({'cid':cid,'bid':bid,'startPrice':startPrice,'endPrice':endPrice,'new':is_new,'hot':is_hot});
                location.href = url;
                return true;
            }else{
                return false;
            }
        }
    });
    serializeObject = function(obj) {
        var str = [];
        for(var p in obj)
            str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
        return str.join("&");
    }
});