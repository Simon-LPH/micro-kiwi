<?php
if(!defined('IN_KIWI')) exit('Access Denied');
/*
 +--------------------------------
 + 简单的图片尺寸修改
 + 支持 gif jpg/jpeg png 格式图片
 + 需要php GD库支持
 +--------------------------------
*/
class SimpleImage
{
	//当前图片文件名
	private $_imageName;
	//当前图片的信息
	private $_imageSize;
	//
	private $_imageString;
	
	private $_oSource;
	
	private $_oDstImg;
	
	public function __construct($in_name=''){
		$this->loadImgName($in_name);
		if(!function_exists('imagecreatetruecolor'))
			exit('GD2 is required.');
	}
	
	/*
	+ 通过图片名称加载图片
	*/
	public function loadImgName($in_name){
		if($in_name && is_file($in_name)){
			$this->_imageName = $in_name;
			$this->_imageSize = $this->getSizeByName($this->_imageName);
			$this->_oSource = $this->getSource($this->_imageSize['mimetype'], $this->_imageName);
			return true;
		}
		return false;
	}
	
	/*
	+ 通过图片文件流加载图片
	*/
	public function loadString($string, $type='image/jpg'){
		$this->_imageString = $string;
		$this->_oSource = $this->getSource();
		$this->_imageSize = $this->getSizeBySource($this->_oSource, $type);
	}
	
	/*
	+ 通过图片对象获取图片信息
	*/
	public function getSizeBySource($_oSource, $type='image/jpg'){
		$imageSize = array(
			'width' => imagesx($_oSource),
			'height' => imagesy($_oSource),
			'mimetype' => $type
		);
		return $imageSize;
	}
	
	/*
	+ 通过图片名获取图片信息
	*/
	public function getSizeByName($in_name=''){
		if(!$in_name) $in_name = $this->_imageName;
		if(!$in_name || !is_file($in_name)) return false;
		$size = getimagesize($in_name);
		if(!$size) return false;
		$imageSize = array(
			'width' => $size[0],
			'height' => $size[1],
			'type' => $size[2],
			'string' => $size[3],
			'mimetype' => image_type_to_mime_type($size[2])
		);
		return array_merge($size, $imageSize);
	}
	
	public function info(){
		return $this->_imageSize;
	}
	
	//按比例缩放
	public function resizeByScale($scale){
		return $this->resizeNewImg(0, 0, $scale, 0, 0);
	}
	
	//按像素缩放
	public function resizeByPixel($newImageWidth, $newImageHeight){
		return $this->resizeNewImg($newImageWidth, $newImageHeight, 0, 0, 0, 0, 0);
	}
	
	//按规定像素大小裁切，可通过 $scale 调节分辨率比值
	public function cutByPixel($newImageWidth, $newImageHeight, $auto=true, $start_x=0, $start_y=0, $scale=0){
		if($newImageWidth<=0 || $newImageHeight<=0){
			return false;
		}
		$scale = floatval($scale);
		$imageSize = $this->getSizeByName($this->_imageName);
		if($scale>0){
			$imageWidth = ceil($imageSize['width']*$scale);
			$imageHeight = ceil($imageSize['height']*$scale);
		}
		else{
			$imageWidth = $newImageWidth;
			$imageHeight = $newImageHeight;
		}
		if($auto){
			$n_scare_w_h = $newImageWidth/$newImageHeight;
			$o_scare_w_h = $imageSize['width']/$imageSize['height'];
			if($n_scare_w_h > $o_scare_w_h){
				$imageWidth = $imageSize['width'];
				$imageHeight = ceil($imageWidth/$n_scare_w_h);
			}
			else{
				$imageHeight = $imageSize['height'];
				$imageWidth = ceil($imageHeight*$n_scare_w_h);
			}
			$start_x = ceil(($imageSize['width'] - $imageWidth)/2);
			$start_y = ceil(($imageSize['height'] - $imageHeight)/2);
		}
		return $this->resizeNewImg($newImageWidth, $newImageHeight, 0, $start_x, $start_y, $imageWidth, $imageHeight);
	}
	
