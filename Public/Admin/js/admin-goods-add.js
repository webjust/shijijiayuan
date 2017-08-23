/** ********* 富文本编辑器和文件管理器集成 --开始 ********** */
UE.getEditor('goods_editor');
UE.getEditor('goods_editor2');
var editor = new UE.ui.Editor({
	imageRealPath:"goods"
});
editor.render("myEditor");
var dialog;
var image_input_id;
var image_input_id_s;
var gsd_image_input_id;
var  gsd_image_input_id_s;

editor.ready(function(){
	editor.hide()
	dialog = editor.getDialog("insertimage");
	editor.addListener('beforeInsertImage',function(t, arg){
	    
		image_input_id = image_input_id-1;
		for(index in arg){
		    if(typeof arg[index]['src']=='undefined')  continue;
			image_input_id = image_input_id + 1;
			var image_path = arg[index]['src'];
			if($("#picture_input_" + image_input_id)){
				$("#picture_input_" + image_input_id).val(image_path);
				$("#pic_img_src_" + image_input_id).attr({src:image_path});
			}
			//规格图片
			if($("#spec_image_input_"+gsd_image_input_id)){
				$("#spec_image_input_"+gsd_image_input_id).val(image_path);
				$("#spec_image_"+gsd_image_input_id).attr({src:image_path});
			}
		}
	});
	editor.addListener('catchremotesuccess',function(t, arg){
	       if($("#picture_input_" + image_input_id_s) && typeof arg[0]!='undefined'){
			    var image_path = arg[0];
				$("#picture_input_" + image_input_id_s).val(image_path);
				$("#pic_img_src_" + image_input_id_s).attr({src:image_path});
			}
		
	});
	editor.addListener('contentchange',function(){
		this.sync();
		//1.2.4+以后可以直接给textarea的id名字就行了
		$('#goods_editor').valid();
		$('#goods_editor2').valid();
	});
});

/**
 * 图片上传方法集成
 */
function upImage(imageId) {
	//editor.options.imageRealPath = 'desc';
	image_input_id = imageId;
	image_input_id_s = imageId;
	dialog.open();
}

function upgsdImage(gsdId) {
	//editor.options.imageRealPath = 'desc';
	gsd_image_input_id = gsdId;
	gsd_image_input_id_s = gsdId;
	dialog.open();
}
/** ******** 富文本编辑器和文件管理器集成 --结束 ********* */

/**
 * 页面DOM数据变更监听绑定
 * 表单验证触发
 */ 
