<?php

class SellerAction extends HomeAction {

    public function intro() {
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }

    public function apply(){
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }

    public function consentLetter(){
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }

    public function deposit(){
        header("location:/Home/Seller/apply.html");
        exit;
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }

    public function shopInformation(){
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }

    public function pendingReview(){
        $csrf = md5(uniqid(rand(), TRUE));  //生成token  
        $_SESSION['csrf'] = $csrf;  
        $this->assign('csrf',$csrf);
        $this->display();
    }
}

