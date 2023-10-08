<?php
class SmartdoorGroup extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_group';
        $this -> __pkName__ = 'smartdoor_group_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"areacode","type":"varchar","size":255,"key":"areacode","default":""},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"address","type":"json","default":"{}"}
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

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/search/1";
			$this -> __errorMsg__ = "존재하지 않는 단지 정보입니다.";
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

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_group_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("SmartdoorGroup", "a");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.areacode like '%".$json -> keyword."%' OR a.name like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorGroup/lists/2";
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
				$obj = new SmartdoorGroup();
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
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/SmartdoorGroup/isValidate/1";
			$this -> __errorMsg__ = "단지명을 입력해 주세요.";
            return false;
        }

		if($this -> getTotal("smartdoor_group_id != '".$obj -> __pkValue__."' AND name='".trim($obj -> name)."'") > 0) {
            $this -> __errorCode__ = "/SmartdoorGroup/isValidate/2";
			$this -> __errorMsg__ = "이미 등록된 단지명 정보입니다. 다른 단지명을 입력해 주세요.";
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
    
        if(isset($json -> smartdoor_group_id)) $this -> getData($json -> smartdoor_group_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> name)) $this -> getDataByCondition("name='".trim($json -> name)."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorGroup/saveAll/2";
			$this -> __errorMsg__ = "단지 정보를 저장하는데 실패하였습니다.";
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

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/joinProcess/1";
			$this -> __errorMsg__ = "관리자 권한으로 로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> name)) {
			$this -> __errorCode__ = "/SmartdoorGroup/joinProcess/2";
			$this -> __errorMsg__ = "단지명이 없습니다.";
			return false;
		}

		//등록된 ID정보 찾기
		$this -> getDataByCondition("name='".trim($json -> name)."'");

		//이미 사용중인 아이디
        if($this -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorGroup/joinProcess/3";
			$this -> __errorMsg__ = "이미 등록된 단지명입니다. 다른 단지명을 입력해 주십시오.";
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

		$this -> __errorCode__ = "/SmartdoorGroup/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if(isset($json -> smartdoor_group_id)) $this -> getData($json -> smartdoor_group_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> name)) $this -> getDataByCondition("name='".trim($json -> name)."'");

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 단지 정보입니다.";
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

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_group_id)) $this -> getData($json -> smartdoor_group_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> name)) $this -> getDataByCondition("name='".trim($json -> name)."'");

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGroup/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 단지 정보입니다.";
			return false;
		}

		if(!parent::delete($isEcho)){
			$this -> __errorCode__ = "/SmartdoorGroup/deleteProcess/3";
			$this -> __errorMsg__ = "단지 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>