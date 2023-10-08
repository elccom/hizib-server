<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Webpage {
    function __construct(){
    }
        
    function init() {
        global $_lib;

		//session_destroy();
		if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
		if(!isset($_lib['db']['handler']['slave'])) $_lib['db']['handler']['slave'] = new DBConn($_lib['db']['slave']);			
		
		//변수 초기화
		if(isset($_lib['mycurrency'])) $_lib['mycurrency'] = $_lib['currency']['field']['kwr'];
		if(isset($_lib['mylanguage'])) $_lib['mylanguage'] = $_lib['languages']['field']['ko'];
		
		//플랫폼 변수 
		$_lib['website'] -> plaform = str_replace($_lib['website'] -> domain, "", str_replace(".".$_lib['website'] -> domain, "", str_replace('dev-', '', $_SERVER['HTTP_HOST'])));
		if(check_blank($_lib['website'] -> plaform)) $_lib['website'] -> plaform = 'www';
		$_lib['website'] -> base = str_replace('dev-', '', $_SERVER['HTTP_HOST']);

		foreach($_lib['url'] as $name => $value) {
			//echo $name.":".$value."-".$_SERVER['HTTP_HOST']."<br>";
			if(str_replace("\/\/", "", $value) == $_SERVER['HTTP_HOST']) {
				$_lib['website'] -> plaform = $name;
				$_lib['website'] -> base = str_replace("\/\/", "", $value);
			}
		}

		//print_r($_lib['website']);

		//echo phpinfo();exit();
        //echo $_lib['directory']['home']."/include/init.conf";
        //기본설정 가져오기
		try {
			includeFile($_lib['directory']['home']."/include", "/\.conf$/");
		} catch(Exception $e) {
			echo $e -> getMessage();exit();
		}

		if(isset($_lib['cryptedPasswd'])) $_lib['cryptedPasswd'] = false;
		if(isset($_lib['doubleLogin'])) $_lib['doubleLogin'] = false;
		        
        
        $urls = ['/User/login'];
		
		//회원 정보
		$_lib['user'] = new User();

		//관리자 정보
		$_lib['admin'] = new Admin();

		//스마트도어 정보
		$_lib['smartdoor'] = new Smartdoor();
		
		if(!empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$_token = trim(str_replace('<br />', '', str_replace("Bearer ", "", $_SERVER['HTTP_AUTHORIZATION'])));
			//echo $_token."<br>";exit();

			if(!check_blank(trim($_token))) {
				//토큰발행
				try {
					$data = JWT::decode($_token, new Key($_lib['website'] -> name, 'HS512'));				
				} catch(Exception $e) {
					header("HTTP/1.1 401 Unauthorized");
					header("Content-type: application/json");
					echo '{"code":"/Webpage/init/2","message":"유효하지 않는 토큰 정보입니다."}';
					exit();
				}

				//print_r($data);exit();

				if(isset($data -> admin_id)) $_lib['admin'] -> getData($data -> admin_id);
				if(isset($data -> user_id)) $_lib['user'] -> getData($data -> user_id);
				if(isset($data -> smartdoor_id)) $_lib['smartdoor'] -> getData($data -> smartdoor_id);
			}
		}
    }
    
    function createDoc() {
        global $_lib;
        
		//echo phpinfo();exit();
        $this -> init();
        
        $temp = explode("?", $_SERVER['REQUEST_URI']);
        $page = $temp[0];
        $path = explode("/", $page);
        if($path[count($path) - 1] == '') $path[count($path) - 1] = 'index';
        $page = implode("/", $path);
        //print_r($path);//exit();
		//print_r($page);exit();

		$args = new stdClass();
		//echo $_SERVER['HTTP_CONTENT_TYPE'];exit();

		if(!empty($_SERVER['HTTP_CONTENT_TYPE']) && preg_match("/application\/json/", $_SERVER['HTTP_CONTENT_TYPE'])) {
			$body = file_get_contents("php://input");
			if(!empty($body) && isJson($body)) {
				$args = (object)jsondecode($body);
				//print_r($args);exit();
			} elseif(!empty($body) && !isJson($body)) {
				header("HTTP/1.1 400 Bad Request");
				header("Content-type: application/json");
				echo '{"code":"/Webpage/createDoc/1","message":"전송된 데이터가 JSON 형식이 아닙니다.'.$body.'"}';
				exit();
			}
		}
		
		foreach($_REQUEST as $key => $value) $args -> $key = $value;
        
        if(count($path) == 2 && class_exists($path[1])) {
            $className = $path[1];
            
			$obj = new $className;

			if($_SERVER['REQUEST_METHOD'] == "POST") {
				if(!$obj -> joinProcess($args)) {
					header("HTTP/1.1 400 Bad Request");
					header("Content-type: application/json");
					echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
				} else {
					header("Content-type: application/json");
					echo $obj -> toJson($args);
				}
			} elseif($_SERVER['REQUEST_METHOD'] == "PUT") {
				if(!$obj -> modifyProcess($args)) {
					header("HTTP/1.1 400 Bad Request");
					header("Content-type: application/json");
					echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
				} else {
					header("Content-type: application/json");
					echo $obj -> toJson($args);
				}
			} else {
				header("HTTP/1.1 404 Not Found");
				header("Content-type: application/json");
				echo '{"code":"/Webpage/createDoc/2","message":"지원하지 않는 서비스입니다."}';
			}
		} else if(count($path) == 3 && class_exists($path[1])) {
            $className = $path[1];
            $method = $path[2];
            
            $obj = new $className;
			$pkValue = $obj -> __pkName__;

			if(preg_match('/^[0-9]+$/', $method)) {
				if($_SERVER['REQUEST_METHOD'] == "PUT") {
					$obj -> getData($method);
					$args -> $pkValue = $obj -> __pkValue__;

					if(!$obj -> masterModifyProcess($args)) {
						header("HTTP/1.1 400 Bad Request");
						header("Content-type: application/json");
						echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
					} else {
						header("Content-type: application/json");
						echo $obj -> toJson($args);
					}
				} else if($_SERVER['REQUEST_METHOD'] == "DELETE") {
					$obj -> getData($method);
					$args -> $pkValue = $obj -> __pkValue__;

					if(!$obj -> deleteProcess($args)) {
						header("HTTP/1.1 400 Bad Request");
						header("Content-type: application/json");
						echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
					} else {
						header("Content-type: application/json");
						jsonMessage('{"result":true}');
					}
				} else if($_SERVER['REQUEST_METHOD'] == "GET") {
					if(!$obj -> search($method)) {
						header("HTTP/1.1 400 Bad Request");
						header("Content-type: application/json");
						echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
					} else {
						header("Content-type: application/json");
						echo $obj -> toJson();
					}
				} else {
					header("HTTP/1.1 404 Not Found");
					header("Content-type: application/json");
					echo '{"code":"/Webpage/createDoc/3","message":"지원하지 않는 서비스입니다."}';
				}
			} else if(method_exists($obj, $method)) {
                $response = getVars('response');
				
				if(!check_blank($args)) $result = $obj -> $method($args);
				else $result = $obj -> $method();

				if(gettype($result) == 'array') {
					header("Content-type: application/json");
					echo '[';
					for($i=0; $i<count($result); $i++) {
						$obj = $result[$i];

						if($i != 0) echo ",";

						if(gettype($obj) == 'object' && method_exists($obj, 'toJson')) echo $obj -> toJson();
						elseif(gettype($obj) == 'object') echo jsonencode($obj);
						else echo '"'.$obj.'"';
					}
					echo ']';
					exit();
				} else if(gettype($result) == 'object' && is_a($result, 'Component')) {
					header("Content-type: application/json");
					echo $result -> toJson();
				} else if(gettype($result) == 'object' && is_a($result, 'Components')) {
					header("Content-type: application/json");
					echo $result -> toJson();
				} else if(gettype($result) == 'object') {
					header("Content-type: application/json");
					echo jsonencode($result);
				} else if(gettype($result) == 'string') {
					header("Content-type: html/text");
					echo $result;
				} else if(gettype($result) == 'boolean') {
					if(!$result) {
						header("HTTP/1.1 400 Bad Request");
						header("Content-type: application/json");
						echo '{"code":"'.$obj -> __errorCode__.'","message":"'.$obj -> __errorMsg__.'"}';
					} else {
						header("Content-type: application/json");
						jsonMessage('{"result":true}');
					}
				} else {
					header("HTTP/1.1 404 Not Found");
					header("Content-type: application/json");
					echo '{"code":"/Webpage/createDoc/4","message":"지원하지 않는 서비스입니다."}';
				}
            } else {
				header("HTTP/1.1 404 Not Found");
				header("Content-type: application/json");
				echo '{"code":"/Webpage/createDoc/5","message":"지원하지 않는 서비스입니다."}';
            }
        } else {
			header("HTTP/1.1 404 Not Found");
			header("Content-type: application/json");
			echo '{"code":"/Webpage/createDoc/6","message":"지원하지 않는 서비스입니다."}';
		}

		flush();
		exit();
    }
}
?>