<?php
class JPush extends CActiveRecord
{
	public $master_secret = '';
	public $app_key = '';
	
    public static function db()
    {
        return Yii::app()->db_common;
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    
    /**
     * 关注推送 		延后1分钟推送
     * 根据用户ID 	随机取 1级 未解锁照片ID
     * 女神ID
     * 等级
     * 
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param number $level
     * @return unknown
     */
    public function followPush($user_id,$goddess_id,$level = 0){
	    	$push_type = 2;
	    	$result = '';
	    	//获取消息体模板
	    	$msg = Message::model()->getGoddessMessTemplate($goddess_id,$push_type);
	   	if($msg){
		    	$time = mktime(date("H"),date("i")+1,date("s"),date("m"),date("d"),date("Y"));
		    	$send_time = date("Y-m-d H:i:s",	$time);
		    	$res = Message::model()->insertUserGoddessMess($user_id,$goddess_id,$msg[0],$push_type,$level,$send_time);
	    		switch ($res){
				case -1 : $result = 'no jpush registration_id'; break;
				case -2 : $result = 'sql err'; break;
				case -3 : $result = 'sql photo'; break;
				default: $result = $res; break;;
			}
	   	}
// 		return $result;
    }
    
    /**
     * 礼物推送		延迟一分钟推送
     * 根据用户ID 	随机取未解锁照片ID
     *
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @return unknown
     */
	public function giftPush($user_id, $goddess_id){
		$push_type = 4;
		$result = '';
		//获取消息体模板
		$msg = Message::model()->getGoddessMessTemplate($goddess_id,$push_type);
		if($msg){
			$time = mktime(date("H"),date("i")+1,date("s"),date("m"),date("d"),date("Y"));
			$send_time = date("Y-m-d H:i:s",	$time);
			//根据用户 和女神 获得好感等级
			$level = 0;
			$res = Message::model()->insertUserGoddessMess($user_id,$goddess_id,$msg[0],$push_type,$level,$send_time);
			switch ($res){
				case -1 : $result = 'no jpush registration_id'; break;
				case -2 : $result = 'sql err'; break;
				default: $result = $res; break;;
			}
			
			return $result;
		}
		
	}
    
    /**
     * 好感推送	好感值到多少   	延后1分钟推送
     * 
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param int $liking			//好感到多少推迟1分钟推送消息
     * 
     * @return unknown
     */
    	public function likePush($user_id,$goddess_id,$liking){
    		$push_type = 5;
    		//
	    	//根据好感值比好感值低的剧情消息体模板
	    	$res = Message::model()->getGoddessMessLiking($user_id,$goddess_id,$liking,$push_type);
   		switch ($res){
			case -1 : $result = 'no jpush registration_id'; break;
			case -2 : $result = 'sql err'; break;
			case -3 : $result = 'NO push msg'; break;
			default: $result = $res; break;;
		}
		return $result;
    	}

    	/**
    	 * 周推送  C做
    	 * 256张user / 9-24点
    	 * 随机推送1个陌生女神
    	 *
    	 * @param unknown $user_id
    	 * @param number $level
    	 * @return unknown
    	 */
    	/* public function weekPush($user_id,$level = 1){
    	    $type = 4;
    	    $push_type = 1;
    	    //获取用户所有未关注的女神id
    	    $id_list = Follow::model()->getNoFollowIds($user_id);
    	    //随机取女神ID
    	    $goddess_id = Common::model()->randData($id_list);
    	
    	    $goddess_id = 1;
    	    //获取消息体模板
    	    $msg = Message::model()->getGoddessMessTemplate($goddess_id,$type);
    	    $send_time = date("Y-m-d H:i:s");
    	    $m_id = Message::model()->insertUserGoddessMess($user_id,$goddess_id,$msg[0],$type,1,$send_time);
    	    return $m_id;
    	} */
    
    /**
     * 推送消息 广播  
     *
     * @param  int   	$master_secret			//秘钥
     * @param  int   	$app_key 				//app_key
     * @param  string	$title 				//推送消息标题
     * @param  int		$msgid 				//女神消息ID
     * @param  string	$RegistrationID 		//用户手机标识
     * @param  string	$extras 				//扩展消息json
     * 						{"id":"消息ID","type":"消息类型","text":"消息文本","image":"消息图片",
     * 							"url":"链接","time":"消息事件","goddess_id":"女神ID",
     * 							"goddess_face":"女神头像","goddess_name":"女神名称"}
     * 				$type		2、指定的 tag。3、指定的 alias。4、广播：对 app_key 下的所有用户推送消息。 5 RegistrationID
     * @return array 	$ret  				//返回
     */
    public function pushAllMsg($title, $msgid, $RegistrationID)
    {
		$this->master_secret = Yii::app()->params[10]['master_secret'];
	    	$this->app_key = Yii::app()->params[10]['app_key'];
    	
	    	$client = new JPushClient($this->app_key,$this->master_secret);
	    	
	    	$params = array("receiver_type" => 4,
	    			"receiver_value" => $RegistrationID,
	    			"sendno" => $msgid,
	    			"send_description" => "",
	    			"override_msg_id" => "");
	    	$heroine_message = Message::model()->getGoddessMessRow($msgid);
	    	$ret = $client->sendNotification($title, $params, $heroine_message);
        	return $ret;
    }
    
    /**
     * 发送消息
     * 
     * @param unknown $title
     * @param unknown $content
     * @param unknown $msgid
     * @param unknown $RegistrationID
     * @param unknown $extras
     * @param number $type	0 安卓 1 ios
     * @return Ambigous <MessageResult, unknown>
     */
    public function PushMsg($title,$content, $msgid, $RegistrationID, $extras, $type = 0)
    {
	    	Yii::import('application.extensions.jpush.*');
	    	require_once('JPushClient.php');
	    	
	    	$this->master_secret = Yii::app()->params[11]['master_secret'];
	    	$this->app_key = Yii::app()->params[11]['app_key'];
	    	if($type == 1){
	    		$client = new JpushClient($this->app_key,$this->master_secret,0,'ios',false);
	    		$params = array("receiver_type" => 5,
	    		        "receiver_value" => $RegistrationID,
	    		        "sendno" => $msgid,
	    		        "send_description" => "",
	    		        "override_msg_id" => "");
	    		$ret = $client->sendNotification($title,  $params, $extras);
	    	}else{
	    		$client = new JPushClient($this->app_key,$this->master_secret,0,'android',false);
	    		$params = array("receiver_type" => 5,
	    		        "receiver_value" => $RegistrationID,
	    		        "sendno" => $msgid,
	    		        "send_description" => "",
	    		        "override_msg_id" => "");
	    		$ret = $client->sendCustomMessage($title,$content, $params, $extras);
	    	}
	    	
// 	    	
// 	    	
	    	
	    	return $ret;
    }
    
    
}