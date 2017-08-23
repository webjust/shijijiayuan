/*
@Name : selectUi v1.0.0 formUi特殊表单自定义样式
@date: 2013-08-30
@ author: xxxDawei
@ email: zhangdw620@sina.com
*/
;(function(){
$.fn.formUi = function(setting){
	var $_this = $(this);
	var opts = $.extend({
			//select		
			type:'select',
			hdSelect : 'hdSelect',
			dtBox : 'dtBox',
			ddBox : 'ddBox',
			dlBox : 'dlBox',
			
			//radio
			hdRadio:'hdRadio',
			radioName:'radioName',
			radioP:'radioP',
			lbSpan:'lbSpan',
			callBack:function(){},
		},setting);
	
	opts.callBack();
if(opts.type=='select'){
	var hdSelect = $("<div><dl><dt><p></p><input readonly='readonly' type= 'hidden'/></dt><dd><ul></ul></dd></dl></div>").attr({'class':opts.hdSelect}).appendTo($_this);
	$_this.css({'position':'relative','z-index':opts.zIndex});
	var dtBox = hdSelect.find("dl>dt").attr({'class':'dtBox'}).css({'position':'relative','z-index':opts.zIndex});
	var ddBox= hdSelect.find("dl>dd").attr({'class':opts.ddBox}).css({'display':'none','position':'absolute','z-index':opts.zIndex});
	var dlBox = hdSelect.find("dl").attr({'class':'dlBox'}).css({'position':'relative','z-index':opts.zIndex});
	var ulList = hdSelect.find("dl>dd>ul").attr({'class':'ulList'});
	var optionList = $_this.find("option");
	var currentSelect = hdSelect.find("dl>dt>p").text($_this.find("option:selected").text());
	optionList.each(function(index){
			ulList.append('<li>'+$(this).text()+'</li>');
		})
	$_this.find("select").css({'display':'none'});
	dtBox.on('click',function(){
		if(ddBox.is(":hidden")){
			ddBox.show();
			}else {
			ddBox.hide();	
			}
	})
	ulList.find("li").each(function(index){
	var index = index;
	$(this).hover(function(){   
			$(this).addClass("hover");
		},function(){
			$(this).removeClass("hover");		
	})								
	$(this).click(function(){
		optionList.eq(index).attr('selected','selected').siblings().removeAttr('selected');	
		currentSelect.text($(this).text());
		ddBox.slideUp(200);
		})
	})
}//if select
else if((opts.type=='radio')||(opts.type=='checkbox')) {
	var hdRadio =$("<div></div>").attr({'class':opts.hdRadio}).appendTo($_this);
	hdRadio.siblings().hide();
	var iptRadio = $_this.find('input[name='+opts.radioName+']');
	iptRadio.each(function(index){			   
	var radioP = $('<p class='+opts.radioP+'><span class='+opts.lbSpan+'>'+$(this).next(':not(input[name='+opts.radioName+'])').attr('class',opts.lbSpan).text()+'</span></p>').appendTo(hdRadio);
		});
	var radioPlist = $_this.find('.'+opts.radioP);
	radioPlist.each(function(index){
		var index = index;
		if(iptRadio.eq(index).attr('checked')){
			$(this).addClass("current");
			};
			if(opts.type=='radio'){
			$(this).on('click',function(){	
				var index = $(this).index();
				$(this).addClass("current");	
				$(this).siblings().removeClass('current');
				iptRadio.eq(index).attr('checked','checked').siblings().removeAttr('checked');
			})
			}else {
			$(this).on('click',function(){	
				var index = $(this).index();
				if(!($(this).hasClass('current'))){
						$(this).addClass("current");	
						iptRadio.eq(index).attr('checked','checked');
					}else {
						$(this).removeClass("current");
						iptRadio.eq(index).removeAttr('checked');
					}
			})
			}
		})
	}else{
		alert("请正确选择要美化的表单类型！");
		}
	}   
})();