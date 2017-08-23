$(document).ready(function(){
	/* 导航  */
	$('.wd_nav_map li').mousemove(function(){
		$(this).addClass('hover-bg');
	});
	$('.wd_nav_map li').mouseleave(function(){
		$(this).removeClass('hover-bg');
	});
	/*分类显示隐藏   */   	
	$('#category_container').mousemove(function(){
       $('.wd-bd').show();
       $('.wd-hd').addClass('bg');   
    });
    $('#category_container').mouseleave(function(){
      $(".wd-bd").hide();
      $('.wd-hd').removeClass('bg');   
    });
    $('.side-list').mousemove(function(){
			$(this).find('.wd-listall').show();
			$(this).find('h3').addClass('hover');
	});
	$('.side-list').mouseleave(function(){
		$(this).find('.wd-listall').hide();
		$(this).find('h3').removeClass('hover');
	});
	var t,k=0;
	var imgL=$(".wd_changeImages img").length;
	$(".wd_picNav a").mouseover(function(){
		k=$(".wd_picNav a").index(this);
		navImg(k);
	});
	$(".wd_flashBox").hover(function(){
			clearInterval(t);
		}
		,function(){
			t=setInterval(function(){
				k++;
				if(k==imgL) k=0;
				navImg(k);
			},3000);
	}).trigger("mouseleave");
	
	function navImg(index){
		$(".wd_changeImages img").hide().eq(index).show();
		$(".wd_picNav a").removeClass("picA2").eq(index).addClass("picA2");
	}
	
	$(".tg_tab_nav li").mouseover(function(){
        $(".tg_tab_nav li").removeClass("tg_tab_fouce");
    	$(this).addClass("tg_tab_fouce");
    	switch ($(this).attr("id")) {
    		case "today" :
    			$(".tg_nr").show();
    			$(".tg_nr2").hide();
    			$(".tg_nr3").hide();			
    			break;
    		case "future" :
    			$(".tg_nr").hide();
    			$(".tg_nr2").show();
    			$(".tg_nr3").hide();
    			break;
    		case "after" :
    			$(".tg_nr").hide();
    			$(".tg_nr2").hide();
    			$(".tg_nr3").show();
    			break;
    		};
    });
})
