<?php namespace Riedayme\InstagramKit;

Class InstagramDirectBroadcastAPI
{

	public $cookie;	

	public function SetCookie($data) 
	{
		$this->cookie = $data;
	}

	public function Process($message,$thread_ids)
	{

		$url = "https://i.instagram.com/api/v1/direct_v2/threads/broadcast/text/";

		$data = json_encode([
			'text'   => $message,
			'thread_ids' => '['.implode(',', $thread_ids).']'
		]);

		$buildpostdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $buildpostdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);
		
		if ($response['status'] == 'ok') {
			return [
				'status' => true,
				'response' => 'success send message '. $message,
			];
		}else{
			return [
				'status' => false,
				'response' => $access['body']
			];
		}

	}

}