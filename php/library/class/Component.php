<?php
class Component {
    var $__lib__;
    var $__tableName__;
    var $__pkName__;
    var $__pkValue__;
    var $__errorCode__;
    var $__errorMsg__;
    var $__columns__;
    
    public function __construct($isInstall=true) {
        global $_lib;
        
        foreach($this -> __columns__ as $column) {
            if(isset($column -> field) && isset($column -> default)) $this -> setField($column -> field, $column -> type, $column -> default);
            elseif(isset($column -> field) && !isset($column -> default)) $this -> setField($column -> field, $column -> type, '');
        }
        /*
        if($isInstall && !$this -> install()) {
            echo $this -> __errorMsg__;
            exit();
        }
		*/
    }
  
	public function get($name, $json, $isFlag=true) {
		$value = '';
		if(isset($_REQUEST[$name])) $value = $_REQUEST[$name];
		if(isset($json -> $name)) $value = $json -> $name;

		return $value;
	}

	public function getJson($json="") {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
        
        foreach($this -> __columns__ as $column) {
            $key = $column -> field;
			if($this -> __pkName__ == $key) $json -> $key = $this -> __pkValue__;
			elseif(!empty($this -> $key)) $json -> $key = $this -> $key;
        }

		return $json;
    }
	
	public function getFields($label) {
        $result = '';
        
        foreach($this -> __columns__ as $column) {
            if(!check_blank($result)) $result .= ',';
            
            if(check_blank($label)) $result .= $column['field'];
            else $result .= $label.'.'.$column -> field.' AS '.$label.'_'.$column -> field;
            
        }
        
        return $result;
    }
    
    public function getDataByCondition($condition, $isEcho=false) {
		global $_lib;

        $json = new stdClass();
        $json -> where = $condition;
        $json -> row = 1;

		$data = $this -> getResult($json, $isEcho);
		$this -> setData($data);
		
		/*
		$key = "{".$this -> __tableName__."}^{".$condition."}";

		try {
			$value = $this -> getRedis($key);
		} catch(Exception $e) {
			$value = "";
		}
		
		if(empty($value)) {
			$data = $this -> getResult($json, $isEcho);
			$this -> setData($data);

			try {
				$this -> setRedis($key, $data);
			} catch(Exception $e) {
			}
		} else {
			$this -> setData($value);
		}
		*/
    }

	function getPkValueByCondition($condition) {
		$query = new stdClass();
		$query -> field = $this -> __pkName__;
		$query -> where = $condition;
		$query -> row = 1;

		$results = $this -> getResults($query);
		if($results -> num_rows <= 0) return 0;

		$data = $results -> fetch_array();
		return $data[0];
	}
    
    public function getData($pkValue="", $isEcho=false) {
		global $_lib;

        if($pkValue == '') $pkValue = getVars($this -> __pkName__);

		$this -> getDataByCondition($this -> __pkName__.'='.(int)$pkValue, $isEcho);
		
		/*
		try {
			$key = '{'.$_lib['website'] -> name.'}^{'.$this -> __tableName__.'}^{'.$pkValue.'}';
			$json = $this -> getRedis($key);

			if(empty($json)) {
				$this -> getDataByCondition($this -> __pkName__.'='.(int)$pkValue);
				$this -> setRedis($key, $this -> toJson());
			} else $this -> setJson($json);
		} catch(Exception $e) {
			$this -> getDataByCondition($this -> __pkName__.'='.(int)$pkValue);
		}
		*/
    }

	public function getRedis($key) {
		global $_lib;

		if(empty($_lib['config']['redis']['host'])) throw new Exception("Redis 호스트 정보가 없습니다.");
		if(empty($_lib['config']['redis']['port'])) throw new Exception("Redis 포트 정보가 없습니다.");

		if(!isset($_lib['redis'])) $_lib['redis'] = new Redis();
		$_lib['redis'] -> connect($_lib['config']['redis']['host'], $_lib['config']['redis']['port'], 2.5, NULL, 150);
		
		return jsondecode($_lib['redis'] -> get($key));
	}

	public function setRedis($key, $value, $pkValue=0) {
		global $_lib;

		if(empty($_lib['config']['redis']['host'])) throw new Exception("Redis 호스트 정보가 없습니다.");
		if(empty($_lib['config']['redis']['port'])) throw new Exception("Redis 포트 정보가 없습니다.");

		if(!isset($_lib['redis'])) $_lib['redis'] = new Redis();
		$_lib['redis'] -> connect($_lib['config']['redis']['host'], $_lib['config']['redis']['port'], 2.5, NULL, 150);

        return $_lib['redis'] -> set($key, $value);
	}
    
    public function getDataAtRecent($isEcho=false) {
        $json = new stdClass();
        $json -> orderby = $this -> __pkName__." desc";
        $json -> row = 1;
        
        //print_r($json);
        
        $data = $this -> getResult($json, $isEcho);
        
        $this -> setData($data);
    }

    function getCloudFiles($path) {
        $cloudFolderObj = new CloudFolder();
        $cloudFolderObj -> getDataAllByPath($path);

		if($cloudFolderObj -> __pkValue__ <= 0) $cloudFolderObj -> mkdir($path);
		if($cloudFolderObj -> __pkValue__ <= 0) return [];

        return $cloudFolderObj -> getFiles();
    }

	function getFileUrlByIndex($path, $index=0) {
		$files = $this -> getCloudFiles($path);

		if($index > count($files)) return '';

		return $files[$index] -> getUrl();
	}

	public function getLastData($isEcho=false) {
        $json = new stdClass();
        $json -> orderby = $this -> __pkName__." DESC";
        $json -> row = 1;
        
        //print_r($json);
        
        $data = $this -> getResult($json, $isEcho);
        
        $this -> setData($data);
    }
    
    public function getTotal($condition='', $isEcho=false) {
        $json = new stdClass();
        $json -> field = 'count(*)';
        if(!check_blank($condition)) $json -> where = $condition;
        
        $data = $this -> getResult($json, $isEcho);

        return $data[0];
    }
    
    public function getMax($field, $condition='', $isEcho=false) {
        $json = new stdClass();
        $json -> field = 'MAX('.$field.')';
        if(!check_blank($condition)) $json -> where = $condition;
        
        $data = $this -> getResult($json, $isEcho);
        
        return $data[0];
    }
    
    public function getSum($field, $condition='', $isEcho=false) {
        $json = new stdClass();
        $json -> field = 'SUM('.$field.')';
        if(!check_blank($condition)) $json -> where = $condition;
        
        $data = $this -> getResult($json, $isEcho);
        
        return $data[0];
    }
    
    public function getDate($field) {
        if(check_blank($this -> $field)) return '';
        
        $temp = explode(" ", $this -> $field);
        
        if($temp[0] == '9999-12-31') return '';
        else return $temp[0];
    }
    
    public function getTime($field) {
        if(check_blank($this -> $field)) return '';
        
        $temp = explode(" ", $this -> $field);
        
        if($temp[0] == '9999-12-31') return '';
        else return $temp[1];
    }
    
    public function getDday($field) {
        if(check_blank($this -> $field)) return '';
        
        $dday = getTimestamp($this -> $field);
		$now = time();

		$rest = $dday - $now;
        
        return $rest;
    }
    
    public function getYear($field) {
        if(check_blank($this -> $field)) return '';
        
        $this -> $field = str_replace('년', '-', str_replace('월', '-', str_replace('일', '', $this -> $field)));
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode("-", $temp[0]);
        
        if(count($tmp) != 3) return '';
        
        return $tmp[0];
    }
    
    public function getMonth($field) {
        if(check_blank($this -> $field)) return '';
        
        $this -> $field = str_replace('년', '-', str_replace('월', '-', str_replace('일', '', $this -> $field)));
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode("-", $temp[0]);
        
        if(count($tmp) != 3) return '';
        
        return $tmp[1];
    }
    
    public function getDay($field) {
        if(check_blank($this -> $field)) return '';
        
        $this -> $field = str_replace('년', '-', str_replace('월', '-', str_replace('일', '', $this -> $field)));
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode("-", $temp[0]);
        
        if(count($tmp) != 3) return '';
        
        return $tmp[2];
    }
    
