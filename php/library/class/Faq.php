<?php
class Faq extends Component {    
    function __construct(){
        $this -> __tableName__ = 'faq';
        $this -> __pkName__ = 'faq_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"question","type":"varchar","size":255,"key":"question","default":""},
			{"field":"answer","type":"text","default":""},
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

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Faq/search/1";
			$this -> __errorMsg__ = "존재하지 않는 FAQ 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_faq_id";
		if(empty($json -> desc)) $json -> desc = "desc";
		if(empty($json -> keyword)) $json -> keyword = "";
		if(empty($json -> isAll)) $json -> isAll = 0;

		$start = ($json -> page - 1) * $json -> rowsPerPage;

		$listObj = new Components();
		$listObj -> setJoin("Faq", "a");
		if(!empty($json -> keyword)) $listObj -> setAndCondition("a.question like '%".$keyword."%'");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/Faq/lists/1";
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
				$obj = new Faq();
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
        if(check_blank($obj -> question)) {
            $this -> __errorCode__ = "/Faq/isValidate/1";
			$this -> __errorMsg__ = "질문을 입력해 주세요.";
            return false;
        }

        if(check_blank($obj -> answer)) {
            $this -> __errorCode__ = "/Faq/isValidate/2";
			$this -> __errorMsg__ = "답변을 입력해 주세요.";
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
        
        if(isset($json -> faq_id)) $this -> getData($json -> faq_id);
        
        $this -> setJson($json);

		$this -> question = trim($this -> question);
		$this -> answer = trim($this -> answer);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/Faq/saveAll/1";
			$this -> __errorMsg__ = "FAQ 정보를 저장하는데 실패하였습니다.";
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

		if($_lib['admin'] -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/Faq/joinProcess/1";
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

		$this -> __errorCode__ = "/Faq/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['admin'] -> __pkValue__ <= 0) {
            $this -> __errorCode__ = "/Faq/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
            return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> faq_id)) $this -> getData($json -> faq_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Faq/masterModifyProcess/2";
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
			$this -> __errorCode__ = "/Faq/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> faq_id)) $this -> getData($json -> faq_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/Faq/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 메세지 정보입니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/Faq/deleteProcess/3";
			$this -> __errorMsg__ = "FAQ 정보를 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}
}
?>