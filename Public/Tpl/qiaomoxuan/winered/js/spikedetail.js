$(document).ready(function(){
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
    //商品详细标签切换
    tagChange({
        tagObj:$('.tagarea li'),
        tagCon:$('.tagCon .ever'),
        et:'click',
        currentClass:'on'

    });
    tagChange({
        tagObj:$('.procon_r li'),
        tagCon:$('.tagCon .ever'),
        et:'click',
        currentClass:'cur'

    });

    var surplusNums = $("#surplus").html();
    if(0 == surplusNums){
        $("#addToOrder").attr("class", "notSpike");
    }
    $('#addToOrder').click(function(){
		var res = allSpecSelectedCheck('on');
        if(res[0] == false) {
            $.ThinkBox.error('请选择要购买的商品规格！');return false;
        }
        var pdt_id = $('#pdt_id').val();
        var g_id = $('#g_id').val();
        var sp_id = $('#sp_id').val();
        var pdt_stock = parseInt($('#pdt_stock').val());
        var num = parseInt($('#item_num').val());
        var delivery = $("#delivery").val();
		var is_spike = $("#is_spike").val();
		if(is_spike == 1){
			showAlert(false,"您已秒杀过该商品！");
			return false;
		}
        if(delivery == 1){
            var cr_id = parseInt($("#cr_ids").val());
            if(isNaN(cr_id)){
                showAlert(false,"请选择配送区域");
                return;
            }
        }
        if (isNaN(num)){
            $.ThinkBox.error("请重新选择库存，库存大于零");
            return;
        }
        if (num < 1){
            $.ThinkBox.error("请重新选择库存，库存大于零");
            return;
        }
        if (num > 1){
            $.ThinkBox.error("秒杀商品限购1件");
            return;
        }
        if (pdt_stock < 1){
            $.ThinkBox.error("您选择货品的库存已不足");
            return;
        }
        if (num > pdt_stock){
            $.ThinkBox.error("请重新选择库存，库存大于可用库存");
            return;
        }
        if (pdt_id == ""){
            $.ThinkBox.error("库存不足或商品信息不存在");
            return;
        }
        //发送ajax请求
//        var data = $('#goodsForm').serialize();
        var data = new Object();
        data['cart'] = {};
        data['cart']['pdt_id'] = pdt_id;
        data['cart']['g_id'] = g_id;
        data['cart']['sp_id'] = sp_id;
        data['cart']['num'] = num;
        data['type'] = 'spike';
        if("{$Think.session.Members}" == ''){
            $.post('/Home/User/doBulkLogin/',{},function(htmlMsg){
                $.ThinkBox(htmlMsg, {'title' : '会员登录','width':'448px','drag' : true,'unload':true});
            },'html');
            return false;
        }
        if (data != ''){
//                data = data + '&skip=1';
            $.post('/Home/Cart/doAdd',data,function(dataMsg){
                if(dataMsg.status){
                    $.ThinkBox.success("正在跳转……");
                    location.href='/Ucenter/Orders/pageSpikeAdd';
                }else{
                    $.ThinkBox.error(dataMsg.msg);
                }
            },'json');
        }
    });
});

//商品详情页 评论
function CommentPage(gid,page,type){
    $.ajax({
        url:'/Home/Comment/getCommentPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            p:page,
            type:type
        },
        success:function(msgObj){
            $("#recomment").html(msgObj);
            return false;
        }
    });
}

//获得购买记录 gid 商品id num 显示条数 p 第几页
function getBuyRecordPage(gid,num,p){
    $.ajax({
        url:'/Home/Products/getBuyRecordPage',
        dataType:'HTML',
        type:'GET',
        data:{
            gid:gid,
            num:num,
            p:p
        },
        success:function(msgObj){
            $("#buyrecord").html(msgObj);
            return false;
        }
    });
}
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