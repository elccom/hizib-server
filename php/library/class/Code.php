<?php
class Code extends Component {
    function __construct(){
        $this -> __tableName__ = 'code';
        $this -> __pkName__ = 'code_id';
        $this -> __columns__ = jsondecode('[
            {"field":"code_id","type":"bigint","option":"unsigned","keytype":"primary","key":"code_id","extra":"auto_increment"},
            {"field":"gcode","type":"varchar","size":255,"key":"gcode","default":""},
            {"field":"code","type":"varchar","size":255,"key":"code","default":""},
            {"field":"name","type":"varchar","size":255,"key":"name","default":""},
            {"field":"nickname","type":"varchar","size":255,"key":"nickname","default":""},
            {"field":"sortNum","type":"int","size":10,"option":"unsigned","key":"sortNum","default":0},
            {"field":"isUse","type":"int","size":1,"key":"isUse","default":1}
        ]');
        
        parent::__construct();
		$this -> install();
        //$this -> tableUpdate();
    }
    
    function tableUpdate() {
        if(!$this -> isExistField('sortNum')) {
            if(!$this -> addTableField('sortNum', 'int(10) unsigned', 0, 'nickname', '', true, true)) return false;
            if(!$this -> addTableIndex('sortNum', 'sortNum')) return false;
        }
    }
    
    function getDataAllByCondition($condition, $isEcho=false) {
        $this -> getDataByCondition($condition, $isEcho);
    }
    
    function getDataAll($pkValue, $isEcho=false) {
        $this -> getData($pkValue, $isEcho);
    }
    
    function getResultsByGcode($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;


        $query = new stdClass();
        $query -> where = "isUse=1";
		if(isset($json -> gcode)) $query -> where .=" AND gcode='".$json -> gcode."'";
        $query -> orderby = "sortNum";
        
        return $this -> getResults($query);
    }
    
    function getListByGcode($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		
		if(empty($json -> gcode)) {
			$this -> __errorCode__ = "/Code/getListByGcode/1";
			$this -> __errorMsg__ = "코드 그룹 정보가 없습니다.";
			return false;
		}

        $array = array();
        $results = $this -> getResultsByGcode($json);
        while($data = $results -> fetch_array()) {
            $obj = new Code();
            $obj -> setData($data);
            
            array_push($array, $obj);
        }
        
        return $array;
    }

	function setLibVarsByGcode($name, $gcode) {
		global $_lib;

        $lists = $this -> getListByGcode($gcode);
        
        foreach($lists as $codeObj) {
            $_lib[$name]['name'][$codeObj -> code] = $codeObj -> name;
        }
	}

	function search($pkValue) {
		global $_lib;

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/search/1";
			$this -> __errorMsg__ = "존재하지 않는 코드 정보입니다.";
			return false;
		}

		return true;
	}
	
    function isValidate($obj) {
        if(check_blank($obj -> gcode)) {
            $this -> __errorCode__ = "/Code/isValidate/1";
            $this -> __errorMsg__ = '코드그룹을 입력해 주십시오.';
            return false;
        }
        
        if(check_blank($obj -> code)) {
            $this -> __errorCode__ = "/Code/isValidate/2";
            $this -> __errorMsg__ = '코드를 입력해 주십시오.';
            return false;
        }
        
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/Code/isValidate/3";
            $this -> __errorMsg__ = '코드명을 입력해 주십시오.';
            return false;
        }
        
        return true;
    }
    
    
    function saveAll($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

        if($this -> __pkValue__ <= 0 && isset($json -> code_id)) $this -> getData($json -> code_id);
        if($this -> __pkValue__ <= 0 && isset($json -> gcode) && isset($json -> code)) $this -> getDataByCondition("gcode='".$json -> gcode."' AND code='".$json -> code."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		if($this -> getTotal("gcode='".$this -> gcode."' AND sortNum='".$this -> sortNum."'")) {
			if(!$this -> updateByCondition("sortNum=sortNum+1", "code_id!='".$this -> __pkValue__."' AND gcode='".$this -> gcode."' AND sortNum>='".$this -> sortNum."'")) {
				$this -> __errorCode__ = "/Code/saveAll/1";
				$this -> __errorMsg__ = '코드정보 저장하는데 실패하였습니다.';
				return false;
			}
		}
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Code/saveAll/1";
            $this -> __errorMsg__ = '코드정보 저장하는데 실패하였습니다.';
            return false;
        }
		
		if(!$this -> autosortProcess()) return false;

        return true;
    }

	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
        if(empty($json -> gcode)) {
            $this -> __errorCode__ = "/Code/joinProcess/2";
			$this -> __errorMsg__ = "코드그룹을 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> code)) {
            $this -> __errorCode__ = "/Code/joinProcess/3";
			$this -> __errorMsg__ = "코드를 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> name)) {
            $this -> __errorCode__ = "/Code/joinProcess/4";
			$this -> __errorMsg__ = "코드명을 입력해 주십시오.";
            return false;
        }

        if($this -> __pkValue__ <= 0 && isset($json -> code_id)) $this -> getData($json -> code_id);
        if($this -> __pkValue__ <= 0 && isset($json -> gcode) && isset($json -> code)) $this -> getDataByCondition("gcode='".$json -> gcode."' AND code='".$json -> code."'");
		
		if($this -> __pkValue__) {
			$this -> __errorCode__ = "/Code/joinProcess/5";
			$this -> __errorMsg__ = "이미 등록된 코드 정보입니다.";
			return false;
		} 

		if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		$this -> __errorCode__ = "/Code/modifyProcess/1";
		$this -> __errorMsg__ = "수정 기능을 지원하지 않습니다.";

		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if($this -> __pkValue__ <= 0 && isset($json -> code_id)) $this -> getData($json -> code_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 코드 정보입니다.";
			return false;
		}		

        if(!$this -> saveAll($json, $isEcho)) return false;	

		return true;
	}
	
	//관리자만 삭제 가능
	function deleteProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> code_id)) $this -> getData($json -> code_id);

		$gcode = $this -> gcode;
        
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Code/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 코드 정보입니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/Code/deleteProcess/3";
			$this -> __errorMsg__ = "코드를 삭제하는데 실패하였습니다.";
			return false;
		}

		if(!$this -> autosortProcess('{"gcode":"'.$gcode.'"}')) return false;

		return true;
	}

	//자동으로 순서를 정렬하는 메소드
	function autosortProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$query = new stdClass();
		$query -> where = "gcode='".$this -> gcode."'";
		$query -> orderby = "sortNum";
		$results = $this -> getResults($query);
		$sortNum = 1;
		while($data = $results -> fetch_array()) {
			if($data['sortNum'] != $sortNum) {
				$q = "UPDATE ".$this -> __tableName__." SET sortNum='".$sortNum."' WHERE code_id='".$data['code_id']."'";
				$r = $_lib['db']['handler']['master'] -> query($q);
				if(!$r) {
					$this -> __errorCode__ = "/Code/autosortProcess/1";
					$this -> __errorMsg__ = $sortNum."번째 재정렬 하는데 실패하였습니다.";
					return false;
				}
			}

			$sortNum ++;
		}
		
		return true;
	}
}
?>