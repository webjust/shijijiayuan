$(function(){
				$(".dropdown-btn").bind("click",function(){
					$(this).toggleClass("open");
					$(".navbar").toggleClass("open")
            $(".index_wrap").toggle();
				})
        $(".index_wrap").click(function(){
           $(".index_wrap").hide();
           $(".navbar").removeClass("open")
           $(".dropdown-btn").removeClass("open")
        })  
        var index_banner = new Swiper('.index_banner', {
          pagination: '.index_banner .swiper-pagination',
          paginationClickable: true
        });

        (function(){
          var num = 1;
          var m = $(".nav_in .navA .navAli.current").index();
          $(".nav_in .navA .navAli").mouseenter(function(){
            var _this = $(this);
            var index = _this.attr("data-toggle");
            var h = $("[data-id="+index+"]").height();

            _this.addClass("current").siblings(".navAli").removeClass("current");
            $("[data-id="+index+"]").stop(true).slideDown(300).siblings("[data-id]").slideUp(300);
            $(".navMenu").css({'height' : h});
            if( index > num ){
              $("[data-id="+num+"]").slideUp(300)
              $("[data-id="+index+"]").show();
            }else{
              $("[data-id="+index+"]").slideDown(1000);
            }
            num = index;

          })
          $("#nav").mouseleave(function(){
            $("[data-id="+num+"]").slideUp(300);
            $(".navMenu").animate({height: 0},300)
            $(".nav_in .navA .navAli").eq(m).addClass("current").siblings(".navAli").removeClass("current");
          });
        })();
        $(".neiye_case .title02 .ulli li").click(function(){
          var index = $(this).attr("data-toggle");
          alert(index )
          var num= $(".neiye_case .title02 .ulli2 li").attr("data-id");
          $("[data-id="+index+"]").show();

        });
      var win = $(window).height();      
      $(window).scroll(function(e) {
            var top = $(window).scrollTop();
        //如果 这个滚动坐标值 top 大于这个窗口的高度，就让图片显示，否则让你隐藏
        if(top>win){
          $('.alltop').fadeIn();
        }else{
          $('.alltop').fadeOut();
        }
        });
      
      //事件模块
      $('.alltop').click(function(e) {
            $('html,body').animate({scrollTop:0},300) 
        }); 
})