<?php
class Goddess extends CActiveRecord
{

	public  $heroine = array(
                'nickname'     => '',
                'sex'          => '',
                'bwh'          => '',
                'lable_id'     => '',
                'birthplace'   => '',
                'signature'    => '',
                'height'       => '',
                'weight'       => '',
                'hobby'        => '',
                'glamorous'    => '',
                'follower_count' => '',
                'picture_count' => '',
                'praised_counts' => '',
                'status' => '',
                'create_ts' => '',
                'type' => '',
                'flowers' => '',
	           'caption' => ''
	           );

    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 获取女神详细信息
     *
     * @param int $goddessId
     */
    public function getGoddessInfo($goddessId)
    {
	    	$goddess_redis = new Goddess_redis();
	    	$data = $goddess_redis->getGoddess($goddessId);
	    	if(!empty($data)){
	    		return $data;
	    	}else{
	    	   $data = array();
	        try{
	        	
	            $con_heroine = Yii::app()->db_heroine;
	            $data = $con_heroine->createCommand()
	                            ->select('heroine_id,nickname,sex,
	                                    birthplace,birthday,signature,
	                                    faceurl,cover,height,weight,hobby,glamorous,
	                                    follower_count,picture_count,praised_counts, bwh as vitalstatistics,
	                                    job, name, blood_type, animal_sign, disposition as character, 
	                                    status, constellations, create_ts, type, flowers, caption')
	
	                            ->from('heroine_info')
	                            ->where('heroine_id = :heroine_id AND app_id = "'.$GLOBALS['__APPID'].'"', array(':heroine_id' => $goddessId))
	                            ->queryRow();
	            
	            
	        }catch(Exception $e){
	            error_log($e);
	            return false;
	        }
	        if($data){
	           $data['age'] = $this->getAge($data['birthday']);
	           $data['lable'] = '';
	           if(is_array($data) && !empty($data) ){
        				
        	            if($data['faceurl'] == NULL)
        	            {
        	                $data['faceurl'] = Yii::app()->params['default_head_image'];
        	            }
        	            $data['faceurl'] = Yii::app()->params['img_url_base'] . $data['faceurl'];
        	
        	            if($data['cover'] == NULL)
        	            {
        	                $data['cover'] = Yii::app()->params['default_head_image'];
        	            }
        	            $data['cover'] = Yii::app()->params['img_url_base'] . $data['cover'];
        	            
        	            //获取label标签
        	            $heroine_lables   = Lable::model()->heroineLables($goddessId);
        	            $lables = Lable::model()->lables();
        	            $tags = array();
        	            foreach ($heroine_lables as $v) {
        	                $tags[] = $lables[$v];
        	            }
        	            $data['lable'] = implode(',', $tags);
        	        }
        	        
        	        $goddess_redis->addGoddess(json_encode($data),$goddessId);
        	        
        	        return $data;
	        }else{
	            return $data;
	        }
	    	} 
		
    }
    
    
    /***
     * 获取年龄
     */
    function getAge($birthday) {
        $age = 0;
        $year = $month = $day = 0;
        if (is_array($birthday)) {
            extract($birthday);
        } else {
            if (strpos($birthday, '-') !== false) {
                list($year, $month, $day) = explode('-', $birthday);
                $day = substr($day, 0, 2); //get the first two chars in case of '2000-11-03 12:12:00'
            }
        }
        $age = date('Y') - $year;
        if (date('m') < $month || (date('m') == $month && date('d') < $day)) $age--;
        return $age;
    }
    
    
    /**
     * 查询解锁条件
     * @param unknown $goddess_id
     */
    public function getUnlock($goddess_id){
        $con_heroine = Yii::app()->db_heroine;
        $data = $con_heroine->createCommand()
                    ->select('heroine_id,type,goddess_count,gold,begin_ts,end_ts')
                    ->from('heroine_unblock')
                    ->where('heroine_id = :heroine_id', array(':heroine_id' => $goddess_id))
                    ->queryRow();
        return $data;
    }

    /**
     * 查询女神关注量
     * @param unknown $goddess_id
     * @return unknown
     */
    public function getFollowerCount($goddess_id)
    {
        $con_heroine = Yii::app()->db_heroine;
        $count = $con_heroine->createCommand()
                        ->select('follower_count')
                        ->from('heroine_info')
                        ->where('heroine_id = :heroine_id', array(':heroine_id' => $goddess_id))
                        ->queryScalar();
        return $count;
    }
    
    /**
     * 查询女神魅力值
     * @param unknown $goddess_id
     * @return unknown
     */
    public function getGlamorousCount($goddess_id)
    {
        $con_heroine = Yii::app()->db_heroine;
        $count = $con_heroine->createCommand()
        ->select('glamorous')
        ->from('heroine_info')
        ->where('heroine_id = :heroine_id', array(':heroine_id' => $goddess_id))
        ->queryScalar();
        return $count;
    }

    /**
     * 更新女神被赞数
     *
     * @param int $goddessId
     * @param int $count		   赞数
     * @param
     */
    public function addPraisedCounts($goddessId, $count)
    {
		$con_heroine = Yii::app()->db_heroine;

		$info = $this->getGoddessInfo($goddessId);

		if(is_array($info) && isset($info['praised_counts'])){

		    $count = $info['praised_counts']+$count;

            try{
                $ret = $con_heroine->createCommand()
                        ->update('heroine_info',
                        array( 'praised_counts' => $count ),'heroine_id=:heroine_id',
                        array(':heroine_id' => $goddessId));
                return true;
            }catch(Exception $e){
                error_log($e);
            }
		}
        return false;
    }
    
    /**
     * 更新女神信息
     * 	
     * 	follower_count	关注数量，glamorous 魅力值
     * 
     * @param unknown $heroine_id
     * @param unknown $info
     */
    public function updateHeroineInfo($heroine_id,$info)
    {
        $con_heroine = Yii::app()->db_heroine;
        $info = array_intersect_key($info ,$this->heroine);

        try{
            $con_heroine->createCommand()
                ->update('heroine_info', 
                        $info,
                        'heroine_id=:heroine_id', 
                        array(':heroine_id'=>$heroine_id));
        }catch(Exception $e){
            error_log($e);
        }
    }


    /**
     * 查询是否存在此女神
     *
     * @param int $goddessId
     */
    public function isExitsGoddess($goddessId)
    {
        try{
            $con_heroine = Yii::app()->db_heroine;
            $is_exits = $con_heroine->createCommand()
                            ->select('heroine_id')
                            ->from('heroine_info')
                            ->where('heroine_id = :heroine_id AND app_id = "'.$GLOBALS['__APPID'].'"', array(':heroine_id' => $goddessId))
                            ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }

		if(is_array($is_exits))
			return true;
		else
			return false;
    }

    /**
     * 查询此女神 是否有关注 前置条件
     *
     * @param int $goddessId
     */
    public function isUnlockGoddess($goddessId)
    {
        try{
            $con_heroine = Yii::app()->db_heroine;
            $is_exits = $con_heroine->createCommand()
            ->select('type')
            ->from('heroine_info')
            ->where('heroine_id = :heroine_id AND (type = 1 OR type = 0) ', array(':heroine_id' => $goddessId))
            ->queryRow();
            
        }catch(Exception $e){
            error_log($e);
        }
        if(is_array($is_exits))
            return true;
        else
            return false;
    }
    
    /**
     * 筛选
     *
     * @param int $lable_id
     * @param int $order
     * @param int $page
     * @param int $pageSize
     */
    public function filterGoddessList($lable_id=null, $type, $page, $pagesize)
    {
    	$start = (max(intval($page), 1) - 1) * $pagesize;
    	$limit = max(intval($pagesize), 1);

        $con_heroine = Yii::app()->db_heroine;
        $ids = array();
        if($lable_id == null){
            try{
                $ids = $con_heroine->createCommand()
                    ->select('heroine_id')
                    ->from('heroine_info')
                    ->where('app_id = "'.$GLOBALS['__APPID'].'"')
                    ->order('heroine_id DESC')
                    ->limit($limit, $start)
                    ->queryColumn();
            }
            catch(Exception $e)
            {
                error_log($e);
            }
        }else{

            //查询这个lable_id 里所有的女神
            $range = $con_heroine->createCommand()
                               ->select('heroine_id')
                               ->from('heroine_tag')
                               ->where('lable_id=:lable_id')
                               ->bindParam(':lable_id', $lable_id, PDO::PARAM_INT, 11)
                               ->queryColumn();
            if(empty($range))
            {
                return array();
            }
            $in = implode(',', $range);

            try{
                $ids = $con_heroine->createCommand()
                                    ->select('heroine_id')
                                    ->from('heroine_info')
                                    ->where('app_id = "'.$GLOBALS['__APPID'].'" AND heroine_id in ($in)  ')
                                    //关注热度排序
                                    ->order('follower_count DESC')
                                    ->limit($limit, $start)
                                    ->queryColumn();
            }catch(Exception $e){
                error_log($e);
            }

        }

        $data = array();

        if(!is_array($ids)) return false;

        foreach ($ids as $dsId) {
            $key = array();
            $heroine = $this->getGoddessInfo($dsId);

            if(is_array($heroine)){
                $key['goddess_id']     = (int)$heroine['heroine_id'];
                $key['nickname']       = $heroine['nickname'];
                $key['signature']      = $heroine['signature'];
                $key['face_url']        = $heroine['faceurl'];
                $key['lable']          = $heroine['lable'];
                $key['picture_count']  = (int)$heroine['picture_count'];
                $key['follower_count'] = (int)$heroine['follower_count'];
                $data[] = $key;
            }
        }

        return $data;
    }

    /**
     * 获取女神相册
     *
     * @param  int $user_id
     * @param  int $goddessId
     *
     * @return array $photos     //二维数组
     */
    public function getGoddessPhoto($user_id, $goddessId)
    {
        $followPhotos = Photo::model()->followPhotos($user_id, $goddessId);

        $photos = Photo::model()->heroinePhotos($goddessId);
        
        //todo: 可以优化
        $follows = array();
        $status  = array();
        if(is_array($follows)){
            foreach ($followPhotos as $fval) {
                $follows[] = $fval['photo_id'];
                $status[$fval['photo_id']] = $fval['status'];
            }
        }

        $result = array();

        if(is_array($photos)){
            $imgUrl = Yii::app()->params['img_url_base'];
            foreach ($photos as $pkey=>$pval) {
                $key = array();
                $key['id'] = (int)$pval['photo_id'];
                $key['goddess_id']  = (int)$pval['heroine_id'];
                $key['url'] = stripslashes($imgUrl.$pval['url']);
                $key['thumb'] = stripslashes($imgUrl.$pval['thumb']);
                $key['level'] = (int)$pval['level'];
                $liking = Liking::model()->getLikingLvRow($pval['level']);
                $key['gold'] = (int)$liking['gold'];
                $key['praisednum'] = (int)$pval['praised_counts'];
                
                if(in_array($pval['photo_id'], $follows)){
                    $key['lock'] = 1;
                    $key['praised'] = (int)$status[$pval['photo_id']];
                }else{
                    $key['lock'] = 0;
                    $key['praised'] = 0;
                }
                $result[] = $key;
            }

        }
        return $result;
    }

    /**
     * 获取最大女神ID
     *
     */
    public function getMaxId()
    {
        $con_heroine = Yii::app()->db_heroine;

        try{
            $id = $con_heroine->createCommand()
                                ->select('heroine_id')
                                ->from('heroine_info')
                                ->order('heroine_id DESC')
                                ->queryScalar();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $id;
    }

    /**
     * 获取用户所有女神id
     *
     */
    public function goddessList()
    {
        try{
            $platform = strtolower($GLOBALS['__PLATFORM']);
            $where = '';
            //
            if($platform == 'android'){
                $where = ' AND (platform = 1 OR platform = 2)';
            }elseif($platform == 'ios'){
                $where = ' AND (platform = 1 OR platform = 3)';
            }
            $con_heroine = Yii::app()->db_heroine;
            $data = $con_heroine->createCommand()
                        ->select('heroine_id')
                        ->from('heroine_info')
                        ->where('status=0 AND app_id = "'.$GLOBALS['__APPID'].'"'. $where)
                        ->order('type asc')
                        ->queryColumn();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }

    /**
     * 女神粉丝排行榜
     *
     */
    public function fans($goddess_id)
    {
        $data = array();
        try{
            $con_heroine = Yii::app()->db_heroine;
            $data = $con_heroine->createCommand()
                        ->select('user_id')
                        ->from('heroine_rank')
                        ->where('heroine_id=:heroine_id', array(':heroine_id'=>$goddess_id))
                        ->order('ranking DESC')
                        ->limit(10, 0)
                        ->queryColumn();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }
    
    /**
     * 根据女神ID 查询 粉丝排行
     * @param unknown $goddess_id
     */
    public function getfans($goddess_id, $user_id){
        //查询排行榜表，更新时间如果比当前时间慢1小时，重新查询，并保存
        
        try{
            $con_characters = Yii::app()->db_characters;
            $data = $con_characters->createCommand()
            ->select('user_id, liking ')
            ->from('follow')
            ->where("heroine_id ='$goddess_id' AND followed = 1 ORDER BY liking DESC ")
            ->limit(10)
            ->queryAll();
            $search = 0;

            $return = array();
            foreach ($data as $k => $v){
                $temp_data = array();
                //
                if($user_id == $v['user_id']){
                    $search = 1;
                }
                $user_info = User::model()->getUserInfo($v['user_id']);
                $return[$k]['face_url'] = $user_info['avatar'];
                $return[$k]['nickname'] = $user_info['nickname'];
                $return[$k]['num'] = (int)($k+1);
                $return[$k]['liking'] = (int)$v['liking'];
                $user_name = User::model()->getUsername($v['user_id']);
                $return[$k]['user_name'] = $user_name;
                $temp_data = $this->goddess_gift($goddess_id, $v['user_id']);
                $return[$k]['gift'] = $temp_data;
                
            }
            if($search == 0 && isset($k)){
                unset($return[$k]);
                unset($data[$k]);
                $data_one = $con_characters->createCommand()
                    ->select('liking ')
                    ->from('follow')
                    ->where("heroine_id ='$goddess_id' AND followed = 1 AND user_id = '$user_id' ")
                    ->queryRow();
                $liking = $data_one['liking'];
                $data_num = $con_characters->createCommand()
                    ->select('count(*) as c ')
                    ->from('follow')
                    ->where("heroine_id ='$goddess_id' AND followed = 1 AND liking < '$liking' ")
                    ->queryRow();
                $user_info = User::model()->getUserInfo($user_id);
                $return[count($data)]['face_url'] = $user_info['avatar'];
                $return[count($data)]['nickname'] = $user_info['nickname'];
                $return[count($data)]['num'] = (int)($data_num['c']);
                $return[count($data)]['liking'] = (int)$liking;
                $user_name = User::model()->getUsername($user_id);
                $return[count($data)]['user_name'] = $user_name;
                $temp_data1 = $this->goddess_gift($goddess_id, $user_id);
                $return[count($data)]['gift'] = $temp_data1;
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $return;
    }
   
    /**
     * 根据用户ID,女神ID,查询所有的礼物的赠送数量
     * @param unknown $goddess_id
     * @param unknown $user_id
     * @return boolean|multitype:string
     */
    public function goddess_gift($goddess_id, $user_id){
        try{
            $gift_list = Gift::model()->schedule(1);
            $return = array();
            foreach ($gift_list as $k => $v){
                $con_heroine = Yii::app()->db_heroine;
                $data = $con_heroine->createCommand()
                ->select('sum(count) as num')
                ->from('heroine_gift')
                ->where('heroine_id=:heroine_id AND user_id=:user_id AND gift_id=:gift_id ', 
                        array(':heroine_id' => $goddess_id,':user_id' => $user_id,':gift_id' => $v['gift_id']))
                ->queryRow();
                if($data['num'] > 0 ){
                    $arr['id'] = (int)$v['gift_id'];
                    $arr['num'] = (int)$data['num'];
                    $arr['url'] = Yii::app()->params['img_url_base'].$v['url'];
                    $return[] = $arr;
                }
                
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $return;
    }
    
    /**
     * 获得猜图结果 根据女神ID 取图
     * @param unknown $user_id
     * @param unknown $goddess_id
     */
    public function get_guess_image($user_id,$goddess_id){
        try{
            $con_game = Yii::app()->db_game;
            $con_characters = Yii::app()->db_characters;
            $trans_game = $con_game->beginTransaction();
            $trans_characters = $con_characters->beginTransaction();
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            //获得是否关注
            $follow_info = Follow::model()->getFollowRow($user_id, $goddess_id);
            if(!$follow_info){
                //未关注女神
                return -2;
            }
            //获取等级信息
            $liking = Liking::model()->getLikingRow((int)$follow_info['liking']);
            //查询体力值是否够扣体力
            if($player['vit'] < (int)$liking['guess_vit']){
                //体力不足
                return -1;
            }
            
            //根据规则 抽一张图
            $guess = Guess::model()->getGuess($user_id,$goddess_id,$liking);
            if(isset($guess['err']) && $guess['err'] < 0){
                if($guess['err'] == -4){
                    //猜图5次没中 保存擦图请求
                    $guess_param = array(
                            'user_id'       => (int)$user_id,
                            'photo_id'      => (int)$guess['result']['image_id'],
                            'card_type'     => 8,
                            'game_type'     => 1,
                            'val'           => 0,
//                             'status'        => 1,
                            'create_ts'     => date("Y-m-d h:i:s",time())
                    );
                    $guess_id = Guess::model()->insertGuess($user_id,$guess_param);
                    // 提交事务
                    $trans_game->commit();
                    $trans_characters->commit();
                    $guess['log']['guess_id'] = $guess_id;
                    return $guess;
                }elseif($guess['err'] == -5){
                    //没有完成擦图，返回擦图
                    $return['result'] = array(
                            'image_id'      => (int)$guess['result']['photo_id'],
                            'unlock_num'    => (int)$guess['result']['unlock_num'],
                            'all_num'       => $guess['result']['all_num'],
                            'url'           => $guess['result']['url'],
                            'thumb'         => $guess['result']['thumb'],
                            'type'          => 3,
                            'vit'           => 0,
                            'liking'        => 0,
                            'gold'          => 0,
                            'flowers'       => 0,
                    );
                    $return['log']['guess_id'] = $guess['result']['id'];
                    $return['err'] = $guess['err'];
                    return $return;
                }else{
                    return $guess['err'];
                }
            }
            
            $return_arr['log']['val'] = $guess['val'];
            $return_arr['log']['type'] = $guess['card_type'];
            //保存数据
            if($GLOBALS['__VERSION'] > '1.0'){
                //猜牌结果
                $guess_param = array(
                            'user_id'       => $user_id,
                            'photo_id'      => $guess['image_id'],
                            'card_type'     => $guess['card_type'],
                            'val'           => $guess['val'],
                            'create_ts'     => date("Y-m-d h:i:s",time()),
//                             'status'        => 1
                    );
                unset($guess['card_type']);
            }ELSE{
                
                //猜牌结果
                $guess_param = array(
                        'user_id'       => $user_id,
                        'photo_id'      => $guess['image_id'],
                        'card_type'     => $guess['card_type'],
                        'val'           => $guess['val'],
                        'create_ts'     => date("Y-m-d h:i:s",time()),
//                         'status'        => 1
                );
                unset($guess['val']);
                unset($guess['card_type']);
            }
            $guess_id = Guess::model()->insertGuess($user_id,$guess_param);
            
            $return_arr['log']['vit'] = $liking['guess_vit'];
            $return_arr['log']['vit_after'] = $player['vit']-(int)$liking['guess_vit'];
            
            //扣体力
            $params = array(
                    'vit'=>$player['vit']-(int)$liking['guess_vit']
            );
            Characters::model()->updateCharacters($user_id,$params);
            //返回结果数组
            $return_arr['result'] = $guess;
            
            
            $return_arr['log']['guess_id'] = $guess_id;
            // 提交事务
            $trans_game->commit();
            $trans_characters->commit();
        }catch(Exception $e){
            $trans_game->rollback();
            $trans_characters->rollback();
            error_log($e);
            return false;
        }
        return $return_arr;
    }
    
    /**
     * 女神照片猜牌结果提交
     * 
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $image_id
     */
    public function guess_image_result($user_id,$goddess_id,$image_id){
        $con_game = Yii::app()->db_game;
        $con_characters = Yii::app()->db_characters;
        
        $trans_game = $con_game->beginTransaction();
        $trans_characters = $con_characters->beginTransaction();
        try{
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            $guess_info = Guess::model()->selectGuess($user_id,$image_id);
            if(!$guess_info){
                return -3;
            }
            //只有卡牌类型为1，状态为已经领取时
            if($guess_info['status'] == 1){
                //已经获得奖励不能重复获取奖励
                return -1;
            }
            $follow_info = Follow::model()->getFollowRow($user_id, $goddess_id);
            if(!$follow_info){
                //未关注女神
                return -2;
            }
            
            $params = array();
            $card_type = $guess_info['card_type'];
            //增加翻牌效果  1女神牌解锁女神照片 3体力 4好感 5获得金币 6盗走金币 7获得玫瑰花
            if($card_type == 8){
                return -4;
            }
            
            $data = array();
            $card_val = 0;
            switch ($card_type){
                case 1:
                    $photoParams = array('user_id'=>$user_id,
                            'heroine_id'=>$goddess_id,
                            'photo_id'=>$image_id,
                            'unlock_type'=>4,
                            'status'=>0,
                            'timestamp'=>time(),
                            'type' =>0,
                            );
                    $card_val = $image_id;
                    //插入解锁照片
                    Photo::model()->insertPhoto($user_id, $photoParams);
					
                    $liking_val = (int)$follow_info['liking'] + (int)Yii::app()->params['follow_liking'];
                    $follow_info['liking'] = $liking_val;
                    Follow::model()->updateFollow(date("Y-m-d H:i:s"), $user_id, $goddess_id, null, null, $liking_val);
                    //获取等级信息
                    $liking = Liking::model()->getLikingRow($player['level']);
                    //加经验值
                    $params = $params + array(
                            'exp'=>$player['exp']+(int)$liking['guess_exp']
                    );
                    //加经验更新等级
                    $lv = Level::model()->exp2Level($params['exp']);
                    if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                        $params['level'] = $lv;
                    }
                    
                    
                    break;
                case 2:
                    $card_val = 0;
                    break;
                case 3:
                    $card_val =Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'][3];
                    $lv = Level::model()->exp2Level($player['exp']);
                    $level = Level::model()->getLevelRow($lv);
                    $vit = $player['vit']+(int)$card_val;
                    if($vit > $level['max_vit']){
                        $vit = $level['max_vit'];
                    }
                    //加体力
                    $params = array(
                            'vit'=>$vit
                    );
                    $data['log']['vit_after'] = $params['vit'];
                    break;
                case 4:
                    //加好感
                    //获得好感度
                    
                    $card_val = Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'][4];
                    $liking_val = 0;
                    if(isset($follow_info['liking']))
                    {
                        $liking_val = (int)$follow_info['liking'] + $card_val;
                        $follow_info['liking'] = $liking_val;
                    }
                    //增加女神对角色的好感度
                    Follow::model()->updateFollow(date('Y-m-d H:i:s'), $user_id, $goddess_id, null, null, $liking_val);
                    break;
    	           case 5:
    	               //加金币
    	               $card_val = (int) Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'][5];
    	               $params = array(
    	                       'gold'=>$player['gold']+$card_val,
    	               );
    	               //猜图加金币日志
    	               $gold_params = array(
    	                       'user_id'=>$user_id,
    	                       'type'=>2,
    	                       'value'=>$card_val,
    	                       'gold'=>$player['gold']+$card_val,
							   'create_ts' =>date("Y-m-d H:i:s")
    	               );
    	               Gold::model()->createGold($user_id,$gold_params);
    	               $data['log']['gold_after'] = $player['gold']+$card_val;
                    break;
                case 6:
                    //减金币
                    $card_val = (int)Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'][6];
                    $gold = $player['gold']+$card_val;
                    if($gold < 0 ){
                        $gold = 0;
                    }
                    $params = array(
                            'gold'=>$gold,
                    );
                    //猜图加金币日志
                    $gold_params = array(
                            'user_id'   =>$user_id,
                            'type'      =>3,
                            'value'     =>$card_val,
                            'gold'      =>$gold,
                            'create_ts' =>date("Y-m-d H:i:s")
                    );
                    Gold::model()->createGold($user_id,$gold_params);
                    $data['log']['gold_after'] = $gold;
                    break;
                case 7:
                    //加鲜花
                    $card_val = (int)Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'][7];
                    $params = array(
                            'flowers'=>$player['flowers']+$card_val,
                    );
                    break;
            }
            
            if($params){
                Characters::model()->updateCharacters($user_id,$params);
            }
            //更新翻拍结果
            $param = array('status' => 1);
            Guess::model()->updateGuess($user_id, $guess_info['id'], $param);
            // 提交事务
            $trans_game->commit();
            $trans_characters->commit();
            $user_info = Characters::model()->getCharactersInfo($user_id);
            $data['result'] = array(
                'point'     => (int)$user_info['point'],
                'exp'       => (int)$user_info['exp'],
                'vit'       => (int)$user_info['vit'],
                'vit_time'  => (int)$user_info['vit_time'],
                'level'     => (int)$user_info['level'],
                'gold'      => (int)$user_info['gold'],
                'flowers'  => (int)$user_info['flowers'],
                'liking'    => (int)$follow_info['liking'],
            );
            
            $data['log']['type'] =$card_type;
            $data['log']['val']  =$card_val;
            $data['log']['guess_id'] =$guess_info['id'];
        }catch(Exception $e){
            $trans_game->rollback();
            $trans_characters->rollback();
            error_log($e);
            return false;
        }
        return $data;
    }
    
    /**
     * 擦图结果解锁照片
     *
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $image_id
     * @param unknown $status
     */
    public function wipe_image_result($user_id,$goddess_id,$image_id,$status){
        $con_game = Yii::app()->db_game;
        $con_characters = Yii::app()->db_characters;
        $trans_characters = $con_characters->beginTransaction();
        try{
            $player = Characters::model()->getCharactersInfo($user_id);
            //获得好感度
            $follow_info = Follow::model()->getFollowRow($user_id, $goddess_id);
            //获取等级信息
            $liking = Liking::model()->getLikingRow((int)$follow_info['liking']);
            //查询体力值是否够扣体力  后根据需求 擦图为赠送 不扣体力
           /*  if($player['vit'] < (int)$liking['wipe_vit']){
                //体力不足
                return -3;
            }
            //擦图消耗体力
            //扣体力
            $params = array(
                    'vit'=>$player['vit']-(int)$liking['wipe_vit']
            ); */
            //查询是否可以擦涂 
            $table_name = sprintf('guess_%02s', dechex($user_id % 256));
            $guess_info = $con_game->createCommand()
            ->select('*')
            ->from($table_name)
            ->where('photo_id=:ID AND card_type = 8 AND status = 0 AND user_id =:USERID ',array(':ID' => $image_id,':USERID' => $user_id))
            ->order('create_ts DESC')
            ->queryRow();
            //查询是否有擦图
            if(!$guess_info){
                return -2;
            }
            $photoParams = array('user_id'=>$user_id,
                    'heroine_id'=>$goddess_id,
                    'photo_id'=>$image_id,
                    'unlock_type'=>4,
                    'status'=>0,
                    'timestamp'=>time(),
                    'type' =>0,
            );
            $card_val = $image_id;
            $return['result'] = '';
            if($status == 1){
                //加经验值
                $params = array(
                        'exp'=>$player['exp']+(int)$liking['wipe_exp']
                );
                //更新等级
                $lv = Level::model()->exp2Level($params['exp']);
                if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                    $params['level'] = $lv;
                }
				
                //擦图成功加好感值
                $liking_val = $follow_info['liking'] + (int)Yii::app()->params['follow_liking'];
                Follow::model()->updateFollowRow($user_id, $goddess_id, array('liking' => $liking_val));
                //插入解锁照片
                $return['result'] = Photo::model()->insertPhoto($user_id, $photoParams);
                //擦图获得经验值 扣体力
                Characters::model()->updateCharacters($user_id,$params);
            }
//             $return['log']['vit']= $liking['wipe_vit'];
//             $return['log']['vit_after']= $params['vit'];
            $param = array(
            	'status' => 1,
            );
            Guess::model()->updateGuess($user_id, $guess_info['id'], $param);
            
            
            
            // 提交事务
            $trans_characters->commit();
        }catch(Exception $e){
            $trans_characters->rollback();
            error_log($e);
            return -1;
        }
        return $return;
    }
    
    /**
     * 条件解锁女神
     *
     * @param unknown $user_id
     * @param unknown $goddess_id
     */
    public function terms_unlock_photo($user_id,$goddess_id){
        try{
            $con_heroine = Yii::app()->db_heroine;
            $con_characters = Yii::app()->db_characters;
            $trans_heroine = $con_heroine->beginTransaction();
            
            $follow_info = Follow::model()->getFollowRow($user_id, $goddess_id);
            if($follow_info){
                return -5;
            }
            $heroine_terms = $con_heroine->createCommand()
            ->select('type')
            ->from('heroine_info')
            ->where('heroine_id = :heroine_id', array(':heroine_id' => $goddess_id))
            ->queryRow();
            //是否有前置解锁条件
            if($heroine_terms['type'] == 2 || $heroine_terms['type'] == 3){
                $heroine_unblock = $con_heroine->createCommand()
                ->select('type,goddess_count,gold')
                ->from('heroine_unblock')
                ->where('heroine_id = :heroine_id', array(':heroine_id' => $goddess_id))
                ->queryRow();
                //女神好感度到 多少算追到女神 解锁女神
                if($heroine_unblock['type'] == 1){
                    $liking = Liking::model()->getLikingLvRow(5);
                    $table_name = sprintf('follow_%02s', dechex($user_id % 256));
                    $data = $con_characters->createCommand()
                        ->select('count( heroine_id ) as c')
                        ->from($table_name)
                        ->where('user_id=:user_id AND liking <= :liking')
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->bindParam(':liking', $liking['max'], PDO::PARAM_INT, 11)
                        ->queryRow();
                  if($data['c'] >= $heroine_unblock['goddess_count']){
                      $follow = 0;
                      $status = 1;
                      //关注女神
                      $ret = Characters::model()->followGoddess($user_id, $goddess_id, $follow, $status);
                  }else{
                      return -4;
                  }
                //金币解锁 减金币 解锁女神
                }elseif($heroine_unblock['type'] == 2){
                    //获取用户基本信息
                    $player = Characters::model()->getCharactersInfo($user_id);
                    //查询金币是否够
                    if($player['gold'] < (int)$heroine_unblock['gold']){
                        return -3;
                    }
                    //解锁女神 减金币
                    $params = array(
                            'gold'=>$player['gold']-(int)$heroine_unblock['gold']
                    );
                    Characters::model()->updateCharacters($user_id,$params);
                    //解锁女神 加金币日志
                    $gold_params = array(
                            'user_id'=>$user_id,
                            'type'=>4,
                            'value'=>$heroine_unblock['gold'],
                            'gold'=>$player['gold']+$heroine_unblock['gold'],
							'create_ts' =>date("Y-m-d H:i:s")
                    );
                    Gold::model()->createGold($user_id,$gold_params);
                    $follow = 0;
                    $status = 1;
                    //关注女神
                    $ret = Characters::model()->followGoddess($user_id, $goddess_id, $follow, $status);
                }
                $user_info = Characters::model()->getCharactersInfo($user_id);
                $data['result'] = array(
                        'point'=> (int)$user_info['point'],
                        'exp'=> (int)$user_info['exp'],
                        'vit'=> (int)$user_info['vit'],
                        'vit_time' => (int)($user_info['vit_time']),
                        'level'=> (int)$user_info['level'],
                        'gold'=> (int)$user_info['gold'],
                        'flowers'=> (int)$user_info['flowers'],
                );
                
            }else{
                return -2;
            }
        }catch(Exception $e){
            $trans_heroine->rollback();
            error_log($e);
            return -1;
        }
        return $data;
    }
    
    /**
     * 金币解锁照片
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $image_id
     */
    public function GoldUnlockPhoto($user_id,$goddess_id,$image_id){
        $characters_transaction = Yii::app()->db_characters->beginTransaction();
        try{
            //获取图片的信息
            $imageInfo = Photo::model()->photoInfo($image_id);
            //如果不存在 返回没有这张图片信息
            if(empty($imageInfo)){
                return -1;
            }
            //是否已经解锁
            $is_exits = Photo::model()->selectPhoto($user_id, $goddess_id, $image_id);
            if(!empty($is_exits))
            {
                return -2;
            }
            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);
            //获得解锁照片金币数
            $liking = Liking::model()->getLikingLvRow($imageInfo['level']);
            //金币数
            if((int)( $liking['gold'] ) <= (int)$player['gold']){
                //更新经验 游戏人物等级字段废除 用经验值获取等级
                $param['gold'] = (int)$player['gold'] - (int)$liking['gold'];
            }else{
                return -3;
            }
            //解锁女神 加金币日志
            $gold_params = array(
                    'user_id'=>$user_id,
                    'type'=>5,
                    'value'=>$liking['gold'],
                    'gold'=>(int)$player['gold']-(int)$liking['gold'],
					'create_ts' =>date("Y-m-d H:i:s")
            );
            Gold::model()->createGold($user_id,$gold_params);
            //增加经验值
            $add_exp  = $liking['gold_exp'];
            //更新经验 游戏人物等级字段废除 用经验值获取等级
            $param['exp'] = $player['exp']+$add_exp;
            //更新等级
            $lv = Level::model()->exp2Level($param['exp']);
            if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                $param['level'] = $lv;
            }
            $user_liking = 0;
            //更新体力值 等级 体力时间
            Characters::model()->updatePlayerInfo($user_id, $param);
            $photoParams = array('user_id'=>$user_id,
                    'heroine_id'=>$goddess_id,
                    'photo_id'=>$image_id,
                    'unlock_type'=>0,
                    'status'=>0,
                    'type'=>1,
                    'timestamp'=>time());
            //插入解锁照片
            Photo::model()->insertPhoto($user_id, $photoParams);
            //好感度
            $follow = Follow::model()->getFollowRow($user_id, $goddess_id);
			
            //金币解锁照片增加好感值
            $liking_val = $follow['liking'] + (int)Yii::app()->params['follow_liking'];
            Follow::model()->updateFollowRow($user_id, $goddess_id, array('liking' => $liking_val));
            
            $characters_transaction->commit();
            
            $param['point'] = 0;
            $result['point']        = (int)$player['point'];
            $result['exp']          = (int)$param['exp'];
            $result['vit']          = (int)($player['vit']);
            $result['vit_time']     = (int)($player['vit_time']);
            $result['level']        = (int)$lv;
            $result['goddess_id']   = (int)$goddess_id;
            $result['liking']       = (int)$follow['liking'];
            $result['gold']         = (int)$param['gold'];
            $result['flowers']      = (int)$player['flowers'];
        }catch(Exception $e)
        {
            $characters_transaction->rollback();
            //更新失败
            $this->_return('MSG_ERR_UNKOWN');
        }
        $return['result'] = $result;
        $return['log']['gold'] = $liking['gold'];
        return $return;
    }
    
    /**
     * 获取所有女神
     */
    public function goddessPeach($user_id){
        
        //按类型分别查询，循环提取已经解锁的，剩余的按要求加入数组。
        $all  = Goddess::model()->goddessList();
        $temp_arr = array();
        foreach ($all as $id) {
            $ret    = Goddess::model()->getGoddessInfo($id);
            $followed = Follow::model()->getFollowRow($user_id,$id);
            $unlock_count = Follow::model()->getUserFollowLevel($user_id);
            if(is_array($ret) && !empty($ret)){
                $tmp = array();
                $tmp['goddess_id']      =  (int)$ret['heroine_id'];
                $tmp['nickname']        =  $ret['nickname'];
                $tmp['signature']       =  $ret['sex'];
                $tmp['cover']           =  $ret['cover'];
                //版本大于1.0 时 返回年龄
                if($GLOBALS['__VERSION'] > '1.0'){
                    $tmp['age']         =  $ret['age'];
                }else{
                    $tmp['birthday']    =  $ret['birthday'];
                }
                $tmp['job']             =  $ret['job'];
                $tmp['picture_count']   =  (int)$ret['picture_count'];
                $tmp['follower_count']  =  (int)$ret['follower_count'];
//                 $tmp['praised_count']   =  (int)$ret['praised_counts'];
                $tmp['glamorous']        = (int)$ret['glamorous'];
                $tmp['followed']        = (int)$followed['followed'];
                if($ret['type'] != 0 && $ret['type'] != 1){
                    $unlock = Goddess::model()->getUnlock($ret['heroine_id']);
                    //查询是否完成前置条件
                    if($followed['status'] == 1){
                        $tmp['open_status']  = 2;
                    }else{
                        $tmp['open_status']  = 1;
                    }
                    $tmp['unclock_time']        = 0;
                    $tmp['unclock_gold']        = 0;
                    $tmp['unclock_goddess']     = 0;
                    switch ($unlock['type']){
                    	case 1://几位女神解锁
                    	    if($unlock_count >= $unlock['goddess_count']){
                    	        $tmp['open_status']  = 2;
                    	    }else{
                    	       $tmp['unclock_goddess'] = (int)$unlock['goddess_count'];
                    	    }
                    	    break;
                    	case 2://金币解锁
                    	    $tmp['unclock_gold']= (int)$unlock['gold'];
                    	    break;
                    	case 3://时间解锁
                    	    if($unlock['begin_ts'] <= time()){
                    	        $tmp['open_status']  = 3;
                    	        $tmp['unclock_time']= 0;
                    	    }else{
                    	        $tmp['unclock_time']= date('Y-m-d H:i:s',$unlock['begin_ts']);
                    	    }
                    	    break;
                    }
                }else{
                    $tmp['open_status']         = 0;
                    $tmp['unclock_time']        = 0;
                    $tmp['unclock_gold']        = 0;
                    $tmp['unclock_goddess']     = 0;
                }
                $temp_arr[] = $tmp;
            }
        }
        //公开的
        $arr1 = array();
        //限时的
        $arr2 = array();
        //条件的
        $arr3 = array();
        //金币的
        $arr4 = array();
        foreach ($temp_arr as $k => $v){
            if($v['open_status'] == 0 || $v['open_status'] == 2 || $v['open_status'] == 3){
                $arr1[] = $v;
            }
            if($v['open_status'] == 1){
                if($v['unclock_time'] != 0 ){
                    $arr2[] = $v;
                }
                if($v['unclock_goddess'] != 0 ){
                    $arr3[] = $v;
                }
                if($v['unclock_gold'] != 0 ){
                    $arr4[] = $v;
                }
            }
        }
        $arr2_temp = array();
        if(isset($arr2[0])){
            $arr2_temp[0] = $arr2[0];
        }
        
        $arr = array_merge($arr1 , $arr2_temp , $arr3 , $arr4);
//         $return_arr = array();
//         foreach ($arr as $v){
//             $return_arr[] = $v;
//         }
        $result['goddess'] = $arr;
        $result['server_time'] = date('Y-m-d H:i:s');
        return $result;
    }
}

