ajaxLoadShoppingMember();
var is_shop_open = $("#gy_shop_open").val();
if ( is_shop_open == 1 || is_shop_open == undefined) {
	ajaxLoadShoppingCart(1);
}
/*********异步加载购物车****/
function ajaxLoadShoppingCart(int_page_num){
    if(!int_page_num){
		int_page_num = 1;
	}
    $.post('/Home/Cart/Pagelist',{'int_page_num':int_page_num},function(htmlObj){
        $("#shopping_cart_list").html(htmlObj);
    },'html');
}
/*********异步删除购物车数据********/
function deleteFromMyCart(pid,type){
    if(confirm('确定要将此商品从购物车删除吗？\n\n删除后，您还可以重新加入购物车。')){
		$.get('/Home/Cart/doDel/',{'pid':pid,'type':type},function(da){
            ajaxLoadShoppingCart(1);
        },'json');
	}
}
function ajaxLoadShoppingMember(){
	$.post('/Home/User/showMemberInfo',{},function(htmlObj){
		$("#shopping_member_list").html(htmlObj);
	},'html');
}
