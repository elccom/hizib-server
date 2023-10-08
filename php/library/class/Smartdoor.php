<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Smartdoor extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor';
        $this -> __pkName__ = 'smartdoor_id';

		$this -> smartdoorGroupObj = new SmartdoorGroup();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorGroupObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorGroupObj -> __pkName__.'","refrence":"'.$this -> smartdoorGroupObj -> __tableName__.'('.$this -> smartdoorGroupObj -> __pkName__.') on delete cascade"},
			{"field":"code","type":"varchar","size":255,"key":"code","default":""},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"dong","type":"varchar","size":255,"key":"dong","default":""},
			{"field":"ho","type":"varchar","size":255,"key":"ho","default":""},
			{"field":"ble","type":"varchar","size":255,"key":"ble","default":""},
			{"field":"isDoorOpen","type":"int","size":1,"key":"isDoorOpen","default":0},
			{"field":"updateDate","type":"timestamp","key":"updateDate","default":"CURRENT_TIMESTAMP"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
			{"field":"status","type":"int","size":1,"key":"status","default":1}
        ]');
        
        parent::__construct();
		$this -> install();
        //$this -> tableUpdate();
    }
    
    function tableUpdate() {
        if(!$this -> isExistField('isDoorOpen')) {
            if(!$this -> addTableField('isDoorOpen', 'int(1)', 0, 'ble', '', true, true)) return false;
            if(!$this -> addTableIndex('isDoorOpen', 'isDoorOpen')) return false;
        }
    }

	function getDoorTopic() {
		if($this -> __pkValue__ <= 0) return '';

		return 'hizib01/'.$this -> code.'/door';
	}

	function getOwnerTopic() {
		if($this -> __pkValue__ <= 0) return '';

		$obj = new SmartdoorUser();
		$obj -> getDataByCondition("smartdoor_id='".$this -> __pkValue__."' AND isOwner=1 ORDER BY smartdoor_user_id desc LIMIT 1");

		if($obj -> __pkValue__ <= 0) return '';

		return 'hizib01/'.$this -> code.'/'.$obj -> user_id;
	}

	function getUserTopic($user_id) {
		if($this -> __pkValue__ <= 0) return '';

		return 'hizib01/'.$this -> code.'/'.$user_id;
	}

	function search($pkValue) {
		global $_lib;

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['user'] -> __pkValue__) {
			$obj = new SmartdoorUser();
			$obj -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."' AND smartdoor_id='".$pkValue."' AND isOwner=1");

			if($obj -> __pkValue__ <= 0) {
				$this -> __errorCode__ = "/Smartdoor/search/2";
				$this -> __errorMsg__ = "존재하지 않는 스마트도어 오너 정보입니다.";
				return false;
			}
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/search/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		return true;
	}

	function findByCode($json='') {
		global $_lib;
		
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if(empty($json -> code)) {
            $this -> __errorCode__ = "/Smartdoor/findByCode/1";
			$this -> __errorMsg__ = "조회할 제품 시리얼코드를 입력해 주세요.";
            return false;
		}

		$listObj = new Components();
		$listObj -> setJoin("Smartdoor", "a", "a.code='".trim($json -> code)."'");
		$results = $listObj -> getOnlyResults();
		if($results) {
			$data = $results -> fetch_array();
			$this -> setData($data, 'a');
		}

		if($results -> num_rows <= 0) {
			$this -> __errorCode__ = "/Smartdoor/findByCode/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		return $this;
	}

	function me($json='') {
		global $_lib;
		
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/me/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$obj = new SmartdoorUser();
		$obj -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."' ORDER BY smartdoor_id desc LIMIT 1");

		if($obj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/me/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 오너 정보입니다.";
			return false;
		}

		$obj -> smartdoorObj -> getData($obj -> smartdoor_id);

		if($obj -> smartdoorObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/me/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		return $obj -> smartdoorObj;
	}

	function owner($json='') {
		global $_lib;
		
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/owner/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> smartdoor_id) && $_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/owner/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		$obj = new SmartdoorUser();
		$obj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND isOwner=1");

		if($obj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/owner/3";
			$this -> __errorMsg__ = "등록된 오너 정보가 없습니다.";
			return false;
		}
		
		$obj -> smartdoorObj -> getData($obj -> smartdoor_id);
		$obj -> userObj -> getData($obj -> user_id);

		return $obj;
	}

	function token($json='') {
		global $_lib;
		
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$obj = new SmartdoorUser();

		if(empty($json -> smartdoor_id) && $_lib['user'] -> __pkValue__) {
			$obj -> getDataByCondition("user_id='".$_lib['user'] -> __pkValue__."' ORDER BY smartdoor_id desc LIMIT 1");
			$json -> smartdoor_id = $obj -> smartdoor_id;
		}

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/token/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
			return false;
		}
		
		$this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/token/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		$payload = array(
			"smartdoor_id" => (int)$this -> __pkValue__,
			"exp" => time() + (60 * 60 * 5)
		);

		//토큰발행
		$jwt = JWT::encode($payload, $_lib['website'] -> name, 'HS512');
		
		$result = new stdClass();
		$result -> token = $jwt;

		return $result;
	}

	function lists($json='') {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> page)) $json -> page = 1;
		if(empty($json -> rowsPerPage)) $json -> rowsPerPage = 10;
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("Smartdoor", "a");
		$listObj -> setLeftOuterJoin("SmartdoorUser", "b", "b.smartdoor_id=a.smartdoor_id");
		$listObj -> setAndCondition("b.smartdoor_user_id is null");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$json -> keyword."%' OR a.name like '%".$json -> keyword."%' OR a.dong like '%".$json -> keyword."%' OR a.ho like '%".$json -> keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Smartdoor/lists/2";
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
				$obj = new Smartdoor();
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
        if($obj -> smartdoor_group_id <= 0) {
            $this -> __errorCode__ = "/Smartdoor/isValidate/1";
			$this -> __errorMsg__ = "단지 정보가 없습니다.";
            return false;
        }
        
        if(check_blank($obj -> code)) {
            $this -> __errorCode__ = "/Smartdoor/isValidate/2";
			$this -> __errorMsg__ = "제품기기번호 입력해 주세요.";
            return false;
        }
        
        if(strlen($obj -> code) != 8) {
            $this -> __errorCode__ = "/Smartdoor/isValidate/3";
			$this -> __errorMsg__ = "제품기기번호 입력해 8자리 숫자로 입력해 주세요.";
            return false;
        }
        
		if($this -> getTotal("smartdoor_id != '".$obj -> __pkValue__."' AND code='".trim($obj -> code)."'") > 0) {
            $this -> __errorCode__ = "/Smartdoor/isValidate/4";
			$this -> __errorMsg__ = "이미 등록된 스마트도어 제품시리얼 정보입니다.";
            return false;
		}

        if(!empty($obj -> ho) && $this -> getTotal("smartdoor_id!='".$obj -> __pkValue__."' AND smartdoor_group_id='".$obj -> smartdoor_group_id."' AND dong='".$obj -> dong."' AND ho='".$obj -> ho."'")) {
			$this -> __errorCode__ = "/Smartdoor/isValidate/5";
			$this -> __errorMsg__ = "이미 등록된 동/호수입니다.";
			return false;
		}
        
        return true;
    }
    
	function saveAll($json='', $isEcho=false) {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_group_id) && !empty($json -> code)) $this -> getDataByCondition("smartdoor_group_id='".$json -> smartdoor_group_id."' AND code='".$json -> code."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();

        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Smartdoor/saveAll/1";
			$this -> __errorMsg__ = "스마트도어 정보를 저장하는데 실패하였습니다.";
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

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($json -> smartdoor_group_id <= 0) {
            $this -> __errorCode__ = "/Smartdoor/joinProcess/2";
			$this -> __errorMsg__ = "단지 정보가 없습니다.";
            return false;
        }

        if($this -> smartdoorGroupObj -> __pkValue__ <= 0) $this -> smartdoorGroupObj -> getData($json -> smartdoor_group_id);

		if($this -> smartdoorGroupObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/Smartdoor/joinProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 단지 정보입니다.";
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

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__) {
			$this -> __errorCode__ = "/Smartdoor/modifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		if(empty($json -> user_id) && $_lib['user'] -> __pkValue__) $json -> user_id = $_lib['user'] -> __pkValue__;
		if(empty($json -> smartdoor_id) && !empty($json -> user_id)) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' ORDER BY isOwner,smartdoor_user_id desc LIMIT 1");
			if($smartdoorUserObj -> __pkValue__) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
			else {
				$this -> __errorCode__ = "/Smartdoor/modifyProcess/2";
				$this -> __errorMsg__ = "스마트도어 사용자 본인만 수정할 수 있습니다.";
				return false;
			}
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/modifyProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_group_id) && !empty($json -> code)) $this -> getDataByCondition("smartdoor_group_id='".$json -> smartdoor_group_id."' AND code='".$json -> code."'");

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$this -> __pkValue__."' AND user_id='".$_lib['user'] -> __pkValue__."'");

			if($smartdoorUserObj -> __pkValue__ <= 0) {
				$this -> __errorCode__ = "/Smartdoor/masterModifyProcess/2";
				$this -> __errorMsg__ = "수정 권한이 없습니다.";
				return false;
			}
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
			$this -> __errorCode__ = "/Smartdoor/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$this -> __pkValue__."' AND user_id='".$_lib['user'] -> __pkValue__."'");

			if($smartdoorUserObj -> __pkValue__ <= 0) {
				$this -> __errorCode__ = "/Smartdoor/deleteProcess/3";
				$this -> __errorMsg__ = "삭제 권한이 없습니다.";
				return false;
			}
		}

        if(!parent::delete($isEcho)) {
 			$this -> __errorCode__ = "/Smartdoor/deleteProcess/4";
			$this -> __errorMsg__ = "스마트도어 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
	
	//APP에서 문열기
	function doorOpenProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorOpenProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['smartdoor'] -> __pkValue__ && empty($json -> smartdoor_id)) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;
		
		$smartdoorUserObj = new SmartdoorUser();
		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");		
		elseif(!empty($json -> smartdoor_id) && empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND isOwner=1");		
		elseif(empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' AND isOwner=1");		
			
		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorOpenProcess/2";
			$this -> __errorMsg__ = "문열기 권한이 없습니다.";
			return false;
		} elseif(empty($json -> smartdoor_id)) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/doorOpenProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorOpenProcess/4";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        //mqtt로 문열기 실행
		mqtt_publish($_lib['mqtt']['host'], $this -> getDoorTopic(), '{"request":"/Smartdoor/doorOpenByAppProcess","sender":"'.$this -> getOwnerTopic().'","receiver":"'.$this -> getDoorTopic().'","data":"'.now().'"}');
		
		return true;
	}
	
	//APP에서 문닫기
	function doorCloseProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorCloseProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['smartdoor'] -> __pkValue__ && empty($json -> smartdoor_id)) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;
		
		$smartdoorUserObj = new SmartdoorUser();
		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");		
		elseif(!empty($json -> smartdoor_id) && empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND isOwner=1");		
		elseif(empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' AND isOwner=1");		
			
		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorCloseProcess/2";
			$this -> __errorMsg__ = "문열기 권한이 없습니다.";
			return false;
		} elseif(empty($json -> smartdoor_id)) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/doorCloseProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorCloseProcess/4";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        //mqtt로 문열기 실행
		mqtt_publish($_lib['mqtt']['host'], $this -> getDoorTopic(), '{"request":"/Smartdoor/doorCloseByAppProcess","sender":"'.$this -> getOwnerTopic().'","receiver":"'.$this -> getDoorTopic().'"}');
		
		return true;
	}
	
	//APP에서 영상통화 요청
	function channelJoinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/channelJoinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> channelName) || !preg_match('/^[0-9]{6}$/', $json -> channelName)) {
			$this -> __errorCode__ = "/Smartdoor/channelJoinProcess/2";
			$this -> __errorMsg__ = "채널이름을 6자리 숫자 형식으로 지정해 주세요.";
			return false;
		}

		$json -> user_id = $_lib['user'] -> __pkValue__;

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' ORDER BY isOwner,smartdoor_user_id desc LIMIT 1");		

		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/channelJoinProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자입니다. 사용자 등록 후 이용해 주세요.";
			return false;
		} elseif(empty($json -> smartdoor_id)) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;
			
		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/channelJoinProcess/4";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/channelJoinProcess/5";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		$role = RtcTokenBuilder::RoleAttendee;
		$expireTimeInSeconds = 3600;
		$currentTimestamp = (new DateTime("now", new DateTimeZone('UTC'))) -> getTimestamp();
		$privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

		$token = RtcTokenBuilder::buildTokenWithUid($_lib['agora']['appID'], $_lib['agora']['appCertificate'], $json -> channelName, 0, $role, $privilegeExpiredTs);
		$startDate = date("Y-m-d H:i:s", $currentTimestamp);
		$stopDate = date("Y-m-d H:i:s", $privilegeExpiredTs);

		$results = new stdClass();
		$results -> appID = $_lib['agora']['appID'];
		$results -> appCertificate = $_lib['agora']['appCertificate'];
		$results -> channelName = $json -> channelName;
		$results -> token = $token;
		$results -> uid = 0;
		$results -> startDate = $startDate;
		$results -> stopDate = $stopDate;

        //mqtt로 문열기 실행
		mqtt_publish($_lib['mqtt']['host'], $this -> getDoorTopic(), '{"request":"/Smartdoor/channelJoinProcess","sender":"'.$this -> getOwnerTopic().'","receiver":"'.$this -> getDoorTopic().'","data":'.jsonencode($results).'}');

		return $results;
	}
	
	//도어벨 눌렸을때 액션
	function doorbellPushProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorbellPushProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' ORDER BY isOwner,smartdoor_user_id desc LIMIT 1");		

		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorbellPushProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자입니다. 사용자 등록 후 이용해 주세요.";
			return false;
		}

		$this -> getData($smartdoorUserObj -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/doorbellPushProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		//push 메세지 발송
		$obj = new Fcm();
		
		$data = new stdClass();
		$data -> user_id = $smartdoorUserObj -> user_id;
		$data -> title = 'Hi-Zib 도어벨 PUSH';
		$data -> body = '도어벨이 울렸습니다. 지금 확인하시겠습니까?';
		$data -> data = new stdClass();
		$data -> data -> title = 'Hi-Zib 도어벨 PUSH';
		$data -> data -> body = '도어벨이 울렸습니다. 지금 확인하시겠습니까?';
		$data -> data -> click_action = 'doorbellPush';
		
		if(!$obj -> joinProcess($data)) {
            $this -> __errorCode__ = "/Smartdoor/doorbellPushProcess/4";
			$this -> __errorMsg__ = "도어벨 알림 푸시 메세지를 발송하는데 실패하였습니다.";
            return false;
		}
		
		return true;
	}
	
	//키오스크에서 모션이 감지됐을때 액션
	function motionDetectProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/motionDetectProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' ORDER BY isOwner,smartdoor_user_id desc LIMIT 1");		

		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/motionDetectProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자입니다. 사용자 등록 후 이용해 주세요.";
			return false;
		}

		$this -> getData($smartdoorUserObj -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/motionDetectProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		//push 메세지 발송
		$obj = new Fcm();
		
		$data = new stdClass();
		$data -> user_id = $smartdoorUserObj -> user_id;
		$data -> title = 'Hi-Zib 외부카메라 감지';
		$data -> body = '외부인이 감지되었습니다. 지금 확인하시겠습니까?';
		$data -> data = new stdClass();
		$data -> data -> title = 'Hi-Zib 외부카메라 감지';
		$data -> data -> body = '외부인이 감지되었습니다. 지금 확인하시겠습니까?';
		$data -> data -> click_action = 'motionDetect';
		
		if(!$obj -> joinProcess($data)) {
            $this -> __errorCode__ = "/Smartdoor/motionDetectProcess/4";
			$this -> __errorMsg__ = $obj -> __errorMsg__;
            return false;
		}
		
		return true;
	}
	
	//게스트키 발행 요청
	function guestkeyJoinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 

		$json -> user_id = $_lib['user'] -> __pkValue__;

		$smartdoorUserObj = new SmartdoorUser();
		$smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."' ORDER BY isOwner,smartdoor_user_id desc LIMIT 1");		
		
		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 사용자입니다. 사용자 등록 후 이용해 주세요.";
			return false;
		} else $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/3";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
			return false;
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/4";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		$json -> handphone = trim($json -> handphone);

		if(empty($json -> handphone)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/5";
			$this -> __errorMsg__ = "수신받을 핸드폰 번호를 입력해 주세요.";
            return false;
		}

		if(!check_handphone($json -> handphone)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/6";
			$this -> __errorMsg__ = "핸드폰 번호를 하이픈(-)을 포함하여 형식에 맞게 입력해 주세요.";
            return false;
		}

		$json -> passwd = trim($json -> passwd);

		if(empty($json -> passwd)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/7";
			$this -> __errorMsg__ = "비밀번호를 입력해 주세요.";
            return false;
		}

		if(!preg_match('/^[0-9]{1,12}$/', $json -> passwd)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/8";
			$this -> __errorMsg__ = "비밀번호를 12자리 이내로 입력해 주세요.";
            return false;
		}

		if(empty($json -> startDate)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/9";
			$this -> __errorMsg__ = "시작일자를 입력해 주세요.";
            return false;
		}

		if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/", $json -> startDate)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/10";
			$this -> __errorMsg__ = "시작일자를 날짜 형식에 맞게 입력해 주세요.";
            return false;
		}

		if(empty($json -> stopDate)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/11";
			$this -> __errorMsg__ = "종료일자를 입력해 주세요.";
            return false;
		}

		if(!preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})$/", $json -> stopDate)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/12";
			$this -> __errorMsg__ = "종료일자를 날짜 형식에 맞게 입력해 주세요.";
            return false;
		}

		$smartdoorGuestkeyObj = new SmartdoorGuestkey();
		if(!$smartdoorGuestkeyObj -> joinProcess($json)) {
            $this -> __errorCode__ = "/Smartdoor/guestkeyJoinProcess/13";
			$this -> __errorMsg__ = $smartdoorGuestkeyObj -> __errorMsg__;
            return false;
		}

		return true;
	}

	function initProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		//로그인여부
		if($_lib['admin'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/initProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		} 
		
		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;

		if(empty($json -> smartdoor_id)) {
			$this -> __errorCode__ = "/Smartdoor/initProcess/2";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
			return false;
		}

        if(isset($json -> smartdoor_id)) $this -> getData($json -> smartdoor_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Smartdoor/initProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		$json -> ble = '';

		if(!$this -> saveAll($json)) {
			$this -> __errorCode__ = "/Smartdoor/initProcess/4";
			$this -> __errorMsg__ = "BLE 정보 초기화 실패하였습니다.";
			return false;
		}

		$obj = new SmartdoorUser();
		if(!$obj -> deleteByCondition("smartdoor_id='".$this -> __pkValue__."'")) {
			$this -> __errorCode__ = "/Smartdoor/initProcess/5";
			$this -> __errorMsg__ = "스마트도어 사용자 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

        //mqtt로 문열기 실행
		mqtt_publish($_lib['mqtt']['host'], $this -> getDoorTopic(), '{"request":"/Smartdoor/initProcess","sender":"'.$this -> getDoorTopic().'","receiver":"'.$this -> getDoorTopic().'","data":"'.now().'"}');

		return true;
	}
}
?>