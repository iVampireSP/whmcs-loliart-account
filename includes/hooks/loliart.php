<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;

function attemptUser($username, $password) {
    $moduleconfig = Capsule::table('tbladdonmodules')->where('module', 'LoliArtAccount')->get();

    foreach ( $moduleconfig as $key =>  $value ) { 
        $config[$value->setting] = $value->value;
    }

    $url = $config['token_url'];

    $fields = [
        'grant_type' => 'password',
        'client_id' => $config['password_client_id'],
        'client_secret' => $config['password_client_secret'],
        'username' => $username,
        'password' => $password,
        'scope' => 'user realname'
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
    
    $tokens = json_decode($result);
    
    if (curl_errno($ch)) {
        return false;
    }    


    $ch = curl_init($config['user_info_url']);
    

    $headers[] = 'Authorization: Bearer ' . $tokens->access_token;

    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response = curl_exec($ch);
    $oauth_user = json_decode($response);

    if (empty($oauth_user)) {
        return false;
    }

     if ($oauth_user->real_name_verified_at !== null) {
        $_SESSION['realnamed'] = true;
    } else {
        $_SESSION['realnamed'] = false;
    }

    return $oauth_user;
}

add_hook('UserLogout', 1, function () {
    if (isset($_SESSION['loliart_user_id'])) {
        unset($_SESSION['loliart_user_id']);
    }
});

add_hook('ClientLoginShare', 1, function ($vars) {
    $username = $vars['username'];
    $password = $vars['password'];

    // 检测邮箱是否合法
    if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $oauth_user = attemptUser($username, $password);
    
    // 查询用户
    $user_query = Capsule::table('tblusers')->where('email', $oauth_user->email);
    
    $user = $user_query->first();

    
    if ($user) {
        return [
            'email' => $oauth_user->email    
        ];
    }

    if (empty($oauth_user)) {
        return false;
    }

    return [
        'create' => true,
        'email' => $oauth_user->email,
        'firstname' => $oauth_user->name,
        'lastname' => $oauth_user->id,
        'country' => 'CN',
        'password' => $vars['password'],
    ];
});
