$(function(){

    //轮播
    var sWidth = $("#focus").width();
    var len = $("#focus ul li").length;
    var index = 0;
    var picTimer;

    var btn = "<div class='btnBg'></div><div class='btn'>";
    for(var i=0; i < len; i++) {
        btn += "<span></span>";
    }

    btn += "</div><div class='preNext pre'></div><div class='preNext next'></div>";
    $("#focus").append(btn);
    $("#focus .btnBg").css("opacity",0.5);


    $("#focus .btn span").css("opacity",1).mouseenter(function() {
        index = $("#focus .btn span").index(this);
        showPics(index);
    }).eq(0).trigger("mouseenter");


    $("#focus .preNext").css("opacity",1).hover(function() {
        $(this).stop(true,false).animate({"opacity":"1"},300);
    },function() {
        $(this).stop(true,false).animate({"opacity":"1"},300);
    });


    $("#focus .pre").click(function() {
        index -= 1;
        if(index == -1) {index = len - 1;}
        showPics(index);
    });


    $("#focus .next").click(function() {
        index += 1;
        if(index == len) {index = 0;}
        showPics(index);
    });


    $("#focus ul").css("width",sWidth * (len));


    $("#focus").hover(function() {
        clearInterval(picTimer);
    },function() {
        picTimer = setInterval(function() {
            showPics(index);
            index++;
            if(index == len) {index = 0;}
        },3000);
    }).trigger("mouseleave");

    //
    function showPics(index) {
        var nowLeft = -index*sWidth;
        $("#focus ul li").css("width",sWidth);

        $("#focus ul").stop(true,false).animate({"left":nowLeft},300);
        $("#focus .btn span").stop(true,false).css("backgroundColor","#666").eq(index).stop(true,false).css("backgroundColor","#643C93");
    }
});