    public function getHour($field) {
        if(check_blank($this -> $field)) return '';
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode(":", count($temp) > 1 ? $temp[1] : $temp[0]);
        
        return $tmp[0];
    }
    
    public function getMin($field) {
        if(check_blank($this -> $field)) return '';
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode(":", count($temp) > 1 ? $temp[1] : $temp[0]);
        
        return $tmp[1];
    }
    
    public function getSec($field) {
        if(check_blank($this -> $field)) return '';
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode(":", count($temp) > 1 ? $temp[1] : $temp[0]);
        
        return $tmp[2];
    }
    
    public function getUnixTimestamp($field) {
        if(check_blank($this -> $field)) return '';
        
        $this -> $field = str_replace('년', '-', str_replace('월', '-', str_replace('일', '', $this -> $field)));

		//echo $this -> $field;
        
        $temp = explode(" ", $this -> $field);
        $tmp = explode("-", $temp[0]);
        $year = $tmp[0];
        $month = $tmp[1];
        $day = $tmp[2];

		if(count($temp) == 2) {
			$tmp = explode(":", $temp[1]);
			$hour = $tmp[0];
			$min = $tmp[1];
			$sec = $tmp[2];
        } else {
			$hour = 0;
			$min = 0;
			$sec = 0;
		}

        return mktime($hour, $min, $sec, $month, $day, $year);
    }

	function getIpTime() {
		global $_lib;

		return  str_replace('.', '_', $_SERVER['REMOTE_ADDR']).'|'.time();
	}

	//디바이스 종류 가져오기
	function getDevice() {
		if(stristr($_SERVER['HTTP_USER_AGENT'],'ipad')) {
			$device ="ipad";
		} else if(stristr($_SERVER['HTTP_USER_AGENT'],'iphone') || strstr($_SERVER['HTTP_USER_AGENT'],'iphone')) {
			$device ="iphone";
		}else if(stristr($_SERVER['HTTP_USER_AGENT'],'blackberry')) {
			$device ="blackberry";
		}else if(stristr($_SERVER['HTTP_USER_AGENT'],'android')) {
			$device ="android";
		}else {
			$device ="pc";
		}

		return $device;
	}

	function getObj($json='') {
		$pkValue = $this -> get($this -> __pkName__, $json);
		
		$this -> getDataAll($pkValue);

		return $this;
	}

	function getTxt($txt, $pattern) {
		//echo "--------------".$txt."---------------- 검사패턴[".$pattern."]<br>";

		if(strpos($pattern, '/') !== false) {
			$temp = explode("/", $pattern);
			$tmp = explode(" ", $txt);

			$index = 0;
			$array = array();
			
			for($i=0; $i<count($tmp); $i++) {
				//echo "[어절]".$i.":".$tmp[$i];
				//echo "<br>[검사] /".$temp[$index]."/";
				//echo "<br>[변환] /".str_replace("*", "(.*?)", $temp[$index])."/";

				if(preg_match("/".str_replace("*", "(.*?)", $temp[$index])."/", $tmp[$i])) {
					array_push($array, $tmp[$i]);

					for($j=$index+1; $j<count($temp); $j++) {
						//echo "<br>[세부]".$j." : ".$temp[$j]." / ".str_replace("*", "(.*?)", $tmp[$i + $j])."<br>";
						if(!preg_match("/".$temp[$j]."/", str_replace("*", "(.*?)", $tmp[$i + $j]))) $array = array();
						else array_push($array, $tmp[$i + $j]);
					}

					return implode(" ", $array);
				}
				//echo "<br>";
			}
		} 
		
		if(strpos($pattern, '*') !== false) {
			if(strpos($pattern, '*') !== false) $pattern = str_replace("*", "(.*?)", $pattern);

			preg_match('/'.$pattern.'/', $txt, $temp);

			if(count($temp)) return $temp[0];

			return '';
		} else {

			preg_match('/'.$pattern.'/', $txt, $temp);

			if(count($temp)) return $temp[0];

			return '';
		}
	}

	function getTag($html, $pattern) {	
		return getTag($html, $pattern);
	}

	function getSaleRate($price='price', $sale='sale') {
		$p = $this -> $price;
		$s = $this -> $sale;

		return (int)(($p - $s) / $p * 100);
	}

	function isIncludeTxt($txt, $pattern) {
		//echo "[isIncludeTxt] ".$txt.",".$pattern."<br>";
		// 슬래쉬가 포함된 경우, 순서 고려

		if(strpos($pattern, "/") !== false) {
			$temp = explode("/", $pattern);
			$tmp = explode(" ", $txt);

			$index = 0;
			
			for($i=0; $i<count($tmp); $i++) {
				//echo "[".$pattern."]<br>".$i.":".$tmp[$i];
				//echo "<br>[검사] /".$temp[$index]."/";
				
				//순서대로 일치하는 패턴인지 조회
				if($this -> isIncludeTxt($tmp[$i], $temp[$index])) {
					for($j=$index; $j<count($temp); $j++) {
						//echo "[세부]".$j." : ".$temp[$j]." / ".$tmp[$i + $j]."<br>";
						if(!$this -> isIncludeTxt($tmp[$i + $j], $temp[$j])) return false;
					}

					return true;
				}
				//echo "<br>";
			}
		} else if(strpos($pattern, "|") !== false) {
			$temp = explode("|", $pattern);
			for($i=0; $i<count($temp); $i++) {
				if($this -> isIncludeTxt($txt, $temp[$i])) return true;
			}

			return false;
		} else {
			$pattern = str_replace("(", "\(", $pattern);
			$pattern = str_replace(")", "\)", $pattern);

			if(preg_match("/".$pattern."/", $txt)) return true;
		}

		return false;
	}

