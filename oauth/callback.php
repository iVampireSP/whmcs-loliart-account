<?php

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "init.php";
require_once(ROOTDIR . '/includes/clientfunctions.php');

// use Illuminate\Support\Str;
use WHMCS\Database\Capsule;

$state = $_SESSION['oauth_state'];
unset($_SESSION['oauth_state']);
if (!isset($_GET['state']) || $_GET['state'] !== $state) {
    die("无法确保此请求是否安全。");
}


$moduleconfig = Capsule::table('tbladdonmodules')->where('module', 'LoliArtAccount')->get();

foreach ($moduleconfig as $key => $value) {
    $config[$value->setting] = $value->value;
}

if ($_GET['code']) {
    $url = $config['token_url'];

    $fields = [
        'grant_type' => 'authorization_code',
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['callback_uri'],
        'code' => $_GET['code'],
    ];


    $fields_string = http_build_query($fields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $result = curl_exec($ch);

    $data = json_decode($result);

    $ch = curl_init($config['user_info_url']);


    $headers[] = 'Authorization: Bearer ' . $data->access_token;


    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);

    $data = json_decode($response);

    if (empty($data)) {
        exit('遇到了错误: ' . curl_error($ch));
        // die('<script>window.location.href="/dologin.php";</script>');
    }

    if (empty($data->real_name_verified_at)) {
        // 没有实名
        $_SESSION['realnamed'] = false;
    } else {
        // 再请求 /api/real-name
        $ch = curl_init($config['realname_info_url']);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $realname_response = curl_exec($ch);
        $realname_data = json_decode($realname_response);

        if (empty($realname_data)) {
            exit('获取实名认证信息时遇到了错误: ' . curl_error($ch));
        }

        $_SESSION['realnamed'] = true;
    }




    $openID = Capsule::table('tblclients')->where('email', $data->email)->first()->id;

    if ($openID) {
        go_oauth_login($openID);
    } else {
        //创建账户
        $user_IP = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
        $user_IP = ($user_IP) ? $user_IP : $_SERVER["REMOTE_ADDR"];
        $command = 'AddClient';
        $postData = [
            'firstname' => 'LAE',
            'lastname' => $data->name,
            'email' => $data->email,
            // 'address1' => $validate_ch->city . ' ' . $validate_ch->state_name,
            // 'city' => $validate_ch->city,
            // 'state' => $validate_ch->state_name,
            // 'postcode' => $validate_ch->postal_code,
            // 'country' => $validate_ch->iso_code,
            'phonenumber' => $phonenumbers,
            'password2' => oauth_random_str(10),
            // 'clientip' => $validate_ch->ip,
            'clientip' => $user_IP,
            'noemail' => true,
            'skipvalidation' => true,
            // 'currency' => $_SESSION['switched_currency'] ?? $validate_ch->currency,
            'currency' => $_SESSION['switched_currency'],
            'language' => $_SESSION['Language'] ?? 'chinese'
        ];

        // Client
        $results = localAPI($command, $postData);

        if ($results['result'] != 'success') {
            exit("无法新增用户。");
        } else {
            $user = Capsule::table('tblusers')->where('email', $data->email)->first();

            $openID = $results['clientid'];

            $hookParams = ["user_id" => $user->id, "client_id" => $results['clientid'], "userid" => $results['clientid']];
            run_hook("ClientAreaRegister", $hookParams);

            go_oauth_login($results['clientid']);
        }
    }

    if ($_SESSION['realnamed'] === true) {
        // 如果有 mod_realname_clients 表，则继续
        if (Capsule::schema()->hasTable('mod_realname_clients')) {
            $query = Capsule::table('mod_realname_clients')->where('client_id', $openID);
            $realname_id = $query->first()->id;
            if (!empty($realname_id)) {
                Capsule::table('mod_realname_clients')->where('id', $realname_id)->update([
                    'client_id' => $openID,
                    'verified_at' => $realname_data->real_name_verified_at,
                    'name' => $realname_data->real_name,
                    'id_card' => $realname_data->id_card,
                ]);
            } else {
                Capsule::table('mod_realname_clients')->insert([
                    'client_id' => $openID,
                    'verified_at' => $realname_data->real_name_verified_at,
                    'name' => $realname_data->real_name,
                    'id_card' => $realname_data->id_card,
                ]);
            }
        }
    } else {
        // 如果有 mod_realname_clients 表，则删除
        if (Capsule::schema()->hasTable('mod_realname_clients')) {
            $query = Capsule::table('mod_realname_clients')->where('client_id', $openID);
            $realname_id = $query->first()->id;
            if (!empty($realname_id)) {
                Capsule::table('mod_realname_clients')->where('id', $realname_id)->delete();
            }
        }
    }


    if ($_SESSION['redirect_uri']) {
        $redirect_uri = $_SESSION['redirect_uri'];
        unset($_SESSION['redirect_uri']);

        header('Location: ' . $redirect_uri);
    } else {
        header('Location: /clientarea.php');
    }
}


