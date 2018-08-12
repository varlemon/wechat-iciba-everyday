<?php

class Iciba
{
	private $appid = '';
	private $appsecret = '';
	private $template_id = '';
	private $access_token = '';

	// 构造函数
	function __construct($wechat_config){
		$this->appid = $wechat_config['appid'];
		$this->appsecret = $wechat_config['appsecret'];
		$this->template_id = $wechat_config['template_id'];
	}

	// HTTP请求
	private function https_request($url, $data = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
		return $output;
	}

	// 获取access_token
	private function get_access_token($appid, $appsecret){
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		$result = json_decode($this->https_request($url), true);
		$this->access_token = $result['access_token'];
		return $this->access_token;
	}

	// 获取用户列表
	private function get_user_list(){
		if($this->access_token == ''){
			$this->get_access_token($this->appid, $this->appsecret);
		}
		$access_token = $this->access_token;
		$url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=$access_token&next_openid=";
		$result = $this->https_request($url);
		return json_decode($result, true);
	}

	// 发送消息
	private function send_msg($openid, $template_id, $iciba_everyday){
		$msg = [
			'touser'		=>	$openid,
			'template_id'	=>	$template_id,
			'url'			=>	$iciba_everyday['fenxiang_img'],
			'data'			=>	[
				'content'		=>	[
					'value'	=>	$iciba_everyday['content'],
					'color'	=>	'#0000CD'
				],
				'note'			=>	[
					'value'	=>	$iciba_everyday['note']
				],
				'translation'	=>	[
					'value'	=>	$iciba_everyday['translation']
				]
			]
		];
		$json = json_encode($msg, JSON_UNESCAPED_UNICODE);
		if($this->access_token == ''){
			$this->get_access_token($this->appid, $this->appsecret);
		}
		$access_token = $this->access_token;
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$access_token";
		$result = $this->https_request($url, $json);
		return json_decode($result, true);
	}

	// 获取爱词霸每日一句
	private function get_iciba_everyday(){
		$url = "http://open.iciba.com/dsapi/";
		$result = $this->https_request($url);
		return json_decode($result, true);
	}

	// 为设置的用户列表发送消息
	private function send_everyday_words($openids){
		$everyday_words = $this->get_iciba_everyday();
		foreach ($openids as $openid) {
			$result = $this->send_msg($openid, $this->template_id, $everyday_words);
			if($result['errcode'] == 0){
				print_r(" [INFO] send to $openid is success\r\n");
			}else{
				print_r(" [INFO] send to $openid is error\r\n");
			}
		}
	}

	// 执行
	public function run($openids=[]){
		if($openids == []){
			// 如果openids为空，则遍历用户列表
			$result = $this->get_user_list();
			$openids = $result['data']['openid'];
		}
		// 根据openids对用户进行群发
		$this->send_everyday_words($openids);
	}
}

