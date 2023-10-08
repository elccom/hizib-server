<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User extends Component {    
    function __construct(){
        $this -> __tableName__ = 'user';
        $this -> __pkName__ = 'user_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"id","type":"varchar","size":255,"key":"id","default":""},
			{"field":"passwd","type":"varchar","size":255,"key":"passwd","default":""},
			{"field":"fcm_token","type":"varchar","size":255,"key":"fcm_token","default":""},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"nickname","type":"varchar","size":255,"key":"nickname","default":""},
			{"field":"ci","type":"varchar","size":255,"key":"ci","default":""},
			{"field":"handphone","type":"varchar","size":255,"key":"handphone","default":""},
			{"field":"email","type":"varchar","size":255,"key":"email","default":""},
			{"field":"picture","type":"json","default":"{}"},
			{"field":"faces","type":"json","default":"{}"},
			{"field":"loginTimes","type":"int","size":10,"default":0},
			{"field":"lastLogin","type":"timestamp","key":"lastLogin","default":"CURRENT_TIMESTAMP","extra":"on update CURRENT_TIMESTAMP"},
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

	function getFacePath($idx) {
		$faces = (array)$this -> faces;
		foreach($faces as $obj) {
			$obj = (object)$obj;
			if($obj -> idx == $idx) return $obj -> file;
		}

		return '';
	}

	function getFaceUrl($idx) {
		return str_replace($_lib['directory']['home'], "", $this -> getFacePath($idx));
	}

	function search($pkValue) {
		global $_lib;
		
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 
		
		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/search/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $_lib['user'] -> __pkValue__ != $pkValue) {
			$this -> __errorCode__ = "/User/search/3";
			$this -> __errorMsg__ = "본인만 조회할 수 있습니다.";
			return false;
		}

		return true;
	}

	function me($json='') {
		global $_lib;
		
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/me/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($_lib['user'] -> __pkValue__);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/me/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}

		return $this;
	}

	function lists($json='') {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_user_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("User", "a");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.id like '%".$json -> keyword."%' OR a.name like '%".$json -> keyword."%' OR a.handphone like '%".$json -> keyword."%' OR a.email like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/User/lists/2";
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
				$obj = new User();
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
            $this -> __errorCode__ = "/User/isValidate/1";
			$this -> __errorMsg__ = "아이디를 입력해 주세요.";
            return false;
        }

		if($this -> getTotal("user_id!='".$obj -> __pkValue__."' AND id='".$obj -> id."'")) {
            $this -> __errorCode__ = "/User/isValidate/2";
			$this -> __errorMsg__ = "이미 등록된 아이디입니다.";
            return false;
        }

        if(empty($obj -> passwd)) {
            $this -> __errorCode__ = "/User/isValidate/3";
			$this -> __errorMsg__ = "비밀번호를 입력해 주세요.";
            return false;
        }

        if(empty($obj -> name)) {
            $this -> __errorCode__ = "/User/isValidate/4";
			$this -> __errorMsg__ = "성명을 입력해 주세요.";
            return false;
        }

        if(empty($obj -> handphone)) {
            $this -> __errorCode__ = "/User/isValidate/5";
			$this -> __errorMsg__ = "핸드폰 번호를 입력해 주세요.";
            return false;
        }

		if(!preg_match('/^(010|011|016|017|018|019)-[0-9]{3,4}-[0-9]{4}$/', format_phone($obj -> handphone))) {
            $this -> __errorCode__ = "/User/isValidate/6";
			$this -> __errorMsg__ = "핸드폰 번호를 정확하게 하이픈(-)을 포함하여 입력해 주세요.";
            return false;
		}

		if(!empty($obj -> name) && !empty($obj -> handphone) && $this -> getTotal("user_id!='".$obj -> __pkValue__."' AND name='".$obj -> name."' AND handphone='".$obj -> handphone."'")) {
            $this -> __errorCode__ = "/User/isValidate/7";
			$this -> __errorMsg__ = "이미 등록된 성명과 핸드폰번호 입니다.";
            return false;
        }

        if(!empty($obj -> email) && !check_email($obj -> email)) {
            $this -> __errorCode__ = "/User/isValidate/8";
			$this -> __errorMsg__ = "이메일 형식에 맞게 입력해 주세요.";
            return false;
        } 		
		
		if(!empty($obj -> name) && !empty($obj -> email) && $this -> getTotal("user_id!='".$obj -> __pkValue__."' AND name='".$obj -> name."' AND email='".$obj -> email."'")) {
            $this -> __errorCode__ = "/User/isValidate/9";
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
        
        if(isset($json -> user_id)) $this -> getData($json -> user_id);
        if($this -> __pkValue__ <= 0 && !empty($json -> id)) $this -> getDataByCondition("id='".$json -> id."'");
        
        $this -> setJson($json);

		$this -> id = trim($this -> id);
		$this -> passwd = trim($this -> passwd);
		$this -> name = trim($this -> name);
		$this -> nickname = trim($this -> nickname);
		$this -> handphone = trim($this -> handphone);
		$this -> email = trim($this -> email);

        if(!$this -> isValidate($this)) return false;
        
		if($this -> getTotal("user_id != '".$this -> __pkValue__."' AND id='".trim($this -> id)."'") > 0) {
            $this -> __errorCode__ = "/User/saveAll/1";
			$this -> __errorMsg__ = "이미 등록된 아이디입니다. 다른 아이디를 선택해 주십시오.";
            return false;
		}

		if(check_blank($this -> lastLogin)) $this -> lastLogin = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/User/saveAll/2";
			$this -> __errorMsg__ = "회원정보를 저장하는데 실패하였습니다.";
            return false;
        }

		$obj = new SmartdoorUser();
		$obj -> getDataByCondition("user_id='".$this -> __pkValue__."'");

		if($obj -> __pkValue__) {
			if($obj -> smartdoorObj -> __pkValue__ <= 0) $obj -> smartdoorObj -> getData($obj -> smartdoor_id);

			$msg = '{"request":"/User/updateProcess","sender":"'.$obj -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$obj -> smartdoorObj -> getDoorTopic().'","data":{"user_id":'.(int)$this -> __pkValue__.',"date":"'.now().'"}}';

			//mqtt로 문열기 실행
			mqtt_publish($_lib['mqtt']['host'], $obj -> smartdoorObj -> getDoorTopic(), $msg);
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
            $this -> __errorCode__ = "/User/joinProcess/1";
			$this -> __errorMsg__ = "아이디를 입력해 주십시오.";
            return false;
        } else $json -> id = trim($json -> id);
        
        if(!preg_match("/[0-9a-zA-Z]/i", $json -> id)) {
            $this -> __errorCode__ = "/User/joinProcess/2";
			$this -> __errorMsg__ = "아이디를 영문, 숫자를 사용해 만들어 주세요.";
            return false;
        }
        
        if(strlen($json -> id) < 6) {
            $this -> __errorCode__ = "/User/joinProcess/3";
			$this -> __errorMsg__ = "아이디를 최소 6자 이상으로 작성해 주세요.";
            return false;
        }

		$this -> getDataByCondition("id='".$json -> id."'");

		if($this -> __pkValue__) {
            $this -> __errorCode__ = "/User/joinProcess/4";
			$this -> __errorMsg__ = "이미 등록된 아이디입니다. 다른 아이디를 입력해 주세요.";
            return false;
		}
		
        if(empty($json -> passwd)) {
            $this -> __errorCode__ = "/User/joinProcess/5";
			$this -> __errorMsg__ = "비밀번호를 입력해 주십시오.";
            return false;
        }
        
        if(strlen($json -> passwd) < 4) {
            $this -> __errorCode__ = "/User/joinProcess/6";
			$this -> __errorMsg__ = "비밀번호를 최소 4자 이상으로 작성해 주세요.";
            return false;
        }
        
        if(empty($json -> repasswd)) {
            $this -> __errorCode__ = "/User/joinProcess/7";
			$this -> __errorMsg__ = "비밀번호 확인을 입력해 주십시오.";
            return false;
        }
        
        if($json -> passwd != $json -> repasswd) {
            $this -> __errorCode__ = "/User/joinProcess/8";
			$this -> __errorMsg__ = "비밀번호와 비밀번호 확인이 다릅니다. 다시 확인해 주십시오.";
            return false;
        }

		$json -> name = trim($json -> name);
        
        if(empty($json -> name)) {
            $this -> __errorCode__ = "/User/joinProcess/9";
			$this -> __errorMsg__ = "성명을 입력해 주세요.";
            return false;
        }

		$json -> handphone = trim($json -> handphone);

        if(empty($json -> handphone)) {
            $this -> __errorCode__ = "/User/joinProcess/10";
			$this -> __errorMsg__ = "핸드폰 번호를 입력해 주세요.";
            return false;
        }

		if(!preg_match('/^(010|011|016|017|018|019)-[0-9]{3,4}-[0-9]{4}$/', format_phone($json -> handphone))) {
            $this -> __errorCode__ = "/User/joinProcess/11";
			$this -> __errorMsg__ = "핸드폰 번호를 정확하게 하이픈(-)을 포함하여 입력해 주세요.";
            return false;
		}

		if($this -> getTotal("name='".$json -> name."' AND handphone='".$json -> handphone."'")) {
            $this -> __errorCode__ = "/User/joinProcess/12";
			$this -> __errorMsg__ = "이미 등록된 성명과 핸드폰입니다. 다른 개인정보를 입력해 주세요.";
            return false;
		}

        if(!empty($json -> email) && !check_email($json -> email)) {
            $this -> __errorCode__ = "/User/joinProcess/13";
			$this -> __errorMsg__ = "이메일 형식에 맞게 입력해 주세요.";
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

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/modifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        $this -> getData($_lib['user'] -> __pkValue__);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/modifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
        }

		if(!isset($json -> passwd)) $json -> passwd = "";
        if(!isset($json -> newpasswd)) $json -> newpasswd = "";
        if(!isset($json -> repasswd)) $json -> repasswd = "";
        
        //echo($_lib['user'] -> passwd);
		if($_lib['user'] -> passwd != $json -> passwd) {           
            $this -> __errorCode__ = "/User/modifyProcess/3";
			$this -> __errorMsg__ = "이전 비밀번호가 틀립니다. 이전 비밀번호를 확인해 주십시오.";
            return false;
        }

		if(empty($json -> newpasswd))  {
            $this -> __errorCode__ = "/User/modifyProcess/4";
			$this -> __errorMsg__ = "새 비밀번호를 입력해 주십시오.";
            return false;
        }

		if(empty($json -> repasswd))  {
            $this -> __errorCode__ = "/User/modifyProcess/5";
			$this -> __errorMsg__ = "새 비밀번호 확인을 입력해 주십시오.";
            return false;
        }

		if($json -> newpasswd != $json -> repasswd) {
            $this -> __errorCode__ = "/User/modifyProcess/6";
			$this -> __errorMsg__ = "새 비밀번호와 새 비밀번호 확인이 일치하지 않습니다.";
            return false;
		}
		
		$json -> passwd = $json -> newpasswd;
        
        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }
      
    function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $_lib['user'] -> __pkValue__ != $this -> __pkValue__) {
			$this -> __errorCode__ = "/User/masterModifyProcess/3";
			$this -> __errorMsg__ = "본인만 조회할 수 있습니다.";
			return false;
		}

        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
    }

	function login($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if(empty($json -> id)) {
            $this -> __errorCode__ = "/User/login/1";
			$this -> __errorMsg__ = "아이디를 입력해 주세요.";
            return false;
		}

		$this -> getDataByCondition("id='".trim($json -> id)."'");

		if($this -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/User/login/2";
			$this -> __errorMsg__ = "등록되지 않은 아이디입니다.";
            return false;
		}

		if(empty($json -> passwd)) {
            $this -> __errorCode__ = "/User/login/3";
			$this -> __errorMsg__ = "비밀번호를 입력해 주세요.";
            return false;
		}

		if(trim($this -> passwd) != trim($json -> passwd)) {
            $this -> __errorCode__ = "/User/login/4";
			$this -> __errorMsg__ = "비밀번호를 다시 입력해 주세요.";
            return false;
		}

		$payload = array(
			"user_id" => $this -> __pkValue__,
			"exp" => time() + (60 * 60 * 5)
		);

		//토큰발행
		$jwt = JWT::encode($payload, $_lib['website'] -> name, 'HS512');
		
		$result = new stdClass();
		$result -> token = $jwt;

		return $result;
	}

	function deleteProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($this -> __pkValue__ <= 0 && $json -> user_id <= 0) $this -> getData($json -> user_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $_lib['user'] -> __pkValue__ != $this -> __pkValue__) {
			$this -> __errorCode__ = "/User/deleteProcess/3";
			$this -> __errorMsg__ = "접근 권한이 없습니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/User/deleteProcess/4";
			$this -> __errorMsg__ = "회원 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}

	function nickname($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/nickname/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        $this -> getData($_lib['user'] -> __pkValue__);
		
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/nickname/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
		}

		$json -> user_id = $this -> __pkValue__;
		
		if(empty($json -> nickname)) {
			$this -> __errorCode__ = "/User/nickname/3";
			$this -> __errorMsg__ = "닉네임을 입력해 주세요.";
			return false;
		}

		if(!$this -> saveAll($json, $isEcho)) {
			$this -> __errorCode__ = "/User/nickname/4";
			$this -> __errorMsg__ = "닉네임을 저장하는데 실패하였습니다.";
			return false;
		}

		return true;
	}

	function pictureUpload($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/pictureUpload/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}
		
		$this -> getData($_lib['user'] -> __pkValue__);
		
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/pictureUpload/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
        }

		if(!$this -> pictureUploadProcess($json)) return false;

		return true;
	}

	function pictureUploadProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/pictureUploadProcess/1";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
        }

		if(isset($_FILES['file'])) $json -> file = $_FILES['file'];
		
		$path = $_lib['directory']['home']."/image/user/".$this -> __pkValue__;

		//설치 디렉토리 만들기
		if(!makeDirectory($path)) {
            $this -> __errorCode__ = "/User/pictureUploadProcess/2";
			$this -> __errorMsg__ = "업로드할 디렉토리를 만드는데 실패하였습니다.";
            return false;
        }
		
		//삭제여부
		if(!empty($json -> isDel)) $json -> isDel = true;
		elseif(isset($json -> file) && !is_array($json -> file) && preg_match('/base64/i', $json -> file)) $json -> isDel = true;
		elseif(isset($json -> file) && isset($json -> file['size']) && $json -> file['size'] > 0) $json -> isDel = true;
		else $json -> isDel = false;

		//기존 파일 삭제
		if(isset($json -> isDel) && $json -> isDel) {
			$this -> picture = (object) $this -> picture;
			
			@chmod($this -> picture -> path, 0777);

			if(!empty($this -> picture -> path) && file_exists($this -> picture -> path) && is_writable($this -> picture -> path)) {
				if(!rmUtil($this -> picture -> path)) {
					$this -> __errorCode__ = "/User/pictureUploadProcess/3";
					$this -> __errorMsg__ = "프로필 파일 삭제 권한이 없습니다.";
					return false;
				} else $this -> picture = new stdClass();
			} else $this -> picture = new stdClass();
		}
		
		if(isset($json -> file['name'])) {
			$filename = "picture.".getFileType($json -> file['name']);
			
			//신규 파일 등록
			if(!move_uploaded_file($json -> file['tmp_name'], $path."/".$filename)) {
				$this -> __errorCode__ = "/User/pictureUploadProcess/4";
				$this -> __errorMsg__ = "파일을 업로드하는데 실패하였습니다.";
				return false;
			}			
		} elseif(isset($json -> file) && preg_match('/base64/i', $json -> file)) {
			$temp = explode(";base64,", $json -> file);
			$filename = "picture.".str_replace("data:image/", "", $temp[0]);

			$json -> type = str_replace('data:', '', $temp[0]);
			$data = base64_decode(trim($temp[1]));

			if(!file_put_contents($path."/".$filename, $data)) {
				$this -> __errorCode__ = "/User/pictureUploadProcess/5";
				$this -> __errorMsg__ = $path."에 ".$filename."을 복사하는데 실패하였습니다.";
				return false;
			}
		} else return true;
		
		$obj = new stdClass();
		if(file_exists($path."/".$filename)) {
			$obj = new stdClass();
			$obj -> path = $path."/".$filename;
			$obj -> url = str_replace($_lib['directory']['home'], "", $obj -> path);
			$obj -> name = $filename;
			$obj -> size = filesize($obj -> path);
			@chmod($path."/".$filename, 0755);
		}

		$this -> picture = $obj;

		if(!$this -> save()) {
			$this -> __errorCode__ = "/User/pictureUploadProcess/7";
			$this -> __errorMsg__ = "프로필 사진을 업로드하는데 실패하였습니다.";
			return false;
		}

		return true;
	}

	function faceUpload($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/faceUpload/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        $this -> getData($_lib['user'] -> __pkValue__);
		
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/faceUpload/2";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
        }

		if(!$this -> faceUploadProcess($json)) return false;

		return true;
	}

	function faceUploadProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/User/faceUploadProcess/1";
			$this -> __errorMsg__ = "존재하지 않는 회원 정보입니다.";
			return false;
        }

		if(empty($json -> idx)) {
            $this -> __errorCode__ = "/User/faceUploadProcess/2";
			$this -> __errorMsg__ = "순서 정보가 없습니다.";
            return false;
        }

		if($json -> idx > 20) {
            $this -> __errorCode__ = "/User/faceUploadProcess/3";
			$this -> __errorMsg__ = "순서는 1~20 범위로 지정해 주세요.";
            return false;
		}

		if($json -> idx < 1) {
            $this -> __errorCode__ = "/User/faceUploadProcess/4";
			$this -> __errorMsg__ = "순서는 1~20 범위로 지정해 주세요.";
            return false;
		}

		if(isset($_FILES['file'])) $json -> file = $_FILES['file'];
		
		$idx = (int)$json -> idx;
		$path = $_lib['directory']['home']."/image/user/".$this -> __pkValue__."/face";
		
		if(!isset($this -> faces)) $this -> faces = [];
		if(!is_array($this -> faces)) $this -> faces = (array) $this -> faces;

		//설치 디렉토리 만들기
		if(!makeDirectory($path)) {
            $this -> __errorCode__ = "/User/faceUploadProcess/5";
			$this -> __errorMsg__ = "업로드할 디렉토리를 만드는데 실패하였습니다.";
            return false;
        }

		@chmod($path, 0777);
		
		$faces = [];
		foreach($this -> faces as $key => $face) {
			//echo $face -> path."<br>";
			if(file_exists($face -> path)) $faces[$key] = $face;
		}
		$this -> faces = $faces;
		//print_r($this -> faces);

		//삭제여부
		if(!empty($json -> isDel)) $json -> isDel = true;
		elseif(isset($json -> file) && !is_array($json -> file) && preg_match('/base64/i', $json -> file)) $json -> isDel = true;
		elseif(isset($json -> file) && isset($json -> file['size']) && $json -> file['size'] > 0) $json -> isDel = true;
		else $json -> isDel = false;
		
		//기존 파일 삭제
		if($json -> isDel && isset($this -> faces[$idx])) {
			$face = $this -> faces[$idx];
			
			if(is_writable($face -> path)) {
				@chmod($face -> path, 0777);

				if(!rmUtil($face -> path)) {
					$this -> __errorCode__ = "/User/faceUploadProcess/6";
					$this -> __errorMsg__ = $face -> path."파일을 삭제하는데 실패하였습니다.";
					return false;
				}
			} else {
				$this -> __errorCode__ = "/User/faceUploadProcess/7";
				$this -> __errorMsg__ = $face -> path."파일 삭제 권한이 없습니다.";
				return false;
			}
		}

		if(isset($json -> file['name'])) {
			$filename = $idx.".".getFileType($json -> file['name']);

			//신규 파일 등록
			if(!move_uploaded_file($json -> file['tmp_name'], $path."/".$filename)) {
				$this -> __errorCode__ = "/User/faceUploadProcess/8";
				$this -> __errorMsg__ = "파일을 업로드하는데 실패하였습니다.";
				return false;
			}			
		} elseif(isset($json -> file) && preg_match('/base64/i', $json -> file)) {
			$temp = explode(";base64,", $json -> file);
			$filename = $idx.".".str_replace("data:image/", "", $temp[0]);

			$json -> type = str_replace('data:', '', $temp[0]);
			$data = base64_decode(trim($temp[1]));

			if(!file_put_contents($path."/".$filename, $data)) {
				$this -> __errorCode__ = "/User/faceUploadProcess/9";
				$this -> __errorMsg__ = $path."에 ".$filename."을 복사하는데 실패하였습니다.";
				return false;
			}
		}

		if(!empty($filename) && file_exists($path."/".$filename)) {
			$obj = new stdClass();
			$obj -> path = $path."/".$filename;
			$obj -> url = str_replace($_lib['directory']['home'], "", $obj -> path);
			$obj -> name = $filename;
			$obj -> size = filesize($obj -> path);
			@chmod($path."/".$filename, 0755);
			$this -> faces[$idx] = $obj;
		} else unset($this -> faces[$idx]);
		
		if(!$this -> save()) {
			$this -> __errorCode__ = "/User/faceUploadProcess/10";
			$this -> __errorMsg__ = "얼굴 사진을 업로드하는데 실패하였습니다.";
			return false;
		}

		//print_r($this -> faces);

		return true;
	}
        
    function findId($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
       
        if(empty($json -> name)) {
            $this -> __errorCode__ = "/User/findId/1";
			$this -> __errorMsg__ = "성명을 입력해 주십시오.";
            return false;
        }
        
        if(empty($json -> type) ) {
            $this -> __errorCode__ = "/User/findId/2";
			$this -> __errorMsg__ = "발송 방법을 선택해 주세요.";
            return false;
        }

		if(!isset($json -> handphone)) $json -> handphone = '';
		elseif(is_array($json -> handphone)) $json -> handphone = implode("-", $json -> handphone);

		if($json -> handphone == '--') $json -> handphone = '';

		if($json -> type == 1 && empty($json -> handphone)) {
            $this -> __errorCode__ = "/User/findId/3";
			$this -> __errorMsg__ = "휴대전화번호를 입력해 주십시오.";
            return false;
        }
        
		if($json -> type == 2 && empty($json -> email)) {
            $this -> __errorCode__ = "/User/findId/4";
			$this -> __errorMsg__ = "이메일을 입력해 주십시오.";
            return false;
        }
        
        $listObj = new Components();
        $listObj -> setJoin("User", "a", "a.name='".$json -> name."'");
        $listObj -> setSort("a_id");
        
		if($json -> type == 1) $listObj -> setAndCondition("a.handphone='".displayPhoneNumber($json -> handphone)."'");
		if($json -> type == 2) $listObj -> setAndCondition("a.email='".$json -> email."'");

        $listObj -> rowsPerPage = 0;
        $results = $listObj -> getResults();
        
        if($listObj -> total <= 0) {
            $this -> __errorCode__ = "/User/findId/5";
			$this -> __errorMsg__ = "등록된 회원 정보가 없습니다.";
            return false;
        }
                
        if($listObj -> total > 1) {
            $this -> __errorCode__ = "/User/findId/6";
			$this -> __errorMsg__ = "입력하신 정보와 일치하는 회원이 한명 이상 존재합니다. 관리자에게 문의바랍니다.";
            return false;
        }
        
        $data = $results -> fetch_array();

        $userObj = new User();
        $userObj -> setData($data, 'a');

		$msg = $_lib['website'] -> domain." 사이트에 등록하신 아이디는 <b>".$userObj -> id."</b> 입니다.";

		if($json -> type == 1) {
			//문자 발송 데이터 설정
			$data = new stdClass();
			$data -> authcode = base64_encode($_lib['website'] -> name);
			$data -> to_name = $userObj -> name;
			$data -> to_handphone = str_replace("-", "", $userObj -> handphone);
			$data -> callback = $_lib['website'] -> callback;
			$data -> subject = "[".$_lib['website'] -> nickname."] 아이디 찾기";
			$data -> msg = $msg;

			$umsObj = new Ums();
			if(!$umsObj -> joinProcess($data)) {
				$this -> __errorCode__ = "/User/findId/7";
				$this -> __errorMsg__ = $umsObj -> __errorMsg__;
				return false;
			}
		} elseif($json -> type == 2) {
			$d = new stdClass();
			$d -> from_name = $_lib['website'] -> nickname;
			$d -> from_email = "Webmaster@".$_SERVER['HTTP_HOST'];
			$d -> to_name = $userObj -> name;
			$d -> to_email = $userObj -> email;
			$d -> title = "[".$_lib['website'] -> nickname."] 아이디 찾기";
			$d -> body = $msg;

			$sendmailObj = new Sendmail();
			if(!$sendmailObj -> sendProcess($d)) {
				$this -> __errorCode__ = "/User/findId/8";
				$this -> __errorMsg__ = $sendmailObj -> __errorMsg__;
				return false;
			}
		}
		
        return true;
    }

    function findPasswd($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        $id = getVars('id');
        $email = getVars('email');
        
        if(empty($id)) {
            $this -> __errorCode__ = "/User/findPasswd/1";
			$this -> __errorMsg__ = "아이디를 입력해 주십시오.";
            return false;
        }
         
        if(empty($json -> type) ) {
            $this -> __errorCode__ = "/User/findPasswd/2";
			$this -> __errorMsg__ = "발송 방법을 선택해 주세요.";
            return false;
        }

		if(!isset($json -> handphone)) $json -> handphone = '';
		elseif(is_array($json -> handphone)) $json -> handphone = implode("-", $json -> handphone);

		if($json -> handphone == '--') $json -> handphone = '';

		if($json -> type == 1 && empty($json -> handphone)) {
            $this -> __errorCode__ = "/User/findPasswd/3";
			$this -> __errorMsg__ = "휴대전화번호를 입력해 주십시오.";
            return false;
        }
        
		if($json -> type == 2 && empty($json -> email)) {
            $this -> __errorCode__ = "/User/findPasswd/4";
			$this -> __errorMsg__ = "이메일을 입력해 주십시오.";
            return false;
        }
        
        
        $listObj = new Components();
        $listObj -> setJoin("User", "a", "a.id='".$id."'");
        if($json -> type == 1) $listObj -> setAndCondition("a.handphone='".displayPhoneNumber($json -> handphone)."'");
		elseif($json -> type == 2) $listObj -> setAndCondition("a.email='".$json -> email."'");
        $listObj -> setSort("a_id");
        $listObj -> rowsPerPage = 0;
        $results = $listObj -> getResults();
        
        if($listObj -> total <= 0) {
            $this -> __errorCode__ = "/User/findPasswd/5";
			$this -> __errorMsg__ = "정보가 일치하는 회원이 없습니다.";
            return false;
        }
        
        if($listObj -> total > 1) {
            $this -> __errorCode__ = "/User/findPasswd/6";
			$this -> __errorMsg__ = "입력하신 정보와 일치하는 회원이 한명 이상 존재합니다. 관리자에게 문의바랍니다.";
            return false;
        }
        
        $data = $results -> fetch_array();

        $userObj = new User();
        $userObj -> setData($data, 'a');
        
        if(isset($_lib['cryptedPasswd']) && $_lib['cryptedPasswd']) {
             $passwd = getRandom();
             $userObj -> passwd = $this -> getCryptedPasswd($passwd);
             if(!$userObj -> save()) {
                 $this -> __errorCode__ = "/User/findPasswd/7";
				 $this -> __errorMsg__ = "자동생성된 암호를 저장하는데 실패하였습니다. 관리자에게 문의바랍니다.";
                 return false;
             }
            
             $msg = $_lib['website'] -> domain." 사이트 임시비밀번호는 <b>".$passwd."</b> 입니다. 로그인 후 암호를 변경해 주십시오.";
        } else {
             $passwd = getRandom();
             $userObj -> passwd = $passwd;
             if(!$userObj -> save()) {
                 $this -> __errorCode__ = "/User/findPasswd/8";
				 $this -> __errorMsg__ = "자동생성된 암호를 저장하는데 실패하였습니다. 관리자에게 문의바랍니다.";
                 return false;
             }
            
             $msg = $_lib['website'] -> domain." 사이트 임시비밀번호는 <b>".$passwd."</b> 입니다. 로그인 후 암호를 변경해 주십시오.";
        }      

		if($json -> type == 1) {
			//문자 발송 데이터 설정
			$data = new stdClass();
			$data -> authcode = base64_encode($_lib['website'] -> name);
			$data -> to_name = $userObj -> name;
			$data -> to_handphone = str_replace("-", "", $userObj -> handphone);
			$data -> callback = $_lib['website'] -> callback;
			$data -> subject = "[".$_lib['website'] -> nickname."] 비밀번호 찾기";
			$data -> msg = str_replace("<b>", "", str_replace("</b>", "", $msg));

			$umsObj = new Ums();
			if(!$umsObj -> joinProcess($data)) {
				$this -> __errorCode__ = "/User/findPasswd/9";
				$this -> __errorMsg__ = $umsObj -> __errorMsg__;
				return false;
			}
		} elseif($json -> type == 2) {			
			$d = new stdClass();
			$d -> from_name = $_lib['website'] -> nickname;
			$d -> from_email = "Webmaster@".$_SERVER['HTTP_HOST'];
			$d -> to_name = $userObj -> name;
			$d -> to_email = $userObj -> email;
			$d -> title = "[".$_lib['website'] -> nickname."] 비밀번호 찾기";
			$d -> body = $msg;

			$sendmailObj = new Sendmail();
			if(!$sendmailObj -> sendProcess($d)) {
				$this -> __errorCode__ = "/User/findPasswd/10";
				$this -> __errorMsg__ = $sendmailObj -> __errorMsg__;
				return false;
			}
		}

		return true;
    }
}
?>