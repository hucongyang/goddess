<?php
class UserFriend extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 新增好友
     *
     * @param  string $username
     * @param  string $password
     * @param  string $email
     * @param  string $mobile
	 *
	 * @return int   $uid
     */
    public function insertFriend($user_id, $friend_user_id, $time){
        $user_table = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        $friend_table = sprintf('goddess_user_friend_%02s', dechex($friend_user_id % 256));
        $strtotime = strtotime($time);
        try{
            $con_friend = Yii::app()->db_friend;
            $con_friend->createCommand()
                        ->insert($user_table,
                            array('user_id'=>$user_id,
                                  'friend_user_id'=>$friend_user_id,
                                  'last_give_vit_ts' => $strtotime-86400,
                                  'status' => '0',
                                  'mess_read' => '1',
                                  'update_ts' => $time
                            ));
            $con_friend->createCommand()
                        ->insert($friend_table,
                            array('user_id'=>$friend_user_id,
                                  'friend_user_id'=>$user_id,
                                  'last_give_vit_ts' => $strtotime-86400,
                                  'status' => '0',
                                  'mess_read' => '0',
                                  'update_ts' => $time
                            ));
        }catch(Exception $e){
            error_log($e);
        }
        
        //todo:
    }

    /**
     * 更新好友状态
     *
     * @param  int   $user_id
     * @param  int   $friend_user_id
     * @param  array $status   1-已确认, 2-拒绝, 3-取消
     *
     * @return void
     */
	public function updateFriend($user_id, $friend_user_id, $status)
	{
	    try{
        		$param1 = $param2 = array();
        		$now = date("Y-m-d H:i:s");
        		if($status == 1){
        		    $param1['status']    = 1;
        		    $param1['mess_read'] = 1;
        		    $param1['update_ts'] = $now;
        		    $param2['status']    = 1;
        		    $param2['mess_read'] = 1;
        		    $param2['update_ts'] = $now;
        		}else if($status == 2){
        		    $param1['status']    = 2;
        		    $param1['mess_read'] = 1;
        		    $param1['update_ts'] = $now;
        		    $param2['status']    = 2;
        		    $param2['mess_read'] = 1;
        		    $param2['update_ts'] = $now;
        		}else{
        		    $param1['status']    = 3;
        		    $param1['mess_read'] = 1;
        		    $param1['update_ts'] = $now;
        		    $param2['status']    = 3;
        		    $param2['mess_read'] = 1;
        		    $param2['update_ts'] = $now;
        		}
        		$con_friend = Yii::app()->db_friend;
        		$user_table = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        		$friend_table = sprintf('goddess_user_friend_%02s', dechex($friend_user_id % 256));
        		
        		$con_friend->createCommand()
        		                    ->update($user_table,
        		                    $param1, 'user_id=:user_id AND friend_user_id=:friend_user_id',
        		                     array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id));
        		
        		$con_friend->createCommand()
        		                    ->update($friend_table,
        		                    $param2, 'user_id=:friend_user_id AND friend_user_id=:user_id',
        		                     array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id));
		}catch(Exception $e){
		    error_log($e);
		    return false;
		}
	}
	
	/**
	 * 更新好友状态
	 *
	 * @param  int   $user_id
	 * @param  int   $friend_user_id
	 * @param  array $status   1-已确认, 2-拒绝, 3-取消
	 *
	 * @return void
	 */
    public function updateFriend2($user_id, $friend_user_id, $param1, $param2)
    {
        try{
            $con_friend = Yii::app()->db_friend;
            $user_table = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
            $friend_table = sprintf('goddess_user_friend_%02s', dechex($friend_user_id % 256));
            
            $con_friend->createCommand()
            ->update($user_table,
            		$param1, 'user_id=:user_id AND friend_user_id=:friend_user_id',
            		array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id));
            
            $con_friend->createCommand()
            ->update($friend_table,
            		$param2, 'user_id=:friend_user_id AND friend_user_id=:user_id',
            		array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }
	

    /**
     * 送体力值
     */
    public function updateFriendRow($friend_user_id, $user_id, $param){
        try{
            $con_friend = Yii::app()->db_friend;
            $user_table = sprintf('goddess_user_friend_%02s', dechex($friend_user_id % 256));

            $con_friend->createCommand()
                        ->update($user_table,
                        $param, 'user_id=:friend_user_id AND friend_user_id=:user_id',
                         array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return true;
    }


    /**
     * 获取送好友体力时间
     */
    public function getVitTime($friend_user_id, $user_id){
        $time = false;
        try{
            $table_name = sprintf('goddess_user_friend_%02s', dechex($friend_user_id % 256));
            $con_friend = Yii::app()->db_friend;
            $time = $con_friend->createCommand()
                        ->select('last_give_vit_ts')
                        ->from($table_name)
                        ->where('user_id=:friend_user_id AND friend_user_id=:user_id', array(':user_id'=>$user_id, ':friend_user_id'=>$friend_user_id))
                        ->queryScalar();
        }catch(Exception $e){
            error_log($e);
        }
        return $time;
    }

    /**
     * 获得每日赠送体力数量
     */
    public function getUerFriendNum($user_id){
        $count = false;
        try{
            $table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
            $con_friend = Yii::app()->db_friend;
            $count = $con_friend->createCommand()
            ->select('count(*) as c')
            ->from($table_name)
            ->where('user_id=:USER_ID AND status = 1',
                    array(':USER_ID'=>$user_id))
                    ->queryScalar();
        }catch(Exception $e){
            error_log($e);
        }
        return $count;
    }
    
    /**
     * 获得每日赠送体力数量
     */
    public function getGiveVitNum($user_id){
        $count = false;
        try{
            $begin_time = mktime(0,0,0,date("m"),date("d"),date("Y"));
            $end_time = mktime(23,59,59,date("m"),date("d"),date("Y"));
            
            $table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
            $con_friend = Yii::app()->db_friend;
            $count = $con_friend->createCommand()
                                ->select('count(*) as c')
                                ->from($table_name)
                                ->where('user_id=:USER_ID AND (last_give_vit_ts BETWEEN '.$begin_time.' AND '.$end_time.') AND status = 1', 
                                        array(':USER_ID'=>$user_id))
                                ->queryScalar();
        }catch(Exception $e){
            error_log($e);
        }
        return $count;
    }
    
    /**
     * 获取用户好友
     *
     * @param  int   $user_id    //用户id
     *
     * @return array $data       //数组 好友id
     */
    public function selectFriend($user_id)
    {
		$user_id = intval($user_id);
		$data = array();
		$table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        try{
            $con_friend = Yii::app()->db_friend;
            $data = $con_friend->createCommand()
                            ->select('friend_user_id, last_give_vit_ts, status')
                            ->from($table_name)
                            ->where("user_id=:user_id AND status='1'", array(':user_id' => $user_id))
                            ->queryAll();
        }catch(Exception $e){
            error_log($e);
        }

		return $data;
    }

    /**
     * 是否是好友关系
     *
     * @param  int $user_id
     * @param  int $friend_user_id
     *
     * @return boolean
     */
    public function isFriend($user_id, $friend_user_id)
    {

		$status = false;
        $table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        try{
            $con_friend = Yii::app()->db_friend;
            $status = $con_friend->createCommand()
                            ->select('status')
                            ->from($table_name)
                            ->where('user_id = :user_id AND friend_user_id =:friend_user_id')
                            ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                            ->bindParam(':friend_user_id', $friend_user_id, PDO::PARAM_INT, 11)
                            ->queryScalar();
        }catch(Exception $e){
            error_log($e);
        }
        return $status;
    }
	
    /**
     * 获取和好友的状态
     */
    public function getFriendRelation($user_id, $friend_user_id){
        $table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        try{
            $con_friend = Yii::app()->db_friend;
            $info = $con_friend->createCommand()
                                ->select('status, mess_read')
                                ->from($table_name)
                                ->where('user_id = :user_id AND friend_user_id =:friend_user_id')
                                ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                                ->bindParam(':friend_user_id', $friend_user_id, PDO::PARAM_INT, 11)
                                ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }
        
        if(!$info){
            return  0; //没关系
        }elseif($info['status'] == 0 && $info['mess_read'] == 1){
            return 1; //已经请求过
        }elseif($info['status'] == 0 && $info['mess_read'] == 0){
            return  2; //对方请求你了
        }elseif($info['status'] == 1){
            return 3; //已经是好友了
        }else{
            return 4; //取消或拒绝
        }
    }
    
    
    
    /**
     * 新好友通知
     *
     * @param int $user_id
     *
     */
    public function newFriendMess($user_id)
    {
        $table_name = sprintf('goddess_user_friend_%02s', dechex($user_id % 256));
        $ret = false;
        try{
            $con_friend = Yii::app()->db_friend;
            $ret = $con_friend->createCommand()
                            ->select('friend_user_id, update_ts')
                            ->from($table_name)
                            ->where('user_id=:user_id AND status = 0 AND mess_read = 0')
                            ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                            // ->getText();
                            ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $ret;
    }
}