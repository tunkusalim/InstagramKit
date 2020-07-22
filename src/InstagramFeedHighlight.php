<?php namespace Riedayme\InstagramKit;

Class InstagramFeedHighlight
{

	public $cookie;	
	public $csrftoken;

	public function SetCookie($data) 
	{
		$this->cookie = $data;
		$this->csrftoken = InstagramCookie::GetCSRFCookie($data);
	}

	public function Process($userid)
	{
		$variables = '{"user_id":"'.$userid.'","include_chaining":false,"include_reel":false,"include_suggested_users":false,"include_logged_out_extras":false,"include_highlight_reels":true,"include_live_status":false}';			

		$url = 'https://www.instagram.com/graphql/query/?query_hash=d4d88dc1500312af6f937f7b804c68c3&variables='.urlencode($variables);

		$headers = array();
		$headers[] = "User-Agent: ". InstagramUserAgent::Get('Windows');
		$headers[] = "X-Csrftoken: ".$this->csrftoken;
		$headers[] = "Cookie: ". $this->cookie;

		$access = InstagramHelper::curl($url, false , $headers);

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok' AND $response['data']['user']['edge_highlight_reels']['edges'] != null) {		

			return [
				'status' => true,
				'response' => $response
			];

		}else{

			if ($response['status'] == 'ok') {

				return [
					'status' => false,
					'response' => 'no_highlight'
				];
			}

			return [
				'status' => false,
				'response' => $access['body']
			];
		}		
	}

	public function Extract($response){

		if (!$response['status']) return $response;

		$jsondata = $response['response'];
		$edges = $jsondata['data']['user']['edge_highlight_reels']['edges'];

		$extract = array();
		foreach ($edges as $key => $postdata) {

			$extract[] =  $postdata['node']['id'];

		}

		return $extract;

	}

}