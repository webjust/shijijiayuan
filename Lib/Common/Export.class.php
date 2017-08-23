<?php
/**
 * 导出，报表导出操作
 * 
 * 用于处理全站报表导出操作
 * 
 * @package goodsStock
 * @subpackage
 * @stage 5.0
 * @author Mithern
 * @date 2012-02-20
 * @license MIT
 * @copyright Copyright (C) 2011, Shanghai GuanYiSoft Co., Ltd.
 */
class Export {

	//文件名称，必须包含扩展名，例如download.xls
	private $str_filename;
	//文件路径，例如d:/wwwroot/5.b2b.com/download/ 注意：必须以斜杠结束
	private $str_filepath;
	//PHPExcel资源句柄
	private $PHPExcel;
	//PHPExcel 文件句柄
	private $xls;

	public function __construct($str_file_name,$str_file_path){
		$this -> str_file_name = $str_file_name;
		$this -> str_filepath = $str_file_path;
	}
	
	/**
	 * 导出数据到Excel
	 * 
	 * 注意，本方法多参数有严格要求，注意按照参数要求传参
	 * 
	 * @params $ary_header 报表标头，一维非关联数组，数组的key自增，数组的值为列表项目的表头
	 * @params $ary_content 报表列表内容，如果是单一sheet，则传入二维数组，如果是多sheet，则传入三维数组
	 * @params $excel_fields=array('A','B','C','D')，例如这种形式，定义Excel显示的列
	 * @params $ary_sheet sheet的名称，如果是单一sheet，则可以直接传入字符串，如果是多sheet，则传入一维数组
	 * @params $bool_simple_sheet 是否是单一sheet
	 * 
	 * @return 返回生成的Excel的名称
	 * 
	 * @author Mithern
	 * @version 1.0
	 * @since stage 5.0
	 * @modify 2012-02-20
	 */
	public function exportExcel($ary_header,$ary_content,$excel_fields=array(),$mix_sheet = array(),$bool_simple_sheet = true){
		require_once 'PHPExcel/IOFactory.php';
		require_once 'PHPExcel.php';
		$this->PHPExcel = new PHPExcel();
		$this->xls = new PHPExcel_Writer_Excel5($this->PHPExcel);
              
		if(false === $bool_simple_sheet) {
			//多Sheet模式
			$int_sheet_index = 0;
			foreach($ary_content as $key => $val) {
				//sheet标题
				$str_sheet_title = $mix_sheet[$key];
				$this -> putContentIntoSheet($ary_header,$val,$excel_fields,$str_sheet_title,$int_sheet_index);
				$int_sheet_index ++;
			}
		}else{
                    
			//单Sheet模式
			$this -> putContentIntoSheet($ary_header,$ary_content,$excel_fields,$mix_sheet);
		}
		//将内容写入文件
                //echo $this -> str_filepath . $this -> str_file_name;exit;
		$this->xls->save($this -> str_filepath . $this -> str_file_name);
		//返回文件名
                  
		return $this -> str_file_name;
	}
	
	/*
	 * 内容写入
	 */
	public function putContentIntoSheet($header,$content,$excel_fields,$str_sheet='数据报表',$sheet_index=0){
		$this->PHPExcel->createSheet($sheet_index);
		//echo 888;
		//设置sheet名称
		$this->PHPExcel->getSheet($sheet_index)->setTitle($str_sheet);
		//echo 111;
		//获取当前活动的工作簿
		$sheet=$this->PHPExcel->setActiveSheetIndex($sheet_index);
		//echo 222;
		/**************循环输出表头，开始****************/
		//$excel_fields=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T');
		$i=0;
		foreach($header as $key => $val){
			$sheet->setCellValue($excel_fields[$i] . '1',$val);
			$i++;
		}
		/**************循环输出表头，结束****************/
		/**************循环输出内容，开始****************/
		
		$rows=2;
		foreach($content as $key => $val){
			$i=0;
			foreach($val as $k=>$v){
				if($v === null) $v='';
				$cell=$excel_fields[$i] . $rows;
				$sheet->setCellValue($cell,$v);
				$i++;
			}
			$rows++;
		}
		/**************循环输出内容，结束****************/
	}
}