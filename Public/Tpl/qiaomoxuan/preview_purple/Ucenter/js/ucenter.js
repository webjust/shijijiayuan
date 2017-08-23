/**
 * Created by chelsea on 2015/9/14.
 */
/* ucenter js文件*/

$(function() {

    $(".selector span").click(function () {
        $(".selector ul").show();
    });
    $(".tab_img a i").click(function () {
        $(".alert").toggle(400);
    });
    $(".tab_img a").click(function () {
        $(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
        $(this).children("i").toggleClass("show");

    })
    /*移动到空白位置  隐藏ul li*/
    $(".ucenter_S").hover(
        function () {
            $(".selector ul").hide();
        }
    );

		$(".selectort ul li ").click(function(){
		var inputselect=$("#inputselect");
		var txt = $(this).text();
		$(".selectort .select").html(txt);
		var value = $(this).attr("selectid");
		inputselect.val(value);
		$(".selectort ul").hide();
		
	});
		$(".menu a").click(function(){
			$(this).parent().animate({height:"hide",speed:400});})
		$(".footer ul>li a").click(function(){
			$(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
		$(this).children("i").toggleClass("show");
		$(this).parent().siblings().children(".menu_list").hide(500);
		$(this).siblings(".menu_list").animate({height:"toggle",speed:500});
			})
			$(".proNav ul>li>a").click(function(){
				$(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
				$(this).children("i").toggleClass("show");
				$(this).parent().siblings().children(".menu_list").hide(500);
				$(this).siblings(".menu_list").animate({height:"toggle",speed:500});})
				$(".proNav ul>li .menu_list span a ").click(function(){
					$(this).parent().siblings().children("a").removeClass("on");
			$(this).toggleClass("on");})
					$(".selectort .select").click(function(){
		var ul = $(".selectort ul");
		if(ul.css("display")=="none"){
			ul.slideDown("fast");
		}
		else{
			ul.slideUp("fast");
		}
	})
	$(".tab_img a i").click(function(){
  	$(".alert").toggle(400);
	
 })
 
 $(".tab_img a").click(function(){
	 $(this).children("i").parent().parent().siblings().children("a").children("i").removeClass("show");
	 $(this).children("i").toggleClass("show");

	 })

/*下拉框*/		
jQuery.divselect = function(divselectid,inputselectid) { 
    var inputselect = $(inputselectid); 
    $(divselectid+" .select").click(function(){ 
    var ul = $(divselectid+" ul"); 
    if(ul.css("display")=="none"){ 
    ul.slideDown("fast"); 
    }else{ 
    ul.slideUp("fast"); 
    } 
    }); 
    $(divselectid+" ul li").click(function(){ 
    var txt = $(this).children("a").html(); 
    $(divselectid+" .select").html(txt); 
    var value = $(this).attr("selectid"); 
    inputselect.val(value); 
    $(divselectid+" ul").hide(); 
     
    }); 
    }; 


});

$(document).ready(function() {
    /****START***我的收货地址管理************/
    // 地址管理编辑  表单验证
    $("#edit_deliverTable").validate({
        errorPlacement: function (error, element) {
            element.parent().next().append(error);
        },
        rules: {
            ra_name: {
                required: true,
                isCheck: true,
                maxlength: 20
            },
            ra_mobile_phone: {
                required: true,
                rangelength: [11, 11],
                isPhone: true
            },
            ra_detail: {
                required: true,
                isCheck: true,
                rangelength: [0, 250]
            },
            ra_post_code: {
                isZipCode: true
            }
        },
        messages: {
            ra_name: {
                required: '必填字段',
                isCheck: '包含非法字符！请重新输入',
                maxlength: '收货人姓名不能超过20个字符'
            },
            ra_mobile_phone: {
                required: '必填字段',
                rangelength: '手机号码格式有误',
                isPhone: '请正确输入手机号！'
            },
            ra_detail: {
                required: '必填字段',
                isCheck: '包含非法字符！请重新输入',
                rangelength: '不能超过250个字！'
            },
            ra_post_code: {
                isZipCode: '请输入合法邮编！',
            }
        }
    });
    // 编辑地址 提交 保存
    $("#ajax_pageDeliver").click(function () {
        //$('.red').html('');
        //$('.red').hide();
        //var res = $('#edit_deliverTable').valid();
        //if(res){
        var data = $('#edit_deliverTable').serialize();
        var raid = $('#raid').val();
        var pids = $('#pids').val();
        //alert(raid);return false;
        var url = '/Wap/My/doAddDeliver';
        $.post(url, data, function (msgObj) {
            if (msgObj.success == '1') {
                $(".member_editpass").show();
                $(".member_editpass b").html(msgObj.msg);
                //document.location.reload();
                if (pids != '') {
                    if (pids == 'spike') {
                        window.location.href = "/Wap/Orders/pageSpikeAdd" + "?raid=" + raid;
                    } else {
                        if (pids == 'bulk') {
                            window.location.href = "/Wap/Orders/pageBulkAdd" + "?raid=" + raid;
                        } else {
                            window.location.href = "/Wap/Orders/addOrderPage?pid=" + pids + "&raid=" + raid;
                        }
                    }
                } else {
                    window.location.href = "/Wap/My/pageDeliver";
                }
                return false;
            } else {
                $(".member_editpass").show();
                $(".member_editpass b").html(msgObj.msg);
                return false;
            }
        }, 'json');
        //ajaxReturn('/Ucenter/My/doAddDeliver',data,'post');
        //}
    });
    //删除地址
    $(".del_deliver").click(function(){
        var ra_id = $(this).attr("ra_id");
        var pids = $(this).attr("pids");
		//alert(ra_id);
		//alert(pids);
        $("#tip_div").dialog({
            width:330,
            height:170,
            modal:true,
            title:'提示信息',
            buttons:{
                '确定':function(){
                    $("#tip_div").dialog('destroy');
                    $('#pro_diglog').append($('#tip_div'));
                    mDialogCallback(ra_id,pids);
                },
                "取消": function() {
                    $("#tip_div").dialog('destroy');
                    $('#pro_diglog').append($('#tip_div'));
                }
            }
        });
    });
    /*
     * @param   确定删除时回调此方法
     */
    function mDialogCallback(ra_id,pids){
        var url = "/Wap/My/doDelDeliver/";
        ajaxReturn(url,{'ra_id':ra_id,'pids':pids},'post');
    }
    //我的地址页面 添加收货地址 页面异步加载
    //function addAddress(){
    //    $.ajax({
    //        url:'/Wap/ReceiveAddress/addAddressPage',
    //        dataType:'HTML',
    //        type:'POST',
    //        success:function(msgObj){
    //            $("#addAddress").html(msgObj);
    //            return false;
    //        }
    //    });
    //}
    //addAddress();

    //添加地址时 验证
    $("#regforms").validate({
        errorPlacement: function(error, element) {
            element.next().append(error);
        },
        submitHandler:function(form){
            var province = $("#province").val();
            var city = $("#city").val();
            var region = $("#region").val();
            if("请选择" == province || 0 == city || 0 == region){
                alert("请选择收货地址！");
                return false;
            }
            var m_id = $("#m_id").val();
            var ra_name = $("#ra_name").val();
            var ra_mobile_phone = $("#ra_mobile_phone").val();
            var cr_id = $("#region").val();
            var ra_is_default = $("#ra_is_default:checked").val();
            var ra_detail = $("#ra_detail").val();
            var ra_post_code = $("#ra_post_code").val();
            var ra_phone_area = $("#ra_phone_area").val();
            var ra_phone = $("#ra_phone").val();
            var pids = $('#pids').val();
            $.ajax({
                url:"/Wap/ReceiveAddress/doAdd",
                data:{"ra_name":ra_name,"ra_post_code":ra_post_code,"ra_detail":ra_detail,"ra_mobile_phone":ra_mobile_phone,"cr_id":cr_id,"m_id":m_id,"ra_is_default":ra_is_default,"ra_phone_area":ra_phone_area,"ra_phone":ra_phone},
                type:"POST",
                dataType:"JSON",
                success:function(msgObj){
                    if(msgObj){
                        if(pids!=''){
                            //window.location.href = "/Wap/Orders/addOrderPage?pid=" + pids;
                            if(pids == 'spike'){
                                window.location.href = "/Wap/Orders/pageSpikeAdd"+"?raid="+msgObj.data;
                            }else{
                                if(pids == 'bulk'){
                                    window.location.href = "/Wap/Orders/pageBulkAdd"+"?raid="+msgObj.data;
                                }else{
                                    window.location.href = "/Wap/Orders/addOrderPage?pid=" + pids+"&raid="+msgObj.data;
                                }
                            }
                        }else{
                            window.location.href = "/Wap/My/pageDeliver";
                        }
                    }else{
                        $.ThinkBox.error("保存收货地址失败！");
                    }



                }
            });
        },
        rules : {
            ra_name : {
                required:true,
                isCheck:true,
                maxlength:20
            },
            ra_mobile_phone : {
                required : true,
                rangelength:[11,11],
                isPhone:true
            },
            ra_detail : {
                required : true,
                isCheck : true,
                rangelength:[0,250]
            },
            ra_post_code : {
                isZipCode : true,
            }
        },
        messages : {
            ra_name : {
                required : '必填字段',
                isCheck: '包含非法字符！请重新输入',
                maxlength:'收货人姓名不能超过20个字符'
            },
            ra_mobile_phone  : {
                required : '必填字段',
                rangelength:'手机号码格式有误',
                isPhone:'请正确输入手机号！'
            },
            ra_detail : {
                required : '必填字段',
                isCheck : '包含非法字符！请重新输入',
                rangelength : '不能超过250个字！'
            },
            ra_post_code : {
                isZipCode : '请输入合法邮编！',
            }
        }
    });


    function initSelectCityRegion() {
        $('#city').html('<option value="0">请选择</option>');
        $('#region').html('<option value="0">请选择</option>');
    }

    function doAddAddress(){
        $("form").submit();
    }


    /****END***我的收货地址管理**************/


});
function getcommoninfo(){
    $.ajax({
        url:'/Wap/Ucenter/getcommonInfo',
        dataType:'HTML',
        type:'POST',
        success:function(msgObj){
            $("#userInfo").html(msgObj);
            return false;
        }
    });
}




