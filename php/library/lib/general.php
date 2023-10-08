<?php
/*
 *  JSON -> String
 */
function jsondecode($str) {
    $str = json_decode($str);
    return $str;
}

/*
 *  String -> JSON
 */
function jsonencode($str) {
    $str = json_encode($str, JSON_UNESCAPED_UNICODE);
    return $str;
}

/*
 *  includeFileContent
 */
function includeFileContent($path) {
	ob_start();
	ob_implicit_flush(false);
	include($path);
	return ob_get_clean();
}

/*
 *  JSON Config 파일 가져오기
 */
function getDbConfigByJsonFile($file='config.json') {
    return jsondecode(file_get_contents($file))   ;
}

/*
 * REQUEST에서 Phone 가져오기
 */
function getPhone($name='phone') {
    $phone1 = getVars($name.'1');
    $phone2 = getVars($name.'2');
    $phone3 = getVars($name.'3');
    
    $phone = $phone1.'-'.$phone2.'-'.$phone3;
    
    if($phone == '--') return '';
    else return $phone;
}

function getMenus($name, $depth) {
    $menuObj = new Menu();
    if($menuObj -> getTotal()  <= 0) {
        $menuObj = new Menu();
        if(!$menuObj -> init()) scriptMessage('{"message":"'.$menuObj -> __errorMsg__.'","after":"history.back();"}');
    }
    
    $array = array();
    
    $listObj = new Components();
    $listObj -> setJoin("Menu", "a", "a.name='".$name."' and a.depth='".$depth."' AND a.isUse=1");
    $listObj -> setJoin("Menu", "b", "b.realparent=a.menu_id and b.depth=".($depth + 1)."");
    $listObj -> setSort("b.sortNum");
    $results = $listObj -> getResults();
    
    $_topMenu = array();
    while($data = $results -> fetch_array()) {
        $menuObj = new Menu();
        $menuObj -> setData($data, 'b');
        
        array_push($array, $menuObj);
    }
    
    return $array;
}

function getMenuId($url = "", $level=1) {
    if(check_blank($url)) $url = $_SERVER['REQUEST_URI'];
    
    $menus = getMenuPath($url);
    
    if(count($menus) <= $level) return 0;
    
    $menuObj = $menus[$level];
    
    return $menuObj -> __pkValue__;
}

function getMenuPath($url = "") {
    if(check_blank($url)) $url = $_SERVER['REQUEST_URI'];
    
    $menuObj = new Menu();
    $menuObj -> getDataByCondition("href='".$url."' ORDER BY depth desc LIMIT 1");
    if($menuObj -> __pkValue__ <= 0) $menuObj -> getDataByCondition("href='".getUrlPage($url)."' ORDER BY depth desc LIMIT 1");
    if($menuObj -> __pkValue__ <= 0) $menuObj -> getDataByCondition("href='".getAbsoluteUrlPage($url)."' ORDER BY depth desc LIMIT 1");
    if($menuObj -> __pkValue__ <= 0) return array();
    
    $array = [];
    array_push($array, $menuObj);
    
    $realparent = $menuObj -> realparent;
    
    for($i=$menuObj -> depth - 1; $i>0; $i--) {
        $menuObj = new Menu();
        $menuObj -> getData($realparent);
        
        $realparent = $menuObj -> realparent;
        
        array_push($array, $menuObj);
    }
    
    return array_reverse($array);
}

function getMillisecond() {
	/*
    list($microtime,$timestamp) = explode(' ',microtime());
    $time = $timestamp.substr($microtime, 2, 3);
    
    return $time;
	*/

	list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function milliseconds() {
    $mt = explode(' ', microtime());
    return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
}

function getAge($birth, $date) {
    if($birth == '') return '';  
        
    $birth = explode(' ', $birth);
    $temp = explode("-", $birth[0]);
    
    $birth_year = (int) $temp[0];
    $birth_month = (int) $temp[1];
    $birth_day = (int) $temp[2];
    
    $date = explode(' ', $date);
    $temp = explode("-", $date[0]);
    $date_year = (int) $temp[0];
    $date_month = (int) $temp[1];
    $date_day = (int) $temp[2];
    
    $year = $date_year - $birth_year;
    $month = $date_month - $birth_month;
    $day = $date_day - $birth_day;
    
    if($day < 0) $month--;
    if($month < 0) $year--;
    
    //echo $birth_year.".".$birth_month.".".$birth_day."<br>";
    //echo $competitionDate_year.".".$competitionDate_month.".".$competitionDate_day."<br>";
    //echo $year.".".$month.".".$day."<br>";
    
    return $year;
    
}

function getUnixtimeBeforeYear($year) {
	$dday = mktime(0, 0, 0, date("Y") - $year, date("n"), date("j"));

	return $dday;
}

function getRandom($length=8, $characters = '0123456789abcdefghijklmnopqrstuvwxyz') {
    $charactersLength = strlen($characters);
    
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

function displayPhoneNumber($phone) {
    $phone = preg_replace("/[^0-9]/", "", $phone);
    $length = strlen($phone);
    
    switch($length){
        case 11 :
            return preg_replace("/([0-9]{3})([0-9]{4})([0-9]{4})/", "$1-$2-$3", $phone);
            break;
        case 10:
            return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $phone);
            break;
        default :
            return $phone;
            break;
    }
}

function displayWeek($datetime) {
	if(preg_match('#[0-9]{10}#', $datetime)) $dt = $datetime;
	else $dt = getTimestamp($datetime);

	$weekString = array("일", "월", "화", "수", "목", "금", "토");
	return $weekString[date('w', $dt)];
}

//▒▒	특정페이지로 이동하 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function errorMsg($message, $url='') {
    echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
    echo('<html xmlns="http://www.w3.org/1999/xhtml">');
    echo('<head>');
    echo('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>');
    echo('<meta http-equiv="Content-Script-Type" content="text/javascript"/>');
    echo('<meta http-equiv="Content-Style-Type" content="text/css"/>');
    echo('<meta http-equiv="X-UA-Compatible" content="IE=edge"/>');
    echo('<script type="text/javascript">');
	echo('window.alert("'.addslashes($message).'");');
    if(!empty($url)) echo('location.href="'.$url.'";');
	else echo('history.back();');
    echo('</script>');
    echo('</head>');
    echo('<body>');
    echo('</body>');
    echo('</html>');
    exit();
}

//▒▒	특정페이지로 이동하 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function movepage($url) {
    echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
    echo('<html xmlns="http://www.w3.org/1999/xhtml">');
    echo('<head>');
    echo('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>');
    echo('<meta http-equiv="Content-Script-Type" content="text/javascript"/>');
    echo('<meta http-equiv="Content-Style-Type" content="text/css"/>');
    echo('<meta http-equiv="X-UA-Compatible" content="IE=edge"/>');
    echo('<script type="text/javascript">');
    echo('location.href="'.$url.'";');
    echo('</script>');
    echo('</head>');
    echo('<body>');
    echo('</body>');
    echo('</html>');
    exit();
}

//▒▒	결과 값이나 에러를 경고창으로 나타내는 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function scriptMessage($json) {
    if(is_string($json)) $json = jsondecode($json);
    
    echo('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">');
    echo('<html xmlns="http://www.w3.org/1999/xhtml">');
    echo('<head>');
    echo('<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>');
    echo('<meta http-equiv="Content-Script-Type" content="text/javascript"/>');
    echo('<meta http-equiv="Content-Style-Type" content="text/css"/>');
    echo('<meta http-equiv="X-UA-Compatible" content="IE=edge"/>');
    echo('<script type="text/javascript">');
    if(isset($json -> message)) echo('window.alert("'.addslashes($json -> message).'");');
    if(!empty($json -> after)) echo($json -> after);
    echo('</script>');
    echo('</head>');
    echo('<body>');
    echo('</body>');
    echo('</html>');
    exit();
}

//▒▒	결과 값이나 에러를 경고창으로 나타내는 함수		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function jsonMessage($json) {
    if(is_string($json)) $json = jsondecode($json);
    
    echo jsonencode($json);
    exit();
}

function getVars($name, $default='') {
    if(!isset($_REQUEST[$name])) return $default;
    else return $_REQUEST[$name];
}

function now() {
    return date("Y-m-d H:i:s");
}

function int_format($value) {
    return (int)str_replace(',', '', $value);
}

function float_format($value) {
    return (float)str_replace(',', '', $value);
}

function phone_format($phone){
    $phone = preg_replace("/[^0-9]/", "", $phone);
    $length = strlen($phone);

    switch($length){
      case 11 :
          return preg_replace("/([0-9]{3})([0-9]{4})([0-9]{4})/", "$1-$2-$3", $phone);
          break;
      case 10:
          return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $phone);
          break;
      default :
          return $phone;
          break;
    }
}

function auto_number_format($value) {
	$temp = explode(".", $value);

	if(count($temp) == 2) $num = strlen($temp[1]);
	else $num = 0;

	if($value == '') $value = 0;

	//echo $value.":".$num;

	return number_format($value, $num);
}

function getConvertNumberToKorean($_number) {
    // 0부터 9까지의 한글 배열
    $number_arr = array('','일','이','삼','사','오','육','칠','팔','구');
    
    // 천자리 이하 자리 수의 한글 배열
    $unit_arr1 = array('','십','백','천');
    
    // 만자리 이상 자리 수의 한글 배열
    $unit_arr2 = array('','만','억','조','경','해');
    
    // 결과 배열 초기화
    $result = array();
    
    // 인자값을 역순으로 배열한 후, 4자리 기준으로 나눔
    $reverse_arr = str_split(strrev($_number), 4);
    
    foreach($reverse_arr as $reverse_idx=>$reverse_number){
        // 1자리씩 나눔
        $convert_arr = str_split($reverse_number);
        $convert_idx = 0;
        $result_idx = 0;
        
        foreach($convert_arr as $split_idx=>$split_number){
            // 해당 숫자가 0일 경우 처리되지 않음
            if(!empty($number_arr[$split_number])){
                // 0부터 9까지 한글 배열과 천자리 이하 자리 수의 한글 배열을 조합하여 글자 생성
                $result[$result_idx] = $number_arr[$split_number].$unit_arr1[$split_idx];
                
                // 반복문의 첫번째에서는 만자리 이상 자리 수의 한글 배열을 앞 전 배열에 연결하여 조합
                if(empty($convert_idx)) $result[$result_idx] .= $unit_arr2[$reverse_idx];
                ++$convert_idx;
            }
            
            ++$result_idx;
        }
    }
    
    // 배열 역순으로 재정렬 후 합침
    $result = implode('', array_reverse($result));
    
    // 결과 리턴
    return $result;
}

