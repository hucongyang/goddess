<?php
class Guess extends CActiveRecord
{
    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 创建猜牌
     * @param unknown $user_id
     * @param unknown $param 
     * @return boolean
     */
    public function insertGuess($user_id, $param)
    {
        $con_game = Yii::app()->db_game;
        $table_name = sprintf('guess_%02s', dechex($user_id % 256));
        $con_game->createCommand()->insert($table_name,$param);
        $guess_id = $con_game->getLastInsertID();
        return $guess_id;
    }

    /**
     * 更新猜牌
     * @param unknown $user_id
     * @param unknown $id
     * @param unknown $param
     * @return boolean
     */
    public function updateGuess($user_id, $id, $param)
    {
        $con_game = Yii::app()->db_game;
        $table_name = sprintf('guess_%02s', dechex($user_id % 256));
        $con_game->createCommand()->update($table_name,
                $param,
                'id=:ID', array(':ID' => $id));
    }
    
    /**
     * 查询猜图结果
     * @param unknown $user_id
     * @param unknown $image_id
     * @return unknown
     */
    public function selectGuess($user_id,$image_id){
        $con_game = Yii::app()->db_game;
        $table_name = sprintf('guess_%02s', dechex($user_id % 256));
        $ret = $con_game->createCommand()
                        ->select('id,card_type,val,status')
                        ->from($table_name)
                        ->where('photo_id=:ID AND user_id=:USERID',array(':ID' => $image_id,':USERID' => $user_id))
                        ->order('id DESC')
                        ->queryRow();
        return $ret;
    }
    
