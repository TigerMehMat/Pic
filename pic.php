<?php
class Pic {
	private $im;
	
	public function __construct(){
		$this->im = null;
		$this->err = false;
	}
	
	public function getErr(){//Получение последней ошибки
		return $this->err;
	}
	
	public function load($src){//подгрузка ЛОКАЛЬНОЙ картинки (адрес)
		if(!file_exists($src)){
			$this->err = 'Image not found';
			return false;
		}
		if($this->im!==null)
			imagedestroy($this->im);
		$mime = mime_content_type($src);
		switch($mime){
			case('image/jpeg'):
				$this->im = imagecreatefromjpeg($src);
				break;
			case('image/png'):
				$this->im= imagecreatefrompng($src);
				break;
			case('image/gif'):
				$this->im= imagecreatefromgif($src);
				break;
			default:
				$this->err = 'Not supported MIME-type ('.$mime.')';
				return false;
		}
		return true;
	}
	
	public function save($src, $mime = 'image/jpeg', $level = null){//сохранение картинки (путь_сохранения, MIME-тип, качество)
		if($this->im === null){
			$this->err = 'Image is NULL';
			return false;
		}
		switch($mime){
			case('image/jpeg'):
				$r = imagejpeg($this->im, $src, $level ?? 75);
				if(!$r){
					$this->err = 'ImageJPEG not be saved';
					return false;
				}
				break;
			case('image/png'):
				$r = imagepng($this->im, $src, $level ?? 8);
				if(!$r){
					$this->err = 'ImagePNG not be saved';
					return false;
				}
				break;
			case('image/gif'):
				$r = imagegif($this->im, $src);
				if(!$r){
					$this->err = 'ImageGIF not be saved';
					return false;
				}
			default:
				return false;
		}
		return true;
	}
	
	public function resize($width, $height, $type = 'stretch', $bg_color = [255, 255, 255]){//Изменение размера изображения (ширина, высота, тип_изменения [stretch;inc;gain], цвет)
		if($this->im === null){
			$this->err = 'Image is NULL';
			return false;
		}
		$im = imagecreatetruecolor($width, $height);
		$bg = imagecolorallocate($im, $bg_color[0], $bg_color[1], $bg_color[2]);
		imagefill($im, 0, 0, $bg);
		switch($type){
			case('stretch'):
				imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, imagesx($this->im), imagesy($this->im));
				break;
			case('approx'):
				$c1 = imagesx($this->im)/imagesy($this->im);
				$c2 = $width/$height;
				switch($c1<=>$c2){
					case(1):
						$wr = imagesy($this->im)*$width/$height;
						imagecopyresized($im, $this->im, 0, 0, (int)((imagesx($this->im)-$wr)/2), 0, $width, $height, $wr, imagesy($this->im));
						break;
					case(0):
						imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, imagesx($this->im), imagesy($this->im));
						break;
					case(-1):
						$hr = imagesx($this->im)*$height/$width;
						imagecopyresized($im, $this->im, 0, 0, 0, (int)((imagesy($this->im)-$hr)/2), $width, $height, imagesx($this->im), $hr);
						break;
				}
				break;
			case('upbuild'):
				$c1 = imagesx($this->im)/imagesy($this->im);
				$c2 = $width/$height;
				switch($c1<=>$c2){
					case(1):
						$hr = (int)(imagesy($this->im)*$width/imagesx($this->im));
						imagecopyresized($im, $this->im, 0, (int)(($height-$hr)/2), 0, 0, $width, $hr, imagesx($this->im), imagesy($this->im));
						break;
					case(0):
						imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, imagesx($this->im), imagesy($this->im));
						break;
					case(-1):
						$wr = imagesx($this->im)*$height/imagesy($this->im);
						imagecopyresized($im, $this->im, (int)(($width-$wr)/2), 0, 0, 0, $wr, $height, imagesx($this->im), imagesy($this->im));
						break;
				}
				break;
			default:
				return false;
		}
		imagedestroy($this->im);
		$this->im = $im;
		unset($im);
		return true;
	}
	
	/*public function fastSave($saves, $dir = false, $type = false){//Быстрое сохранение копий изображение с разными размерами в одной папке
		for($i=0;$i<count($saves);$i++){
			
		}
	}
	*/
	public function copy(){//Возврощает копию данного объекта
		return clone $this;
	}
	
	public function __destruct(){
		if($this->im !== null)
			imagedestroy($this->im);
	}
}
