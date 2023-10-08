<?php
class SmartdoorSchedule extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_schedule';
        $this -> __pkName__ = 'smartdoor_schedule_id';

		$this -> smartdoorObj = new Smartdoor();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"name","type":"varchar","size":255,"key":"name","default":""},
			{"field":"dday","type":"timestamp","key":"dday","default":"CURRENT_TIMESTAMP"},
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
			$this -> __errorCode__ = "/SmartdoorSchedule/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorSchedule/search/2";
			$this -> __errorMsg__ = "존재하지 않는 일정 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_schedule_id";
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
		$listObj -> setJoin("SmartdoorSchedule", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.name like '%".$keyword."%' OR a.dday like '%".$keyword."%')");
		if(!empty($json -> startDate)) $listObj -> setAndCondition("a.dday>='".$json -> startDate." 00:00:00'");
		if(!empty($json -> stopDate)) $listObj -> setAndCondition("a.dday<='".$json -> stopDate." 23:59:59'");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorSchedule/lists/1";
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
				$obj = new SmartdoorSchedule();
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
            $this -> __errorCode__ = "/SmartdoorSchedule/isValidate/1";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
            return false;
        }
        
        if(check_blank($obj -> name)) {
            $this -> __errorCode__ = "/SmartdoorSchedule/isValidate/2";
			$this -> __errorMsg__ = "일정명을 입력해 주세요.";
            return false;
        }
        
        if(check_blank($obj -> dday)) {
            $this -> __errorCode__ = "/SmartdoorSchedule/isValidate/3";
			$this -> __errorMsg__ = "일자를 입력해 주세요.";
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
        
        if(isset($json -> smartdoor_schedule_id)) $this -> getData($json -> smartdoor_schedule_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && !empty($json -> name) && !check_blank($json -> dday)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND name='".$json -> name."' AND dday='".$json -> dday."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;
        
		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorSchedule/saveAll/1";
			$this -> __errorMsg__ = "일정을 저장하는데 실패하였습니다.";
            return false;
        }
        
        if($this -> smartdoorObj -> __pkValue__ <= 0 && $this -> smartdoor_id) $this -> smartdoorObj -> getData($this -> smartdoor_id);

		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson('', ['smartdoorObj']).'}');


		return true;
    }

	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['user'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorSchedule/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['user'] -> __pkValue__ && empty($json -> user_id)) $json -> user_id = $_lib['user'] -> __pkValue__;

		$smartdoorUserObj = new SmartdoorUser();		
		
		//로그인여부
		if(!empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND user_id='".$json -> user_id."'");
		elseif(empty($json -> smartdoor_id) && !empty($json -> user_id)) $smartdoorUserObj -> getDataByCondition("user_id='".$json -> user_id."'");

		if($smartdoorUserObj -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorSchedule/joinProcess/2";
			$this -> __errorMsg__ = "등록되지 않은 스마트도어 사용자입니다.";
			return false;
		} elseif(empty($json -> smartdoor_id)) $json -> smartdoor_id = $smartdoorUserObj -> smartdoor_id;

        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
		
		$this -> __errorCode__ = "/SmartdoorSchedule/modifyProcess/1";
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
			$this -> __errorCode__ = "/SmartdoorSchedule/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(isset($json -> smartdoor_schedule_id)) $this -> getData($json -> smartdoor_schedule_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorSchedule/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 일정 정보입니다.";
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
			$this -> __errorCode__ = "/SmartdoorSchedule/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_schedule_id)) $this -> getData($json -> smartdoor_schedule_id);
        
		$pkValue = $this -> __pkValue__;
        
		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/deleteProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":{"'.$this -> __pkName__.'":"'.$pkValue.'"}}');

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorSchedule/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 일정 정보입니다.";
			return false;
		}

		if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorSchedule/deleteProcess/3";
			$this -> __errorMsg__ = "일정 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>