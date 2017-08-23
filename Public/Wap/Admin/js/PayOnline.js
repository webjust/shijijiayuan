    /**
     * 向上移
     * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
     * @date 2013-8-1
     */
    function UpSequence(code) {  
        if ($("#" + code).prev().html() != null) {
            var checkedTR = $("#" + code).prev();
            checkedTR.insertAfter($("#" + code));
              
            var obj = $("#Sequence_" + code)  
            obj.val(parseInt(obj.val()) - 1);  

            var inputId = checkedTR.find("input[id^='Sequence_']");  
            inputId.val(parseInt(inputId.val()) + 1);   
        }
    } 

    /**
     * 向下移
     * @author Zhangjiasuo<Zhangjiasuo@guanyisoft.com>
     * @date 2013-8-1
     */
    function DownSequence(code) { 
        if ($("#" + code).next().html() != null) {
            var checkedTR = $("#" + code).next();
            checkedTR.insertBefore($("#" + code));
              
            var obj = $("#Sequence_" + code)  
            obj.val(parseInt(obj.val()) + 1);  

            var inputId = checkedTR.find("input[id^='Sequence_']");  
            inputId.val(parseInt(inputId.val()) - 1);  
        }
    }