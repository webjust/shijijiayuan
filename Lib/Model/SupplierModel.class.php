<?php

class SupplierModel extends GyfxModel{
	   
    public function supplierSave($data){
        if(!empty($data)){
            $supplier = M('supplier')->data($data)->add();
            //echo M('supplier')->getLastSql();
            if($supplier){
                return true;
            }else{
                return false;
            }
        }
    }

    public function getSupplierinfo($m_id){
        $ary_member = M('supplier')->where("m_id='$m_id'")->find();
        // print_r($ary_member);
        return $ary_member;
    }
	
}