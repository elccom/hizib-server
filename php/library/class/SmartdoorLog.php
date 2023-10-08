<?php
class SmartdoorLog extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_log';
        $this -> __pkName__ = 'smartdoor_log_id';

		$this -> smartdoorObj = new Smartdoor();
		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","key":"'.$this -> userObj -> __pkName__.'","default":0},
			{"field":"type","type":"int","size":2,"key":"type","default":0},
			{"field":"code","type":"varchar","size":255,"key":"code","default":""},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
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

	function search($pkValue) {
		global $_lib;

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/search/2";
			$this -> __errorMsg__ = "존재하지 않는 출입기록 정보입니다.";
			return false;
		}

		return true;
	}

	function lists($json='') {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_log_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		
		if(empty($json -> smartdoor_id) && $_lib['user'] -> __pkValue__) {
			$json -> user_id = $_lib['user'] -> __pkValue__;

			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."' AND isOwner=1");		
			if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
			
			if(empty($json -> smartdoor_id)) $json -> smartdoor_id = 0;
		}

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/SmartdoorLog/lists/1";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}
		
		$listObj = new Components();
		$listObj -> setJoin("SmartdoorLog", "a", "a.smartdoor_id='".$json -> smartdoor_id."'");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$keyword."%' OR a.name like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorLog/lists/2";
			$this -> __errorMsg__ = "정렬 설정에 문제가 있어 조회되지 않습니다. 올바른 정렬을 설정해 주세요.";
			return false;
		}

		if($json -> isAll) {
			$listObj -> totalPages = 1;
			echo '{"total":"'.$results -> num_rows.'","totalPages":"1","page":"1","rowsPerPage":"'.$results -> num_rows.'","sort":"'.$json -> sort.'","desc":"'.$json -> desc.'","keyword":"'.$json -> keyword.'","lists":[';
		} else {
			echo '{"total":"'.$listObj -> total.'","totalPages":"'.$listObj -> totalPages.'","page":"'.$json -> page.'","rowsPerPage":"'.$json -> rowsPerPage.'","sort":"'.$json -> sort.'","desc":"'.$json -> desc.'","keyword":"'.$json -> keyword.'","lists":[';
		}

		if($json -> page <= $listObj -> totalPages) {
			$count = 0;
			while($data = $results -> fetch_array()) {
				$obj = new SmartdoorLog();
				$obj -> setData($data, 'a');
				if($count > 0) echo ",";
				echo $obj -> toJson();
				$count++;
			}
		}
		echo ']}';
		exit();
	}	
	
    function isValidate($obj) {
        if($obj -> smartdoor_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorLog/isValidate/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
            return false;
        }
        
        if(check_blank($obj -> type)) {
            $this -> __errorCode__ = "/SmartdoorLog/isValidate/2";
			$this -> __errorMsg__ = "구분 정보를 입력해 주세요.";
            return false;
        }
		
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/SmartdoorLog/isValidate/3";
			$this -> __errorMsg__ = "결과내용을 입력해 주세요.";
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
        
        if(isset($json -> smartdoor_log_id)) $this -> getData($json -> smartdoor_log_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && isset($json -> user_id) && !empty($json -> type) && !empty($json -> code) && !empty($json -> name) && !empty($json -> regDate)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."' AND type='".$json -> type."' AND code='".$json -> code."' AND name='".$json -> name."' AND regDate='".$json -> regDate."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorLog/saveAll/1";
			$this -> __errorMsg__ = "출입기록을 저장하는데 실패하였습니다.";
            return false;
        }
        
        return true;
    }
	
	//키오스크에서만 등록
	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		
		if($json -> smartdoor_id <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/joinProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        if(!$this -> saveAll($json, $isEcho)) return false;

        return true;
	}
	
	//키오스크에서만 수정
	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/SmartdoorLog/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}
	
	//키오스크와 관리자만 수정
	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(isset($json -> smartdoor_log_id)) $this -> getData($json -> smartdoor_log_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/masterModifyProcess/1";
			$this -> __errorMsg__ = "존재하지 않는 출입기록 정보입니다.";
			return false;
		}

        if(!$this -> saveAll($json, $isEcho)) return false;

        return true;
	}

	function deleteProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_log_id)) $this -> getData($json -> smartdoor_log_id);
        
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorLog/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 출입기록 정보입니다.";
			return false;
		}
        
		if($_lib['smartdoor'] -> __pkValue__ && $this -> smartdoor_id != $_lib['smartdoor'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorLog/deleteProcess/3";
			$this -> __errorMsg__ = "해당 키오스크에서만 삭제할 수 있습니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorLog/deleteProcess/4";
			$this -> __errorMsg__ = "출입기록을 삭제하는데 실패하였습니다.";
			return false;
		}
        
        return true;
	}
}
?>