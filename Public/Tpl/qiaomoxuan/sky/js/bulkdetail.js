/**
 * 根据指定时间显示动态倒计时效果
 *
 * @param times 指定时间年月日 格式 Y-m-d H:i:s
 * @param 显示时间的id 顺序为 天->小时->分->秒
 * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
 */
$(function(){
    //放大镜开始
    $('#example3').etalage({
        thumb_image_width: 415,
        thumb_image_height: 415,
        source_image_width: 900,
        source_image_height: 900,
        zoom_area_width: 450,
        zoom_area_height: 450,
        zoom_area_distance: 5,
        small_thumbs: 5,
        smallthumb_inactive_opacity: 0.5,
        smallthumbs_position: 'top',
        show_icon: true,
        icon_offset: 20,
        autoplay: false,
        keyboard: false,
        zoom_easing: false
    });
   });
function countDown(time,id,status,nowtime,endtime,tuangouOver){
    var day_elem = $("#timebox"+id).find('#day');
    var hour_elem = $("#timebox"+id).find('#hour');
    var minute_elem = $("#timebox"+id).find('#minute');
    var second_elem = $("#timebox"+id).find('#second');

    var reg = new RegExp("-","g");
    var timeStr = time.replace(reg,"/");
    var nowtimeStr = nowtime.replace(reg,"/");
    var timeStr = new Date(timeStr);
    var nowtimeStr = new Date(nowtimeStr);

    var end_time = timeStr.getTime(),//月份是实际月份-1
        sys_second = (end_time-nowtimeStr.getTime())/1000;
    var timer = setInterval(function(){
        if (sys_second > 0) {
            sys_second -= 1;
            var day = Math.floor((sys_second / 3600) / 24);
            var hour = Math.floor((sys_second / 3600));
            var minute = Math.floor((sys_second / 60) % 60);
            var second = Math.floor(sys_second % 60);
            day_elem && $(day_elem).text(day);//计算天
            $(hour_elem).text(hour<10?"0"+hour:hour);//计算小时
            $(minute_elem).text(minute<10?"0"+minute:minute);//计算分钟
            $(second_elem).text(second<10?"0"+second:second);//计算秒杀
        } else {
            if(status == 1){
                $("#miaosha"+id).html("<a class='none'>已结束</a>");
                $(".tuangouOver").toggle();
            } else if(status == 2){
                $("#miaosha"+id).html('<a href="javascript:void(0);" onclick="addToOrder(1);" class="addCart">加入购物车</a>');
                $("#colockbox"+id).find('.changemsg').text('距结束');
                $(".tuangouOver").toggle();
                countDown(endtime,id,1,time);
            }
            clearInterval(timer);
        }
    }, 1000);
}//倒计时

function showSelect(obj){
    var _this = $(obj);
    var item_id  = $('#item_id').val();
    var name = '';
    var yprice  = $('#yprice').val();
    var _item_id = jQuery('#' + item_id);
    if (_this.hasClass('on')){
        _this.removeClass("on");
        $("#pdt_stock").val("");
        $("#pdt_id").val("");
        $("#showNum").html = "";
        $.ThinkBox.error("请勾选您要的商品信息");
    } else{
        _this.siblings().removeClass("on");
        _this.addClass("on");
        var rsize = "";
        var rcolor = "";
        var showvalue = "";
        var _parent_color = jQuery("#sku" + item_id + '_1').find('a.on');
        var _parent_size = jQuery("#sku" + item_id + '_2').find('a.on');
        var color_len = _parent_color.length;
        var size_len = _parent_size.length;
    }

    if (size_len > 0 && color_len > 0){
        rcolor = _parent_color.attr('name');
        rsize = _parent_size.attr('name');
        showvalue = arr[rsize+';'+rcolor];
    }else{
        var _parent_li = _this.parent().parent().find('a.on');
        rsize = _parent_li.attr('name');
        if (rsize != ""){
            var info = rsize;
            showvalue = arr[info];
        }
    }
    if (showvalue != undefined){
        var vale = showvalue.split("|");
        if (vale.length > 0){
            if (vale[0]){
                $("#pdt_id").val(vale[0]);
            }
            if (vale[2]){
                $("#showMarketPirice").html(vale[2]);
                $("#savePrice").html(parseFloat(vale[2] - yprice).toFixed(2));
//                $("#discountPrice").html(parseFloat(((yprice/vale[2])*10).toFixed(2)));
            }
        }
    }
}

function addToOrder(i){
    var res = allSpecSelectedCheck('on');
    if(res[0] == false) {
        $.ThinkBox.error('请选择要购买的商品规格！');return false;
    }
    var pdt_id = $('#pdt_id').val();
    var gp_id  = $('#gp_id').val();
    var error_1  = $('#error_1').val();
    var error_4  = $('#error_4').val();
    var no_login = $("#no_login").val();
    var num = parseInt($('#item_num').val());
    var max = parseInt($('#item_num').attr('max'));
    var verify_code = $('#verify_code').length ? $('#verify_code').val() : '';
    if(pdt_id == ''){
        $.ThinkBox.error(error_4);return false;
    }
    if (isNaN(num)){
        $.ThinkBox.error(error_1);return false;
    }
    if (num < 1){
        $.ThinkBox.error("购买数量不能小于1");return false;
    }
    if(num > max){
        $.ThinkBox.error("您最多能购买"+max+"件本商品");return false;
    }
    var data = new Object();
    data['cart'] = {};
    data['cart']['pdt_id'] = pdt_id;
    data['cart']['gp_id'] = gp_id;
    data['cart']['num'] = num;
    data['cart']['code_data'] = {};
    data['cart']['code_data']['verify'] = verify_code;
    data['type'] = 'bulk';
    if(i==2){
        data['cart']['is_deposit'] = 1;
    }
    if( no_login == ''){
        $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
            $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
        },'html');
        return false;
    }
    $.post('/Home/Cart/doAdd',data,function(dataMsg){
        if(dataMsg.status == 1){
            location.href = dataMsg.url;
        }else{
            $.ThinkBox.error(dataMsg.msg);
        }
    },'json');
}
// 加减调整商品数量
function countNum(i){
    var _this = $("#item_num");
    var num=parseInt(_this.val()),max=parseInt(_this.attr('max'));
    num=num+i;
    if((num<=0)||(num>max)||(num>999)){
        return false;
    }
    _this.val(num);
}
// 修改商品数量
function blurSelectNum(){
    var _this = $("#item_num");
    var max = parseInt(_this.attr('max'));
    var ereg_rule=/^\+?[1-9][0-9]*$/;
    if(!ereg_rule.test(_this.val())){
        _this.val(1);
    }else{
        if(_this.val()>max){
            _this.val(max);
        }
    }
}