<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/vendor/autoload.php';
$ig = new \InstagramAPI\Instagram(false, false, [], 'ios');

// Allow web usage
\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
\InstagramAPI\Debug::$debugLogHeaders = true;
$ig->setVerifySSL(true);

output_clean("");
output_clean("Instagram GraphQL (No Auth, iOS version)");
output_clean("v.1.0");
output_clean("");
output_clean("© Developed by Nextpost Developers Team (https://nextpost.tech)");
output_clean("");

try {
    output("Enable debug mode?");
    output("Default: 2.");
    output("1 - [Yes]");
    output("2 - [No]");

    do { 
        $debug_mode = (int)getVarFromUser("Choice");
    } while ($debug_mode !== 1 && $debug_mode !== 2);

    if ($debug_mode == 1) {
        $ig = new \InstagramAPI\Instagram(true, true, [], 'ios');
    }

    output("Do you want to use proxy?");
    output("Default: 2.");
    output("1 - [Yes]");
    output("2 - [No]");

    do { 
        $proxy_enabled = (int)getVarFromUser("Choice");
    } while ($proxy_enabled !== 1 && $proxy_enabled !== 2);

    if ($proxy_enabled == 1) {
        do { 
            output("Type 99 to skip proxy usage.");
            $proxy = getVarFromUser("Proxy");
        } while (empty($proxy) && $proxy !== 99);

        if ($proxy !== 99) {
            $ig->setProxy($proxy);
        }
    }

    output("What username do you want to check?");

    do { 
        $username_to_check = getVarFromUser("Username");
    } while (empty($username_to_check));

    $fake_username = "fake_user_" . time();

    $ig->setUserWithoutPassword($fake_username);

    $ig->getHashesGraph();

    $ig->isWebLogin = true;

    $resp = $ig->request("users/web_profile_info/")
        ->setNeedsAuth(false)
        ->setSignedPost(false)
        ->setAddDefaultHeaders(false)
        ->addHeader('X-Asbd-Id', $ig->settings->get('asbd_id'))
        ->addHeader('X-CSRFToken', $ig->csrfToken)
        ->addHeader('Referer', 'https://www.instagram.com/')
        ->addHeader('X-Requested-With', 'XMLHttpRequest')
        ->addHeader('X-Ig-App-Id', \InstagramAPI\Constants::IG_WEB_APPLICATION_ID)
        ->addHeader('X-Instagram-AJAX', $ig->settings->get('rollout_hash'))
        ->addParam('username', $username_to_check)
        ->getResponse(new \InstagramAPI\Response\GraphqlResponse());

    if (!empty($resp) && !empty($resp->hasData()) && !empty($resp->getData())) {
        if ($resp->getData()->hasUser()) {
            $user = $resp->getData()->getUser();
            if (!empty($user)) {
                if ($user->hasId()) {
                    $user_id = $user->getId();
                    output("User ID: " . $user->getId());
                }
                if ($user->hasUsername()) {
                    output("Username: " . $user->getUsername());
                }
                if ($user->hasProfilePicUrl()) {
                    output("Profile Pic Url: " . $user->getProfilePicUrl());
                }
                if ($user->hasEdgeFollowedBy() && isset($user->getEdgeFollowedBy()['count'])) {
                    output("Followers Count: " . $user->getEdgeFollowedBy()['count']);
                }
                if ($user->hasEdgeFollow() && isset($user->getEdgeFollow()['count'])) {
                    output("Follow Count: " . $user->getEdgeFollow()['count']);
                }
                if ($user->hasEdgeOwnerToTimelineMedia() && isset($user->getEdgeOwnerToTimelineMedia()['count'])) {
                    output("Media Count: " . $user->getEdgeOwnerToTimelineMedia()['count']);
                }
            } else {
                throw new \InstagramAPI\Exception\NotFoundException("User field is empty in response.");
            }
        } else {
            throw new \InstagramAPI\Exception\NotFoundException("User field not found in response.");
        }
    } else {
        throw new \InstagramAPI\Exception\InstagramException("Empty response from Instagram. Change proxy IP. Remember, that no auth method for web API will work only for mobile proxies with IP rotation. IP should be changed after every 20 calls without auth.");
    }

    if (!empty($user_id)) {
        $request = $ig->request("graphql/query/")
            ->setNeedsAuth(false)
            ->setVersion(5)
            ->setAddDefaultHeaders(false)
            ->setSignedPost(false)
            ->addHeader('Referer', 'https://www.instagram.com/')
            ->addParam('query_hash', $ig->settings->get('query_hash_guf'))
            ->addParam('variables', json_encode([
                "id" => $user_id,
                "first" => 12,
                "after" => null,
            ]));
            
        $resp = $request->getResponse(new \InstagramAPI\Response\GraphqlResponse());

        if (empty($resp)) {
            throw new \InstagramAPI\Exception\InstagramException("Empty response from Instagram. Change proxy IP. Remember, that no auth method for web API will work only for mobile proxies with IP rotation. IP should be changed after every 20 calls without auth.");
        }
    } else {
        // Remove fake session
        $ig->changeUser('temp_user', 'temp_password');
        $ig->settings->deleteUser($fake_username);
        output("User ID is empty. Change proxy IP. Remember, that no auth method for web API will work only for mobile proxies with IP rotation. IP should be changed after every 20 calls without auth.");
    }

    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
} catch (\InstagramAPI\Exception\NetworkException $e) { 
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // Something went wrong on the level of connection with Instagram servers
    output("Network Exception: " . $e->getMessage());
} catch (\InstagramAPI\Exception\EmptyResponseException $e) {
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // Something went wrong on the level of Instagram servers
    output("Empty Response Exception: " . $e->getMessage());
} catch (\InstagramAPI\Exception\ThrottledException $e) {
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // Throttled by Instagram because of too many API requests
    output($e->getMessage());
} catch (\InstagramAPI\Exception\NotFoundException $e) {
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // User not found 
    output("User not found: " . $e->getMessage());
} catch (\InstagramAPI\Exception\InstagramException $e) {
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // Something went wrong on the livel of Instgaram API
    output("Instgaram API Error: " . $e->getMessage());
} catch (\Exception $e) {
    // Remove fake session
    $ig->changeUser('temp_user', 'temp_password');
    $ig->settings->deleteUser($fake_username);
    // Something went wrong on the level of server
    output("Error: " . $e->getMessage());
}

close();

/**
 * Get varable from user
 */
function getVarFromUser($text) {
    echo $text . ": ";
    $var = trim(fgets(STDIN));
    return $var;
}

/**
 * Output message with data to console
 */
function output($message) {
    echo "[", date("H:i:s"), "] ", $message, PHP_EOL;
}

/**
 * Output clean message to console
 */
function output_clean($message) {
    echo $message, PHP_EOL;
}

/**
 * Output clean message to console
 */
function close() {
    output_clean("");
    output_clean("To run script again use following command:");
    output_clean("php " . __FILE__);
    output_clean("");
    exit;
}


