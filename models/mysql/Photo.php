<?php
class Photo extends CActiveRecord
{
  //女神照片的表结构
  public $tbl_heroine_photo = array(
                        'photo_id' => '',
                        'heroine_id' => '',
                        'url' => '',
                        'thumb' => '',
                        'level' => '',
                        'devit' => '',
                        'add_exp' => '',
                        'praised_counts' => ''
                      );


    public static function model($className = __CLASS__)
	  {
        return parent::model($className);
    }

    /**
     *	插入解锁照片
     *
     */
    public function insertPhoto($user_id, $param)
    {
        try{
            $p = array(
                    'user_id'=>'',
                    'heroine_id'=>'',
                    'photo_id'=>'',
                    'unlock_type'=>'',
                    'rating'=>'',
                    'status'=>'',
                    'is_open'=>'',
                    'timestamp'=>'',
                    'type' => ''
              );
            $con_characters = Yii::app()->db_characters;
            $param = array_intersect_key($param, $p);
            $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
            $con_characters->createCommand()->insert($table_name,
                                $param);
            
            if($param['type'] == 0 || $param['type'] == 1){
                //查询解锁数目
                $follow = Follow::model()->getFollowRow($user_id, $param['heroine_id']);
                $unlock_counts = $follow['unlock_counts'] + 1;
                //更新解锁照片数量
                Follow::model()->updateFollowRow($user_id, $param['heroine_id'], array('unlock_counts'=>$unlock_counts));
                //更新好感值
            }	
        }catch(Exception $e){
            error_log($e);
        }	
    }

