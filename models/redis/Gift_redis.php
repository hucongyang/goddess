<?php

class Gift_redis extends RedisBase
{
	public $_redis;
	
	private $_gift_id;		//女神id
	private $_giftKey;		//女神redisKEY
	
	public function __construct ()
	{
		$this->_redis = $this->getClient();
		if($this->_redis == ''){
			return FALSE;
		}
	}
	
	/**
	 * 创建女神key
	 * @param unknown $gift_id
	 */
	private function _setKeys($gift_id)
	{
		$this->_giftKey = 'giftinfo:' . $gift_id;
		$this->_gift_id = $gift_id;
	}
	
	/**
	 * 添加到redis
	 * @param unknown $data
	 * @param string $gift_id
	 * @return boolean
	 */
	public function addGift($data,$gift_id = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $gift_id)
		{
			$this->_setKeys($gift_id);
		}
		if (empty($this->_gift_id))
		{
			return FALSE;
		}
		
		return $this->_redis->set($this->_giftKey,$data);
		
	}
	
	/**
	 * 获得数据
	 * @param string $gift_id
	 * @return boolean
	 */
	public function getGift($gift_id = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $gift_id)
		{
			$this->_setKeys($gift_id);
		}
		if (empty($this->_gift_id))
		{
			return FALSE;
		}
		$data = $this->_redis->get($this->_giftKey);
		return json_decode($data,true);
	}
	
	
	/**
	 * 移除数据
	 * @param unknown $uid
	 * @return boolean
	 */
	public function removeGift($uid){
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
		return $this->_redis->delete($this->_giftKey);
	}
	
}