	//缩放裁切主函数
	public function resizeNewImg($newImageWidth, $newImageHeight, $scale=0,
		$start_x=0, $start_y=0, $imagewidth=0, $imageheight=0){
		
		if(!is_resource($this->_oSource)){
			exit('No resource is loaded.');
		}
		$imageSize = $this->_imageSize;
		$newImageWidth = intval($newImageWidth);
		$newImageHeight = intval($newImageHeight);
		$scale = floatval($scale);
		$start_x = intval($start_x);
		$start_y = intval($start_y);
		$imagewidth = intval($imagewidth);
		$imageheight = intval($imageheight);
		
		if(!$imagewidth){
			$imagewidth = $imageSize['width'];
		}
		if(!$imageheight){
			$imageheight = $imageSize['height'];
		}
		if($scale){
			$newImageWidth = ceil($imagewidth * $scale);
			$newImageHeight = ceil($imageheight * $scale);
		}
		if(!$newImageWidth && !$newImageHeight){
			$newImageWidth = $imagewidth;
			$newImageHeight = $imageheight;
		}
		elseif($newImageWidth && !$newImageHeight){
			$newImageHeight = ceil(($imageheight/$imagewidth) * $newImageWidth);
		}
		elseif(!$newImageWidth && $newImageHeight){
			$newImageWidth = ceil(($imagewidth/$imageheight) * $newImageHeight);
		}
		$this->_oDstImg = imagecreatetruecolor($newImageWidth, $newImageHeight);
		//背景色为白色
		$bg = imagecolorallocate($this->_oDstImg, 255, 255,255);
		//填充背景色
    	imagefilledrectangle($this->_oDstImg, 0, 0, $newImageWidth, $newImageHeight, $bg);
		//将源图片复制到目标图层中
		return imagecopyresampled($this->_oDstImg, $this->_oSource, 0, 0, $start_x, $start_y, $newImageWidth, $newImageHeight, $imagewidth, $imageheight);
	}
	
	//图片竖直拼接
	public function simpleMerge($imageNames, $thumb_image_name, $width){
		if(count($imageNames)>0){
			$height = 0;
			foreach($imageNames as $img){
				$imageSize = $this->getSizeByName($img);
				$height += ceil($width/$imageSize['width'] * $imageSize['height']);
			}
			$this->_oDstImg = imagecreatetruecolor($width, $height);
			$start_y = 0;
			$created = true;
			foreach($imageNames as $img){
				$imageSize = $this->getSizeByName($img);
				$_oSource = $this->getSource($imageSize['mimetype'], $img);
				if(is_resource($_oSource)){
					$t_height = ceil($width/$imageSize['width'] * $imageSize['height']);
					//将源图片复制到目标图层中
					$set = imagecopyresampled($this->_oDstImg, $_oSource, 0, $start_y, 0, 0, $width, $t_height, $imageSize['width'], $imageSize['height']);
					if(!$set){
						$created=false;
						break;
					}
					$start_y += $t_height;
				}
			}
			$this->_imageSize['mimetype'] = 'image/jpeg';
			return $created;
		}
		return false;
	}
	
	//添加文字
	public function addtext($text, $x, $y, $size=12, $color=array()){
		if(empty($color)){
			$color = array(0x00, 0x00, 0x00);
		}
		array_unshift($color, $this->_oDstImg);
		$oColor = call_user_func_array('imagecolorallocate', $color);
		imagettftext($this->_oDstImg, $size, 0, $x, $y+$size, $_color, LIB_PATH.'font/jiankai.ttf', $text);
	}
	
	/*
	+ 图注文字(显示在底部)
	+ @param $text string 文本内容
	+ @param $size int 尺寸
	+ @param $color array RGB数组
	+ @param $align int 对齐方式 0-左对齐|1-居中|2-右对齐
	+ @param $indent int 文本缩进
	*/
	public function addStatementText($text, $size=12, $color=array(), $align=0, $indent=0){
		if(!is_resource($this->_oSource)){
			exit('No resource is loaded.');
		}
		$imageSize = $this->_imageSize;
		$line_count = substr_count($text,"\n");
		$line_height = $size*2 + $size*$line_count*1.5;
		$bg_height = $imageSize['height']+$line_height;
		$this->_oDstImg = imagecreatetruecolor($imageSize['width'], $bg_height);
		if(empty($color)){
			$color = array(0x00, 0x00, 0x00);
		}
		array_unshift($color, $this->_oDstImg);
		$oColor = call_user_func_array('imagecolorallocate', $color);
		if($align==1){
			//文字居中
			$x=$indent + $imageSize['width']/2 - strlen($text)*($size/5);
		}
		elseif($align==2){
			//文字右对齐
			$x=$indent + $imageSize['width'] - strlen($text)*($size/2.2);
		}
		else{
			//文字左对齐
			$x=$indent;
		}
		$y=$imageSize['height']+$size+2;
		//背景色为白色
		$bgcolor = imagecolorallocate($this->_oDstImg, 255, 255,255);
		//填充背景色
		imagefilledrectangle($this->_oDstImg, 0, 0, $imageSize['width'], $bg_height, $bgcolor);
		//将源图片复制到目标图层中
		imagecopyresampled($this->_oDstImg, $this->_oSource, 0, 0, 0, 0, $imageSize['width'], $imageSize['height'], $imageSize['width'], $imageSize['height']);
		imagettftext($this->_oDstImg, $size, 0, $x, $y, $oColor, LIB_PATH.'font/jiankai.ttf', $text);
	}
	