$(document).ready(function(){
	//商品资料编辑页签切换
	$(".form_add_products_labels").click(function(){
		//所有隐藏
		$("#con_addGoods_1,#con_addGoods_2,#con_addGoods_3,#con_addGoods_4,#con_addGoods_5,#con_addGoods_6,#con_addGoods_7").hide();
		$(".form_add_products_labels").removeClass("onHover");
		//获取当前页签的ID
		$("#con_addGoods_" + $(this).attr("value_id")).show();
		$(this).addClass("onHover");
		return true;
	});
	
	//商品类型变更以后，自动更新商品的扩展属性输入框
	$("#goods_type_select").change(function(){
		loadGoodsUnsalesSpecForm();
		loadGoodsSaleSpecForm();
		return true;
	});
	
	//页面初始化的时候对表单中的内容进行初始化加载
	loadGoodsUnsalesSpecForm();
	loadGoodsSaleSpecForm();
	
	//商品分类的展开和收起处理
	$(".parent_cat_button").toggle(
		function(){
			$(this).removeClass("unfold").addClass("packup");
			var gc_id = $(this).attr("gc_id");
			$(".cat_list_li").each(function(){
				if(gc_id == $(this).attr("parent_id")){
					$(this).hide();
				}
			});
		},
		function(){
			$(this).removeClass("packup").addClass("unfold");
			var gc_id = $(this).attr("gc_id");
			$(".cat_list_li").each(function(){
				if(gc_id == $(this).attr("parent_id")){
					$(this).show();
				}
			});
		}
	);
	
	//是否启用积分兑换的控制
	$(".is_exchange").click(function(){
		if(this.checked && 1 == parseInt($(this).val())){
			$("#goods_info_point_tr").show();
		}else{
			$("#goods_info_point_tr").hide();
		}
	});
	
	//商品图片操作工具栏的显示控制
	$(".imagebox_li_classname").mouseover(function(){
		$(this).children("p").show();
	});
	$(".imagebox_li_classname").mouseout(function(){
		$(this).children("p").hide();
	});
	
	/**
	 * 商品图片的向左移动
	 */
	$(".images_tools_bar_left").click(function(){
		//获取当前这张图片的信息
		var image_id = parseInt($(this).attr("image_id"));
		//第一张图片是不允许左移的
		if(image_id > 0){
			var image_url = $("#picture_input_" + image_id).val();
			if("" == image_url){
				//如果此元素还没有被上传图片，则不允许操作
				return false;
			}
			//获取前一张图片的信息
			var pre_image_id = image_id-1;
			var pre_input_image_url = $("#picture_input_" + pre_image_id).val();
			var pre_image_url = PUBLIC_PATH + "/Admin/images/product_image_index.png";
			if(pre_image_id > 0){
				pre_image_url = PUBLIC_PATH + "/Admin/images/product_image_desc.png";
			}
			if(1 == image_id){
				pre_image_url = PUBLIC_PATH + "/Admin/images/product_image_desc.png";
			}
			if("" != pre_input_image_url){
				pre_image_url = pre_input_image_url;
			}
			//完成图片替换
			$("#picture_input_" + pre_image_id).val(image_url);
			$("#pic_img_src_" + pre_image_id).attr({src:image_url});
			//替换本身的图片
			$("#picture_input_" + image_id).val(pre_input_image_url);
			$("#pic_img_src_" + image_id).attr({src:pre_image_url});
		}
	});
	
	/**
	 * 商品图片向右移动
	 */
	$(".images_tools_bar_right").click(function(){
		//获取当前这张图片的信息
		var image_id = parseInt($(this).attr("image_id"));
		//最后一张图片是不允许向右移动的
		if(image_id < 9){
			var image_url = $("#picture_input_" + image_id).val();
			if("" == image_url){
				//如果此元素还没有被上传图片，则不允许操作
				return false;
			}
			//获取下一张图片的信息
			var next_image_id = image_id+1;
			var next_input_image_url = $("#picture_input_" + next_image_id).val();
			var next_image_url = PUBLIC_PATH + "/Admin/images/product_image_index.png";
			if(next_image_id > 0){
				next_image_url = PUBLIC_PATH + "/Admin/images/product_image_desc.png";
			}
			if(0 == image_id){
				next_image_url = PUBLIC_PATH + "/Admin/images/product_image_index.png";
			}
			if("" != next_input_image_url){
				next_image_url = next_input_image_url;
			}
			//完成图片替换
			$("#picture_input_" + next_image_id).val(image_url);
			$("#pic_img_src_" + next_image_id).attr({src:image_url});
			//替换本身的图片
			$("#picture_input_" + image_id).val(next_input_image_url);
			$("#pic_img_src_" + image_id).attr({src:next_image_url});
		}
	});
	
	/**
	 * 商品图片的删除
	 */
	$(".images_tools_bar_del").click(function(){
		//获取当前这张图片的信息
		var image_id = parseInt($(this).attr("image_id"));
		var image_input_url = $("#picture_input_" + image_id).val();
		if("" != image_input_url && confirm("确定要删除此图片吗？")){
			$("#picture_input_" + image_id).val("");
			var pic_url = PUBLIC_PATH + "/Admin/images/product_image_index.png";
			if(image_id > 0){
				pic_url = PUBLIC_PATH + "/Admin/images/product_image_desc.png";
			}
			$("#pic_img_src_" + image_id).attr({src:pic_url});
		}
	});
	
	//赠品属性发生变化
	$("#goods_gifts,#goods_gifts_2").click(function(){
		if(this.checked){
			if($(this).attr('id') == 'goods_gifts'){
				$("#goods_gifts_2").attr({checked:false});
			}else{
				$("#goods_gifts").attr({checked:false});
			}
		}
	});
	
	/**
	 * 会员等级固定价格输入框值发生变化
	 * 变化以后自动计算折合折扣
	 */
	$(".member_level_fixed_price").change(function(){
		var fixed_value = $(this).val();
		var discount = '无优惠折扣';
		
		//验证输入的值是否是数组，如果是非数字，则提示错误
		if("" == fixed_value){
			$(this).parent("td").next("td").html(discount);
			return false;
		}
		
		//如果输入的值是一个非数字
		if(isNaN(fixed_value)){
			$(this).val("").css({"border":'1px solid #ff0000'}).next("span").html("该项必须输入数字。");
		}
		
		//验证是否输入销售价格，如果没有输入销售价格或者销售价格不合法，则无法计算折扣
		var pdt_sale_price = $("#pdt_sale_price").val();
		if(pdt_sale_price == "" || isNaN(pdt_sale_price)){
			$(this).parent("td").next("td").html('请填写销售价');
			return false;
		}
		//根据销售价计算折扣
		var discount_percent = parseInt(fixed_value/pdt_sale_price*10000)/1000;
		discount = discount_percent + '折';
		$(this).parent("td").next("td").html(discount);
	});
	
	/**
	 * 当销售价发生变化时，会员等级折扣联动
	 * 必须当输入框失去光标时验证
	 */
	$("#pdt_sale_price").change(function(){
		//验证是否输入销售价格，如果没有输入销售价格或者销售价格不合法，则无法计算折扣
		var pdt_sale_price = $(this).val();
		if(pdt_sale_price == "" || isNaN(pdt_sale_price)){
			$(this).next("span").html('该项必须输入一个数字。');
			return false;
		}
		$(".member_level_fixed_price").each(function(){
			var fixed_value = $(this).val();
			var discount = '无优惠折扣';
			
			//验证输入的值是否是数组，如果是非数字，则提示错误
			if("" == fixed_value){
				$(this).parent("td").next("td").html(discount);
				return false;
			}
			
			//如果输入的值是一个非数字
			if(isNaN(fixed_value)){
				$(this).val("").css({"border":'1px solid #ff0000'}).next("span").html("该项必须输入数字。");
			}
			
			//根据销售价计算折扣
			var discount_percent = parseInt(fixed_value/pdt_sale_price*10000)/1000;
			discount = discount_percent + '折';
			$(this).parent("td").next("td").html(discount);
		});
	});
	
	/**
	 * 规格会员等级折扣固定价格发生变化
	 * 变化以后自动计算折合折扣
	 */
	$(".member-level-price-input").change(function(){
		var fixed_value = $(this).val();
		var discount = '无优惠折扣';
		
		//验证输入的值是否是数组，如果是非数字，则提示错误
		if("" == fixed_value){
			$(this).parent("td").next("td").html(discount);
			return false;
		}
		
		//如果输入的值是一个非数字
		if(isNaN(fixed_value)){
			$(this).val("").css({"border":'1px solid #ff0000'}).next("span").html("该项必须输入数字。");
		}
		
		//验证是否输入销售价格，如果没有输入销售价格或者销售价格不合法，则无法计算折扣
		var pdt_sale_price = $("#member-level-price-input-spec-price").val();
		if(pdt_sale_price == "" || isNaN(pdt_sale_price)){
			$(this).parent("td").next("td").html('请填写规格销售价');
			return false;
		}
		//根据销售价计算折扣
		var discount_percent = parseInt(fixed_value/pdt_sale_price*10000)/1000;
		discount = discount_percent + '折';
		$(this).parent("td").next("td").html(discount);
	});
});

