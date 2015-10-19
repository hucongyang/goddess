<?php

class RedisBase
{

	public $_redis;

	
	public function __construct()
	{
		
	}
	
	
	public function getClient()
	{
		$path = dirname(dirname(dirname(__FILE__)))."/www/redis_switch.conf";
		
		if(is_readable($path) == false){
			try
			{
				return $this->_redis = Yii::app()->redis->getClient();
			}
			catch (Exception $e)
			{
				
				$fp = fopen($path, "w+");  
				$str = time();
				$handle = fopen($path, "w");  
				fwrite($handle, $str);     	
				fclose($handle);     
			}
		}
// 		else{
// 			$data = file_get_contents($path);
// 			$end_time = time() + 18000;
// 			if((int)$end_time < (int)$data){
// 				$result = @unlink ($path);
// 			}
// 		}
	}
	
}