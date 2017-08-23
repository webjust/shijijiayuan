<?php
/**
 * 地区选择 widget
 * @example W('Area',array('cr_id'=>1))
 * @author czy <chengzongyao@guanyisoft.com>
 * @version TS3.0
 */
class AreaWidget extends Widget {
	
    /**
     * 渲染会员地区模板
     * @example
     * $data['cr_id'] integer 会员地区id
     * @param array $data 配置的相关信息
     * @return string 渲染后的模板数据
     */
	public function render($data) {
	   $obj_city = D('CityRegion');
       $ary_cr_path = $obj_city->field('cr_path')->where(array('cr_id'=>$data['cr_id']))->find();
       $ary = array();
       if($ary_cr_path['cr_path']){
        $ary_ids = explode('|',$ary_cr_path['cr_path']);
        $obj_CityRegion = D('CityRegion');
        foreach($ary_ids as $key=>$val){
            $ary["region_{$key}"] = $obj_CityRegion->getParentsAddr($val);
        }
         
         array_shift($ary_ids);
         array_push($ary_ids,$data['cr_id']);
        foreach($ary_ids as $key=>$val){
            $ary["selected_{$key}"] = $val;
         }
         
       }
      $content = $this->renderFile('addressPage',$ary);
      return $content;  
	}
	
		
}