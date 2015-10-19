<?php
class Gift extends CActiveRecord
{
    public $table_level = 'gift';

    public static function db()
    {
        return Yii::app()->db_common;
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取礼品信息
     *
     * @param  int   $gift_id    //礼品id
     * @return array $ret        //返回一行数据
     */
    public function getGiftInfo($gift_id)
    {
	    	$gift_redis = new Gift_redis();
	    	$data = $gift_redis->getGift($gift_id);
	    	if(!empty($data)){
	    		return $data;
	    	}else{
	    	
	        try{
	            $con_common = Yii::app()->db_common;
	            $ret = $con_common->createCommand()
	                    ->select('gift_id, name, type, url, add_liking, add_exp, add_point, minus_vit, lv, gold, add_glamorous, is_give')
	                    ->from('gift')
	                    ->where('gift_id=:gift_id AND app_id = :APP_ID')
	                    ->bindParam(':gift_id', $gift_id, PDO::PARAM_STR, 50)
	                    ->bindParam(':APP_ID', $GLOBALS['__APPID'], PDO::PARAM_INT, 11)
	                    ->queryRow();
	        }catch(Exception $e){
	            error_log($e);
	        }
	        $gift_redis->addGift(json_encode($ret),$gift_id);
	        return $ret;
	    	}
    }

    /**
     * 按礼物类别获得礼物信息, 例：春节礼品 情人节礼品等
     *
     * @param  int $type
     * @return array
     */
    public function schedule($type)
    {
        try{
            $con_common = Yii::app()->db_common;
            $ret = $con_common->createCommand()
                    ->select('gift_id,name, type, url, add_liking, add_exp, add_point, minus_vit, lv, gold')
                    ->from('gift')
                    ->where('type=:type AND app_id =:APP_ID ')
                    ->bindParam(':type', $type, PDO::PARAM_STR, 50)
                    ->bindParam(':APP_ID', $GLOBALS['__APPID'], PDO::PARAM_INT, 11)
                    ->queryAll();
        }catch(Exception $e){
            error_log($e);
        }
        return $ret;
    }
    
    /**
     * 给女神送礼
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $gift_id
     * @param unknown $number
     */
    public function giveGift($user_id,$goddess_id,$gift_id,$number){
        try{
            $characters_transaction = Yii::app()->db_characters->beginTransaction();
            $heroine_transaction = Yii::app()->db_heroine->beginTransaction();
            //是否关注女神
            if(!Follow::model()->isExitsFollow($user_id, $goddess_id)){
                return -3;
            }
            //获取礼物信息
            $gift_info = Gift::model()->getGiftInfo($gift_id);
            if($gift_info){
                //获取角色信息
                $characters = Characters::model()->getCharactersInfo($user_id);
                if($gift_info['is_give']==1){
                    $flowers_counts = $characters['flowers'];
                    if($number > $flowers_counts){
                        //赠送鲜花数超过已有鲜花数
                        $number = $number - $flowers_counts;
                        $temp_num = $flowers_counts;
                    }else{
                        $temp_num = $number;
                    }
                    if($temp_num != 0){
                        $characters['flowers'] = $characters['flowers'] - $temp_num;
                        //角色加 经验 积分
                        $params = array(
                                'exp' => $characters['exp'] + $temp_num * $gift_info['add_exp'],
                                'point' => $characters['point'] + $temp_num * $gift_info['add_point'],
                                'flowers' => $characters['flowers'],
                        );
                        //更新等级
                        $lv = Level::model()->exp2Level($params['exp']);
                        if(!empty($lv) && strcmp($lv, $characters['level']) != 0){
                            $params['level'] = $lv;
                        }
                        Characters::model()->updateCharacters($user_id,$params);
                        //女神好感
                        $follow = Follow::model()->getFollowRow($user_id, $goddess_id);
                        //好感度
                        $liking_val =  $follow['liking'] + $temp_num *  $gift_info['add_liking'];
                        //增加女神对角色的好感度
                        Follow::model()->updateFollow(date('Y-m-d H:i:s'), $user_id, $goddess_id, null, null, $liking_val);
                        //增加魅力值
                        $glamorousCount = Goddess::model()->getGlamorousCount($goddess_id);
                        $glamorous = array('glamorous' => (int)$glamorousCount + $temp_num * (int)$gift_info['add_glamorous']);
                        Goddess::model()->updateHeroineInfo($goddess_id, $glamorous);
                        
                        //增加用户送礼物给女神记录
                        $this->createHeroineGift($user_id,$goddess_id,$gift_id,$temp_num);
                        // 提交事务
                        $characters_transaction->commit();
                        $heroine_transaction->commit();
                        $res['log'] = '';
                        $res['log']['gold'] = 0;
                        $res['result'] = array(
                                'point' => (int)$params['point'],
                                'exp' => (int)$params['exp'],
                                'vit' => (int)$characters['vit'],
                                'vit_time' => (int)($characters['vit_time']),
                                'level' => (int)$lv,
                                'gold' => (int)$characters['gold'],
                                'flowers' => (int)$characters['flowers'],
                                'goddess_id' => (int)$goddess_id,
                                'liking' => $liking_val,
                        );
                        return $res;
                    }
                }
                $total = $gift_info['gold'] * $number;
                //扣金币
                if($total > $characters['gold']){
                    return -4;
                }
                //角色加金币 经验 积分
                $params = array(
                        'gold'=>(int)$characters['gold'] - (int)$total,
                        'exp' =>(int) $characters['exp'] + $number * $gift_info['add_exp'],
                        'point' => (int)$characters['point'] + $number * $gift_info['add_point'],
                );
                //更新等级
                $lv = Level::model()->exp2Level($params['exp']);
                if(!empty($lv) && strcmp($lv, $characters['level']) != 0){
                    $params['level'] = $lv;
                }
               
                Characters::model()->updateCharacters($user_id,$params);
                //女神好感
                $follow = Follow::model()->getFollowRow($user_id, $goddess_id);
                //好感度
                $liking_val =  $follow['liking'] + $number *  $gift_info['add_liking'];
                //增加女神对角色的好感度
                Follow::model()->updateFollow(date('Y-m-d H:i:s'), $user_id, $goddess_id, null, null, $liking_val);
                //增加魅力值
                $glamorousCount = Goddess::model()->getGlamorousCount($goddess_id);
                $glamorous = array('glamorous' => (int)$glamorousCount + $number * (int)$gift_info['add_glamorous']);
                Goddess::model()->updateHeroineInfo($goddess_id, $glamorous);
                //增加用户送礼物给女神记录
                $this->createHeroineGift($user_id,$goddess_id,$gift_id,$number);
                // 提交事务
                $characters_transaction->commit();
                $heroine_transaction->commit();
                $res['log']['gold'] = -$total;
                $res['log']['gold_after'] = (int)$characters['gold'] - (int)$total;
                $res['result'] = array(
                	'point' => (int)$params['point'],
                    'exp' => (int)$params['exp'],
                    'vit' => (int)$characters['vit'],
                    'vit_time' => (int)($characters['vit_time']),
                    'level' => (int)$lv,
                    'gold' => (int)$params['gold'],
                    'flowers' => (int)$characters['flowers'],
                    'goddess_id' => (int)$goddess_id,
                    'liking' => (int)$liking_val,
                );
            }else{
                return -2;
            }
        }catch(Exception $e)
        {
            error_log($e);
            $characters_transaction->rollback();
            $heroine_transaction->rollback();
            //更新失败
            return -1;
        }
        return $res;        
    }
    
    /**
     * 创建礼物使用记录
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $gift_id
     * @param unknown $count
     */
    public function createHeroineGift($user_id,$goddess_id,$gift_id,$count=1){
        try{
            $param = array(
                    'user_id'=>$user_id,
                    'heroine_id'=>$goddess_id,
                    'gift_id'=>$gift_id,
                    'count'=>$count,
                    'create_ts'=>date("Y-m-d H:i:s")
            );
            $con_characters = Yii::app()->db_heroine;
            $con_characters->createCommand()->insert('heroine_gift',
                    $param);
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }
}