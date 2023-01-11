<?php

require __DIR__.'/vendor/autoload.php';
$ig = new \InstagramAPI\Instagram(false, false, [], 'ios');

// Allow web usage
\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
$ig->setVerifySSL(true);

output_clean("");
output_clean("Debugger for login (iOS version)");
output_clean("v.1.0");
output_clean("");
output_clean("© Developed by Nextpost Developers Team (https://nextpost.tech)");
output_clean("");

run($ig);

/**
 * Let's start the show
 */
function run($ig, $login = null, $password = null) {
    try {
        output('Please provide login data of your Instagram Account.');

        // $login = '';
        // $password = '';

        if (empty($login)) {
            do { 
                $login = getVarFromUser("Username"); 
            } while (empty($login));
        }
        if (empty($password)) {
            do { 
                $password = getVarFromUser("Password");
            } while (empty($password));
        }

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

        output("Charles proxying enabled?");
        output("Default: 2.");
        output("1 - [Yes]");
        output("2 - [No]");

        do { 
            $charles_enabled = (int)getVarFromUser("Choice");
        } while ($charles_enabled !== 1 && $charles_enabled !== 2);

        $is_connected = false;
        $is_connected_count = 0;
        $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";

        do {
            if ($is_connected_count == 10) {
                if ($e->getResponse()) {
                    output($e->getMessage());
                }
                throw new Exception($fail_message);
            }

            try {
                if ($is_connected_count == 0) {
                    output("Emulation of an Instagram app initiated...");
                }

                if ($charles_enabled == 1) {
                    $ig->setProxy("127.0.0.1:8888");
                }
                
                $login_resp = $ig->login($login, $password);
    
                if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                    // Default verification method is phone
                    $twofa_method = '1';
    
                    // Detect is 2FA login method
                    $is_totp = json_decode(json_encode($login_resp), true);
                    if ($is_totp['two_factor_info']['totp_two_factor_on']){
                        if ($is_totp['two_factor_info']['show_trusted_device_option'] == true) {
                            output("Instagram send a notification to the trusted device to help you log in. If you're already logged in on another device, you can open the Instagram app to approve this request.");
                            output("Type 3 to use a code from Authentication app");
                            output("Type 2 to use a you backup code for two-factor authentication");
                            output("Type 1 if you confirmed login attempt in the Instagram app");
                            $twofa_method = '4';                            
                        } else {
                            output("Two-factor authentication required, please enter the code from you Authentication app");
                            output("Type 2 to use a you backup code for two-factor authentication");
                            $twofa_method = '3';
                        }
                    } elseif($is_totp['two_factor_info']['whatsapp_two_factor_on']) {
                        output("Two-factor authentication is required, please enter the code from your WhatsApp app");
                        output("Type 2 to use a you backup code for two-factor authentication");
                        $twofa_method = '0';
                    } else {
                        output(sprintf("Two-factor authentication required, please enter the code Instagram sent to your number ending in %s", $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber()));
                        output("Type 2 to use a your backup code for two-factor authentication");
                    }
                    output("Type 99 to login again.");

                    $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();

                    $twofa_code = null;
                    do { 
                        $twofa_code = getVarFromUser("Two-factor code");
                    } while (empty($twofa_code));

                    // Login again
                    if ($twofa_code == 99) {
                        run($ig, $login, $password);
                    }

                    if ($twofa_code == 3) {
                        output("Please enter the code from you Authentication app");
                        output("Type 2 to use a you backup code for two-factor authentication");
                        output("Type 99 to login again.");

                        $twofa_method = '3';

                        $twofa_code = null;
                        do { 
                            $twofa_code = getVarFromUser("Two-factor code");
                        } while (empty($twofa_code));
                    }

                    if ($twofa_code == 2) {
                        output("Please enter one of your backup code.");
                        output("Type 99 to login again.");

                        $twofa_method = '5';

                        $twofa_code = null;
                        do { 
                            $twofa_code = getVarFromUser("Two-factor backup code");
                        } while (empty($twofa_code));
                    }

                    // Login again
                    if ($twofa_code == 99) {
                        run($ig, $login, $password);
                    }
    
                    $is_connected = false;
                    $is_connected_count = 0;
                    do {
                        if ($is_connected_count == 10) {
                            if ($e->getResponse()) {
                                output($e->getMessage());
                            }
                            throw new Exception($fail_message);
                        }

                        if ($is_connected_count == 0) {
                            output("Two-factor authentication in progress...");
                        }

                        if ($twofa_method == 4) {
                            $twofa_confirmed = false;
                            
                            do {
                                $tn_resp = $ig->request('two_factor/check_trusted_notification_status/')
                                    ->setNeedsAuth(false)
                                    ->addPost('two_factor_identifier', $twofa_id)
                                    ->addPost('username', $login)
                                    ->addPost('device_id', $ig->device_id)
                                    ->getResponse(new \InstagramAPI\Response\GenericResponse());

                                $tn = json_decode($tn_resp);

                                if ($tn_resp->getStatus() == "ok") {
                                    if (isset($tn->review_status)) {
                                        if ($tn->review_status == 0) {
                                            output("Login request not confirmed. If you're already logged in on another device, you can open the Instagram app to approve this request.");
                                            output("Type 1 if you confirmed login attempt in the Instagram app");
                                            output("Type 99 to login again.");

                                            $twofa_code = null;
                                            do { 
                                                $twofa_code = getVarFromUser("Two-factor code");
                                            } while (empty($twofa_code));
    
                                            // Login again
                                            if ($twofa_code == 99) {
                                                run($ig, $login, $password);
                                            }
                                            
                                            $twofa_confirmed = false;
                                        } elseif ($tn->review_status == 1 || $tn->review_status == 2) {
                                            // All fine, confirm 2FA login
                                            $twofa_confirmed = true;
                                        } else {
                                            output("We couldn't check trusted notification status in Instagram at the moment. Please try login again with different verification method.");
                                            exit;
                                        }
                                    } else {
                                        output("We couldn't check trusted notification status in Instagram at the moment. Please try login again with different verification method.");
                                        exit;
                                    }
                                } else {
                                    output("We couldn't check trusted notification status in Instagram at the moment. Please try login again with different verification method.");
                                    exit;
                                }
                            } while (!$twofa_confirmed);
                        }

                        try {
                            $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                            $is_connected = true;
                        } catch (\InstagramAPI\Exception\NetworkException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                            $is_code_correct = false;
                            $is_connected= true;
                            do {
                                output("Code is incorrect. Please check the syntax and try again.");
                                output("Type 2 to use a you backup code for two-factor authentication");
                                output("Type 99 to login again.");
                                
                                $twofa_code = null;
                                do { 
                                    $twofa_code = getVarFromUser("Two-factor code");
                                } while (empty($twofa_code));

                                // Login again
                                if ($twofa_code == 99) {
                                    run($ig, $login, $password);
                                }

                                if ($twofa_code == 2) {
                                    output("Please enter one of your backup code.");
                                    output("Type 99 to login again.");
            
                                    $twofa_method = '5';
            
                                    $twofa_code = null;
                                    do { 
                                        $twofa_code = getVarFromUser("Two-factor backup code");
                                    } while (empty($twofa_code));
                                }

                                // Login again
                                if ($twofa_code == 99) {
                                    run($ig, $login, $password);
                                }
            
                                $is_connected = false;
                                $is_connected_count = 0;
                                do {
                                    try {
                                        if ($is_connected_count == 10) {
                                            if ($e->getResponse()) {
                                                output($e->getMessage());
                                            }
                                            throw new Exception($fail_message);
                                        }

                                        if ($is_connected_count == 0) {
                                            output("Verification in progress...");
                                        }
                                        $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                                        $is_code_correct = true;
                                        $is_connected = true;
                                    } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                                        $is_code_correct = false;
                                        $is_connected = true;
                                    } catch (\Exception $e) {
                                        throw $e;
                                    }
                                    $is_connected_count += 1;
                                } while (!$is_connected);
                            } while (!$is_code_correct);
                        } catch (\Exception $e) {
                            throw $e;
                        }

                        $is_connected_count += 1;
                    } while (!$is_connected);
                }

                $is_connected = true;
            } catch (\InstagramAPI\Exception\NetworkException $e) {
                output("Network Exception catched. Loop $is_connected_count.");
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                output("Empty Response Exception catched. Loop $is_connected_count.");
                sleep(7);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                throw new Exception("Please go to Instagram website or mobile app and pass checkpoint!");
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

                if (!($ig instanceof InstagramAPI\Instagram)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (invalid_instagram_client)");
                }
        
                if (!($e instanceof InstagramAPI\Exception\ChallengeRequiredException)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception)");
                }

                if (!$e->hasResponse() || !$e->getResponse()->isChallenge()) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception_response)");
                }
        
                $challenge = $e->getResponse()->getChallenge();

                if (is_array($challenge)) {
                    $api_path = $challenge["api_path"];
                } else {
                    $api_path = $challenge->getApiPath();
                }

                output("Instagram want to send you a security code to verify your identity.");
                output("How do you want receive this code?");
                output("1 - [Email]");
                output("2 - [SMS]");
                output("3 - [Exit]");

                $choice = getVarFromUser("Choice");

                if (empty($choice)) {
                    do { 
                        $choice = getVarFromUser("Choice");
                    } while (empty($choice));
                }

                if ($choice == '1' || $choice == '2' || $choice == '3') {
                    // All fine
                } else {
                    $is_choice_ok = false;
                    do {
                        output("Choice is incorrect. Type 1, 2 or 3.");
                        $choice = getVarFromUser("Choice");

                        if (empty($choice)) {
                            do { 
                                $choice = getVarFromUser("Choice");
                            } while (empty($choice));
                        }

                        if ($confirm == '1' || $confirm == '2' || $confirm == '3') { 
                            $is_choice_ok = true;
                        }
                    } while (!$is_choice_ok);
                }

                $challange_choice = 0;
                if ($choice == '3') {
                    run($ig);
                } elseif ($choice == '1') {
                    // Email
                    $challange_choice = 1;
                } else {
                    // SMS
                    $challange_choice = 0;
                }

                $is_connected = false;
                $is_connected_count = 0;
                do {
                    if ($is_connected_count == 10) {
                        if ($e->getResponse()) {
                            output($e->getMessage());
                        }
                        throw new Exception($fail_message);
                    }

                    try {
                        $challenge_resp = $ig->sendChallangeCode($api_path, $challange_choice);

                        // Failed to send challenge code via email. Try with SMS.
                        if ($challenge_resp->status != "ok") {
                            $challange_choice = 0;
                            sleep(7);
                            $challenge_resp = $ig->sendChallangeCode($api_path, $challange_choice);
                        }

                        $is_connected = true;
                    } catch (\InstagramAPI\Exception\NetworkException $e) {
                        output("Network Exception catched. Loop $is_connected_count.");
                        sleep(7);
                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                        output("Empty Response Exception catched. Loop $is_connected_count.");
                        sleep(7);
                    } catch (\Exception $e) {
                        throw $e;
                    }

                    $is_connected_count += 1;
                } while (!$is_connected);
                
                if ($challenge_resp->status != "ok") {
                    if (isset($challenge_resp->message)) {
                        if ($challenge_resp->message == "This field is required.") {
                            output("We received the response 'This field is required.'. This can happen in 2 reasons:");
                            output("1. Instagram already sent to you verification code to your email or mobile phone number. Please enter this code.");
                            output("2. Instagram forced you to phone verification challenge. Try login to Instagram app or website and take a look at what happened.");
                        }
                    } else {
                        output("Instagram Response: " . json_encode($challenge_resp));
                        output("Couldn't send a verification code for the login challenge. Please try again later.");
                        output("- Is this account has attached mobile phone number in settings?");
                        output("- If no, this can be a reason of this problem. You should add mobile phone number in account settings.");
                        throw new Exception("- Sometimes Instagram can force you to phone verification challenge process.");
                    }
                }

                if (isset($challenge_resp->step_data->contact_point)){
                    $contact_point = $challenge_resp->step_data->contact_point;
                    if ($choice == 2) {
                        output("Enter the code sent to your number ending in " . $contact_point . ".");
                    } else {
                        output("Enter the 6-digit code sent to the email address " . $contact_point . ".");
                    }
                }

                $security_code = getVarFromUser("Security code");

                if (empty($security_code)) {
                    do { 
                        $security_code = getVarFromUser("Security code");
                    } while (empty($security_code));
                }

                if ($security_code == "3") {
                    throw new Exception("Reset in progress...");
                }

                // Verification challenge
                $ig = challange($ig, $login, $password, $api_path, $security_code);

            } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                throw new Exception("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
            } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                // throw new Exception("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
                $response = $ig->internal->sendConsent();
                while (1) {
                    switch ($response->getScreenKey()) {
                        case 'qp_intro': // Intro
                            $response = $ig->internal->sendConsent($response->getScreenKey());
                            break;
                        case 'dob': // Date of Birth
                            $response = $ig->internal->sendConsent($response->getScreenKey(), 7, 7, 1990); // Example: 1, 1, 1925
                            break;
                        case 'tos': // Terms of Service
                            $response = $ig->internal->sendConsent($response->getScreenKey());
                            break;
                        case 'tos_and_two_age_button': // Terms of Service and age over 18
                            $response = $ig->internal->sendConsent($response->getScreenKey());
                            break;
                        case 'finished': // finished
                        case 'already_finished':
                        default:
                            break 2;
                    }
                }
            } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                throw new Exception("Access to Instagram API restricted for spam behavior or otherwise abusing. Please try to use different proxy.");
            } catch (\InstagramAPI\Exception\IncorrectPasswordException $e) {
                throw new Exception("The password you entered is incorrect. Please try again.");
            } catch (\InstagramAPI\Exception\InvalidUserException $e) {
                throw new Exception("The username you entered doesn't appear to belong to an account. Please check your username and try again.");
            } catch (\InstagramAPI\Exception\InstagramException $e) {
                output($e->getMessage());
                output("Please run script command again.");
                exit;
            } catch (\Exception $e) {
                output($e->getMessage());
                output("Please run script command again.");
                exit;
            }

            $is_connected_count += 1;
        } while (!$is_connected);

        output("Logged as @" . $login . " successfully.");
        output("Session saved to /vendor/mgp25/instagram-php/sessions/" . $login . "/");
        output("Settings file: /vendor/mgp25/instagram-php/sessions/" . $login . "/" . $login . "-settings.dat");
        output_clean("");
        output("Which user data do you want to get?");

        $t_username = getVarFromUser("Username");
        if (empty($t_username)) {
            do { 
                $t_username = getVarFromUser("Username"); 
            } while (empty($t_username));
        }

        output("Identifying user...");
            
        $user_info = $ig->people->getInfoByName($t_username);
        $pk = $user_info->getUser()->getPk();

        output("User ID: " . $pk);

    } catch (\Exception $e){
        output($e->getMessage());
        output("Please run script command again.");
        exit;
    }
}

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
 * Validates proxy address
 */
