// JavaScript Document
// JavaScript Document


//导航滑过下拉
function hoverFunc(obj, className) {
    obj.hover(
        function () {
            $(this).addClass(className);
        }, function () {
            $(this).removeClass(className);
        }
    )
}


//标签切换
function tagChange(opts) {
    var opts = opts ? opts :{};
    var dftIdx = opts.defaultIdx ? opts.defaultIdx : '0' ;
    var curCls = opts.currentClass ? opts.currentClass : 'reg' ;
    var evt = opts.et ? opts.et : 'click' ;
    var tagObj = opts.tagObj;
    var tagCon = opts.tagCon;
    tagObj.eq(dftIdx).addClass(curCls).siblings().removeClass(curCls);
    tagCon.eq(dftIdx).show().siblings().hide();
 

	 if(evt == 'click') {
        tagObj[evt](function(){
            var idx = $(this).index();	
            $(this).addClass(curCls).siblings().removeClass(curCls);
            tagCon.eq(idx).show().siblings().hide();
        })
    }

}