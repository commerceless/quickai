<?php
// Include FB config file && User class
// Path to root directory of app.
define("ROOTPATH", dirname(dirname(dirname(__DIR__))));
require_once 'fbConfig.php';
require_once('../../lang/lang_'.$config['lang'].'.php');

if( !ini_get('allow_url_fopen') ) {
    die('allow_url_fopen is disabled. file_get_contents would not work');
}

// if disabled by admin
if(!get_option("enable_user_registration", '1')) {
    page_not_found();
}

if(isset($accessToken)){
    if(isset($_SESSION['facebook_access_token'])){
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }else{
        // Put short-lived access token in session
        $_SESSION['facebook_access_token'] = (string) $accessToken;

        // OAuth 2.0 client handler helps to manage access tokens
        $oAuth2Client = $fb->getOAuth2Client();

        // Exchanges a short-lived access token for a long-lived one
        $longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($_SESSION['facebook_access_token']);
        $_SESSION['facebook_access_token'] = (string) $longLivedAccessToken;

        // Set default access token to be used in script
        $fb->setDefaultAccessToken($_SESSION['facebook_access_token']);
    }

    // Redirect the user back to the same page if url has "code" parameter in query string
    if(isset($_GET['code'])){
        header('Location: ./');
    }

    // Getting user facebook profile info
    try {
        $profileRequest = $fb->get('/me?fields=name,first_name,last_name,email,link,gender,picture');
        $fbUserProfile = $profileRequest->getGraphNode()->asArray();
    } catch(FacebookResponseException $e) {
        echo 'Graph returned an error: ' . $e->getMessage();
        session_destroy();
        // Redirect user back to app login page
        header("Location: ./");
        exit;
    } catch(FacebookSDKException $e) {
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    // Initialize User class
    //$user = new User();
    if(isset($fbUserProfile['email'])){
        // Insert or update user data to the database
        $fbUserData = array(
            'oauth_provider'=> 'facebook',
            'oauth_uid' 	=> $fbUserProfile['id'],
            'first_name' 	=> $fbUserProfile['first_name'],
            'last_name' 	=> $fbUserProfile['last_name'],
            'email' 		=> $fbUserProfile['email'],
            'gender' 		=> $fbUserProfile['gender'],
            'picture' 		=> $fbUserProfile['picture']['url'],
            'link' 			=> $fbUserProfile['link']
        );

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $fsmallPic = file_get_contents('https://graph.facebook.com/'.$fbUserProfile['id'].'/picture', false, stream_context_create($arrContextOptions));
        $flargePic = file_get_contents('https://graph.facebook.com/'.$fbUserProfile['id'].'/picture?type=normal', false, stream_context_create($arrContextOptions));

        $upOne = realpath(dirname(__FILE__) . '/../../..');

        $picname = $fbUserProfile['id'].'.jpg';
        $sfile = $upOne.'/storage/profile/small_'.$picname;
        $lfile = $upOne.'/storage/profile/'.$picname;
        file_put_contents($sfile, $flargePic);
        file_put_contents($lfile, $flargePic);

    }


    if($fbUserData['email'] == "")
    {
        $error = "Please add email id in facebook account later try again";
        echo "<script type='text/javascript'>alert('$error');</script>";
        redirect_parent($config['site_url'] ."logout",true);
        exit();
    }


    /* ---- Session Variables -----*/
    $userData = array();
    $userData = checkSocialUser($fbUserData,$picname);

    if(!isset($userData['email']))
    {
        $error = __('Email address does not exist');
        echo "<script type='text/javascript'>alert('$error');</script>";
        redirect_parent($config['site_url'] ."logout",true);
        exit();
    }
    elseif($userData['status'] == 2)
    {
        $error = __('This account has been banned');
        echo "<script type='text/javascript'>alert('$error');</script>";
        redirect_parent($config['site_url'] ."logout",true);
        exit();
    }
    else
    {
        create_user_session($userData['id'],$userData['username'],$userData['password_hash'],$userData['user_type']);
        update_lastactive();

        $redirect_url = get_option('after_login_link');
        if(empty($redirect_url)){
            $redirect_url = $config['site_url'] ."login";
        }

        redirect_parent($redirect_url,true);
    }

    // Get logout url
    $logoutURL = $helper->getLogoutUrl($accessToken, $redirectURL.'logout');

}
else{
    // Get login url
    $loginURL = $helper->getLoginUrl($redirectURL, $fbPermissions);
    headerRedirect($loginURL);
}