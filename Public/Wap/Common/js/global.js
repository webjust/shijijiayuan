/*删除左右两端的空格*/
function trim(str){
    return str.replace(/(^\s*)|(\s*$)/g, "");
}
/*删除左边的空格*/
function ltrim(str){
    return str.replace(/(^\s*)/g,"");
}
/*删除右边的空格*/
function rtrim(str){
    return str.replace(/(\s*$)/g,"");
}
/*匹配电话号码*/
function telExp(str){
    var RegExp = /^(\(((010)|(021)|(0\d{3,4}))\)( ?)([0-9]{7,8}))|((010|021|0\d{3,4}))([- ]{1,2})([0-9]{7,8})$/;
    return RegExp.test(str);
}
/*匹配邮箱*/
function emailExp(str){
    var RegExp = /^[a-zA-Z0-9][a-zA-Z0-9._-]*\@[a-zA-Z0-9]+\.[a-zA-Z0-9\.]+$/;
    return RegExp.test(str);
}
/*匹配网址*/
function urlExp(str){
    var RegExp = /^(([a-zA-Z]+)(:\/\/))?([a-zA-Z]+)\.(\w+)\.([\w.]+)(\/([\w]+)\/?)*(\/[a-zA-Z0-9]+\.(\w+))*(\/([\w]+)\/?)*(\?(\w+=?[\w]*))*((&?\w+=?[\w]*))*$/;
    return RegExp.test(str);
}
/*匹配手机*/
function mobileExp(str){
    var RegExp = /^((13[0-9])|147|(15[0-35-9])|180|182|(18[5-9]))[0-9]{8}$/;
    return RegExp.test(str);
}
/*匹配QQ*/
function tencentExp(str){
    var RegExp = /^[1-9][0-9]{4,}$/;
    return RegExp.test(str);
}
/*匹配银行账户*/
function bankExp(str){
    var RegExp = /^(998801|998802|622525|622526|435744|435745|483536|528020|526855|622156|622155|356869|531659|622157|627066|627067|627068|627069)\d{10}$/;
    return RegExp.test(str);
}