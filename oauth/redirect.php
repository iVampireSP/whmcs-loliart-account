<?php

require_once '../init.php';

use WHMCS\Database\Capsule;
use Illuminate\Support\Str;

$moduleconfig = Capsule::table('tbladdonmodules')->where('module', 'LoliArtAccount')->get();

foreach ( $moduleconfig as $key =>  $value ) { 
    $config[$value->setting] = $value->value;
} 


$state = Str::random(40);

$_SESSION['oauth_state'] = $state;

$query = http_build_query([
    'client_id' => $config['client_id'],
    'redirect_uri' => $config['callback_uri'],
    'response_type' => 'code',
    'scope' => 'user realname',
    'state' => $state,
]);

header("Location: ".$config['auth_url']. '?' . $query);
	