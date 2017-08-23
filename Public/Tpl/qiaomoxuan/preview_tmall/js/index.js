/***异步加载获取商品信息****/
ajaxLoadGoodsInfo();
function ajaxLoadGoodsInfo(g_id){
    if(!g_id){
		g_id = 0;
	}
    $.post('/Home/Products/getHotProducts',{'g_id':g_id},function(htmlObj){
        $("#picBig").html(htmlObj);
		if(g_id !=0){
			$(".removeHover").removeClass('onHover');
			$("#addHover"+g_id).addClass('onHover');		
		}
    },'html');
}

//异步获取首页会员登陆信息
ajaxLoadMemberInfo();
function ajaxLoadMemberInfo(){
    $.post('/Home/User/getMemberInfo',{'type':1},function(htmlObj){
        $("#shopping_member_list").html(htmlObj);
    },'html');
}

//异步获取热卖推荐商品
ajaxLoadGoodsList();
function ajaxLoadGoodsList(){
    $.post('/Home/Products/getHotProductsList',{},function(htmlObj){
        $("#show_good_list").html(htmlObj);
    },'html');
}
$(function(){
    var thisURL = document.URL;
    var tmpUPage = thisURL.split( "/" );
    var tag = true;
    for(var j=0;j<=tmpUPage.length;j++){
        if(tmpUPage[j] == 'tmall'){
            tag = false;break;
        }
    }
    if(tag){
        var muilift = '';
        var temp = new Array();
        $('strong[id$="F"]').each(function(index,area){
            var title = $(this).html();
            var tag = true;
            for(var i=0;i<=temp.length;i++){
                if(temp[i] == title){
                    tag = false;
                    break;
                }
            }
            if(tag){
                muilift += '<a href="#'+(index+1)+'F"><b>'+(title.substr(0,2))+'</b><span>'+title.substr(3,title.length-3)+'</span></a>';
            }
            temp[index] = title;
        });
        $('#main').after('<div class="muiLift" id="muiLift">'+muilift+'</div>');
    }
})