<?php

/*********************************************************
 * This is the model class for user
 *
 * @package User
 * @author  Lujia
 *
 * @version 1.0 by Lujia @ 2013.12.23 创建类以及相关的操作
 ***********************************************************/

class User extends CActiveRecord
{

	/*******************************************************
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return User the static model class
     *******************************************************/
    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 用户表新增用户
     *
     * @param  string $username
     * @param  string $password
     * @param  string $email
     * @param  string $mobile
	 *
	 * @return int   $user_id
     */
    public function insertUser($username, $password, $email=null, $mobile=null, $time){
    	   $user_id = 0;
        try{
            $con_user = Yii::app()->db_user;
            // 创建用户 主表写入数据
            $con_user->createCommand()->insert('user',
            		array('user_name'       => $username,
            			  'password'        => $password,
            			  'email'           => $email,
            		       'regist_mac'      => $GLOBALS['__DEVICEID'],
            		       'app_id'          => $GLOBALS['__APPID'],
            			  'status'		=> 0,
            			  'regist_ts'		=> $time,
            			  'last_login_ts' 	=> $time));
            $user_id = Yii::app()->db_user->getLastInsertID();
            // 创建分表用户 分表写入数据方便后续查询
            $table_name = sprintf('user_%02s', dechex($user_id % 256));     // dechex() 十进制转换为十六进制；sprintf()把格式化的字符串写入一个变量中
            $con_user->createCommand()->insert($table_name,
            		array('user_id'         => $user_id,
            			  'user_name'       => $username,
            			  'password'        => $password,
            			  'email'           => $email,
            		       'regist_mac'      => $GLOBALS['__DEVICEID'],
            		       'app_id'          => $GLOBALS['__APPID'],
            			  'status'		=> 0,
            			  'regist_ts'		=> $time,
            			  'last_login_ts' 	=> $time));
        }catch(Exception $e){
            error_log($e);
        }
        return $user_id;
    }

	/*******************************************************
	 * 获取用户UID     getUserId
	 *
	 * @param string $username		// 用户名
	 * @param string $email		// 邮箱
	 * @param string $mobile		// 手机号码
	 *
     * @return int $uid
	 * 说明：根据用户名/邮箱/手机号码，获取用户的userid
	 *******************************************************/
	public function getUserId($username, $email=null, $mobile=null)
	{
		$uid = 0;
		if($username != NULL || $email != NULL || $mobile != NULL)
		{
			$con_user = Yii::app()->db_user;
			$param = array();
			$condition = NULL;
			if($username)
			{
				$condition = 'user_name = :UserName';
				$param[':UserName'] = $username;
			}
			else if($email)
			{
				$condition = 'email = :Email';
				$param[':Email'] = $email;
			}
			else if($mobile)
			{
				$condition = 'mobile = :Mobile';
				$param[':Mobile'] = $mobile;
			}
			// goddess_user数据库里面user表没有app_id字段，所以先注释以下两行代码
//			$condition = $condition.' AND app_id = :APP_ID';
//			$param[':APP_ID'] = $GLOBALS['__APPID'];
			$uid = 0;
			try{
				$uid = $con_user->createCommand()
							->select('user_id')
							->from('user')
							->where($condition, $param)
							->queryScalar();
			}catch(Exception $e){
				error_log($e);
			}

		}
		return $uid;
	}