/** 
 * 异步加载商品对应类型下的扩展属性
 * 此方法在商品类型onchange事件和document ready事件发生时触发调用
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function loadGoodsUnsalesSpecForm(){
	var type_id = $("#goods_type_select").val();
	$("#tbody_goods_spec_area_tr").hide();
	if(type_id > 0){
		$("#button_enable_spec").show();
		$.ajax({
			url:ajaxLoadUnsaleSpec_url,
			data:{type_id:type_id,def:1},
			beforeSend:function(){
				//alert("正在请求远端数据，请稍候...");
			},
			success:function(htmlObj){
				$("#tbody_goods_spec_area").empty().append(htmlObj).parent("tr").show();
			},
			type:'POST',
			timeout:30000,
			dataType:'html'
		});
	}
}

/**
 * 异步加载商品对应类型下的商品销售属性和属性值列表
 * 此方法会在页面载入完成和商品类型change事件发生时调用
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function loadGoodsSaleSpecForm(){
	var type_id = $("#goods_type_select").val();
	$("#tbody_goods_spec_area_tr").hide();
	if(type_id > 0){
		$.ajax({
			url:loadGoodsSaleSpecForm_url,
			data:{type_id:type_id},
			beforeSend:function(){
				//alert("正在请求远端数据，请稍候...");
			},
			success:function(htmlObj){
				$("#goods_sales_spec_select_area").empty().append(htmlObj);
			},
			type:'POST',
			timeout:30000,
			dataType:'html'
		});
	}
}

/**
 * 异步加载sku list 表单
 * 此方法用于页面上选中销售属性以后，
 * 点击“生成SKU”按钮以后，异步加载获取SKU列表。
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function createProductListForm(clickObj){
	var click_num = parseInt($(clickObj).attr("click_num"));
	if(click_num > 0 && !confirm("确定要重新计算规格组合吗？\n如果选择时，您输入的部分规格值可能需要您重新输入！")){
		return false;
	}
	var specinfo = "";
	$(".sale_spec_detail").each(function(){
		if(this.checked){
			specinfo += $(this).attr("pid") + ':' + $(this).attr("vid") + ';';
		}
	});
	if("" == specinfo){
		alert("请选择要生成SKU的规格参数值！");
		return false;
	}
	$.ajax({
		url:ajaxSkuLists_url,
		data:{specinfo:specinfo,sku_info:$(".sku_info").serializeArray(),def:1},
		beforeSend:function(){
			$(clickObj).attr({disabled:true,click_num:$(clickObj).attr("click_num")+1});
			$("#createMessageBox").html('正在重新计算规格，请稍候...');
		},
		success:function(htmlObj){
			$("#createMessageBox").html('');
			$(clickObj).attr({disabled:false}).html("重新生成");
			$("#goods_sku_list_form").empty().append(htmlObj).parent("tr").show();
		},
		type:'POST',
		timeout:30000,
		dataType:'html'
	});
}

/**
 * 商品资料表单提交 - 启用SKU时对SKU输入项的验证
 * 由于SKU输入项的页面较短，无法在表单项目的后面添加提示语
 * 所以这里采用的方法是，将输入项的表单变成红色
 * 并且以alert的方式输入提示信息，并且将页面页签切换到第一帧
 * 并且鼠标光标落入项目中
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function skuFromSubmitCheck(){
	var check_result = true;
	//验证SKU货号是否输入
	//$(".sku-list-info-rows .pdt_sn").each(function(){
	//	if("" == $(this).val()){
	//		check_result = false;
	//		$(this).css({"border":'1px solid #ff0000'});
	//	}
	//});
	//if(false == check_result){
	//	alert("您至少有一个规格没有输入货号。");
	//	return false;
	//}
	//验证SKU库存是否输入：该项为可选输入，如果输入，则必须是一个数字，否则值为0；
	$(".sku-list-info-rows .pdt_stock").each(function(){
		if("" != $(this).val() && isNaN($(this).val())){
			check_result = false;
			$(this).css({"border":'1px solid #ff0000'});
		}
	});
	if(false == check_result){
		alert("规格库存数量为可选参数但必须是数字：您至少有一项输入错误。");
		return false;
	}
	//验证SKU销售价是否输入：此参数为必填参数，且必须输入一个数字。
	$(".sku-list-info-rows .pdt_sale_price").each(function(){
		if("" == $(this).val() || isNaN($(this).val())){
			check_result = false;
			$(this).css({"border":'1px solid #ff0000'});
		}
	});
	if(false == check_result){
		alert("规格销售价不能为空且必须是数字：您至少有一项输入错误。");
		return false;
	}
	//验证SKU成本价是否输入：此值为可选，如果输入为非数字，则提示错误
	$(".sku-list-info-rows .pdt_cost_price").each(function(){
		if("" != $(this).val() && isNaN($(this).val())){
			check_result = false;
			$(this).css({"border":'1px solid #ff0000'});
		}
	});
	if(false == check_result){
		alert("规格成本价格为可选参数，但必须是数字：您至少有一项输入错误。");
		return false;
	}
	//验证SKU市场价是否输入：值是否合法
	$(".sku-list-info-rows .pdt_market_price").each(function(){
		if("" == $(this).val() || isNaN($(this).val())){
			check_result = false;
			$(this).css({"border":'1px solid #ff0000'});
		}
	});
	if(false == check_result){
		alert("规格市场价为必选参数且必须输入数字：您至少有一项输入错误。");
		return false;
	}
	//验证SKU重量值是否输入：可选，如果不输入，默认为0，如输入则必须是一个数字。
	$(".sku-list-info-rows .pdt_weight").each(function(){
		if("" == $(this).val() || isNaN($(this).val())){
			check_result = false;
			$(this).css({"border":'1px solid #ff0000'});
		}
	});
	if(false == check_result){
		alert("规格重量为可选参数但是必须输入数字：您至少有一项输入错误。");
		return false;
	}
	//ajax异步验证货号是否唯一
	check_result = ajaxCheckPdtSnUnique();
	if(false == check_result){
		alert("规格商品编码必须唯一：您至少有一个规格的商家编码与其他商品重复。");
		return false;
	}
	return check_result;
}

/**
 * 检查SKU的唯一性
 * 这里使用的方式是将所有的SKU货号POST方式提交到服务器端
 * 服务器端验证完成以后，返回一个json数组，对SKU SN 的唯一性验证结果
 */
