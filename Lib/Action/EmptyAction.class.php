<?php
class EmptyAction extends Action{
    function _empty(){
        layout(false);
        header("HTTP/1.0 404 Not Found");//使HTTP返回404状态码
        $this->display("./Tpl/404.html");
    }
 }