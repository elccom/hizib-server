<?php
function displayCurrentMenu($name='') {
    global $_lib;
    
    if(empty($_lib['menu'])) return $name;
    
    return $_lib['menu'][count($_lib['menu']) - 1] -> name;
}

function displayAllMenu() {
    global $_lib;
    
    if(count($_lib['menu']) < 1) return '';
    
    $_lib['menu'][0] -> subMenus = $_lib['menu'][0] -> getMenus();
    
    $tag  = '<div id="leftMenu'.$_lib['menu'][0] -> __pkValue__.'" class="leftMenu">';
    $tag .= '<ul>';
    
    for($i=0; $i<count($_lib['menu'][0] -> subMenus); $i++) {
        $obj = $_lib['menu'][0] -> subMenus[$i];
        $obj -> subMenus = $obj -> getMenus();
        
        $isFlag = false;
        if(count($_lib['menu']) >= 2 && $_lib['menu'][1] -> __pkValue__ == $obj -> __pkValue__) $isFlag = true;
        
        $tag .= '<li id="leftMenu'.$obj -> __pkValue__.'"';
        
        if($isFlag) {
            if(count($obj -> subMenus)) $tag .= ' class="over bottom"';
            else $tag .= ' class="over"';
        } else {
            if(count($obj -> subMenus)) $tag .= ' class="top"';
            //else $tag .= ' class=""';
        }
        
        $tag .= '>';
        $tag .= '<a href="';
        if(count($obj -> subMenus)) {
            $tag .= '" onclick="$(this).ELCDiaplyLeftMenu();return false;">';
        } else {
            $tag .= $obj -> href;
            $tag .= '" target="'.$obj -> target.'">';
        }
        $tag .= $obj -> name;
        $tag .= '</a>';
        $tag .= '</li>';
        
        for($j=0; $j<count($obj -> subMenus); $j++) {
            $subMenuObj = $obj -> subMenus[$j];
            
            $tag .= '<li id="leftMenu'.$subMenuObj -> __pkValue__.'"';
            
            if(count($_lib['menu']) > 2 &&  $_lib['menu'][2] -> __pkValue__ == $subMenuObj -> __pkValue__) {
                if($isFlag) $tag .= ' class="over leftMenu'.$obj -> __pkValue__.'"';
                else $tag .= ' class="sub over leftMenu'.$obj -> __pkValue__.'"';
            } else {
                if($isFlag) $tag .= ' class="leftMenu'.$obj -> __pkValue__.'"';
                else $tag .= ' class="sub leftMenu'.$obj -> __pkValue__.'"';
            }
            
            $tag .= '>';
            $tag .= '<a href="'.$subMenuObj -> href.'" target="'.$subMenuObj -> target.'">';
            $tag .= $subMenuObj -> name;
            $tag .= '</a>';
            $tag .= '</li>';
        }
    }
    
    $tag .= '</ul>';
    $tag .= '</div>';
    
    return $tag;
}

function displayCryptedHandphone($handphone) {
    $temp = explode("-", $handphone);
    
    $handphone = substr($temp[0], 0, 2).'*-'.substr($temp[1], 0, 1).'**';
    if(strlen($temp[1]) == 4) $handphone .= substr($temp[1], 3, 1);
    $handphone .= '-'. substr($temp[2], 0, 1).'*'.substr($temp[2], 2, 1).'*';
    
    return $handphone;
}

function inputRadioLabelTag($formName, $array, $name='name', $default='') {
    $tag = '<ul class="ul-radio-list">';

	foreach($array['field'] as $key => $value) {
		$tag .= '<li>';
		$tag .= '<input type="radio" id="'.$key.'" name="'.$formName.'" value="'.$value.'"';
		if($value == $default) $tag .= ' checked="checked"';
		$tag .= '/>';
		$tag .= '<label for="'.$key.'">'.$array[$name][$value].'</label>';
		$tag .= '</li>';
	}
	$tag .= '</ul>';
    
    return $tag;
}

