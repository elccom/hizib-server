<?php
class Fcm extends Component {    
    function __construct(){
        $this -> __tableName__ = 'fcm';
        $this -> __pkName__ = 'fcm_id';

		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"title","type":"varchar","size":255,"key":"title","default":""},
			{"field":"body","type":"text","default":""},
			{"field":"data","type":"json","default":"{}"},
			{"field":"options","type":"json","default":"{}"},
			{"field":"result","type":"varchar","size":255,"key":"result","default":""},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
			{"field":"readDate","type":"timestamp","key":"readDate","default":"0000-00-00 00:00:00"},
			{"field":"status","type":"int","size":1,"key":"status","default":1}
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
			$this -> __errorCode__ = "/Fcm/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Fcm/search/2";
			$this -> __errorMsg__ = "존재하지 않는 FCM 정보입니다.";
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
			$this -> __errorCode__ = "/Fcm/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_fcm_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;
		
		$listObj = new Components();
		$listObj -> setJoin("Fcm", "a");
		$listObj -> setJoin("User", "b", "b.user_id=a.user_id");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.title like '%".$keyword."%' OR b.id like '%".$keyword."%' OR b.name like '%".$keyword."%' OR b.nickname like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Fcm/lists/2";
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
				$obj = new Fcm();
				$obj -> setData($data, 'a');
				$obj -> userObj -> setData($data, 'b');
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
            $this -> __errorCode__ = "/Fcm/isValidate/1";
			$this -> __errorMsg__ = "받는 회원 정보가 없습니다.";
            return false;
        }

        if(check_blank($obj -> title)) {
            $this -> __errorCode__ = "/Fcm/isValidate/2";
			$this -> __errorMsg__ = "제목을 입력해 주세요.";
            return false;
        }

        if(check_blank($obj -> body)) {
            $this -> __errorCode__ = "/Fcm/isValidate/3";
			$this -> __errorMsg__ = "내용을 입력해 주세요.";
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
        
        if(isset($json -> fcm_id)) $this -> getData($json -> fcm_id);
        if($this -> __pkValue__ <= 0 && isset($json -> user_id) && !empty($json -> title) && !empty($json -> regDate)) $this -> getDataByCondition("user_id='".$json -> user_id."' AND title='".$json -> title."' AND regDate='".$json -> regDate."'");
        
        $this -> setJson($json);

		$this -> title = trim($this -> title);
		$this -> body = trim($this -> body);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Fcm/saveAll/1";
            $this -> __errorMsg__ = 'FCM 정보를 저장하는데 실패하였습니다.';
            return false;
        }
        
        return true;
    }
	
	function joinProcess($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if(empty($json -> user_id)) {
            $this -> __errorCode__ = "/Fcm/joinProcess/1";
			$this -> __errorMsg__ = "받는 회원 정보가 없습니다.";
            return false;
        }
        
        if(empty($json -> title)) {
            $this -> __errorCode__ = "/Fcm/joinProcess/2";
			$this -> __errorMsg__ = "제목을 입력해 주십시오.";
            return false;
        }
              
        if(empty($json -> body)) {
            $this -> __errorCode__ = "/Fcm/joinProcess/3";
			$this -> __errorMsg__ = "내용을 입력해 주십시오.";
            return false;
        }

		$this -> setJson($json);

        if(check_blank($this -> regDate)) $this -> regDate = now();

        if(!$this -> isValidate($this)) return false;

		$userObj = new User();
		$userObj -> getData($json -> user_id);
		
		$cmd  = $_lib['directory']['python']."/fcm.py send";
		$cmd .= " -token '".$userObj -> fcm_token."'";
		$cmd .= " -title '".$this -> title."'";
		$cmd .= " -body '".$this -> body."'";
		$cmd .= " -data '".jsonencode($this -> data)."'";
		//echo $cmd;

		$result = execpython($cmd);
		
		$data = jsondecode(!empty($result) ? $result : '{"result":true}');
		if(!$data -> result) $this -> status = $_lib['ums_status']['field']['failed'];
		else $this -> status = $_lib['ums_status']['field']['completed'];

		//$isEcho = true;

        if(!$this -> saveAll($json, $isEcho)) return false;
 		
		return true;
    }
      
    function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
		$this -> __errorCode__ = "/Fcm/modifyProcess/1";
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
			$this -> __errorCode__ = "/Fcm/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if(isset($json -> fcm_id)) $this -> getData($json -> fcm_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Fcm/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 FCM 정보입니다.";
			return false;
		}		

		if($_lib['user'] -> __pkValue__ && $this -> user_id != $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/Fcm/masterModifyProcess/3";
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

		$this -> __errorCode__ = "/Fcm/deleteProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}
}
?>