<?php
class SmartdoorItem extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_item';
        $this -> __pkName__ = 'smartdoor_item_id';

		$this -> smartdoorObj = new Smartdoor();
		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","key":"'.$this -> smartdoorObj -> __pkName__.'","default":0},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
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

	function search($pkValue) {
		global $_lib;

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/search/2";
			$this -> __errorMsg__ = "존재하지 않는 소지품 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_item_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;
		if($_lib['smartdoor'] -> __pkValue__ && empty($json -> smartdoor_id)) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		
		if(empty($json -> smartdoor_id) && !empty($json -> user_id)) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."'");		
			if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
		}
		
		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."' AND a.user_id='".$json -> user_id."'";
		else if(!empty($json -> smartdoor_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."'";
		else if(!empty($json -> user_id)) $condition .= "a.user_id='".$json -> user_id."'";
		else $condition = "";
		
		$listObj = new Components();
		$listObj -> setJoin("SmartdoorItem", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$keyword."%' OR a.name like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorItem/lists/1";
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
				$obj = new SmartdoorItem();
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
        if($obj -> user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorItem/isValidate/1";
			$this -> __errorMsg__ = "회원 정보가 없습니다.";
            return false;
        }
        
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/SmartdoorItem/isValidate/2";
			$this -> __errorMsg__ = "소지품명을 입력해 주세요.";
            return false;
        }

		if($this -> getTotal("smartdoor_item_id!='".$this -> __pkValue__."' AND user_id='".$obj -> user_id."' AND name='".$obj -> name."'")) {
            $this -> __errorCode__ = "/SmartdoorItem/isValidate/3";
			$this -> __errorMsg__ = "이미 등록된 소지품명입니다.";
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
        
        if(isset($json -> smartdoor_item_id)) $this -> getData($json -> smartdoor_item_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && isset($json -> user_id) && !empty($json -> name)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."' AND name='".$json -> name."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
        
		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();

		if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorItem/saveAll/1";
            $this -> __errorMsg__ = '소지품 정보를 저장하는데 실패하였습니다.';
            return false;
        }

		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson('', ['smartdoorObj']).'}');
        
        return true;
    }

	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if(empty($json -> user_id) && $_lib['user'] -> __pkValue__) $json -> user_id = $_lib['user'] -> __pkValue__;			
		if($_lib['user'] -> __pkValue__ && !empty($json -> user_id) && $json -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorItem/joinProcess/2";
			$this -> __errorMsg__ = "로그인 한 사용자는 본인 것만 등록할 수 있습니다.";
			return false;
		}
		
		$smartdoorUserObj = new SmartdoorUser();
		if(!empty($json -> user_id) && !empty($json -> smartdoor_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' AND smartdoor_id='".$json -> smartdoor_id."'");		
		if($smartdoorUserObj -> __pkValue__ <= 0 && !empty($json -> user_id) && empty($json -> smartdoor_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' AND isOwner=1");		
		if($smartdoorUserObj -> __pkValue__ <= 0 && !empty($json -> user_id) && empty($json -> smartdoor_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."'");		
		if($smartdoorUserObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorItem/joinProcess/3";
			$this -> __errorMsg__ = "스마트도어 사용자 등록 후 이용해 주세요.";
			return false;
		} else $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;

        if($this -> getTotal("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."' AND name='".$json -> name."'")) {
            $this -> __errorCode__ = "/SmartdoorItem/joinProcess/4";
			$this -> __errorMsg__ = "이미 등록된 소지품명입니다.";
            return false;
        }
        
        if(!$this -> saveAll($json)) return false;
        
        return true;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		$this -> __errorCode__ = "/SmartdoorItem/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		//정보 가져오기
        if(isset($json -> smartdoor_item_id)) $this -> getData($json -> smartdoor_item_id);

		//등록안된 경우,
        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 소지품 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorItem/masterModifyProcess/3";
			$this -> __errorMsg__ = "본인만 수정할 수 있습니다.";
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

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_item_id)) $this -> getData($json -> smartdoor_item_id);
        
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorItem/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 소지품 정보입니다.";
			return false;
		}
        
		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorItem/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 삭제할 수 있습니다.";
			return false;
		}

		$pkValue = $this -> __pkValue__;

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorItem/deleteProcess/4";
			$this -> __errorMsg__ = "소지품 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/deleteProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":{"'.$this -> __pkName__.'":"'.$pkValue.'"}}');

		return true;
	}
}
?>