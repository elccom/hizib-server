<?php
//▒▒	파일 이름을 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getFileName($file_name) {
    $temp = explode("/", $file_name);
    
    return $temp[count($temp) - 1];
}

//▒▒	파일 사이트를 이쁘게 변경하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getFileSize($size) {
    if(!$size) return "0 Byte";
    if($size<1024) {
        return ($size." Byte");
    } elseif($size >1024 && $size< 1024 *1024)  {
        return sprintf("%0.2f KB",$size / 1024);
    }
    else return sprintf("%0.2f MB",$size / (1024*1024));
}

//▒▒	파일 확장자를 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getFileType($file_name) {
    $temp_file = explode(".", $file_name);
    $temp_file_num = count($temp_file)-1;
    $file_type = $temp_file[$temp_file_num];
    
    return $file_type;
}

//▒▒	자동으로 디렉토르를 만드는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function makeDirectory($path) {
    $temp = explode('/', $path);
    
    $path = '';
    for($i=1; $i<count($temp); $i++) {
        $path .= '/'.$temp[$i];
        //echo $path;
        if(!file_exists($path) && !preg_match('/\./', $temp[$i])) {
            //if(!is_writable($path)) throw new Exception($path." 폴더 쓰기 권한이 없습니다.");
            //else mkdir($path, 0777);

            if(!mkdir($path, 0777)) throw new Exception($path." 폴더 생성에 실패하였습니다.");
        }
    }
    
    return true;
}

//▒▒	파일을 삭제하는 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function rmUtil($filename) {
    if(!file_exists($filename)) return;
    
    @chmod($filename,0777);
    if(is_file($filename)) unlink($filename);
    else {
        $dir = opendir($filename);
        while($file = @readdir($dir)) {
            if($file == '.' || $file == '..') continue;
            elseif(is_file($filename.'/'.$file)) unlink($filename.'/'.$file);
            elseif(is_dir($filename.'/'.$file)) rmUtil($filename.'/'.$file);
        }
        @closedir($dir);
        @rmdir($filename);
    }
    
    
    if(!file_exists($filename)) return true;
    else return false;
}

//▒▒	지정된 디렉토리의 파일 정보를 구함		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function lsUtil($path) {
    $handle=@opendir($path);
    if(!$handle) return array();
	$dir = [];
    while($info = readdir($handle)) {
        if($info != "." && $info != "..") {
            $dir[] = $info;
        }
    }
    @closedir($handle);
    return $dir;
}

//▒▒	지정된 파일의 내용을 읽어옴		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function readFileUtil($filename) {
    if(!file_exists($filename)) return '';
    
    $f = fopen($filename,"r");
    $str = fread($f, filesize($filename));
    fclose($f);
    
    return $str;
}

//▒▒	지정된 파일에 주어진 데이타를 씀		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function writeFileUtil($filename, $str) {
	$path = str_replace(getFileName($filename), '', $filename);
	if(!is_dir($path)) {
	    try {
	       makeDirectory($path);
	    } catch(Exception $e) {
	        throw $e;
	    }
	}
	
    $f = fopen($filename,"w");
    if(!$f) return false;
    $lock = flock($f,2);
    if($lock) {
        fwrite($f,$str);
    }
    flock($f,3);
    fclose($f);
    
    return true;
}

//▒▒	로그를 남기는 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function logWrite($msg) {
    global $_lib;
    
    $log_file = fopen($_lib['directory']['home']."/log/".date("Ymd").".txt", "a");
    fwrite($log_file, $msg."\r\n");
    fclose($log_file);
}

//▒▒	zip파일만들기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function createZip($files = array(), $destination = '', $overwrite = false) {
    if(file_exists($destination) && !$overwrite) { return false; }
    
    $validFiles = [];
    if(is_array($files)) {
        foreach($files as $file) {
            if(file_exists($file)) {
                $validFiles[] = $file;
            }
        }
    }
    
    if(count($validFiles)) {
        $zip = new ZipArchive();
        if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
            return false;
        }
        
        foreach($validFiles as $file) {
            $zip->addFile($file,$file);
        }
        
        
        $zip->close();
        return file_exists($destination);
    } else {
        return false;
    }
}

