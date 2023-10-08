<?php
class JWT {
	protected $alg;
	protected $secret_key;

	// 생성자
	function __construct() {
		global $_lib;

        //사용할 알고리즘
		$this -> alg = 'sha256';

        // 비밀 키
		$this -> secret_key = $_lib['website'] -> name;
    }

	//jwt 발급하기
    function hashing(array $data): string {
		// 헤더 - 사용할 알고리즘과 타입 명시
		$header = json_encode(array(
			'alg' => $this -> alg,
			'typ' => 'JWT'
		));

		$header = base64_encode($header);

		// 페이로드 - 전달할 데이터
		$payload = base64_encode(jsonencode($data));

		echo $header.".".$payload.".".$this -> secret_key."<br>";

		// 시그니처
		$signature = hash($this -> alg, $header.$payload.$this -> secret_key);
		//print_r($signature);

		//return $header.'.'.$payload.'.'.$signature;
		return $header.'.'.$payload.'.'.$signature;
    }

	// jwt 해석하기
	function dehashing($token, $isEcho=false) {
		if($isEcho) echo $token."<br>";
		if(empty($token)) return jsondecode('{}');

		// 구분자 . 로 토큰 나누기
		//$decode_data = base64_decode($token);
		$decode_data = $token;
		if($isEcho) echo $decode_data."<br>";
		$parted = explode('.', base64_decode($decode_data));
		if($isEcho) print_r($parted);
		$signature = $parted[2];
		
		if($isEcho) echo $signature."!=".hash($this -> alg, $parted[0].$parted[1].$this -> secret_key)."<br>";

		// 토큰 만들 때처럼 시그니처 생성 후 비교
		if(hash($this -> alg, $parted[0].$parted[1].$this -> secret_key) != $signature) throw new Exception("서명이 다릅니다.");

		// 만료 검사
		$payload = (object) jsondecode($parted[1]);
		
		if($isEcho) echo $payload -> exp."<".time()."<br>";
		// 유효시간이 현재 시간보다 전이면
		//if($payload -> exp < time()) throw new Exception("유효기간이 만료되었습니다.");

		return $payload;
	}
}
?>