<?php
/*
Version: 1.0.1 Stable
*/
class Pic {
	private $im;
	private $standart = [
		'MIME' => 'image/jpeg',
		'PNGlvl' => 7,
		'JPEGlvl' => 75,
		'resizetype' => 'stretch'
	];
	private $size = [null, null];
	
	public function __construct($src = null) {
		if($src!==null)
			$this->load($src);
	}
	
	public function getErr():?string{//Получение последней ошибки
		return $this->err;
	}
	
	public function load(string $src):bool {//подгрузка ЛОКАЛЬНОЙ картинки (адрес)
		if(!file_exists($src)){
			$this->err = 'Image not found';
			return false;
		}
		if($this->im!==null){
			imagedestroy($this->im);
		}
		$this->im = null;
		$this->err = null;
		$mime = mime_content_type($src);
		switch($mime){
			case('image/jpeg'):
				$this->im = imagecreatefromjpeg($src);
				break;
			case('image/png'):
				$this->im = imagecreatefrompng($src);
				break;
			case('image/gif'):
				$this->im = imagecreatefromgif($src);
				break;
			default:
				$this->err = 'Not supported MIME-type ('.$mime.') in function load';
				return false;
		}
		if(!$this->im){
			$this->im = null;
			$this->err = 'Image not be loaded';
			return false;
		}
		$this->updateSize();
		return true;
	}
	
	public function loadURL($url, $tmp_file=null){//Загрузка сторонней картинки
		if(!filter_var($url, FILTER_VALIDATE_URL)){
			$this->err = 'url to load not valid';
			return false;
		}
		if($tmp_file==null){
			$i = 1;
			while(file_exists($i.'.tmp'))
				$i++;
			$tmp_file = $i.'.tmp';
		}
		$f = fopen($tmp_file, 'w');
		$r = curl_init();
		curl_setopt_array($r, array(CURLOPT_URL => $url, CURLOPT_FILE => $f, CURLOPT_HEADER => false));
		curl_exec($r);
		curl_close($r);
		fclose($f);
		$this->load($tmp_file);
		unlink($tmp_file);
	}
	
	public function save(string $src,?string $mime = null,?int $level = null):bool {//сохранение картинки (путь_сохранения, MIME-тип, качество)
		if($this->im === null){
			$this->err = 'Image is NULL';
			return false;
		}
		switch($mime??$this->standart['MIME']){
			case('image/jpeg'):
				$r = imagejpeg($this->im, $src, $level??$this->standart['JPEGlvl']);
				if(!$r){
					$this->err = 'ImageJPEG not be saved';
					return false;
				}
				break;
			case('image/png'):
				$r = imagepng($this->im, $src, $level??$this->standart['PNGlvl']);
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
				break;
			default:
				$this->err = 'Not supported MIME-type ('.($mime??$this->standart['MIME']).') in function save';
				return false;
		}
		return true;
	}
	
	public function resize(?int $width,?int $height,?string $type = null,array $bg_color = [255, 255, 255]):bool {
		//Изменение размера изображения (ширина, высота, тип_изменения [stretch;approx;upbuild], цвет [r, g, b])
		if($this->im === null){
			$this->err = 'Image is NULL';
			return false;
		}
		$width = $width??$this->size[0];
		$height = $height??$this->size[1];
		$im = imagecreatetruecolor($width, $height);
		$bg = imagecolorallocate($im, $bg_color[0], $bg_color[1], $bg_color[2]);
		imagefill($im, 0, 0, $bg);
		switch($type??$this->standart['resizetype']){
			case('stretch'):
				imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, $this->size[0], $this->size[1]);
				break;
			case('approx'):
				$c1 = $this->size[0]/$this->size[1];
				$c2 = $width/$height;
				switch($c1<=>$c2){
					case(1):
						$wr = $this->size[1]*$width/$height;
						imagecopyresized($im, $this->im, 0, 0, (int)(($this->size[0]-$wr)/2), 0, $width, $height, $wr, $this->size[1]);
						break;
					case(0):
						imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, $this->size[0], $this->size[1]);
						break;
					case(-1):
						$hr = $this->size[0]*$height/$width;
						imagecopyresized($im, $this->im, 0, 0, 0, (int)(($this->size[1]-$hr)/2), $width, $height, $this->size[0], $hr);
						break;
				}
				break;
			case('upbuild'):
				$c1 = $this->size[0]/$this->size[1];
				$c2 = $width/$height;
				switch($c1<=>$c2){
					case(1):
						$hr = (int)($this->size[1]*$width/$this->size[0]);
						imagecopyresized($im, $this->im, 0, (int)(($height-$hr)/2), 0, 0, $width, $hr, $this->size[0], $this->size[1]);
						break;
					case(0):
						imagecopyresized($im, $this->im, 0, 0, 0, 0, $width, $height, $this->size[0], $this->size[1]);
						break;
					case(-1):
						$wr = $this->size[0]*$height/$this->size[1];
						imagecopyresized($im, $this->im, (int)(($width-$wr)/2), 0, 0, 0, $wr, $height, $this->size[0], $this->size[1]);
						break;
				}
				break;
			default:
				$this->err = 'Not supported resize type ('.($type??$this->standart['resizetype']).')';
				return false;
		}
		imagedestroy($this->im);
		$this->im = $im;
		unset($im);
		return true;
	}

	public function qSave(array $saves,string $dir = '',?string $type = null):void {//Быстрое сохранение копий изображение с разными размерами в одной папке
		for($i=0;$i<count($saves);$i++){
			if($saves[$i][0]!==null){
				$im1 = clone $this;
				$im1->resize($saves[$i][0][0], $saves[$i][0][1], $saves[$i][0][2]??'stretch', $saves[$i][0][3]??[255, 255, 255]);
				$im1->save($dir.$saves[$i][1], $saves[$i][2]??$type, $saves[$i][3]??null);
				unset($im1);
			} else {
				$this->save($dir.$saves[$i][1], $saves[$i][2]??$type, $saves[$i][3]??null);
			}
		}
	}
	
	public function setIm($im):void {
		$this->im = $im;
	}
	
	public function imageInfo():array {
		return $this->size;
	}
	
	private function updateSize():void {
		$this->size[0] = imagesx($this->im);
		$this->size[1] = imagesy($this->im);
	}

	public function __clone() {//Возврощает копию данного объекта
		$im = $this;
		$imim = imagecreatetruecolor($this->size[0], $this->size[1]);
		imagecopy($imim, $this->im, 0, 0, 0, 0, $this->size[0], $this->size[1]);
		$im->setIm($imim);
		return $im;
	}
	
	public function __destruct() {
		if($this->im !== null)
			imagedestroy($this->im);
		unset($this->standart);
		unset($this->size);
	}
}
