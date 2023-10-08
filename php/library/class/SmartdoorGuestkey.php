<?php
class SmartdoorGuestkey extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_guestkey';
        $this -> __pkName__ = 'smartdoor_guestkey_id';

		$this -> smartdoorObj = new Smartdoor();
		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"handphone","type":"varchar","size":255,"key":"handphone","default":""},
			{"field":"passwd","type":"varchar","size":255,"key":"passwd","default":""},
			{"field":"startDate","type":"timestamp","key":"startDate","default":"0000-00-00 00:00:00"},
			{"field":"stopDate","type":"timestamp","key":"stopDate","default":"0000-00-00 00:00:00"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
			{"field":"status","type":"int","size":1,"key":"status","default":0}
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
			$this -> __errorCode__ = "/SmartdoorGuestkey/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/search/2";
			$this -> __errorMsg__ = "존재하지 않는 게스트키 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_guestkey_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		if($_lib['user'] -> __pkValue__) $json -> user_id = $_lib['user'] -> __pkValue__;
		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		
		if(empty($json -> smartdoor_id) && !empty($json -> user_id)) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."'");		
			if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
		}

		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."' AND a.user_id='".$json -> user_id."'";
		else if(!empty($json -> smartdoor_id) && empty($json -> user_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."'";
		else if(empty($json -> smartdoor_id) && !empty($json -> user_id)) $condition = "a.user_id='".$json -> user_id."'";
		else $condition = "";
		
		$listObj = new Components();
		$listObj -> setJoin("SmartdoorGuestkey", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$keyword."%' OR a.name like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/lists/1";
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
				$obj = new SmartdoorGuestkey();
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
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
            return false;
        }

		$smartdoorObj = new Smartdoor();
		$smartdoorObj -> getData($obj -> smartdoor_id);

		if($smartdoorObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
            return false;
        }
        
        if($obj -> user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/3";
			$this -> __errorMsg__ = "회원 정보가 없습니다.";
            return false;
        }

		$userObj = new User();
		$userObj -> getData($obj -> user_id);

		if($userObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/4";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
            return false;
        }

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$obj -> smartdoor_id."' AND user_id='".$obj -> user_id."'");

		if($smartdoorUserObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/5";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자 정보입니다.";
            return false;
        }
        
        if(check_blank($obj -> handphone)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/6";
			$this -> __errorMsg__ = "핸드폰번호를 입력해 주세요.";
            return false;
        }   
		
		if(empty($obj -> passwd)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/7";
			$this -> __errorMsg__ = "비밀번호를 입력해 주세요.";
            return false;
		}
		
		if(strlen($obj -> passwd) < 4 || strlen($obj -> passwd) > 8) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/8";
			$this -> __errorMsg__ = "비밀번호를 4자리 이상 8자리 이내로 입력해 주세요.";
            return false;
		}

		if(empty($obj -> startDate)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/9";
			$this -> __errorMsg__ = "시작일자를 입력해 주세요.";
            return false;
		}

		if(empty($obj -> stopDate)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/10";
			$this -> __errorMsg__ = "종료일자를 입력해 주세요.";
            return false;
		}

		if(getTimestamp($obj -> startDate) > getTimestamp($obj -> stopDate)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/isValidate/11";
			$this -> __errorMsg__ = "시작일자가 종료일자 보다 클 수 없습니다.";
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
        
        if(isset($json -> smartdoor_guestkey_id)) $this -> getData($json -> smartdoor_guestkey_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && isset($json -> user_id) && !empty($json -> handphone) && !check_blank($json -> startDate) && !check_blank($json -> stopDate)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."' AND handphone='".$json -> handphone."' AND startDate='".$json -> startDate."' AND stopDate='".$json -> stopDate."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
		
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorGuestkey/saveAll/1";
            $this -> __errorMsg__ = '게스트키 정보를 저장하는데 실패하였습니다.';
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

		//로그인여부
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 
		
		//회원정보가 없는데 로그인한 사용자라면 로그인정보를 회원정보로 사용
		if(empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;		

		//오너 데이터
		$smartdoorUserObj = new SmartdoorUser();

		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");
		elseif(empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."'");

		if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
		else {
			$this -> __errorCode__ = "/SmartdoorGuestkey/joinProcess/2";
			$this -> __errorMsg__ = "등록되지 않은 스마트도어 사용자입니다.";
			return false;			
		}

        if(!$this -> saveAll($json, $isEcho)) return false;
		
		if($this -> smartdoorObj -> __pkValue__ <= 0) $this -> smartdoorObj -> getData($this -> smartdoor_id);

        //mqtt로 게스트키 발행 요청
		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/Smartdoor/guestkeyJoinProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson('', ['smartdoorObj','userObj']).'}');

		//문자 발송 데이터 설정
		$data = new stdClass();
		$data -> to_name = '이름없음';
		$data -> to_handphone = str_replace("-", "", $this -> handphone);
		$data -> callback = $_lib['website'] -> callback;
		$data -> subject = "[".$_lib['website'] -> nickname."] 게스트키 발행";
		$data -> msg  = "[일회용 비밀번호 : ".(string)$this -> passwd."]\n";
		$data -> msg .= "시작일시 : ".$this -> startDate."\n";
		$data -> msg .= "종료일시 : ".$this -> stopDate."\n";
		$data -> msg .= "\n";
		$data -> msg .= "번호가 노출되지 않도록 주의해 주세요.";

		$umsObj = new Ums();
		if(!$umsObj -> joinProcess($data)) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/joinProcess/3";
			$this -> __errorMsg__ = $umsObj -> __errorMsg__;
			return false;
		}
        
        return true;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		$this -> __errorCode__ = "/SmartdoorGuestkey/modifyProcess/1";
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
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if(isset($json -> smartdoor_guestkey_id)) $this -> getData($json -> smartdoor_guestkey_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 게스트키 정보입니다.";
			return false;
		}		

		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/masterModifyProcess/3";
			$this -> __errorMsg__ = "본인만 수정할 수 있습니다.";
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

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_guestkey_id)) $this -> getData($json -> smartdoor_guestkey_id);
        
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 게스트키 정보입니다.";
			return false;
		}
        
		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 삭제할 수 있습니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorGuestkey/deleteProcess/4";
			$this -> __errorMsg__ = "게스트키 발행 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>