//▒▒	타임스탬프를 구하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getTimestamp($value) {
    if(!preg_match("/-/", $value)) return '';
    
    if(check_blank($value)) return '';
    $temp = explode(" ", $value);
    $date = explode("-", $temp[0]);
    if(count($temp) > 1 && preg_match("/:/", $temp[1])) $time = explode(":", $temp[1]);
    else $time = array(0,0,0);

    if(!preg_match("/^[0-9]{4}$/", $date[0])) return '';
    
    return mktime((int)$time[0], (int)$time[1], (int)$time[2], (int)$date[1], (int)$date[2], (int)$date[0]);
}

//▒▒	PHP_SELF		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getUrlPage($url) {
    $temp = explode("?", $url);
    return str_replace($_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'], '', $temp[0]);
}

//▒▒	PHP_SELF		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getAbsoluteUrlPage($url) {
    $temp = explode("?", $url);
    return str_replace($_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'], '', preg_replace('/\/index$/', '/', $temp[0]));
}

//▒▒	URL에 변수와 값을 설정		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function setUrlParam($url, $name, $value) {
	$temp = explode("?", $url);
	$url = $temp[0];
	if(count($temp) == 1) return $url."?".$name."=".$value;

	$vars = explode("&", $temp[1]);

	$isFlag = false;
	$params = "";

	foreach($vars as $str) {
		$tmp = explode("=", $str);
		if(!check_blank($params)) $params .= "&";
		if($tmp[0] == $name) {
			$isFlag = true;
			$params.=$name."=".$value;
		} else $params .= $str;
	}

	return $url."?".$params;
}

//▒▒	슈퍼관리자 정보 가져오기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getSuperMaster() {
    $obj = new User();
    $obj -> getDataAllByCondition("level=99 AND isUse=1");
    
    return $obj;
}

//▒▒	코드 정보 가져오기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getCodeName($gcode, $code) {
    $obj = new Code();
    $obj -> getDataAllByCondition("gcode='".$gcode."' AND code='".$code."'");
    
    return $obj -> name;
}

//▒▒	코드 정보 가져오기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getCodeValue($gcode, $name) {
    $obj = new Code();
    $obj -> getDataAllByCondition("gcode='".$gcode."' AND name='".$name."'");
    
    return $obj -> code;
}

//▒▒	디바이스정보		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getDevice() {
	if(empty($_SERVER['HTTP_USER_AGENT'])) return 'pc';

	if(preg_match('/ipad/i', $_SERVER['HTTP_USER_AGENT']) ) {
		return "ipad";
	} elseif(preg_match('/iphone/i', $_SERVER['HTTP_USER_AGENT']) ) {
		return "iphone";
	} elseif(preg_match('/blackberry/i', $_SERVER['HTTP_USER_AGENT']) ) {
		return "blackberry";
	} elseif(preg_match('/android/i', $_SERVER['HTTP_USER_AGENT']) ) {
		return "android";
	}

	return "pc";
}

//▒▒	디바이스정보		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getAuthorizationHeader(){
	$headers = null;
	if (isset($_SERVER['Authorization'])) {
		$headers = trim($_SERVER["Authorization"]);
	}
	else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
		$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
	} elseif (function_exists('apache_request_headers')) {
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
		$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
		//print_r($requestHeaders);
		if (isset($requestHeaders['Authorization'])) {
			$headers = trim($requestHeaders['Authorization']);
		}
	}
	return $headers;
}

//▒▒	디바이스정보		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getBearerToken() {
	$headers = getAuthorizationHeader();
	// HEADER: Get the access token from the header
	if (!empty($headers)) {
		if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
		return $matches[1];
		}
	}

	return null;
}

//▒▒	언어 검색		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function isLang($lang) {
	global $_lib, $_SESSION;

	if(!isset($_SESSION['lang'])) $_SESSION['lang'] = $_lib['languages']['field']['ko'];

	return $_SESSION['lang'] == $_lib['languages']['field'][$lang];
}


//▒▒	쌍따옴표, 따옴표를 제거하기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function replaceQuotes($name) {
	if(!is_string($name)) return $name;
    return str_replace("'", '&#039;', str_replace('"', '&quot;', str_replace('‘', '&#039;', str_replace('’', '&#039;', $name))));
    //return str_replace("\r\n", "<br />", str_replace("'", '&#039;', str_replace('"', '&quot;', str_replace('‘', '&#039;', str_replace('’', '&#039;', $name)))));
}

//▒▒	쌍따옴표, 따옴표를 복구하기		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function restoreQuotes($name) {
    return str_replace('&#039;', "'", str_replace('&quot;', '"', str_replace("<br />", "\r\n", $name)));
}

//▒▒	base64 url encode		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function base64UrlEncode($text) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
}

//▒▒	폼 이름 자동생		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function makeFormFieldName($name, $index) {
    $temp = explode("[", $name);
    
    if(count($temp) == 1) return $name.$index;
    
    $tag = '';
    for($i=0; $i<count($temp); $i++) {
        if($i != 0) $tag .= "[";
        $tmp = explode("]", $temp[$i]);
        for($j=0; $j<count($tmp); $j++) {
            if($j != 0) $tag .= $index."]";
            $tag .= $tmp[$j];
        }
    }
    
    return $tag;
}

//▒▒	자동번역		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function translate($msg, $from='ko', $to='en') {
    $sentenceObj = new Sentence();
    $translateObj = new Translate();
    return $translateObj -> translate($msg, $from, $to);
}

//▒▒	국가정보를 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getCountry($ip) {
    $response = @file_get_contents('http://api.ipinfodb.com/v3/ip-country?key=555fe1fc5d1bce92afd2375a69b7877947aa0075510d4d4dec11ac6017bd71ad&ip=' . $ip . '&format=json');
    
    if(($json = json_decode($response, true)) === null) {
        $json['statusCode'] = 'ERROR';
        return false;
    }
        
    $json['statusCode'] = 'OK';
    
    return $json['countryCode'];
 }
    
//▒▒	도시 정보를 가져오는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function getCity($ip) {
    $response = @file_get_contents('http://api.ipinfodb.com/v3/ip-city?key=555fe1fc5d1bce92afd2375a69b7877947aa0075510d4d4dec11ac6017bd71ad&ip=' . $ip . '&format=json');
        
    if (($json = json_decode($response, true)) === null) {
        $json['statusCode'] = 'ERROR';
        return false;
    }
        
    $json['statusCode'] = 'OK';
        
    return $json;
}

//▒▒	환전금액을 계산하는 메소드		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function currency($price, $before='1', $after='2') {
    global $_lib;
    
    if($before == $after) return $price;
    
    if(time() > mktime(11, 0, 0, date("n"), date("j"), date("Y"))) $date = mktime(11, 0, 0, date("n"), date("j") - 1, date("Y"));
    else $date = time();

    $currencyObj = new Currency();
    $currencyObj -> getDataByCondition("before_currency='".$before."' AND after_currency='".$after."' AND regDate='".date("Y-m-d", $date)."'");
    
    if($currencyObj -> __pkValue__) {
        if($after == $_lib['currency']['field']['usd']) return ceil($price / $currencyObj -> exchangeRate * 100) / 100;
        else return ceil($price / $currencyObj -> exchangeRate);
    }

    $response = @file_get_contents('https://www.koreaexim.go.kr/site/program/financial/exchangeJSON?authkey=nJjGDtI81Dt2G189iFAUlkJqWz9xMzZC&searchdate='.date("Ymd", $date).'&data=AP01');
    $results = jsondecode($response);
    
    if(count($results) <= 0) {
        $response = @file_get_contents('https://www.koreaexim.go.kr/site/program/financial/exchangeJSON?authkey=nJjGDtI81Dt2G189iFAUlkJqWz9xMzZC&searchdate='.date("Ymd", $date - 86400).'&data=AP01');
        $results = jsondecode($response);
    }
    
    if(count($results) <= 0) {
        $response = @file_get_contents('https://www.koreaexim.go.kr/site/program/financial/exchangeJSON?authkey=nJjGDtI81Dt2G189iFAUlkJqWz9xMzZC&searchdate='.date("Ymd", $date - 86400 * 2).'&data=AP01');
        $results = jsondecode($response);
    }
    
    foreach($results as $result) {
        //echo $before.":".isset($before)."<br>";
        //echo strtolower($result -> cur_unit).":".isset($_lib['currency']['field'][strtolower($result -> cur_unit)])."<br>";

        if(isset($before) && isset($_lib['currency']['field'][strtolower($result -> cur_unit)])) {
            $currencyObj = new Currency();
            $currencyObj -> getDataByCondition("before_currency='".$before."' AND after_currency='".$_lib['currency']['field'][strtolower($result -> cur_unit)]."' AND regDate='".date("Y-m-d", $date)."'");
            
            $currencyObj -> before_currency = $before;
            $currencyObj -> after_currency = $_lib['currency']['field'][strtolower($result -> cur_unit)];
            $currencyObj -> regDate = date("Y-m-d", $date);
            $currencyObj -> exchangeRate = float_format($result -> ttb);
            
            if(!$currencyObj -> save()) throw new Exception("환율 정보 저장 오류(원인 : ".$currencyObj -> __errorMsg__.")");
        }
    }

    if($after == 2) return ceil($price / $currencyObj -> exchangeRate * 100) / 100;
    else return ceil($price / $currencyObj -> exchangeRate);
}

