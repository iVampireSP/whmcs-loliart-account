<?php
use WHMCS\Database\Capsule;

function LoliArtAccount_config() {
	$configarray = array(
		'name' 			=> 'LoliArt Account',
		'description' 	=> 'LoliArt 账户系统',
		'version' 		=> '1.0',
		'author' 		=> 'iVampireSP.com',
		'fields' 		=> []
	);
	

	$configarray['fields']['auth_url'] = [
		'FriendlyName' 	=> 'Auth URL',
		'Type' 			=> 'text',
		'Size' 			=> '50',
		'Default'       => 'https://oauth.laecloud.com/oauth/authorize'
	];

	$configarray['fields']['token_url'] = [
		'FriendlyName' 	=> 'Token URL',
		'Type' 			=> 'text',
		'Size' 			=> '50',
		'Default'       => 'https://oauth.laecloud.com/oauth/token'
	];

	$configarray['fields']['user_info_url'] = [
		'FriendlyName' 	=> 'User Info URL',
		'Type' 			=> 'text',
		'Size' 			=> '50',
		'Default'       => 'https://oauth.laecloud.com/api/user',
	];
	
	$configarray['fields']['client_id'] = [
		'FriendlyName' 	=> 'Client ID',
		'Type' 			=> 'text',
		'Size' 			=> '25',
	];
	
	$configarray['fields']['client_secret'] = [
		'FriendlyName' 	=> 'Client secret',
		'Type' 			=> 'text',
		'Size' 			=> '25',
	];
	
		$configarray['fields']['password_client_id'] = [
		'FriendlyName' 	=> 'Password Client ID',
		'Type' 			=> 'text',
		'Size' 			=> '25',
	];
	
	$configarray['fields']['password_client_secret'] = [
		'FriendlyName' 	=> 'Password Client secret',
		'Type' 			=> 'text',
		'Size' 			=> '25',
	];
	
	$configarray['fields']['callback_uri'] = [
		'FriendlyName' 	=> 'Callback URL',
		'Type' 			=> 'text',
		'Size' 			=> '50',
		'Default'       => 'https://stack.laecloud.com/oauth/callback.php',
	];
	
	

	return $configarray;
}



// function loliartaccount_activate() {
    
//     try {
//         Capsule::schema()
//             ->create(
//                 'tblclients_realname',
//                 function ($table) {
//                     $table->increments('id')->primary();
//                     $table->unsignedBitintger('user_id')->index();
//                     $table->text('real_name')->nullable();
//                     $table->text('verified_at')->nullable()->index();
//                 }
//             );

//         return [
//             'status' => 'success',
//             'description' => '模块激活成功. 点击 配置 对模块进行设置。'
//         ];
//     } catch (\Exception $e) {
//         return [
//             'status' => "error",
//             'description' => '无法创建数据库: ' . $e->getMessage(),
//         ];
//     }
    

// }

// function loliartaccount_deactivate() {
// 	return [
// 		'status' => 'success',
// 		'description' => '模块卸载成功'
// 	];
// }
