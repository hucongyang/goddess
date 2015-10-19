<?php
/**
* 获取游戏服务器的根目录地址
* $_REQUEST['from'], 服务器类型 online: 在线, debug: 测试 ， development: 开发 onlinedebug: 线上测试环境
* $_REQUEST['gameid'], 游戏ID   1: 天天跳羊羊,  2: 天天撞神将, 3: 天天跳羊羊2, 10:女神,11:下限少女,12:二次元私密,
*/

define('NONE', 0); 			//默认地址
define('TTTYY',1);    		//天天跳羊羊
define('TTZSJ',2);    		//天天撞神将
define('TTTYY2',3);    		//天天跳羊羊2
define('GODDESS',10);    	//女神零距离
define('GIRL',11);    		//下限少女
define('SECRET',12);    	//二次元私密

//url配置
$url = array('online'=>array(), 'debug'=>array(), 'development'=>array(), 'onlinedebug'=>array());


//开发url配置
$url['development'][NONE]="http://192.168.0.34:82/";
$url['development'][TTTYY]=$url['development'][NONE];
$url['development'][TTZSJ]=$url['development'][NONE];
$url['development'][TTTYY2]=$url['development'][NONE];
$url['development'][GODDESS]="http://192.168.0.34/";
$url['development'][GIRL]="http://192.168.0.34/";
$url['development'][SECRET]="http://192.168.0.34/";

//测试url配置
$url['debug'][NONE]="http://192.168.0.35/";
$url['debug'][TTTYY]=$url['debug'][NONE];
$url['debug'][TTZSJ]=$url['debug'][NONE];
$url['debug'][TTTYY2]=$url['debug'][NONE];
$url['debug'][GODDESS]="http://192.168.0.26/";
$url['debug'][GIRL]="http://192.168.0.26/";
$url['debug'][SECRET]="http://192.168.0.26/";

//线上测试url配置
$url['onlinedebug'][NONE]="http://42.62.67.243/";
$url['onlinedebug'][TTTYY]=$url['onlinedebug'][NONE];
$url['onlinedebug'][TTZSJ]=$url['onlinedebug'][NONE];
$url['onlinedebug'][TTTYY2]=$url['onlinedebug'][NONE];
$url['onlinedebug'][GODDESS]='http://42.62.67.243:83/';
$url['onlinedebug'][GIRL]='http://42.62.67.243:83/';
$url['onlinedebug'][SECRET]='http://42.62.67.243:83/';

//线上环境
$url['online'][NONE]="http://42.62.40.217/";
$url['online'][TTTYY]=$url['online'][NONE];
$url['online'][TTZSJ]=$url['online'][NONE];
$url['online'][TTTYY2]=$url['online'][NONE];
$url['online'][GODDESS]='http://server.nsljl.com/';
$url['online'][GIRL]='http://server.nsljl.com/';
$url['online'][SECRET]='http://server.nsljl.com/';


$from =( isset($_REQUEST['from']) && isset($url[$_REQUEST['from']]) )  ? $_REQUEST['from'] : 'online';
$gameid = isset($_REQUEST['gameid']) ? $_REQUEST['gameid'] : NONE;

//echo $from . " " . $gameid . "<br>";
if(isset($url[$from][$gameid])){
	echo $url[$from][$gameid];
}else{
	echo $url[$from][NONE];
}
