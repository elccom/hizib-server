<?php
class Qna extends Component {    
    function __construct(){
        $this -> __tableName__ = 'qna';
        $this -> __pkName__ = 'qna_id';

		$this -> userObj = new User();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> userObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> userObj -> __pkName__.'","refrence":"'.$this -> userObj -> __tableName__.'('.$this -> userObj -> __pkName__.') on delete cascade"},
			{"field":"title","type":"varchar","size":255,"key":"title","default":""},
			{"field":"comment","type":"text","default":""},
			{"field":"reply","type":"text","default":""},
			{"field":"replyDate","type":"timestamp","key":"replyDate","default":"0000-00-00 00:00:00"},
			{"field":"updateDate","type":"timestamp","key":"updateDate","default":"CURRENT_TIMESTAMP"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
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

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/search/1";
			$this -> __errorMsg__ = "존재하지 않는 Q&A 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_qna_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("Qna", "a");
		$listObj -> setJoin("User", "b", "b.user_id=a.user_id");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.title like '%".$keyword."%' AND b.id like '%".$keyword."%' AND b.name like '%".$keyword."%' AND b.nickname like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Qna/lists/1";
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
				$obj = new Qna();
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
			$this -> __errorCode__ = "/Qna/isValidate/1";
			$this -> __errorMsg__ = "작성자 정보가 없습니다.";
			return false;
        }

        if(empty($obj -> title)) {
			$this -> __errorCode__ = "/Qna/isValidate/2";
			$this -> __errorMsg__ = "제목을 입력해 주세요.";
			return false;
        }

        if(empty($obj -> comment)) {
			$this -> __errorCode__ = "/Qna/isValidate/3";
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

		if($_lib['user'] -> __pkValue__) $json -> user_id = $_lib['user'] -> __pkValue__;
		//print_r($json);exit();
        
        if(isset($json -> qna_id)) $this -> getData($json -> qna_id);
        
        $this -> setJson($json);

		$this -> title = trim($this -> title);
		$this -> comment = trim($this -> comment);
		$this -> reply = trim($this -> reply);

        if(!$this -> isValidate($this)) return false;

		$this -> updateDate = now();
		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Qna/saveAll/1";
			$this -> __errorMsg__ = "Q&A 정보를 저장하는데 실패하였습니다.";
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

		if($_lib['user'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
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

		$this -> __errorCode__ = "/Qna/modifyProcess/1";
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
			$this -> __errorCode__ = "/Qna/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(isset($json -> qna_id)) $this -> getData($json -> qna_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 Q&A 정보입니다.";
			return false;
		}

		if($_lib['admin'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ != $this -> user_id) {
			$this -> __errorCode__ = "/Qna/masterModifyProcess/3";
			$this -> __errorMsg__ = "본인만 수정할 수 있습니다.";
			return false;
		}

        if(!$this -> saveAll($json, $isEcho)) return false;
        
        return true;
	}

	function replyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/replyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(empty($json -> reply)) {
			$this -> __errorCode__ = "/Qna/replyProcess/2";
			$this -> __errorMsg__ = "답변을 작성해 주세요.";
			return false;
		}

		if(isset($json -> qna_id)) $this -> getData($json -> qna_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/replyProcess/3";
			$this -> __errorMsg__ = "존재하지 않는 Q&A 정보입니다.";
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
			$this -> __errorCode__ = "/Qna/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> qna_id)) $this -> getData($json -> qna_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Qna/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 Q&A 정보입니다.";
			return false;
		}

		if($_lib['admin'] -> __pkValue__ <= 0 && $_lib['user'] -> __pkValue__ != $this -> user_id) {
			$this -> __errorCode__ = "/Qna/deleteProcess/3";
			$this -> __errorMsg__ = "본인만 삭제할 수 있습니다.";
			return false;
		}
		
		$pkName = $this -> __pkName__;
		$json -> $pkName = $this -> __pkValue__;

        if(!parent::delete($json, $isEcho)) {
			$this -> __errorCode__ = "/Qna/deleteProcess/4";
			$this -> __errorMsg__ = "Q&A 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>