function removeTags($tag, $str) {
	//echo($tag.' 제거 소스 <xmp>');echo($str);echo('</xmp>');

	preg_match('!<'.$tag.'>(.*?)\</'.$tag.'>!is', $str, $source);
	//echo($tag.' 제거 1차변환<xmp>');print_r($source);echo('</xmp>');

	if(count($source)) {
		$str = str_replace($source[0], $source[1], $str);
		//echo($tag.' 제거 최종 <xmp>');echo($str);echo('</xmp>');
		return removeTags($tag, $str);
	}

	preg_match('!<'.$tag.'[^<]+?>(.*?)\</'.$tag.'>!is', $str, $source);
	//echo($tag.' 제거 2차변환<xmp>');print_r($source);echo('</xmp>');

	if(count($source)) {
		$str = str_replace($source[0], $source[1], $str);
		//echo($tag.' 제거 최종 <xmp>');echo($str);echo('</xmp>');

		return removeTags($tag, $str);
	} else {
		//echo($tag.' 제거 최종 <xmp>');echo($str);echo('</xmp>');
		return $str;
	}
}

function replaceTags($tag, $str) {
	//echo($tag.' 교체 src <xmp>');echo($str);echo('</xmp>');

	preg_match('!<'.$tag.'[^<]+?>(.*?)\</'.$tag.'>!is', $str, $source);
	//echo($tag.' 교체 변환<xmp>');print_r($source);echo('</xmp>');

	if(count($source)) {
		$str = str_replace($source[0], '<'.$tag.'>'.$source[1].'</'.$tag.'>', $str);
		//echo($tag.' 교체 최종 <xmp>');echo($str);echo('</xmp>');
		
		return replaceTags($tag, $str);
	} else {
		//echo($tag.' 교체 최종 <xmp>');echo($str);echo('</xmp>');
		return $str;
	}
}

function elcsoft_encrypt($str, $secret_key='elcsoft', $secret_iv='#@$%^&*()_+=-') {
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 32)    ;

    return str_replace("=", "", base64_encode(@openssl_encrypt($str, "AES-256-CBC", $key, 0, $iv)));
}


function elcsoft_decrypt($str, $secret_key='elcsoft', $secret_iv='#@$%^&*()_+=-') {
    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 32);

    return @openssl_decrypt(base64_decode($str), "AES-256-CBC", $key, 0, $iv);
}

function getTag($html, $pattern) {
	$temp = explode("*", $pattern);
	
	if(preg_match('#>#', $temp[0])) $isInner = true;
	else $isInner = false;

	$pattern = '#'.str_replace('*', '(.*?)', $pattern).'#';

	preg_match($pattern, $html, $temp);

	if(count($temp)) return $isInner ? $temp[1] : $temp[0];
	
	return '';
}

function getNode($comment, $tag) {
	$comment = htmlspecialchars_decode($comment);
	//echo '<XMP>'.$comment.'</XMP>';exit();

	$html = new simple_html_dom();
	$html -> load($comment);

	foreach($html -> root -> nodes as $obj) {
		foreach($obj -> children as $o) {
			//echo '<xmp>'.$o -> nodetype.' : '.$o -> tag.' : '.$o -> outertext.' : '.$o -> innertext.'</xmp>';
			if($o -> nodetype == 3) return '';
			else if($o -> tag == $tag) return $o -> innertext;
			else return getNode($o -> innertext, $tag);
		}		
	}

	return '';
}

function getNodeClass($comment, $class) {
	$comment = htmlspecialchars_decode($comment);
	//echo '<XMP>'.$comment.'</XMP>';exit();

	$html = new simple_html_dom();
	$html -> load($comment);

	foreach($html -> root -> nodes as $obj) {
		//echo '<xmp>'.$obj -> nodetype.' : '.$obj -> tag.' : '.$obj -> outertext.'</xmp>';
		
		if($obj -> nodetype == 3) return '';
		else if($obj -> hasClass($class)) return $obj -> outertext;
		else return getNodeClass($obj -> innertext, $class);
	}

	return '';
}

function getSameStr($a, $b) {
	$count = mb_strlen($a, 'utf-8');

	for($i=0; $i<$count; $i++) {
		if(mb_substr($a, 0, $i) != mb_substr($b, 0, $i)) return mb_substr($a, 0, $i - 1);
	}
}


 function sess_open($sess_path, $sess_name) {
	$obj = new Session();
	$obj -> open($sess_path, $sess_name);
	//print "Session opened.\n";
	//print "Sess_path: $sess_path\n";
	//print "Sess_name: $sess_name\n\n";
	return true;
}

function sess_close() {
	$obj = new Session();
	$obj -> close();
	//print "Session closed.\n";
	return true;
}

function sess_read($sess_id) {
	$obj = new Session();
	$obj -> read($sess_id);
	//print "Session read.\n";
	//print "Sess_ID: $sess_id\n";
	return '';
}

function sess_write($sess_id, $data) {
	$obj = new Session();
	$obj -> write($sess_id, $data);
	//print "Session value written.\n";
	//print "Sess_ID: $sess_id\n";
	//print "Data: $data\n\n";
	return true;
}

function sess_destroy($sess_id) {
	$obj = new Session();
	$obj -> destroy($sess_id);
	//print "Session destroy called.\n";
	return true;
}

function sess_gc($sess_maxlifetime) {
	$obj = new Session();
	$obj -> gc($sess_maxlifetime);
	//print "Session garbage collection called.\n";
	//print "Sess_maxlifetime: $sess_maxlifetime\n";
	return true;
}

function execpython($str) {
	putenv("LANG=ko_KR.UTF-8");
	setlocale(LC_ALL, 'ko_KR.utf8');

	$fnc = '/usr/bin/python3 '.$str;
	//echo $fnc."<br>";exit();

	//$command = escapeshellcmd($fnc);
	//$result = shell_exec($command);

	//$result = system($fnc);
	//print_r($result);
	//return $result;

	exec($fnc, $out, $status);
	if(isset($out[1])) return $out[1];
	if(isset($out[0])) return $out[0];
	
	return '';
}

function mqtt_publish($host, $topic, $msg, $qos=2, $isEcho=false) {
	global $_lib;

	putenv("LANG=ko_KR.UTF-8");
	setlocale(LC_ALL, 'ko_KR.utf8');

	$fnc = 'mosquitto_pub -h '.$host.' -t '.$topic.' -m \''.$msg.'\' -q '.$qos;
	if($isEcho) {echo $fnc;exit();}
	$loggerObj = new Logger();
	$loggerObj -> type = 3;
	$loggerObj -> location = "/general/mqtt_publish";
	if(!isset($loggerObj -> data)) $loggerObj -> data = new stdClass();
	if(!is_object($loggerObj -> data)) $loggerObj -> data = (object)$loggerObj -> data;
	$loggerObj -> data -> host = $host;
	$loggerObj -> data -> topic = $topic;
	$loggerObj -> data -> msg = jsondecode($msg);

	exec($fnc, $out, $status);
	$loggerObj -> data -> result = $out;
	$loggerObj -> regDate = now();
	$loggerObj -> save();

	if(isset($out[1])) return $out[1];
	//print_r($out);
	if(!empty($out[0])) return $out[0];
}


function get_matching_word($str, $keyword) {
	if(check_blank($str)) return '';
	if(check_blank($keyword)) return '';
	if($str == $keyword) return $keyword;
	//echo "<br>".$str."[".$keyword."]<br>";
	//echo "<br>".$str."<br>";
	if(mb_strpos($str, $keyword) !== false) return $keyword;
	if(mb_strpos($keyword, $str) !== false) return $str;

	$keyword = str_replace("(", "\(", $keyword);
	$keyword = str_replace(")", "\)", $keyword);
	
	$word = preg_replace("#에는$#", "", $keyword);
	if(preg_match('#'.$word.'#', $str)) return $word;
	
	$word = preg_replace("#는$#", "", $keyword);
	if(preg_match('#'.$word.'#', $str)) return $word;
	
	$word = preg_replace("#은$#", "", $keyword);
	if(preg_match('#'.$word.'#', $str)) return $word;
	
	$word = preg_replace("#에$#", "", $keyword);
	if(preg_match('#'.$word.'#', $str)) return $word;

	return '';
}

function get_matching_words($str, $keywords) {
	if($str == $keywords) return $keywords;
	
	$words = array();
	$array = explode(" ", $keywords);
	$temp = str_replace(" ", "", $str);
	
	//단어 중 일치하는 것이 있는지 검사해서 등록
	for($i=0; $i<count($array); $i++) {
		$word = get_matching_word($temp, $array[$i]);
		//echo "[검색어]".$word."<br>";
		if(!check_blank($word)) {
			$temp = mb_str_replace($word, '', $temp);
			array_push($words, $word);
			$array[$i] = '';
		}
	}

	//남은 단어 중 일치하는 것이 있는지 검사해서 등록
	for($i=0; $i<count($array); $i++) {
		$word = get_matching_word($temp, $array[$i]);
		//echo "[검색어]".$word."<br>";
		if(!check_blank($word)) {
			$temp = mb_str_replace($word, '', $temp);
			array_push($words, $word);
		}
	}

	return $words;
}

function get_matching_rate($str, $keywords) {
	if($str == $keywords) return 100;

	$total = mb_strlen(str_replace(" ", "", $keywords));
	$words = get_matching_words($str, $keywords);
	//print_r($matching_str);exit();
	$count = 0;

	foreach($words as $word) {
		$count += mb_strlen($word, 'utf-8');
	}

	return (int)($count / $total * 100);
}

function get_matching_string($str, $keywords) {
	//띄어쓰기로 단어 구분
	$words = explode(' ', $keywords);
	//print_r($words);

	$array = array();

	foreach($words as $word) {
		if($word) {
			$pos = mb_strpos($str, $word);
			array_push($array, $pos);
		}
	}

	sort($array);

	$length = $array[count($array) - 1] - $array[0] + mb_strlen($word);

	//return mb_substr($str, $array[0], );
	//$tmp = mb_substr($str, 0, $array[0]);
	
	return mb_substr($str, $array[0], $length);
}