	/**
	 * 模糊搜索
	 */
	public function fuzzy($nickname, $start, $page_size)
	{
        if (!get_magic_quotes_gpc())
        {
            $nickname = addslashes($nickname);
        }

        $ids = array();
		try{

			$ids = Yii::app()->db_user->createCommand("SELECT user_id FROM user WHERE  user_name LIKE '".$nickname."%'
			        AND app_id = ".$GLOBALS['__APPID']." limit ".$start.",".$page_size)->queryColumn();

		}catch(Exception $e){
			error_log($e);
		}

		return $ids;
	}

	/*******************************************************
	 * 获取用户个人信息  getUserInfo
	 *
	 * @param string $uid					// 用户ID
	 *
	 * @return string $sex				// 性别
	 * @return $age				// 年龄
	 * @return $constellation		// 星座
	 * @return $sign				// 签名
	 * @return $avatar				// 头像
	 * @return $isCanModifyUsername	// 能否修改用户名
	 * @return $isSetPass			// 是否已经设定密码
	 *
	 * 说明：获取用户个人信息
	 *******************************************************/
	 public function getUserInfo($uid)
	{
		$userinfo_redis = new UserInfo_redis();
		$data = $userinfo_redis->getUserInfo($uid);
		if(!empty($data)){
			return $data;
		}else{
			$data = array();
			try{
				$con_user = Yii::app()->db_user;
				$table_name = sprintf('user_info_%02s', dechex($uid % 256));
				$result = $con_user->createCommand()
								->select('sex,age,constellation,avatar,nickname,signature,birthday,birthplace')
								->from($table_name)
								->where('user_id=:UserId')
								->bindParam(':UserId', $uid, PDO::PARAM_INT, 11)
								->queryRow();
				if(!$result) return false;

				$data['sex']           = $result['sex'];
				$data['age']           = $result['age'];
				$data['constellation'] = $result['constellation'];
				$data['avatar']        = $result['avatar'];
				$data['nickname']      = $result['nickname'];
				$data['signature']     = $result['signature'];
				$data['birthday']      = $result['birthday'];
				$data['birthplace']    = $result['birthplace'];
				if($data['avatar'] == NULL)
				{
				    
				    if($result['sex'] == 2){
				        $data['avatar'] = Yii::app()->params['default_woman_head_image'];
				    }else{
				        
					   $data['avatar'] = Yii::app()->params['default_head_image'];
				    }
				}
				$data['avatar'] = Yii::app()->params['img_url_base'] . $data['avatar'];
				$data['avatar'] = stripslashes($data['avatar']);
			}catch(Exception $e){
				error_log($e);
				return false;
			}

			$userinfo_redis->addUserInfo(json_encode($data),$uid);
			return $data;
		}
	}


	/**
	 * 获取用户安全信息 getUserSafeInfo
	 *
	 * @param  int   $user_id
	 * @return array $data
	 */
	public function getUserSafeInfo($user_id)
	{
		$user_id = intval($user_id);

		$user_redis = new User_redis();
		$data = $user_redis->getUser($user_id);
		if(!empty($data)){
			return $data;
		}else{
			$data = array();

			$table_name = sprintf('user_%02s', dechex($user_id % 256));
			try{
			$con_user = Yii::app()->db_user;
			$data = $con_user->createCommand()
							->select('user_name,password,email,mobile,status,from_type,from_user_id,regist_ts')
							->from($table_name)
							->where('user_id = :user_id', array(':user_id' => $user_id))
							->queryRow();
			}catch(Exception $e){
				error_log($e);
				return false;
			}
			$user_redis->addUser(json_encode($data),$user_id);
			return $data;
		}
	}

	/**
	 * 获取用户名
	 * @param int $user_id
	 */
	public function getUsername($user_id)
	{
		$user_redis = new User_redis();
		$data = $user_redis->getUserParameter($user_id,'user_name');
		if(!empty($data)){
			return $data;
		}else{
			$table_name = sprintf('user_%02s', dechex($user_id % 256));
			try{
			$con_user = Yii::app()->db_user;
			$username = $con_user->createCommand()
							->select('user_name')
							->from($table_name)
							->where('user_id = :user_id', array(':user_id' => $user_id))
							->queryScalar();
			}catch(Exception $e){
				error_log($e);
				return false;
			}
			return $username;
		}
	}

	/**
	 * 修改用户密码 updateUserPassword
	 *
	 * @param  int    $user_id    //用户id
	 * @param  string $password   //用户密码
	 *
	 * @return boolean
	 * 更新用户表和用户分表
	 */
    public function updateUserPassword($user_id, $password)
    {
        try{
            $con_user = Yii::app()->db_user;
            // 创建用户
            $con_user->createCommand()->update('user',
            		array('password' => $password),
            		'user_id=:UserId', array(':UserId' => $user_id));
            
            // 创建分表用户
            $table_name = sprintf('user_%02s', dechex($user_id % 256));
            $con_user->createCommand()->update($table_name,
            		array('password' => $password),
            		'user_id=:UserId', array(':UserId' => $user_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }

	/**
	 * 更新用户信息 updateUserInfo
	 *
	 * @param int    $user_id
	 * @param array  $param
	 */
	public function changeUserInfo($user_id, $param)
	{
		if(!is_array($param) || empty($param)) return false;

		
		try{
			$con_user = Yii::app()->db_user;
			$table_name = sprintf('user_info_%02s', dechex($user_id % 256));
			if(isset($param['sex'])){
			    $avatar = $con_user->createCommand()
        			    ->select('avatar')
        			    ->from($table_name)
        			    ->where('user_id=:UserId')
        			    ->bindParam(':UserId', $user_id, PDO::PARAM_INT, 11)
        			    ->queryRow();
			    if($param['sex'] == 2){
					
			        if($avatar['avatar'] == Yii::app()->params['default_head_image']){
			            $param['avatar'] = Yii::app()->params['default_woman_head_image'];
			        }
			    }elseif($param['sex'] == 1){
					
			        if($avatar['avatar'] == Yii::app()->params['default_woman_head_image']){
			             $param['avatar'] = Yii::app()->params['default_head_image'];
			        }
			    }
			}
			$ret = $con_user->createCommand()->update($table_name,
					$param,
					'user_id=:UserId', array(':UserId' => $user_id));
			//更新用户消息移除user_info redis
// 			$userinfo_redis = new UserInfo_redis();
// 			$userinfo_redis->removeUserInfo($user_id);
		}catch(Exception $e){
			error_log($e);
			return false;
		}
		return $ret;
	}

	/**
	 * 用户好友列表
	 *
	 * @param int $user_id
	 *
	 */
	public function friendList($user_id)
	{
		$t_safe = array(
				//用户名
				'user_name' => ''
			);
		$t_info  = array(
				//头像
				'avatar' => '',
				//昵称
				'nickname' =>''
			);
		$t_player = array(
				//积分
				'exp' => '',
				'level' => ''
			);

		$friend_ids = UserFriend::model()->selectFriend($user_id);
		$data = array();
		if(is_array($friend_ids)){
			foreach ($friend_ids as $val) {
				//获取头像
				$info = $this->getUserInfo($val['friend_user_id']);

				//获取用户名
				$safe = $this->getUserSafeInfo($val['friend_user_id']);


				//获取游戏角色信息
				$characters = Characters::model()->getCharactersInfo($val['friend_user_id']);

				if(is_array($info) && is_array($safe) && is_array($characters)){

					$info = array_intersect_key($info, $t_info);
					//avatar 替换成 face_url
					if(isset($info['avatar'])){
						$info['face_url'] = $info['avatar'];
						unset($info['avatar']);
					}
					//增加user_id
					$info['friend_id'] = (int)$val['friend_user_id'];

					$safe = array_intersect_key($safe, $t_safe);
					if(isset($safe['user_name'])){
						$safe['username'] = $safe['user_name'];
						unset($safe['user_name']);
					}
					$characters = array_intersect_key($characters, $t_player);
					if(isset($characters['exp'])){
						$characters['exp'] = (int) $characters['exp'];
					}

					//送体力是否超过一天
					$std = array();
					//if(( (int)$val['last_give_vit_ts'] + 3600 * 24 ) < time()){
					if(date('Ymd', (int)$val['last_give_vit_ts']) < date('Ymd', time())){
						//0-未送过体力
						$std['vit_status'] = 0;
					}else{
						//1-已送过体力
						$std['vit_status'] = 1;
					}

					//添加好友状态(0-未确认, 1-已确认, 2-拒绝, 3-取消)
					if( $val['status'] == 0 ){
						$std['status'] = 0;
					}elseif( $val['status'] == 1 ){
						$std['status'] = 1;
					}elseif( $val['status'] == 2 ){
						$std['status'] = 0;
					}elseif( $val['status'] == 3 ){
						$std['status'] = 0;
					}

					$data[] = array_merge_recursive($info, $safe, $characters, $std);
				}
				unset($info, $safe, $characters);
			}
		}
        return $data;
	}

	/**
	 * 查找好友
	 *
	 * @param int    $user_id    //用户id
	 * @param string $nickname   //查找好友昵称
	 *
	 */
	public function findFriend($user_id, $nickname)
	{
		$data = array();
		//查看是否有此人
		$friend_id = User::model()->getUserId($nickname);

		if($friend_id < 1) return false;

		$info = Consumer::model()->getUserAll($friend_id);

		if(!empty($info)){
			//查看是否已经建立了好友关系
			$is_exist = UserFriend::model()->isFriend($user_id, $friend_id);
			if($is_exist)
				$data['status'] = 1;
			else
				$data['status'] = 0;

			$data['friend_id'] = (int)$friend_id;
			$data['face_url'] = $info['avatar'];
			$data['nickname'] = $info['nickname'];
			$data['username'] = $info['username'];
			$data['exp']      = $info['point'];
			$data['level']      = $info['level'];

		}else{
			return false;
		}

		return $data;
	}

	/**
	 * 用户ID 查找 极光ID
	 *
	 * @param int $user_id
	 */
	public function findRegJpush($user_id)
	{
		$con_user = Yii::app()->db_user;
		$result = $con_user->createCommand()
                    		->select('*')
                    		->from('jpush_user')
                    		->where('user_id=:UserId')
                    		->bindParam(':UserId', $user_id, PDO::PARAM_INT, 11)
                    		->queryRow();
		if(!empty($result)){
			return $result;
		}else{
			return false;
		}
	}

	/**
	 * 极光ID 查找 userID
	 *
	 * @param int $user_id
	 */
	public function findUseridJpush($registration_id)
	{
		$con_user = Yii::app()->db_user;
		$result = $con_user->createCommand()
                    		->select('*')
                    		->from('jpush_user')
                    		->where('registration_id=:Registration_id')
                    		->bindParam(':Registration_id', $registration_id, PDO::PARAM_STR, 11)
                    		->queryRow();
		if(!empty($result)){
			return $result;
		}else{
			return false;
		}
	}


	/**
	 * 保存 极光ID
	 * @param unknown $registration_id
	 * @param unknown $user_id
	 * @return number
	 */
	public function insertJpush($user_id, $registration_id, $platform){
		try{
			$con_user = Yii::app()->db_user;
			// 创建用户
			$con_user->createCommand()->insert('jpush_user',
					array('registration_id'       => $registration_id,
						'user_id'       	=> $user_id,
						'platform'       	=> $platform));
			
		}catch(Exception $e){
			error_log($e);
		}

		return $user_id;
	}

	/**
	 * 更新 极光表 极光ID
	 * @param unknown $user_id
	 */
	public function updateJpush($user_id, $registration_id, $platform){
		try{
			$con_user = Yii::app()->db_user;

			$ret = $con_user->createCommand()->update('jpush_user',
					array('registration_id' => $registration_id, 'platform' => $platform),
					'user_id=:UserId', array(':UserId' => $user_id));
		}catch(Exception $e){
			error_log($e);
			return false;
		}
		return $ret;
	}

	/**
	 * 登录更新 极光ID
	 * @param unknown $user_id
	 * @param unknown $registration_id
	 * @return unknown
	 */
	public function pushRegister($user_id, $registration_id, $platform){
        try{
            $ret = $this->findRegJpush($user_id);
            $userret = $this->findUseridJpush($registration_id);
            //判断注册 来源
            if($platform == 'IOS' || $platform == 'ios'){
            	$platform = 1;
            }else{
            	$platform = 0;
            }
            //不存在uid
            if(!$ret){
            	if(!$userret){
            		//不存在直接保存
            		$user_id = $this->insertJpush($user_id, $registration_id, $platform);
            	}else{
            		$user_transaction  = Yii::app()->db_user->beginTransaction();
            		$result = $this->updateJpush($userret['user_id'], '',$platform);
            		$user_id = $this->insertJpush($user_id, $registration_id, $platform);
            		$user_transaction->commit();
            	}
            }else{
            	//数据已经存在不需要更新
            	if($ret['registration_id'] == $registration_id && $ret['user_id'] == $user_id){
            		return array('user_id'=>$user_id);
            	}else{
            		//更新掉已有用户 极光ID
            		$user_transaction  = Yii::app()->db_user->beginTransaction();
            		$this->updateJpush($userret['user_id'], '', $platform);
            		$this->updateJpush($user_id,$registration_id, $platform);
            		$user_transaction->commit();
            	}
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return array('user_id'=>$user_id);
	}

	/**
	 * 用户表新增用户 (系统分配用户帐号)
	 *
	 * @param  string $username
	 * @param  string $password
	 * @param  string $email
	 * @param  string $mobile
	 *
	 * @return int   $user_id
	 */
	public function SysInsertUser($pass,$username = 'user'){
	    $user_id = 0;
	    try{
			$time = date("Y-m-d H:i:s");
			// 创建用户并获取$user_id
			$con_user = Yii::app()->db_user;
			// 创建用户
			$con_user->createCommand()->insert('user',
								array('user_name'           => $username,
									'password'             => $pass,
									'email'                => '',
									'status'			   => 0,
						               'regist_ts'		   => $time,
							          'regist_mac'           => $GLOBALS['__DEVICEID'],
							          'app_id'               => $GLOBALS['__APPID'],
									'last_login_ts' 	   => $time));
			$user_id = Yii::app()->db_user->getLastInsertID();
			$new_username = $username.$user_id.substr(strtotime($time),5,10);
			// 更新用户名  
			$con_user->createCommand()->update('user',
								array('user_name' => $new_username),
								'user_id=:UserId', array(':UserId' => $user_id));
			// 创建分表用户
			$table_name = sprintf('user_%02s', dechex($user_id % 256));
			$con_user->createCommand()->insert($table_name,
			array('user_id'          => $user_id,
				'user_name'         => $new_username,
				'password'          => $pass,
				'email'             => '',
				'status'			=> 0,
				'regist_ts'		=> $time,
			     'regist_mac'        => $GLOBALS['__DEVICEID'],
		          'app_id'            => $GLOBALS['__APPID'],
				'last_login_ts' 	=> $time));
			
			$face_url = Yii::app()->params['img_url_base'] . Yii::app()->params['default_head_image'];
			
		}catch(Exception $e){
			error_log($e);
			return -1;
		}
		return array('user_id'=>$user_id,'user_name'=>$new_username);
	}
	
	/**
	 * 
	 * 修改用户名与绑定邮箱
	 * 
	 * @param unknown $user_id
	 * @param unknown $email
	 * @param unknown $username
	 */
	public function SysChangeUserName($user_id,$email,$username,$password,$sys_username = 'user'){
		
		try{
			$con_user = Yii::app()->db_user;
			$table_name = sprintf('user_%02s', dechex($user_id % 256));
			
			$user_name = $con_user->createCommand()
			->select('user_name,regist_ts')
			->from($table_name)
			->where('user_id=:UID',array(':UID' => $user_id))
			->queryRow();
			if($user_name['user_name'] != $sys_username . $user_id . substr(strtotime($user_name['regist_ts']),5,10)){
				//用户只能修改系统注册的用户名
				return -4;
			}
			$result = $con_user->createCommand()
							->select('user_id')
							->from('user')
							->where('user_name=:UserName',array(':UserName' => $username))
							->queryRow();
			if($result){
				//用户名已存在不能注册
				return -2;
			}
			
			$mail_result = $con_user->createCommand()
								->select('user_id')
								->from('user')
								->where('email=:EMail',array(':EMail' => $email))
								->queryRow();
			if($mail_result){
				//邮箱已存在不能注册
				return -3;
			}
			//
			if($password != NULL){
			    $param = array('user_name'=>$username,'email'=>$email, 'password'=>$password);
			}else{
			    $param = array('user_name'=>$username,'email'=>$email);
			}
			
			//修改用户名和邮箱,密码
			$con_user->createCommand()->update($table_name,
			        $param,
			        'user_id=:UserId', array(':UserId' => $user_id));
			//修改用户名和邮箱,密码
			$con_user->createCommand()->update('user',
			        $param,
			        'user_id=:UserId', array(':UserId' => $user_id));
			//获取用户安全信息
			$user_safe_info = User::model()->getUserSafeInfo($user_id);
			//获取头像和其它信息
			$info = $this->getUserInfo($user_id);
			$data = $info;
			$data['user_id'] = (int)$user_id;
			$data['username'] = $username;
			$data['email'] = $email;
			$data['face_url'] = $info['avatar'];
			$data['isChangePW'] = $user_safe_info['password'] == '' ? 0:1;
			unset($data['avatar']);
			unset($info);
			
		}catch(Exception $e){
		    error_log($e);
		    return -1;
		}
		return $data;
	}
	
	/**
	 * 领取每日登陆奖励
	 */
    public function login_reward_result($user_id,$bag_id){
	   try{
	       $day_info = Common::model()->login_reward($user_id,$bag_id);
	       if($day_info < 0)
	       {
	           //小于0 为错误
	           return $day_info;
	       }elseif(empty($day_info)){
	           return -2;
	       }
	       if(isset($day_info['day']) && $day_info['day'] <= 7){
	           $day = $day_info['day']+1;
	       }else{
	           $day = 1;
	       }
	       
	       //获取游戏角色基本信息
	       $info = Characters::model()->getCharactersInfo($user_id);
	       $params['gold'] = $info['gold']+$day_info['gold'];
	       $params['login_alldays'] = $info['login_alldays']+1;
	       $params['login_days'] = $day-1;
	       $params['update_ts'] = date("Y-m-d h:i:s",time());
	       //更新经验 游戏人物等级字段废除 用经验值获取等级
		   
	       $params['exp'] = $info['exp']+(int)Yii::app()->params['every_login_exp'];
	       //更新等级
	       $lv = Level::model()->exp2Level($params['exp']);
	       if(!empty($lv) && strcmp($lv, $info['level']) != 0){
	           $params['level'] = $lv;
	       }
            $updateres = Characters::model()->updateCharacters($user_id,$params);
            //每日登陆 加金币日志
            $gold_params = array(
                    'user_id'=>$user_id,
                    'type'=>1,
                    'value'=>$day_info['gold'],
                    'gold'=>$info['gold']+$day_info['gold'],
					'create_ts' =>date("Y-m-d H:i:s")
            );
            Gold::model()->createGold($user_id,$gold_params);
            if($updateres == true){
                $res['log']['gold'] = $day_info['gold'];
                $res['log']['gold_after'] = $gold_params['gold'];
                $res['result'] = ($day-1).'|'.$day_info['gold'];
            }
        }catch(Exception $e){
            error_log($e);
            return -1;
        }
	   return $res;
    }
	
    /**
     *  获取翻牌赚金币 扣体力  三张卡牌 actionEarnGoldList  100 300 500
     * @param unknown $user_id
     */
    public function earn_gold_list($user_id){
        $rand_id = 0;
        try{
            $con_game = Yii::app()->db_game;
            $con_characters = Yii::app()->db_characters;
            $trans_game = $con_game->beginTransaction();
            $trans_characters = $con_characters->beginTransaction();
            $minusVit = 10;
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            //获取等级金币数
            $level = Level::model()->getLevelRow($player['level']);
            //查询体力值是否够扣体力
            if($player['vit'] < (int)$level['gold_vit']){
                return -1;
            }
            //随机取3张牌
            $arr['Card'] = Common::model()->randomNum();
            $total = 0 ;
            $total_temp = 0;
            foreach ($arr['Card'] as $k => $v){
                if($v['type'] == 2){
                    $total_temp = 1;
                    break;
                }
            }
			
            foreach ($arr['Card'] as $k => $v){
                $arr['Card'][$k]['id'] = $k+1;
                if($k == 0){
                    $j = 1;
                }
                if($v['type'] == 2){
                    $total_temp = 1;
                }
                if($v['type'] == 0){
                    $arr['Card'][$k]['gold'] = 0;
                }elseif($v['type'] == 1 && $j != 0){
                    $arr['Card'][$k]['gold'] = (int)Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['gold']*Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['gold'.$j];
                    $total = (int)Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['gold']*Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['gold'.$j];
                    $j++;
                }else{
                    $j = 0;
                    $arr['Card'][$k]['gold'] = 0;
                }
            }
            if($total_temp == 1){
                $total = 0;
            }
            //
            $earn_gold_params = array(
                            'user_id'       => $user_id,
                            'result1'       => $arr['Card'][0]['type'],
                            'result2'       => $arr['Card'][1]['type'],
                            'result3'       => $arr['Card'][2]['type'],
                            'total'         => $total,
                            'status'        => 0,
                            'create_ts'     => date("Y-m-d h:i:s",time())
                        );
           $set_id = EarnGold::model()->insertGuess($user_id, $earn_gold_params);
            //扣体力
            $params = array(
                    'vit'=>$player['vit']-(int)$level['gold_vit']
            );
            $res_c = Characters::model()->updateCharacters($user_id,$params);
            $return['log']['vit'] = (int)$level['gold_vit'];
            $return['log']['vit_after'] = (int)$params['vit'];
            $return['log']['gold'] = (int)$total;
            $return['log']['id'] = (int)$set_id;
            $arr['gold'] = (int)$total;
            $arr['set_id'] = (int)$set_id;
            $return['result'] = $arr;
            // 提交事务
            $trans_game->commit();
            // 提交事务
            $trans_characters->commit();
        }catch(Exception $e){
            error_log($e);
            $trans_game->rollback();
            $trans_characters->rollback();
            return -1;
        }
        return $return;
    }
    
    /***
     * 提交翻牌 赚金币结果   加金币
     */
    public function earn_gold_result($user_id,$set_id){
        try{
            $con_game = Yii::app()->db_game;
            $con_characters = Yii::app()->db_characters;
            $trans_game = $con_game->beginTransaction();
            $trans_characters = $con_characters->beginTransaction();
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            $table_name = sprintf('earn_gold_%02s', dechex($user_id % 256));
            $earn_gold = $con_game->createCommand()
            ->select('total,status')
            ->from($table_name)
            ->where('id=:ID',array(':ID' => $set_id))
            ->queryRow();
            if(!$earn_gold){
                //查询结果错误 没有找到翻牌
                return -1;
            }elseif($earn_gold['status'] == 1){
                //金币已经领取过不能重复领取
                return -3;
            }
            //加金币
            $params['gold'] = $player['gold']+$earn_gold['total'];
            //更新领取状态
            $con_game->createCommand()
                    ->update($table_name,
                    array('status'=>1,'create_ts'=>date("Y-m-d h:i:s",time())),
                    'id=:ID', array(':ID'=>$set_id));
			
            //更新经验 游戏人物等级字段废除 用经验值获取等级
            $params['exp'] = $player['exp']+(int)Yii::app()->params['earn_gold_exp'];
            //更新等级
            $lv = Level::model()->exp2Level($params['exp']);
            if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                $params['level'] = $lv;
            }
            Characters::model()->updateCharacters($user_id,$params);
            //翻牌 加金币记录
            $gold_params = array(
                    'user_id'=>$user_id,
                    'type'=>8,
                    'value'=>$earn_gold['total'],
                    'gold'=>$player['gold']+$earn_gold['total'],
					'create_ts' =>date("Y-m-d H:i:s")
            );
            Gold::model()->createGold($user_id,$gold_params);
            //获取角色信息
            $info = Characters::model()->getCharactersInfo($user_id);
            $user_info = array(
                    'point' => $info['point'],
                    'exp' => $info['exp'],
                    'vit' => $info['vit'],
                    'vit_time' => (int)($info['vit_time']),
                    'level' => $info['level'],
                    'gold' => $info['gold'],
                    'flowers' => $info['flowers'],
            );
            $return['log'] = array('gold'=>$earn_gold['total'], 'gold_after'=>$gold_params['gold']);
            $return['set_id'] = $set_id;
            $return['result'] = $user_info;
            
            // 提交事务
            $trans_game->commit();
            $trans_characters->commit();
        }catch(Exception $e){
            error_log($e);
            $trans_game->rollback();
            $trans_characters->rollback();
            return -2;
        }
        
        return $return;
    }
    
    /**
     * 金币买体力
     * 
     * @param unknown $user_id
     */
    public function gold_buy_vit($user_id){
        try{
            $con_characters = Yii::app()->db_characters;
            $trans_characters = $con_characters->beginTransaction();
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            $lv = Level::model()->exp2Level($player['exp']);
            $level = Level::model()->getLevelRow($lv);
            if($level['max_vit'] <= $player['vit']){
                return -3;
            }
            //金币数
            if(( $player['gold'] >= $level['buy_vit'])){
                //金币购买体力
                $param['gold'] = $player['gold']-$level['buy_vit'];
            }else{
                return -2;
            }
            $param['vit'] = $level['max_vit'];
            //更新 加体力，扣金币
            Characters::model()->updateCharacters($user_id,$param);
            //解锁女神 加金币日志
            $gold_params = array(
                    'user_id'=>$user_id,
                    'type'=>9,
                    'value'=>-$level['buy_vit'],
                    'gold'=>$player['gold']-$level['buy_vit'],
					'create_ts' =>date("Y-m-d H:i:s")
            );
            Gold::model()->createGold($user_id,$gold_params);
            // 提交事务
            $trans_characters->commit();
            //获取角色信息
            $info = Characters::model()->getCharactersInfo($user_id);
            $user_info = array(
                    'point' => (int)$info['point'],
                    'exp' => (int)$info['exp'],
                    'vit' => (int)$info['vit'],
                    'vit_time' => (int)$info['vit_time'],
                    'level' => (int)$info['level'],
                    'gold' => (int)$info['gold'],
                    'flowers' => (int)$info['flowers'],
            );
            $return['log']['gold'] = -$level['buy_vit'];
            $return['log']['gold_after'] = $gold_params['gold'];
            $return['result'] = $user_info;
        }catch(Exception $e){
            error_log($e);
            $trans_characters->rollback();
            return -1;
        }
        return $return;
    }
    
    /**
     * 软件兑换金币
     *
     * @param unknown $user_id
     */
    public function software_to_gold($user_id,$software_id){
        try{
            $con_characters = Yii::app()->db_characters;
            $trans_characters = $con_characters->beginTransaction();
            
            $table_name = sprintf('software_%02s', dechex($user_id % 256));
            $ret = $con_characters->createCommand()
            ->select('id')
            ->from($table_name)
            ->where('software_id=:ID AND status=1')
            ->bindParam(':ID', $software_id, PDO::PARAM_INT, 11)
            ->order('id DESC')
            ->queryRow();
            if($ret){
                return -2;
            }else{
                //查询软件推荐
                $software_info = Common::model()->getSoftware($software_id);
                if(!$software_info){
                    return -3;
                }
                //获取用户基本信息
                $player = Characters::model()->getCharactersInfo($user_id);
                //金币购买体力
                $param['gold'] = (int)$player['gold'] + (int)$software_info[0]['gold'];
                //更新 加金币
                Characters::model()->updateCharacters($user_id,$param);
                //解锁女神 加金币日志
                $gold_params = array(
                        'user_id'=>$user_id,
                        'type'=>10,
                        'value'=>$software_info[0]['gold'],
                        'gold'=>$param['gold'],
						'create_ts' =>date("Y-m-d H:i:s")
                );
                Gold::model()->createGold($user_id,$gold_params);
                //软件换金币记录
                $p = array(
                        'user_id'=>$user_id,
                        'software_id'=>$software_id,
                        'gold'=>$software_info[0]['gold'],
                        'status'=>1,
                );
                $con_characters->createCommand()->insert($table_name,$p);
                //提交事务
                $trans_characters->commit();
                //获取角色信息
                $info = Characters::model()->getCharactersInfo($user_id);
                $return['log']['gold'] = $software_info[0]['gold'];
                $return['log']['gold_after'] = $param['gold'];
                $return['result'] = array(
                        'point' => (int)$info['point'],
                        'exp' => (int)$info['exp'],
                        'vit' => (int)$info['vit'],
                        'vit_time' => (int)($info['vit_time']),
                        'level' => (int)$info['level'],
                        'gold' => (int)$info['gold'],
                        'flowers' => (int)$info['flowers'],
                );
            }
        }catch(Exception $e){
            error_log($e);
            $trans_characters->rollback();
            return -1;
        }
        return $return;
    }
    
    function isSetPassword($uid){
        	$con_user = Yii::app()->db_user;
        	$table_name = sprintf('user_%02s', dechex($uid % 256));
        		
        	$password = $con_user->createCommand()
                        	->select('password')
                        	->from($table_name)
                        	->where('user_id=:UID',array(':UID' => $uid))
                        	->queryScalar();
        	
        	return $password == '' ? false : true;
    }
    
    /**
     * 查询最后活跃账号，
     */
    function getLastAccount(){
        $con_user = Yii::app()->db_user;
        try{
            $data = $con_user->createCommand()
				->select('*')
				->from('user')
				->where('regist_mac =:DEVICEID AND app_id =:APP_ID ORDER BY last_login_ts DESC', 
				        array(':DEVICEID' => $GLOBALS['__DEVICEID'],':APP_ID' => $GLOBALS['__APPID']))
				->queryRow();
            return $data;
        }catch(Exception $e){
            error_log($e);
        }        
    }
}