    /**
     * 根据规则 抽一张图
     * @param unknown $user_id
     * @param unknown $goddess_id
     * @param unknown $liking
     * @return number|multitype:number Ambigous <number, unknown> Ambigous <number, mixed, unknown>
     */
    public function getGuess($user_id,$goddess_id,$liking){
        $con_game = Yii::app()->db_game;
        $table_name = sprintf('guess_%02s', dechex($user_id % 256));
        //根据女神ID 查询解锁照片数  总照片数 解锁照片ID
        $user_photos = Photo::model()->unlockPhotosIds($user_id,$goddess_id);
        if(count($user_photos) > 0){
            $heroine_photoIds_temp = array();
            for ($i=1; $i<=10; $i++){
                $heroine_photoIds = Photo::model()->heroinePhotosIds($goddess_id,$i);
                $all_num = count($heroine_photoIds);
                $ids = Common::model()->array_dif($heroine_photoIds,$user_photos);
                if(count($ids) != 0){
                    $temp = $i;
                    break;
                }
            }
            $unlock_num = count($ids);
        }else{
            $i = 0;
            $heroine_photoIds = Photo::model()->heroinePhotosIds($goddess_id,1);
            $all_num = count($heroine_photoIds);
            $ids = $heroine_photoIds;
            $unlock_num = count($ids);
        }
        if(count($heroine_photoIds) == 0){
            return array('err' => -6);
        }
        //判定好感等级， 不到等级 不能参加 猜牌
        if((int)($liking['level']) < (int)($i)){
            return array('err' => -3);
        }
        foreach ($ids as $k => $v){
            $temp = $v;
            break;
        }
        $photo_id = (int)$temp['photo_id'];
        $photo_info = Photo::model()->photoInfo($photo_id);
        
        //查询这张照片已经猜图几次  每多一次 增加5% 几率；
        $guess_res = $con_game->createCommand()
        ->select('count(*) as count')
        ->from($table_name)
        ->where('photo_id=:ID AND status=1 AND user_id=:USERID AND game_type = 0')
        ->bindParam(':ID', $photo_id, PDO::PARAM_INT, 11)
        ->bindParam(':USERID', $user_id, PDO::PARAM_INT, 11)
        ->queryRow();
        $add_rate = 0;
        if($guess_res['count'] != 0){
            $sel_guess_res = $con_game->createCommand()
            ->select('*')
            ->from($table_name)
            ->where('photo_id=:ID AND status=0 AND card_type = 8 AND user_id=:USERID')
            ->bindParam(':ID', $photo_id, PDO::PARAM_INT, 11)
            ->bindParam(':USERID', $user_id, PDO::PARAM_INT, 11)
            ->queryRow();
            if($sel_guess_res){
                $sel_guess_res['unlock_num'] = $unlock_num;
                $sel_guess_res['all_num'] = $all_num;
                $sel_guess_res['url'] = $photo_info['url'];
                $sel_guess_res['thumb'] = $photo_info['url'];
                return array('err' => -5, 'result' => $sel_guess_res);
            }
            //查询是否猜牌是否5的倍数  如果是5次，查询是否已经擦涂过
            if($guess_res['count']%5 == 0){
                $num = $guess_res['count']/5;
                $guess_res = $con_game->createCommand()
                ->select('count(id) as count')
                ->from($table_name)
                ->where('photo_id=:ID AND status=1 AND card_type = 8 AND user_id=:USERID')
                ->bindParam(':ID', $photo_id, PDO::PARAM_INT, 11)
                ->bindParam(':USERID', $user_id, PDO::PARAM_INT, 11)
                ->queryRow();
                if($num > $guess_res['count']){
                    $guess = array(
                            'image_id'      => (int)$photo_id,
                            'unlock_num'    => $unlock_num,
                            'all_num'       => $all_num,
                            'url'           => $photo_info['url'],
                            'thumb'         => $photo_info['url'],
                            'type'          => 3,
                            'vit'           => 0,
                            'liking'        => 0,
                            'gold'          => 0,
                            'flowers'       => 0,
                    );
                    
                    return array('err' => -4, 'result' => $guess);
                }
            }
            $add_rate = $guess_res['count']%5*5;
        }
        
        $liking = Liking::model()->getLikingRow($photo_info['level']);
        //按等级抽女神牌几率
        $rate = ($liking['rate']) + $add_rate;
        //剩余牌几率
        $status_rate = (100 - (int)$rate);
        //0 空牌 1目标牌 2效果牌
        $prize_arr = array(
                '1'=> $rate,
                '2'=> $status_rate
        );
        $type  = Common::model()->get_rand($prize_arr);
        $card_type = $type;
        $card_val = 0;
        $vit = 0;
        $liking = 0;
        $gold = 0;
        $flowers = 0;
        $i = rand(1,2);
        //随机取效果牌 0无效牌  3体力 4好感 5获得金币 6盗走金币 7获得玫瑰花
        $return_arr[0]['url'] =  $photo_info['url'];
        $url_thumb = $photo_info['url'];
        if($type == 1){
            $return_arr[0]['url'] = $photo_info['url'];
            $return_arr[0]['type'] = 1;
        }
        
        $status_arr = Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'];
        
        $r_1 = rand(1,17);
        
        switch ($r_1){
        	case 1:case 2: $random_arr_1 =3;break;
        	case 3: case 4: case 5:
    	         $random_arr_1 = 4;break;
	     case 6:case 7:case 8:
	         $random_arr_1 = 5;break;
          case 9:case 10:case 11:
              $random_arr_1 = 6;break;
          case 12:case 13:case 14:
              $random_arr_1 = 7;break;
          case 15:case 16:case 17:
              $random_arr_1 = 2;break;     
        }
        unset($status_arr[$random_arr_1]);
        $random_arr_2 = array_rand($status_arr,1);
        $random[0] = $random_arr_1;
        $random[1] = $random_arr_2;
        $return_arr[1] = $this->getRandomCard($random[0]);
        $return_arr[2] = $this->getRandomCard($random[1]);
        if($type == 2){
            if($random[0] == 2){
                $type = 0;
            }
            $card_type = $random[0];
            $card_val = $return_arr[1]['val'];
            $vit = $return_arr[1]['vit'];
            $liking = $return_arr[1]['liking'];
            $gold = $return_arr[1]['gold'];
            $flowers = $return_arr[1]['flowers'];
            $url = $return_arr[1]['url'];
            if($i == 1){
                $return_arr[0]['type'] = $return_arr[1]['type'];
                $return_arr[0]['url'] = $return_arr[1]['url'];
                $return_arr[1]['type'] = 1;
                $return_arr[1]['url'] = $photo_info['url'];
            }
            if($i == 2){
                $return_arr[0]['type'] = $return_arr[1]['type'];
                $return_arr[0]['url'] = $return_arr[1]['url'];
                $return_arr[1]['type'] = $return_arr[2]['type'];
                $return_arr[1]['url'] = $return_arr[2]['url'];
                $return_arr[2]['type'] = 1;
                $return_arr[2]['url'] = $photo_info['url'];
            }
        }
        unset($return_arr[1]['val']);
        unset($return_arr[1]['vit']);
        unset($return_arr[1]['liking']);
        unset($return_arr[1]['gold']);
        unset($return_arr[1]['flowers']);
        
        
        
        unset($return_arr[2]['val']);
        unset($return_arr[2]['vit']);
        unset($return_arr[2]['liking']);
        unset($return_arr[2]['gold']);
        unset($return_arr[2]['flowers']);
        
//         var_dump($return_arr);exit;
        if($GLOBALS['__VERSION'] > '1.0'){
            $guess = array(
                    'cards'         => $return_arr,
                    'image_id'      => (int)$photo_id,
                    //             'url'           => $url,
                    'thumb'         => $url_thumb,
                    'unlock_num'    => $unlock_num,
                    'all_num'       => $all_num,
                    'type'          => $type,
                    'card_type'     => $card_type,
                    'val'          => $card_val,
                    'vit'          => $vit,
                    'liking'          => $liking,
                    'gold'          => $gold,
                    'flowers'          => $flowers,
            );
        }else{
            $url =  $return_arr[0]['url'];
            $guess = array(
                    'image_id'      => (int)$photo_id,
                    'url'           => $url,
                    'thumb'         => $url_thumb,
                    'unlock_num'    => $unlock_num,
                    'all_num'       => $all_num,
                    'type'          => $type,
                    'card_type'     => $card_type,
                    'val'          => $card_val,
                    'vit'          => $vit,
                    'liking'          => $liking,
                    'gold'          => $gold,
                    'flowers'          => $flowers,
            );
        }
        
        return $guess;
    }
    
    
    function getRandomCard($random){
        $status_arr = Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['status_card'];
//         $return_type['card_type'] = $random;
        $return_type['url'] = Yii::app()->params['img_url_base'].Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['guess_card_url'][$random];
        $return_type['vit'] = 0;
        $return_type['liking'] = 0;
        $return_type['gold'] = 0;
        $return_type['flowers'] = 0;
        if($random == 2){
            //vit
            $return_type['val'] = 0;
            $return_type['url'] = Yii::app()->params['img_url_base'].$status_arr[2];
        }elseif($random == 3){
            //vit
            $return_type['val'] = $status_arr[3];
            $return_type['vit'] = $status_arr[3];
        }elseif($random == 4){
            //liking
            $return_type['val'] = $status_arr[4];
            $return_type['liking'] = $status_arr[4];
        }elseif($random == 5 || $random == 6){
            //gold
            $return_type['val'] = $status_arr[$random];
            $return_type['gold'] = $status_arr[$random];
        }elseif($random == 7){
            //flowers
            $return_type['val'] = $status_arr[7];
            $return_type['flowers'] = $status_arr[$random];
        }
        $return_type['type'] = $random;
        return $return_type;
    }
}