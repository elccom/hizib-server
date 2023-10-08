<?php
class SmartdoorUserInvite extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_user_invite';
        $this -> __pkName__ = 'smartdoor_user_invite_id';

		$this -> smartdoorObj = new Smartdoor();
		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"handphone","type":"varchar","size":255,"key":"handphone","default":""},
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
		
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/search/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 초대 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/search/3";
			$this -> __errorMsg__ = "본인만 조회할 수 있습니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_user_invite_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;

		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;

		if(empty($json -> smartdoor_id) && $_lib['user'] -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."' AND isOwner=1");		
			if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
			
			if(empty($json -> smartdoor_id)) $json -> smartdoor_id = 0;
		} 

		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."' AND a.user_id='".$json -> user_id."'";
		else if(!empty($json -> smartdoor_id)) $condition = "a.smartdoor_id='".$json -> smartdoor_id."'";
		else if(!empty($json -> user_id)) $condition = "a.user_id='".$json -> user_id."'";
		else $condition = '';
		
		$listObj = new Components();
		$listObj -> setJoin("SmartdoorUserInvite", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.name like '%".$json -> keyword."%' OR a.handphone like '%".$json -> keyword."%')");
		if(!empty($json -> name)) $listObj -> setAndCondition("a.name='".$json -> name."'");
		if(!empty($json -> handphone)) $listObj -> setAndCondition("a.handphone='".$json -> handphone."'");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/lists/1";
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
				$obj = new SmartdoorUserInvite();
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
            $this -> __errorCode__ = "/SmartdoorUserInvite/isValidate/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
            return false;
        }
        
        if($obj -> user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorUserInvite/isValidate/2";
			$this -> __errorMsg__ = "회원 정보가 없습니다.";
            return false;
        }
        
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/SmartdoorUserInvite/isValidate/3";
			$this -> __errorMsg__ = "성명을 입력해 주세요.";
            return false;
        }
        
        if(check_blank($obj -> handphone)) {
            $this -> __errorCode__ = "/SmartdoorUserInvite/isValidate/4";
			$this -> __errorMsg__ = "핸드폰 번호를 입력해 주세요.";
            return false;
        }

		if(!preg_match('/^(010|011|016|017|018|019)-[0-9]{3,4}-[0-9]{4}$/', format_phone($obj -> handphone))) {
            $this -> __errorCode__ = "/SmartdoorUserInvite/isValidate/5";
			$this -> __errorMsg__ = "핸드폰 번호가 형식에 맞지 않습니다.";
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
        
        if(isset($json -> smartdoor_user_invite_id)) $this -> getData($json -> smartdoor_user_invite_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && isset($json -> user_id) && !check_blank($json -> name) && !check_blank($json -> handphone)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."' AND name='".$json -> name."' AND handphone='".$json -> handphone."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
        
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorUserInvite/saveAll/1";
            $this -> __errorMsg__ = '사용자 초대를 저장하는데 실패하였습니다.';
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
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if(empty($json -> user_id) && $_lib['user'] -> __pkValue__) $json -> user_id = $_lib['user'] -> __pkValue__;
		if(empty($json -> user_id)) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/joinProcess/2";
			$this -> __errorMsg__ = "회원정보가 없습니다.";
			return false;
		}

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' AND isOwner=1");		

		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/joinProcess/3";
			$this -> __errorMsg__ = "오너만 초대할 수 있습니다. 오너 등록 후 이용해 주세요.";
			return false;
		}
		
		$json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;			

        if(!$this -> saveAll($json, $isEcho)) return false;

		$smartdoorUserObj -> userObj -> getData($smartdoorUserObj -> user_id);

		//문자 발송 데이터 설정
		$data = new stdClass();
		$data -> to_name = $this -> name;
		$data -> to_handphone = str_replace("-", "", $this -> handphone);
		$data -> callback = $_lib['website'] -> callback;
		$data -> subject = "Hizib 초대";
		$data -> msg  = $smartdoorUserObj -> userObj -> name."님이 ".$this -> name."님을 초대하셨습니다.\n";
		$data -> msg .= $_lib['directory']['app'];

		$umsObj = new Ums();
		if(!$umsObj -> joinProcess($data)) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/joinProcess/3";
			$this -> __errorMsg__ = "초대 문자를 발송하는데 실패하였습니다.";
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
		
		$this -> __errorCode__ = "/SmartdoorUserInvite/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스 입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/SmartdoorUserInvite/masterModifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스 입니다.";
		return false;
	}

	//관리자만 삭제 가능
	function deleteProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_user_invite_id)) $this -> getData($json -> smartdoor_user_invite_id);
        
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 초대 정보입니다.";
			return false;
		}
        
		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 삭제할 수 있습니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorUserInvite/deleteProcess/4";
			$this -> __errorMsg__ = "초대 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>