function finishLogin($ig, $login, $password) {
    $is_connected = false;
    $is_connected_count = 0;

    try {
        do {
            if ($is_connected_count == 10) {
                if ($e->getResponse()) {
                    output($e->getMessage());
                }
                $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";
                output($fail_message);
                run($ig);
            }

            try {
                $login_resp = $ig->login($login, $password);
        
                if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                    // Default verification method is phone
                    $twofa_method = '1';

                    // Detect is Authentification app verification is available 
                    $is_totp = json_decode(json_encode($login_resp), true);
                    if ($is_totp['two_factor_info']['totp_two_factor_on'] == '1'){
                        output("Two-factor authentication required, please enter the code from you Authentication app");
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                        $twofa_method = '3';
                    } else {
                        output("Two-factor authentication required, please enter the code sent to your number ending in %s", 
                            $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber());
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                    }

                    $twofa_code = getVarFromUser("Two-factor code");

                    if (empty($twofa_code)) {
                        do { 
                            $twofa_code = getVarFromUser("Two-factor code");
                        } while (empty($twofa_code));
                    }

                    $is_connected = false;
                    $is_connected_count = 0;
                    do {
                        if ($is_connected_count == 10) {
                            if ($e->getResponse()) {
                                output($e->getMessage());
                            }
                            output($fail_message);
                            run($ig);
                        }

                        if ($is_connected_count == 0) {
                            output("Two-factor authentication in progress...");
                        }

                        try {
                            $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                            $is_connected = true;
                        } catch (\InstagramAPI\Exception\NetworkException $e) {
                            output("Network Exception catched. Loop $is_connected_count.");
                            sleep(7);
                        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                            output("Empty Response Exception catched. Loop $is_connected_count.");
                            sleep(7);
                        } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                            $is_code_correct = false;
                            $is_connected= true;
                            do {
                                output("Code is incorrect. Please check the syntax and try again.");
                                $twofa_code = getVarFromUser("Two-factor code");
            
                                if (empty($twofa_code)) {
                                    do { 
                                        $twofa_code = getVarFromUser("Security code");
                                    } while (empty($twofa_code));
                                }
            
                                $is_connected = false;
                                $is_connected_count = 0;
                                do {
                                    try {
                                        if ($is_connected_count == 10) {
                                            if ($e->getResponse()) {
                                                output($e->getMessage());
                                            }
                                            output($fail_message);
                                            run($ig);
                                        }

                                        if ($is_connected_count == 0) {
                                            output("Verification in progress...");
                                        }
                                        $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                                        $is_code_correct = true;
                                        $is_connected = true;
                                    } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                        output("Network Exception catched. Loop $is_connected_count.");
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                        output("Empty Response Exception catched. Loop $is_connected_count.");
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                                        $is_code_correct = false;
                                        $is_connected = true;
                                    } catch (\Exception $e) {
                                        throw new $e;
                                    }
                                    $is_connected_count += 1;
                                } while (!$is_connected);
                            } while (!$is_code_correct);
                        } catch (\Exception $e) {
                            throw $e;
                        }

                        $is_connected_count += 1;
                    } while (!$is_connected);
                }

                $is_connected = true;
            } catch (\InstagramAPI\Exception\NetworkException $e) { 
                output("Network Exception catched. Loop $is_connected_count.");
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                output("Empty Response Exception catched. Loop $is_connected_count.");
                sleep(7);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                throw new Exception("Please go to Instagram website or mobile app and pass checkpoint!");
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
                output("Instagram Response: " . json_encode($e->getResponse()));
                output("Couldn't complete the verification challenge. Please try again later.");
                throw new Exception("Developer code: Challenge loop at finishLogin().");
            } catch (\Exception $e) {
                throw $e;
            }

            $is_connected_count += 1;
        } while (!$is_connected);
    } catch (\Exception $e){
        output($e->getMessage());
        run($ig);
    }

    return $ig;
}

