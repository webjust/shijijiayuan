(function($){
    $.fn.hoverForIE6=function(option){
        var s=$.extend({current:"hover",delay:10},option||{});
        $.each(this,function(){
            var timer1=null,timer2=null,flag=false;
            $(this).bind("mouseover",function(){
                if (flag){
                    clearTimeout(timer2);
                }else{
                    var _this=$(this);
                    timer1=setTimeout(function(){
                        _this.addClass(s.current);
                        flag=true;
                    },s.delay);
                }
            }).bind("mouseout",function(){
                if (flag){
                    var _this=$(this);timer2=setTimeout(function(){
                        _this.removeClass(s.current);
                        flag=false;
                    },s.delay);
                }else{
                    clearTimeout(timer1);
                }
            })
        })
    }
})(jQuery);

//收藏本站
function AddFavorite(title, url) {
    try {
        window.external.addFavorite(url, title);
    }
    catch (e) {
        try {
            window.sidebar.addPanel(title, url, "");
        }
        catch (e) {
            alert("抱歉，您所使用的浏览器无法完成此操作。\n\n加入收藏失败，请使用Ctrl+D进行添加");
        }
    }
}