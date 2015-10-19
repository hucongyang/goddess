<?php

class Goddess_redis extends RedisBase
{
	public $_redis;
	
	private $_goddess_id;		//女神id
	private $_goddessKey;		//女神redisKEY
	
	/* 
	private $nickname;			//昵称
	private $sex;				//性别  0-未知  1-男   2-女
	private $birthplace;		//出生地
	private $birthday;			//出生日期 2014-03-10
	private $signature;			//签名
	private $face_url;			//头像url
	private $height;			//身高（cm）
	private $weight;			//体重（kg）
	private $hobby;			//爱好
	private $lable;			//标签
	private $glamorous;			//魅力值
	private $follower_count;		//关注数
	private $vitalstatistics;	//三围
	private $job;				//职业
	private $blood_type;		//血型 0-空 1-A 2-B 3-AB 4-O
	private $animal_sign;		//生肖0-空 1-12 生肖顺序
	private $constellations;		//星座0-空 1-12 星座顺序
	private $character;			//性格
	private $picture_count;		//照片数
	 */
	
	public function __construct ()
	{
		$this->_redis = $this->getClient();
		if($this->_redis == ''){
			return FALSE;
		}
	}
	
	/**
	 * 创建女神key
	 * @param unknown $goddess_id
	 */
	private function _setKeys($goddess_id)
	{
		$this->_goddessKey = 'goddessinfo:' . $goddess_id;
		$this->_goddess_id = $goddess_id;
	}
	
	/**
	 * 添加到redis
	 * @param unknown $data
	 * @param string $goddess_id
	 * @return boolean
	 */
	public function addGoddess($data,$goddess_id = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $goddess_id)
		{
			$this->_setKeys($goddess_id);
		}
		if (empty($this->_goddess_id))
		{
			return FALSE;
		}
		
		return $this->_redis->set($this->_goddessKey,$data);
		
	}
	
	/**
	 * 获得数据
	 * @param string $goddess_id
	 * @return boolean
	 */
	public function getGoddess($goddess_id = NULL){
		if(empty($this->_redis)){
			return FALSE;
		}
		if (NULL !== $goddess_id)
		{
			$this->_setKeys($goddess_id);
		}
		if (empty($this->_goddess_id))
		{
			return FALSE;
		}
		$data = $this->_redis->get($this->_goddessKey);
		return json_decode($data,true);
	}
}