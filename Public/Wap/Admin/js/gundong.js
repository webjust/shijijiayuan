var TencntART=new Object();
TencntART.Browser=
{
	ie:/msie/.test(window.navigator.userAgent.toLowerCase()),
	moz:/gecko/.test(window.navigator.userAgent.toLowerCase()),
	opera:/opera/.test(window.navigator.userAgent.toLowerCase()),
	safari:/safari/.test(window.navigator.userAgent.toLowerCase())
};
TencntART.JsLoader=
{
	load:function(sUrl,fCallback)
	{
		var _script=document.createElement('script');
		_script.setAttribute('charset','gb2312');
		_script.setAttribute('type','text/javascript');
		_script.setAttribute('src',sUrl);
		document.getElementsByTagName('head')[0].appendChild(_script);
		if(TencntART.Browser.ie)
		{
			_script.onreadystatechange=function()
			{
				if(this.readyState=='loaded'||this.readyStaate=='complete')
				{
					fCallback();
				}
			};
		}else if(TencntART.Browser.moz)
		{
			_script.onload=function()
			{
				fCallback();
			};
		}else
		{
			fCallback();
		}
	}
};
var GYArticl=new Object();
GYArticl=
{
	$:function(v){return document.getElementById(v)},
	getEles:function(id,ele)
	{	
		
		 return this.$(id).getElementsByTagName(ele);
		// return document.getElementById(id).getElementsByTagName(ele);
	},
	tabId:"sildPicBar",
	tabDot:"dot",
	tabBox:"shortcutCont",
	tabSilder:"shortcutList",
	tabSilderSon:"li",
	comtList:"ComList",
	rightBorder:"silidBarBorder",
	Count:function()
	{
		return this.getEles(this.tabSilder,this.tabSilderSon).length
	 },
	 Now:0,
	 isCmt:true,
	 isSild:true,
	 timer:null,
	 site:'news',
	 cmtId:'21572303',
	 cmtBase:'comment5',
	 sideTab:
	 {
		 heads:'tabTit',heads_ele:'span',bodys:'tabBody',bodys_ele:'ol'
	 },
	 SildTab:function(now)
	 {
		 this.Now=Number(now);
		 if(this.Now>Math.ceil(this.Count()/3)-1)
		 {
			 this.Now=0;
		 }else if(this.Now<0)
		 {
			 this.Now=Math.ceil(this.Count()/3)-1;
		 }
		 
		if(parseInt(this.$(this.tabSilder).style.left)>-165*parseInt(this.Now*1))
		{
			this.moveR();
		}else
		{
			this.moveL();
		}
		for(var i=0;i<Math.ceil(this.Count()/3);i++)
		{
			if(i==this.Now)
			{
				this.getEles(this.tabId,"li")[this.Now].className="select";
			}else
			{
				
				
				this.getEles(this.tabId,"li")[i].className="";
			}
		}
	},
	moveR:function(setp)
	{
		var _curLeft=parseInt(this.$(this.tabSilder).style.left);
		var _distance=105;
		if(_curLeft>-165*parseInt(this.Now*1))
		{
			this.$(this.tabSilder).style.left=(_curLeft-_distance)+15+"px";
			window.setTimeout("GYArticl.moveR()",1);
		}
	},
	moveL:function(setp)
	{
		var _curLeft=parseInt(this.$(this.tabSilder).style.left);
		var _distance=75;
		if(_curLeft<-165*parseInt(this.Now*1))
		{
			this.$(this.tabSilder).style.left=(_curLeft+_distance)-15+"px";
			window.setTimeout("GYArticl.moveL()",1);
		}
	},
	pagePe:function(way)
	{
		if(way=="next")
		{
			this.Now+=1;
			this.SildTab(this.Now);
		}else
		{
			this.Now-=1;this.SildTab(this.Now);
		}
	},
	smallCk:function()
	{
		for(var i=0;i<Math.ceil(this.Count()/3);i++)
		{
			if(i==0)
			{
				this.$(this.tabDot).innerHTML+="<li class='select' onclick='GYArticl.SildTab("+i+")'></li>";
			}else
			{
				this.$(this.tabDot).innerHTML+="<li onclick='GYArticl.SildTab("+i+")'></li>";
			}
		}
	},
	TabChang:function()
	{
		var eles=this.getEles(this.sideTab.heads,this.sideTab.heads_ele);
		var body=this.getEles(this.sideTab.bodys,this.sideTab.bodys_ele);
		for(var i=0;i<eles.length;i++)
		{
			(function()
				  {
					  var p=i;eles[p].onmouseover=function(){
						  	TencentArticl._TabChang(p,body,eles);
					   }
				  }
			)();
		}
	},
	_TabChang:function(n,body,obj)
	{
		for(var i=0;i<body.length;i++)
		{
			if(i==n)
			{
				body[n].className="block";obj[n].className="select";
			}else
			{
				body[i].className="none";obj[i].className="";
			}
		}
	},
	ComList:function()
	{
		/*TencntART.JsLoader.load('http://sum.comment.gtimg.com.cn/php_qqcom/gsum.php?site='+
		TencentArticl.site+'&c_id='+TencentArticl.cmtId+'',
			function()
			{
				
				setTimeout(SildTab(1),0);
			}
		);*/
		// setTimeout(GYArticl.pagePe("next"),0);
		// GYArticl.pagePe("next");
	},
	
	onload:function()
	{
		/**
		 如果是FireFox浏览器
		**/
		if(TencntART.Browser.moz)
		{
			document.addEventListener("DOMContentLoaded",
				function()
				{
					GYArticl.ints();
					setInterval("GYArticl.pagePe('next')",80000000);
				},
			null);
		}else
		{
			if(document.readyState=="complete")
			{
				GYArticl.ints();
				setInterval("GYArticl.pagePe('next')",80000000);
			}else
			{
				document.onreadystatechange=function()
				{
					if(document.readyState=="complete")
					{
						GYArticl.ints();
						setInterval("GYArticl.pagePe('next')",80000000);
					
					}
				}
			}
		}
	},
	ints:function()
	{
		if(this.isSild)
		{
			this.$(this.tabBox).style.position="relative";
			this.$(this.tabSilder).style.position="absolute";
			this.$(this.tabSilder).style.left=0+"px";
			this.getEles(this.tabId,"span")[1].onclick=function(){GYArticl.pagePe("next");}
			this.getEles(this.tabId,"span")[0].onclick=function(){GYArticl.pagePe("pre");}
			this.smallCk();
		}
		if(this.isCmt)
		{
			this.ComList();
			
		}
		
	}
}
Object.beget=function(o){var F=function(){};F.prototype=o;return new F();}