	function removeTag($html, $pattern) {
		$pattern = '#'.str_replace('*', '(.*?)', $pattern).'#';
		return preg_replace($pattern, '', $html);
	}

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
			} else {
				$this -> __errorMsg__ = $path.' 폴더에 접근하는데 실패하였습니다.';
				return false;			
			}
		} else {
            $this -> __errorMsg__ = '존재하지 않는 폴더입니다.';
            return false;			
		}

		return true;
	}
	
	//rest api를 curl로 요청하는 메소드
	function requestRestapi($options, $debug=false) {
		if(!isset($options)) throw new Exception("curl 옵션 정보가 없습니다.");

		$ch = curl_init();
		//print_r($options);

		curl_setopt_array($ch, $options); 
		$response = curl_exec($ch); 
		
		if($debug) {
			var_dump($response); //결과값 출력
			print_r(curl_getinfo($ch)); //모든정보 출력
			echo curl_errno($ch); //에러정보 출력
			echo curl_error($ch); //에러정보 출력
		}
		curl_close($ch);
		return $response;
	}

	//rest api를 소켓으로 요청하믄 메소드
	function requestSocketRestapi($method, $url, $data, $options=array()) {
		$info = parse_url($url);
		//print_r($info);
		if(is_array($data)) $data = http_build_query($data);

		switch($info['scheme'] = strtoupper($info['scheme'])) {
			case 'HTTP':
				$info['port']   = 80;
				break;
	 
			case 'HTTPS':
				$info['port']   = 443;
				break;
	 
			default:
				throw new Exception("http://인지 https://인지 입력해 주세요.");
		}

		if(!$info['path']) $info['path'] = '/';

		$obj = new ElcSocket($info['host'], $info['port']);
		$obj -> debug = true;

		if(!$obj -> connect()) {
			$this -> __errorMsg__ = $info['host']." 호스트 연결실패!";
			return false;
		}
		
		$obj -> headers = array(
			'User-Agent' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0)',
		);

		foreach($options as $key => $value) {
			$obj -> headers[$key] = $value;
		}
		
		switch($method = strtoupper($method)) {
			case 'GET':
				$obj -> headers['Connection'] = 'Close';
				//echo $url."?".$data;
				$obj -> get(check_blank($data) ? $info['path'] : $info['path']."?".$data);
				break;
	 
			case 'POST':
				$obj -> headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
				$obj -> headers['Content-length'] = strlen($data);
				$obj -> post($info['path'], $data);
				break;
		}
		
		//echo "<xmp>";
		//print_r($obj -> body);
		//echo "</xmp>";

		//print_r($obj);

		return $obj -> body;
	}
	
	//부가세전 금액 구하기
	public function calcBeforeVat($p, $vat=10, $currency=1) {
		return $p - $this -> calcVat($p, $vat, $currency);
	}
	
	//부가세 구하기
	public function calcVat($p, $vat=10, $currency=1) {
		//세전
		$price = $p / (100 + $vat) * 100;
		if($this -> isIntCurrency($currency)) $price = (int)$price;

		//과세금액
		$vat = $price * $vat / 100;
		if($this -> isIntCurrency($currency)) $vat = (int)$vat;

		//세전금액
		return $vat;
	}

	public function setColumns($json='', $keys=array(), $reference=array(), $isFlag=true) {
		$this -> __pkValue__ = 0;
		$pkValue = $this -> get($this -> __pkName__, $json, $isFlag);
		if($pkValue) $this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			for($i=0; $i<count($keys); $i++) {
				$keyArray = explode(",", $keys[$i]);

				if($this -> __pkValue__ <= 0)  {
					$condition = "";
					for($j=0; $j<count($keyArray); $j++) {
						$field = trim($keyArray[$j]);

						//다른참조
						if(isset($reference[$field])) $name = $reference[$field];
						else $name = $field;

						//값
						$value = $this -> get($name, $json, $isFlag);

						if(!check_blank($condition)) $condition .= " AND ";
						$condition .= $name."='".$value."'";
					}

					$this -> getDataByCondition($condition);
				}
			}
		}

		foreach($this -> __columns__ as $column) {
			$field = $column -> field;

			//다른참조
			if(isset($reference[$field])) $name = $reference[$field];
			else $name = $field;
			
			//값
			$value = $this -> get($name, $json, $isFlag);

			//echo $field.":".$value.'<br>';

			//타입
			$fieldType = $column -> type;

			//문자열타입인데 배열이면
			if(strtoupper($fieldType) != 'JSON' && $this -> isColumnTypeStr($fieldType) && is_array($value)) {
				$value = implode("-", $value);
				if($value == '--') $value = "";
			} elseif($this -> isColumnTypeDate($fieldType) && !check_blank($value)) {
				$date = $value;
				$hour = $this -> get($name.'_hour', $json, $isFlag);
				$min = $this -> get($name.'_min', $json, $isFlag);
				$sec = $this -> get($name.'_sec', $json, $isFlag);

				if(check_blank($hour)) $hour = '00';
				if(check_blank($min)) $min = '00';
				if(check_blank($sec)) $sec = '00';

				$value = $date.' '.$hour.':'.$min.':'.$sec;
			}

			//if($field == 'data') echo $value;

			if(isset($_REQUEST[$name]) || isset($json -> $name)) $this -> setField($field, $fieldType, $value);
		}
	}
    	
    public function setData($data, $label='', $isEcho=false) {
        if(!is_array($data)) return;
        
        foreach($this -> __columns__ as $column) {
            if(check_blank($label)) $key = $column -> field;
            else $key = $label.'_'.$column -> field;
            
            if(!isset($data[$key])) {
				if(isset($column -> default) && $column -> default === null) $data[$key] = null;
				else if(isset($column -> default)) $data[$key] = $column -> default;
                else $data[$key] = '';
            }
            
			if($isEcho) {
				echo $key.":".$data[$key]."(".($data[$key] === null).")(".isset($data[$key]).")<br>";
			}

            $this -> setField($column -> field, $column -> type, $data[$key], $isEcho);
        }
    }
    
    public function setObj($obj) {
        foreach($this -> __columns__ as $column) {
            $key = $column -> field;
            //echo $key."<br>";
            
            if(isset($obj -> $key)) $this -> setField($column -> field, $column -> type, $obj -> $key);
        }

        $key = $obj -> __pkName__;
        if(isset($this -> $key)) $this -> $key = $obj -> __pkValue__;
    }
    
    public function setArguments($name="") {
        if(isset($_REQUEST[$this -> __pkName__])) $this -> getData($_REQUEST[$this -> __pkName__]);
        
        foreach($this -> __columns__ as $column) {
            $key = $column -> field;
            if(!check_blank($name)) $key = $name."[".$key."]";
            $keytype = $column -> type;
            
            if(isset($_REQUEST[$key]) && !check_blank($_REQUEST[$key]) && (isset($_REQUEST[$key.'_hour']) || isset($_REQUEST[$key.'_min']) || isset($_REQUEST[$key.'_sec']))) {
                $hour = isset($_REQUEST[$key.'_hour']) ? $_REQUEST[$key.'_hour'] : 0;
                $min = isset($_REQUEST[$key.'_min']) ? $_REQUEST[$key.'_min'] : 0;
                $sec = isset($_REQUEST[$key.'_sec']) ? $_REQUEST[$key.'_sec'] : 0;
                
                $value = $_REQUEST[$key]." ".sprintf("%02d", $hour).":".sprintf("%02d", $min).":".sprintf("%02d", $sec);
                $this -> setField($key, $keytype, $value);
            } elseif(isset($_REQUEST[$key])) $this -> setField($key, $keytype, $_REQUEST[$key]);
        }
    }
    
    public function setJson($json="") {
        global $_lib;
        
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        
        foreach($this -> __columns__ as $column) {
            $key = $column -> field;
            if(!empty($name)) $key = $name."[".$key."]";
            $keytype = $column -> type;

			$keyhour = $key.'_hour';
			$keymin = $key.'_min';
			$keysec = $key.'_sec';
            
            if(isset($json -> $key) && !check_blank($json -> $key) && (isset($json -> $keyhour) || isset($json -> $keymin) || isset($json -> $keysec))) {
                $hour = isset($json -> $keyhour) ? $json -> $keyhour : 0;
                $min = isset($json -> $keymin) ? $json -> $keymin : 0;
                $sec = isset($json -> $keysec) ? $json -> $keysec : 0;
                
                $value = $json -> $key." ".sprintf("%02d", $hour).":".sprintf("%02d", $min).":".sprintf("%02d", $sec);
                $this -> setField($key, $keytype, $value);
            } elseif(isset($json -> $key)) $this -> setField($key, $keytype, $json -> $key);
        }
    }
    
    public function setField($key, $keytype, $value) {
        //echo $key."(".$keytype.") - ".$value."<br>";
        //if($key == 'data') print_r($value);
		if($value === null) $this -> $key = null;
        elseif($key == $this -> __pkName__) $this -> __pkValue__ = $value;
        elseif(strtoupper($keytype) == 'INT' || strtoupper($keytype) == 'SMALLINT' || strtoupper($keytype) == 'MEDIUMINT' || strtoupper($keytype) == 'INTEGER' || strtoupper($keytype) == 'BIGINT') $this -> $key = int_format($value);
        elseif(strtoupper($keytype) == 'FLOAT' || strtoupper($keytype) == 'DOUBLE' || strtoupper($keytype) == 'DOUBLE PRECISION' || strtoupper($keytype) == 'REAL' || strtoupper($keytype) == 'BIT') $this -> $key = float_format($value);
        elseif(strtoupper($keytype) == 'JSON' && is_string($value)) {
			//if($value == '') $this -> $key = '{}';
			if($value == '') $this -> $key = new stdClass();
			else $this -> $key = jsondecode($value);
        } elseif(strtoupper($keytype) == 'JSON' && !is_string($value)) $this -> $key = $value;
        elseif(strtoupper($keytype) == 'DATE' || strtoupper($keytype) == 'TIME' || strtoupper($keytype) == 'DATETIME' || strtoupper($keytype) == 'TIMESTAMP' || strtoupper($keytype) == 'YEAR') {
            if(strtoupper($value) == 'CURRENT_TIMESTAMP') $this -> $key = '';
            else $this -> $key = $value;

			if(strtoupper($keytype) == 'DATE' && strtoupper($value) == '') $this -> $key = '0000-00-00';
			elseif(strtoupper($keytype) == 'DATETIME' && strtoupper($value) == '') $this -> $key = '0000-00-00 00:00:00';
        } elseif(!is_array($value)) $this -> $key = restoreQuotes($value);
    }

	public function setPhone($name) {
		$phone = getVars($name);
		if(is_array($phone)) $phone = implode("-", $phone);
		if($phone == '--') $phone = '';

		return $phone;
	}

	public function setHandphone($name) {
		$phone = getVars($name);
		if(is_array($phone)) $phone = implode("-", $phone);
		if($phone == '--') $phone = '';

		return $phone;
	}

	public function setFax($name) {
		$phone = getVars($name);
		if(is_array($phone)) $phone = implode("-", $phone);
		if($phone == '--') $phone = '';

		return $phone;
	}
    
    public function isColumnTypeInt($keytype) {
        if(strtoupper($keytype) == 'INT') return true;
        if(strtoupper($keytype) == 'SMALLINT') return true;
        if(strtoupper($keytype) == 'MEDIUMINT') return true;
        if(strtoupper($keytype) == 'INTEGER') return true;
        if(strtoupper($keytype) == 'BIGINT') return true;
        
        return false;
    }
    
    public function isColumnTypeFloat($keytype) {
        if(strtoupper($keytype) == 'FLOAT') return true;
        if(strtoupper($keytype) == 'DOUBLE') return true;
        if(strtoupper($keytype) == 'DOUBLE PRECISION') return true;
        if(strtoupper($keytype) == 'REAL') return true;
        if(strtoupper($keytype) == 'BIT') return true;
        
        return false;
    }
    
    public function isColumnTypeStr($keytype) {
        if($this -> isColumnTypeInt($keytype)) return false;
        if($this -> isColumnTypeFloat($keytype)) return false;
        
        return true;
    }

	public function isColumnTypeDate($keytype) {
        if(strtoupper($keytype) == 'DATE') return true;
        if(strtoupper($keytype) == 'DATETIME') return true;
        if(strtoupper($keytype) == 'TIME') return true;
        if(strtoupper($keytype) == 'TIMESTAMP') return true;
        
        return false;
    }

	public function isColumnTypeUsedDefaultValue($keytype) {
        if(strtoupper($keytype) == 'BLOB') return false;
        if(strtoupper($keytype) == 'TINYBLOB') return false;
        if(strtoupper($keytype) == 'MEDIUMBLOB') return false;
        if(strtoupper($keytype) == 'LONGBLOB') return false;
        if(strtoupper($keytype) == 'TEXT') return false;
        if(strtoupper($keytype) == 'TINYTEXT') return false;
        if(strtoupper($keytype) == 'MEDIUMTEXT') return false;
        if(strtoupper($keytype) == 'LONGTEXT') return false;
        if(strtoupper($keytype) == 'GEOMETRY') return false;
        if(strtoupper($keytype) == 'JSON') return false;

		return true;
	}

    public function isTable($isEcho=false) {
		global $_lib;

		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);

        return $_lib['db']['handler']['master'] -> isTable($this -> __tableName__);
    }
    
    public function isData($condition, $isEcho=false) {
        $json = new stdClass();
        $json -> field = 'count(*)';
        $json -> where = $condition;
        
        $result = $this -> getResults($json, $isEcho);
        $data = $result -> fetch_array();
        
        return $data[0] > 0;
    }
    
    function isExistField($name, $isEcho=false) {
		global $_lib;

        $query = "SHOW COLUMNS FROM ".$this -> __tableName__." LIKE '".$name."'";
        if($isEcho) { echo $query;exit(); }

		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($query);
        
        if(!$result) throw new Exception($_lib['db']['handler']['master'] -> db_handler -> error);
        
        if($result -> num_rows) return true;
        else return false;
    }
 
    function isUse($field) {
        global $_lib;
        
        return $this -> isUse == $_lib['isUse']['field'][$field];
    }

	function isIntCurrency($currency) {
		global $_lib;

		if($currency == $_lib['currency']['field']['kwr']) return true;

		return false;
	}

	function isCloudFiles($path) {
		return count($this -> getCloudFiles($path)) > 0;
	}

	function toObj($json="", $except=array()) {
		if(is_string($json)) $json = jsondecode($json);
		if(empty($json)) $json = new stdClass();

		$obj = $this;
		$pkName = $obj -> __pkName__;
		$obj -> $pkName = $obj -> __pkValue__;
		
		$vars = get_object_vars($json);
		foreach($vars as $name => $value) $obj -> $name = $value;

		for($i=0; $i<count($except); $i++) {
			unset($except[$i]);	
		}
		
		unset($obj -> __lib__);
		unset($obj -> __errorMsg__);
		unset($obj -> __tableName__);
		unset($obj -> __pkName__);
		unset($obj -> __pkValue__);
		unset($obj -> __columns__);
		
		$vars = get_object_vars($obj);
		//print_r($names);

		foreach($vars as $name => $value) {
			if(preg_match('/Obj$/', $name)) $obj -> $name = $obj -> $name -> toObj('', $except);
			elseif(gettype($obj -> $name) == "array") {
				for($i=0; $i<count($obj -> $name); $i++) {
					$o = $obj -> $name[$i];
					if(method_exists ($o , 'toObj')) $obj -> $name[$i] = $o -> toObj('', $except);
				}
			}
		}

		return $obj;
	}
   
    function toJson($json='', $except=array()) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
        
        //print_r($json);
		$result = new stdClass();
        
        $str = '';
        foreach($this -> __columns__ as $column) {
            $key = $column -> field;
            $keytype = $column -> type;
            
            if($key == $this -> __pkName__) $result -> $key = $this -> __pkValue__;
            elseif(preg_match('/_id$/', $key)) {
                $result -> $key = $this -> $key;
                $name = preg_replace_callback('/_[a-z]{1}/', function($word) { return strtoupper(substr($word[0], 1, 1));}, preg_replace('/_id$/', '', $key))."Obj";
                if(isset($this -> $name) && $this -> $name -> __pkValue__ <= 0) $this -> $name -> getData($this -> $key);
                if(isset($this -> $name)) $result -> $name = jsondecode($this -> $name -> toJson());
            } else $result -> $key = $this -> $key;
        }

		foreach($except as $name) {
			unset($result -> $name);
		}
        
        return jsonencode($result);
    }
    
	public function createPrivateKey() {
		return $_SERVER['REMOTE_ADDR'].'|'.time();
	}
	
	function displayCloudFiles($path) {
		$files = $this -> getCloudFiles($path);

		$tag = '';
		foreach($files as $cloudFileObj) {
			$tag .= '<div id="clodu_file_'.$cloudFileObj -> __pkValue__.'">';
			
			if($cloudFileObj -> type == 'image/png' || $cloudFileObj -> type == 'image/gif' || $cloudFileObj -> type == 'image/jpeg') $tag .= '<a href="'.$cloudFileObj -> getUrl().'" target="_blank"><img src="'.$cloudFileObj -> getThumnailImg().'" alt=""/></a>';
			else $tag .= '<div class="file"><a href="'.$cloudFileObj -> getUrl().'" target="_blank">'.$cloudFileObj -> name.'</a></div>';

			$tag .= '<div class="bottom"><a href="/CloudFile/deleteProcess?cloud_file_id='.$cloudFileObj -> __pkValue__.'" target="process" class="button">삭제</a></div>';
			$tag .= '</div>';
		}

		return $tag;
	}

	function displayDday($name) {
		$time = getTimestamp($this -> $name);
		$rest = (int)$time - time();

		if($rest < 86400) {
			return '<span class="dday_hour">'.date("H", $time).'</span><span class="dday_min">'.date("i", $time).'</span><span class="dday_sec">'.date("s", $time).'</span>';
		} else {
			$day = (int)($rest / 86400) + 1;
			return '<span class="dday_day">'.$day.'</span>';
		}
	}
    
    function displayDateTime($name) {
        global $_lib;
        
        if(empty($_lib['lang'])) $_lib['lang'] = 'ko';
        $time = getTimestamp($this -> $name);
        
        if(mktime(0, 0, 0, date("n"), date("j"), date("Y")) <= $time && $time < mktime(0, 0, 0, date("n"), date("j") + 1, date("Y"))) {
            if($_lib['lang'] == 'en') return "Today ".$this -> getHour($name).":".$this -> getMin($name);
            else return "오늘 ".$this -> getHour($name).":".$this -> getMin($name);
        } elseif(mktime(0, 0, 0, date("n"), date("j")-1, date("Y")) <= $time && $time < mktime(0, 0, 0, date("n"), date("j"), date("Y"))) {
            if($_lib['lang'] == 'en') return "Yesterday ".$this -> getHour($name).":".$this -> getMin($name);
            else return "어제 ".$this -> getHour($name).":".$this -> getMin($name);
        } else return $this -> getDate($name);
    }

	function date($format, $name) {
		$dt = getTimestamp($this -> $name);
		return date($format, $dt);
	}

	function displayDayOfWeek($name) {
		$dt = getTimestamp($this -> $name);
		$weekString = array("일", "월", "화", "수", "목", "금", "토");
		return $weekString[date('w', $dt)];
	}
    
    function displayDate($name, $defaultValue='') {
        global $_lib;
        
        if(check_blank($this -> $name)) return $defaultValue;
        
        $datetime = explode(" ", $this -> $name);
        
        return $datetime[0];
    }

	function displayTime($name, $defaultValue='') {
        global $_lib;
        
        if(check_blank($this -> $name)) return $defaultValue;
        
        $datetime = explode(" ", $this -> $name);
        
        return $datetime[1];
    }
  
    function displayIsUse($isShort=false) {
        global $_lib;
        
        if($isShort) {
            if($this -> isUse) return 'O';
            else return 'X';
        } else return $_lib['isUse']['name'][$this -> isUse];
    }

	function displayPrice($price="price", $sale="sale", $currency="currency") {
        global $_lib;

        if($this -> $price == 0 && $this -> $sale == 0) {
			$html = '<div class="free">무료</div>';
		} else {
	        $html  = '<div class="price'.($this -> $price == $this -> $sale ? ' same' : ' differ').'">'.auto_number_format($this -> $price).'</div>';
			$html .= '<div class="sale'.($this -> $price == $this -> $sale ? ' same' : ' differ').'">'.auto_number_format($this -> $sale).'</div>';
			$html .= '<div class="currency">'.$_lib['currency']['nickname'][$this -> $currency].'</div>';
	        if($this -> price != $this -> sale) $html .= '<div class="rate">'.$this -> getSaleRate().'%</div>';
		}
        
        return $html;
    }

	function displayCurrency() {
        global $_lib;
        
        return $_lib['currency']['nickname'][$this -> currency];
    }

	function displaySelectCurrency($formName='currency', $option="", $initName='▒ 선택 ▒') {
		global $_lib;

		return selectboxTag("currency", $_lib['currency']['value'], $_lib['currency']['name'], $this -> currency, $option, $initName);
	}

	function displayInputPrice($price="price", $sale="sale", $currency="currency", $option="", $initName="▒ 선택 ▒") {
        global $_lib;

        $html  = '<div class="price"><input type="text" name="'.$price.'" value="'.$this -> $price.'" class="won"/></div>';
        $html .= '<div class="sale"><input type="text" name="'.$sale.'" value="'.$this -> $sale.'" class="won"/></div>';
        $html .= '<div class="currency">'.selectboxTag($currency, $_lib['currency']['value'], $_lib['currency']['nickname'], $this -> currency, $option, $initName).'</div>';
        
        return $html;
    }
  
    function displayInputPhone($formName="phone") {
        return $this -> displayPhoneTag($formName, $this -> phone);
    }
    
    function displayInputHandphone($formName="handphone") {
        return $this -> displayPhoneTag($formName, $this -> handphone);
    }
    
    function displayInputFax($formName="fax") {
        return $this -> displayPhoneTag($formName, $this -> fax);
    }
    
    function displayAddress($zipcode="zipcode", $country="country", $addressMain="addressMain", $addressDetail="addressDetail") {
        $tag  = '<div class="zipcode">'.$this -> $zipcode.'</div>';
        $tag .= '<div class="country">'.$this -> $country.'</div>';
        $tag .= '<div class="addressMain">'.$this -> $addressMain.'</div>';
        $tag .= '<div class="addressDetail">'.$this -> $addressDetail.'</div>';

		return $tag;
    }

	function displayInputAddress($isMobile=false) {
		if($isMobile) return displayMobileAddressTag("address", $this -> zipcode, $this -> country, $this -> addressMain, $this -> addressDetail);
        else return displayAddressTag("address", $this -> zipcode, $this -> country, $this -> addressMain, $this -> addressDetail);
    }
    
    function displayInputDate($formName) {
		$tag  = '<input type="date" name="'.$formName.'" value="'.$this -> getDate($formName).'" class="date"/> ';
		$tag .= '<input type="text" name="'.$formName.'_hour" value="'.$this -> getHour($formName).'" class="hour"/> : ';
		$tag .= '<input type="text" name="'.$formName.'_min" value="'.$this -> getMin($formName).'" class="min"/> : ';
		$tag .= '<input type="text" name="'.$formName.'_sec" value="'.$this -> getSec($formName).'" class="sec"/>';
        return $tag;
    }
    
    function displayRadioIsUse($formName='isUse') {
        return inputRadioIsUse($formName, $this -> isUse);
    }

	function displaySelectbox($formName, $results, $default='', $option="", $initName="▒ 선택 ▒") {
		$tag = '<select name="'.$formName.'"';
		if(!check_blank($option)) $tag .= ' '.trim($option);
		$tag .= '>';

		if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
		
		while($data = $results -> fetch_array()) {
			$tag .= '<option value="'.$data[0].'"';
			if($data[0] == $default) $tag .= ' selected="selected"';
			$tag .= '>';
			$tag .= $data[1];
			$tag .= '</option>';
		}
		
		$tag .= '</select>';
		
		return $tag;
	}

	function displaySelectByCode($gcode, $formName, $default='', $option="", $initName="▒ 선택 ▒") {
		$query = new stdClass();
		$query -> where = "gcode='".$gcode."'";
		$query -> orderby = "sortNum";
		
		$codeObj = new Code();
		$results = $codeObj -> getResults($query);
		
		$tag = '<select name="'.$formName.'"'.$option.'>';
		if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
		
		while($data = $results -> fetch_array()) {
			$obj = new Code();
			$obj -> setData($data);
			
			$tag .= '<option value="'.$obj -> code.'"';
			if($obj -> code == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
			$tag .= '>';
			if(!check_blank($obj -> nickname)) $tag .= $obj -> nickname;
			elseif(!check_blank($obj -> name)) $tag .= $obj -> name;
			else $tag .= $obj -> code;
			$tag .= '</option>';
		}
		
		$tag .= '</select>';
		
		return $tag;
	}

	function displaySelectByCodeName($gcode, $formName, $default='', $option="", $initName="▒ 선택 ▒") {
		$query = new stdClass();
		$query -> where = "gcode='".$gcode."'";
		$query -> orderby = "sortNum";
		
		$codeObj = new Code();
		$results = $codeObj -> getResults($query);
		
		$tag = '<select name="'.$formName.'"'.$option.'>';
		if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
		
		while($data = $results -> fetch_array()) {
			$obj = new Code();
			$obj -> setData($data);
			
			$tag .= '<option value="'.$obj -> name.'"';
			if($obj -> name == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
			$tag .= '>';
			if(!check_blank($obj -> nickname)) $tag .= $obj -> nickname;
			elseif(!check_blank($obj -> name)) $tag .= $obj -> name;
			else $tag .= $obj -> code;
			$tag .= '</option>';
		}
		
		$tag .= '</select>';
		
		return $tag;
	}

	function displayInputAddressTag($id, $zipcode="", $country="", $addressMain="", $addressDetail="", $before='', $after='', $mode='kr') {
		if($mode == 'kr') {
			$tag  = '<ul id="'.$id.'">';
			$tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode search" placeholder="우편번호"/> ';
			$tag .= '<a href="/zipcode/search?response='.urlencode("$('#".$id."').ELCZipcodeSelected").'" target="dialog" class="button">검색</a></li>';
			$tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="국가"/></li>';
			$tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="시군구 도로명"/></li>';
			$tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="상세주소"/></li>';
			$tag .= '</ul>';
		} else {
			$tag  = '<ul id="'.$id.'">';
			$tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="Apartment, suite, unit, building, floor, etc."/></li>';
			$tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="State/Province/Region/City/Street address"/></li>';
			$tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="Zip"/></li>';
			$tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="Country/region"/></li>';
			$tag .= '</ul>';
		}
		
		return $tag;		
	}

    //결제방법
	function displayDecideMethod() {
        global $_lib;

		if(in_array($this -> decideMethod, $_lib['decideMethod']['value'])) return $_lib['decideMethod']['nickname'][$this -> decideMethod];
		else return '';
	}

    //결제방법
	function displayInputDecideMethod($formName='decideMethod') {
        global $_lib;

		if($this -> decideMethod == 0) $this -> decideMethod = 1;

		$tag = '<ul class="ul-radio-list">';

		for($i=0; $i<count($_lib['decideMethod']['value']); $i++) {
			if(isset($_lib['decideMethod']['isUse'][$_lib['decideMethod']['value'][$i]]) && $_lib['decideMethod']['isUse'][$_lib['decideMethod']['value'][$i]]) {
				$codeObj = new Code();
				$codeObj -> getDataByCondition("gcode='DECIDEMETHOD' AND code='".$_lib['decideMethod']['value'][$i]."'");

				if($codeObj -> __pkValue__) $_lib['decideMethod']['nickname'][$_lib['decideMethod']['value'][$i]] = $codeObj -> name;

				$tag .= '<li>';
				$tag .= '<input id="decideMethod'.$i.'" type="radio" name="'.$formName.'" value="'.$_lib['decideMethod']['value'][$i].'"'.($_lib['decideMethod']['value'][$i] == $this -> decideMethod ? ' checked="checked"' : '').'/>';
				$tag .= '<label for="decideMethod'.$i.'">'.$_lib['decideMethod']['nickname'][$_lib['decideMethod']['value'][$i]].'</label>';
				$tag .= '</li>';
			}
		}

		$tag .= '</ul>';
        
        return $tag;        
	}

    //결제방법
	function displaySelectDecideMethod($formName='decideMethod', $option="", $initName='▒ 선택 ▒') {
        global $_lib;

		return selectboxTag($formName, $_lib['decideMethod']['value'], $_lib['decideMethod']['nickname'], $this -> decideMethod, $option, $initName);
	}

    //사용여부
	function displayInputisUse($formName='isUse') {
        global $_lib;

        return inputRadioTag($formName, $_lib['isUse']['value'], $_lib['isUse']['name'], $this -> isUse);
	}

	function displayPhoneTag($formName="handphone", $value="") {
		if(preg_match('/\-/', $value)) {
			$temp = explode('-', $value);
			if(count($temp) <= 1) $temp[1] = '';
			if(count($temp) <= 2) $temp[2] = '';
		} else {
			$temp = [];
			if(strlen($value) == 11) {
				$temp[0] = substr($value, 0, 3);
				$temp[1] = substr($value, 3, 4);
				$temp[2] = substr($value, 7, 4);
			} elseif(strlen($value) == 10) {
				$temp[0] = substr($value, 0, 3);
				$temp[1] = substr($value, 3, 3);
				$temp[2] = substr($value, 6, 4);
			} else {
				$temp[0] = $value;
				$temp[1] = '';
				$temp[2] = '';
			}
		}
		
		$tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="handphone1"/>';
		$tag .= ' - ';
		$tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="handphone2"/>';
		$tag .= ' - ';
		$tag .= '<input type="text" name="'.$formName.'[2]" value="'.$temp[2].'" class="handphone3"/>';
		
		return $tag;
	}

	function strToJsonUnit($json, $name, $value, $unit, $defaultUnit='m') {
		if(preg_match('/^[0-9\.\s]{*}mm$/', $value)) {
			$json -> $unit = 'mm';
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		} elseif(preg_match('/[0-9\.\s]{*}cm$/', $value)) {
			$json -> $unit = 'cm';
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		} elseif(preg_match('/[0-9\.\s]{*}m$/', $value)) {
			$json -> $unit = 'cm';
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		} elseif(preg_match('/[0-9\.\s]{*}kg$/', $value)) {
			$json -> $unit = 'kg';
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		} elseif(preg_match('/[0-9\.\s]{*}lb$/', $value)) {
			$json -> $unit = 'lb';
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		} else {
			$json -> $unit = $defaultUnit;
			$json -> $name = float_format(trim(str_replace($json -> $unit, '', $value)));
		}

		return $json;
	}

	function strToJsonCurrency($json, $name, $value, $unit='currency', $defaultUnit=1) {
		global $_lib;

		if(preg_match('/USD/', $value)) {
			$json -> $name = float_format(trim(str_replace('USD', '', $value)));
			$json -> $unit = $_lib['currency']['field']['usd'];
		} elseif(preg_match('/\$/', $value)) {
			$json -> $name = float_format(trim(str_replace('$', '', $value)));
			$json -> $unit = $_lib['currency']['field']['usd'];
		} elseif(preg_match('/KRW/', $value)) {
			$json -> $name = float_format(trim(str_replace('KRW', '', $value)));
			$json -> $unit = $_lib['currency']['field']['kwr'];
		} elseif(preg_match('/￦/', $value)) {
			$json -> $name = float_format(trim(str_replace('￦', '', $value)));
			$json -> $unit = $_lib['currency']['field']['kwr'];
		} elseif(preg_match('/JPY/', $value)) {
			$json -> $name = float_format(trim(str_replace('JPY', '', $value)));
			$json -> $unit = $_lib['currency']['field']['jpy'];
		} elseif(preg_match('/￥/', $value)) {
			$json -> $name = float_format(trim(str_replace('￥', '', $value)));
			$json -> $unit = $_lib['currency']['field']['jpy'];
		} elseif(preg_match('/EUR/', $value)) {
			$json -> $name = float_format(trim(str_replace('EUR', '', $value)));
			$json -> $unit = $_lib['currency']['field']['eur'];
		} elseif(preg_match('/€/', $value)) {
			$json -> $name = float_format(trim(str_replace('€', '', $value)));
			$json -> $unit = $_lib['currency']['field']['eur'];
		} elseif(preg_match('/CNY/', $value)) {
			$json -> $name = float_format(trim(str_replace('CNY', '', $value)));
			$json -> $unit = $_lib['currency']['field']['cny'];
		} elseif(preg_match('/圆/', $value)) {
			$json -> $name = float_format(trim(str_replace('圆', '', $value)));
			$json -> $unit = $_lib['currency']['field']['cny'];
		}

		return $json;
	}

	function strToVar($str) {
        foreach($this -> __columns__ as $column) {
			$name = $column -> field;
			$str = str_replace("{".$name."}", $this -> $name, $str);
        }
		
		return $str;
	}

	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function dataTransfer($dbconn, $tableName, $pkName) {
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
		//print_r($_lib['db']['handler']['master'] -> name);

		$origindb = $dbconn -> name;
		$origintable = $tableName;
		$origindbtable = $origindb.".".$origintable;
		$tempdb = $_lib['db']['handler']['master'] -> name;
		$temptable = "temp_".$tableName;
		$tempdbtable = $tempdb.".".$temptable;
		$tmptable = "tmp_".$this -> __tableName__;
		
		if($_lib['db']['handler']['master'] -> isTable($temptable)) {
			if(!$_lib['db']['handler']['master'] -> query("DROP TABLE ".$temptable)) {
				$this -> __errorMsg__ = $temptable.' 테이블을 삭제하는데 실패하였습니다. 원인 - '.$this -> __errorMsg__;
				return false;
			}
		}
		
		$sql = "CREATE TABLE ".$tempdbtable." LIKE ".$origindbtable;
		//echo $sql."<br>";
		if(!$_lib['db']['handler']['master'] -> query($sql)) {
			$this -> __errorMsg__ = '테이블 이전 설치 실패! 원인 - '.$this -> __errorMsg__;
			return false;
		}

		$sql = "INSERT INTO ".$tempdbtable." SELECT * FROM ".$origindbtable;
		//echo $sql."<br>";
		if(!$_lib['db']['handler']['master'] -> query($sql)) {
			$this -> __errorMsg__ = '데이터 이전 실패! 원인 - '.$this -> __errorMsg__;
			return false;
		}

        $sql = "ALTER TABLE ".$temptable." ADD COLUMN new_".$this -> __pkName__." bigint unsigned NOT NULL default 0 AFTER ".$pkName;
		//echo $sql."<br>";
		if(!$_lib['db']['handler']['master'] -> query($sql)) {
			$this -> __errorMsg__ = '테이블 변경 실패! 원인 - '.$this -> __errorMsg__;
			return false;
		}

		return true;
	}

	function insert_array($arr, $idx, $add) {       
		$arr_front = array_slice($arr, 0, $idx); //처음부터 해당 인덱스까지 자름
		$arr_end = array_slice($arr, $idx); //해당인덱스 부터 마지막까지 자름
		$arr_front[] = $add;//새 값 추가
		return array_merge($arr_front, $arr_end);
	}

	/********************************/
	/*****		DB 처리 프로그램		*****/
	/********************************/
    
	//▒▒	결과를 하나만 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    public function getResult($json='', $isEcho=false) {
		global $_lib;

		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
       
        if(!isset($json -> table)) $json -> table = $this -> __tableName__;

		if(!isset($_lib['db']['handler']['slave'])) $_lib['db']['handler']['slave'] = new DBConn($_lib['db']['slave']);
        
		$result = $_lib['db']['handler']['slave'] -> selectQuery($json, $isEcho);
        
		return $result -> fetch_array();
    }
    
	//▒▒	결과를 모두 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    public function getResults($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        
        if(!isset($json -> table)) $json -> table = $this -> __tableName__;

		if(!isset($_lib['db']['handler']['slave'])) $_lib['db']['handler']['slave'] = new DBConn($_lib['db']['slave']);
		
        return $_lib['db']['handler']['slave'] -> selectQuery($json, $isEcho);
    }

	//▒▒	인스톨 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function install($isEcho=false) {
        if(!$this -> isTable()) return $this -> createTable($isEcho);
 
        return true;
    }
    
    //▒▒	테이블을 변경, 수정하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function alterTable($condition, $isEcho=false) {
		global $_lib;

        if(check_blank($condition)) {
            $this -> __errorMsg__ = 'Query 조건이 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        $query = "ALTER TABLE ".$this -> __tableName__." ".$condition;
        if($isEcho) {echo $query;exit();}
		
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($query);
        
        if(!$result) throw new Exception($_lib['db']['handler']['master'] -> db_handler -> error.$query);
        
        return true;
    }
    
    //▒▒	테이블에 필드를 추가하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function addTableField($name, $type, $default='', $after='', $before='', $isNotNull=true, $isKey=false, $isEcho=false) {
        if(check_blank($name)) {
            $this -> __errorMsg__ = '추가할 필드명이 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        if(check_blank($type)) {
            $this -> __errorMsg__ = '추가할 필드타입이 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        $condition = "ADD COLUMN ".$name." ".$type;
        if($isNotNull) $condition .= " NOT NULL DEFAULT '".$default."'";
        if(!check_blank($after)) $condition .= " AFTER ".$after;
        if(!check_blank($before)) $condition .= " BEFORE ".$before;
        
        return $this -> alterTable($condition, $isEcho);
    }
    
    //▒▒	테이블에 키를 추가하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function addTableIndex($name, $value) {
        if(check_blank($name)) {
            $this -> __errorMsg__ = '추가할 Index명 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        if(check_blank($value)) {
            $this -> __errorMsg__ = '추가할 Index 필드명이 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        $condition = "ADD INDEX ".$name." (".$value.")";
        
        return $this -> alterTable($condition);
    }
    
    //▒▒	테이블에 필드를 삭제하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function dropTableField($name, $isEcho=false) {
        if(check_blank($name)) {
            $this -> __errorMsg__ = '삭제할 필드명이 없습니다. 관리자에게 문의바랍니다.';
            return false;
        }
        
        $condition = "DROP ".$name;
        
        return $this -> alterTable($condition, $isEcho);
    }
    
    //▒▒	테이블을 생성하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function createTable($isEcho=false) {
		global $_lib;

        $sql = "";
        
        foreach($this -> __columns__ as $column) {
            if(!check_blank($sql)) $sql .= ',';
            
            if(strtoupper($column -> type) == 'JSON') $sql .= $column -> field." longtext";
            else $sql .= $column -> field." ".$column -> type;
            
            if(isset($column -> size)) $sql .= "(".$column -> size.")";
            
            if(isset($column -> option)) $sql .= " ".$column -> option;
            
            if(isset($column -> key) && !check_blank($column -> key)) $sql .= " NOT NULL";
            
            if($this -> isColumnTypeUsedDefaultValue($column -> type) && isset($column -> default)) {
                if(strtoupper($column -> default) == 'CURRENT_TIMESTAMP') $sql .= " DEFAULT ".$column -> default;
                elseif($this -> isColumnTypeStr($column -> type)) $sql .= " DEFAULT '".$column -> default."'";
                else $sql .= " DEFAULT ".$column -> default;
            }
            
            if(isset($column -> extra)) $sql .= " ".$column -> extra;
            
        }
        
        foreach($this -> __columns__ as $column) {
            if(isset($column -> keytype) && strtoupper($column -> keytype) == 'PRIMARY') $sql .= ",PRIMARY KEY (".$column -> key.")";
            elseif(isset($column -> keytype) && strtoupper($column -> keytype) == 'FOREIGN') $sql .= ",FOREIGN KEY (".$column -> key.") REFERENCES ".$column -> refrence;
            elseif(isset($column -> key) && strtoupper($column -> key) != '') $sql .= ",KEY ".$column -> key."(".$column -> key.")";
        }
        
        $sql = "CREATE TABLE ".$this -> __tableName__."(".$sql.")";
        
        if($isEcho) {
            echo $sql;
            exit();
        }
        
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);
        
        if(!$result) throw new Exception($_lib['db']['handler']['master'] -> db_handler -> error.'<br>'.$sql);
        else return true;
    }
    
    //▒▒	테이블을 삭제하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function dropTable($isEcho=False) {
        global $_lib;
        
        $sql = "DROP TABLE ".$this -> __tableName__;
        
        if($isEcho) {
            echo $sql;
            exit();
        }
        
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);
        
        if(!$result) throw new Exception($_lib['db']['handler']['master'] -> db_handler -> error);
        else return true;
    }

    //▒▒	테이블에 추가하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function insert($isEcho=false) {
		global $_lib;

        $name = '';
        $value = '';

		//if($isEcho) print_r($this -> __columns__);

		foreach($this -> __columns__ as $column) {
			$key = $column -> field;
			$keytype = $column -> type;
			
			if($key != $this -> __pkName__) {
				if(empty($name)) $name .= $key;
				else $name .= ','.$key;
				
				if($value != '') $value .= ',';
				
				if(strtoupper($keytype) == 'JSON') $value .= "'".addslashes(jsonencode($this -> $key))."'";
				elseif($this -> isColumnTypeStr($keytype)) $value .= "'".replaceQuotes($this -> $key)."'";
				elseif($this -> isColumnTypeInt($keytype)) $value .= (int)$this -> $key;
				elseif($this -> isColumnTypeFloat($keytype)) $value .= (float)$this -> $key;
				elseif($this -> isColumnTypeDate($keytype)) $value .= empty($this -> $key) ? "'0000-00-00 00:00:00'" : "'".$this -> $key."'";
				elseif($key != $this -> __pkName__) $value .= "'".$this -> $key."'";
			}
		}
		
		
		$sql = "INSERT INTO ".$this -> __tableName__." (".$name.") VALUES (".$value.")";
		if($isEcho) {
			echo '<xmp>'.$sql.'</xmp>';
			exit();
		}
		
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);
		
		if(!$result) {
			$this -> __errorMsg__ = $this -> __tableName__.' 신규등록실패 - 원인:'.$_lib['db']['handler']['master'] -> db_handler -> error;
			return false;
		}
		
		$this -> __pkValue__ = $_lib['db']['handler']['master'] -> insert_id();
		
		return true;
	}

    //▒▒	테이블에 업데이트하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function update($isEcho=false) {
		global $_lib;

        $name = '';
        $value = '';
		$sql = '';

		foreach($this -> __columns__ as $column) {
			$key = $column -> field;
			$keytype = $column -> type;
			
			if($this -> __pkName__ != $key) {
				//echo $key."(".$keytype.") - ".$this -> $key."<br>";
				
				if(!check_blank($sql)) $sql .= ',';
				
				if(strtoupper($keytype) == 'JSON') {
					if(is_string($this -> $key)) $sql .= $key."='".stripslashes($this -> $key)."'";
					else $sql .= $key."='".stripslashes(jsonencode($this -> $key))."'";
				} elseif($this -> isColumnTypeStr($keytype)) $sql .= $key."='".replaceQuotes($this -> $key)."'";
				elseif($key != $this -> __pkName__) $sql .= $key."='".$this -> $key."'";
			}
		}
		
		$sql = "UPDATE ".$this -> __tableName__." SET ".$sql." WHERE ".$this -> __pkName__."=".$this -> __pkValue__;
		if($isEcho) {
			echo '<xmp>'.$sql.'</xmp>';
			exit();
		}
		
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);

		if(!$result) {
			$this -> __errorMsg__ = $this -> __tableName__.'의 '.$this -> __pkValue__.' 업데이트실패';
			if(isset($result -> error)) $this -> __errorMsg__ .=  ' - 원인:'.$result -> error;
			return false;
		}
		
		return true;
	}
    
	//▒▒	저장하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function save($isEcho=false) {
        $name = '';
        $value = '';

        if($this -> isData($this -> __pkName__."='".$this -> __pkValue__."'")) return $this -> update($isEcho);
		else return $this -> insert($isEcho);
    }
    
	//▒▒	삭제하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function delete($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;

		$sql = "DELETE FROM ".$this -> __tableName__." WHERE ".$this -> __pkName__."=".$this -> __pkValue__;
        if(!empty($json -> isEcho)) {
            echo '<xmp>'.$sql.'</xmp>';
            exit();
        }
        
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);

        if(!$result) {
            $this -> __errorMsg__ = $this -> __tableName__.'의 '.$this -> __pkValue__.' 삭제실패';
            return false;
        }
        
        return true;
    }
    
	//▒▒	조회하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function selectByCondition($condition, $isEcho=false) {
		global $_lib;

		$sql = "SELECT * FROM ".$this -> __tableName__." WHERE ".$condition;
        if($isEcho) {
            echo '<xmp>'.$sql.'</xmp>';
            exit();
        }
        
		if(!isset($_lib['db']['handler']['slave'])) $_lib['db']['handler']['slave'] = new DBConn($_lib['db']['slave']);
        $result = $_lib['db']['handler']['slave'] -> query($sql);

		if(!$result) throw new customException($this -> __tableName__.' '.$condition.' 조건 삭제 실패 - 원인:'.$result -> error); 
        
        return $result;
    }
    
	//▒▒	조건삭제 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
    function deleteByCondition($condition, $isEcho=false) {
		global $_lib;

        $sql = "DELETE FROM ".$this -> __tableName__;
		if(!empty($condition))  $sql .= " WHERE ".$condition;
        if($isEcho) {
            echo $sql;
            exit();
        }
        
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);

		if(!$result) {
            $this -> __errorMsg__ = $this -> __tableName__.' '.$condition.' 조건 삭제 실패 - 원인:'.$result -> error;
            return false;
        }
        
        return true;
    }
    
	//▒▒	조건업데이트 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function updateByCondition($field, $condition, $isEcho=false) {
		global $_lib;

		$sql = "UPDATE ".$this -> __tableName__." SET ".$field." WHERE ".$condition;
        if($isEcho) {
            echo $sql;
            exit();
        }
        
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
        $result = $_lib['db']['handler']['master'] -> query($sql);

		if(!$result) {
            $this -> __errorMsg__ = $this -> __tableName__.' '.$condition.' 조건 업데이트 실패 - 원인:'.$result -> error;
            return false;
        }
        
        return true;
    }

	//▒▒	클라우드 파일 저장 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function cloudFileProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//echo print_r($json);exit();
		
		if(empty($json -> path)) {
			$this -> __errorMsg__ = '클라우드 패스 정보가 없습니다.';
		} else $path = $json -> path;

		if(empty($json -> formName)) {
			$this -> __errorMsg__ = '전송 폼 정보가 없습니다.';
		} else $formName = $json -> formName;

        if(isset($_FILES[$formName]) && $_FILES[$formName]['size'] > 0) $json -> isDelBgImg = true;
		
		//기존 파일 삭제
		if(isset($json -> isDelBgImg) && $json -> isDelBgImg) {
			$cloudFolderObj = new CloudFolder();
			$cloudFolderObj -> getDataAllByPath($path);

			$files = $cloudFolderObj -> getFiles();
			foreach($files as $cloudFileObj) {
				if(!$cloudFileObj -> delete()) {
					$this -> __errorMsg__ = $cloudFileObj -> path." 파일을 삭제하는데 실패하였습니다. 원인 - ".$cloudFileObj -> __errorMsg__;
				}
			}
		}
        
		//신규 파일 등록
		if(isset($_FILES[$formName]) && $_FILES[$formName]['size'] > 0) {
			$cloudFolderObj = new CloudFolder();
			$cloudFolderObj -> getDataAllByPath($path);
			
			if($cloudFolderObj -> __pkValue__ <= 0) $cloudFolderObj -> mkdir($path);
			
			$data = new stdClass();
			$data -> cloud_folder_id = $cloudFolderObj -> __pkValue__;
			$data -> name = $_FILES[$formName]['name'];
			$data -> type = $_FILES[$formName]['type'];
			$data -> origin = $_FILES[$formName]['tmp_name'];
			$data -> size = $_FILES[$formName]['size'];
			
			$cloudFileObj = new CloudFile();
			if(!$cloudFileObj -> moveProcess($data)) {
				$this -> __errorMsg__ = $cloudFileObj -> __errorMsg__;
			}
		}

		//url로 이미지 등록
		if(!empty($json -> $formName -> url)) {
			$cloudFolderObj = new CloudFolder();
			$cloudFolderObj -> getDataAllByPath($path);
			
			if($cloudFolderObj -> __pkValue__ <= 0) $cloudFolderObj -> mkdir($path);

			$data = new stdClass();
			$data -> cloud_folder_id = $cloudFolderObj -> __pkValue__;
			$data -> url = $json -> $formName -> url;
			$data -> name = $json -> $formName -> name;
			
			$cloudFileObj = new CloudFile();
			if(!$cloudFileObj -> copyProcess($data)) {
				$this -> __errorMsg__ = $cloudFileObj -> __errorMsg__;
			}
		}

		return true;
	}

	function search($pkValue) {
		$this -> getData($pkValue);
		return true;
	}

	function joinProcess($json = '', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
        
        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }
      
    function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
        
        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		return $this -> modifyProcess($json, $isEcho);
	}

	function deleteProcess($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if((isset($this -> user_id) && $_lib['user'] -> __pkValue__ != $this -> user_id) || $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorMsg__ = '삭제 권한이 없습니다.';
			return false;
		}

        $cart = array();
		if(isset($json -> cart)) $cart = $json -> cart;

		$pkName = $this -> __pkName__;
		if(isset($json -> $pkName)) {
			$pkValue = $json -> $pkName;
			if($pkValue) array_push($cart, $pkValue);
		}
        
        for($i=0; $i<count($cart); $i++) {
            if($cart[$i] <= 0 ) {
                $this -> __errorMsg__ = '삭제할 '.$this -> __tableName__.' 정보가 없습니다. 관리자에게 문의바랍니다.';
                return false;
            } else {
                $this -> getData($cart[$i]);
                
                if(!$this -> delete($isEcho)) return false;
            }
        }

        return true;
    }
}
?>