    /***********
     * 查询女神照片信息
     * 
     * @param unknown $user_id
     * @param unknown $heroine_id
     * @param unknown $photo_id
     * @return unknown
     *************/
    public function selectPhoto($user_id, $heroine_id, $photo_id)
    {
        $data = array();
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                            ->select('*')
                            ->from($table_name)
                            ->where('user_id = :user_id AND heroine_id=:heroine_id AND photo_id=:photo_id')
                            ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                            ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                            ->bindParam(':photo_id', $photo_id, PDO::PARAM_INT, 11)
                            ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }
        return $data;
    }

    /**
     * 用户关注女神照片信息列表
     *
     */
    public function followPhotos($user_id, $heroine_id)
    {
        $data = array();
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                            ->select('praise_id, user_id, heroine_id, photo_id, unlock_type, rating, status, is_open')
                            ->from($table_name)
                            ->where('user_id = :user_id AND heroine_id=:heroine_id')
                            ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                            ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                            ->queryAll();
        }catch(Exception $e){
            error_log($e);
        }

        return $data;
    }

    /**
     * 获取图片的详细信息
     *
     * @param  int $photo_id
     *
     * @return array $data
     */
    public function photoInfo($photo_id)
    {
        $data = array();
        try{
            $con_herione = Yii::app()->db_heroine;
            $data = $con_herione->createCommand()
                                ->select('photo_id, heroine_id, url, thumb, level, devit, praised_counts as praised_count, type')
                                ->from('heroine_photo')
                                ->where('photo_id=:photo_id')
                                ->bindParam(':photo_id', $photo_id, PDO::PARAM_INT, 11)
                                ->queryRow();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        $server_name = Yii::app()->params['img_url_base'];
        if(!empty($data)){
            $data['photo_id']       = (int)$data['photo_id'];
            $data['heroine_id']     = (int)$data['heroine_id'];
            $data['level']          = (int)$data['level'];
            $data['devit']          = (int)$data['devit'];
            $data['praised_count']  = (int)$data['praised_count'];
            $data['url']   = $server_name.$data['url'];
            $data['thumb'] = $server_name.$data['thumb'];
            $data['type']          = (int)$data['type'];
        }
        return $data;
    }

    /**
     * 查询女神照片ID 
     */
    public function getMaxPhotoId(){
        try{
            $con_herione = Yii::app()->db_heroine;
            $id = $con_herione->createCommand()
                                ->select('photo_id')
                                ->from('heroine_photo')
                                ->order('photo_id DESC')
                                ->queryScalar();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $id;
    }

    /**
     * 修改 女神照片表  照片信息
     *
     * @param int $photo_id
     * @param array $param
     *
     */
    public function updateHeroinePhoto($photo_id, $param)
    {
        if(!is_array($param)) return false;

        if(isset($param['praised_counts'])) $count = $param['praised_counts'];

        $param = array_intersect_key($this->tbl_heroine_photo, $param);
        $param = array_filter($param);

        if(isset($count)) $param['praised_counts'] = $count;

        try{
            $con_heroine = Yii::app()->db_heroine;
            $ret = $con_heroine->createCommand()
                        ->update('heroine_photo', $param, 'photo_id=:photo_id', array(
                            ':photo_id' => $photo_id
                          ));
        }catch(Exception $e){
            error_log($e);
        }

        return empty($ret) ? 0 : 1;
    }

    /**
     * 查询指定女神所有图片信息
     *
     * @param  int   $heroine_id
     *
     * @return array $data
     */
    public function heroinePhotos($heroine_id)
    {
        try{
            $con_heroine = Yii::app()->db_heroine;
            $data = $con_heroine->createCommand()
                            ->select('photo_id, heroine_id, url, thumb, level, devit, praised_counts, create_ts')
                            ->from('heroine_photo')
                            ->where('heroine_id=:heroine_id ORDER BY type DESC')
                            ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                            ->queryAll();
        }catch(Exception $e){
            error_log($e);
        }

        return $data;
    }

    
    
    public function single($heroine_id){
        try{
            $con_heroine = Yii::app()->db_heroine;
            $data = $con_heroine->createCommand()
                            ->select('photo_id, url, thumb, level, praised_counts, create_ts')
                            ->from('heroine_photo')
                            ->where('heroine_id=:heroine_id')
                            ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                            ->queryRow();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        $server_name = Yii::app()->params['img_url_base'];
        if(!empty($data)){
            $data['photo_id']       = (int)$data['photo_id'];
            $data['level']          = (int)$data['level'];
            $data['praised_count']  = (int)$data['praised_counts'];
            $data['url']   = $server_name.$data['url'];
            $data['thumb'] = $server_name.$data['thumb'];
            unset($data['praised_counts']);
        }
        return $data;
    }

    /**
     * 赞女神照片 更新用户和女神照片的状态
     * 
     * 用户赞信息表  信息
     *  
     * @param  int $user_id
     * @param  int $heroine_id
     * @param  int $photo_id
     * @param  int $status
     *
     * @return boolean
     */
    public function updatePhoto($user_id, $heroine_id, $photo_id, $status=null)
    {
        $time = date("Y-m-d H:i:s");

        if(isset($status)){
            $type = 1;
        }else if(isset($praised)){
            $type = 2;
        }else if(isset($liking)){
            $type = 3;
        }

        switch($type)
        {
            case 1:
                    $param = array( 'status' => $status);
                    break;
            default : return -1;
        }
        
        $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
        try{
            $con_characters = Yii::app()->db_characters;
            $ret = $con_characters->createCommand()
                    ->update($table_name,
                     $param ,'user_id=:user_id AND heroine_id=:heroine_id AND
                       photo_id=:photo_id', array(':user_id'=> $user_id, ':heroine_id'
                        => $heroine_id, ':photo_id'=> $photo_id));
            
        }catch(Exception $e){
            error_log($e);
        }

        if($ret)
            return true;
        else
            return false;
    }


    /**
     * 解锁照片列表
     * @param int $user_id
     * @param int $timestamp
     * @param int $limit
     */
    public function unlockList($user_id, $timestamp=0, $limit=10){
        $con_characters = Yii::app()->db_characters;
        $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
        $ret = array();
        try{
            $ret = $con_characters->createCommand()
                        ->select('*')
                        ->from($table_name)
                        ->where('user_id=:user_id AND is_open=0 AND timestamp>:timestamp ORDER BY type DESC')
                         ->order('photo_id')
                        // ->limit("$limit, 0")
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->bindParam(':timestamp', $timestamp, PDO::PARAM_INT, 11)
                        ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $ret;
    }

    /**
     * 获取该女神的所有照片ID 列表
     *
     * @param unknown $heroine_id
     * @param number $level			好友度等级 1-10
     * @return unknown
     */
    public function heroinePhotosIds($heroine_id,$level = 0){
        try{
            $con_heroine = Yii::app()->db_heroine;
            if($level > 0){
                $data = $con_heroine->createCommand()
                                    ->select('photo_id,level')
                                    ->from('heroine_photo')
                                    ->where('heroine_id=:heroine_id AND level=:level ORDER BY type DESC,photo_id ASC')
                                    ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                                    ->bindParam(':level', $level, PDO::PARAM_INT, 11)
                                    ->queryAll();
            }else{
                $data = $con_heroine->createCommand()
                                    ->select('photo_id,level')
                                    ->from('heroine_photo')
                                    ->where('heroine_id=:heroine_id ORDER BY type DESC,photo_id ASC')
                                    ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                                    ->queryAll();
            }
        }catch(Exception $e){
            error_log($e);
        }
        return $data;
    }

    /**
     * 获取用户已解锁图片ID 列表
     *
     * @param unknown $user_id
     * @param unknown $heroine_id
     * @return boolean|unknown
     */
    public function unlockPhotosIds($user_id,$heroine_id){
        $con_characters = Yii::app()->db_characters;
        $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
        $ret = array();
        $re	= array();
        try{
            $ret = $con_characters->createCommand()
                                    ->select('photo_id')
                                    ->from($table_name)
                                    ->where('user_id=:user_id AND is_open=0 AND heroine_id=:heroine_id ')
                                    ->order('photo_id')
                                    ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                                    ->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
                                    ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $ret;
    }

    /**
     * 获得某用户 某女神  未关注  图片 详细信息
     *
     * @param unknown $user_id
     * @param unknown $heroine_id
     * @param number $level
     * @return unknown
     */
    public function getNoFollowPhotoInfo($user_id,$heroine_id,$level = 0){
        //随机取照片等级 '等级'=> '概率'
        $prize_arr = array(
                '1' => 70,
                '2' => 14,
                '3' => 10,
                '4' => 5,
                '5' => 1,
        );
        $rlevel  = Common::model()->get_rand($prize_arr);
        //获取该女神的所有照片ID 列表
        $heroine_photoIds = $this->heroinePhotosIds($heroine_id,$rlevel);
        //查询解锁照片
        $user_photos = $this->unlockPhotosIds($user_id,$heroine_id);
        //去重
        $ids = Common::model()->array_dif($heroine_photoIds,$user_photos);
        unset($user_photos);
        unset($heroine_photoIds);
        $photoinfo = array();
        if($ids){
        //随机取
        	$photo_id = Common::model()->randData($ids);
        	$photoinfo = Photo::model()->photoInfo($photo_id['photo_id']);
        }
        return $photoinfo;
	}

	/**
	 * 第一张封面照解锁
	 * 查询出女神 1级第一张封面照
	 */
	public function insertGoddessCoverPhoto($heroine_id,$user_id,$level = 1){
		try{
		$con_heroine = Yii::app()->db_heroine;
		$con_characters = Yii::app()->db_characters;
		$table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
		//查询出此女神的第一张封面照 封面照类型1
		$photo_data = $con_heroine->createCommand()
			->select('photo_id')
			->from('heroine_photo')
			->where('heroine_id=:heroine_id AND level=:level AND type=1')
			->bindParam(':heroine_id', $heroine_id, PDO::PARAM_INT, 11)
			->bindParam(':level', $level, PDO::PARAM_INT, 11)
			->queryRow();
		    	if($photo_data){
		    	//查询用户是否有已经关注此女神的封面照 	是否已经解锁
		    	$user_photo_data = $con_characters->createCommand()
			    	->select('photo_id')
			    	->from($table_name)
			    	->where('user_id=:user_id AND photo_id=:photo_id ')
			    	->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
			    	->bindParam(':photo_id', $photo_data['photo_id'], PDO::PARAM_INT, 11)
			    	->queryAll();
		    		//没关注封面照 填入封面照
			    	if($user_photo_data == NULL){
			    		$photoParams = array('user_id'=>$user_id,
			    		        'heroine_id'=>$heroine_id,
			    		        'photo_id'=>$photo_data['photo_id'],
			    		        'unlock_type'=>2,
			    		        'status'=>0,
			    			    'is_open' => 0,
			    		        'timestamp'=>time(),
			    		        'type' => 1,
			    		       );
			    		//插入解锁照片
			    		Photo::model()->insertPhoto($user_id, $photoParams);
// 			    		echo '插入照片成功';
//					日志
			    	}else{
			    		//已经解锁
// 			    		echo '已经解锁';
			    	}
		    	}else{
		    		//没有封面照
// 		    		echo '没有封面照';
		    	}
		}catch(Exception $e){
		    error_log($e);
		}
	}
	
	/**
	 * 
	 * 根据照片ID 查询女神照片的类型
	 * 
	 * @param unknown $photo_id
	 */
	public function selectPhotoType($photo_id){
	    $data = array();
	    try{
	        $con_herione = Yii::app()->db_heroine;
	        $data = $con_herione->createCommand()
	        ->select('type')
	        ->from('heroine_photo')
	        ->where('photo_id=:photo_id')
	        ->bindParam(':photo_id', $photo_id, PDO::PARAM_INT, 11)
	        ->queryRow();
	    }catch(Exception $e){
	        error_log($e);
	        return false;
	    }
	    if($data){
	       return $data;
	    }
	}
	
	
	
    /***
     * 根据类型获取用户得到的所有照片（包括推送的剧情照片和女神相册中解锁的照片）
     * 
     */
    public function getUserAllPhoto($user_id,$type,$timestamp = '0'){
        $data = array();
        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('photo_praise_%02s', dechex($user_id % 256));
            if($type == 0){
                $data = $con_characters->createCommand()
                ->select('heroine_id as goddess_id,photo_id as id,status as praised,timestamp')
                ->from($table_name)
                ->where('user_id = :user_id AND timestamp>:timestamp')
                ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                ->bindParam(':timestamp', $timestamp, PDO::PARAM_INT, 11)
                ->queryAll();
            }else{
                $data = $con_characters->createCommand()
                ->select('heroine_id as goddess_id,photo_id as id,status as praised,timestamp')
                ->from($table_name)
                ->where('user_id = :user_id AND type=:Type AND timestamp>:timestamp')
                ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                ->bindParam(':Type', $type, PDO::PARAM_INT, 11)
                ->bindParam(':timestamp', $timestamp, PDO::PARAM_INT, 11)
                ->queryAll();
            }
            foreach ($data as $k => $v){
                $temp_data = $this->photoInfo($v['id']);
                if($temp_data){
                    $data[$k]['url'] = $temp_data['url'];
                    $data[$k]['thumb'] = $temp_data['thumb'];
                    $data[$k]['level'] = $temp_data['level'];
                    $data[$k]['lock'] = 1;
                    $data[$k]['timestamp'] = date('Y-m-d H:i:s',$v['timestamp']);
                }
            }
        }catch(Exception $e){
            error_log($e);
        }
        return $data;
    }
	
}