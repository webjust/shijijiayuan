$(document).ready(function() {
    $("input:text,input:password,textarea").focus(function(){
        $(this).removeClass("blur");
        $(this).addClass("focus");
    }).blur(function(){
        $(this).removeClass("focus");
        $(this).addClass("blur");
    });
    $.metadata.setType("attr", "validate");
    
    /****START***我的收货地址管理************/
    $('.spanWrong').hide();
    $('#edit_deliverTable').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            for (var name in errors) {
                //alert(errors[name]);
                $('#' + name).parent('li').children('p').show();
                $('#' + name).parent('li').children('p').html(errors[name]);
            }

            return false;
        },
        onkeyup: false,
        onfocusout: false
    });
    $("#ajax_pageDeliver").click(function(){
        $('.red').html('');
        $('.red').hide();
        var res = $('#edit_deliverTable').valid();
        if(res){
            var data = $('#edit_deliverTable').serialize();
            var raid = $('#raid').val();
			var pids = $('#pids').val();
			var zt = $('#zt').val();
            //alert(raid);return false;
            var url = '/Wap/My/doAddDeliver';
            $.post(url, data, function(msgObj) {
                if(msgObj.success == '1'){
                    $(".member_editpass").show();
                    $(".member_editpass b").html(msgObj.msg);
                    //document.location.reload();
					if(pids!=''){
						if(pids == 'spike'){
							window.location.href = "/Wap/Orders/pageSpikeAdd"+"?raid="+raid+"&zt="+zt;
						}else{
							if(pids == 'bulk'){
								window.location.href = "/Wap/Orders/pageBulkAdd"+"?raid="+raid+"&zt="+zt;
							}else{
								window.location.href = "/Wap/Orders/addOrderPage?pid=" + pids+"&raid="+raid+"&zt="+zt;
							}
						}
					}else{
						window.location.href = "/Wap/My/pageDeliver";
					}
                    return false;
                }else{
                    $(".member_editpass").show();
                    $(".member_editpass b").html(msgObj.msg);
                    return false;
                }
            }, 'json');
            //ajaxReturn('/Ucenter/My/doAddDeliver',data,'post');
        }
    });
    /****END***我的收货地址管理**************/
    
    /****START***编辑我的资料************/
    $("#ajax_doedit").click(function(){
        var res = $('#my_doedit').valid();
        if(res){
            var data = $('#my_doedit').serialize();
            ajaxReturn('/Wap/My/doEdit',data,'post');
        }
    });
    /****END***编辑我的资料************/
    
    /****START***处理修改密码************/
    $('.spanWrong').hide();
    $('#edit_changePass').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            for (var name in errors) {
                $('#' + name).parent('td').next().children('span').show();
                $('#' + name).parent('td').next().children('span').html(errors[name]);
            }

            return false;
        },
        onkeyup: false,
        onfocusout: false
    });
    $('.spanWrong').hide();
	var ti;
    $("#ajax_changePass").click(function() {
        $('.spanWrong').html('');
        $('.spanWrong').hide();
        var res = $('#edit_changePass').validate();
        if (res) {
            var data = $('#edit_changePass').serialize();
            var url = '/Wap/My/doChange/';
            $.post(url, data, function(msgObj) {
                if(msgObj.success == '1'){
                    $(".member_editpass").show();
                    $(".member_editpass b").html(msgObj.msg);
                    ti = setTimeout(function(){
                        location.href = '/Wap/User/doLogout';
                    },2000)
                   // window.location.href = "/Wap/User/doLogout";
                    return false;
                }else{
                    $(".member_editpass").show();
                    $(".member_editpass b").html(msgObj.msg);
                    return false;
                }
            }, 'json');
            $(":input").val('');
        }
    });
    /****END****处理修改密码************/

    /********执行用户注册************/
    $('.spanWrong').hide();

    /********执行用户注册***END*********/

    /********执行用户登录************/
    $('#login_form').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            for (var name in errors) {
                $('.login_error').show();
                $('.login_error').html(errors[name]);
                return false;
            }
            return false;
        },
        onkeyup: false,
        onfocusout: false
    });
    $('#login_submit').click(function() {
        var res = $('#login_form').valid();
        if (res) {
            var data = $('#login_form').serialize();
            var redirct_url  =  $('#redirct_url').val();
            var login_url = $('#login_url').val();
            if(redirct_url == "" || redirct_url == undefined){
            	redirct_url = "/Ucenter";
            }
            if(login_url == "" || login_url == undefined){
            	 var url = "/Wap/User/doLogin/";
            }else{
            	 var url = login_url;
            }
            var url = "/Ucenter/User/doLogin/";
            $.post(url, data, function(json) {
                if (json.result) {
                    window.location.href = redirct_url;
                } else {
                    $('.login_error').show();
                    $('.login_error').html(json.msg);
                    replaceVerificationCode();
                }
            }, 'json');
        }
    });
    $('#home_login_submit').click(function() {
        var res = $('#login_form').valid();
        if (res) {
            var data = $('#login_form').serialize();
            var login_url = $('#login_url').val();
            if(login_url == "" || login_url == undefined){
            	 var url = "/Wap/User/doLogin/";
            }else{
            	 var url = login_url;
            }

            $.post(url, data, function(json) {
                if (json.result) {
                    window.location.href = "/Wap/Index/index";
                } else {
                    
                    $('.login_error').show();
                    $('.login_error').html(json.msg);
                    
                }
            }, 'json');
        }
    });
    /********执行用户登录******END******/

    /**
     * 忘记密码页面表单验证
     * @author zuo <zuojianghua@gmail.com>
     * @date 2012-12-12
     */
    $('#foget_form').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            $(".spanWrong").html('');
            $(".spanWrong").hide();
            for (var name in errors) {
                $(".spanWrong[for='" + name + "']").html(errors[name]);
                $(".spanWrong[for='" + name + "']").show();
            }
            return false;
        },
        onkeyup: false,
        onfocusout: false
    });

});
/**********刷新验证码**********/
function replaceVerificationCode() {
    $("#verificationcode").attr('src', '/Wap/User/verify/' + Math.random());
}
/***********用户登录回车触发登录事件**************/
function EnterPress(e) { //传入 event 
    var e = e || window.event;
    if(!e){
    	return;
    }
    if (e.keyCode == 13) {
        var res = $('#login_form').valid();
        if (res) {
            var data = $('#login_form').serialize();
            var login_url = $('#login_url').val();
            var redirct_url  =  $('#redirct_url').val();
            if(redirct_url == "" || redirct_url == undefined || redirct_url == null){
            	redirct_url = "/Ucenter";
            }
            if(login_url == "" || login_url == undefined){
            	var url = $("#login_form").attr("action");
            }else{
            	var url = login_url;
            }         
            //Linus报错
            redirct_url = redirct_url.replace("/index.php","");
            $.post(url, data, function(json) {
                if (json.result) {
                    window.location.href = redirct_url;
                } else {
                    $('.login_error').show();
                    $('.login_error').html(json.msg);
                }
            }, 'json');
        }
    }

}

/*
 * @param   确定删除时回调此方法
 * @author  Terry<wanghui@guanyisoft.com>
 * @date    2012-12-28
 */         
    function mDialogCallback(ra_id,pids){
        var url = "/Wap/My/doDelDeliver/";
        ajaxReturn(url,{'ra_id':ra_id,'pids':pids},'post');
    }

$('#set_names').validate({
        errorPlacement: function(error, element) {
        },
        showErrors: function(errors) {
            for (var name in errors) {
                alert(errors[name]);
                $('#' + name).parent('td').children('span').show();
                $('#' + name).parent('td').children('span').html(errors[name]);
            }

            return false;
        },
        onkeyup: false,
        onfocusout: false
    });
/**
 * 第三方授权登录设置用户名
 *
 * @author Joe <qianyijun@guanyisoft.com>
 * @date 2013-08-07
 */
function setThidUserName(){
    var res = $('#set_names').valid();
    if(res){
        var data = $('#edit_changePass').serialize();
        $.post('/Wap/My/setThdMembers',data,function(Msg){
            
        },'json');
    }
 
}
