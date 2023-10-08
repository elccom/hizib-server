<?php
class SmartdoorVod extends Component {    
    function __construct(){
        $this -> __tableName__ = 'smartdoor_vod';
        $this -> __pkName__ = 'smartdoor_vod_id';

		$this -> smartdoorObj = new Smartdoor();

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"'.$this -> smartdoorObj -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"foreign","key":"'.$this -> smartdoorObj -> __pkName__.'","refrence":"'.$this -> smartdoorObj -> __tableName__.'('.$this -> smartdoorObj -> __pkName__.') on delete cascade"},
			{"field":"gcode","type":"varchar","size":255,"key":"gcode","default":""},
			{"field":"code","type":"varchar","size":255,"key":"code","default":""},
			{"field":"filepath","type":"varchar","size":255,"key":"filepath","default":""},
			{"field":"fileurl","type":"varchar","size":255,"key":"fileurl","default":""},
			{"field":"filename","type":"varchar","size":255,"key":"filename","default":""},
			{"field":"comment","type":"text","default":""},
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
			$this -> __errorCode__ = "/SmartdoorVod/search/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		$this -> getData($pkValue);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/search/2";
			$this -> __errorMsg__ = "존재하지 않는 영상 정보입니다.";
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
		if(empty($json -> sort)) $json -> sort = "a_smartdoor_vod_id";
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
		$listObj -> setJoin("SmartdoorVod", "a", $condition);
		if(!empty($json -> keyword)) $listObj -> setAndCondition("(a.gcode like '%".$keyword."%' OR a.code like '%".$keyword."%' OR a.filepath like '%".$keyword."%' OR a.fileurl like '%".$keyword."%'' OR a.filename like '%".$keyword."%')");
		$listObj -> setSort($json -> sort, $json -> desc);
		
		$listObj -> page = $json -> page;
		$listObj -> rowsPerPage = $json -> rowsPerPage;

		if($json -> isAll) $results = $listObj -> getOnlyResults();
		else $results = $listObj -> getResults();

		if(!$results) {
			$this -> __errorCode__ = "/SmartdoorVod/lists/1";
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
				$obj = new SmartdoorVod();
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
            $this -> __errorCode__ = "/SmartdoorVod/isValidate/1";
			$this -> __errorMsg__ = "스마트도어 정보가 없습니다.";
            return false;
        }
        
        if(check_blank($obj -> gcode)) {
            $this -> __errorCode__ = "/SmartdoorVod/isValidate/2";
			$this -> __errorMsg__ = "일자를 입력해 주세요.";
            return false;
        }   
		
        if(check_blank($obj -> code)) {
            $this -> __errorCode__ = "/SmartdoorVod/isValidate/3";
			$this -> __errorMsg__ = "시간을 입력해 주세요.";
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
        
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);
        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_id) && !empty($json -> gcode) && !empty($json -> code)) $this -> getDataByCondition("smartdoor_id='".$json -> smartdoor_id."' AND gcode='".$json -> gcode."' AND code='".$json -> code."'");
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
            $this -> __errorCode__ = "/SmartdoorVod/saveAll/1";
			$this -> __errorMsg__ = "영상 정보를 저장하는데 실패하였습니다.";
            return false;
        }

		if(!$this -> vodUpload($json)) return false;

		if($this -> smartdoorObj -> __pkValue__ <= 0 && $this -> smartdoor_id) $this -> smartdoorObj -> getData($this -> smartdoor_id);

		//echo $this -> smartdoorObj -> getDoorTopic();
		//echo "<br>";
		//echo '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data",'.$this -> toJson('', ['smartdoorObj']).'}';

		mqtt_publish($_lib['mqtt']['host'], $this -> smartdoorObj -> getDoorTopic(), '{"request":"/'.get_class($this).'/updateProcess","sender":"'.$this -> smartdoorObj -> getOwnerTopic().'","receiver":"'.$this -> smartdoorObj -> getDoorTopic().'","data":'.$this -> toJson('', ['smartdoorObj']).'}');
        
        return true;
    }

	function joinProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/joinProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if($_lib['smartdoor'] -> __pkValue__) $json -> smartdoor_id = $_lib['smartdoor'] -> __pkValue__;
		
		if($json -> smartdoor_id <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/joinProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 스마트도어 정보입니다.";
			return false;
		}

		if(empty($json -> gcode)) $json -> gcode = date("Ymd");
		if(empty($json -> code)) $json -> code = date("Hi");

		if(!$this -> saveAll($json, $isEcho)) return false;

		if(!$this -> vodUpload($json)) return false;
        
        return true;
	}

	function modifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		$this -> __errorCode__ = "/SmartdoorVod/modifyProcess/1";
		$this -> __errorMsg__ = "지원하지 않는 서비스입니다.";
		return false;
	}

	function masterModifyProcess($json='', $isEcho=false) {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/masterModifyProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

		if(isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/masterModifyProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 영상 정보입니다.";
			return false;
		}		

		if($_lib['smartdoor'] -> __pkValue__ && $this -> smartdoor_id == $_lib['smartdoor'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorVod/masterModifyProcess/3";
			$this -> __errorMsg__ = "수정 관한이 없습니다.";
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

		if($_lib['smartdoor'] -> __pkValue__ <= 0 && $_lib['admin'] -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/deleteProcess/1";
			$this -> __errorMsg__ = "로그인 후 이용해 주세요.";
			return false;
		}

        if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/deleteProcess/2";
			$this -> __errorMsg__ = "존재하지 않는 영상 정보입니다.";
			return false;
		}

		if($_lib['smartdoor'] -> __pkValue__ && $this -> smartdoor_id != $_lib['smartdoor'] -> __pkValue__) {
			$this -> __errorCode__ = "/SmartdoorVod/deleteProcess/3";
			$this -> __errorMsg__ = "삭제 관한이 없습니다.";
			return false;
		}

        if(!parent::delete($isEcho)) {
			$this -> __errorCode__ = "/SmartdoorVod/deleteProcess/4";
			$this -> __errorMsg__ = "영상을 삭제하는데 실패하였습니다.";
			return false;
		}

		return true;
	}

	function vodUpload($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);
		
		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/vodUpload/1";
			$this -> __errorMsg__ = "존재하지 않는 녹화영상 정보입니다.";
			return false;
        }

		if(!$this -> vodUploadProcess($json)) return false;

		return true;
	}

	function vodUploadProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);

		if($this -> __pkValue__ <= 0) {
			$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/1";
			$this -> __errorMsg__ = "존재하지 않는 녹화영상 정보입니다.";
			return false;
        }

		//print_r($_FILES);exit();

		if(isset($_FILES['file'])) $json -> file = $_FILES['file'];
		
		$path = $_lib['directory']['home']."/vod/".$this -> smartdoor_id."/".$this -> gcode;

		//설치 디렉토리 만들기
		if(!makeDirectory($path)) {
            $this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/2";
			$this -> __errorMsg__ = "업로드할 디렉토리를 만드는데 실패하였습니다.";
            return false;
        }

		if(empty($this -> gcode)) $this -> gcode = date("Ymd");
		if(empty($this -> code)) $this -> code = date("Hi");
		
		$this -> filepath = $path."/".$this -> code.".mp4";
		$this -> fileurl = str_replace($_lib['directory']['home'], "", $this -> filepath);
		$this -> filename = $this -> code.".mp4";
		@chmod($this -> filepath, 0777);

		//삭제여부
		if(!empty($json -> isDel)) $json -> isDel = true;
		elseif(file_exists($this -> filepath) && isset($json -> file) && !is_array($json -> file) && preg_match('/base64/i', $json -> file)) $json -> isDel = true;
		elseif(file_exists($this -> filepath) && isset($json -> file) && isset($json -> file['size']) && $json -> file['size'] > 0) $json -> isDel = true;
		else $json -> isDel = false;

		//기존 파일 삭제
		if(isset($json -> isDel) && $json -> isDel) {
			if(!empty($this -> filepath) && file_exists($this -> filepath) && is_writable($this -> filepath)) {
				if(!rmUtil($this -> filepath)) {
					$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/3";
					$this -> __errorMsg__ = "영상 파일 삭제 권한이 없습니다.";
					return false;
				}
			}
		}
		
		if(isset($json -> file['tmp_name'])) {
			//신규 파일 등록
			if(!move_uploaded_file($json -> file['tmp_name'], $this -> filepath)) {
				$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/4";
				$this -> __errorMsg__ = "파일을 업로드하는데 실패하였습니다.";
				return false;
			}			
		} elseif(isset($json -> file) && preg_match('/base64/i', $json -> file)) {
			$temp = explode(";base64,", $json -> file);

			$data = base64_decode(trim($temp[1]));

			if(!file_put_contents($this -> filepath, $data)) {
				$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/5";
				$this -> __errorMsg__ = $path."에 ".$this -> filename."을 복사하는데 실패하였습니다.";
				return false;
			}
		}
		
		if(!file_exists($this -> filepath)) {
			$this -> filepath = "";
			$this -> fileurl = "";
			$this -> filename = "";

			if(!$this -> save()) {
				$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/6";
				$this -> __errorMsg__ = "녹화영상을 업로드하는데 실패하였습니다.";
				return false;
			}
		}

		if(!$this -> save()) {
			$this -> __errorCode__ = "/SmartdoorVod/vodUploadProcess/7";
			$this -> __errorMsg__ = "녹화영상 정보를 저장하는데 실패하였습니다.";
			return false;
			
		}

		return true;
	}

	function streamProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();

		if($this -> __pkValue__ <= 0 && isset($json -> smartdoor_vod_id)) $this -> getData($json -> smartdoor_vod_id);
		
		$obj = new VideoStream($this -> filepath);
        $obj -> start();
		exit();
	}
}
?>