function mb_str_replace($pattern, $replace, $str, $count=1) {
	$temp = mb_split($pattern, $str);
	$result = '';

	for($i=0; $i<count($temp); $i++) {
		if($i>$count) $result .= $pattern;
		
		$result .= $temp[$i];
	}
	
	return $result;
}

function strToChar($str, $char) {
	$len = mb_strlen($str);
	$char = '';

	for($i=0; $i<$len; $i++) $char .= '_';

	return $char;
}

function makeSignature($timestamp) {
	global $_lib;

	$space = " ";  // 공백
	$newLine = "\n";  // 줄바꿈
	$method = "POST";  // HTTP 메소드
	$uri= "/api/v1/mails";  // 도메인을 제외한 "/" 아래 전체 url (쿼리스트링 포함)
	if(empty($timestamp)) $timestamp = getMillisecond();  // 현재 타임스탬프 (epoch, millisecond)
	$accessKey = $_lib['ncloud']['accessKey'];  // access key id (from portal or sub account)
	$secretKey = $_lib['ncloud']['secretKey'];  // secret key (from portal or sub account)

	$hmac = $method.$space.$uri.$newLine.$timestamp.$newLine.$accessKey;
	$signautue = base64_encode(hash_hmac('sha256', $hmac, $secretKey,true));

	return $signautue;
}

//조사제거
function makeKeyword($word) {
	if(mb_strlen($word, 'utf-8') == 1) return $word;
	if(preg_match("#\,$#", $word)) return preg_replace("#\,$#", "", $word);
	if(preg_match("#\.$#", $word)) return preg_replace("#\.$#", "", $word);
	if(preg_match("#\'$#", $word)) return preg_replace("#\'$#", "", $word);
	if(preg_match("#\"$#", $word)) return preg_replace("#\"$#", "", $word);

	if(trim($word) == '이를') return '';
	if(trim($word) == '위해') return '';
	if(trim($word) == '젊은') return '젊은';
	if(trim($word) == '늙은') return '늙은';
	if(trim($word) == '높은') return '높은';
	if(trim($word) == '낮은') return '낮은';

	if(preg_match("#[0-9가-힣A-Za-z]란$#", $word)) return preg_replace("#란$#", "", $word);
	if(!preg_match("#없는$#", $word)) {
		if(preg_match("#하는$#", $word)) return preg_replace("#하는$#", "", $word);
		elseif(preg_match("#[0-9가-힣A-Za-z]는$#", $word)) return preg_replace("#는$#", "", $word);
	}

	if(preg_match("#[0-9가-힣A-Za-z]으로$#", $word)) return preg_replace("#으로$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]에는$#", $word)) return preg_replace("#에는$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]에서$#", $word)) return preg_replace("#에서$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]이던$#", $word)) return preg_replace("#이던$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]하고$#", $word)) return preg_replace("#하고$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]하여$#", $word)) return preg_replace("#하여$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]이나$#", $word)) return preg_replace("#이나$#", "", $word);

	if(preg_match("#[0-9가-힣A-Za-z]된다$#", $word)) return preg_replace("#된다$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]이다$#", $word)) return preg_replace("#이다$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]한다$#", $word)) return preg_replace("#한다$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]했다$#", $word)) return preg_replace("#했다$#", "", $word);

	if(preg_match("#[0-9가-힣A-Za-z]은$#", $word)) return preg_replace("#은$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]에$#", $word)) return preg_replace("#에$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]을$#", $word)) return preg_replace("#을$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]를$#", $word)) return preg_replace("#를$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]이$#", $word)) return preg_replace("#이$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]가$#", $word)) return preg_replace("#가$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]도$#", $word)) return preg_replace("#도$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]로$#", $word)) return preg_replace("#로$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]만$#", $word)) return preg_replace("#만$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]할$#", $word)) return preg_replace("#할$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]한$#", $word)) return preg_replace("#한$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]의$#", $word)) return preg_replace("#의$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]란$#", $word)) return preg_replace("#란$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]과$#", $word)) return preg_replace("#과$#", "", $word);
	if(preg_match("#[0-9가-힣A-Za-z]와$#", $word)) return preg_replace("#와$#", "", $word);

	return $word;
}

//숫자를 한글로
function intToKr($number){
	$num = array('', '일', '이', '삼', '사', '오', '육', '칠', '팔', '구');
	$unit4 = array('', '만', '억', '조', '경');
	$unit1 = array('', '십', '백', '천');

	$res = array();

	$number = str_replace(',','',$number);
	$split4 = str_split(strrev((string)$number),4);

	for($i=0;$i<count($split4);$i++){
			$temp = array();
			$split1 = str_split((string)$split4[$i], 1);
			for($j=0;$j<count($split1);$j++){
					$u = (int)$split1[$j];
					if($u > 0) $temp[] = $num[$u].$unit1[$j];
			}
			if(count($temp) > 0) $res[] = implode('', array_reverse($temp)).$unit4[$i];
	}

	return implode('', array_reverse($res));
}

//단락을 배열로 반환하는 메쏘드
function getParagraphes($str) {
	$array = [];
	$paragraphes = explode("\r\n", $str);
	
	foreach($paragraphes as $paragraph) {
		if(!check_blank($paragraph)) {
			$obj = new stdClass();
			$obj -> paragraph = $paragraph;
			$obj -> length = mb_strlen($obj -> paragraph, 'UTF-8');
			$obj -> sentences = getSentences($paragraph);

			array_push($array, $obj);
		}
	}

	return $array;
}

//문장을 배열로 반환하는 메쏘드
function getSentences($str) {
	$array = [];
	$sentences = explode("다.", $str);

	foreach($sentences as $sentence) {
		if(!check_blank($sentence)) {
			$obj = new stdClass();
			$obj -> sentence = trim($sentence.'다.');
			$obj -> length = mb_strlen($obj -> sentence, 'UTF-8');
			$obj -> keywords = [];

			array_push($array, $obj);
		}
	}
	return $array;
}

//키워드를 배열로 반환하는 메소드
function getKeywords($str) {
	$url = '/home/klata/python/subject_grading/spliter_client.py --mode 2 --answer "'.$str.'"';
	$keywords = execpython($url);
	$keywords = str_replace("'", '"', $keywords);
	$keywords = jsondecode($keywords);
	
	$array = [];
	foreach($keywords as $keyword) {
		//print_r($keyword);

		$obj = new stdClass();
		$obj -> gcode = $keyword -> gcode;
		$obj -> code = $keyword -> code;
		array_push($array, $obj);
	}
	
	return $array;
}

//문장에서 채점기준 키워드 정보 뽑기
function getKeywordsInfo($model) {
	//모범답안의 키워드 분석
	$temp = explode("/", $model);
	$models = [];
	$start = 0;

	foreach($temp as $tmp) {
		$t = explode("(", $tmp);

		$o = new stdClass();

		if(count($t) == 2) {
			$o -> points = str_replace(")", "", $t[1]);

			$a = explode("[", $t[0]);
			//유사어 지정한것이 있으면....
			if(count($a) == 2) {
				$o -> code = trim($a[0]);
				$o -> name = trim($a[0]);
				$o -> useful_name = explode(',', str_replace(']', '', $a[1]));
				$o -> keywords = [];
				$o -> startPos = -1;
				$o -> endPos = -1;
				$o -> length = -1;
				$o -> rate = 0;
			} else {
				$o -> code = trim($a[0]);
				$o -> name = trim($a[0]);
				$o -> useful_name = [];
				$o -> keywords = [];
				$o -> startPos = -1;
				$o -> endPos = -1;
				$o -> length = -1;
				$o -> rate = 0;
			}
		} else {
			$o -> points = 0;

			$a = explode("[", $t[0]);
			//유사어 지정한것이 있으면....
			if(count($a) == 2) {
				$o -> code = trim($a[0]);
				$o -> name = trim($a[0]);
				$o -> useful_name = explode(',', str_replace(']', '', $a[1]));
				$o -> keywords = [];
				$o -> startPos = -1;
				$o -> endPos = -1;
				$o -> length = -1;
				$o -> rate = 0;
			} else {
				$o -> code = trim($a[0]);
				$o -> name = trim($a[0]);
				$o -> useful_name = [];
				$o -> keywords = [];
				$o -> startPos = -1;
				$o -> endPos = -1;
				$o -> length = -1;
				$o -> rate = 0;
			}
		}

		array_push($models, $o);
	}

	//print_r($models);exit();

	//위치 정보 설정	
	return $models;
}

function setKeywordsInfo($array) {
	$tag = '';
	foreach($array as $obj) {
		if($tag != '') $tag .= '/';
		$tag .= $obj -> code;
		if(!empty($obj -> points)) $tag .= '('.$obj -> points.')';
		if(isset($obj -> useful_name) && count($obj -> useful_name)) $tag .= '['.$obj -> useful_name.']';
	}

	return $tag;
}

//존재하는 키워드 인지
function isKeyword($keywords, $obj) {
	foreach($keywords as $o) {
		//echo "<br>[".$o -> code."][".$o -> name."][".$o -> startPos."][".$o -> endPos."]<br>";
		if($o -> name == $obj -> name && $obj -> startPos < 0 && $obj -> endPos < 0) return true;
		elseif($o -> name == $obj -> name && $o -> startPos <= $obj -> startPos && $obj -> startPos <= $o -> endPos) return true;
		elseif($o -> name == $obj -> name && $o -> startPos <= $obj -> endPos && $obj -> endPos <= $o -> endPos) return true;
	}

	return false;
}