function go_oauth_login($uid)
{
    // 登录

    // 写入 SESSION UID
    $_SESSION['uid'] = $uid;

    // 获取 UID
    $userinfo = Capsule::table('tblclients')->where('id', $uid)->first();
    // $username = $userinfo->firstname . ' ' . $userinfo->lastname;

    // 取出值
    $login_uid = $userinfo->id;
    // $login_pwd = $userinfo->password;
    // $language = $userinfo->language;

    $remote_ip = $_SERVER['REMOTE_ADDR'];

    // 更新登录时间登录IP
    $fullhost = gethostbyaddr($remote_ip);

    if ($userinfo->firstname == '') {
        $command = 'UpdateClient';
        $postData = array(
            'clientid' => $login_uid,
            'firstname' => 'LAE',
        );

        $results = localAPI($command, $postData);
    }

    Capsule::table('tblclients')->where('id', $login_uid)->update([
        'lastlogin' => time(),
        'ip' => $remote_ip,
        'host' => $fullhost,
    ]);



    $_SESSION['uid'] = $login_uid;
    if ($login_cid) {
        $_SESSION['cid'] = $login_cid;
    }
    // 仅支持8.1+
    oauth_finishLogin($login_uid);
    $hookParams = ['userid' => $login_uid];
    $hookParams['contactid'] = $login_cid ? $login_cid : 0;
    run_hook('ClientLogin', $hookParams);
    // $loginsuccess = true;
    return 1;
}


function oauth_getWHMCSversion()
{
    //获取WHMCS当前版本
    global $CONFIG;
    return substr($GLOBALS["CONFIG"]["Version"], 0, 5);
}
function oauth_finishLogin($clientid)
{
    //登录参数
    $user = \WHMCS\User\Client::find($clientid);
    $userData["email"] = $user->email;
    if (oauth_isWhmcsVersionHigherOrEqual("8.0.0")) {
        $login = oauth_authUserV8($user);
    } else {
        require ROOTDIR . "/includes/clientfunctions.php";
        $login = oauth_authUserOther($userData);
    }
}
function oauth_isWhmcsVersionHigherOrEqual($toCompare)
{
    if (isset($GLOBALS["CONFIG"]["Version"])) {
        $version = explode("-", $GLOBALS["CONFIG"]["Version"]);
        return version_compare($version[0], $toCompare, ">=");
    }
    global $whmcs;
    return version_compare($whmcs->getVersion()->getRelease(), $toCompare, ">=");
}

function oauth_authUserV8(\WHMCS\User\Client $client)
{
    try {
        $class = new \WHMCS\Authentication\AuthManager();
        $class->login($client->owner());
        return true;
    } catch (\Error $e) {
        return false;
    }
}

function oauth_authUserOther($userData)
{
    $user = json_encode($userData);
    $password = hash("md5", rand());
    return validateClientLogin($user, $password);
}

function oauth_random_str(int $length, int $type = 1)
{
    if ($type == 1) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    } else if ($type == 2) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    } else if ($type == 3) {
        $chars = '0123456789';
    }
    $str = '';
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}
