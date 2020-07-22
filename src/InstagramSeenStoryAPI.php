<?php namespace Riedayme\InstagramKit;

Class InstagramSeenStoryAPI
{

	public $cookie; 

	public function SetCookie($data) 
	{
		$this->cookie = $data;
	}

	public function BuildSeenStory($items){

		$reels = [];
		$maxSeenAt = time(); 
		$seenAt = $maxSeenAt - (3 * count($items)); 
		foreach ($items as $item) {

			$itemTakenAt = $item['taken_at'];
			if ($seenAt < $itemTakenAt) {
				$seenAt = $itemTakenAt + 2;
			}

			if ($seenAt > $maxSeenAt) {
				$seenAt = $maxSeenAt;
			}

			$itemSourceId = $item['userid'];

			$reelId = $item['id'].'_'.$itemSourceId;

			$reels[$reelId] = $itemTakenAt.'_'.$seenAt;

			$seenAt += rand(1, 3);
		}

		return $reels;
	}       

	public function Process($storydata)
	{

		$is_vod = false;

		$params = '?' . ($is_vod==false ? 'reel=1' : 'reel=0') . '&' . ($is_vod==true ? 'live_vod=1' : 'live_vod=0');

		$url = 'https://i.instagram.com/api/v1/media/seen/'.$params;

		$data = json_encode([
			'container_module' => 'feed_timeline',
			'reels'      => ($is_vod == false ? $storydata : []),
			'live_vods'  => ($is_vod == true ? $storydata : [])
			]); 

		$postdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $postdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {
			return [
			'status' => true,
			'response' => 'success seen '.count($storydata).' story'
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];
	}

	public function AnswerQuestions($storydata,$answer)
	{

		$url = 'https://i.instagram.com/api/v1/media/'.$storydata['id'].'/'.$storydata['story_detail']['id'].'/story_question_response/';

		$data = json_encode([
			'response' => $answer,
			'type' => 'text'
			]); 

		$postdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $postdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {

			$message = "succes answer question {$storydata['story_detail']['id']} | message : {$answer}";

			return [
			'status' => true,
			'response' => $message
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];
	}

	public function VotePolls($storydata,$vote)
	{

		if ($storydata['story_detail']['viewer_vote']) {
			return [
			'status' => false,
			'response' => 'story_hasben_voted'
			];
		}

		$url = 'https://i.instagram.com/api/v1/media/'.$storydata['id'].'/'.$storydata['story_detail']['id'].'/story_poll_vote/';

		$data = json_encode([
			'radio_type' => 'none',
			'vote' => "{$vote}",
			]); 

		$postdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $postdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {

			$message = "succes polling {$storydata['story_detail']['id']} vote : {$vote}";

			return [
			'status' => true,
			'response' => $message
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];
	}

	public function FollowCountdowns($storydata)
	{

		$url = 'https://i.instagram.com/api/v1/media/'.$storydata['story_detail']['id'].'/follow_story_countdown/';

		$access = InstagramHelperAPI::curl($url, 'empty' , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {

			$message = "succes follow story countdown {$storydata['story_detail']['id']}";

			return [
			'status' => true,
			'response' => $message
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];     
	}

	public function VoteSliders($storydata,$vote)
	{

		if ($storydata['story_detail']['viewer_vote']) {
			return [
			'status' => false,
			'response' => 'story_hasben_voted'
			];
		}

		$url = 'https://i.instagram.com/api/v1/media/'.$storydata['id'].'/'.$storydata['story_detail']['id'].'/story_slider_vote/';

		$data = json_encode([
			'radio_type' => 'wifi-none',
			'vote' => "{$vote}",
			]); 

		$postdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $postdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {

			$message = "succes vote sliders {$storydata['story_detail']['id']} vote : {$vote}";

			return [
			'status' => true,
			'response' => $message
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];             
	}

	public function AnswerQuizs($storydata)
	{

		if ($storydata['story_detail']['viewer_answer']) {
			return [
			'status' => false,
			'response' => 'story_hasben_answer'
			];
		}

		$url = 'https://i.instagram.com/api/v1/media/'.$storydata['id'].'/'.$storydata['story_detail']['id'].'/story_quiz_answer/';

		$answer = rand(0,$storydata['story_detail']['count_question']-1);
		$data = json_encode([
			'answer' => "{$answer}",
			]); 

		$postdata = InstagramHelperAPI::generateSignature($data);

		$access = InstagramHelperAPI::curl($url, $postdata , false , $this->cookie , InstagramUserAgent::Get('Android'));

		$response = json_decode($access['body'],true);

		if ($response['status'] == 'ok') {

			$message = "succes vote story quiz {$storydata['story_detail']['id']}";

			return [
			'status' => true,
			'response' => $message
			];
		}

		return [
		'status' => false,
		'response' => $access['body']
		];             
	}   
}