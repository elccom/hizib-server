<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1);
ini_set('memory_limit','-1');

//모듈 가져오기
include_once($_lib['directory']['library'].'/class/DBConn.php');
include_once($_lib['directory']['library'].'/class/Component.php');
include_once($_lib['directory']['library'].'/class/Components.php');
include_once($_lib['directory']['library'].'/class/AccessToken.php');
include_once($_lib['directory']['library'].'/class/RtcTokenBuilder.php');
include_once($_lib['directory']['library'].'/class/Sendmail.php');
include_once($_lib['directory']['library'].'/class/VideoStream.php');

include_once($_lib['directory']['library'].'/class/Admin.php');
include_once($_lib['directory']['library'].'/class/Code.php');
include_once($_lib['directory']['library'].'/class/Faq.php');
include_once($_lib['directory']['library'].'/class/Fcm.php');
include_once($_lib['directory']['library'].'/class/Logger.php');
include_once($_lib['directory']['library'].'/class/Qna.php');
include_once($_lib['directory']['library'].'/class/Smartdoor.php');
include_once($_lib['directory']['library'].'/class/SmartdoorGroup.php');
include_once($_lib['directory']['library'].'/class/SmartdoorGuestkey.php');
include_once($_lib['directory']['library'].'/class/SmartdoorItem.php');
include_once($_lib['directory']['library'].'/class/SmartdoorLog.php');
include_once($_lib['directory']['library'].'/class/SmartdoorMessage.php');
include_once($_lib['directory']['library'].'/class/SmartdoorNotice.php');
include_once($_lib['directory']['library'].'/class/SmartdoorSchedule.php');
include_once($_lib['directory']['library'].'/class/SmartdoorUser.php');
include_once($_lib['directory']['library'].'/class/SmartdoorUserInvite.php');
include_once($_lib['directory']['library'].'/class/SmartdoorVod.php');
include_once($_lib['directory']['library'].'/class/Ums.php');
include_once($_lib['directory']['library'].'/class/User.php');
include_once($_lib['directory']['library'].'/class/Weather.php');
include_once($_lib['directory']['library'].'/class/Webpage.php');

//라이브러리 
include_once($_lib['directory']['library'].'/lib/general.php');
include_once($_lib['directory']['library'].'/lib/check.php');
include_once($_lib['directory']['library'].'/lib/design.php');
include_once($_lib['directory']['library'].'/lib/file.php');

//사용여부
$_lib['isUse']['field']['used'] = 1;
$_lib['isUse']['field']['unused'] = 0;

$_lib['isUse']['value'][0] = $_lib['isUse']['field']['used'];
$_lib['isUse']['value'][1] = $_lib['isUse']['field']['unused'];

$_lib['isUse']['name'][$_lib['isUse']['field']['used']] = '사용중';
$_lib['isUse']['name'][$_lib['isUse']['field']['unused']] = '사용안함';

//성별
$_lib['sexType']['field']['man'] = 1;
$_lib['sexType']['field']['woman'] = 2;

$_lib['sexType']['value'][0] = $_lib['sexType']['field']['man'];
$_lib['sexType']['value'][1] = $_lib['sexType']['field']['woman'];

$_lib['sexType']['name'][$_lib['sexType']['field']['man']] = '남자';
$_lib['sexType']['name'][$_lib['sexType']['field']['woman']] = '여자';

//문자구분
$_lib['ums_type']['field']['sms'] = 1;
$_lib['ums_type']['field']['mms'] = 2;
$_lib['ums_type']['field']['vms'] = 3;
$_lib['ums_type']['field']['fms'] = 4;

$_lib['ums_type']['value'][0] = $_lib['ums_type']['field']['sms'];
$_lib['ums_type']['value'][1] = $_lib['ums_type']['field']['mms'];
$_lib['ums_type']['value'][2] = $_lib['ums_type']['field']['vms'];
$_lib['ums_type']['value'][3] = $_lib['ums_type']['field']['fms'];

$_lib['ums_type']['name'][$_lib['ums_type']['field']['sms']] = 'SMS';
$_lib['ums_type']['name'][$_lib['ums_type']['field']['mms']] = 'MMS';
$_lib['ums_type']['name'][$_lib['ums_type']['field']['vms']] = 'VMS';
$_lib['ums_type']['name'][$_lib['ums_type']['field']['fms']] = 'FMS';

//문자상태
$_lib['ums_status']['field']['join'] = 1;
$_lib['ums_status']['field']['completed'] = 5;
$_lib['ums_status']['field']['failed'] = 9;

$_lib['ums_status']['value'][0] = $_lib['ums_status']['field']['join'];
$_lib['ums_status']['value'][1] = $_lib['ums_status']['field']['completed'];
$_lib['ums_status']['value'][2] = $_lib['ums_status']['field']['failed'];

$_lib['ums_status']['name'][$_lib['ums_status']['field']['join']] = '신청';
$_lib['ums_status']['name'][$_lib['ums_status']['field']['completed']] = '완료';
$_lib['ums_status']['name'][$_lib['ums_status']['field']['failed']] = '실패';


//기본설정
//if(!isset($_lib['doubleLogin'])) $_lib['doubleLogin'] = '';

//DB연결 함수
if(!isset($_lib['db']['handler'])) $_lib['db']['handler'] = array();
if(!isset($_lib['db']['handler']['master'])) $_lib['db']['handler']['master'] = new DBConn($_lib['db']['master']);
if(!isset($_lib['db']['handler']['slave'])) $_lib['db']['handler']['slave'] = new DBConn($_lib['db']['slave']);
?>