<?php
class Characters extends CActiveRecord
{

    public $characters_table = array(
                    'user_id'       => '',
                    'vit'           => '',
                    'extra_vit'     => '',
                    'point'         => '',
                    'exp'           => '',
                    'level'         => '',
                    'charge_vit_ts' => '',
                    'follow_counts' => '',
                    'push_enabled'  => '',
                    'push_start'    => '',
                    'push_end'      => '',
                    'charge_exp_ts' => '',
                    'create_ts'     => '',
                    'gold'          => '',
                    'login_days'    => '',
                    'login_alldays' => '',
                    'update_ts'     => '',
                    'flowers'        => '',
                    'flowers_ts'    => '',
                );

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 创建游戏角色
     *
     */
    public function createGoddessCharacters($user_id, $vit, $extra_vit, $point, $exp, $level, $charge_vit_ts, $follow_counts, $now, $gold)
    {
        try{
        $con_characters = Yii::app()->db_characters;
        $table_name = sprintf('characters_%02s', dechex($user_id % 256));
        $con_characters->createCommand()->insert('characters',
                array('user_id'        => $user_id,
                        'vit'            => $vit,
                        'extra_vit'      => $extra_vit,
                        'point'          => $point,
                        'exp'            => $exp,
                        'level'          => $level,
                        'charge_vit_ts'  => $charge_vit_ts,
                        'follow_counts'  => $follow_counts,
                        'gold'           => $gold,
                        'push_start'     => 9,
                        'push_end'       => 23,
                        'flowers'        => 10,
                        'flowers_ts'     => $now,
                        'create_ts'      => $now));
        $con_characters->createCommand()->insert($table_name,
                            array('user_id'        => $user_id,
                                  'vit'            => $vit,
                                  'extra_vit'      => $extra_vit,
                                  'point'          => $point,
                                  'exp'            => $exp,
                                  'level'          => $level,
                                  'charge_vit_ts'  => $charge_vit_ts,
                                  'follow_counts'  => $follow_counts,
                                  'gold'           => $gold,
                                  'push_start'     => 9,
                                  'push_end'       => 23,
                                  'flowers'        => 10,
                                  'flowers_ts'     => $now,
                                  'create_ts'      => $now));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }

    /**
     * 获取游戏角色基本信息
     *
     * @param  int $user_id
     * @return array
     */
    public function getCharactersInfo($user_id)
    {
        $user_id = intval($user_id);
        $data = array();

        try{
            $con_characters = Yii::app()->db_characters;
            $table_name = sprintf('characters_%02s', dechex($user_id % 256));
            $data = $con_characters->createCommand()
                            ->select('vit,extra_vit,point,exp,level,charge_vit_ts,follow_counts,charge_exp_ts,gold,
                                    login_days,login_alldays,update_ts,flowers,flowers_ts')
                            ->from($table_name)
                            ->where('user_id = :user_id', array(':user_id' => $user_id))
                            ->queryRow();

            $ymd = date('Ymd', time());
            $now = time();
            $charge_vit_ts_int = strtotime($data['charge_vit_ts']);
			
            $lInfo = Level::model()->getLevelRow($data['level']);
            
            if($data['vit'] >= $lInfo['max_vit']){ //现有体力大于最大体力值
            	$data['vit_time'] = -1;
            }else{
            	if(date('Ymd', $now) > date('Ymd', $charge_vit_ts_int)){ //每日第一次登陆加满体力
            		$data['vit'] = max($lInfo['max_vit'], $data['vit']);
            		$data['vit_time'] = -1;	
            	}else{
            		$add_vit = floor(($now-$charge_vit_ts_int)/180);
            		$data['vit'] = min($data['vit'] + $add_vit, $lInfo['max_vit']);
            		if($data['vit'] >= $lInfo['max_vit']){ //如果体力已经达到最大值, 则不再倒计时
            			$data['vit_time'] = -1;
            		}else{
            			$data['vit_time'] = 180 - ($now-$charge_vit_ts_int)%180;
            		}
            	}
            }
            
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $data;
    }

    /**
     * 关注女神
     *
     * @param int $user_id
     * @param int $goddessId
     * @param int $follow
     * @param int $status  0普通解锁， 1猜图擦图 
     */
    public function followGoddess($user_id, $goddessId, $follow, $status = 0)
    {
		
        $user_id    = intval($user_id);
        $goddessId  = intval($goddessId);
        $follow     = intval($follow);
        $time = date("Y-m-d H:i:s");
        //是否存在
        if(Follow::model()->getFollowRow($user_id, $goddessId)){
            if($follow == 0){
                $follow = 2;
            }
            $ret = Follow::model()->updateFollow($time, $user_id,$goddessId, $follow);
        }else{
            $ret = Follow::model()->insertFollow($time, $user_id, $goddessId,$status,$follow);
//             if($status == 0){
                //赠送女神1级封面照
                Photo::model()->insertGoddessCoverPhoto($goddessId,$user_id);
                //关注推送女神图片 延迟1分钟
                //JPush::model()->followPush($user_id, $goddessId);
//             }
        }
        return $ret;
    }

    /**
     * 赞照片
     *
     * @param int $user_id
     * @param int $goddessId
     * @param int $imageid
     * @param int $status
     */
    /* public function praised($user_id, $goddessId, $imageid, $status)
    {
      //赞照片
      if(Photo::model()->updatePhoto($user_id, $goddessId, $imageid, $status)){
          //贊照片 增加女神被赞总数
          if(Goddess::model()->addPraisedCounts($goddessId, 1))
            return true;
      }
      return false;
    } */

    /**
     * 更新体力值
     *
     * @param int $user_id
     * @param int $vit
     */
    public function updatePlayerInfo($user_id, $param)
    {    	
    	$this->updateCharacters($user_id, $param);
    }

    /**
     * 更新角色信息表 
     * 
     * 改。。。
     *
     * @param int   $user_id
     * @param array $param
     *
     * @return bollean
     */
    public function updateCharacters($user_id, $param)
    {
        $param = array_intersect_key($param, $this->characters_table);
        $con_characters = Yii::app()->db_characters;
        
        try{
            if(isset($param['exp'])){
                $level_info = Level::model()->getMaxLevelRow();
                if($param['exp'] > $level_info['max_exp']){
                    $param['exp'] = $level_info['max_exp'];
                }
            }
            if(isset($param['vit'])){ //如果update接口涉及到更新体力, 则处理体力的更新时间
            	$data = $this->getCharactersInfo($user_id);
            	$lInfo = Level::model()->getLevelRow($data['level']);
            	if($param['vit'] >= $lInfo['max_vit']){
            		$param['charge_vit_ts'] = date('Y-m-d H:i:s', time());
            	}else{
            		if($data['vit_time'] == -1){ //减体力操作, 且减少之前体力是满的, 倒计时停止
            			$param['charge_vit_ts'] = date('Y-m-d H:i:s', time());
            		}else{ //体力改变之前,倒计时正在进行中
            			$param['charge_vit_ts'] = date('Y-m-d H:i:s', time()-180+$data['vit_time']);
            		}
            	}
            }
            
            $table_name = sprintf('characters_%02s', dechex($user_id % 256));
            $con_characters->createCommand()
                            ->update('characters',
                                $param,
                                'user_id=:user_id', array(':user_id'=>$user_id));
            $ret = $con_characters->createCommand()
                          ->update($table_name,
                            $param,
                            'user_id=:user_id', array(':user_id'=>$user_id));        
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        if($ret === false){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 获取用户app设置
     * 
     * @param unknown $user_id
     * @return boolean|unknown
     */
    public function getAppSetting($user_id){
        $con_characters = Yii::app()->db_characters;
        $table_name = sprintf('characters_%02s', dechex($user_id % 256));
        try{
            $data = $con_characters->createCommand()
                        ->select('push_enabled, push_start, push_end')
                        ->from($table_name)
                        ->where('user_id = :user_id', array(':user_id' => $user_id))
                        ->queryRow();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $data;
    }
    
    /**
     * 每日玫瑰领取
     * @param unknown $user_id
     */
    public function everyRose($user_id){    	
        $data= $this->getCharactersInfo($user_id);
        try{
            if(isset($data['level'])){
                $level_info = Level::model()->getLevelRow($data['level']);
                $now = date("Y-m-d");
                $last_get_time = date("Y-m-d",$data['flowers_ts']);
                if($now == $last_get_time){
                    return false;
                }
                $param=array('flowers_ts'=>time());
                if($data['flowers'] < $level_info['max_follower']){ //拥有数量小于最大值
                    if($now != $last_get_time){ //今天第一次登陆(只在第一次登陆的时候领取, 如果第一次登陆没有领取, 以后登陆也不再有领取机会)
                        $param['flowers'] = $level_info['max_follower'];
                    }
                }
                $this->updateCharacters($user_id, $param);
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }
}