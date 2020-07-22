<?php namespace Riedayme\InstagramKit;

Class InstagramAuthAPI
{

	public function Login($username,$password,$cookiepath)
	{

		$url = 'https://i.instagram.com/api/v1/accounts/login/';

		$guid = InstagramHelperAPI::generateUUID(true);

		$data = [
			'device_id'           => InstagramHelperAPI::generateDeviceId(md5($username.$password)),
			'guid'                => $guid,
			'phone_id'            => InstagramHelperAPI::generateUUID(true),
			'username'            => $username,
			'password'            => $password,
			'login_attempt_count' => '0',
		];

		$postdata = InstagramHelperAPI::generateSignature(json_encode($data));

		$headers = [
			'Connection: close',
			'Accept: */*',
			'Content-type: application/x-www-form-urlencoded; charset=UTF-8',
			'Cookie2: $Version=1',
			'Accept-Language: en-US',
		];

		if (!$cookiepath) {
			$cookiepath = './'.strtolower($username);
		}else{
			$cookiepath = $cookiepath.strtolower($username);
		}

		$login = InstagramHelper::curl($url, $postdata , $headers , $cookiepath, InstagramUserAgent::Get('Android'));

		$response = json_decode($login['body'],true);

		if($response['status'] == 'ok') {

			/* Result Success on explore/api-v1-accounts-login.json */

			$userid = $response['logged_in_user']['pk'];

			$cookie = InstagramCookie::ReadCookie($login['header']);
			$csrftoken = InstagramCookie::GetCSRFCookie($cookie);

			$userinfo = InstagramResourceUser::GetUserInfoByID($userid);

			if (!$userinfo['status']) {
				return [
					'status' => false,
					'response' => $userinfo['response']
				];
			}

			return [
				'status' => 'success',
				'response' => [
					'userid' => $userid,
					'username' => $userinfo['response']['username'], 
					'photo' => $userinfo['response']['photo'],
					'cookie' => $cookie,
					'csrftoken' => $csrftoken,
					'uuid' => $guid,
					'cookiepath' => $cookiepath
				]
			];

		}else{

			if ($response['error_type'] == 'bad_password') {

				/* Response Error Password
				{
				  "message": "The password you entered is incorrect. Please try again.",
				  "invalid_credentials": true,
				  "error_title": "Incorrect password for username",
				  "buttons": [
				    {
				      "title": "Try Again",
				      "action": "dismiss"
				    }
				  ],
				  "status": "fail",
				  "error_type": "bad_password"
				}			
				*/

				return [
					'status' => false,
					'response' => $response['message']
				];

			}elseif ($response['error_type'] == 'checkpoint_challenge_required') {

				$cookie = InstagramCookie::ReadCookie($login['header']);

				return [
					'status' => 'checkpoint',
					'response' => [
						'url' => $response['challenge']['url'],
						'cookie' => $cookie,
						'csrftoken' => InstagramCookie::GetCSRFCookie($cookie),
						'uuid' => $guid,
						'cookiepath' => $cookiepath
					]
				];		

			}else{
				return [
					'status' => false,
					'response' => $login['body']
				];
			}

		}
	}

	public function CheckPointSend($postdata,$choice = 1)
	{
		$url = $postdata['url'];

		$sendpost = "choice={$choice}";

		$headers = [
			'Connection: keep-alive',
			'Proxy-Connection: keep-alive',
			'Accept-Language: en-US,en',
			'x-csrftoken: '.$postdata['csrftoken'],
			'x-instagram-ajax: 1',
			'Referer: '.$url,
			'x-requested-with: XMLHttpRequest',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
		];

		$access = InstagramHelper::curl($url, $sendpost , $headers , $postdata['cookiepath'], InstagramUserAgent::Get('Android'));

		echo $access['body'];

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {					
				return [
					'status' => true,
					'response' => $response['extraData']['content'][1]['text']
				];		
		}else{


			if (isset($response['message'])) {
				$message = $response['message'];
			}else{
				$message = $response['challenge']['errors'][0];
			}

			return [
				'status' => false,
				'response' => $message
			];
		}
	}

	public function CheckPointSolve($postdata)
	{

		$url = $postdata['url'];

		$sendpost = "security_code={$postdata['security_code']}";

		$headers = [
			'Connection: keep-alive',
			'Proxy-Connection: keep-alive',
			'Accept-Language: en-US,en',
			'x-csrftoken: '.$postdata['csrftoken'],
			'x-instagram-ajax: 1',
			'Referer: '.$url,
			'x-requested-with: XMLHttpRequest',
			'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
		];

		$access = InstagramHelper::curl($url, $sendpost , $headers , $postdata['cookiepath'], InstagramUserAgent::Get('Android'));

		// echo $access['body'];

		$response = json_decode($access['body'],true);

		if($response['status'] == 'ok') {

			$cookie = InstagramCookie::ReadCookie($access['header']);

			$check_cookie = InstagramChecker::CheckLiveCookie($cookie);
			if (!$check_cookie['status']) {
				return [
					'status' => false,
					'response' => 'cookie_invalid'
				];
			}

			$csrftoken = InstagramCookie::GetCSRFCookie($cookie);

			return [
				'status' => true,
				'response' => [
					'userid' => $check_cookie['response']['userid'],
					'username' => $check_cookie['response']['username'], 
					'photo' => $check_cookie['response']['photo'],
					'cookie' => $cookie,
					'csrftoken' => $csrftoken,
					'uuid' =>$postdata['uuid'],
					'cookiepath' => $postdata['cookiepath']
				]
			];
		}else{
			return [
				'status' => false,
				'response' => $response['challenge']['errors'][0]
			];
		}
	}	

}