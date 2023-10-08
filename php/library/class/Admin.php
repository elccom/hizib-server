<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Admin extends Component {    
    function __construct(){
        $this -> __tableName__ = 'admin';
        $this -> __pkName__ = 'admin_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"id","type":"varchar","size":255,"key":"id","default":""},
			{"field":"passwd","type":"varchar","size":255,"key":"passwd","default":""},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"handphone","type":"varchar","size":255,"key":"handphone","default":""},
			{"field":"email","type":"varchar","size":255,"key":"email","default":""},
			{"field":"loginTimes","type":"int","size":10,"default":0},
			{"field":"lastLogin","type":"timestamp","key":"lastLogin","default":"CURRENT_TIMESTAMP","extra":"on update CURRENT_TIMESTAMP"},
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
			$this -> __errorCode__ = "/Admin/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Admin/search/2";
			$this -> __errorMsg__ = "존재하지 않는 관리자 정보입니다.";
			return false;
		}
	}

	function lists($json='') {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Admin/lists/2";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_admin_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("Admin", "a");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.id like '%".$json -> keyword."%' OR a.name like '%".$json -> keyword."%' OR a.handphone like '%".$json -> keyword."%' OR a.email like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Admin/lists/3";
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
				$obj = new Admin();
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
        if(empty($obj -> id)) {
            $this -> __errorCode__ = "/Admin/isValidate/1";
			$this -> __errorMsg__ = "아이디를 입력해 주세요.";
            return false;
        }

		if($this -> getTotal("admin_id!='".$obj -> __pkValue__."' AND id='".$obj -> id."'")) {
            $this -> __errorCode__ = "/Admin/isValidate/2";
			$this -> __errorMsg__ = "이미 등록된 아이디입니다.";
            return false;
        }

        if(empty($obj -> name)) {
            $this -> __errorCode__ = "/Admin/isValidate/3";
			$this -> __errorMsg__ = "성명을 입력해 주세요.";
            return false;
        }

        if(empty($obj -> passwd)) {
            $this -> __errorCode__ = "/Admin/isValidate/4";
			$this -> __errorMsg__ = "비밀번호를 입력해 주십시오.";
            return false;
        }

        if(empty($obj -> handphone)) {
            $this -> __errorCode__ = "/Admin/isValidate/5";
			$this -> __errorMsg__ = "핸드폰 번호를 입력해 주세요.";
            return false;
        }

		if(!preg_match('/^(010|011|016|017|018|019)-[^0][0-9]{3,4}-[0-9]{4}/', $obj -> handphone)) {
            $this -> __errorCode__ = "/Admin/isValidate/6";
			$this -> __errorMsg__ = "핸드폰 번호를 하이픈(-)를 포함하여 입력해 주세요.";
            return false;
		}

		if(!empty($obj -> name) && !empty($obj -> handphone) && $this -> getTotal("admin_id!='".$obj -> __pkValue__."' AND name='".$obj -> name."' AND handphone='".$obj -> handphone."'")) {
            $this -> __errorCode__ = "/Admin/isValidate/7";
			$this -> __errorMsg__ = "이미 등록된 성명과 핸드폰번호 입니다.";
            return false;
        }

        if(!empty($obj -> email) && !check_email($obj -> email)) {
            $this -> __errorCode__ = "/Admin/isValidate/8";
			$this -> __errorMsg__ = "이메일 형식에 맞게 입력해 주세요.";
            return false;
        }       

		if(!empty($obj -> name) && !empty($obj -> email) && $this -> getTotal("admin_id!='".$obj -> __pkValue__."' AND name='".$obj -> name."' AND email='".$obj -> email."'")) {
            $this -> __errorCode__ = "/Admin/isValidate/9";
			$this -> __errorMsg__ = "이미 등록된 성명과 이메일 입니다.";
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
        
        if(isset($json -> admin_id)) $this -> getData($json -> admin_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> id)) $this -> getDataByCondition("id='".$json -> id."'");
        
        $this -> setJson($json);

		$this -> id = trim($this -> id);
		$this -> passwd = trim($this -> passwd);
		$this -> name = trim($this -> name);
		$this -> handphone = trim($this -> handphone);
		$this -> email = trim($this -> email);

        if(!$this -> isValidate($this)) return false;
        
		if(check_blank($this -> lastLogin)) $this -> lastLogin = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Admin/saveAll/5";
			$this -> __errorMsg__ = "관리자 정보룰 저장하는데 실패하였습니다.";
            return false;
        }
        
        return true;
    }

	function joinProcess($json = '', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if(empty($json -> id)) {
            $this -> __errorCode__ = "/Admin/joinProcess/1";
			$this -> __errorMsg__ = "아이디를 입력해 주십시오.";
            return false;
        } else $json -> id = trim($json -> id);
        
        if(!preg_match("/[0-9a-zA-Z]/i", $json -> id)) {
            $this -> __errorCode__ = "/Admin/joinProcess/2";
			$this -> __errorMsg__ = "아이디를 영문과 숫자를 사용해 만들어 주세요.";
            return false;
        }
        
        if($this -> getTotal() && strlen($json -> id) < 4) {
            $this -> __errorCode__ = "/Admin/joinProcess/3";
			$this -> __errorMsg__ = "아이디를 최소 4자 이상으로 작성해 주세요.";
            return false;
        }
		
		//등록된 ID정보 찾기
		$this -> getDataByCondition("id='".trim($json -> id)."'");
        
		//이미 사용중인 아이디
        if($this -> __pkValue__) {
            $this -> __errorCode__ = "/Admin/joinProcess/4";
			$this -> __errorMsg__ = "이미 등록된 아이디입니다. 다른 아이디를 선택해 주십시오.";
            return false;
		}
        
        if(empty($json -> name)) {
            $this -> __errorCode__ = "/Admin/joinProcess/5";
			$this -> __errorMsg__ = "성명을 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> passwd)) {
            $this -> __errorCode__ = "/Admin/joinProcess/6";
			$this -> __errorMsg__ = "비밀번호를 입력해 주십시오.";
            return false;
        }
        
        if(strlen($json -> passwd) < 4) {
            $this -> __errorCode__ = "/Admin/joinProcess/7";
			$this -> __errorMsg__ = "비밀번호를 최소 4자 이상으로 작성해 주세요.";
            return false;
        }
        
        if(empty($json -> repasswd)) {
            $this -> __errorCode__ = "/Admin/joinProcess/8";
			$this -> __errorMsg__ = "비밀번호 확인을 입력해 주십시오.";
            return false;
        }
        
        if($json -> passwd != $json -> repasswd) {
            $this -> __errorCode__ = "/Admin/joinProcess/9";
			$this -> __errorMsg__ = "비밀번호와 비밀번호 확인이 다릅니다. 다시 확인해 주십시오.";
            return false;
        }
 
        if(empty($json -> handphone)) {
            $this -> __errorCode__ = "/Admin/joinProcess/10";
			$this -> __errorMsg__ = "핸드폰 번호를 입력해 주세요.";
            return false;
        }

		if(!preg_match('/^(010|011|016|017|018|019)-[^0][0-9]{3,4}-[0-9]{4}/', $json -> handphone)) {
            $this -> __errorCode__ = "/Admin/joinProcess/11";
			$this -> __errorMsg__ = "핸드폰 번호를 하이픈(-)를 포함하여 형식에 맞게 입력해 주세요.";
            return false;
		}

		if(!empty($json -> name) && !empty($json -> handphone) && $this -> getTotal("name='".$json -> name."' AND handphone='".$json -> handphone."'")) {
            $this -> __errorCode__ = "/Admin/joinProcess/12";
			$this -> __errorMsg__ = "이미 등록된 성명과 핸드폰번호 입니다.";
            return false;
        }


        if(!empty($json -> email) && !check_email($json -> email)) {
            $this -> __errorCode__ = "/Admin/joinProcess/12";
			$this -> __errorMsg__ = "이메일 형식에 맞게 입력해 주세요.";
            return false;
        }       

		if(!empty($json -> name) && !empty($json -> email) && $this -> getTotal("name='".$json -> name."' AND email='".$json -> email."'")) {
            $this -> __errorCode__ = "/Admin/joinProcess/13";
			$this -> __errorMsg__ = "이미 등록된 성명과 핸드폰번호 입니다.";
            return false;
        }
       
        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }
      
    function modifyProcess($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Admin/modifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        $this -> getData($_lib['admin'] -> __pkValue__);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Admin/modifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 관리자 정보입니다.";
			return false;
        }

		if(!isset($json -> passwd)) $json -> passwd = "";
        if(!isset($json -> newpasswd)) $json -> newpasswd = "";
        if(!isset($json -> repasswd)) $json -> repasswd = "";
        
		if($this -> passwd != $json -> passwd) {            
            $this -> __errorCode__ = "/Admin/modifyProcess/3";
			$this -> __errorMsg__ = "이전 비밀번호가 틀립니다. 이전 비밀번호를 확인해 주십시오.";
            return false;
        }

		if(check_blank($json -> newpasswd))  {
            $this -> __errorCode__ = "/Admin/modifyProcess/4";
			$this -> __errorMsg__ = "새 비밀번호를 입력해 주십시오.";
            return false;
        }
        
        if(strlen($json -> newpasswd) < 4) {
            $this -> __errorCode__ = "/Admin/modifyProcess/5";
			$this -> __errorMsg__ = "새 비밀번호를 최소 4자 이상으로 작성해 주세요.";
            return false;
        }

		if(check_blank($json -> repasswd))  {
            $this -> __errorCode__ = "/Admin/modifyProcess/6";
			$this -> __errorMsg__ = "새 비밀번호 확인을 입력해 주십시오.";
            return false;
        }

		if($json -> newpasswd != $json -> repasswd) {
            $this -> __errorCode__ = "/Admin/modifyProcess/7";
			$this -> __errorMsg__ = "새 비밀번호와 새 비밀번호 확인이 일치하지 않습니다.";
            return false;
		}        
        
        if(!$this -> saveAll($json, $isEcho)) return false;

        return true;
    }
    
	function masterModifyProcess($json='', $isEcho=false) {
		$this -> __errorCode__ = "/Admin/masterModifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}
    
	function deleteProcess($json='', $isEcho=false) {
		$this -> __errorCode__ = "/Admin/deleteProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function login($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if(empty($json -> id)) {
            $this -> __errorCode__ = "/Admin/login/1";
			$this -> __errorMsg__ = "아이디를 입력해 주세요.";
            return false;
		}

		$this -> getDataByCondition("id='".trim($json -> id)."'");

		if($this -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/Admin/login/2";
			$this -> __errorMsg__ = "등록되지 않은 아이디입니다.";
            return false;
		}

		if(trim($this -> passwd) != trim($json -> passwd)) {
            $this -> __errorCode__ = "/Admin/login/3";
			$this -> __errorMsg__ = "비밀번호를 다시 입력해 주세요.";
            return false;
		}

		$payload = array(
			"admin_id" => (int)$this -> __pkValue__,
			"exp" => time() + (60 * 60 * 5)
		);

		//토큰발행
		$jwt = JWT::encode($payload, $_lib['website'] -> name, 'HS512');
		
		$result = new stdClass();
		$result -> token = $jwt;

		return $result;

	}
}
?>