<?php
namespace app\api\validate;

use	think\Validate;

class User extends Validate
{

	protected $rule = [
		'phone'=>'require|max:15',
		'realname'=>'require|max:15',
		'verify_code' => 'require|length:6|regex:/^.*(?=.*[0-9]).*$/',
		'user_password' => 'require|length:6,20',
		'confirm_password' => 'require|length:6,20',
//		'user_password' => 'require|length:7,20|alphaNum|regex:/^.*(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z]).*$/',
//		'confirm_password' => 'require|length:7,20|alphaNum|regex:/^.*(?=.*[0-9])(?=.*[A-Z])(?=.*[a-z]).*$/',
	];


	protected $message = [
        'phone.require' => '手机号码不能为空',
		'verify_code.require' => '验证码不能为空',
		'verify_code.length' => '验证码长度有误',
		'verify_code.regex' => '验证码格式有误',
		'phone.max' => '手机号码长度有误',
		'realname.max' => '姓名长度不能超过15位',
		'user_password.require' =>  '密码不能为空',
		'user_password.length' => '密码长度为7-20位',
		'confirm_password.regex'=>'密码格式有误',
		'confirm_password.length' => '密码长度为7-20位',
		'confirm_password.require' => '密码不能为空',
    ];

	protected $scene = [
		'login'=>['phone','user_password'],
		'edit_name'=>['realname'],
		'register_phone'=>['phone','user_password','verify_code','confirm_password'],
		'find_login_password'=>['phone','user_password','confirm_password','verify_code'],
	];

}
