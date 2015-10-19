<?php

class User_redis extends RedisBase
{
	public $_redis;
	
	private $_uid;				//用户id
	private $_userKey;		//用户级别信息KEY
	private $_life;			//生命周期
	
	public function __construct ()
	{
		$this->_redis = $this->getClient();
		if($this->_redis == ''){
			return FALSE;
		}
	}
	
	/**
	 * 创建key
	 * @param unknown $uid
	 */
	private function _setKeys($uid)
	{
		$this->_userKey = 'user:' . $uid;
		$this->_uid = $uid;
	}
	
	/**
	 * 添加到redis
	 * @param unknown $data
	 * @param string $uid
	 * @return boolean
	 */
	public function addUser($data,$uid = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $uid)
		{
			$this->_setKeys($uid);
		}
		if (empty($this->_uid))
		{
			return FALSE;
		}
	
		return $this->_redis->set($this->_userKey,$data);
	
	}
	
	/**
	 * 获得数据
	 * @param string $uid
	 * @return boolean
	 */
	public function getUser($uid = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $uid)
		{
			$this->_setKeys($uid);
		}
		if (empty($this->_uid))
		{
			return FALSE;
		}
		$data = $this->_redis->get($this->_userKey);
		return json_decode($data,true);
	}
	
	/**
	 * 移除数据
	 * @param unknown $uid
	 * @return boolean
	 */
	public function removeUser($uid){
		if (NULL !== $uid)
		{
			$this->_setKeys($uid);
		}
		if (empty($this->_uid))
		{
			return FALSE;
		}
		echo $num = $this->_redis->delete($this->_userKey);
		return $num;
	}
	
	/**
	 * 
	 * @param unknown $uid
	 * @param unknown $parameter
	 */
	public function getUserParameter($uid,$parameter){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $uid)
		{
			$this->_setKeys($uid);
		}
		if (empty($this->_uid))
		{
			return FALSE;
		}
		$data = $this->_redis->get($this->_userKey);
		$data_arr = json_decode($data,true);
		return $data_arr[$parameter];
	}
}