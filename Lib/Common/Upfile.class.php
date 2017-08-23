<?php
class Upfile {
	
	protected $keys;
	protected $err = array();
	protected $target;
	protected $exts;
	protected $maxSize;
	protected $randName;
	protected $files = array();

	/**
	* 初始化变量
	*/
	public function __construct() {
		$this->exts = array('jpeg','jpg','gif','png','zip','rar','xlsx','swf');
		$this->maxSize = 1024*1024*2;
		$this->target =  PICUPLOADPATH;
		$this->randName = true;
		$this->keys = $this->getKeys();
	}
	
	/**
	* 获取 file 的名称
	*/
	public function getKeys() {
		$keys = array_keys($_FILES);
		return $keys;
	}

	/**
	* 设置不同类型的变量
	*/
	public function __set($name, $value) {
		$property = array('target','exts','maxSize','randName');
		if(!in_array($name, $property)) return false;
		switch(strval($name))
		{
			case 'target':
			$this->$name = $value;
			break;
			case 'exts':
			$this->$name = explode(',', $value);
			break;
			case 'randName':
			if($value === true || $value == 1)
			{
			$this->$name = true;
			}
			else {
			$this->$name = false;
			}
			break;
			default:
			$this->$name = $value;
		}
	}

	/**
	* 移动上传的文件到指定的目录
	* @param $fileName 移动单个文件名称的时候，对上传的文件重新命名
	*/
	public function save($fileName='')
	{
		$this->err = array();
		$this->files = array();
		if(!file_exists($this->target)) {
		mkdir($this->target);
		chmod($this->target, 0777);
		}
		foreach($this->keys as $key)
		{
		if(is_array($_FILES[$key]['name']))
		{
		$count = count($_FILES[$key]['name']);
		for($i=0; $i<$count; $i++)
		{
		$keys = array_keys($_FILES[$key]);
		foreach($keys as $item)
		{
		$file[$item] = $_FILES[$key][$item][$i];
		}
		$this->upload($file, $fileName);
		}
		return (count($this->err) > 0)? false:true;
		}
		else {
		return $this->upload($_FILES[$key], $fileName);
		}
		}
	}
	
	/** 内部处理上传文件的过程 */
	public function upload($file, $fileName)
	{
		if(!is_uploaded_file($file['tmp_name'])) return false;
		if(!$this->checkExt($file)) return false;
		if(!$this->checkSize($file)) return false;


		if($this->randName)
		{
			$newFileName = $this->target . date('YmdHis', time()) . rand(0,9) . '.' . $this->getExt($file['name']);
		}
		elseif(empty($fileName))
		{
			$newFileName = $this->target  . $file['name'].'.'.$this->getExt($file['name']);
		}
		else {
			$newFileName = $this->target  . $fileName.'.'.$this->getExt($file['name']);
		}
				
		$result = move_uploaded_file($file['tmp_name'], $newFileName);
		
		$path_parts = pathinfo($newFileName);

		$file_name  = $path_parts['basename'];

	//	$info = new SplFileInfo($newFileName);
	//	$file_name  = $info->getFilename();

		$res = array(
			'status' 	=> 0,
			"img_src"	=>	$file_name
		);

		if(!$result)
		{
			$res['status'] = 0;
		}
		else {
		//$this->files[] = str_replace($this->target, $newFileName, $result);
			chmod( $newFileName, 0755);

			$this->files[] = $newFileName;

			$res['status'] = 1;
		}
	
		return $res;
	}
	
	/**
	* 是否是可上传的文件类型
	* @param $file 文件对象
	*/
	public function checkExt($file) {
		if(!in_array($this->getExt($file['name']), $this->exts))
		{
		$this->err[] = $file['name'].':ext';
		return false;
		}
		else {
		return true;
		}
	}

	/**
	* 获取文件后缀名
	*/
	public function getExt($fileName)
	{
		$pos = strrpos($fileName, '.')+1;
		return substr($fileName, $pos);
	}

	/**
	* 检查文件大小是否合法
	*/
	public function checkSize($file)
	{
		if($file['size'] > $this->maxSize)
		{
		$this->err[] = $file['name'].':max';
		return false;
		}
		else {
		return true;
		}
	}
	
	/**
	* 取得已经上传的文件名称
	*/
	public function getFiles()
	{
		return $this->files;
	}
}

/*
$U = new Upfile();
$U->target = '/tmp/';
$U->exts = 'jpg,gif';
$U->maxSize = 1024*275; //275KB
$U->save();
$files = $U->getFiles();
print_r($files);



<!-- 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>无标题文档</title>
</head>
<body>
<form action="" method="post" enctype="multipart/form-data">
 以下两上file类型控制的name属性可以任意设置，系统会自己取出input 的名称
<input name="files[]" type="file" size="30" />
<input name="files[]" type="file" size="30" />
<input type="submit" value="开始上传" />
</form>
</body>
</html> -->
*/