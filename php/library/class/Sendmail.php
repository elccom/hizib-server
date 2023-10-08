<?php
class Sendmail extends Component {
    function __construct(){
        $this -> __tableName__ = 'sendmail';
        $this -> __pkName__ = 'sendmail_id';

		$this -> __columns__ = jsondecode('[
            {"field":"'.$this -> __pkName__.'","type":"bigint","option":"unsigned","keytype":"primary","key":"'.$this -> __pkName__.'","extra":"auto_increment"},
            {"field":"from_name","type":"varchar","size":255,"key":"from_name","default":""},
            {"field":"from_email","type":"varchar","size":255,"key":"from_email","default":""},
            {"field":"to_name","type":"varchar","size":255,"key":"to_name","default":""},
            {"field":"to_email","type":"varchar","size":255,"key":"to_email","default":""},
            {"field":"title","type":"varchar","size":255,"key":"title","default":""},
			{"field":"body","type":"longtext","default":""},
            {"field":"file","type":"varchar","size":255,"key":"file","default":""},
            {"field":"templateSid","type":"varchar","size":255,"key":"templateSid","default":""},
			{"field":"data","type":"json","default":"{}"},
			{"field":"sendedDate","type":"timestamp","key":"sendedDate","default":"0000-00-00 00:00:00"},
			{"field":"regDate","type":"timestamp","key":"regDate","default":"CURRENT_TIMESTAMP"},
            {"field":"status","type":"int","size":1,"key":"status","default":1}
        ]');
        
        parent::__construct();
		$this -> install();

		$this -> data -> to = [];
    }
   
    function getDataAllByCondition($condition, $isEcho=false) {
        $this -> getDataByCondition($condition, $isEcho);
    }
    
    function getDataAll($pkValue, $isEcho=false) {
        $this -> getData($pkValue, $isEcho);
    }
    
    function isValidate($obj) {
        if(empty($obj -> from_email)) {
            $this -> __errorCode__ = "/Sendmail/isValidate/1";
            $this -> __errorMsg__ = '보내는 사람 이메일 정보를 입력해 주십시오.';
            return false;
        }
        
        if(empty($obj -> to_email)) {
            $this -> __errorCode__ = "/Sendmail/isValidate/2";
            $this -> __errorMsg__ = '받는 사람 이메일 정보를 입력해 주십시오.';
            return false;
        }
        
        if(empty($obj -> title)) {
            $this -> __errorCode__ = "/Sendmail/isValidate/3";
            $this -> __errorMsg__ = '메일 제목을 입력해 주십시오.';
            return false;
        }
        
		if(empty($obj -> body) && empty($obj -> file) && empty($obj -> templateSid)) {
            $this -> __errorCode__ = "/Sendmail/isValidate/4";
			$this -> __errorMsg__ = '메일 내용을 입력해 주세요.';
			return false;
		}

              
        return true;
    }
    
    
    function saveAll($json='') {
		global $_lib;

        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;
		//print_r($json);exit();
        
        if($this -> __pkValue__ <= 0 && isset($json -> sendmail_id)) $this -> getData($json -> sendmail_id);

		$this -> setJson($json);
		
		if(!empty($this -> to_name) && !empty($this -> to_email)) $this -> addPerson('{"name":"'.$this -> to_name.'","email":"'.$this -> to_email.'"}');
		if(empty($this -> to_name) && count($this -> data -> to)) $this -> to_name = $this -> data -> to[0] -> name;
		if(empty($this -> to_email) && count($this -> data -> to)) $this -> to_email = $this -> data -> to[0] -> email;
        
        $this -> updateDate = now();
        if(check_blank($this -> regDate)) $this -> regDate = now();
        
        if(!$this -> isValidate($this)) return false;
        
        if(!$this -> save()) {
            $this -> __errorCode__ = "/Sendmail/saveAll/1";
            $this -> __errorMsg__ = '메일발송 정보를 저장하는데 실패하였습니다.';
            return false;
        }
        
        return true;
    }

	//▒▒	발송대상을 추가하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function addPerson($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;	
		//print_r($json);exit();
		