//키워드 증폭하기
function amplifyKeyword($keyword) {
	$o = new stdClass();
	$o -> code = trim($keyword -> code);
	$o -> name = trim($keyword -> name);
	$o -> keywords = [];
	$o -> startPos = -1;
	$o -> endPos = -1;
	$o -> length = -1;
	$o -> rate = 0;

	//현재 키워드
	$a = new stdClass();
	$a -> code = trim($keyword -> code);
	$a -> name = trim($keyword -> name);
	$a -> keywords = [];
	$a -> startPos = -1;
	$a -> endPos = -1;
	$a -> length = -1;
	$a -> rate = 0;
	
	array_push($o -> keywords, $a);

	//공백으로 구분한 키워드
	$a = new stdClass();
	$a -> code = trim($keyword -> code);
	$a -> name = trim(str_replace(" ", "", $keyword -> name));
	$a -> keywords = [];
	$a -> startPos = -1;
	$a -> endPos = -1;
	$a -> length = -1;
	$a -> rate = 0;
	
	if(!isKeyword($o -> keywords, $a)) array_push($o -> keywords, $a);

	foreach($keyword -> useful_name as $k) {
		$a = new stdClass();
		$a -> code = trim($keyword -> code);
		$a -> name = trim($k);
		$a -> keywords = [];
		$a -> startPos = -1;
		$a -> endPos = -1;
		$a -> length = -1;
		$a -> rate = 0;

		if(!isKeyword($o -> keywords, $a)) array_push($o -> keywords, $a);
	}

	//설정된 키워드 뿐만 아니라 유의어까지 모두 가져오기
	$words = getWords(trim($keyword -> name));
	foreach($words as $word) {
		$a = new stdClass();
		$a -> code = trim($keyword -> code);
		$a -> name = trim($word);
		$a -> keywords = [];
		$a -> startPos = -1;
		$a -> endPos = -1;
		$a -> length = -1;
		$a -> rate = 0;

		if(!isKeyword($o -> keywords, $a)) array_push($o -> keywords, $a);
	}

	//print_r($o);

	return $o;
}

//문장에서 동일한 키워드 찾기
function findSameKeyword($sentence, $obj, $isDebug=false) {
	//if($obj -> code == "핀테크") $isDebug = true;

	if($isDebug) echo "일치키워드 ".$obj -> code." 찾기 : ".$sentence."<br>";
	$temp = explode($obj -> name, $sentence);
	if($isDebug) { print_r($temp);echo "<br>"; }

	//일치하는 단어가 없고 유의어 검색도 아니면 종료
	if(count($temp) <= 1) return false;

	$start = 0;

	$obj -> keywords = [];
	$obj -> length = mb_strlen($obj -> code, 'UTF-8');
	
	for($i=1; $i<count($temp); $i++) {
		$pos = mb_strpos($sentence, $obj -> name, $start, 'utf-8');
		$length = mb_strlen($obj -> name, 'utf-8');

		$o = new stdClass();
		$o -> name = $obj -> name;
		$o -> startPos = $pos;
		$o -> endPos = $pos + $length;
		$o -> length = $length;
		$o -> rate = 100;

		array_push($obj -> keywords, $o);
		$start = $o -> endPos;
	}

	if(count($obj -> keywords) == 1) {
		$obj -> startPos = $obj -> keywords[0] -> startPos;
		$obj -> endPos = $obj -> keywords[0] -> endPos;
		$obj -> length = $obj -> keywords[0] -> length;
		$obj -> rate = $obj -> keywords[0] -> rate;
	} else {
		foreach($obj -> keywords as $o) {
			if($obj -> rate < $o -> rate) {
				$obj -> startPos = $o -> startPos;
				$obj -> endPos = $o -> endPos;
				$obj -> length = $o -> length;
				$obj -> rate = $o -> rate;
			}
		}
	}

	if($isDebug) { print_r($obj);echo "<br>"; }

	return $obj;
}

//문장에서 유사한 키워드 찾기
function findSimilarKeyword($sentence, $obj) {
	//echo "<br>";
	//echo "검색어:".$obj -> code."<br>";
	$words = explode("*", trim($obj -> name));
	//print_r($words);
	//echo "<br>";
	//print_r($obj);
	
	$obj -> length = mb_strlen($obj -> code, 'UTF-8');
	if(!isset($obj -> keywords)) $obj -> keywords = [];

	$fWord = '';
	//존재하는 단어의 첫 위치 잡기
	for($i=0; $i<count($words); $i++) {
		if(!empty($words[$i])) {
			$fWord = $words[$i];
			break;
		}
	}

	//echo "검색 첫글자 : ".$fWord."<br>";

	$temp = explode($fWord, $sentence);
	$start = 0;

	//echo "문장내 검색 첫글차 일치도 : ".(count($temp) - 1)."<br>";
	//print_r($temp);echo "<br>";
	if(count($temp) <= 1) return false;

	for($i=0; $i<count($temp) - 1; $i++) {
		$tmp = $temp[$i];
		$pos = -1;
		$keyword = '';

		//존재하는 단어의 첫 위치 잡기
		for($j=0; $j<count($words); $j++) {
			$word = $words[$j];
			//echo $i.":".$word."(".$keyword." : ".$pos.",".$start.")<br>";

			//유의미한 첫단어에서 위치값 구함
			if($pos < 0 && !empty($word)) {
				$pos = mb_strpos($sentence, $word, $start, 'utf-8') - $j;
				$len = mb_strlen($word, 'utf-8') + $j;
				$start = $pos + $len;
				$keyword = mb_substr($sentence, $pos, $len);
			//유의미한 단어 이후에 *이 나오면
			} elseif($pos >= 0 && empty($word)) {
				$char = mb_substr($sentence, $start, 1, 'utf-8');
				$start ++;
				if(empty($char)) return false;
				else $keyword .= $char;
			//*이 아니라 글자가 나오면
			} elseif(!empty($word)) {
				$len = mb_strlen($word, 'utf-8');
				$char1 = mb_substr($sentence, $start, 1, 'utf-8');
				$char2 = mb_substr($sentence, $start + 1, $len, 'utf-8');
				if($word != $char2) return false;
				//echo "일치단어 : ".$char1."/".$char2."<br>";
				$char = $char1.$char2;
				$keyword .= $char;
				$start += $len;
			}
		}

		$length = mb_strlen($keyword, 'utf-8');
		
		//앞 위에 공백이 있는 경우, 유사어 검색은 무시
		if($length == mb_strlen(trim($keyword), 'utf-8')) {
			$o = new stdClass();
			$o -> name = $keyword;
			$o -> startPos = $pos;
			$o -> endPos = $o -> startPos + $length;
			$o -> length = $length;
			$o -> rate = getKeywordRate($obj -> code, $o -> name);

			//echo "<br>키워드 찾기: ".$obj -> code." - ".$o -> name."(".$o -> startPos."/".$o -> rate.")<br>";
			//print_r($o);
			array_push($obj -> keywords, $o);
		}
	}

	if(count($obj -> keywords) == 1) {
		$obj -> name = $obj -> keywords[0] -> name;
		$obj -> startPos = $obj -> keywords[0] -> startPos;
		$obj -> endPos = $obj -> keywords[0] -> endPos;
		$obj -> length = $obj -> keywords[0] -> length;
		$obj -> rate = $obj -> keywords[0] -> rate;
	}
	
	//print_r($obj);echo "<br>";
	
	return $obj;
}

//문장에서 일치하는 단어 찾기
function findKeyword($sentence, $obj, $start=0) {
	//키워드 증폭
	$obj = amplifyKeyword($obj);
	
	/*
	echo '<div>[키워드증폭]'.$obj -> code.'</div>';
	echo '<table class="list">';
	echo '<tbody>';
	foreach($obj -> keywords as $o) {
		echo '<tr>';
		echo '<td style="border:1px solid #000">'.$o -> code.'</td>';
		echo '<td style="border:1px solid #000">'.$o -> name.'</td>';
		echo '<td style="border:1px solid #000">'.$o -> startPos.'</td>';
		echo '<td style="border:1px solid #000">'.$o -> endPos.'</td>';
		echo '<td style="border:1px solid #000">'.$o -> rate.'</td>';
		echo '<td style="border:1px solid #000">'.count($o -> keywords).'</td>';
		echo '<tr>';
	}
	echo '</tbody>';
	echo '</table>';
	*/
	
	$posArray = [];
	$rateArray = [];
	
	//키워드 동일한 위치의 키워드를 일치율이 높은 것으로 채택
	foreach($obj -> keywords as $w) {
		//print_r($w);echo "<br>";
		//echo "[키워드확인]".$w -> name."<br>";
			
		if(preg_match('/\*/', $w -> name)) $o = findSimilarKeyword($sentence, $w);
		else $o = findSameKeyword($sentence, $w);
			
		if($o !== false) {
			//echo "[키워드탐색]".$w -> name." - ".$o -> startPos." / ".$o -> endPos." / ".$o -> rate."<br>";
			//print_r($o);echo "<br>";

			if(isset($posArray[$o -> startPos])) {
				$a = $posArray[$o -> startPos];
				if($a -> rate < $o -> rate) $posArray[$o -> startPos] = $o;
			} else $posArray[$o -> startPos] = $o;
		}
	}

	ksort($posArray);

	if(count($posArray) <= 0) return $obj;

	$keywords = [];
	//같이 검색된 키워드 등록
	foreach($posArray as $key => $value) {
		//echo "[남은키워드 설정]";
		//print_r($value);
		//echo "<br>";
		array_push($keywords, $value);
	}
	
	//정렬 순서로 키워드 목록 교체
	$obj -> keywords = $keywords;

	//echo "<br>[남은 결과]";
	//print_r($obj);
	//echo "<br>";

	$obj = choiceKeyword($start, $obj);
	if($obj -> endPos >= 0) $start = $obj -> endPos;
	
	//echo "<br>[최종 결과]";
	//print_r($obj);
	//echo "<br>";
	//echo "<br>";
	
	return $obj;
}

//이전단어 시작위치
function getStartpos($keyword) {
	return $keyword -> startPos + mb_strlen($keyword -> name, 'utf-8');
}

