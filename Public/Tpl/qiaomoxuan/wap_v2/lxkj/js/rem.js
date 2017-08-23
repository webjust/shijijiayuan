(function() {
	
	/*设计图为640 字体大小为40*/
	function changefontsize(){
			var _this=this;

			var html=document.getElementsByTagName('html')[0];

			var screenWitdh=_this.innerWidth;

			html.style.fontSize=(screenWitdh*0.125)/2+"px";
	}
	changefontsize();

	window.onresize=function(){
		changefontsize();
	}

})();