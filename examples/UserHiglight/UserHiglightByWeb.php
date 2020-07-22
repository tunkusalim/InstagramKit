<?php  
require "../../vendor/autoload.php";

use Riedayme\InstagramKit\InstagramFeedHighlight;
use Riedayme\InstagramKit\InstagramUserHiglight;

$datacookie = 'yourcookie';

$userid = '1931014527'; // fvrskyla

$readfeed = new InstagramFeedHighlight();
$readfeed->SetCookie($datacookie);
$feedlight = $readfeed->Process($userid);

if (!$feedlight['status']) {
	die($feedlight['response']);
}

$idshiglight = $readfeed->Extract($feedlight);

$readhiglight = new InstagramUserHiglight();
$readhiglight->SetCookie($datacookie);
$highlightdata = $readhiglight->Process($idshiglight);

$results = $readhiglight->Extract($highlightdata);

echo "<pre>";
var_dump($results);
echo "</pre>";