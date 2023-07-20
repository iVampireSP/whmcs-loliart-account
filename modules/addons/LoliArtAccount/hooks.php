<?php

use WHMCS\Database\Capsule;
use Illuminate\Support\Str;


if (!defined("WHMCS"))
	die("This file cannot be accessed directly");



// add_hook('AdminAreaClientSummaryPage', 1, function($vars) {
//     $userID = $vars['userid'];
//     $verifyinfo = Capsule::table('tblclients_realname')->where('userid', $userID)->first()->real_name;

// 	if ( $verifyinfo != '' ) {
// 		$verifyinfo = '<span class="label label-success">已通过实名认证</span>';
// 	} else {
// 		$verifyinfo = '<span class="label label-danger">未通过实名认证</span>';
// 	}

//     return $verifyinfo;
// });



// add_hook('ClientAreaPage', 1, function($vars) {

// $moduleconfig = Capsule::table('tbladdonmodules')->where('module', 'Oauth')->get();

// foreach ( $moduleconfig as $key =>  $value ) { 
//   $config[$value->setting] = $value->value;
// } 

//     if($vars['templatefile'] == 'login' or $vars['templatefile'] == 'clientregister'){
//           $state = Str::random(40);
//     $query = http_build_query([
//             'client_id' => $config['client_id'],
//             'redirect_uri' => $config['callback_uri'],
//             'response_type' => 'code',
//             'scope' => '',
//             'state' => $state,
//     ]);
   
// 	header("Location: ".$config['auth_url']. '?' . $query);
	
// die();


//     }
// });