		if(empty($json -> name)) $json -> name = '';
		
		if(empty($json -> email)) {
            $this -> __errorCode__ = "/Sendmail/addPerson/1";
			$this -> __errorMsg__ = '받는분 이메일을 입력해 주세요.';
			return false;
		}
		
		if(empty($json -> params)) $json -> params = new stdClass();

		array_push($this -> data -> to, $json);

		return true;
	}

	//▒▒	메일발송		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function sendProcess($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;	
		//print_r($json);exit();
		
		if(!$this -> saveAll($json)) return false;
		
		if($this -> localSendmail($json)) return true;

		return true;
	}

	function localSendmail($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;	
		//print_r($json);exit();
		
		if(empty($json -> from_email)) {
            $this -> __errorCode__ = "/Sendmail/localSendmail/1";
			$this -> __errorMsg__ = '보내는분 이메일을 입력해 주세요.';
			return false;
		}
		
		if(empty($json -> to_mail)) {
            $this -> __errorCode__ = "/Sendmail/localSendmail/2";
			$this -> __errorMsg__ = '받는분 이메일을 입력해 주세요.';
			return false;
		}
		
		if(empty($json -> title)) {
            $this -> __errorCode__ = "/Sendmail/localSendmail/3";
			$this -> __errorMsg__ = '제목을 입력해 주세요.';
			return false;
		}
		
		if(empty($json -> body)) {
            $this -> __errorCode__ = "/Sendmail/localSendmail/4";
			$this -> __errorMsg__ = '내용을 입력해 주세요.';
			return false;
		}

		if(mail($json -> to_mail, $json -> title, $json -> body, "From:".$json -> from_email."\r\n")) {
            $this -> __errorCode__ = "/Sendmail/localSendmail/5";
			$this -> __errorMsg__ = "서버에서 메일 발송에 실패하였습니다.";
			return false;
		}
		
		return true;
	}

	function phpmailerSendmail($json='') {
		global $_lib;
		
		//echo phpinfo();exit();
        if(is_string($json)) $json = jsondecode($json);
        if(empty($json)) $json = new stdClass();
        if(is_array($json)) $json = (object) $json;	
		//print_r($json);exit();

		return false;
		
		$mail = new PHPMailer\PHPMailer\PHPMailer(true);								// Passing `true` enables exceptions
		try {
			//Server settings
			$mail -> SMTPDebug = 2;														// Enable verbose debug output
			//$mail -> isSMTP();														// Set mailer to use SMTP
			$mail -> Host = $_lib['smtp']['host'];										// Specify main and backup SMTP servers
			$mail -> SMTPAuth = $_lib['smtp']['auth'];									// Enable SMTP authentication
			$mail -> Username = $_lib['smtp']['username'];								// SMTP username
			$mail -> Password = $_lib['smtp']['password'];								// SMTP password
			$mail -> SMTPSecure = $_lib['smtp']['secure'];								// Enable TLS encryption, `ssl` also accepted
			$mail -> Port = $_lib['smtp']['port'];										// TCP port to connect to
			
			//Recipients
			$mail -> setFrom($_lib['email']['member_join']['from_email'], iconv("UTF-8", "EUC-KR", $_lib['email']['member_join']['from_name']));
			$mail -> addAddress($userObj -> personObj -> email, iconv("UTF-8", "EUC-KR", $userObj -> personObj -> name));     // Add a recipient
			
			//Content
			$mail -> isHTML(true);                                  // Set email format to HTML
			$mail -> Charset = 'EUC-KR';
			$mail -> Encoding = 'base64';
			
			$mail -> Subject =  "=?UTF-8?B?".base64_encode("아이디 찾기")."?="."\r\n";
			$mail -> msgHTML(restoreQuotes("등록하신 아이디는 ".$userObj -> id." 입니다."));
			
			$mail -> send();
		} catch (Exception $e) {
            $this -> __errorCode__ = "/Sendmail/phpmailerSendmail/1";
			$this -> __errorMsg__ = $mail -> ErrorInfo;
			return false;
		}

		return true;
	}
}
?>