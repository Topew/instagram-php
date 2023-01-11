<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/vendor/autoload.php';

/////// CONFIG ///////
$proxy = null;
$username = "";
$password = "";
$username_to_check = "";
$debug = true;
$truncatedDebug = false;
//////////////////////

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
$ig->setVerifySSL(true);
if (!empty($proxy)) {
    $ig->setProxy($proxy);
}

try {
    // Use /examples/Login/login-ios.php for proper login handling
    $ig->login($username, $password);

    $resp = $ig->people->getInfoByNameWeb($username_to_check, $ig->username);

    if (!empty($resp->getData())) {
        if ($resp->getData()->isUser()) {
            $user = $resp->getData()->getUser();
            if ($user->isUsername()) {
                echo 'Username: ' . $user->getUsername() . "\n";
            }
            if ($user->isProfilePicUrl()) {
                echo 'Profile Pic Url: ' . $user->getProfilePicUrl() . "\n";
            }
            if ($user->isEdgeFollowedBy() && isset($user->getEdgeFollowedBy()['count'])) {
                echo 'Followers Count: ' . $user->getEdgeFollowedBy()['count'] . "\n";
            }
            if ($user->isEdgeFollow() && isset($user->getEdgeFollow()['count'])) {
                echo 'Follow Count: ' . $user->getEdgeFollow()['count'] . "\n";
            }
            if ($user->isEdgeOwnerToTimelineMedia() && isset($user->getEdgeOwnerToTimelineMedia()['count'])) {
                echo 'Media Count: ' . $user->getEdgeOwnerToTimelineMedia()['count'] . "\n";
            }
        } else {
            throw new \InstagramAPI\Exception\NotFoundException("User field not found in response.");
        }
    } else {
        throw new \InstagramAPI\Exception\InstagramException("Data field not found in response.");
    }
} catch (\InstagramAPI\Exception\NetworkException $e) { 
    // Something went wrong on the level of connection with Instagram servers
    echo 'Network Exception: ' . $e->getMessage() . "\n";
} catch (\InstagramAPI\Exception\EmptyResponseException $e) {
    // Something went wrong on the level of Instagram servers
    echo 'Empty Response Exception: ' . $e->getMessage() . "\n";
} catch (\InstagramAPI\Exception\ThrottledException $e) {
    // Throttled by Instagram because of too many API requests
    echo $e->getMessage() . "\n";
} catch (\InstagramAPI\Exception\NotFoundException $e) {
    // User not found 
    echo 'User not found: ' . $e->getMessage() . "\n";
} catch (\InstagramAPI\Exception\InstagramException $e) {
    // Something went wrong on the livel of Instgaram API
    echo 'Instgaram API Error: ' . $e->getMessage() . "\n";
} catch (\Exception $e) {
    // Something went wrong on the level of server
    echo 'Error: ' . $e->getMessage() . "\n";
}