//이전단어 시작위치
function getEndpos($keyword) {
	return $keyword -> startPos;
}

//단어와의 거리
function getWordgap($keyword1, $keyword2) {
	return abs(getEndpos($keyword2) - getStartpos($keyword1));
}

//기준점과 가까운 것을 채택
function choiceKeyword($start, $obj, $isEcho=false) {
	$array = [];

	foreach($obj -> keywords as $o) {
		if(!isset($array[$o -> rate])) $array[$o -> rate] = [];
		if($o -> rate) array_push($array[$o -> rate], $o);
	}

	krsort($array);

	$gap = 999999999;
	$rate = 0;

	if($isEcho) {
		echo '<div>'.$obj -> code.' 선택 결정전('.$start.')</div>';
		echo '<table class="list">';
		echo '<tbody>';
	}

	foreach($array as $key => $list) {
		for($i=0; $i<count($list); $i++) {
			$o = $list[$i];

			if(isset($o -> keywords) && count($o -> keywords)) $o = choiceKeyword($start, $o);
			
			if($isEcho) {
				echo '<tr>';
				echo '<td style="border:1px solid #000">'.$gap.'/'.abs($start - $o -> startPos).'</td>';
				echo '<td style="border:1px solid #000">'.$rate.'/'.$key.'</td>';
				echo '<td style="border:1px solid #000">'.(isset($o -> code) ? $o -> code : '').'</td>';
				echo '<td style="border:1px solid #000">'.$o -> name.'</td>';
				echo '<td style="border:1px solid #000">'.$o -> startPos.'</td>';
				echo '<td style="border:1px solid #000">'.$o -> endPos.'</td>';
				echo '<td style="border:1px solid #000">'.$o -> rate.'</td>';
				echo '<td style="border:1px solid #000">'.(isset($o -> keywords) ? count($o -> keywords) : '0').'</td>';
				echo '<tr>';
			}

			if($gap > abs($start - $o -> startPos) && $rate <= $key)  {
				$gap = abs($start - $o -> startPos);
				$rate = $key;
				$obj -> name = $o -> name;
				$obj -> startPos = $o -> startPos;
				$obj -> endPos = $o -> endPos;
				$obj -> rate = $o -> rate;
			}
		}
	}

	if($isEcho) {
		echo '</tbody>';
		echo '</table>';
	}

	return $obj;
}

//문장에서 키워드 결정
function decideKeywordPosition2($sentence, $models, $isDebug=false) {
	//echo $sentence."<br>";
	$start = 0;
	for($i=0; $i<count($models); $i++) {
		$models[$i] = choiceKeyword($start, $models[$i]);

		//echo $start.":".(abs($start - $models[$i] -> startPos))." ".$models[$i] -> name."<br>";

		if(abs($start - $models[$i] -> startPos) > 50 && $models[$i] -> rate < 100) {
			$models[$i]  -> startPos = -1;
			$models[$i]  -> endPos = -1;
			$models[$i]  -> rate = 0;
		} else {
			//순차적으로 안나오는지 비교
			if($i != 0 && $models[$i - 1] -> startPos >= $models[$i] -> startPos) {

				//echo $start.":".count($models).":".$i." - ".$models[$i] -> name."(".$models[$i] -> startPos.")";
				//if(count($models) > $i + 1) echo " - ".$models[$i+1] -> name."(".$models[$i+1] -> startPos.")<br>";
				
				if($models[$i] -> rate < 100) {
					//echo count($models).":".$i." - ".$models[$i] -> name."(".$models[$i] -> rate.")<br>";
					$models[$i]  -> startPos = -1;
					$models[$i]  -> endPos = -1;
					$models[$i]  -> rate = 0;
				} elseif($models[$i - 1] -> rate < 100) {
					$models[$i - 1]  -> startPos = -1;
					$models[$i - 1]  -> endPos = -1;
					$models[$i - 1]  -> rate = 0;
				}
				/*
				*/

				//echo $i." > 1 && ".count($models)." > ".($i + 1)." && ".$models[$i - 1] -> startPos." >= ".$models[$i + 1] -> startPos."<br>";
				//이전 것과 다음 것의 순서가 맞는 경우 현재 것을 초기화
				/*
				if(count($models) > $i + 1 && $models[$i - 1] -> startPos < $models[$i + 1] -> startPos) {
					$models[$i]  -> startPos = -1;
					$models[$i]  -> endPos = -1;
					$models[$i]  -> rate = 0;
				} elseif($models[$i - 1] -> startPos >= $models[$i] -> startPos) {
					$models[$i - 1]  -> startPos = -1;
					$models[$i - 1]  -> endPos = -1;
					$models[$i - 1]  -> rate = 0;
				//전전 것과 전의 것 순서가 맞으면 PASS
				} elseif($i > 1 && $models[$i - 2] -> startPos == -1) {
					$models[$i - 1]  -> startPos = -1;
					$models[$i - 1]  -> endPos = -1;
					$models[$i - 1]  -> rate = 0;
				//전전 것과 전의 것 순서가 맞으면 PASS
				} elseif($i > 1 && $models[$i - 2] -> startPos < $models[$i -1] -> startPos) {
				//다음 것도 안나오는 경우 초기화
				} elseif($i > 1 && count($models) > $i + 1 && $models[$i - 1] -> startPos >= $models[$i + 1] -> startPos) {
					$models[$i - 1]  -> startPos = -1;
					$models[$i - 1]  -> endPos = -1;
					$models[$i - 1]  -> rate = 0;
				}
				*/
			}

			if($models[$i] -> endPos >= 0) $start = $models[$i] -> endPos;
		}
	}

	return $models;
}

