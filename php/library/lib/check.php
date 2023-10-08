<?php 
//▒▒	비어있는지 검사		▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒<
function check_blank($str, $isCheckTag=false) {
    if(!is_string($str)) return false;
    
    if($str == '0000-00-00 00:00:00') return true;
    if($str == '0000-00-00') return true;
    if($str == '0') return true;
    if($str == '') return true;
    
    $temp=str_replace("　","",$str);
    $temp=str_replace("\n","",$temp);
    $temp=str_replace("&nbsp;","",$temp);
    $temp=str_replace(" ","",$temp);
    
    if($temp == '') return true;
    
    if($isCheckTag) {
        $check=0;
        for($i=0;$i<strlen($temp);$i++) {
            if($temp[$i]=="<") $check=1;
            if(!$check) $temp2.=$temp[$i];
            if($temp[$i]==">") $check=0;
        }
    } else $temp2 = $temp;
    
    if(is_array($temp2)) print_r($temp2);
    
    if($temp2 == '') return true;
    
    if(preg_match('/[a-zA-z]/i', $temp2)) return false;
    if(preg_match('/[0-9]/i', $temp2)) return false;
    if(trim($temp2) != '') return false;
    
    return true;
}

function isEnglish($str) {
    return preg_match('/[^A-Za-z]+/', $str);
}

function isKorean($str) {
    return preg_match("/[\xE0-\xFF][\x80-\xFF][\x80-\xFF]/", $str);
}

function isJson($string) {
	json_decode($string);
	if(json_last_error() == JSON_ERROR_NONE) return true;	
	return false;
}

function check_email($email) {
	return preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/i", $email);
}

function check_handphone($number) {
	return preg_match("/^(010|011|016|017|018|019)-[^0][0-9]{3,4}-[0-9]{4}$/", $number);
}
?>