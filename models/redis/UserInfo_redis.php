<?php

class UserInfo_redis extends RedisBase
{
	public $_redis;
	
	private $_uid;				//用户id
	private $_userInfoKey;		//用户级别信息KEY
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
		$this->_userInfoKey = 'userinfo:' . $uid;
		$this->_uid = $uid;
	}
	
	/**
	 * 添加到redis
	 * @param unknown $data
	 * @param string $uid
	 * @return boolean
	 */
	public function addUserInfo($data,$uid = NULL){
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
	
		return $this->_redis->set($this->_userInfoKey,$data);
	
	}
	
	/**
	 * 获得数据
	 * @param string $uid
	 * @return boolean
	 */
	public function getUserInfo($uid = NULL){
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
		$data = $this->_redis->get($this->_userInfoKey);
		return json_decode($data,true);
	}
	
	/**
	 * 移除数据
	 * @param unknown $uid
	 * @return boolean
	 */
	public function removeUserInfo($uid){
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
		return $this->_redis->delete($this->_userInfoKey);
	}
}