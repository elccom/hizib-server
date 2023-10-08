<?php
class Weather extends Component {    
    function __construct(){
        $this -> __tableName__ = 'weather';
        $this -> __pkName__ = 'weather_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"areacode","type":"varchar","size":255,"key":"areacode","default":""},
			{"field":"timecode","type":"varchar","size":255,"key":"timecode","default":""},
			{"field":"location","type":"varchar","size":255,"key":"location","default":""},
			{"field":"currentTemperature","type":"float","size":10,"key":"currentTemperature","default":0},
			{"field":"minTemperature","type":"float","size":10,"key":"minTemperature","default":0},
			{"field":"maxTemperature","type":"float","size":10,"key":"maxTemperature","default":0},
			{"field":"sky","type":"varchar","size":255,"key":"sky","default":""},
			{"field":"icon","type":"int","size":2,"key":"icon","default":0},
			{"field":"finedust","type":"varchar","size":255,"key":"finedust","default":""},
			{"field":"ultrafinedust","type":"varchar","size":255,"key":"ultrafinedust","default":""},
			{"field":"lists","type":"json","default":"{}"},
			{"field":"updateDate","type":"timestamp","key":"updateDate","default":"CURRENT_TIMESTAMP"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"}
        ]');
        
        parent::__construct();
		$this -> install();
        //$this -> tableUpdate();
    }
    
    function tableUpdate() {
		/*
        if(!$this -> isExistField('token')) {
            if(!$this -> addTableField('token', 'varchar(255)', '', 'passwd', '', true, true)) return false;
            if(!$this -> addTableIndex('token', 'token')) return false;
        }
		*/
    }
	
	//현재 지역코드 시간코드가지고 조회
	function search($areacode) {
		global $_lib;

		if(strlen($areacode) != 8 && !preg_match('/^[0-9]{8}$/', $areacode)) {
            $this -> __errorCode__ = "/Weather/search/1";
			$this -> __errorMsg__ = "8자리 숫자로된 지역코드를 입력해 주세요.";
			return false;
		}

		$smartdoorGroupObj = new SmartdoorGroup();
		if($smartdoorGroupObj -> getTotal("areacode='".$areacode."'") <= 0) {
            $this -> __errorCode__ = "/Weather/search/2";
			$this -> __errorMsg__ = "등록되지 않은 지역코드입니다.";
            return false;
		}

		$timecode = date("YmdH");

		$this -> getDataByCondition("areacode='".$areacode."'");
		
		//검색된 데이터가 없으면 크롤링
		if($this -> __pkValue__ <= 0 || $this -> timecode != $timecode) {
			$json = jsondecode(execpython($_lib['directory']['python']."/weather.py weather -areacode ".$areacode));
			$json -> areacode = $areacode;
			$json -> timecode = $timecode;

			if(!$this -> saveAll($json)) {
				$this -> __errorCode__ = "/Weather/search/3";
				$this -> __errorMsg__ = "날씨를 저장하는데 실패하였습니다.";
				return false;
			}
		}

		return true;
	}

	//시/도, 구/군, 동/면/리 정보를 가지고 네이버날씨에서 지역코드정보를 검색
	function areacode($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/Weather/areacode/1";
			$this -> __errorMsg__ = "관리자 권한으로 등록 후 이용해 주세요.";
            return false;
		}

		if(empty($json -> keyword)) {
            $this -> __errorCode__ = "/Weather/areacode/2";
			$this -> __errorMsg__ = "검색할 지역명을 입력해 주세요.";
            return false;
		}

		$data = jsondecode(execpython($_lib['directory']['python'].'/weather.py areacode -keyword "'.strip_tags($json -> keyword).'"'));
		$array = [];
		if(isset($data -> items)) {
			foreach($data -> items as $item) {
				foreach($item as $i) {
					$obj = new stdClass();
					$obj -> code = $i[1][0];
					$obj -> name = $i[0][0];
					array_push($array, $obj);
				}
			}
		}

		return $array;
	}
	    
    function isValidate($obj) {
        if(check_blank($obj -> areacode)) {
            $this -> __errorCode__ = "/Weather/isValidate/1";
			$this -> __errorMsg__ = "네이버날씨 지역코드를 입력해 주세요.";
            return false;
        }
        
        if(check_blank($obj -> timecode)) {
            $this -> __errorCode__ = "/Weather/isValidate/2";
			$this -> __errorMsg__ = "시간코드를 입력해 주세요.";
            return false;
        }
        
        return true;
    }
    
	function saveAll($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if(isset($json -> weather_id)) $this -> getData($json -> weather_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> areacode) && !empty($json -> timecode)) $this -> getDataByCondition("areacode='".$json -> areacode."' AND timecode='".$json -> timecode."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		$this -> updateDate = now();
        if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Weather/saveAll/1";
			$this -> __errorMsg__ = "날씨 정보를 저장하는데 실패하였습니다.";
            return false;
        }
        
        return true;
    }

	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/Weather/joinProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/Weather/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/Weather/masterModifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function deleteModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/Weather/deleteModifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}
}
?>