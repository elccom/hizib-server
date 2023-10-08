<?php
class Ums extends Component {    
    function __construct(){
        $this -> __tableName__ = 'ums';
        $this -> __pkName__ = 'ums_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"code","type":"varchar","size":255,"key":"code","default":""},
			{"field":"type","type":"int","size":2,"key":"type","default":1},
			{"field":"subject","type":"varchar","size":255,"key":"subject","default":""},
			{"field":"callback","type":"varchar","size":255,"key":"callback","default":""},
			{"field":"callbackUrl","type":"varchar","size":255,"key":"callbackUrl","default":""},
			{"field":"toPerson","type":"json","default":"{}"},
			{"field":"msg","type":"text","default":""},
			{"field":"data","type":"json","default":"{}"},
			{"field":"requestUrl","type":"varchar","size":255,"key":"requestUrl","default":""},
			{"field":"ip","type":"varchar","size":255,"key":"ip","default":""},
			{"field":"totalCount","type":"int","size":10,"option":"unsigned","key":"totalCount","default":0},
			{"field":"succCount","type":"int","size":10,"option":"unsigned","key":"succCount","default":0},
			{"field":"failCount","type":"int","size":10,"option":"unsigned","key":"failCount","default":0},
			{"field":"isRsv","type":"int","size":1,"key":"isRsv","default":0},
			{"field":"sendDate","type":"timestamp","key":"sendDate","default":"0000-00-00 00:00:00"},
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

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Ums/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Ums/search/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 발송 정보입니다.";
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
			$this -> __errorCode__ = "/Ums/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 20;
		if(empty($json -> sort)) $json -> sort = "a_ums_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("Ums", "a");
		$listObj -> setLeftOuterJoin("User", "b", "b.user_id=a.user_id");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$json -> keyword."%' OR a.subject like '%".$json -> keyword."%' OR a.callback like '%".$json -> keyword."%' OR b.id like '%".$json -> keyword."%' OR b.name like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = (int)$json -> page;
		$listObj -> rowsPerPage = (int)$json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Ums/lists/2";
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
				$obj = new Ums();
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
        if(check_blank($obj -> subject)) {
            $this -> __errorCode__ = "/Ums/isValidate/1";
			$this -> __errorMsg__ = "제목을 입력해 주세요.";
            return false;
        }

        if(check_blank($obj -> callback)) {
            $this -> __errorCode__ = "/Ums/isValidate/2";
			$this -> __errorMsg__ = "회신번호를 입력해 주세요.";
            return false;
        }

        if(check_blank($obj -> toPerson)) {
            $this -> __errorCode__ = "/Ums/isValidate/3";
			$this -> __errorMsg__ = "발송대상을 입력해 주세요.";
            return false;
        }

        if(check_blank($obj -> msg)) {
            $this -> __errorCode__ = "/Ums/isValidate/4";
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
        
        if(isset($json -> ums_id)) $this -> getData($json -> ums_id);
        
        $this -> setJson($json);

		$this -> subject = trim($this -> subject);
		$this -> msg = trim($this -> msg);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Ums/saveAll/1";
			$this -> __errorMsg__ = "메세지 발송 정보를 저장하는데 실패하였습니다.";
            return false;
        }
        
        return true;
    }

	function joinProcess($json = '', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;

		//로그인여부
		if(empty($json -> authcode) && $_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Ums/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
        
        if(empty($json -> subject)) {
            $this -> __errorCode__ = "/Ums/joinProcess/2";
			$this -> __errorMsg__ = "제목을 입력해 주십시오.";
            return false;
        }
              
        if(empty($json -> callback)) {
            $this -> __errorCode__ = "/Ums/joinProcess/3";
			$this -> __errorMsg__ = "회신번호를 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> to_name)) $json -> to_name = "이름없음";

        if(empty($json -> to_handphone)) {
            $this -> __errorCode__ = "/Ums/joinProcess/4";
			$this -> __errorMsg__ = "받는 사람 핸드폰번호를 입력해 주십시오.";
            return false;
        }

		$json -> toPerson = [];
		
		$obj = new stdClass();
		$obj -> name = trim($json -> to_name);
		$obj -> handphone = trim(str_replace("-", "", $json -> to_handphone));

		array_push($json -> toPerson, $obj);

		$json -> totalCount = count($json -> toPerson);
        
        if(empty($json -> msg)) {
            $this -> __errorCode__ = "/Ums/joinProcess/5";
			$this -> __errorMsg__ = "내용을 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> ip)) $json -> ip = $_SERVER['REMOTE_ADDR'];

		if(!$this -> sendProcess($json)) return false;
        
        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }
      
    function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/Ums/modifyProcess/1";
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
		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Ums/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		if(isset($json -> ums_id)) $this -> getData($json -> ums_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Ums/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 발송 정보입니다.";
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

		$this -> __errorCode__ = "/Ums/deleteProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function sendProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		/*
		$this -> __errorCode__ = "/Ums/sendProcess/1";
		$this -> __errorMsg__ = "문자메세지 발송을 지원하지 않습니다.";

		return false;
		*/

		return true;
	}
}
?>