function ajaxCheckPdtSnUnique(){
	var sn_uq_result = true;
	var pdt_sns = "";
	$(".sku-list-info-rows .pdt_sn").each(function(){
		pdt_sns += $(this).val() + "||||";
	});
	$.post(checkPdtSnUnique,{'pdt_sns':pdt_sns},function(jsonObj){
		if(jsonObj.status == false){
			$(".sku-list-info-rows .pdt_sn").each(function(){
				var pdt_sn = $(this).val();
				var pdt_input_obj = $(this);
				for(var pdt_id in jsonObj.data){
					if(pdt_sn == jsonObj.data[pdt_id]){
						pdt_input_obj.css({'border':'1px solid #ff0000'});
						sn_uq_result = false;
					}
				}
			});
		}
	},'json');
	return sn_uq_result;
}

/**
 * 商品添加表单充值
 * 由于商品添加表单重置时可能存在一些关联项目已经选中
 * 所以需要对取消掉商品类型的处理。
 * 这里的算法时强制浏览器刷新，reload一个表单到页面上
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function resetFrom(){
	if(confirm("确定要重置表单吗？")){
		var loction_href= resetFrom_url + "?t=" + Math.random()*1000;
		location.href= loction_href;
	}
}

/**
 * 使用javascript获取一个字符串的长度
 */
