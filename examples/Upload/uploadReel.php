<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../../vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////

/////// MEDIA ////////
$videoFilename = '';
$captionText = '';
$shareReelToFeed = false;
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

try {
    // Note that all video upload functions perform some automatic chunk upload
    // retries, in case of failing to upload all video chunks to Instagram's
    // server! Uploads therefore take longer when their server is overloaded.

    // If you want to guarantee that the file is valid (correct format, codecs,
    // width, height and aspect ratio), then you can run it through our
    // automatic video processing class. It only does any work when the input
    // video file is invalid, so you may want to always use it. You have nothing
    // to worry about, since the class uses temporary files if the input needs
    // processing, and it never overwrites your original file.
    //
    // Also note that it has lots of options, so read its class documentation!

    $metadata = [];
    $metadata['caption'] = $captionText;
    $metadata['share_to_feed'] = $shareReelToFeed;
    // This is not all metadata, you can use more, please check file /instagram-php/src/Request/Internal.php to see all metadata options

    $video = new \InstagramAPI\Media\Video\InstagramVideo($videoFilename, [
        'targetFeed' => \InstagramAPI\Constants::FEED_CLIP,
        'operation' => \InstagramAPI\Media\InstagramMedia::CROP,
        'allowNewAspectDeviation' => true
    ]);
    $ig->reels->uploadVideo($video->getFile(), $metadata);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