//문장에서 키워드 결정
function decideKeywordPosition($sentence, $models, $isDebug=false) {
	$keywords = [];
	if($isDebug) print_r($models);

	//echo "[위치결정]";
	//echo "<br>";

	//주변 키워드와의 거리를 구하여 판단
	for($i=0; $i<count($models); $i++) {
		$model = $models[$i];
		if($isDebug) {
			print_r($models[$i]);echo "<br>";
		}

		//print_r($models[$i]);

		//일치하는 키워드가 하나도 없는 경우 그대로 반환
		if(count($models[$i] -> keywords) <= 0) array_push($keywords, $models[$i]);
		else if($models[$i] -> startPos >= 0) array_push($keywords, $models[$i]);
		else {
			//위치가 미정인 경우
			if($models[$i] -> startPos < 0) {
				//echo "[".$i."]";print_r($models[$i]);echo "<br>";//exit();
				//if($i - 1 >= 0) echo "[이전키워드]".$models[$i - 1] -> keyword;
				//echo "[현재키워드]".$models[$i] -> keyword."(".count($models[$i] -> keywords).")";
				//if($i + 1 < count($models)) echo "[다음키워드]".$models[$i + 1] -> keyword;
				//echo "<br>";

				//2개 이상의 위치가 있어 미정인 모델만
				if(count($models[$i] -> keywords) > 1) {
					//첫키워드
					if($i == 0) {
						//다음 키워드가 없다면,,
						if($i + 1 >= count($models)) {

						//다음 키워드가 정해졌다면
						} else if($models[$i + 1] -> startPos >= 0) {
							$endpos = getEndpos($models[$i + 1]);
							
							for($j=0; $j<count($models[$i] -> keywords); $j++) {
								$currentpos = getEndpos($models[$i] -> keywords[$j]);

								//echo $models[$i] -> keywords[$j] -> keyword." = currentpos:".$currentpos." < endpos:".$endpos." - gap:".getWordgap($models[$i] -> keywords[$j], $models[$i + 1])."<br>";

								if($currentpos <= $endpos && getWordgap($models[$i] -> keywords[$j], $models[$i + 1]) <= 4) {
									$models[$i] -> name = $models[$i] -> keywords[$j] -> name;
									$models[$i] -> startPos = $models[$i] -> keywords[$j] -> startPos;
									$models[$i] -> endPos = $models[$i] -> keywords[$j] -> endPos;
									$models[$i] -> length = $models[$i] -> keywords[$j] -> length;
									$models[$i] -> rate = $models[$i] -> keywords[$j] -> rate;
									//print_r($models[$i]);echo "<br>";
									break;
								}
							}
						// 다음 키워드가 안정해졌다면
						} else {
							for($j=0; $j<count($models[$i + 1] -> keywords); $j++) {
								$endpos = getEndpos($models[$i + 1] -> keywords[$j]);

								for($k=0; $k<count($models[$i] -> keywords); $k++) {
									$currentpos = getEndpos($models[$i] -> keywords[$k]);

									//echo $models[$i] -> keywords[$k] -> keyword." = currentpos:".$currentpos." < endpos:".$endpos." - gap:".getWordgap($models[$i] -> keywords[$k], $models[$i + 1] -> keywords[$j])."<br>";

									if($currentpos <= $endpos && getWordgap($models[$i] -> keywords[$k], $models[$i + 1] -> keywords[$j]) <= 4) {
										$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
										$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
										$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
										$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
										$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
										//echo "첫단어의 다음키워드 안정해진 상태 - ";print_r($models[$i]);echo "<br>";
										break;
									}
								}
							}
						}
					//나머지 키워드
					} else {
						//echo "찾는키워드 : ".$models[$i] -> name." / 이전키워드 ".$models[$i - 1] -> name."(".$models[$i - 1] -> pos.")<br>";//exit();
						
						//이전 키워드가 정해졌다면
						if($models[$i - 1] -> startPos >= 0) {
							$startpos = getStartpos($models[$i - 1]);
							//echo "시작키워드 : ".$startpos."<br>";

							//다음 키워드가 마지막 키워드라면
							if($i == count($models) - 1) {
								for($j=0; $j<count($models[$i] -> keywords); $j++) {
									$currentpos = getEndpos($models[$i] -> keywords[$j]);

									//echo $models[$i] -> keywords[$j] -> name." = currentpos:".$currentpos." - endpos:".$endpos." - gap:".getWordgap($models[$i - 1], $models[$i] -> keywords[$j])."<br>";

									if($startpos <= $currentpos && getWordgap($models[$i - 1], $models[$i] -> keywords[$j]) <= 4) {
										$models[$i] -> name = $models[$i] -> keywords[$j] -> name;
										$models[$i] -> startPos = $models[$i] -> keywords[$j] -> startPos;
										$models[$i] -> endPos = $models[$i] -> keywords[$j] -> endPos;
										$models[$i] -> length = $models[$i] -> keywords[$j] -> length;
										$models[$i] -> rate = $models[$i] -> keywords[$j] -> rate;
										break;
									}
								}
							//다음 키워드가 정해졌다면
							} else if($models[$i + 1] -> startPos >= 0) {
								$endpos = getEndpos($models[$i + 1]);

								for($j=0; $j<count($models[$i] -> keywords); $j++) {
									$currentpos = getEndpos($models[$i] -> keywords[$j]);

									//echo $models[$i] -> keywords[$j] -> name." = currentpos:".$currentpos." - endpos:".$endpos." - 이전 gap:".getWordgap($models[$i - 1], $models[$i] -> keywords[$j])." / 이후 gap:".getWordgap($models[$i] -> keywords[$j], $models[$i + 1])."<br>";

									//3개가 일치하면 채택
									if($startpos <= $currentpos && $currentpos <= $endpos) {
										$models[$i] -> name = $models[$i] -> keywords[$j] -> name;
										$models[$i] -> startPos = $models[$i] -> keywords[$j] -> startPos;
										$models[$i] -> endPos = $models[$i] -> keywords[$j] -> endPos;
										$models[$i] -> length = $models[$i] -> keywords[$j] -> length;
										$models[$i] -> rate = $models[$i] -> keywords[$j] -> rate;
										break;
									// 앞에 것과 본인 일치하면 채택
									} else if($startpos <= $currentpos && getWordgap($models[$i - 1], $models[$i] -> keywords[$j]) <= 4) {
										$models[$i] -> name = $models[$i] -> keywords[$j] -> name;
										$models[$i] -> startPos = $models[$i] -> keywords[$j] -> startPos;
										$models[$i] -> endPos = $models[$i] -> keywords[$j] -> endPos;
										$models[$i] -> length = $models[$i] -> keywords[$j] -> length;
										$models[$i] -> rate = $models[$i] -> keywords[$j] -> rate;
										break;
									//뒤에 것과 본인 일치하면 채택
									} else if($currentpos <= $endpos && getWordgap($models[$i] -> keywords[$j], $models[$i + 1]) <= 4) {
										$models[$i] -> name = $models[$i] -> keywords[$j] -> name;
										$models[$i] -> startPos = $models[$i] -> keywords[$j] -> startPos;
										$models[$i] -> endPos = $models[$i] -> keywords[$j] -> endPos;
										$models[$i] -> length = $models[$i] -> keywords[$j] -> length;
										$models[$i] -> rate = $models[$i] -> keywords[$j] -> rate;
										break;
									}
								}
							// 다음 키워드가 안정해졌다면
							} else {
								for($j=0; $j<count($models[$i + 1] -> keywords); $j++) {
									$endpos = getEndpos($models[$i + 1] -> keywords[$j]);

									for($k=0; $k<count($models[$i] -> keywords); $k++) {
										$currentpos = getEndpos($models[$i] -> keywords[$k]);

										//echo $models[$i - 1] -> keyword."(".$startpos.")";
										//echo $models[$i] -> keywords[$k] -> keyword."(".$currentpos.")";
										//echo $models[$i + 1] -> keyword."(".$currentpos.")<br>";
										//echo "이전 gap:".getWordgap($models[$i - 1], $models[$i] -> keywords[$k])."<br>";
										//echo "이후 gap:".getWordgap($models[$i] -> keywords[$k], $models[$i + 1])."<br>";

										if($startpos <= $currentpos && $currentpos <= $endpos) {
											$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
											$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
											$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
											$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
											$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
											break;
										} else if($startpos <= $currentpos && getWordgap($models[$i - 1], $models[$i] -> keywords[$k]) <= 4) {
											$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
											$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
											$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
											$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
											$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
											break;
										} else if($currentpos <= $endpos && getWordgap($models[$i] -> keywords[$k], $models[$i + 1]) <= 4) {
											$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
											$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
											$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
											$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
											$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
											break;
										}
									}
								}
							}
						//이전 키워드가 안정해졌다면
						} else {
							for($j=0; $j<count($models[$i - 1] -> keywords); $j++) {
								$startpos = getStartpos($models[$i - 1] -> keywords[$j]);

								//다음 키워드가 마지막 키워드라면
								if($i == count($models) - 1) {
									for($k=0; $k<count($models[$i] -> keywords); $k++) {
										$currentpos = getEndpos($models[$i] -> keywords[$k]);

										if($startpos <= $currentpos && getWordgap($models[$i - 1] -> keywords[$j], $models[$i] -> keywords[$k]) <= 4) {
											$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
											$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
											$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
											$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
											$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
											break;
										}
									}
								//다음 키워드가 정해졌다면
								} else if($models[$i + 1] -> startPos >= 0) {
									$endpos = getEndpos($models[$i + 1]);

									for($k=0; $k<count($models[$i] -> keywords); $k++) {
										$currentpos = getEndpos($models[$i] -> keywords[$k]);

										if($startpos <= $currentpos && $currentpos < $endpos) {
											$models[$i] -> name = $models[$i] -> keywords[$k] -> name;
											$models[$i] -> startPos = $models[$i] -> keywords[$k] -> startPos;
											$models[$i] -> endPos = $models[$i] -> keywords[$k] -> endPos;
											$models[$i] -> length = $models[$i] -> keywords[$k] -> length;
											$models[$i] -> rate = $models[$i] -> keywords[$k] -> rate;
											break;
										}
									}
								// 다음 키워드가 안정해졌다면
								} else {
									for($k=0; $k<count($models[$i + 1] -> keywords); $k++) {
										$endpos = getEndpos($models[$i + 1] -> keywords[$k]);

										for($l=0; $l<count($models[$i] -> keywords); $l++) {
											$currentpos = getEndpos($models[$i] -> keywords[$l]);

											if($startpos <= $currentpos && $currentpos <= $endpos) {
												$models[$i] -> name = $models[$i] -> keywords[$l] -> name;
												$models[$i] -> startPos = $models[$i] -> keywords[$l] -> startPos;
												$models[$i] -> endPos = $models[$i] -> keywords[$l] -> endPos;
												$models[$i] -> length = $models[$i] -> keywords[$l] -> length;
												$models[$i] -> rate = $models[$i] -> keywords[$l] -> rate;
												break;
											} else if($startpos <= $currentpos && getWordgap($models[$i - 1] -> keywords[$j], $models[$i] -> keywords[$l]) <= 4) {
												$models[$i] -> name = $models[$i] -> keywords[$l] -> name;
												$models[$i] -> startPos = $models[$i] -> keywords[$l] -> startPos;
												$models[$i] -> endPos = $models[$i] -> keywords[$l] -> endPos;
												$models[$i] -> length = $models[$i] -> keywords[$l] -> length;
												$models[$i] -> rate = $models[$i] -> keywords[$l] -> rate;
												break;
											} else if($currentpos <= $endpos && getWordgap($models[$i] -> keywords[$l], $models[$i + 1] -> keywords[$k]) <= 4) {
												$models[$i] -> name = $models[$i] -> keywords[$l] -> name;
												$models[$i] -> startPos = $models[$i] -> keywords[$l] -> startPos;
												$models[$i] -> endPos = $models[$i] -> keywords[$l] -> endPos;
												$models[$i] -> length = $models[$i] -> keywords[$l] -> length;
												$models[$i] -> rate = $models[$i] -> keywords[$l] -> rate;
												break;
											}
										}
									}
								}
							}
						}
					}
				}

				array_push($keywords, $models[$i]);
				if($isDebug) echo $models[$i] -> name.":".$models[$i] -> startPos."<br>";
			} else {
				array_push($keywords, $models[$i]);
			}
		}
	}

	return $keywords;
}

//검색할 다양한 키워드 만들기
function getWords($keyword) {
	$array = [];
	array_push($array, $keyword);

	$length = mb_strlen($keyword, 'utf-8');

	if($length <= 2) return $array;

	for($i=0; $i<(int)($length/2); $i++) {
		for($j=0; $j<$length; $j++) {
			$word = getWordRegexp($keyword, $j, $i + 1);
			array_push($array, $word);
		}
	}

	return $array;
}

//문자열에서 해당 문자열 제거
function getWordRegexp($str, $index, $length) {
	$len = mb_strlen($str, 'utf-8');
	//echo $str."(".$index."-".$length.")<br>";
	$keyword = '';

	if($len < $index + $length) $start = $index + $length - $len;
	else $start = 0;

	if($start) {
		for($i=0; $i<$start; $i++) $keyword .= '*';
		//$keyword .= '(.*?){'.$start.'}';
	}

	if($index > 0) $keyword .= mb_substr($str, $start, $index);
	
	for($i=0; $i<($length - $start); $i++) $keyword .= '*';
	$keyword .= mb_substr($str, $index + $length);

	//$keyword .= '(.*?){'.($length - $start).'}'.mb_substr($str, $index + $length);
	//echo $keyword."<br>";

	return $keyword;
}

//밀집도 구하기
function getDensity($sentence, $keywords) {
	$start = 999999999;
	$stop = 0;
	$length = 0;

	foreach($keywords as $o) {
		if($o -> startPos < $start) $start = $o -> startPos;
		if($o -> endPos > $stop) $stop = $o -> endPos;
	}

	if(count($keywords) <= 0) return 0;
	
	return (int)(($stop - $start) / count($keywords) * 100) / 100;
}