function getStringRealLength(str) {
    //获得字符串实际长度，中文2，英文1
    //要获得长度的字符串
    var realLength = 0, len = str.length, charCode = -1;
    for (var i = 0; i < len; i++) {
        charCode = str.charCodeAt(i);
        if (charCode >= 0 && charCode <= 128){
			realLength += 1;
		}else{
			realLength += 2;
		}
    }
    return realLength;
}

/**
 * 商品添加表单提交之前的javascript验证
 * 
 * @author Mihern
 * @date 2013-06-24
 * @version 1.0
 * @change log 1:
 * @change log 2:
 * @change log 3:
 */
function javascriptCheckBeforeSubmit(){
	//提交表单验证
	//验证商品名称的长度
	if("" == $("#g_name").val()){
		$("#g_name").parent("td").children(".g_name").html("商品标题必须输入。");
		$("#g_name").focus();
		return false;
	}
	
	//验证商品标题的程度
	var g_name_length = getStringRealLength($("#g_name").val());
	var max_length = 60;
	if($("g_name").attr("maxlength")){
		max_length = $("g_name").attr("maxlength");
	}
	if(g_name_length > max_length){
		var tmp_nums = g_name_length - max_length;
		$("#g_name").parent("td").children(".g_name").html("商品标题不能超过" + max_length + "个字符，您已超出" + tmp_nums + "个字符（一个中文占2个字符）");
		$("#g_name").focus();
	}
	
	//商品标题js验证通过
	$("#g_name").parent("td").children(".g_name").html("");
	
	//验证商家编码的唯一性
	//if("" == $("#g_sn").val()){
	//	$("#g_sn").parent("td").children(".g_sn").html("商品标题必须输入。");
	//	$("#g_sn").focus();
	//	return false;
	//}
	
	$("#g_tax_rate").parent("td").children(".g_tax_rate").html("");
	var trade_type=$("#goods_trade_type_select").val();
	if(trade_type==true){
		var g_tax_rate = $("#g_tax_rate").val();
		if(g_tax_rate!=''){
			if( g_tax_rate <=0 || g_tax_rate> 1 ){
				$("#g_tax_rate").parent("td").children(".g_tax_rate").html("商品税率输入不正确。");
				$("#g_tax_rate").focus();
				return false;
			}
		}else{
			$("#g_tax_rate").parent("td").children(".g_tax_rate").html("商品税率必须输入。");
			$("#g_tax_rate").focus();
			return false;
		}
	}
	
	//对商品资料是否启用规格进行分开验证
	if('none' == $(".disabled_goods_sale_spec_info").css("display")){
		/**
		 * 如果启用规格，则验证SKU LIST中的数据是否填写完整
		 * 需要对以下项目进行验证：
		 * 1. 货号是否输入：不能为空，且必须唯一
		 * 2. 商品库存数量必须输入一个数字且大于等于0
		 * 3. 商品销售价，必须输入一个数字且大约等于0
		 * 4. 成本价，必须输入一个数字且大于等于0
		 * 5. 市场价，必须输入一个数字且大于等于0
		 * 6. 重量必须输入，是一个大于等于0的数字
		 * -- 目前验证规则仅包含如上，后续增加请在这里写明备注
		 **/
		var check_result = skuFromSubmitCheck();
		if(false == check_result){
			return false;
		}
	}else{
		//如果不启用规格，则销售价，市场价，成本价，重量等数据必须输入
		//如果不启用规格，则首先删除掉可能存在于dom中的规格列表
		$(".sku-list-info-rows").remove();
		var sku_check = true;
		$(".disabled_goods_sale_spec_info input").each(function(){
			$(this).next('span').html('');
			if($(this).hasClass("not_null")){
				if("" == $(this).val()){
					var msg = "该项不能为空";
					if('undefined' != typeof($(this).attr("not_null")) && "" != $(this).attr("not_null")){
						msg = $(this).attr("not_null");
					}
					$(this).focus().next("span").html(msg);
					sku_check = false;
					return false;
				}
				if($(this).hasClass("input_number")){
					if(isNaN($(this).val())){
						var msg = "该项必须输入一个数字。";
						if('undefined' != typeof($(this).attr("input_number")) && "" != $(this).attr("input_number")){
							msg = $(this).attr("input_number");
						}
						$(this).focus().next("span").html(msg);
						sku_check = false;
						return false;
					}
				}
			}else{ 
				//可选不输入，如果输入则必须输入一个数字的情况
				//主要是针对会员等级价格的验证
				if($(this).hasClass("input_number")){
					if($(this).val() != "" && isNaN($(this).val())){
						var msg = "该项必须输入一个数字。";
						if('undefined' != typeof($(this).attr("input_number")) && "" != $(this).attr("input_number")){
							msg = $(this).attr("input_number");
						}
						$(this).focus().next("span").html(msg);
						sku_check = false;
						return false;
					}
				}
			}
		});
		if(sku_check == false){
			return false;
		}
	}

	//验证是否选择商品分类，至少选择一个商品分类
	var cat_check = false;
	$(".goods_category_checkbox").each(function(){
		if(this.checked){
			cat_check = true;
		}
	});
	if(false === cat_check){
		alert("请至少选择一个商品分类。");
		return false;
	}

	//验证是否输入商品主图
	if($("#picture_input_0").val() == ""){
		alert("请上传商品主图。");
		return false;
	}
	return true;
}