function inputRadioTag($formName, $values, $names, $default='') {
    $tag = '<ul class="ul-radio-list">';

    for($i=0; $i<count($values); $i++) {
        $tag .= '<li><input id="'.$formName.'_'.$values[$i].'" type="radio" name="'.$formName.'" value="'.$values[$i].'"';
        if($values[$i] == $default) $tag .= ' checked="checked"';
        $tag .= '><label for="'.$formName.'_'.$values[$i].'">';
        $tag .= $names[$values[$i]];
        $tag .= '</label></li>';
    }
    
    $tag .= '</ul>';
    
    return $tag;
}

function inputRadioTagByCode($gcode, $formName, $default='') {
    $query = new stdClass();
    $query -> where = "gcode='".$gcode."'";
    $query -> orderby = "sortNum";
    
    $codeObj = new Code();
    $results = $codeObj -> getResults($query);
    
    $tag = '<ul class="ul-radio-list">';
    
    while($data = $results -> fetch_array()) {
        $obj = new Code();
        $obj -> setData($data);
        
        $tag .= '<li><input type="radio" name="'.$formName.'" value="'.$obj -> code.'"';
        if($obj -> code == $default) $tag .= ' checked="checked"';
        $tag .= ' data-sortNum="'.$obj -> sortNum.'"/><label>';
        if(!check_blank($obj -> nickname)) $tag .= $obj -> nickname;
        else $tag .= $obj -> name;
        
        $tag .= '</label></li>';
    }
    
    $tag .= '</ul>';
    
    return $tag;
}

