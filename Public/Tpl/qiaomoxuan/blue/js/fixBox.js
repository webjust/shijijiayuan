/*
@ Name : fixBox v1.0.0 页面元素锁定
@ date: 2013-12-07
@ author: xxxDawei
@ email: zhangdw620@sina.com
@ blog: http://blog.sina.com.cn/u/1832879114
*/
;(function(){
	$.fn.fixedBox = function(setting){
	var _this = $(this);
	var opts = $.extend({
		id:'',
		zIndex:'',
		setEvent:'auto',//auto||scroll 两种类型，auto是页面载入以后就执行素锁定事件，scroll是根据boxTop的距离来执行元素锁定事件
		parentObj : '.fixedBox',//设定了被锁定元素的框架，防止运行以后产生空缺位置后位移
		left:'auto',//被锁定元素距上一级相对定位元素的左边距
		right:'auto',//被锁定元素距上一级相对定位元素的右边距
		bottom:'auto',//被锁定元素距离浏览器窗口的底部距离
		top: 'auto',//被锁定元素块距离浏览器窗口的顶部距离
		boxT:''//滚动条滚动多少，来出发锁定元素事件（滚动条滚动多少以后这个块就被锁定）
	},setting);
	//获取要锁定Box的位置
	var boxT = _this.offset().top; 
	var boxL = _this.offset().left;
	var boxH = _this.height(); 
	var boxW =_this.width(); 
	var winH = $(window).height();
	var winW = $(window).width();
	//判断浏览器的版本
	window.sys = {};//保存浏览器信息，让外部可以使用
	var ua = navigator.userAgent.toLowerCase(); //获取浏览器的版本信息
	var s;
	(s = ua.match(/msie ([\d.]+)/)) ? sys.ie = s[1]:
	(s = ua.match(/firefox\/([\d.]+)/)) ? sys.firefox = s[1]:
	(s = ua.match(/chrome\/([\d.]+)/)) ? sys.chrome = s[1]:
	(s = ua.match(/opera\/.*version\/([\d.]+)/)) ?sys.opera = s[1]:
	(s = ua.match(/version\/([\d.]+).*safari/)) ? sys.safari = s[1]:0;
	//_this.css({'width':boxW,'height':boxH});
	_this.parent(opts.parentObj).css({'height':boxH,'width':boxW});
		if(sys.ie=='6.0'){
			$('html').css({'backgroundImage':'url(about:blank)','backgroundAttachment':'fixed'});
			var ie6class= 'ie6Fixed_'+opts.id;
			var ie6style = '<style type="text/css">.'+ie6class+'{position:absolute;';
			if(opts.setEvent=='scroll'){
				if(Number(opts.top)||opts.top==0){
					ie6style+='top:expression(eval(document.documentElement.scrollTop+'+opts.top+'));}</style>;';	
					}else {
					ie6style+='top:expression(eval(document.documentElement.scrollTop+'+0+'));}</style>;';	
				}
				$(ie6style).appendTo('head');
				wheelIe6(ie6class);
				$(window).scroll(function(){wheelIe6(ie6class)});
			}else if(opts.setEvent=='auto') {
				autorunIe6(ie6style);
			}
		}else {	 
			if(opts.setEvent=='scroll'){
				if(Number(opts.top)||opts.top==0) {
					var ctop = opts.top;
					//_this.css({'top':ctop});
					$(window).scroll(function(){wheel(ctop);});	
				}else{
					//var ctop=boxT
					//_this.css({'top':ctop});
					$(window).scroll(function(){wheel(0);});
				}
			}else if(opts.setEvent=='auto') {
				autorun();
				$(window).resize(function(){autorun();})
			}
		};	
		
	function autorun(){
		var halfW = ($(window).width()-_this.width())/2;
		var halfH = ($(window).height()-_this.height())/2;
		_this.css({'position':'fixed','top':opts.top,'left':opts.left,'bottom':opts.bottom,'right':opts.right});	
		if((opts.left!='auto')&&opts.right!='center'){if(Number(opts.left)||opts.left==0){_this.css({'left':opts.left});};};
		if((opts.right!='auto')&&opts.right!='center'){if(Number(opts.right)||opts.right==0){_this.css({'right':opts.right});};};
		if((opts.top!='auto')&&opts.top!='center'){if(Number(opts.top)||opts.top==0){_this.css({'top':opts.top});};};
		if((opts.bottom!='auto')&&opts.bottom!='center'){if(Number(opts.bottom)||opts.bottom==0){_this.css({'bottom':opts.bottom});};};
		if(opts.top=='center'||opts.bottom=='center'){_this.css({'top':halfH});};
		if(opts.left=='center'||opts.right=='center'){_this.css({'left':halfW});};
		};
	function autorunIe6(ie6style){
			if(opts.top=='auto'){
				ie6style+=topChk();//对top值进行判断
				ie6style+=leftChk();//对left值进行判断
			}else if(opts.top=='center'){
				ie6style+= 'top:expression(eval(document.documentElement.scrollTop+(document.documentElement.clientHeight-this.offsetHeight)/2-(parseInt(this.currentStyle.marginTop,10)||0)-(parseInt(this.currentStyle.marginBottom,10)||0)));';	
				ie6style+=leftChk();
			}else if(Number(opts.top)||Number(opts.top)==0) {
				ie6style+='top:expression(eval(document.documentElement.scrollTop+'+opts.top+'));';
				ie6style+=leftChk();
			}else {alert("top/bottom只能一个为auto属性并且其中有一个为数值或者center");return false;};
				$(ie6style).appendTo('head');
				_this.addClass(ie6class);
	}	
	function topChk() {
		if(Number(opts.bottom)||Number(opts.bottom)==0){
			return'top:expression(eval(document.documentElement.scrollTop+document.documentElement.clientHeight-this.offsetHeight-(parseInt(this.currentStyle.marginTop,10)||0)-(parseInt(this.currentStyle.marginBottom,'+(10+Number(opts.bottom))+')||0)));'
		}else if (opts.bottom=='center') {
			return 'top:expression(eval(document.documentElement.scrollTop+(document.documentElement.clientHeight-this.offsetHeight)/2-(parseInt(this.currentStyle.marginTop,10)||0)-(parseInt(this.currentStyle.marginBottom,10)||0)));'
		}else{alert("top/bottom只能一个为auto属性,并且其中有一个为数值或center");return false;}
	}
	function leftChk(){
		if(opts.left=='auto'){
			if(Number(opts.right)||Number(opts.right)==0) {
				return 'left:expression(eval(document.documentElement.scrollLeft+document.documentElement.clientWidth-this.offsetWidth)-(parseInt(this.currentStyle.marginLeft,10)||0)-(parseInt(this.currentStyle.marginRight,'+(10+Number(opts.right))+')||0));}</style>';
			}else if(opts.right=='center') {
				return 'left:expression(eval((document.documentElement.scrollLeft+document.documentElement.clientWidth-this.offsetWidth)-(parseInt(this.currentStyle.marginLeft,10)||0)-(parseInt(this.currentStyle.marginRight,10)||0))/2);}</style>';
			}else if(opts.right=='auto'){
				return 'left:auto</style>';
		}else{alert("left/right值为非法字符！");return false;}
		}else if(Number(opts.left)||Number(opts.left)==0){
			return ie6style1='left:'+Number(opts.left)+'));}</style>';
		}else if(opts.left=='center'){
			return'left:expression(eval((document.documentElement.scrollLeft+document.documentElement.clientWidth-this.offsetWidth)-(parseInt(this.currentStyle.marginLeft,10)||0)-(parseInt(this.currentStyle.marginRight,10)||0))/2);}</style>';
		}else{alert("left/right值为非法字符"); return false;}
	}
	function wheel(ctop){
		var ctop = ctop;
		if(Number(opts.boxT)){
			wheelAdd(Number(opts.boxT),ctop);
		}else{
			wheelAdd(boxT,ctop);
		}
	}
	function wheelAdd(value,ctop){
		if( $(this).scrollTop()>value){
			if(_this.css('position')!='fixed'){
			_this.css({'position':'fixed','top':ctop});
			}
		}else{
			if(_this.css('position')!='relative'){
			_this.css({'position':'relative','top':0});
			}
		};
	}
	function wheelIe6(ie6class){
		if(!Number(opts.boxT)){
			wheelIe6Addclass(boxT);
		}else{
			wheelIe6Addclass(Number(opts.boxT));
		}
	}

	function wheelIe6Addclass(value){
		if (($(this).scrollTop()>=value)){
				if(!_this.hasClass(ie6class)){
					_this.addClass(ie6class);
				}else {
					_this.addClass(ie6class);
				}
			}else {
				_this.removeClass(ie6class);
		}
	}
}
})();