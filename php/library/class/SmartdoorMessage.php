<?php
class SmartdoorMessage extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_message';
        $this -> __pkName__ = 'smartdoor_message_id';

		$this -> toUserObj = new User();
		$this -> fromUserObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"to_user_id","type":"bigint","option":"unsigned","keytype":"foreign","key":"to_user_id","refrence":"'.$this -> toUserObj -> __tableName__.'('.$this -> toUserObj -> __pkName__.') on delete cascade"},
			{"field":"from_user_id","type":"bigint","option":"unsigned","keytype":"foreign","key":"from_user_id","refrence":"'.$this -> fromUserObj -> __tableName__.'('.$this -> fromUserObj -> __pkName__.') on delete cascade"},
			{"field":"msg","type":"varchar","size":255,"key":"msg","default":""},
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
			$this -> __errorCode__ = "/SmartdoorMessage/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorMessage/search/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_message_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> startDate)) $json -> startDate = "";
		if(empty($json -> stopDate)) $json -> stopDate = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['smartdoor'] -> __pkValue__ <= 0  && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorMessage/lists/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		if($_lib['user'] -> __pkValue__ && empty($json -> from_user_id)) $json -> from_user_id = $_lib['user'] -> __pkValue__;
		if($_lib['user'] -> __pkValue__ && empty($json -> to_user_id)) $json -> to_user_id = $_lib['user'] -> __pkValue__;
		
		if(!empty($json -> to_user_id) && !empty($json -> from_user_id)) $condition = "(a.from_user_id='".$json -> from_user_id."' OR a.to_user_id='".$json -> to_user_id."')";
		else if(!empty($json -> from_user_id) && empty($json -> to_user_id)) $condition = "a.from_user_id='".$json -> from_user_id."')";
		else if(empty($json -> from_user_id) && !empty($json -> to_user_id)) $condition = "a.to_user_id='".$json -> to_user_id."')";
		else $condition = "";

		$listObj = new Components();
		$listObj -> setJoin("SmartdoorMessage", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.code like '%".$keyword."%' OR a.name like '%".$keyword."%')");
		if(!empty($json -> startDate)) $listObj -> setAndCondition("a.regDate>='".$json -> startDate."'");
		if(!empty($json -> stopDate)) $listObj -> setAndCondition("a.regDate<='".$json -> stopDate."'");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		$results = $listObj -> getResults();

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorMessage/lists/2";
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
				$obj = new SmartdoorMessage();
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
        if($obj -> to_user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/isValidate/1";
			$this -> __errorMsg__ = "받는 사람 정보가 없습니다.";
            return false;
        }

		$userObj = new User();
		$userObj -> getData($obj -> to_user_id);
        
        if($userObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/isValidate/2";
			$this -> __errorMsg__ = "존재하지 않는 받는 사람 정보입니다.";
            return false;
        }

        if($obj -> from_user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/isValidate/3";
			$this -> __errorMsg__ = "보내는 사람 정보가 없습니다.";
            return false;
        }

		$userObj -> getData($obj -> from_user_id);
        
        if($userObj -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/isValidate/4";
			$this -> __errorMsg__ = "존재하지 않는 보내는 사람 정보입니다.";
            return false;
        }
        
        if(check_blank($obj -> msg)) {
            $this -> __errorCode__ = "/SmartdoorMessage/isValidate/4";
			$this -> __errorMsg__ = "메세지 내용을 입력해 주세요.";
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
        
        if(isset($json -> smartdoor_message_id)) $this -> getData($json -> smartdoor_message_id);
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
		        
		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();

		//$isEcho = true;

        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorMessage/saveAll/1";
            $this -> __errorMsg__ = '메세지를 저장하는데 실패하였습니다.';
            return false;
        }
        
		if($this -> toUserObj -> __pkValue__ <= 0) $this -> toUserObj -> getData($this -> to_user_id);
		if($this -> toUserObj -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$this -> toUserObj -> __pkValue__."'");
			if($smartdoorUserObj -> __pkValue__) {
				if($smartdoorUserObj -> smartdoorObj -> __pkValue__ <= 0) $smartdoorUserObj -> smartdoorObj -> getData($smartdoorUserObj -> smartdoor_id);
				mqtt_publish($_lib['mqtt']['host'], $smartdoorUserObj -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$smartdoorUserObj -> smartdoorObj -> getUserTopic($this -> toUserObj -> __pkValue__).'","receiver":"'.$smartdoorUserObj -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson().'}');
			}
		}

		if($this -> fromUserObj -> __pkValue__ <= 0) $this -> fromUserObj -> getData($this -> from_user_id);
		if($this -> fromUserObj -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$this -> fromUserObj -> __pkValue__."'");
			if($smartdoorUserObj -> __pkValue__) {
				if($smartdoorUserObj -> smartdoorObj -> __pkValue__ <= 0) $smartdoorUserObj -> smartdoorObj -> getData($smartdoorUserObj -> smartdoor_id);
				mqtt_publish($_lib['mqtt']['host'], $smartdoorUserObj -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$smartdoorUserObj -> smartdoorObj -> getUserTopic($this -> toUserObj -> __pkValue__).'","receiver":"'.$smartdoorUserObj -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson().'}');
			}
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
			$this -> __errorCode__ = "/SmartdoorMessage/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> from_user_id)) $json -> from_user_id = $_lib['user'] -> __pkValue__;

        if($json -> to_user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/joinProcess/2";
			$this -> __errorMsg__ = "받는 사람 정보가 없습니다.";
            return false;
        }
        
        if($json -> from_user_id <= 0) {
            $this -> __errorCode__ = "/SmartdoorMessage/joinProcess/3";
			$this -> __errorMsg__ = "보내는 사람 정보가 없습니다.";
            return false;
        }
        
        if($json -> to_user_id == $json -> from_user_id) {
            $this -> __errorCode__ = "/SmartdoorMessage/joinProcess/4";
			$this -> __errorMsg__ = "동일한 사람한테 메세지를 주고 받을 수 없습니다.";
            return false;
        }
        
        if(check_blank($json -> msg)) {
            $this -> __errorCode__ = "/SmartdoorMessage/joinProcess/5";
			$this -> __errorMsg__ = "메세지 내용을 입력해 주세요.";
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
		
		$this -> __errorCode__ = "/SmartdoorMessage/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		
		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorMessage/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(isset($json -> smartdoor_message_id)) $this -> getData($json -> smartdoor_message_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorMessage/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $_lib['user'] -> __pkValue__ != $this -> from_user_id) {
			$this -> __errorCode__ = "/SmartdoorMessage/masterModifyProcess/3";
			$this -> __errorMsg__ = "본인만 메세지를 수정할 수 있습니다.";
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
			$this -> __errorCode__ = "/SmartdoorMessage/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_message_id)) $this -> getData($json -> smartdoor_message_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorMessage/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 정보입니다.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && $_lib['user'] -> __pkValue__ != $this -> from_user_id) {
			$this -> __errorCode__ = "/SmartdoorMessage/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 메세지를 삭제할 수 있습니다.";
			return false;
		}

		$pkValue = $this -> __pkValue__;

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorMessage/deleteProcess/4";
			$this -> __errorMsg__ = "메시지 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		if($this -> toUserObj -> __pkValue__ <= 0) $this -> toUserObj -> getData($this -> to_user_id);
		if($this -> toUserObj -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$this -> toUserObj -> __pkValue__."'");
			if($smartdoorUserObj -> __pkValue__) {
				if($smartdoorUserObj -> smartdoorObj -> __pkValue__ <= 0) $smartdoorUserObj -> smartdoorObj -> getData($smartdoorUserObj -> smartdoor_id);
				mqtt_publish($_lib['mqtt']['host'], $smartdoorUserObj -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/deleteProcess","sender":"'.$smartdoorUserObj -> smartdoorObj -> getUserTopic($this -> toUserObj -> __pkValue__).'","receiver":"'.$smartdoorUserObj -> smartdoorObj -> getDoorTopic().'","data":{"'.$this -> __pkName__.'":"'.$pkValue.'"}}');
			}
		}

		if($this -> fromUserObj -> __pkValue__ <= 0) $this -> fromUserObj -> getData($this -> from_user_id);
		if($this -> fromUserObj -> __pkValue__) {
			$smartdoorUserObj = new SmartdoorUser();
			$smartdoorUserObj -> getDataByCondition("user_id='".$this -> fromUserObj -> __pkValue__."'");
			if($smartdoorUserObj -> __pkValue__) {
				if($smartdoorUserObj -> smartdoorObj -> __pkValue__ <= 0) $smartdoorUserObj -> smartdoorObj -> getData($smartdoorUserObj -> smartdoor_id);
				mqtt_publish($_lib['mqtt']['host'], $smartdoorUserObj -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/deleteProcess","sender":"'.$smartdoorUserObj -> smartdoorObj -> getUserTopic($this -> toUserObj -> __pkValue__).'","receiver":"'.$smartdoorUserObj -> smartdoorObj -> getDoorTopic().'","data":{"'.$this -> __pkName__.'":"'.$pkValue.'"}}');
			}
		}

		return true;
	}
}
?>