	/*
	+ 把当前目标图片对象作为源对象
	*/
	public function setDstAsSource($type='image/jpg'){
		if(!is_resource($this->_oDstImg)){
			return false;
		}
		$this->_oSource = $this->_oDstImg;
		$this->_imageSize = $this->getSizeBySource($this->_oSource, $type);
	}
	
	//旋转
	public function rotate($angle){
		//背景色为白色
		$bg = imagecolorallocate($this->_oDstImg, 255, 255,255);
		$this->_oDstImg = imagerotate($this->_oDstImg, $angle, $bg);
	}
	
	//图像亮度
	public function brightness($value){
		$this->filter(IMG_FILTER_BRIGHTNESS ,$value);
	}
	
	//图像对比度
	public function contrast($value){
		$this->filter(IMG_FILTER_CONTRAST ,$value);
	}
	 
	//反相处理
	public function negate(){
		$this->filter(IMG_FILTER_NEGATE);
	}
	
	//转为灰度图
	public function grayscale(){
		$this->filter(IMG_FILTER_GRAYSCALE);
	}
	
	//浮雕
	public function emboss(){
		$this->filter(IMG_FILTER_EMBOSS);
	}
	
	//高斯模糊
	public function gaussian_blur(){
		$this->filter(IMG_FILTER_GAUSSIAN_BLUR);
	}
	
	//光滑
	public function smooth($value){
		imagefilter($this->_oDstImg, IMG_FILTER_SMOOTH, $value);
	}
	
	//滤镜效果
	public function filter($filtertype, $arg1=null, $arg2=null, $arg3=null){
		imagefilter($this->_oDstImg, $filtertype, $arg1, $arg2, $arg3);
	}
	
	//保存为文件
	public function save($new_name){
		return $this->imageSetup($this->_imageSize['mimetype'], $this->_oDstImg, $new_name);
	}
	
	//下载图片
	public function download($filename){
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=".basename($filename)); 
		$this->imageSetup($this->_imageSize['mimetype'], $this->_oDstImg);
		imagedestroy($this->_oDstImg);
		exit;
	}
	
	//输出图片（直接显示）
	public function output($filename='', $send_header = true){
		if($send_header){
			header("Expires: -1");
			header("Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE);
			header("Pragma: no-cache");
			header("Content-type:".$this->_imageSize['mimetype']."\r\n");
			if(!empty($filename)){
				header("Content-Disposition: inline; filename=".basename($filename));
			}
		}
		$this->imageSetup($this->_imageSize['mimetype'], $this->_oDstImg);
		imagedestroy($this->_oDstImg);
	}
	
	protected function getSource($mimetype='', $imageName=''){
		$_oSource = '';
		if($imageName && $mimetype){
			switch($mimetype){
				case "image/gif":
					$_oSource=imagecreatefromgif($imageName);
				break;
				case "image/pjpeg":
				case "image/jpeg":
				case "image/jpg":
					$_oSource=imagecreatefromjpeg($imageName); 
				break;
				case "image/png":
				case "image/x-png":
					$_oSource=imagecreatefrompng($imageName); 
				break;
			}
		}
		else{
			$_oSource = imagecreatefromstring($this->_imageString);
		}
		return $_oSource;
	}
	
	//生成图片
	protected function imageSetup($mimetype, $desImg, $thumb_image_name=null){
		switch($mimetype) {
			case "image/gif":
				if(!empty($thumb_image_name))
	  				imagegif($desImg, $thumb_image_name);
				else
					imagegif($desImg);
			break;
      		case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				if(!empty($thumb_image_name))
	  				imagejpeg($desImg, $thumb_image_name,90);
				else
					imagejpeg($desImg);
			break;
			case "image/png":
			case "image/x-png":
				if(!empty($thumb_image_name))
					imagepng($desImg, $thumb_image_name);
				else
					imagepng($desImg);
			break;
		}
		if($thumb_image_name){
			chmod($thumb_image_name, 0777);
		}
		return $thumb_image_name;
	}
}
?>