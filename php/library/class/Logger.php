<?php
class Logger extends Component {    
    function __construct(){
        $this -> __tableName__ = 'logger';
        $this -> __pkName__ = 'logger_id';

		$this -> __columns__ = jsondecode('[
			{"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
			{"field":"type","type":"int","size":1,"key":"type","default":1},
			{"field":"location","type":"varchar","size":255,"key":"location","default":""},
			{"field":"data","type":"json","default":"{}"},
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
	    
    function isValidate($obj) {
        if(check_blank($obj -> location)) {
			$this -> __errorCode__ = "/Logger/isValidate/1";
            $this -> __errorMsg__ = '위치를 입력해 주세요.';
            return false;
        }

        if(check_blank($obj -> data)) {
			$this -> __errorCode__ = "/Logger/isValidate/2";
            $this -> __errorMsg__ = '데이터를 입력해 주세요.';
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
        
        if(isset($json -> logger_id)) $this -> getData($json -> logger_id);
        
        $this -> setJson($json);

        if(!$this -> isValidate($this)) return false;

		if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> save($isEcho)) {
			$this -> __errorCode__ = "/Logger/saveAll/1";
            $this -> __errorMsg__ = '로그 정보를 저장하는데 실패하였습니다.';
            return false;
        }
        
        return true;
    }
}
?>