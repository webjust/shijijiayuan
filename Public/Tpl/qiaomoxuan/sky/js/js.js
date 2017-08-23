$(document).ready(function(){	
	$(".allGoodsCon ul li").hover(function(){
		$(this).children("div").show();
		$(this).css("backgroundColor","#F8F8F8");
		$(this).css("borderRight","1px solid #F8F8F8");
	},function(){
		$(this).children("div").hide();
		$(this).css("backgroundColor","#EEEDEF");
		$(this).css("borderRight","1px solid #2D4F8E");
	});

	$(".shopcartCon").hover(function(){
		$(this).css("border","1px solid #c5c5c5");
		$(this).css("borderBottom","1px solid #c5c5c5");
		$(".shopcartHide").show();
	},function(){
		$(this).css("border","1px solid #F8F8F8");
		$(".shopcartHide").hide();
	});	
	//判断页面是首页还是其他页面，首页展示类目其他页面隐藏类目导航
//	var is_show_category = ($('#is_show_category').val() == undefined)?0:1;
//	if(is_show_category != '1'){
//		$('#category_show').addClass('allGoodshide');
//	}
	$('#category_show').addClass('allGoodshide');
	//类目显示与隐藏
	$(".allGoods").hover(function(){
		$(".allGoodshide").show();
	},function(){
		$(".allGoodshide").hide();
	});
    
    //清空浏览历史
    $("#clear_history").live("click",function(){
    	var liContent =  $("#all_history_box");
		if(liContent){
			liContent.html('<ul id="all_history_box">暂无浏览历史</ul>');
		}
		var dc=document.cookie.split(';');
		var date = new Date();
		date.setTime(date.getTime() - 10000);
	    for ( var i=0;i < dc.length;i++) {
	        var c = dc[i];
	        while (c.charAt(0)==' ') c = c.substring(1,c.length);
	        if ( c.indexOf('HistoryItems')==0 ) {
	        	var cn = c.substring(0,c.indexOf('='));
	            //document.cookie = cn + "=" + "; expires=" + date.toUTCString();
	            $.ajax({
	                url:'/Home/Products/deleteBrowsehistory',
	                cache:false,
	                dataType:'json',
	                data:{gid:cn},
	                type:"POST"
	            });
	        }
	    }
    });
});

function setTab(name,cursel,n){
	//清除焦点
	$('.hold').removeClass('hold');
	//隐藏浮层
	$('#hover').hide();
	 for(i=1;i<=n;i++){
		  var tab=(document.getElementById(name+i))?document.getElementById(name+i):'';
		  if(tab !=''){
			  var con=document.getElementById("con_"+name+"_"+i);
			  tab.className=i==cursel?"onHover":"";
			  con.style.display=i==cursel?"block":"none"; 
		  }
	 }
}
function yLogin() {
	$.colorbox({ inline: true, href: "#yLogi", width: "402px", height: "282px"});
}

$(document).ready(function(){
    //区域限购
    $("#restriction").click(function(){
        $.ajax({
            url:'/Home/Index/doCity',
            cache:false,
            dataType:'html',
            data:{},
            type:'POST',
            success:function(msgObj){
                $.webox({
                    height:376,
                    width:640,
                    bgvisibel:true,
                    title:'选择地区',
                    html: msgObj
                });
            }
        });
        
    });
});

$(document).ready(function(e) {
	$('#iToTop').click(function(){
		$(document).scrollTop(0);	
	})
});

/*加入对比*/
$(document).ready(function(){
    $("#clearToCompare").live("click",function(){
        var exp = $(this).attr("exp");
        if(exp == 'Expand'){
            $(this).attr("exp","Collapse");
            $(".contC").hide();
            $(".titD").hide();
            $(".contrast").css({"height":"auto"});
        }else{
            $(this).attr("exp","Expand");
            $(".contC").animate({display:''},1000,function(){
                $(".contrast").css({"height":"182px"});
            });
        }
    });
});
function addToCompare(gid) {
    if(isNaN(gid)){
        $.ThinkBox.error("商品不存在或者已经被下架");
        return false;
    }
    var compButton = $("#comp_bt_"+gid);
    if(compButton.hasClass('dbClick')) {
        delCompare(gid);
        toCompare();
    } else {
        $(compButton).addClass("dbClick");
        $.ajax({
            url:'/Home/Products/addToCompare',
            cache:false,
            dataType:'json',
            data:{gid:gid,check:'checked'},
            type:"POST",
            success:function(msgObj){
                if(msgObj.status == '0'){
                    $.ThinkBox.error(msgObj.info);
                    $(compButton).removeClass("dbClick");
                    return false;
                }else{
                    $.ThinkBox.success('加载中...');
                    toCompare();
                }
            }
        });
    }
}
function delCompare(gid) {
    if(gid >= 0){
        $.ajax({
            url:'/Home/Products/clearToCompareList',
            cache:false,
            dataType:'json',
            data:{gid:gid},
            type:"POST",
            success:function(msgObj){
                if(msgObj.status == '1'){
                    if(gid == 0) {
                        $(".compare_li").remove();
                        $(".dbClick").removeClass('dbClick');
                    } else {
                        $("#compare_li_"+gid).remove();
                    }
                    $(".db").each(function(){
                        if($(this).attr("gid") == gid){
                            $(this).removeClass("dbClick");
                        }
                    });
                    $.ThinkBox.success("删除成功");
                    return false;
                }else{
                    $.ThinkBox.error("删除失败");return false;
                }
            }
        });
    }else{
         $.ThinkBox.error("删除错误，请重试...");return false;
    }
} 
function toCompare(){
    $.ajax({
        url:'/Home/Products/getGoodsCompareList',
        cache:false,
        dataType:'HTML',
        data:{},
        type:"POST",
        success:function(msgObj){
            $("#contrast").css({"height":"182px"});
            $("#contrast").html(msgObj);
        }
    });
}

//加入收藏
function AddFavorite(sURL, sTitle) {
        sURL = encodeURI(sURL);
    try{  
        window.external.addFavorite(sURL, sTitle);  
    }catch(e) {  
        try{  
            window.sidebar.addPanel(sTitle, sURL, "");  
        }catch (e) {  
            alert("加入收藏失败，请使用Ctrl+D进行添加,或手动在浏览器里进行设置.");
        }  

    }
}

//设为首页

function SetHome(url){
    if (document.all) {
        document.body.style.behavior='url(#default#homepage)';
           document.body.setHomePage(url)
    }else{
        alert("您好,您的浏览器不支持自动设置页面为首页功能,请您手动在浏览器里设置该页面为首页!");
    }
}