function selectboxTag($formName, $values, $names, $default='', $option="", $initName="▒ 선택 ▒") {
    $tag  = '<select name="'.$formName.'"'.$option.'>';
    if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
    
    for($i=0; $i<count($values); $i++) {
        $tag .= '<option value="'.$values[$i].'"';
        if($values[$i] == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
        $tag .= '> ';
        $tag .= $names[$values[$i]];
        $tag .= '</option>';
    }
    
    $tag .= '</select>';
    
    return $tag;
}

function selectboxTagByDb($formName, $query, $default='', $option="", $initName="▒ 선택 ▒", $isEcho=false) {
	if(check_blank($query -> table)) throw new Exception("테이블정보가 없습니다.");
    
	$codeObj = new Code();
    $results = $codeObj -> getResults($query, $isEcho);
    
    $tag = '<select name="'.$formName.'"'.$option.'>';
    if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
    
    while($data = $results -> fetch_array()) {
        $tag .= '<option value="'.$data[0].'"';
        if($data[0] == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
        $tag .= '>'.$data[1].'</option>';
    }
    
    $tag .= '</select>';
    
    return $tag;	
}

function selectboxTagByCode($gcode, $formName, $default='', $option="", $initName="▒ 선택 ▒") {
    $query = new stdClass();
    $query -> where = "gcode='".$gcode."'";
    $query -> orderby = "sortNum";
    
    $codeObj = new Code();
    $results = $codeObj -> getResults($query);
    
    $tag = '<select name="'.$formName.'"'.$option.'>';
    if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
    
    while($data = $results -> fetch_array()) {
        $obj = new Code();
        $obj -> setData($data);
        
        $tag .= '<option value="'.$obj -> code.'"';
        if($obj -> code == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
        $tag .= '>';
        if(!check_blank($obj -> nickname)) $tag .= $obj -> nickname;
        elseif(!check_blank($obj -> name)) $tag .= $obj -> name;
        else $tag .= $obj -> code;
        $tag .= '</option>';
    }
    
    $tag .= '</select>';
    
    return $tag;
}

function selectboxTagByCode2($gcode, $formName, $default='', $option="", $initName="▒ 선택 ▒") {
    $query = new stdClass();
    $query -> where = "gcode='".$gcode."'";
    $query -> orderby = "sortNum";
    
    $codeObj = new Code();
    $results = $codeObj -> getResults($query);
    
    $tag = '<select name="'.$formName.'"'.$option.'>';
    if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
    
	$tag .= '<option value="-1"';
	if($default == -1) $tag .= ' selected="selected" style="background:#EFEFEF;"';
	$tag .= '>없음</option>';

	while($data = $results -> fetch_array()) {
        $obj = new Code();
        $obj -> setData($data);
        
		if(!empty($obj -> code)) {
			$tag .= '<option value="'.$obj -> code.'"';
			if($obj -> code == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
			$tag .= '>';
			if(!check_blank($obj -> nickname)) $tag .= $obj -> nickname;
			elseif(!check_blank($obj -> name)) $tag .= $obj -> name;
			else $tag .= $obj -> code;
			$tag .= '</option>';
		}
    }
    
    $tag .= '</select>';
    
    return $tag;
}

function selectboxTagByArray($array, $formName, $nameField, $valueField, $default='', $option="", $initName="▒ 선택 ▒") {
    global $_lib;
    
    $tag = '<select name="'.$formName.'"'.$option.'>';
    if(!check_blank($initName)) $tag .= '<option value="">'.$initName.'</option>';
    
    for($i=0; $i<count($array); $i++) {
        $obj = $array[$i];
        
        if(isset($obj -> $nameField)) $name = $obj -> $nameField;
        elseif(method_exists($obj, $nameField)) $name = call_user_func(array($obj, $nameField));
        else $name = '';
        
        if(isset($obj -> $valueField)) $value = $obj -> $valueField;
        elseif(method_exists($obj, $valueField)) $value = call_user_func(array($obj, $valueField));
        else $value = '';
        
        $tag .= '<option value="'.$value.'"';
        if($value == $default) $tag .= ' selected="selected" style="background:#EFEFEF;"';
        $tag .= '>';
        $tag .= $name;
        $tag .= '</option>';
    }
    
    $tag .= '</select>';
    
    return $tag;
}

function inputRadioIsUse($formName='isUse', $default='') {
    $tag = '<ul class="ul-radio-list">';
    $tag .= '<li><input id="'.$formName.'_used" type="radio" name="'.$formName.'" value="1"';
    if($default == 1) $tag .= ' checked="checked"';
    $tag .= '/><label for="'.$formName.'_used">사용중</label>';
    $tag .= '</li>';
    $tag .= '<li><input id="'.$formName.'_unused" type="radio" name="'.$formName.'" value="0"';
    if($default != 1) $tag .= ' checked="checked"';
    $tag .= '/><label for="'.$formName.'_unused">사용안함</label>';
    $tag .= '</li>';
    $tag .= '</ul>';
    
    return $tag;
}

function displayCode($gcode, $value) {
    $obj = new Code();
    $obj -> getDataByCondition("gcode='".$gcode."' AND code='".$value."'");
    
    if(!check_blank($obj -> nickname)) return $obj -> nickname;
    else return $obj -> name;
}

function displayCompanyNumTag($formName, $value="") {
    $temp = explode('-', $value);
    if(count($temp) <= 1) $temp[1] = '';
    if(count($temp) <= 2) $temp[2] = '';
    
    $tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="companyNum1"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="companyNum2"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[2]" value="'.$temp[2].'" class="companyNum3"/>';
    
    return $tag;
}

function displayRegistNumTag($formName, $value="") {
    $temp = explode('-', $value);
    if(count($temp) <= 1) $temp[1] = '';
    if(count($temp) <= 2) $temp[2] = '';
    
    $tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="registNum1"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="registNum2"/>';
    
    return $tag;
}

function displayPhoneTag($formName, $value="") {
    $temp = explode('-', $value);
    if(count($temp) <= 1) $temp[1] = '';
    if(count($temp) <= 2) $temp[2] = '';
    
    $tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="phone1"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="phone2"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[2]" value="'.$temp[2].'" class="phone3"/>';
    
    return $tag;
}

function displayHandphoneTag($formName, $value="") {
    $temp = explode('-', $value);
    if(count($temp) <= 1) $temp[1] = '';
    if(count($temp) <= 2) $temp[2] = '';
    
    $tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="handphone1"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="handphone2"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[2]" value="'.$temp[2].'" class="handphone3"/>';
    
    return $tag;
}

function displayFaxTag($formName, $value="") {
    $temp = explode('-', $value);
    if(count($temp) <= 1) $temp[1] = '';
    if(count($temp) <= 2) $temp[2] = '';
    
    $tag  = '<input type="text" name="'.$formName.'[0]" value="'.$temp[0].'" class="fax1"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[1]" value="'.$temp[1].'" class="fax2"/>';
    $tag .= ' - ';
    $tag .= '<input type="text" name="'.$formName.'[2]" value="'.$temp[2].'" class="fax3"/>';
    
    return $tag;
}

function displayAddressTag($id, $zipcode, $country, $addressMain, $addressDetail, $before='', $after='') {
    global $_lib;
    
    if($_lib['mylang'] == 'en') {
        $tag  = '<ul id="'.$id.'">';
        $tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="Apartment, suite, unit, building, floor, etc."/></li>';
        $tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="State/Province/Region/City/Street address"/></li>';
        $tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="Zip"/></li>';
        $tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="Country/region"/></li>';
        $tag .= '</ul>';
    } else {
        $tag  = '<ul id="'.$id.'">';
        $tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="우편번호"/> ';
        $tag .= '<a href="/zipcode/search?response='.urlencode("$('#".$id."').ELCZipcodeSelected").'" target="dialog" class="button">검색</a></li>';
        $tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="국가"/></li>';
        $tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="시군구 도로명"/></li>';
        $tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="상세주소"/></li>';
        $tag .= '</ul>';
    }
    
    return $tag;
}

function displayLocationTag($id, $zipcode, $country, $addressMain, $addressDetail, $lat, $lng, $before='', $after='') {
    global $_lib;
    
	$tag  = '<ul id="'.$id.'">';
	$tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="우편번호"/> ';
	$tag .= '<a href="/zipcode/search?response='.urlencode("$('#".$id."').ELCLocationSelected").'" target="dialog" class="button">검색</a></li>';
	$tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="국가"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="시군구 도로명"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="상세주소"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'lat'.$after.'" value="'.$addressDetail.'" class="lat" placeholder="위도"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'lng'.$after.'" value="'.$addressDetail.'" class="lng" placeholder="경도"/></li>';
	$tag .= '</ul>';
    
    return $tag;
}

function displayMobileAddressTag($id, $zipcode, $country, $addressMain, $addressDetail, $before='', $after='') {
    global $_lib;

	$tag  = '<ul id="'.$id.'">';
	$tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="우편번호"/> ';
	$tag .= '<a href="/m/zipcode/search?response='.urlencode("$('#".$id."').ELCMobileZipcodeSelected").'" target="dialog" class="button">검색</a></li>';
	$tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="국가"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'addressMain'.$after.'" value="'.$addressMain.'" class="addressMain" placeholder="시군구 도로명"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="상세주소"/></li>';
	$tag .= '</ul>';
    
    return $tag;
}

function displayAddressEnTag($id, $zipcode, $country, $state, $city, $street, $addressDetail, $before='', $after='') {
    global $_lib;
    
	$tag  = '<ul id="'.$id.'">';
	$tag .= '<li><input type="text" name="'.$before.'addressDetail'.$after.'" value="'.$addressDetail.'" class="addressDetail" placeholder="Apartment, suite, unit, building, floor, etc."/></li>';
	$tag .= '<li><input type="text" name="'.$before.'street'.$after.'" value="'.$street.'" class="street" placeholder="Street"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'city'.$after.'" value="'.$city.'" class="city" placeholder="City"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'state'.$after.'" value="'.$state.'" class="state" placeholder="State/Province"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'country'.$after.'" value="'.$country.'" class="country" placeholder="Country/Region"/></li>';
	$tag .= '<li><input type="text" name="'.$before.'zipcode'.$after.'" value="'.$zipcode.'" class="zipcode" placeholder="Zip"/></li>';
	$tag .= '</ul>';
    
    return $tag;
}

function displaySelectbox($json="", $isEcho=false) {
    global $_lib;
    
    if(is_string($json)) $json = jsondecode($json);
    if(empty($json)) $json = new stdClass();
    
    //print_r($json);
    if(!isset($json -> table)) throw new Exception("테이블명이 없습니다. 테이블명을 선언해 주십시오.");
    if(!isset($json -> default)) $json -> default = '';
    if(!isset($json -> initName)) $json -> initName = '▒ 선택 ▒';
    if(!isset($json -> isDisplayIsZero)) $json -> isDisplayIsZero = true;
    
    $listObj = new Components();
    $result = $listObj -> db_handler -> selectQuery($json, $isEcho);
    if(!$result) return '';
    $count = $result -> num_rows;
    
    if(!$json -> isDisplayIsZero && $count <= 0) return '';
    
    $tag  = '<select';
    if(isset($json -> formName)) $tag .= ' name="'.$json -> formName.'"';
    if(isset($json -> option)) $tag .= ' '.$json -> option;
    if(isset($json -> onchange)) $tag .= ' onchange="'.$json -> onchange.'"';
    $tag .= '>';
        
    if(isset($json -> initName)) $tag .= '<option value="">'.$json -> initName.'</option>';
        
    while($data = $result -> fetch_array()) {
        $tag .= '<option value="'.$data[0].'"';
        if($json -> default == $data[0]) $tag .= ' selected="selected" style="background:#EFEFEF;"';
        $tag .= '>'.$data[1].'</option>';
    }
        
    $tag .= '</select>';
    
    return $tag;
}

function displayRadioBankAccount($formName) {
    $codeObj = new Code();
    $query = new stdClass();
    if(isLang('en')) $query -> where = "gcode='BANK_EN'";
    else $query -> where = "gcode='BANK'";
    $query -> orderby = "sortNum";
    
    $html = '';
    
    $results = $codeObj -> getResults($query);
    
    if($results -> num_rows == 1) {
        $data = $results -> fetch_array();
        
        $codeObj = new Code();
        $codeObj -> setData($data);
        
        $temp = explode('|', $codeObj -> code);
        $bank = $temp[0];
        $account = $temp[1];
        
        $html .= '<div class="list"><input type="hidden" name="'.$formName.'" value="'.$bank.'|'.$account.'"/> <span class="bank">'.$bank.'</span><span class="account">'.$account.'</span><span class="account_name">'.$codeObj -> name.'</span></div>';
    } else {
        while($data = $results -> fetch_array()) {
            $codeObj = new Code();
            $codeObj -> setData($data);
            
            $temp = explode('|', $codeObj -> code);
            $bank = $temp[0];
            $account = $temp[1];
            
            $html .= '<div class="list"><input type="radio" name="'.$formName.'" value="'.$bank.'|'.$account.'"/> <span class="bank">'.$bank.'</span><span class="account">'.$account.'</span><span class="account_name">'.$codeObj -> name.'</span></div>';
        }
    }
    
    return $html;
}

function displayTable($obj) {
	$keys = array_keys($obj);
	
	$tag  = '<table class="list">';
	$tag .= '<thead>';
	$tag .= '<tr>';
	foreach($keys as $name) {
		$tag .= '<th>'.$name.'</th>';
	}
	$tag .= '</tr>';
	$tag .= '</thead>';
	$tag .= '<tbody>';
	foreach($keys as $name => $obj) {
		print_r($obj -> $name);
		$tag .= '<td>'.(isset($obj -> $name) && !is_object($obj -> name) && !is_array($obj -> name) ? var_dump($obj -> name) : $obj -> name).'</td>';
	}
	$tag .= '</tbody>';
	$tag .= '</table>';

	return $tag;
}
?>