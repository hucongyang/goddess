<?php
class Message extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 女神的消息
     *
     * @param int $user_id
     * @param int $heroin_id
     *
     * @param array
     */
    public function goddessMess($user_id, $heroine_id)
    {

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

        $info = false;
        try{
            $con_message = Yii::app()->db_message;
            $info = $con_message->createCommand()
                                    ->select('m_id')
                                    ->from($table_name)
                                    ->where("user_id=:user_id AND heroine_id=:heroine_id AND type=0")
                                    ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                                    ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                                    ->queryColumn();
        }catch(Exception $e){
            error_log($e);
        }


      return $info;
    }

    /**
     * 未读女神的消息
     *
     * @param int $user_id
     * @param int $goddess_id
     *
     * @param array
     */
    public function noReadGoddessMessList($user_id, $goddess_id)
    {
	    	$table_name = sprintf('user_message_%02s', dechex($user_id % 256));

	    	$info = false;
	    	try{
	    		$con_message = Yii::app()->db_message;
	    		$info = $con_message->createCommand()
	    		->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url,msg_image')
	    		->from($table_name)
	    		->where("user_id=:user_id AND heroine_id=:heroine_id AND type=0")
	    		->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
	    		->bindParam(':heroine_id', $goddess_id, PDO::PARAM_INT, 11)
	    		->queryAll();
	    	}catch(Exception $e){
	    		error_log($e);
	    	}
	    	return $info;
    }

    /**
     * 已读和删除的女神的消息
     *
     * @param int $user_id
     * @param int $heroin_id
     *
     * @param array
     */
    public function readGoddessMessList($user_id, $heroine_id)
    {
	    $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

	    	$info = false;
	    	try{
	    		$con_message = Yii::app()->db_message;
	    		$info = $con_message->createCommand()
	    		->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url')
	    		->from($table_name)
	    		->where("user_id=:user_id AND heroine_id=:heroine_id AND type !=0")
	    		->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
	    		->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
	    		->queryAll();
	    	}catch(Exception $e){
	    		error_log($e);
	    	}
	    	return $info;
    }



    /**
     * 用户新消息
     *
     * @param int $user_id
     *
     * @return array
     */
    public function newMessageRow($user_id)
    {

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

        $info = false;
        try{
            $con_message = Yii::app()->db_message;
            $info = $con_message->createCommand()
                                  ->select('m_id, heroine_id, type, create_ts')
                                  ->from($table_name)
                                  ->where("user_id=:user_id AND type=0")
                                  ->order('m_id DESC')
                                  ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                                  ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }

        return $info;
    }

    /**
     * 用户消息列表  未读消息
     *
     * @param int $user_id
     * @param     $time
     */
    public function noReadMessage($user_id, $time=null)
    {
        $param = '';

        if($time){
            $param = "AND create_ts>='$time'";
        }

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

        $info = false;
        try{
            $con_message = Yii::app()->db_message;
            $info = $con_message->createCommand()
                                  ->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url')
                                  ->from($table_name)
                                  ->where("user_id=:user_id AND type = 0 $param")
                                  ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                                  ->order('create_ts DESC')
                                  ->queryAll();
        }catch(Exception $e){
            error_log($e);
        }

        return $info;
    }

    /**
     * 重新拼装 用户消息列表  未读消息
     * @param unknown $messagelist
     * @return number
     */
    public function newMessageList($info, $user_id){
    		$result = array();
    		if($info){
		    	//排序
		    	sort($info);
		    	// 按照女神归类
		    	foreach ($info as $ival) {
		    		$tmp[$ival['heroine_id']][] = $ival;
		    	}
		    	// 女神逐个封装
		    	foreach ($tmp as $tid => $tval) {
		    		$message = array();
		    		$ginfo = array();
		    		$heroineInfo  = array();
		    		//获取未读消息 m_id, user_message_%02s 主键
		    		foreach ($tval as $mid) {
		    			$message[] = $mid['m_id'];
		    		}
		    		//女神的详细信息
		    		$heroineInfo = Goddess::model()->getGoddessInfo($tid);
		    		//未读总数
		    		$count = count($tval);
		    		//弹出最后一条信息
		    		$pop = array_pop($tval);

		    		//获取最后一条信息的详细内容
		    		$lastInfo = Message::model()->userMessRow($user_id, $pop['m_id']);
		    		$ginfo['goddess_id']     = $tid;                       #女神id
		    		$ginfo['goddess_name']   = $heroineInfo['nickname'];           #女神名字
		    		$ginfo['goddess_face']   = $heroineInfo['faceurl'];            #头像地址
		    		$ginfo['last_text']      = $lastInfo['msg_text'];               #留言内容
		    		$ginfo['last_time']      = $pop['create_ts'];                   #时间
		    		$ginfo['unread_num']     = $count;

		    		$result[] = $ginfo;
		    	}
    		}
	    	return $result;
    }


    /**
     * 女神留言
     *
     * @param  int $herione_id
     *
     * @return array $info
     */
    public function goddessMessRow($heroine_id)
    {
        $info = false;
        try{
            $con_message = Yii::app()->db_heroine;
            $info = $con_message->createCommand()
                                  ->select('mess_id, content, url, heroine_id, create_ts, msg_text')
                                  ->from('heroine_message')
                                  ->where("heroine_id=:heroine_id")
                                  ->order('mess_id DESC')
                                  ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                                  ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }

        return $info;
    }




    /**
     * 获取消息详情
     *
     */
    public function userMessRow($user_id, $mess_id)
    {

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));
        $info = false;
        try{
            $con_message = Yii::app()->db_message;
            $info = $con_message->createCommand()
                          ->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url')
                          ->from($table_name)
                          ->where("m_id=:mess_id")
                          ->bindParam(':mess_id', $mess_id, PDO::PARAM_INT, 11)
                          ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }

        return $info;
    }

    /**
     * 获取用户消息内容 拼接详细消息体
     *
     * @param unknown $user_id
     * @param unknown $mess_id
     * @return unknown
     */
    public function	getUserMessInfo($user_id, $mess_id)
    {

	    $table_name = sprintf('user_message_%02s', dechex($user_id % 256));
	    $info = false;
	    try{
		    $con_message = Yii::app()->db_message;
		    $info = $con_message->createCommand()
		    ->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url, msg_image')
		    ->from($table_name)
		          ->where("m_id=:mess_id")
		    		->bindParam(':mess_id', $mess_id, PDO::PARAM_INT, 11)
		    		->queryRow();

		    $ret = Goddess::model()->getGoddessInfo($info['heroine_id']);
		    $dateTime = date('Y-m-d H:i:s', time());
		    $data['goddess_face'] = $ret['faceurl'];
		    $data['goddess_name'] = $ret['nickname'];
		    $data['goddess_id'] = $info['heroine_id'];
		    $data['time'] = $dateTime;
		    $data['url'] = $info['msg_url'];
		    $data['image'] = $info['msg_image'];
		    $data['goddess_id'] = (int)$info['heroine_id'];
		    $data['text'] = $info['msg_text'];
		    $data['type'] = (int)$info['msg_type'];
		    $data['id'] = (int)$info['m_id'];
	    	    $follow = Follow::model()->getFollowRow($user_id, $info['heroine_id']);
	    		if($follow){
	    			$data['followed'] = 1;
	    		}else{
	    			$data['followed'] = 0;
	    		}
	    }catch(Exception $e){
	    error_log($e);
	    }

	    return $data;
    }

    /**
     * 清空女神留言
     *
     * @param  int $user_id
     * @param  int $heroine_id
     *
     * @return boolean
     */
    public function delGoddessMess($user_id, $heroine_id)
    {

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

        $ret = false;
        try{
            $con_message = Yii::app()->db_message;
            $ret = $con_message->createCommand()
                                    ->update($table_name, array('type' => '2'), 'user_id=:user_id AND heroine_id=:heroine_id', 
                                    		array(':user_id'=>$user_id, ':heroine_id'=>$heroine_id));
        }catch(Exception $e){
            error_log($e);
        }


        return $ret === false ? false : true;
    }
	
    /**
     * 更新所有留言为未读
     *
     * @param  int $user_id           //用户id
     *
     * @return boolean
     */
    public function upallMessType($user_id)
    {
    
    	$table_name = sprintf('user_message_%02s', dechex($user_id % 256));
    
    	$ret = false;
    	try{
    		$con_message = Yii::app()->db_message;
    		$ret = $con_message->createCommand()
    		->update($table_name,
    				array('type' => 0),
    				'user_id=:user_id ',
    				array(':user_id'=>$user_id));
    	}catch(Exception $e){
    		error_log($e);
    	}
    
    	return $ret === false ? false : true;
    }
    
    /**
     * 更新留言 已读 未读
     *
     * @param  int $user_id           //用户id
     * @param  int $mess_id           //主键
     * @param  int $type              //0-已读 1-未读 2-删除
     *
     * @return boolean
     */
    public function updateMessType($user_id, $mess_id, $type)
    {
        if($type != 0 && $type != 1 && $type != 2) return false;

        $table_name = sprintf('user_message_%02s', dechex($user_id % 256));

        $ret = false;
        try{
            $con_message = Yii::app()->db_message;
            $ret = $con_message->createCommand()
                            ->update($table_name,
                                        array('type' => $type),
                                        'user_id=:user_id AND m_id=:mess_id',
                                        array(':user_id'=>$user_id,
                                              ':mess_id'=>$mess_id));
        }catch(Exception $e){
            error_log($e);
        }

        return $ret === false ? false : true;
    }

    
    
    /**
     * 获取女神消息
     *
     * @param  int $mess_id
     *
     * @return array $info
     */
    public function getGoddessMessRow($mess_id)
    {
        $data = array();
        $info = false;
        try{
        	$con_message = Yii::app()->db_heroine;
        	$info = $con_message->createCommand()
        	->select('mess_id, title, content, url, type, heroine_id, create_ts, image')
        	->from('heroine_message')
        	->where('mess_id = :mess_id', array(':mess_id' => $mess_id))
        	->queryRow();
        	if(empty($info))
        	{
        		return array();
        	}
        	$ret = Goddess::model()->getGoddessInfo($info['heroine_id']);
        	$data['goddess_face'] = $ret['faceurl'];
        	$data['goddess_name'] = $ret['nickname'];
        	$data['goddess_id'] = $info['mess_id'];
        	$data['time'] = time();
        	$data['url'] = $info['url'];
        	$data['image'] = $info['image'];
        	$data['goddess_id'] = $info['heroine_id'];
        	$data['text'] = $info['content'];
        	$data['type'] = $info['type'];
        	$data['id'] = $info['mess_id'];
        }catch(Exception $e){
        	error_log($e);
        }
        return $data;
    }



    /**
     * 获取女神剧情消息模板
     *
     * @param unknown $heroine_id
     * @param number $push_type			 0.其他  1 陌生打招呼  2关注女神   3好感升级   4送礼物   5剧情
     * @return boolean
     */
    public function getGoddessMessTemplate($heroine_id, $push_type = 1)
    {
	    	$info = false;
	    	try{
	    			$con_message = Yii::app()->db_heroine;
	    			$info = $con_message->createCommand()
	    			->select('mess_id, content, url, heroine_id, create_ts, type, msg_id')
	    			->from('heroine_message')
	    			->where("heroine_id=:heroine_id AND push_type=:push_type")
	    			->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
	    			->bindParam(':push_type', $push_type, PDO::PARAM_INT, 11)
	    			->queryAll();
	    	}catch(Exception $e){
	    		error_log($e);
	    	}

	    	return $info;
    }
    
	/**
	 * 
	 * 女神好感值到多少直接发送 剧情消息
	 * 
	 * @param unknown $heroine_id
	 * @param unknown $liking
	 */
	public function getGoddessMessLiking($user_id,$heroine_id,$liking,$push_type)
	{

		$info = false;
		try{
		    $con_heroine = Yii::app()->db_heroine;
		    $con_message = Yii::app()->db_message;
		    $table_name = sprintf('user_plot_log_%02s', dechex($user_id % 256));
		    $sql = "SELECT liking FROM `user_plot_log` 
		    				where heroine_id=$heroine_id AND user_id=$user_id  
		    				ORDER BY liking DESC";
		    $polt_log_data = $con_message->createCommand($sql)->queryRow();
		    if($polt_log_data){
			    $liking_end = $polt_log_data['liking'];
			    $sql_msg = "SELECT mess_id, content, url, heroine_id, create_ts, type, liking, msg_id 
					    FROM `heroine_message`
					    where heroine_id=$heroine_id AND liking<=$liking AND liking>$liking_end AND push_type=$push_type
					    ORDER BY liking DESC";
			    $msg_data = $con_heroine->createCommand($sql_msg)->queryAll();
		    }else{
				$sql_msg = "SELECT mess_id, content, url, heroine_id, create_ts, type, liking, msg_id
						    	FROM `heroine_message`
						    	where heroine_id=$heroine_id AND liking<=$liking AND push_type=$push_type
						    	ORDER BY liking DESC";
				$msg_data = $con_heroine->createCommand($sql_msg)->queryAll();
		    }
		    if($msg_data){
			    	$time = mktime(date("H"),date("i")+1,date("s"),date("m"),date("d"),date("Y"));
			    	$send_time = date("Y-m-d H:i:s",	$time);
		    		//循环插入 队列  推送LOG表
		    		foreach ($msg_data as $k => $v){
		    			$con_message->createCommand()->insert('user_plot_log',
		    			        array('user_id'         		=> $user_id,
		    			                'heroine_id'        	=> $heroine_id,
		    			                'mess_id'			=> $v['mess_id'],
		    			                'liking'				=> $v['liking']
		    			        ));
		    			
		    			$res[$k] = $this->insertUserGoddessMess($user_id,$heroine_id, $v, $push_type,1,$send_time);
		    		}
		    }else{
		     return $res = -3;
		    }
		}catch(Exception $e){
		    error_log($e);
		    return $res = -2;
		}
		
		return $res;
	}
	
	
    /**
     * 保存用户女神消息到消息队列
     *
     * @param unknown $user_id
     * @param unknown $msg
     * @param number $level
     * @param number $send_time
     * @return unknown
     */
    public function insertUserGoddessMess($user_id,$goddess_id, $msg, $type, $level = 0, $send_time = 0){
        
        $con_message = Yii::app()->db_message;
        $message_transaction  = Yii::app()->db_message->beginTransaction();
        try{
                //极光ID
                $registration_arr = User::model()->findRegJpush($user_id);
                if($registration_arr){
                    $registration_id = $registration_arr['registration_id'];
                    $mess_id = $msg['mess_id'];
                    $time = strtotime($send_time);
                    //查询女神信息
                    $info = Goddess::model()->getGoddessInfo($goddess_id);
                    $content = sprintf($msg['content'], $info['nickname']);
                    $content = $msg['content'];
                    //获得某用户某女神  未关注  图片ID列表 随机图片ID
                    $photoinfo = Photo::model()->getNoFollowPhotoInfo($user_id,$goddess_id,$level);
                    
                    if($photoinfo){
                        $msgcontent['image'] = $photoinfo['url'];
                        //$type 2-audio  3-media 时 url 有
                        if($msg['type'] == 2 || $msg['type'] == 3){
                            $msgcontent['url'] = Yii::app()->params['img_url_base'] . $msg['url'];
                            $msg_id = $msg['msg_id'];
                        }else{
                            $msgcontent['url'] = '';
                            $msg_id = $photoinfo['photo_id'];
                        }
                        //保存消息到队列 
                        // 创建消息队列
                        $con_message->createCommand()->insert('message_queue',
                        		array('user_id'         		=> $user_id,
                        				'send_time'        	=> $time,
                        				'msg_text'		=> $content,
                        				'msg_type'		=> $msg['type'],
                        				'msg_image'		=> $photoinfo['url'],
                        				'msg_url'			=> $msgcontent['url'],
                        		          'msg_id'			=> $msg_id,
                        				'msg_goddess_id'	=> $goddess_id,
                        				'type'			=> $type,
                        				'status'			=> 0
                        		));
                    //记录推送消息
                    $res = Yii::app()->db_message->getLastInsertID();
		    	    }else{
		    	    	//没有获得随机推送照片
		    	    	$res = -3;
		    	    }
			    $message_transaction->commit();
		    	}else{
                    //未查到用户极光ID  不能发送
                    $res = -1;
                    $message_transaction->commit();
		    	}
	    	}catch(Exception $e){
	    		error_log($e);
	    		//SQL错误
	    		return $res = -2;
	    	}
    		return $res;
    	}

    	
    	/**************************
    	 * 消息读取，将消息内的图片，声音，视屏 和相应的表关联
    	 * 
    	 * 
    	 * 
    	 **************************/
    	public function readMess($user_id, $mess_id){
    	    $table_name = sprintf('user_message_%02s', dechex($user_id % 256));
    	    $info = false;
    	    $res = '';
    	    try{
    	        $con_message = Yii::app()->db_message;
    	        $info = $con_message->createCommand()
    	        ->select('m_id, heroine_id, type, create_ts, msg_text, msg_type, msg_url, msg_id')
    	        ->from($table_name)
    	        ->where("m_id=:mess_id AND type = 0")
    	        ->bindParam(':mess_id', $mess_id, PDO::PARAM_INT, 11)
    	        ->queryRow();
    	        if($info){
        	        //image 解锁图片 根据图片类型，解锁图片或推送图片
        	        if($info['msg_type'] == 1){
        	            $photo_type = Photo::model()->selectPhotoType($info['msg_id']);
        	            $photo_type = $photo_type['type'];
        	            $photoParams = array('user_id'=>$user_id,
        	                    'heroine_id'=>$info['heroine_id'],
        	                    'photo_id'=>$info['msg_id'],
        	                    'unlock_type'=>3,
        	                    'status'=>0,
        	                    'is_open' => 1,
        	                    'type' => $photo_type,
        	                    'timestamp'=>time());
        	            
        	            //插入解锁照片
        	            Photo::model()->insertPhoto($user_id, $photoParams);
        	            
        	        //audio 预留
        	        }elseif($info['msg_type'] == 2){
        	            
        	        //media 预留   
        	        }elseif($info['msg_type'] == 3){
        	            
        	        }
        	        
        	        
    	        }else{
    	        //没有此消息
    	            return -1;
    	        }
    	    }catch(Exception $e){
            error_log($e);
            //SQL错误
    	    }
    	    return $res;
    	}
    
}
