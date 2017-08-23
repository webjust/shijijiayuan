<?php
class GoodsAction extends GyfxAction {

	public function index(){
		$this->display();
	}

	public function AutoMatchV3(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = trim($_GET['brand']);

    	$M = M('');
    	$sql = 'select * from fx_in_goods where `brand_en` like "%'.$brand.'%"';
    	$goods = $M->query($sql);

    	
    	foreach ($goods as $k1 => $v1) {
    		$v1['unit'] = str_replace('ml', '', $v1['unit']);
    		$v1['unit'] = str_replace('g', '', $v1['unit']);
			$where = 'brand_en like "%'.$brand.'%"';
    		if($v1['main_class']||$v1['class1']||$v1['class2']||$v1['class3']){
    			$wherec = ' and (0';
	    		$class = $v1['main_class'].','.$v1['class1'].','.$v1['class2'].','.$v1['class3'];
	    		$c = explode(',',$class);
	    		foreach ($c as $value) {
	    			if($value){
	    				$wherec.=' or name_cn like "%'.trim($value).'%"';
	    			}
				}
				$wherec.=')';   			
    		}

    		if($v1['nomenclature']){
    			$wheren = ' and name_cn like "%'.trim($v1['nomenclature']).'%"';
    		}

    		if($v1['material']){
    			$wherem= ' and name_cn like "%'.trim($v1['material']).'%"';
    		}
    		if($wherem&&$wheren&&$wherec){
    			$this->AutoMatchSql($v1,$where.$wherem.$wheren.$wherec,'mnc_in_id');
    		}
    		if($wherem&&$wheren){
    			$this->AutoMatchSql($v1,$where.$wherem.$wheren,'mn_in_id');
    		}
    		if($wherem&&$wherec){
				$this->AutoMatchSql($v1,$where.$wherem.$wherec,'mc_in_id');
    		}
    		if($wheren&&$wherec){
				$this->AutoMatchSql($v1,$where.$wheren.$wherec,'nc_in_id');
    		}
    		if($wherem){
    			$this->AutoMatchSql($v1,$where.$wherem,'m_in_id');
    		}
    		if($wheren){
    			$this->AutoMatchSql($v1,$where.$wheren,'n_in_id');
    		}
    		if($wherec){
    			$this->AutoMatchSql($v1,$where.$wherec,'c_in_id');
    		}
    		
    	}
	}

