<?php
//defined('ADMIN_HOST') or define('ADMIN_HOST', 'http://122.227.43.176/admin');
//defined('API_HOST') or define('API_HOST', 'http://122.227.43.176/index.php/');

// this contains the application parameters that can be maintained via GUI
return array(
	'token'=>array(
			//线下测试环境KEY
			'key' => '0c1225c5347509d5f01e909c75bc599c',
			//线上环境KEY
    		// 'key' => '0525fc0547f64a1470232fb34364b82b',
    		'sign'=>'sign',
    		'exclude'=>array('version','device_id','platform','channel','app_version','os_version','app_id','sign')
    	),	
    // 图片域名前缀（最后一定要带/）
    'img_url_base' => 'http://192.168.0.34:81/',
    // 图片服务器域名地址
    'img_server_url' => 'http://192.168.0.34:81/index.php/goddess/uploadAvatar',
	//支付宝服务器主动通知商户网站里指定的页面http路径。需要URL encode。
	'notifyUrl' => 'http://192.168.0.34/index.php/pay/notify',
	//支付宝
	'sellerId' => 'eric.mai@mokun.net',
	//合作者身份ID
	'partner' =>'2088901724200650',
	//订单前缀
	'order_num' =>'1000650',
	//支付配置
	'alipay_config' => array(
			//合作身份者id，以2088开头的16位纯数字
			'partner'		    => '2088901724200650',
			//商户的私钥（后缀是.pen）文件相对路径
			'private_key_path' => 'key/rsa_private_key.pem',
	
			//支付宝公钥（后缀是.pen）文件相对路径
			'ali_public_key_path' => '../extensions/alipay/key/alipay_public_key.pem',
			//签名方式 不需修改
			'sign_type'    => strtoupper('RSA'),
			//字符编码格式 目前支持 gbk 或 utf-8
			'input_charset'=> strtolower('utf-8'),
			//ca证书路径地址，用于curl中ssl校验
			//请保证cacert.pem文件在当前文件夹目录中
			'cacert'    => getcwd().'\\cacert.pem',
			//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
			'transport'    => 'http',
	), 
);