//▒▒	이미지파일만들기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function createImage($path, $info=null, $width=0, $height=0, $background=[]) {
	if(!file_exists($path)) throw new Exception("존재하지 않는 파일(".$path.")입니다.");
	if(empty($info)) $info = getimagesize($path);

	if($width <= 0) $width = $info[0];
	if($height <= 0) $height = $info[1];

	if($info['mime'] == "image/jpeg") $src = imagecreatefromjpeg($path); 
	elseif($info['mime'] == "image/png") $src = imagecreatefrompng($path); 
	elseif($info['mime'] == "image/gif") $src = imagecreatefromgif($path); 
	elseif($info['mime'] == "image/bmp") $src = imagecreatefromwbmp($path);
	else throw new Exception("지원하지 않는 파일 타입(".$info['mime'].")입니다.");

	$img = imagecreatetruecolor($width, $height);
	
	if(count($background) == 3) {
		$color = imagecolorallocate($img, $background[0], $background[1], $background[2]);
		imagefilledrectangle($img, 0, 0, $width, $height, $color);
	}

    imagecopyresampled($img, $src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

	return $img;
}

//▒▒	이미지파일만들기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function saveImage($type, $image, $path, $name="") {
	if(check_blank($name)) $name = time();
	if(check_blank($path)) throw new Exception("디렉토리 정보가 없습니다.");

	if(!makeDirectory($path)) throw new Exception($path." 디렉토리를 생성하는데 실패하였습니다.");

	if($type == "image/jpeg") $obj = imagejpeg($image, $path."/".$name.".jpg");
	elseif($type == "image/png") $obj = imagepng($image, $path."/".$name.".png");
	elseif($type == "image/gif") $obj = imagegif($image, $path."/".$name.".gif");
	elseif($type == "image/bmp") $obj = imagebmp($image, $path."/".$name.".bmp");
	else throw new Exception("지원하지 않는 파일 타입입니다.");

	if(file_exists($path)) return true;
	else return false;
}


//▒▒	일정시간이 지난 파일 삭제		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function deletePathFileByTime($dir,$del_time) {
	/*
	if (is_dir($dir)) {
		$files = [];
		$dh  = @opendir($dir);
		while (false !== ($filename = @readdir($dh))) {
			if($filename != '.' && $filename != '..') array_push($files, $filename);
		}

		if(count($files))  {
			sort($files);
			
			$now = time();
			for($i=0; $i<count($files); $i++){

				$ftime = @filemtime($dir.'/'.$files[$i]) + $del_time;

				if ($ftime <= $now) {
					if(is_file($dir.'/'.$files[$i])) @unlink($dir.'/'.$files[$i]);
					else rmUtil($dir.'/'.$files[$i]);
				}
			}
			
			@closedir($dh);
		}
    }
	*/
}

function deletePathFileAll($dir, $del_time = 60) {
	if (is_dir($dir)) {
		$files = [];
		$dh  = @opendir($dir);
		$now = time();
		while (false !== ($filename = @readdir($dh))) {
			if($filename != '.' && $filename != '..') {
				if(is_dir($dir."/".$filename)) {
					$dh2  = @opendir($dir."/".$filename);

					while (false !== ($filename2 = @readdir($dh2))) {
						if($filename2 != '.' && $filename2 != '..') {
							$ftime = @filemtime($dir."/".$filename."/".$filename2) + $del_time;
							if(!is_dir($dir."/".$filename."/".$filename2) && $ftime <= $now) array_push($files, $dir."/".$filename."/".$filename2);
						}
					}

					@closedir($dh2);
				}

				$ftime = @filemtime($dir."/".$filename) + $del_time;
				if($ftime <= $now) array_push($files, $dir."/".$filename);
			}
		}

		@closedir($dh);
		
		if(count($files))  {			
			for($i=0; $i<count($files); $i++) {
				rmUtil($files[$i]);
			}
		}
    }
}

//▒▒	특정 패턴의 파일을 include하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function includeFile($path, $pattern) {
	global $_lib;

	if(is_dir($path)) {
		if($folder = opendir($path)) {
			while($f = readdir($folder)) {
				if(preg_match($pattern, $f)) {
					//echo $path."/".$f."<br>";
					include($path."/".$f);
				}
			}
		} else throw new Exception($path.' 폴더에 접근하는데 실패하였습니다.');			
	} else throw new Exception('존재하지 않는 폴더입니다.');
}
?>