/**
 * Verification challenge
 */
function challange($ig, $login, $password, $api_path, $security_code) {
    $is_connected = false;
    $is_connected_count = 0;
    $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";

    do {
        if ($is_connected_count == 10) {
            if ($e->getResponse()) {
                output($e->getMessage());
            }
            throw new Exception($fail_message);
        }

        if ($is_connected_count == 0) {
            output("Verification in progress...");
        }

        try {
            $challenge_resp = $ig->finishChallengeLogin($login, $password, $api_path, $security_code);
            $is_connected = true;
        } catch (\InstagramAPI\Exception\NetworkException $e) {
            output("Network Exception catched. Loop $is_connected_count.");
            sleep(7);
        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
            output("Empty Response Exception catched. Loop $is_connected_count.");
            sleep(7);
        } catch (\InstagramAPI\Exception\InstagramException $e) {

            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
                output($msg);
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
                output($msg);
            }

            output("Type 3 - to exit.");

            $security_code = getVarFromUser("Security code");

            if (empty($security_code)) {
                do { 
                    $security_code = getVarFromUser("Security code");
                } while (empty($security_code));
            }

            if ($security_code == "3") {
                throw new Exception("Reset in progress...");
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($msg == 'Invalid Login Response at finishChallengeLogin().') {
                sleep(7);
                $ig = finishLogin($ig, $login, $password);
                $is_connected = true;
            } else {
                throw $e;
            }
        }

        $is_connected_count += 1;
    } while (!$is_connected);

    return $ig;
}

?>