$(".menu-opener").click(function(){
  $(".menu-opener, .menu-opener-inner, .menu").toggleClass("active");
});
$(".neiye_case .title02 .ulli li").click(function(){   
	var index = $(this).attr("data-toggle");
  $(this).addClass("on").siblings().removeClass("on"); 
  $("[data-id="+index+"]").toggleClass("menu-inner2").siblings("[data-id]").removeClass("menu-inner2");
});

function checkCartprice_diy(){
    var url = "/Wap/Cart/checkCartprice";

    $.post(
        url,
        {"pdt_id": "pdt_id"},
        function(dataMsg){
            if(typeof dataMsg.all_nums != 'undefined') $("#shopping_all_nums").html(dataMsg.all_nums);
        },
        'json'
    );
}

      var win = $(window).height();      
      $(window).scroll(function(e) {
            var top = $(window).scrollTop();
        //如果 这个滚动坐标值 top 大于这个窗口的高度，就让图片显示，否则让你隐藏
        if(top>win){
          $('.alltop').fadeIn();
        }else{
          $('.alltop').fadeOut();
        }
        if(top>win){
          $('#ydzw_pp .zimu').fadeIn();
        }else{
          $('#ydzw_pp .zimu').fadeOut();
        }
        });
      
      //事件模块
      $('.alltop').click(function(e) {
            $('html,body').animate({scrollTop:0},300) 
        }); 
        var wimb;
        var wimw;
        $(".footer ul li").hover(function() {
            wimb = $(this).find("img").attr("src");
            wimw = $(this).find("img").attr("_src");
            $(this).find('img').attr('src', '' + wimw + '');
        }, function() {
            $(this).find('img').attr('src', '' + wimb + '');
        });
        $(".ysxz").click(function(){
          
          $(".shadowbox").css({"display":"block"})
          $(".gwc").animate({"bottom":"0"})
          $('html,body').css({"overflow":"hidden"})
        })
        $(".close").live("click", function(){
          $('html,body').css({"overflow":"auto"})
          $(".shadowbox").css({"display":"none"})
          $(".gwc").animate({"bottom":"-100%"})
        })

