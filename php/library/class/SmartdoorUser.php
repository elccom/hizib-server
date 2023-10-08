<?php
class SmartdoorUser extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_user';
        $this -> __pkName__ = 'smartdoor_user_id';

		$this -> smartdoorObj = new Smartdoor();
		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"isOwner","type":"int","size":1,"key":"isOwner","default":1},
			{"field":"isDoorbell","type":"int","size":1,"key":"isDoorbell","default":1},
			{"field":"isAccessRecord","type":"int","size":1,"key":"isAccessRecord","default":1},
			{"field":"isMotionDetect","type":"int","size":1,"key":"isMotionDetect","default":1},
			{"field":"updateDate","type":"timestamp","key":"updateDate","default":"CURRENT_TIMESTAMP"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
			{"field":"isUse","type":"int","size":1,"key":"isUse","default":1}
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
			$this -> __errorCode__ = "/SmartdoorUser/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/search/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
			return false;
		}

		return true;
	}

	function me() {
		global $_lib;

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/me/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."'");

        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/me/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
			return false;
		}

		if($this -> smartdoorObj -> __pkValue__ <= 0) $this -> smartdoorObj -> getData($this -> smartdoor_id);
		if($this -> userObj -> __pkValue__ <= 0) $this -> userObj -> getData($this -> user_id);

		return $this;
	}
	    
    function isValidate($obj) {
        if($obj -> smartdoor_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorUser/isValidate/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
            return false;
        }

 		$smartdoorObj = new Smartdoor();
		$smartdoorObj -> getData($obj -> smartdoor_id);
		
		if($smartdoorObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        if($obj -> user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorUser/isValidate/2";
			$this -> __errorMsg__ = "회원 정보가 없습니다.";
            return false;
        }
        
		$userObj = new User();
		$userObj -> getData($obj -> user_id);
		
		if($userObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}       

		if($this -> getTotal("smartdoor_user_id != '".$obj -> __pkValue__."' AND user_id='".$obj -> user_id."'") > 0) {
            $this -> __errorCode__ = "/SmartdoorUser/isValidate/3";
			$this -> __errorMsg__ = "이미 등록된 스마트도어 사용자 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_user_id";
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

		if(empty($json -> smartdoor_id)) $condition = "";
		else $condition = "a.smartdoor_id='".$json -> smartdoor_id."'";


		$listObj = new Components();
		$listObj -> setJoin("SmartdoorUser", "a", $condition);
		$listObj -> setJoin("Smartdoor", "b", "b.smartdoor_id=a.smartdoor_id");
		$listObj -> setJoin("User", "c", "c.user_id=a.user_id");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(b.code like '%".$json -> keyword."%' OR b.name like '%".$json -> keyword."%' OR c.name like '%".$json -> keyword."%' OR c.nickname like '%".$json -> keyword."%' OR c.handphone like '%".$json -> keyword."%' OR c.email like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorUser/lists/2";
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
				$obj = new SmartdoorUser();
				$obj -> setData($data, 'a');
				$obj -> smartdoorObj -> setData($data, 'b');
				$obj -> userObj -> setData($data, 'c');

				if($count > 0) echo ",";
				echo $obj -> toJson();
				$count++;
			}
		}
		echo ']}';

		exit();
	}
	
	function saveAll($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if(isset($json -> smartdoor_user_id)) $this -> getData($json -> smartdoor_user_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && isset($json -> user_id)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
        
		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
		//기존에 오너로 등록된 사람 찾기
		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$this -> smartdoor_id."' AND isOwner=1");

		if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorUser/saveAll/2";
			$this -> __errorMsg__ = "스마트도어 사용자를 저장하는데 실패하였습니다.";
            return false;
        }
        
		if($smartdoorUserObj -> __pkValue__) {
			$smartdoorUserObj -> isOwner = 0;
			$smartdoorUserObj -> updateDate = now();
			if(!$smartdoorUserObj -> save()) {
				$this -> __errorCode__ = "/SmartdoorUser/saveAll/3";
				$this -> __errorMsg__ = "기존 오너를 일반 사용자로 전환하는데 실패하였습니다.";
				return false;
			}
		}

		return true;
    }

	//[사용자, 관리자] 정보등록
	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && !empty($json -> user_id) && $_lib['user'] -> __pkValue__ != $json -> user_id) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/2";
			$this -> __errorMsg__ = "본인 정보로만 등록이 가능합니다.";
			return false;
		}
					
		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/3";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
			return false;
		}

		$this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");

		if($this -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorUser/joinProcess/4";
			$this -> __errorMsg__ = "이미 등록된 스마트도어 사용자 정보입니다.";
			return false;
		}
		
        if(!$this -> saveAll($json, $isEcho)) return false;

		if($this -> smartdoorObj -> __pkValue__ <= 0 && $this -> smartdoor_id) $this -> smartdoorObj -> getData($this -> smartdoor_id);

        //mqtt로 사용자 삭제 요청
		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/SmartdoorUser/joinProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson().'}');
        
        return true;
	}
	
	//[사용자] 정보수정
	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/modifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 
		
		$json -> user_id = $_lib['user'] -> __pkValue__;

		if(isset($json -> smartdoor_user_id)) $this -> getData($json -> smartdoor_user_id);
        if($this -> __pkValue__ <= 0 && isset($json -> user_id)) $this -> getDataByCondition("user_id='".$json -> user_id."'");

        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/modifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
			return false;
		}

		//$isEcho = true;

		if(!$this -> saveAll($json, $isEcho)) return false;

		if($this -> getTotal("smartdoor_id='".$this -> smartdoor_id."' AND smartdoor_user_id!='".$this -> __pkValue__."'") && $this -> isOwner) {
			if(!$this -> updateByCondition("isOwner=0", "smartdoor_id='".$this -> smartdoor_id."' AND smartdoor_user_id!='".$this -> __pkValue__."'")) {
				$this -> __errorCode__ = "/SmartdoorUser/modifyProcess/3";													  
				$this -> __errorMsg__ = "오너 정보를 변경하는데 실패하였습니다.";
				return false;
			}
		}
        
        return true;
	}
	
	
	//[회원, 관리자] 정보수정
	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 
		
		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;

		if(isset($json -> smartdoor_user_id)) $this -> getData($json -> smartdoor_user_id);
        if($this -> __pkValue__ <= 0 && isset($json -> user_id)) $this -> getDataByCondition("user_id='".$json -> user_id."'");

        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
			return false;
		}

		if(!$this -> saveAll($json, $isEcho)) return false;

		if($this -> getTotal("smartdoor_id='".$this -> smartdoor_id."' AND smartdoor_user_id!='".$this -> __pkValue__."'") && $this -> isOwner) {
			if(!$this -> updateByCondition("isOwner=0", "smartdoor_id='".$this -> smartdoor_id."' AND smartdoor_user_id!='".$this -> __pkValue__."'")) {
				$this -> __errorCode__ = "/SmartdoorUser/modifyProcess/3";													  
				$this -> __errorMsg__ = "오너 정보를 변경하는데 실패하였습니다.";
				return false;
			}
		}
        
        return true;
	}
	
	//[사용자] 정보삭제
	function deleteProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_user_id)) $this -> getData($json -> smartdoor_user_id);
        if($this -> __pkValue__ <= 0 && isset($json -> user_id)) $this -> getDataByCondition("user_id='".$json -> user_id."'");

        if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUser/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
			return false;
		}
        
		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorUser/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 삭제할 수 있습니다.";
			return false;
		}

		$smartdoorObj = new Smartdoor();
		$smartdoorObj -> getData($this -> smartdoor_id);
		$pkValue = $this -> __pkValue__;

		if(!$this -> delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorUser/deleteProcess/4";
			$this -> __errorMsg__ = "스마트도어 사용자 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

        //mqtt로 사용자 삭제 요청
		mqtt_publish($_lib['mqtt']['host'], $smartdoorObj -> getDoorTopic(), '{"request":"/SmartdoorUser/deleteProcess","sender":"'.$smartdoorObj -> getOwnerTopic().'","receiver":"'.$smartdoorObj -> getDoorTopic().'","data":{"smartdoor_user_id":"'.$pkValue.'"}}');
		
		return true;
	}
}
?>