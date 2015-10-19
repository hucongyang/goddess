<?php

// uncomment the following to define a path alias
// Yii::setPathOfAlias('local','path/to/local-folder');

// This is the main Web application configuration. Any writable
// CWebApplication properties can be configured here.
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'墨鹍手机游戏平台',
     'timeZone'=>'Asia/Shanghai',
	'defaultController'=>'Index',

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.models.redis.*',
		'application.models.mysql.*',
        	'application.extensions.*',
		'application.components.*',
		'ext.jpush.*',
		'ext.YiiRedis.*',
	),
	'modules'=>array(
        // uncomment the following to enable the Gii tool
        'gii'=>array(
            'class'=>'system.gii.GiiModule',
            'password'=>'123456',
             // If removed, Gii defaults to localhost only. Edit carefully to taste.
            'ipFilters'=>array('127.0.0.1','::1'),
        ),
    ),

	// application components
	'components'=>array(
		/* 'memcache'=>array(
		        'class'=>'CMemCache',
		        'servers'=>array(
		            array(
		                // 'host'=>'192.168.0.21',
		                // 'port'=>11211
		                'host'=>'127.0.0.1',
		                'port'=>11211
		                //'weight'=>100,
		            )
		        )
		 ), */
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
		),
		// uncomment the following to use a MySQL database
		'db_login'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_login_history',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_user'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_user',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_common'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_common',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_friend'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_user_friend',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_token'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_token',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_log'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_log',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_heroine'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_heroine',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_characters'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_characters',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_message'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_message',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),

		'db_friend_vit'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_friend_vit',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),
		'db_statistics'=>array(
			'connectionString' => 'mysql:host=localhost;dbname=goddess_statistics',
			'emulatePrepare' => true,
			'username' => 'root',
			'password' => 'mokun',
			'charset' => 'utf8',
			'class' =>  'CDbConnection',
			//'tablePrefix' => 'tbl_',
		),
		'db_game'=>array(
		        'connectionString' => 'mysql:host=localhost;dbname=goddess_game',
		        'emulatePrepare' => true,
		        'username' => 'root',
		        'password' => 'mokun',
		        'charset' => 'utf8',
		        'class' =>  'CDbConnection',
		        //'tablePrefix' => 'tbl_',
		),
		'db_pay'=>array(
				'connectionString' => 'mysql:host=localhost;dbname=goddess_pay',
				'emulatePrepare' => true,
				'username' => 'root',
				'password' => 'mokun',
				'charset' => 'utf8',
				'class' =>  'CDbConnection',
				//'tablePrefix' => 'tbl_',
		),
		'db_upload'=>array(
		        'connectionString' => 'mysql:host=localhost;dbname=goddess_upload',
		        'emulatePrepare' => true,
		        'username' => 'root',
		        'password' => 'mokun',
		        'charset' => 'utf8',
		        'class' =>  'CDbConnection',
		        //'tablePrefix' => 'tbl_',
		),
		"redis" => array(
				"class" => "ARedisConnection",
				"hostname" => "127.0.0.1",
				"port" => 6379,
				'database'=>0
		),
		'errorHandler'=>array(
			// use 'site/error' action to display errors
			//'errorAction'=>'site/error',
		),
		'urlManager'=>array(
			'urlFormat'=>'path',
			'rules'=>array(
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'trace,info,profile,error, warning',
				),
				// uncomment the following to show log messages on web pages

				array(
					'class'=>'CWebLogRoute',
					'levels'=>'trace,info,profile,error, warning',
				),

			),
		),
		'curl' => array(
		        'class' => 'ext.Curl',
		        'options' => array() //.. additional curl options ../
		)
	),

	// application-level parameters that can be accessed
	'params'=>array_merge(require(dirname(__FILE__).'/params.php'),require(dirname(__FILE__).'/con_params.php'),require(dirname(__FILE__).'/word.php')),
);