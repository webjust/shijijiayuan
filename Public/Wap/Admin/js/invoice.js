 /***发票设置
   * @author zhangjiasuo <zhangjiasuo@guanyisoft.com>
   * @date 2013-04-23
   */
   var rowCount =0
   function add_content(obj){
       var content = $("#invoice_content").val();
       var trHtml = $('#list').html();
       //var rowCount = $("#list div").length + parseInt(1);
       var del ='<a class="blue" onclick="del_content(this);" href="javascript:void(0);">删除</a>';
       if(content==''){
            return false;
       }
       trHtml +='<div><input id="u10" value="'+content+'" name="content[]" checked="true" type="checkbox"> '+ content +' '+ del +'</div>';
       $("#list").html(trHtml);
       $("#invoice_content").attr("value","");  
       
    }
    
    function del_content(obj){
       var rowCount = $("#list div").length;
       if(rowCount>1){
            $(obj).parent().remove(); 
       }else{
            alert("发票内容不能为空");
       }
    }
