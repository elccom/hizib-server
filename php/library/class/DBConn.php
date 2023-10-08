
<?
//▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒
//▒▒
//▒▒			DBConn Class
//▒▒			
//▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒
class DBConn {
	var $__errorMsg__;

	var $db_handler;
	var $type;
	var $host;
	var $id;
	var $passwd;
	var $name;
	var $charset;

	var $max;
	var $total;

	//▒▒	생성자		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function __construct($config) {
		$this -> type = $config -> type;
		$this -> host = $config -> host;
		$this -> user = $config -> user;
		$this -> passwd = $config -> passwd;
		$this -> name = $config -> name;
		$this -> charset = $config -> charset;

		$this -> connnect();
	}

	//▒▒	DB연결		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function connnect() {
		if($this -> type == 'mysql') $this -> connectMysql();
		else throw new Exception('DB 종류를 선택해 주십시오.');
	}

	//▒▒	mysql 연결		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function connectMysql() {
		if(check_blank($this -> host)) throw new Exception('DB Host 정보가 없습니다.');
		if(check_blank($this -> user)) throw new Exception('DB 아이디 정보가 없습니다.');
		if(check_blank($this -> passwd)) throw new Exception('DB 비밀번호 정보가 없습니다.');
		if(check_blank($this -> name)) throw new Exception('DB명 정보가 없습니다.');
		
		$this -> db_handler = @new mysqli($this -> host, $this -> user, $this -> passwd, $this -> name);		
		if($this -> db_handler -> connect_errno) {
			header("HTTP/1.1 500 Internal Server Error");
			header("Content-type: application/json");
			echo '{"code":"/DBConn/connectMysql/1","message":"DB연결에 실패하였습니다."}';
			exit();
		}
		
		if(!check_blank($this -> charset)) {
			$result = $this -> query("SET NAMES '".$this -> charset."'");
			if(!$result) throw new Exception("1:".$this -> db_handler -> error);

			$result = $this -> query("SET SESSION CHARACTER_SET_CONNECTION='".$this -> charset."'");
			if(!$result) throw new Exception("1:".$this -> db_handler -> error);

			$result = $this -> query("SET SESSION CHARACTER_SET_RESULTS='".$this -> charset."'");
			if(!$result) throw new Exception("2:".$this -> db_handler -> error);

			$result = $this -> query("SET SESSION CHARACTER_SET_CLIENT='".$this -> charset."'");
			if(!$result) throw new Exception("3:".$this -> db_handler -> error);
		}
	}

	//▒▒	쿼리		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function query($query, $isEcho=false) {
		if($isEcho) {echo $query;exit();}
		if(!$this -> db_handler) throw new Exception("데이터베이스에 연결이 안되어 있습니다.");

		if($this -> type == 'mysql') $result = $this -> db_handler -> query($query);
		else throw new Exception("지원되지 않는 데이터 베이스입니다.");

		if(!$result) $this -> __errorMsg__ = $this -> db_handler -> errno." : ".$this -> db_handler -> error;
		
		return $result;
	}
	
	//▒▒	select_db쿼리		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function select_db($dbname) {
	    return $this -> db_handler -> select_db($dbname);
	}
	
	//▒▒	현재 접속되어 있는 DB		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function selected_db() {
	    $result = $this -> query("SELECT DATABASE()");
	    $data = $result -> fetch_array();
	    return $data[0];
	}
	
	//▒▒	select쿼리		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function selectQuery($obj, $isEcho=false) {
		if($this -> type == 'mysql') return $this -> selectQueryMysql($obj, $isEcho);
		else throw new Exception('DB 종류를 선택해 주십시오.');
	}

	//▒▒	select쿼리		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function selectQueryMysql($obj, $isEcho=false) {
		global $db_handler;
		
		if(!isset($obj -> table)) throw new Exception('조회할 테이블명을 입력해 주십시오.');
		if(!isset($obj -> field)) $obj -> field = '*';

		$query = "SELECT ".$obj -> field;

		if(isset($obj -> table) && !check_blank($obj -> table)) $query .= " FROM ".$obj -> table;
		if(isset($obj -> where) && !check_blank($obj -> where)) $query .= " WHERE ".$obj -> where;
		if(isset($obj -> groupby) && !check_blank($obj -> groupby)) $query .= " GROUP BY ".$obj -> groupby;
		if(isset($obj -> orderby) && !check_blank($obj -> orderby)) $query .= " ORDER BY ".$obj -> orderby;
		if(isset($obj -> start) && isset($obj -> rows) && $obj -> rows > 0) $query .= " LIMIT ".$obj -> start.", ".$obj -> rows;
		elseif(isset($obj -> rows) && $obj -> rows > 0) $query .= " LIMIT ".$obj -> rows;
		
		$result = $this -> query($query, $isEcho);

		if(!$result) $this -> __errorMsg__ = $this -> db_handler -> errno." : ".$this -> db_handler -> error;

		return $result;
	}

	//▒▒	insert_id		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function insert_id() {
		if($this -> type == 'mysql') return $this -> db_handler -> insert_id;
		else return 0;
	}

	//▒▒	데이터를 가져오는메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function getData($obj, $isEcho=false) {
		global $db_handler;
		
		if(check_blank($obj -> table))  throw new Exception("테이블 명을 입력해 주십시요.");
		if(check_blank($obj -> field))  throw new Exception("가져올 필드명을 입력해 주십시요.");
		
		$result = $this -> selectQuery($obj, $isEcho);
		if(!$result) throw new Exception("데이터를 가져오는데 실패하였습니다. 관리자에게 문의바랍니다.".$this -> __errorMsg__);

		$data =  $result -> fetch_array();
		//$result -> free();

		return $data[0];
	}

	//▒▒	데이터를 가져오는메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function getDataAll($obj, $isEcho=false) {
		global $db_handler;
		
		if(check_blank($obj -> table))  throw new Exception("테이블 명을 입력해 주십시요.");
		if(check_blank($obj -> field))  $obj -> field = '*';
		
		$result = $this -> selectQuery($obj, $isEcho);
		if(!$result) throw new Exception("데이터를 가져오는데 실패하였습니다. 관리자에게 문의바랍니다.".$this -> __errorMsg__);

		$data =  $result -> fetch_array();
		//$result -> free();

		return $data;
	}

	//▒▒	테이블이 존재하는지 확인하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function isTable($table, $isEcho=false) {
		$query = "SHOW TABLES FROM ".$this -> name;
		if($isEcho) {echo $query;exit();}
		$result = $this -> query($query);
		if(!$result) error($table."테이블존재여부를 확인하는데 실패하였습니다.\n관리자에게 문의바랍니다.");

		while($data = $result -> fetch_array()) {
			if($data[0] == $table) return true;
		}
		
		$result -> free();

		return false;
	}

	//▒▒	데이터를 가져오는메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function getTotal($table, $field="", $condition="", $isEcho=false) {
		global $db_handler;

		if(check_blank($field)) $field = "count(*)";

		$query = new stdClass();
		$query -> table = $table;
		$query -> field = $field;
		$query -> where = $condition;
		
		$result = $this -> selectQuery($query, $isEcho);
		if(!$result) throw new Exception("데이터를 가져오는데 실패하였습니다. 관리자에게 문의바랍니다.".$this -> __errorMsg__);

		$data =  $result -> fetch_array();
		//$result -> free();

		return $data[0];
	}

	//▒▒	데이터를 가져오는메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function getPkValue($table, $condition, $isEcho=false) {
		global $db_handler;

		$query = new stdClass();
		$query -> table = $table;
		$query -> field = $table."_id";
		$query -> where = $condition;
		
		$result = $this -> selectQuery($query, $isEcho);
		if(!$result) {
			echo $this -> selectQuery($query, 1);
			throw new Exception("데이터를 가져오는데 실패하였습니다. 관리자에게 문의바랍니다.".$this -> __errorMsg__);
		}

		if($result -> num_rows <= 0) return 0;

		$data =  $result -> fetch_array();
		//$result -> free();

		return $data[0];
	}

	//▒▒	저장하는 메소드 가져오는메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
	function save($table, $pkValue, $array, $isEcho=false) {
		global $_lib;

		if($pkValue) {
			$query = "UPDATE ".$table." SET ";
			$i=0;
			foreach($array as $key => $value) {
				if($i != 0) $query .= ",";
				$query .= $key."='".$value."'";
				$i++;
			}
			$query .= " WHERE ".$table."_id='".$pkValue."'";
			if($isEcho) { echo $query;exit(); }
			$result = $this -> query($query);

			if(!$result) throw new Exception($table."에 데이터를 업데이트를 하는데 실패하였습니다.".$this -> db_handler -> error);
			else return $pkValue;
		} else {
			$query = "INSERT INTO ".$table." (".implode(",", array_keys($array)).") VALUES ('".implode("','", array_values($array))."')";
			if($isEcho) { echo $query;exit(); }
			$result = $this -> query($query);

			if(!$result) {
				print_r($array);
				throw new Exception($table."에 데이터를 등록하는데 실패하였습니다.".$this -> db_handler -> error);
			} else return $this -> insert_id();
		}
	}
}
?>