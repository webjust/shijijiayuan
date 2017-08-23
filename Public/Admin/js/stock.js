 /***库存设置
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-04-23
   */
function oncheck(obj){
   var radio_id = $(":checkbox[name=Open][checked]").val();
   if(radio_id){
		$("#stock_set").show();
		$("#user_check").show();
		$("#user_type").show();
		$("#is_wat_stock").show();
   }else{
		$("#stock_set").hide();
		$("#user_check").hide();
		$("#user_type").hide();
		$("#is_wat_stock").hide();
   }  
}

function usercheck(obj){
   var user_type = $(":radio[name=user][checked]").val();
   if(user_type==1){
		$("#user_type").hide();
   }else{
		$("#user_type").show();
   }
}

function javascriptCheckBeforeSubmit(){
	$("#stock_num").next("span").html("").hide();
	//如果设置了使用模糊库存
	if($("#Open").attr("checked")){
		//判断边界值是否输入，且是否是数字
		if($("#stock_num").val() == ""){
			$("#stock_num").next("span").html("请输入一个临界值。").show();
			return false;
		}else if(isNaN($("#stock_num").val()) || $("#stock_num").val() <= 0){
			$("#stock_num").next("span").html("请输入一个大于0的数字。").show();
			return false;
		}
	}
	
	//如果选择了部分会员，则验证是否选中会员等级
	if($("#u101111").attr("checked")){
		var is_checked = false;
		$(".member_levels").each(function(){
			if(this.checked){
				is_checked = true;
			}
		});
                var Open = $("input[name='Open']:checked").val();
                if(Open == '1'){
                    if(false === is_checked){
			alert("请选择会员等级！");
			return false;
                    }   
                }
		
	}
	return true;
}