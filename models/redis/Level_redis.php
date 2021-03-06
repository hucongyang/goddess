<?php

class Level_redis extends RedisBase
{
	public $_redis;
	
	private $_Key;		//KEY
	
	public function __construct ()
	{
		$this->_redis = $this->getClient();
		if($this->_redis == ''){
			return FALSE;
		}
	}
	
	/**
	 * 创建女神key
	 * 
	 */
	private function _setKeys()
	{
		$this->_Key = 'levelInfo';
	}
	
	/**
	 * 添加到redis
	 * 
	 * @param unknown $data
	 * @return boolean
	 */
	public function addLevel($data){
		if(empty($this->_redis)){
			return FALSE;
		}
		$this->_setKeys();
		
		return $this->_redis->set($this->_Key,$data);
		
	}
	
	/**
	 * 获得数据
	 * 
	 * @return boolean
	 */
	public function getLevel(){
		if(empty($this->_redis)){
			return FALSE;
		}
		$this->_setKeys();
		$data = $this->_redis->get($this->_Key);
		return json_decode($data,true);
	}
}