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
$photoFilename = '';
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

// You don't have to provide hashtags or locations for your story. It is
// optional! But we will show you how to do both...

// NOTE: This code will make the hashtag area 'clickable', but YOU need to
// manually draw the hashtag or a sticker-image on top of your image yourself
// before uploading, if you want the tag to actually be visible on-screen!

// NOTE: The same thing happens when a location sticker is added. And the
// "location_sticker" WILL ONLY work if you also add the "location" as shown
// below.

// NOTE: And "caption" will NOT be visible either! Like all the other story
// metadata described above, YOU must manually draw the caption on your image.

// Now create the metadata array:
$metadata = [
    // (optional) You can use new type of story links ONLY if you have a business account with >= 10k followers.
    'link_sticker' => [
        'x'                => 0.5,
        'y'                => 0.1,
        'z'                => 1,
        'width'            => 0.24,
        'height'           => 0.07,
        'rotation'         => 0.0,
        'type'             => 'story_link',
        'link_type'        => 'web',
        'url'              => 'http://apple.com',
        'selected_index'   => 0,
        'is_sticker'       => true,
        'tap_state'        => 0,
        'tap_state_str_id' => 'link_sticker_default'
    ]

    // (optional) You can use story links ONLY if you have a business account with >= 10k followers.
    // 'link' => 'https://github.com/mgp25/Instagram-API',
];

try {
    // This example will upload the image via our automatic photo processing
    // class. It will ensure that the story file matches the ~9:16 (portrait)
    // aspect ratio needed by Instagram stories. You have nothing to worry
    // about, since the class uses temporary files if the input needs
    // processing, and it never overwrites your original file.
    //
    // Also note that it has lots of options, so read its class documentation!
    $ig->event->sendNavigation('button', 'reel_composer_preview', 'story_stickers_tray');
    $ig->event->sendNavigation('button', 'story_stickers_tray', 'reel_composer_preview');
    $ig->event->sendNavigation('button', 'reel_composer_preview', 'reel_composer_camera');
    $ig->event->sendNavigation('button', 'reel_composer_preview', 'self_profile');
    $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($photoFilename, ['targetFeed' => \InstagramAPI\Constants::FEED_STORY]);
    $ig->story->uploadPhoto($photo->getFile(), $metadata);

    // NOTE: Providing metadata for story uploads is OPTIONAL. If you just want
    // to upload it without any tags/location/caption, simply do the following:
    // $ig->story->uploadPhoto($photo->getFile());
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}
