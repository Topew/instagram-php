<?php

use InstagramAPI\Utils;

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/../vendor/autoload.php';

/////// CONFIG ///////
$username = '';
$password = '';
$debug = true;
$truncatedDebug = false;
//////////////////////

/////// MEDIA ID ////////
$mediaId = '1772128724164452834'; // Only Media PK for this scenario.
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

// NOTE: This code will make the credits of the media area 'clickable', but YOU need to
// manually draw the credit to the user or a sticker-image on top of your image yourself
// before uploading, if you want the credit to actually be visible on-screen!

// If we want to attach a media, we must find a valid media_id first.
try {
    $mediaInfo = $ig->media->getInfo($mediaId)->getItems()[0];
    $mediaOwnerId = $mediaInfo->getUser()->getPk();
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
}

// Create the metadata array for feed post:
$metadata = [
    'reshare_type'   => 'post',
    'attached_media' => [
        'media_id'         => $mediaId,
        'x'                => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
        'y'                => 0.5, // Also note that X/Y is setting the position of the CENTER of the clickable area.
        'z'                => 1,   // Position on the layer, higher value for higher priority
        'width'            => 0.8, // Clickable area size, as percentage of image size: 0.0 - 1.0
        'height'           => 0.6224662, // ...
        'rotation'         => 0.0,
        'is_sticker'       => true, // Don't change this value.
        'type'             => 'feed_media',
        'product_type'     => 'feed',
        'media_owner_id'   => $mediaOwnerId,
        'tap_state'        => 0,
        'tap_state_str_id' => 'feed_post_sticker_square'
    ],
];

// Create the metadata array for Reels post:
$metadata_reels = [
    'reshare_type'   => 'clips',
    'attached_media' => [
        'media_id'         => $mediaId,
        'x'                => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
        'y'                => 0.5, // Also note that X/Y is setting the position of the CENTER of the clickable area.
        'z'                => 1,   // Position on the layer, higher value for higher priority
        'width'            => 0.8, // Clickable area size, as percentage of image size: 0.0 - 1.0
        'height'           => 0.6224662, // ...
        'rotation'         => 0.0,
        'is_sticker'       => true, // Don't change this value.
        'type'             => 'feed_media',
        'product_type'     => 'clips',
        'media_owner_id'   => $mediaOwnerId,
        'tap_state'        => 0,
        'tap_state_str_id' => 'story-reels-metadata-sticker-' . $mediaId
    ],
];

// Create the metadata array for Story post:
$metadata_story = [
    'reshare_type'   => 'story',
    'attached_media' => [
        'user_id'          => $mediaOwnerId,
        'x'                => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
        'y'                => 0.5, // Also note that X/Y is setting the position of the CENTER of the clickable area.
        'z'                => 1,   // Position on the layer, higher value for higher priority
        'width'            => 0.8, // Clickable area size, as percentage of image size: 0.0 - 1.0
        'height'           => 0.6224662, // ...
        'rotation'         => 0.0,
        'is_sticker'       => true, // Don't change this value.
        'type'             => 'mention',
        'display_type'     => 'mention_reshare',
        'tap_state'        => 0,
        'tap_state_str_id' => 'reel_mention_post'
    ],
];

// Create the metadata array for IGTV post:
$metadata_igtv = [
    'reshare_type'   => 'igtv',
    'attached_media' => [
        'media_id'         => $mediaId,
        'x'                => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
        'y'                => 0.5, // Also note that X/Y is setting the position of the CENTER of the clickable area.
        'z'                => 1,   // Position on the layer, higher value for higher priority
        'width'            => 0.8, // Clickable area size, as percentage of image size: 0.0 - 1.0
        'height'           => 0.6224662, // ...
        'rotation'         => 0.0,
        'is_sticker'       => true, // Don't change this value.
        'type'             => 'feed_media',
        'product_type'     => 'igtv',
        'tap_state'        => 0,
        'tap_state_str_id' => 'story-igtv-metadata-sticker-' . $mediaId
    ],
];

// Create the metadata array for Guide post:
$metadata_guide = [
    'reshare_type'   => 'guide',
    'attached_media' => [
        'guide_id'         => $guideId,
        'x'                => 0.5, // Range: 0.0 - 1.0. Note that x = 0.5 and y = 0.5 is center of screen.
        'y'                => 0.5, // Also note that X/Y is setting the position of the CENTER of the clickable area.
        'z'                => 1,   // Position on the layer, higher value for higher priority
        'width'            => 0.8, // Clickable area size, as percentage of image size: 0.0 - 1.0
        'height'           => 0.6224662, // ...
        'rotation'         => 0.0,
        'is_sticker'       => true, // Don't change this value.
        'type'             => 'guide',
        'tap_state'        => 0,
        'tap_state_str_id' => 'guide' . $guideId
    ],
];

$client = new GuzzleHttp\Client();
$outputFile = Utils::createTempFile(sys_get_temp_dir(), 'IMG');

try {
    $response = $client->request('GET', $mediaInfo->getImageVersions2()->getCandidates()[0]->getUrl(), ['sink' => $outputFile]);

    // This example will upload the image via our automatic photo processing
    // class. It will ensure that the story file matches the ~9:16 (portrait)
    // aspect ratio needed by Instagram stories.
    $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($outputFile, ['targetFeed' => \InstagramAPI\Constants::FEED_STORY]);
    $ig->story->uploadPhoto($photo->getFile(), $metadata);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
} finally {
    @unlink($outputFile);
}