//문장을 결과에 따라 이쁘게 변환하는 메소드
function displayKeywordsStr($keywords, $str) {
	$str = restoreQuotes($str);
	//echo $str."<br>";
	$array = [];
	$keywordEa = 0;
	foreach($keywords as $keyword) {
		//echo $keyword -> pos."<br>";
		if($keyword -> startPos >= 0) $keywordEa++;
		$array[$keyword -> startPos] = $keyword;
	}

	krsort($array);
	//print_r($array);exit();
	
	$idx = 0;
	foreach($array as $keyword) {
		$temp  = explode(trim($keyword -> name), $str);
		//print_r($temp);echo"<br>";
		$tmp = '';
		$startPos = 0;
		for($i=0; $i<count($temp); $i++) {
			$o = $temp[$i];

			if($i != 0) $tmp .= '<b>'.$keyword -> name.'<sup>'.($keywordEa - $idx).'</sup></b>';
			//if($keyword -> startPos > $startPos) $tmp .= '<b>'.$keyword -> name.'</b>';
			//else if($keyword -> startPos > 0) $tmp .= '<b>'.$keyword -> name.'</b>';
			$tmp .= $o;
			$length = mb_strlen($o, 'utf-8');
		}

		$str = $tmp;
		$idx++;
	}

	return $str;
}

function findKeywordProcess($json = '', $isEcho=false) {
	global $_lib;
	
	//echo phpinfo();exit();
	if(is_string($json)) $json = jsondecode($json);
	if(empty($json)) $json = new stdClass();
	if(is_array($json)) $json = (object) $json;
	//print_r($json);exit();

	$results = [];
	$keywordposArray = [];
	$array = [];
	
	if($isEcho) {
		echo "<br><br><br>";
		echo "[검색할 문장]".$json -> sentence."<br>";
		echo '<div>키워드 모델 분석</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($json -> keywords as $obj) {
			//print_r($obj);
			echo '<tr>';
			echo '<td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td>';
			echo '<tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo "<br>";
	}

	//키워드를 모범답안에서 찾기
	foreach($json -> keywords as $keyword) {
		$keyword = findKeyword($json -> sentence, $keyword);
		array_push($results, $keyword);
	}
	
	if($isEcho) {
		echo '<div>위치 결정전</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($results as $obj) {
			//print_r($obj);
			echo '<tr><td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td><tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	$results = decideKeywordPosition($json -> sentence, $results);

	if($isEcho) {
		echo '<div>위치 결정후</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($results as $obj) {
			//print_r($obj);
			echo '<tr><td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td><tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	return $results;

}

function findKeyword2Process($json = '', $isEcho=false) {
	global $_lib;
	
	//echo phpinfo();exit();
	if(is_string($json)) $json = jsondecode($json);
	if(empty($json)) $json = new stdClass();
	if(is_array($json)) $json = (object) $json;
	//print_r($json);exit();

	$results = [];
	$keywordposArray = [];
	$array = [];
	
	if($isEcho) {
		echo "<br><br><br>";
		echo "[검색할 문장]".$json -> sentence."<br>";
		echo '<div>키워드 모델 분석</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($json -> keywords as $obj) {
			//print_r($obj);
			echo '<tr>';
			echo '<td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td>';
			echo '<tr>';
		}
		echo '</tbody>';
		echo '</table>';
		echo "<br>";
	}

	//키워드를 모범답안에서 찾기
	$start = 0;
	foreach($json -> keywords as $keyword) {
		$keyword = findKeyword($json -> sentence, $keyword, $start);
		array_push($results, $keyword);
		if($keyword -> endPos >= 0) $start = $keyword -> endPos;
	}
	
	if($isEcho) {
		echo '<div>위치 결정전</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($results as $obj) {
			//print_r($obj);
			echo '<tr><td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td><tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	$results = decideKeywordPosition2($json -> sentence, $results);

	if($isEcho) {
		echo '<div>위치 결정후</div>';
		echo '<table class="list">';
		echo '<tbody>';
		foreach($results as $obj) {
			//print_r($obj);
			echo '<tr><td style="border:1px solid #000">'.$obj -> code.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> name.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> startPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> endPos.'</td>';
			echo '<td style="border:1px solid #000">'.$obj -> rate.'</td>';
			echo '<td style="border:1px solid #000">'.count($obj -> keywords).'</td><tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	return $results;

}

// 같은 키워드가 배열 안에 있는지 검사
/*
function isKeyword($array, $keyword) {
	foreach($array as $obj) {
		if($obj -> pos == $keyword -> pos) return true;
	}

	return false;
}
*/

// 같은 키워드는 교체여부를 결하고 배열에 넣는 메소드
function keyword_push($array, $keyword) {
	$results = $array;
	//print_r($keyword);
	
	//echo "-------------시작--------------------<br>[이전]";
	//foreach($array as $o) echo "[CODE]".$o -> code."[KEYWORD]".$o -> keyword."(".$o -> pos."/".$o -> rate."),";
	//echo "<br><br>";
	//print_r($array);echo(count($array));echo "<br><br><br>";
	
	$isExists = false;
	for($i=0; $i<count($results); $i++) {
		$obj = $results[$i];

		//같은 위치인 경우, 일치율이 높은 쪽을 채택
		if((!($obj -> pos <= $keyword -> pos + mb_strlen($keyword -> keyword, 'utf-8')) || !($obj -> pos  + mb_strlen($obj -> keyword, 'utf-8') <= $keyword -> pos)) && $keyword -> rate > $obj -> rate) {
			//echo "[위치]".$obj -> pos." <= ".$keyword -> pos." + ".mb_strlen($keyword -> keyword, 'utf-8')." || ".$obj -> pos."+".mb_strlen($obj -> keyword, 'utf-8')." > ".$keyword -> pos." 비교<br>";	
			//echo "키워드 ".$keyword -> keyword." ".$obj -> pos.":".$keyword -> pos." / ".$keyword -> rate.":".$obj -> rate." 교체<br>";
			$results[$i] = $keyword;
			$isExists = true;
		} elseif(!($obj -> pos <= $keyword -> pos + mb_strlen($keyword -> keyword, 'utf-8')) || !($obj -> pos  + mb_strlen($obj -> keyword, 'utf-8') <= $keyword -> pos)) {
			//echo "키워드 ".$keyword -> keyword." ".$keyword -> pos.":".$obj -> pos." 등록 안함<br>";
			$isExists = true;
		}
	}

	if(!$isExists) {
		array_push($results, $keyword);
		//echo "[CODE]".$keyword -> code."[KEYWORD]".$keyword -> keyword."(".$keyword -> pos."/".$keyword -> rate.") 키워드 추가<br>";
	}

	$results = sortKeyword($results);
	
	//echo "<br>[결과]";
	//foreach($results as $o) echo $o -> keyword."(".$o -> pos."/".$o -> rate."),";
	//echo "<br>-------------종료--------------------<br><br><br>";
	
	return $results;
}

//배열로 키워드 목록 가져오기
function getArrayKeyword($keywords, $label='name') {
	$array = [];
			
	foreach($keywords as $o) {
		//array_push($array, $o -> keyword."(".$o -> pos.")");
		array_push($array, $o -> $label);
	}

	return $array;
}

//위치값에 따라 재정렬
function sortKeyword($keywords, $desc="desc") {
	$array = [];

	foreach($keywords as $o) {
		$pos = $o -> startPos;
		$array[$pos] = $o;
	}
	
	if($desc == "desc") krsort($array);
	else ksort($array);

	$keywords = [];
	
	foreach($array as $key => $o) {
		//echo $o -> name."(".$o -> startPos.")<br>";
		array_push($keywords, $o);
		if($o -> length <= 0) $o -> length = mb_strlen($o -> name, 'utf-8');
	}

	//print_r($keywords);
	
	//echo "<br>";

	return $keywords;
}

//키워드를 정리하는 메소드
function cleanupKeyword($results) {
	$array = [];
	foreach($results as $obj) {
		unset($obj -> keywords);
		array_push($array, $obj);
	}

	return $array;
}

//키워드 일치율을 구하는 메소드
function getKeywordRate($a, $b) {
	//문자열 갯수
	$a_len = mb_strlen($a, 'utf-8');
	$b_len = mb_strlen($b, 'utf-8');
	$count = 0;

	for($i=0; $i<$a_len; $i++) {
		$c = mb_substr($a, $i, 1);
		$d = mb_substr($b, $i, 1);

		if($c == $d) $count++;
	}

	return (int)($count / $b_len * 10000) / 100;
}

//해당 위치 근방의 키워드 검색
function searchKeyword($array, $obj, $start) {
	$keywords = [];

	foreach($array as $o) {
		//echo $o -> code.' == '.$obj -> code.'('.$o -> startPos.' / '.$o -> endPos.'> '.$start.')<br>';
		//print_r($o);
		if($o -> code == $obj -> code && $o -> startPos > $start) {
			$obj -> name = $o -> name;
			$obj -> startPos = $o -> startPos;
			$obj -> endPos = $o -> endPos;
			$obj -> length = $o -> length;
			if($obj -> length <= 0) $obj -> length = mb_strlen($o -> name, 'utf-8');
			$obj -> rate = $o -> rate;

			return $obj;
		}
	}

	return $obj;
}

//GUID가져오기
function getGUID() {
	if (function_exists('com_create_guid')){
		return com_create_guid();
	} else {
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = chr(45);// "-"
		$uuid = chr(123)// "{"
			.substr($charid, 0, 8).$hyphen
			.substr($charid, 8, 4).$hyphen
			.substr($charid,12, 4).$hyphen
			.substr($charid,16, 4).$hyphen
			.substr($charid,20,12)
			.chr(125);// "}"
		return $uuid;
	}
}

//폰번호에 하이픈 자동 추가
function format_phone($phone){
	$phone = preg_replace("/[^0-9]/", "", $phone);
	$length = strlen($phone);

	switch($length){
		case 11 :
			return preg_replace("/([0-9]{3})([0-9]{4})([0-9]{4})/", "$1-$2-$3", $phone);
			break;
		case 10:
			return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $phone);
			break;
		default :
			return $phone;
			break;
	}
}
?>