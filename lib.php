<?php
//db설정
$_lib['db']['master'] = new stdClass();
$_lib['db']['master'] -> type = "mysql";
$_lib['db']['master'] -> host = "localhost";
$_lib['db']['master'] -> port = 3306;
$_lib['db']['master'] -> name = "hizib";
$_lib['db']['master'] -> user = "hizib";
$_lib['db']['master'] -> passwd = "dnlzlqkrtm";
$_lib['db']['master'] -> charset = "utf8";
$_lib['db']['master'] -> autocommit = 1;

$_lib['db']['slave'] = new stdClass();
$_lib['db']['slave'] -> type = "mysql";
$_lib['db']['slave'] -> host = "localhost";
$_lib['db']['slave'] -> port = 3306;
$_lib['db']['slave'] -> name = "hizib";
$_lib['db']['slave'] -> user = "hizib";
$_lib['db']['slave'] -> passwd = "dnlzlqkrtm";
$_lib['db']['slave'] -> charset = "utf8";
$_lib['db']['slave'] -> autocommit = 1;

//mqtt
$_lib['mqtt']['host'] = "13.124.155.19";
//$_lib['mqtt']['host'] = "118.67.142.61";

//특별 path 설정
$_lib['directory']['www'] = '/home/hizib';				//일반 사이트
$_lib['directory']['m'] = '/home/hizib';				//m 사이트
$_lib['directory']['master'] = '/home/hizib';			//관리자 사이트
$_lib['directory']['api'] = '/home/hizib';				//api 사이트
$_lib['directory']['app'] = '/home/hizib';				//APP 사이트

//특별 url 설정
$_lib['url']['www'] = '//www.hizib.wikibox.kr';			//일반 사이트
$_lib['url']['m'] = '//m.hizib.wikibox.kr';				//m 사이트
$_lib['url']['master'] = '//master.hizib.wikibox.kr';	//관리자 사이트
$_lib['url']['api'] = '//api.hizib.wikibox.kr';			//api 사이트
$_lib['url']['app'] = '//app.hizib.wikibox.kr';			//APP 사이트

//기본적인 정의
$_lib['website'] = new stdClass();
$_lib['website'] -> name = "hizib";
$_lib['website'] -> nickname = "하이집";
$_lib['website'] -> callback = "0220270037";
$_lib['website'] -> email = "webmaster@wikibox.kr";
$_lib['website'] -> domain = "hizib.wikibox.kr";

$_lib['directory']['root'] = '/home/hizib/';
$_lib['directory']['php'] = '/home/hizib/php';
$_lib['directory']['python'] = '/home/hizib/python';
$_lib['directory']['library'] = $_lib['directory']['php'].'/library';
$_lib['directory']['default'] = $_lib['directory']['root'].'/www';

$_lib['directory']['home'] = $_lib['directory']['www'];

//기본언어 설정
$_lib['language'] = 'ko,en';

//아고라설정
$_lib['agora']['appID'] = "8a8331548d8c4b88a76952d9b103cbad";
$_lib['agora']['appCertificate'] = "33e81f0573a64f9b895a9d2341e4361b";

//라이브러리 가져오기
include_once($_lib['directory']['library'].'/lib.php')
?>