<?php
class Follow extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 关注女神 插入一条数据
     * @param datetime $time
     * @param int      $user_id
     * @param int      $heroine_id
     */
    public function insertFollow($time, $user_id, $heroine_id, $status = 0,$followed = 1)
    {

        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $con_characters->createCommand()->insert('follow',
                    array('user_id'        => $user_id,
                            'heroine_id'     => $heroine_id,
                            'followed'       => $followed,
                            'praised'        => 0,
                            'liking'         => 0,
                            'create_ts'      => $time,
                            'last_update_ts' => $time,
                            'status'         => $status,
                    ));
            $ret = $con_characters->createCommand()->insert($table_name,
                                array('user_id'        => $user_id,
                                      'heroine_id'     => $heroine_id,
                                      'followed'       => $followed,
                                      'praised'        => 0,
                                      'liking'         => 0,
                                      'create_ts'      => $time,
                                      'last_update_ts' => $time,
                                      'status'         => $status,
            ));
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return true;
    }

    /**
     * 更新关注表 goddess_common.follow
     *  
     * @param int $time
     * @param int $user_id
     * @param int $heroine_id
     * @param int $followed		关注
     * @param int $praised		赞
     * @param int $liking		送礼物 好感值
     * @return boolean
     */
    public function updateFollow($time, $user_id, $heroine_id, $followed=null, $praised=null, $liking=null)
    {
        $con_characters = Yii::app()->db_characters;

        if(isset($followed)){
            $type = 1;
        }else if(isset($praised)){
            $type = 2;
        }else if(isset($liking)){
            $type = 3;
        }

        $condition = NULL;
        $param = NULL;
        switch($type)
        {
            case 1: $condition ='followed';
                    $param = $followed;
                    break;
            case 2: $condition ='praised';
                    $param = $praised;
                    break;
            case 3: $condition ='liking';
                    $linking_info = Liking::model()->getMaxLikingLvRow();
                    if($liking > $linking_info['max']){
                        $liking = $linking_info['max'];
                    }
                    $param = $liking;
					if($GLOBALS['__APPID'] == 10){
						//增加好感值触发剧情推送
						JPush::model()->likePush($user_id,$heroine_id,$liking);
					}
                    break;
            default : return -1;
        }

        try{
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $con_characters->createCommand()->update('follow',
                    array( $condition => $param),
                    'user_id=:UserId AND heroine_id=:heroine_id', 
                    array(':UserId' => $user_id, ':heroine_id'=> $heroine_id));
            $ret = $con_characters->createCommand()->update($table_name,
                        array( $condition => $param),
                        'user_id=:UserId AND heroine_id=:heroine_id', 
                        array(':UserId' => $user_id, ':heroine_id'=> $heroine_id));
            
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return true;
    }

    /**
     * 更新关注行
     * updateFollow 函数一样 参数修改为数组更新  $param
     * 
     * @param int $user_id
     * @param int $heroine_id
     * @param array $param
     */
    public function updateFollowRow($user_id, $heroine_id, $param){
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
			if(isset($param['liking'])){
				$linking_info = Liking::model()->getMaxLikingLvRow();
				if($param['liking'] > $linking_info['max']){
					$param['liking'] = $linking_info['max'];
				}
			}
            $con_characters->createCommand()->update('follow',
                    $param,
                    'user_id=:UserId AND heroine_id=:heroine_id', array(':UserId' => $user_id, ':heroine_id'=> $heroine_id));
            $con_characters->createCommand()->update($table_name,
                        $param,
                        'user_id=:UserId AND heroine_id=:heroine_id', array(':UserId' => $user_id, ':heroine_id'=> $heroine_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }

    /**
     * 获取用户和女神关系信息
     *
     * @param int $user_id
     * @param int $heroine_id
     */
    public function getFollowRow($user_id, $heroine_id)
    {
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                ->select('follow_id, user_id, heroine_id, followed, praised, liking, unlock_counts, create_ts, last_update_ts, status')
                ->from($table_name)
                ->where('user_id=:user_id AND heroine_id=:heroine_id')
                ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                ->queryRow();
        }catch(Exception $e){
            return false;
        }
        return $data;
    }

    /**
     * 我的女神列表 我关注的女神
     *
     * @param int $user_id
     * @param int $page
     * @param int $limit
     *
     */
    public function myGoddess($user_id, $page=null, $limit=null)
    {
        // $user_id    = intval($user_id);
        // $page       = intval($page);
        // $limit      = intval($limit);

        // $start = ($page-1)*$limit;
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                        ->select('heroine_id, followed, unlock_counts, praised, liking, create_ts')
                        ->from($table_name)
                        ->where('user_id = :user_id')
                        ->order('create_ts DESC')
                        //->limit($limit, $start)
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }

    /**
     *  按时间取我的女神
     * @param unknown $user_id
     * @param unknown $timestamp
     * @param unknown $limit
     * @return boolean|unknown
     */
    public function popMyGoddess($user_id, $timestamp, $limit){
        $user_id    = intval($user_id);
        $create_ts  = date('Y-m-d H:i:s', $timestamp);
        $limit      = intval($limit);

        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                        // ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        // ->bindParam(':create_ts', $create_ts)
                        ->select('heroine_id, followed, unlock_counts, praised, liking ,create_ts')
                        ->from($table_name)
                        ->where("user_id ='$user_id' AND create_ts<'$create_ts' ORDER BY create_ts DESC")
                        ->limit($limit)
                        ->queryAll();

        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }


    /**
     * 获取用户所有关注的女神id
     *
     */
    public function followedList($user_id)
    {
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                        ->select('heroine_id')
                        ->from($table_name)
                        ->where('user_id = :user_id')
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->queryColumn();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }

    /**
     * 角色是否有关注此女神
     * @param int $user_id
     * @param int $heroine_id
     */
    public function isExitsFollow($user_id, $heroine_id)
    {
        $id = null;
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('follow_%02s', dechex($user_id % 256));
            $id = $con_characters->createCommand()
                            ->select('heroine_id')
                            ->from($table_name)
                            ->where("user_id = :user_id AND heroine_id = :heroine_id AND followed='1'", array(':user_id' => $user_id, ':heroine_id' => $heroine_id))
                            ->queryScalar();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return empty($id) ? false : true;
    }

    /**
     * 获取所有未关注女神ID列表
     * @param int $user_id
     * @return multitype:
     */
	public function getNoFollowIds($user_id){
		//获取用户所有女神id
		$goddess_list = Goddess::model()->goddessList();
		//获取用户所有关注的女神id
		$data = Follow::model()->followedList($user_id);
		//去重
		$id_list = array_diff_assoc($goddess_list,$data);
		return $id_list;
	}

	/**
	 * 查询用户成功解锁几位女神
	 * @param unknown $user_id
	 * @param unknown $level
	 */
	public function getUserFollowLevel($user_id, $level = 5){
	    $liking = Liking::model()->getLikingLvRow($level);
	    
	    $con_characters = Yii::app()->db_characters;
	    $table_name = sprintf('follow', dechex($user_id % 256));
	    $count = $con_characters->createCommand()
	    ->select('count(*) as c')
	    ->from($table_name)
	    ->where("user_id = :user_id AND liking >= :liking AND followed='1'", array(':user_id' => $user_id, ':liking' => $liking['max']))
	    ->queryRow();
	    if(isset($count['c'])){
	        return $count['c'];
	    }else{
	        return 0;
	    }
	}
}