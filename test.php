<?php  
require "vendor/autoload.php";

use Riedayme\InstagramKit\InstagramHelper;
use Riedayme\InstagramKit\InstagramUserAgent;

$cookiejson = 'exit';

$url = 'https://i.instagram.com/api/v1/feed/timeline/';

$access = InstagramHelper::curl($url, 'empty' , false , './cookie.json' , InstagramUserAgent::Get('Android'));

if (!$access['body'] & !$access['header']) {
echo "noresponse";
}
exit;
?>