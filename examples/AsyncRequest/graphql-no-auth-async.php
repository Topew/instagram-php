<?php

set_time_limit(0);
date_default_timezone_set('UTC');

require __DIR__.'/vendor/autoload.php';

use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;

$ig = new \InstagramAPI\Instagram(false, false, [], 'ios');

// Allow web usage
\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
$ig->setVerifySSL(true);

output_clean("");
output_clean("Async Instagram GraphQL (No Auth, iOS version)");
output_clean("v.1.0");
output_clean("");
output_clean("© Developed by Nextpost Developers Team (https://nextpost.tech)");
output_clean("");

output("Charles proxying enabled?");
output("Default: 2.");
output("1 - [Yes]");
output("2 - [No]");

do { 
    $charles_enabled = (int)getVarFromUser("Choice");
} while ($charles_enabled !== 1 && $charles_enabled !== 2);

if ($charles_enabled == 2) {
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
} else {
    $ig->setProxy("127.0.0.1:8888");
    output("All traffic going throw Charles proxy via port 8888");
}

output("What usernames do you want to check?");
output("Please type minimum 3 usernames separated by comma.");

do { 
    $usernames = getVarFromUser("Usernames");
    $usernames_array = explode(",", $usernames);
} while (count($usernames_array) < 3);

$fake_username = "fake_user_" . time();
$ig->setUserWithoutPassword($fake_username);

$promises = [];

foreach ($usernames_array as $key => $username) {
    $username = trim($username);

    $ig->client->setIsAsyncRequest(true);

    $promise = $ig->people->getInfoByNameWeb($username, false);

    $promise->then(
        function (HttpResponseInterface $httpResponse) use ($ig, $username, $usernames_array, $key, $fake_username) {
            $rawResponse = (string) $httpResponse->getBody();

            try {
                $resp = new \InstagramAPI\Response\GraphqlResponse();

                $ig->client->mapServerResponse(
                    $resp,
                    $rawResponse,
                    $httpResponse,
                    false
                );

                if (!empty($resp) && !empty($resp->hasData()) && !empty($resp->getData())) {
                    if ($resp->getData()->hasUser()) {
                        $user = $resp->getData()->getUser();
                        if (!empty($user)) {
                            $output = "@" . $username;
                            if ($user->hasId()) {
                                $output .= " | " . "User ID: " . $user->getId();
                            }
                            if ($user->hasUsername()) {
                                $output .= " | " . "Username: " . $user->getUsername();
                            }
                            if ($user->hasEdgeFollowedBy() && isset($user->getEdgeFollowedBy()['count'])) {
                                $output .= " | " . "Followers Count: " . $user->getEdgeFollowedBy()['count'];
                            }
                            if ($user->hasEdgeFollow() && isset($user->getEdgeFollow()['count'])) {
                                $output .= " | " . "Follow Count: " . $user->getEdgeFollow()['count'];
                            }
                            if ($user->hasEdgeOwnerToTimelineMedia() && isset($user->getEdgeOwnerToTimelineMedia()['count'])) {
                                $output .= " | " . "Media Count: " . $user->getEdgeOwnerToTimelineMedia()['count'];
                            }
                            output($output);
                        } else {
                            output(sprintf("%s | %s", "@" . $username, "User field is empty in response."));
                        }
                    } else {
                        output(sprintf("%s | %s", "@" . $username, "User field not found in response."));
                    }
                } else {
                    output(sprintf("%s | %s", "@" . $username, "Empty response from Instagram. Change proxy IP."));
                }
            } catch (\InstagramAPI\Exception\NetworkException $e) { 
                // Something went wrong on the level of connection with Instagram servers
                output(sprintf("%s | %s", "@" . $username, "Network Exception: " . $e->getMessage()));
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                // Something went wrong on the level of Instagram servers
                output(sprintf("%s | %s", "@" . $username, "Empty Response Exception: " . $e->getMessage()));
            } catch (\InstagramAPI\Exception\ThrottledException $e) {
                // Throttled by Instagram because of too many API requests
                output(sprintf("%s | %s", "@" . $username, $e->getMessage()));
            } catch (\InstagramAPI\Exception\NotFoundException $e) {
               // User not found 
                output(sprintf("%s | %s", "@" . $username, "User not found: " . $e->getMessage()));
            } catch (\InstagramAPI\Exception\InstagramException $e) {
                // Something went wrong on the livel of Instgaram API
                output(sprintf("%s | %s", "@" . $username, "Instgaram API Error: " . $e->getMessage()));
            } catch (\Exception $e) {
                // Something went wrong on the level of server
                output(sprintf("%s | %s", "@" . $username, "Error: " . $e->getMessage()));
            }

            if ($key == count($usernames_array) - 1) {
                // Remove fake session
                $ig->changeUser('temp_user', 'temp_password');
                $ig->settings->deleteUser($fake_username);
                close();
            }
        },
        function (RequestException $e) use ($ig, $username) {
            output(sprintf("%s | %s", "@" . $username, $e->getMessage()));

            if ($key == count($usernames_array) - 1) {
                // Remove fake session
                $ig->changeUser('temp_user', 'temp_password');
                $ig->settings->deleteUser($fake_username);
                close();
            }
        }
    );

    $promises[] = $promise;
}

// Wait for the requests to complete, even if some of them fail
$responses = Promise\Utils::settle($promises)->wait();

// Remove fake session
$ig->changeUser('temp_user', 'temp_password');
$ig->settings->deleteUser($fake_username);

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