    public function AutoMatch(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = trim($_GET['brand']);

    	$M = M('');
    	//$sql = 'select * from fx_in_goods_supplier where brand_en like "%'.$brand.'%"';
    	//$supplier = $M->query($sql);

    	$sql = 'select * from fx_in_goods where `brand_en` like "%'.$brand.'%"';
    	$goods = $M->query($sql);

    	foreach ($goods as $k1 => $v1) {
    		$v1['unit'] = str_replace('ml', '', $v1['unit']);
    		$v1['unit'] = str_replace('g', '', $v1['unit']);

    		$where = 'brand_en like "%'.$brand.'%"';
    		if($v1['nomenclature']){
    			$where1 = $where.' and name_cn like "%'.$v1['nomenclature'].'%"';
    			if($v1['material']){
    				$where2 = $where1.' and name_cn like "%'.$v1['material'].'%"';
					if($v1['main_class']){
						if(strpos($v1['main_class'], ',')){
							$c = explode(',',$v1['main_class']);
							$where3 = $where2.' and (0';
							foreach ($c as $value) {
								$where3.=' or name_cn like "%'.$value.'%"';
							}
							$where3.=')';
						}
						else{
							$where3 = $where2.' and name_cn like "%'.$v1['main_class'].'%"';
						}
						
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class1'].'%"';
							}							
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
							}
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where4);
						}				
						$this->AutoMatchSql($v1,$where3);
					}
					else{
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							}
							if($v1['class2']){
								if(strpos($v1['class2'], ',')){
									$c = explode(',',$v1['class2']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}				
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where3);
						}
						$this->AutoMatchSql($v1,$where2); 						
					}			
    				
    			}
    			else{
					if($v1['main_class']){
						if(strpos($v1['main_class'], ',')){
							$c = explode(',',$v1['main_class']);
							$where2 = $where1.' and (0';
							foreach ($c as $value) {
								$where2.=' or name_cn like "%'.$value.'%"';
							}
							$where2.=')';
						}
						else{
							$where2 = $where1.' and name_cn like "%'.$v1['main_class'].'%"';
						}						
						$this->AutoMatchSql($v1,$where2);
					}
					else{
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where2 = $where1.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where1.' and name_cn like "%'.$v1['class1'].'%"';
							}
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where2 = $where1.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where1.' and name_cn like "%'.$v1['class2'].'%"';
							}
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where2 = $where1.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where1.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where2);
						}  						
					}  				
    			}
    		}
    		else{
				if($v1['material']){
					$where2 = $where.' and name_cn like "%'.$v1['material'].'%"';
					if($v1['main_class']){
						if(strpos($v1['main_class'], ',')){
							$c = explode(',',$v1['main_class']);
							$where3 = $where2.' and (0';
							foreach ($c as $value) {
								$where3.=' or name_cn like "%'.$value.'%"';
							}
							$where3.=')';
						}
						else{
							$where3 = $where2.' and name_cn like "%'.$v1['main_class'].'%"';
						}
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class1'].'%"';
							}							
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
							}
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where4 = $where3.' and (0';
								foreach ($c as $value) {
									$where4.=' or name_cn like "%'.$value.'%"';
								}
								$where4.=')';
							}
							else{
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where4);
						}				
						$this->AutoMatchSql($v1,$where3);
					}
					else{
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							}
							if($v1['class2']){
								if(strpos($v1['class2'], ',')){
									$c = explode(',',$v1['class2']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}				
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where4 = $where3.' and (0';
									foreach ($c as $value) {
										$where4.=' or name_cn like "%'.$value.'%"';
									}
									$where4.=')';
								}
								else{
									$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where4);
							}
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where3);
						}
						$this->AutoMatchSql($v1,$where2);						
					}  				
					
				}
	    		else{
					if($v1['main_class']){
						if(strpos($v1['main_class'], ',')){
							$c = explode(',',$v1['main_class']);
							$where2 = $where.' and (0';
							foreach ($c as $value) {
								$where2.=' or name_cn like "%'.$value.'%"';
							}
							$where2.=')';
						}
						else{
							$where2 = $where.' and name_cn like "%'.$v1['main_class'].'%"';
						}
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							}							
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							}
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where3 = $where2.' and (0';
								foreach ($c as $value) {
									$where3.=' or name_cn like "%'.$value.'%"';
								}
								$where3.=')';
							}
							else{
								$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where3);
						}				
						$this->AutoMatchSql($v1,$where2);
					}
					else{
						if($v1['class1']){
							if(strpos($v1['class1'], ',')){
								$c = explode(',',$v1['class1']);
								$where2 = $where.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where.' and name_cn like "%'.$v1['class1'].'%"';
							}

							if($v1['class2']){
								if(strpos($v1['class2'], ',')){
									$c = explode(',',$v1['class2']);
									$where3 = $where2.' and (0';
									foreach ($c as $value) {
										$where3.=' or name_cn like "%'.$value.'%"';
									}
									$where3.=')';
								}
								else{
									$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
								}

								$this->AutoMatchSql($v1,$where3);
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where3 = $where2.' and (0';
									foreach ($c as $value) {
										$where3.=' or name_cn like "%'.$value.'%"';
									}
									$where3.=')';
								}
								else{
									$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where3);
							}				
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class2']){
							if(strpos($v1['class2'], ',')){
								$c = explode(',',$v1['class2']);
								$where2 = $where.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where.' and name_cn like "%'.$v1['class2'].'%"';
							}
							if($v1['class3']){
								if(strpos($v1['class3'], ',')){
									$c = explode(',',$v1['class3']);
									$where3 = $where2.' and (0';
									foreach ($c as $value) {
										$where3.=' or name_cn like "%'.$value.'%"';
									}
									$where3.=')';
								}
								else{
									$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
								}
								$this->AutoMatchSql($v1,$where3);
							}
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class3']){
							if(strpos($v1['class3'], ',')){
								$c = explode(',',$v1['class3']);
								$where2 = $where.' and (0';
								foreach ($c as $value) {
									$where2.=' or name_cn like "%'.$value.'%"';
								}
								$where2.=')';
							}
							else{
								$where2 = $where.' and name_cn like "%'.$v1['class3'].'%"';
							}
							$this->AutoMatchSql($v1,$where2);
						}						
					}    			
	    		}    			
    		}
    	}
    }


    public function AutoMatchlast(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = trim($_GET['brand']);

    	$M = M('');
    	//$sql = 'select * from fx_in_goods_supplier where brand_en like "%'.$brand.'%"';
    	//$supplier = $M->query($sql);

    	$sql = 'select * from fx_in_goods where `brand_en` like "%'.$brand.'%"';
    	$goods = $M->query($sql);

    	foreach ($goods as $k1 => $v1) {
    		$v1['unit'] = str_replace('ml', '', $v1['unit']);
    		$v1['unit'] = str_replace('g', '', $v1['unit']);

    		$where = 'brand_en like "%'.$brand.'%"';
    		if($v1['nomenclature']){
    			$where1 = $where.' and name_cn like "%'.$v1['nomenclature'].'%"';
    			if($v1['material']){
    				$where2 = $where1.' and name_cn like "%'.$v1['material'].'%"';
					if($v1['main_class']){
						$where3 = $where2.' and name_cn like "%'.$v1['main_class'].'%"';
						if($v1['class1']){
							$where4 = $where3.' and name_cn like "%'.$v1['class1'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class2']){
							$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class3']){
							$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}				
						$this->AutoMatchSql($v1,$where3);
					}
					else{
						if($v1['class1']){
							$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							if($v1['class2']){
								$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}
							if($v1['class3']){
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}				
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							if($v1['class3']){
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where3);
						}
						$this->AutoMatchSql($v1,$where2); 						
					}			
    				
    			}
    			else{
					if($v1['main_class']){
						$where2 = $where1.' and name_cn like "%'.$v1['main_class'].'%"';
						$this->AutoMatchSql($v1,$where2);
					}
					else{
						if($v1['class1']){
							$where2 = $where1.' and name_cn like "%'.$v1['class1'].'%"';
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class2']){
							$where2 = $where1.' and name_cn like "%'.$v1['class2'].'%"';
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class3']){
							$where2 = $where1.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where2);
						}  						
					}  				
    			}
    		}
    		else{
				if($v1['material']){
					$where2 = $where.' and name_cn like "%'.$v1['material'].'%"';
					if($v1['main_class']){
						$where3 = $where2.' and name_cn like "%'.$v1['main_class'].'%"';
						if($v1['class1']){
							$where4 = $where3.' and name_cn like "%'.$v1['class1'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class2']){
							$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}
						if($v1['class3']){
							$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where4);
						}				
						$this->AutoMatchSql($v1,$where3);
					}
					else{
						if($v1['class1']){
							$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							if($v1['class2']){
								$where4 = $where3.' and name_cn like "%'.$v1['class2'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}
							if($v1['class3']){
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}				
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							if($v1['class3']){
								$where4 = $where3.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where4);
							}
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where3);
						}
						$this->AutoMatchSql($v1,$where2);						
					}
    				
					
				}
	    		else{
					if($v1['main_class']){
						$where2 = $where.' and name_cn like "%'.$v1['main_class'].'%"';
						if($v1['class1']){
							$where3 = $where2.' and name_cn like "%'.$v1['class1'].'%"';
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class2']){
							$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
							$this->AutoMatchSql($v1,$where3);
						}
						if($v1['class3']){
							$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where3);
						}				
						$this->AutoMatchSql($v1,$where2);
					}
					else{
						if($v1['class1']){
							$where2 = $where.' and name_cn like "%'.$v1['class1'].'%"';
							if($v1['class2']){
								$where3 = $where2.' and name_cn like "%'.$v1['class2'].'%"';
								$this->AutoMatchSql($v1,$where3);
							}
							if($v1['class3']){
								$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where3);
							}				
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class2']){
							$where2 = $where.' and name_cn like "%'.$v1['class2'].'%"';
							if($v1['class3']){
								$where3 = $where2.' and name_cn like "%'.$v1['class3'].'%"';
								$this->AutoMatchSql($v1,$where3);
							}
							$this->AutoMatchSql($v1,$where2);
						}
						if($v1['class3']){
							$where2 = $where.' and name_cn like "%'.$v1['class3'].'%"';
							$this->AutoMatchSql($v1,$where2);
						}						
					}    			
	    		}    			
    		}
    	}
    }

    private function AutoMatchSql($v1,$where,$id='in_id'){
    	$M = M('');
		//$sql = 'select * from fx_in_goods_supplier where '.$where.' and (in_id=0 or in_id is null)';
		$sql = 'select * from fx_in_goods_supplier where '.$where;

		echo $sql.'<br>';
		$supplier = $M->query($sql);
		if($supplier){
			foreach ($supplier as $k2 => $v2) {
				$v2['unit'] = str_replace('ml', '', $v2['unit']);
				$v2['unit'] = str_replace('g', '', $v2['unit']);
				if($v1['unit']==$v2['unit']){
 		    		if($v1['tmall_price']&&$v1['jumei_price']){
		    			$TJ_price = $v1['tmall_price']>$v1['jumei_price']?$v1['jumei_price']:$v1['tmall_price'];
		    		}
		    		elseif($v1['tmall_price']){
		    			$TJ_price = $v1['tmall_price'];
		    		}
		    		elseif($v1['jumei_price']){
		    			$TJ_price = $v1['jumei_price'];
		    		}
		    		
		    		$in_id = $v1['id'];

		    		// $g_sn = 'czs'.$in_id.rand(1000,9999);

		    		// $sale_price = $TJ_price*0.99;

		    		// $trade_price_cn = $v2['trade_price_ori']*0.0059;

		    		// $profit = ($sale_price-$trade_price_cn)/$trade_price_cn;

		    		//$sql = 'update fx_in_goods_supplier set sale_price="'.$sale_price.'",trade_price_cn="'.$trade_price_cn.'",profit="'.$profit.'",g_sn="'.$g_sn.'", TJ_price="'.$TJ_price.'",in_id='.$in_id.',tmall_price="'.$v1['tmall_price'].'",jumei_price="'.$v1['jumei_price'].'",tmall_url="'.$v1['tmall_url'].'",jumei_url="'.$v1['jumei_url'].'" where id='.$v2['id'];
		    		$sql = 'update fx_in_goods_supplier set TJ_price="'.$TJ_price.'",'.$id.'='.$in_id.',tmall_price="'.$v1['tmall_price'].'",jumei_price="'.$v1['jumei_price'].'",tmall_url="'.$v1['tmall_url'].'",jumei_url="'.$v1['jumei_url'].'" where id='.$v2['id'];
		    		echo $sql.'<br>';
		    		$M->query($sql);   							
				}
			}
		}   	
    }

    public function manualMatch(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$sql = 'select * from fx_in_goods_supplier where in_id is not null and TJ_price is null';
    	//$sql = "select * from fx_in_goods_supplier where in_id is not null and tmall_price='' and jumei_price=''";
    	$M = M('');
    	$ary_data = $M->query($sql);
    	foreach($ary_data as $vl){
    		$sql = 'select * from fx_in_goods where id='.$vl['in_id'];
    		$good = $M->query($sql);
    		if($good[0]['tmall_price']&&$good[0]['jumei_price']){
    			$TJ_price = $good[0]['tmall_price']>$good[0]['jumei_price']?$good[0]['jumei_price']:$good[0]['tmall_price'];
    		}
    		elseif($good[0]['tmall_price']){
    			$TJ_price = $good[0]['tmall_price'];
    		}
    		elseif($good[0]['jumei_price']){
    			$TJ_price = $good[0]['jumei_price'];
    		}

			$sql = 'update fx_in_goods_supplier set TJ_price="'.$TJ_price.'",tmall_price="'.$good[0]['tmall_price'].'",jumei_price="'.$good[0]['jumei_price'].'",tmall_url="'.$good[0]['tmall_url'].'",jumei_url="'.$good[0]['jumei_url'].'" where in_id='.$vl['in_id'];
			$M->query($sql);
    	}
    }

    public function setProfitBase(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$sql = 'select * from fx_in_goods where id in (select in_id from fx_in_goods_supplier)';
    	$M = M('');
    	$ary_data = $M->query($sql);

    	foreach($ary_data as $vl){
    		$profit_notsole = '';

    		
    		if($vl['main_class']){
    			$sql = 'select profit_notsole from fx_goods_category where gc_name="'.$vl['main_class'].'"';
    			$c = $M->query($sql);
    			$profit_notsole = $c[0]['profit_notsole']/100;
    		}
    		if(empty($profit_notsole)&&$vl['class1']){
    			$sql = 'select profit_notsole from fx_goods_category where gc_name="'.$vl['class1'].'"';
    			$c = $M->query($sql);
    			$profit_notsole = $c[0]['profit_notsole']/100;
    		}
    		if(empty($profit_notsole)&&$vl['class2']){
    			$sql = 'select profit_notsole from fx_goods_category where gc_name="'.$vl['class2'].'"';
    			$c = $M->query($sql);
    			$profit_notsole = $c[0]['profit_notsole']/100;
    		}
    		if(empty($profit_notsole)&&$vl['class3']){
    			$sql = 'select profit_notsole from fx_goods_category where gc_name="'.$vl['class3'].'"';
    			$c = $M->query($sql);
    			$profit_notsole = $c[0]['profit_notsole']/100;
    		}

    		if($profit_notsole){
    			$sql = 'update fx_in_goods_supplier set profit_base="'.$profit_notsole.'" where in_id='.$vl['id'];
    			echo $sql.'<br>';
    			$M->query($sql);
    		}
    	}

    }

    public function setGcid(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$sql = 'select * from fx_in_goods_supplier where bar_code!=""';
    	$M = M('');
    	$ary_data = $M->query($sql);
    	//var_dump($ary_data);
    	foreach($ary_data as $vl){
    		if($vl['bar_code']){
    			$sql = 'select g_id from fx_goods_products where pdt_bar_code="'.$vl['bar_code'].'"';
    			echo $sql.'<br>';
    			$rs = $M->query($sql);
    			if($rs){
    				$g_id = $rs[0]['g_id'];
    				$sql = 'select * from fx_related_goods_category where g_id='.$g_id;
    				echo $sql.'<br>';
    				$rs = $M->query($sql);
    				if($rs){
    					$gc_id = $rs[0]['gc_id'];
    					$sql = 'update fx_in_goods_supplier set gc_id='.$gc_id.' where id='.$vl['id'];
    					echo $sql.'<br>';
    					$M->query($sql);
    				}
    			}
    		}    		
    	}
    }

    public function explortIngoods(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$M = M('');
    	$sql = 'select * from fx_in_goods where `brand_en`="OMM"';
    	$ary_data = $M->query($sql);
    	$header = array('id', '国家', '进口', '国产', '中文品牌', '外国品牌', '主功效', '功效一','功效二','功效三','颜色','材料','自命名','主分类','分类一','分类二','分类三','单位','包装','规格','使用部位','平台一价格','平台二价格','平台一网址','平台二网址');
        $contents = array();
        foreach($ary_data as $vl){
	         $contents[0][] = array(
	            $vl['id'],
	            $vl['country'],
	            $vl['importation'],
	            $vl['home_made'],
	            $vl['brand_cn'],
	            $vl['brand_en'],
	            $vl['main_fun'],
	            $vl['fun1'],
	            $vl['fun2'],
	            $vl['fun3'],
	            $vl['color'],
	            $vl['material'],
	            $vl['nomenclature'],
	            $vl['main_class'],
	            $vl['class1'],
	            $vl['class2'],
	            $vl['class3'],
	            $vl['unit'],
	            $vl['package'],
	            $vl['spec'],
	            $vl['part_used'],
	            $vl['tmall_price'],
	            $vl['jumei_price'],
	            $vl['tmall_url'],
	            $vl['jumei_url'],
	        );
        }
        $fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y');
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $filename = 'Ingoods_'.date('YmdHis') . '.xls';
        $Export = new Export($filename, $filexcel);
        $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '商品识别表', true);
        if (!empty($excel_file)) {
            echo '导出成功';
            echo $filexcel.$filename;
        } else {
            echo '导出失败';
        }
    }

    public function explortSupplierMatch(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = $_GET['brand'];

    	$where = 'gs.brand_en like "%'.$brand.'%"';
		//$where.=' and gs.in_id>0';
		$where.=' and (0 or gs.mnc_in_id>0 or gs.mn_in_id>0 or gs.mc_in_id>0 or gs.nc_in_id>0 or gs.m_in_id>0 or gs.n_in_id>0 or gs.c_in_id>0)';

    	$M = M('');
    	$fileds = 'gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
    	$fileds.= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,';
    	$fileds.= 'gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.mnc_in_id,gs.mn_in_id,gs.mc_in_id,gs.nc_in_id,gs.m_in_id,gs.n_in_id,gs.c_in_id,';
    	$fileds.= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url';
    	$sql = 'select '.$fileds.' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id where '.$where;
    	echo $sql;
    	//exit();
    	$ary_data = $M->query($sql);

    	$header = array('序号', '供应商自编商品名称','mnc','mn','mc','nc','m','n','c', '平台网址一','平台网址二','品牌属国', '进口', '国产', '中文品牌', '外国品牌', '主功效', '功效一','功效二','功效三','颜色','材料','自命名','主分类','分类一','分类二','分类三','单位','包装','规格','使用部位','条形码','供应商当前库存','基本库存','供应商名称','品牌官网URL');
        $contents = array();
        foreach($ary_data as $key=>$vl){
	         $contents[0][] = array(
	            $key,
	            $vl['name_cn'],
	            $vl['mnc_in_id'],
	            $vl['mn_in_id'],
	            $vl['mc_in_id'],
	            $vl['nc_in_id'],
	            $vl['m_in_id'],
	            $vl['n_in_id'],
	            $vl['c_in_id'],
	            $vl['tmall_url'],
	            $vl['jumei_url'],
	            $vl['language_country'],
	            $vl['importation'],
	            $vl['home_made'],
	            $vl['brand_cn'],
	            $vl['brand_en'],
	            $vl['main_fun'],
	            $vl['fun1'],
	            $vl['fun2'],
	            $vl['fun3'],
	            $vl['color'],
	            $vl['material'],
	            $vl['nomenclature'],
	            $vl['main_class'],
	            $vl['class1'],
	            $vl['class2'],
	            $vl['class3'],
	            $vl['unit'],
	            $vl['package'],
	            $vl['spec'],
	            $vl['part_used'],
	            $vl['bar_code'],
	            $vl['all_stock'],
	            $vl['base_stock'],
	            $vl['supplier'],
	            $vl['website'],
	        );
        }
		$fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ');
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $filename = 'Supplier_'.$brand.'_match'.date('YmdHis') . '.xls';
        $Export = new Export($filename, $filexcel);
        $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '供应商商品识别表', true);
        if (!empty($excel_file)) {
            //echo '导出成功';
            header('Location: http://www.caizhuangguoji.com/Public/Uploads/'.CI_SN.'/excel/'.$filename); 
        } else {
            echo '导出失败';
        }
    }

    public function explortSupplierNotMatch(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = $_GET['brand'];

    	$where = 'gs.brand_en like "%'.$brand.'%"';
		$where.=' and (gs.in_id is null or gs.in_id=0)';

    	$M = M('');
    	$fileds = 'gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
    	$fileds.= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,';
    	$fileds.= 'gs.all_stock,gs.base_stock,gs.supplier,gs.website,';
    	$fileds.= 'gs.purchase_price_cn,gs.supply_price_ori,gs.retail_price_ori,gs.discount_ori';
    	$sql = 'select '.$fileds.' from fx_in_goods_supplier gs where '.$where;
    	echo $sql;
    	//exit();
    	$ary_data = $M->query($sql);

    	$header = array('序号', '供应商自编商品名称', '品牌属国', '进口', '国产', '中文品牌', '外国品牌','单位','包装','规格','使用部位','条形码','批发采购价(人民币)','批发供应价（属国币）','零售价（属国币）','优惠折头','供应商当前库存','基本库存','供应商名称','品牌官网URL');
        $contents = array();
        foreach($ary_data as $key=>$vl){
	         $contents[0][] = array(
	            $key,
	            $vl['name_cn'],
	            $vl['language_country'],
	            $vl['importation'],
	            $vl['home_made'],
	            $vl['brand_cn'],
	            $vl['brand_en'],
	            $vl['unit'],
	            $vl['package'],
	            $vl['spec'],
	            $vl['part_used'],
	            $vl['bar_code'],
	            $vl['purchase_price_cn'],
	            $vl['supply_price_ori'],
	            $vl['retail_price_ori'],
	            $vl['discount_ori'],
	            $vl['all_stock'],
	            $vl['base_stock'],
	            $vl['supplier'],
	            $vl['website'],
	        );
        }
		$fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S','T');
        $filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $filename = 'Supplier_'.$brand.'_notmatch'.date('YmdHis') . '.xls';
        $Export = new Export($filename, $filexcel);
        $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '供应商商品未能识别表', true);
        if (!empty($excel_file)) {
            //echo '导出成功';
            header('Location: http://www.caizhuangguoji.com/Public/Uploads/'.CI_SN.'/excel/'.$filename); 
        } else {
            echo '导出失败';
        }
    }

    public function explortSupplierOrder(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = $_GET['brand'];

    	$where = 'gs.brand_en like "%'.$brand.'%"';
     	$M = M('');
    	$fileds = 'gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
    	$fileds.= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.goods_sn,gs.bar_code,';
    	$fileds.= 'gs.all_stock,gs.base_stock,gs.supplier,gs.website,';
    	$fileds.= 'gs.purchase_price_cn,gs.supply_price_ori,gs.retail_price_ori,gs.discount_ori';
    	$sql = 'select '.$fileds.' from fx_in_goods_supplier gs where '.$where;   	
    }

    public function explortC6(){
    	echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    	$brand = $_GET['brand'];
    	$where = 'gs.brand_en like "%'.$brand.'%"';
    	$M = M('');
    	$fileds = 'gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
    	$fileds.= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,gs.purchase_price_cn,gs.supply_price_cn,gs.retail_price_cn,';
    	$fileds.= 'gs.discount_cn,gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.sale_price,gs.profit,gs.profit_base,gs.tmall_price,gs.jumei_price,gs.tmall_url,gs.jumei_url,';
    	$fileds.= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url';
    	$sql = 'select '.$fileds.' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id where '.$where;
    	echo $sql;
    	//exit();
    	$ary_data = $M->query($sql);

/*    	$header = array('序号',
    	 '供应商自编商品名称', 
    	 '品牌属国',
    	 '进口', 
    	 '国产', 
    	 '中文品牌',
    	 '外国品牌',
    	 '主功效',
    	 '功效一',
    	 '功效二',
    	 '功效三',
    	 '颜色',
    	 '材料',
    	 '自命名',
    	 '主分类',
    	 '分类一',
    	 '分类二',
    	 '分类三',
    	 '单位',
    	 '包装',
    	 '规格',
    	 '使用部位',
    	 '条形码',
    	 '批发采购价',
    	 '批发供应价',
    	 '零售价',
    	 '优惠折头',
    	 '平台一价格',
    	 '平台二价格',
    	 '销售价格',
    	 '个人直邮税率',
    	 'BC直邮税率',
    	 '利润率',
    	 '供应商当前库存',
    	 '基本库存',
    	 '供应商名称',
    	 '是否为新零售供应商(Y/N）',
    	 '供应商url',
    	 '平台一url',
    	 '平台二url',
    	 '品牌官网URL',
    	 );
        $contents = array();
        foreach($ary_data as $key=>$vl){
	         $contents[0][] = array(
	            $key+1,
	            $vl['name_cn'],
	            $vl['language_country'],
	            $vl['importation'],
	            $vl['home_made'],
	            $vl['brand_cn'],
	            $vl['brand_en'],
	            $vl['main_fun'],
	            $vl['fun1'],
	            $vl['fun2'],
	            $vl['fun3'],
	            $vl['color'],
	            $vl['material'],
	            $vl['nomenclature'],
	            $vl['main_class'],
	            $vl['class1'],
	            $vl['class2'],
	            $vl['class3'],
	            $vl['unit'],
	            $vl['package'],
	            $vl['spec'],
	            $vl['partused'],
	            $vl['bar_code'],
	            $vl['purchase_price_cn'],
	            $vl['supply_price_cn'],
	            $vl['retail_price_cn'],
	            $vl['discount_cn'],
	            $vl['tmall_price'],
	            $vl['jumei_price'],
	            $vl['sale_price'],
	            '',
	            '',
	            $vl['profit'],
	            $vl['all_stock'],
	            $vl['base_stock'],
	            $vl['supplier'],
	            '',
	            $vl['tmall_url'],
	            $vl['jumei_url'],
	            $vl['website'],
	        );
        }*/

    	$header = array('序号',
    	 '供应商自编商品名称', 
    	 '品牌属国',
    	 '进口', 
    	 '国产', 
    	 '中文品牌',
    	 '外国品牌',
    	 '颜色',
    	 '材料',
    	 '自命名',
    	 '主分类',
    	 '分类一',
    	 '分类二',
    	 '分类三',
    	 '单位',
    	 '包装',
    	 '规格',
    	 '使用部位',
    	 '条形码',
    	 '批发采购价',
    	 '批发供应价',
    	 '零售价',
    	 '优惠折头',
    	 '平台一价格',
    	 '平台二价格',
    	 '销售价格',
    	 '个人直邮税率',
    	 'BC直邮税率',
    	 '利润率',
    	 '商品利润率',
    	 '供应商当前库存',
    	 '基本库存',
    	 '供应商名称',
    	 '是否为新零售供应商(Y/N）',
    	 '平台一url',
    	 '平台二url',
    	 '品牌官网URL',
    	 );
        $contents = array();
        foreach($ary_data as $key=>$vl){
	         $contents[0][] = array(
	            $key+1,
	            $vl['name_cn'],
	            $vl['language_country'],
	            $vl['importation'],
	            $vl['home_made'],
	            $vl['brand_cn'],
	            $vl['brand_en'],
	            $vl['color'],
	            $vl['material'],
	            $vl['nomenclature'],
	            $vl['main_class'],
	            $vl['class1'],
	            $vl['class2'],
	            $vl['class3'],
	            $vl['unit'],
	            $vl['package'],
	            $vl['spec'],
	            $vl['partused'],
	            $vl['bar_code'],
	            $vl['purchase_price_cn'],
	            $vl['supply_price_cn'],
	            $vl['retail_price_cn'],
	            $vl['discount_cn'],
	            $vl['tmall_price'],
	            $vl['jumei_price'],
	            $vl['sale_price'],
	            '',
	            '',
	            $vl['profit'],
	            $vl['profit_base'],
	            $vl['all_stock'],
	            $vl['base_stock'],
	            $vl['supplier'],
	            '',
	            $vl['tmall_url'],
	            $vl['jumei_url'],
	            $vl['website'],
	        );
        }
		//$fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC', 'AD', 'AE', 'AF', 'AG', 'AH','AI','AJ','AK','AL','AM','AN');
		$fields = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC', 'AD', 'AE', 'AF', 'AG', 'AH','AI','AJ','AK');
		$filexcel = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/';
        if(!is_dir($filexcel)){
                @mkdir($filexcel,0777,1);
        }
        $filename = 'Supplier_'.$brand.'_C6_'.date('YmdHis') . '.xls';
        $Export = new Export($filename, $filexcel);
        $excel_file = $Export->exportExcel($header, $contents[0], $fields, $mix_sheet = '管理列表（C6）', true);
        if (!empty($excel_file)) {
            //echo '导出成功';
            header('Location: http://www.caizhuangguoji.com/Public/Uploads/'.CI_SN.'/excel/'.$filename); 
        } else {
            echo '导出失败';
        }
    }

    public function importInGoodsSupplier(){
		header("Content-type: text/html;charset=utf-8");
        require_once FXINC . '/Lib/Common/' . 'PHPExcel/IOFactory.php';
        require_once FXINC . '/Lib/Common/' . 'PHPExcel.php';

		$objPHPExcel = new PHPExcel();
		$objReader = PHPExcel_IOFactory::createReader('Excel5');  //加载2003的
		$objPHPExcel = $objReader->load(APP_PATH.'Public/Uploads/'.CI_SN.'/excel/test7.5.xls');  //载入文件
		//$Allnewpic= $objPHPExcel->getSheet(0)->getDrawingCollection();

		//var_dump($Allnewpic);
		//exit();

		foreach ($objPHPExcel->getSheet(0)->getDrawingCollection() as $k => $drawing) {
		        $codata = $drawing->getCoordinates(); //得到单元数据 比如G2单元
		        $filename = $drawing->getIndexedFilename();  //文件名
		        ob_start();
		        call_user_func(
		            $drawing->getRenderingFunction(),
		            $drawing->getImageResource()
		        );
		        $imageContents = ob_get_contents();
		        //file_put_contents('pic/'.$codata.'_'.$filename.'.jpg',$imageContents); //把文件保存到本地
		        echo $imageContents;
		        ob_end_clean();
		}
		exit();
        $str_upload_file = APP_PATH.'Public/Uploads/'.CI_SN.'/excel/test7.5.xlsx';
        $objCalc = PHPExcel_Calculation::getInstance();
        //读取Excel客户模板
        $objPHPExcel = PHPExcel_IOFactory::load($str_upload_file);
        $obj_Writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //读取第一个工作表(编号从 0 开始)
        $sheet = $objPHPExcel->getSheet(0);
        //取到有多少条记录 
        //$highestRow = $sheet->getHighestRow();
        $array_goods_info = array();
        for($row=7; $row <= 16; $row++){
        	 echo trim($objPHPExcel->getActiveSheet()->getCell('E' . $row)->getCalculatedValue()).'<br>';
        }	

        foreach ($sheet->getDrawingCollection() as $k => $drawing) {
	        $codata = $drawing->getCoordinates(); //得到单元数据 比如G2单元
	        $filename = $drawing->getIndexedFilename();  //文件名
	        ob_start();
	        call_user_func(
	            $drawing->getRenderingFunction(),
	            $drawing->getImageResource()
	        );
	        $imageContents = ob_get_contents();
	        echo $imageContents;
	        ob_end_clean();
		}
    }
}