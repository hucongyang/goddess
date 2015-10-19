<?php
class Common extends CActiveRecord
{
    public $table_level = 'level';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 获取分类id
     *
     * @param  string $lable
     * @return int    $lable_id
     */
    public function getLableID($lable)
    {
        try{
            $con_common = Yii::app()->db_common;
            $lable_id = $con_common->createCommand()
                    ->select('lable_id')
                    ->from('lable')
                    ->where('lable=:lable')
                    ->bindParam(':lable', $lable, PDO::PARAM_INT, 11)
                    ->queryScalar();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $lable_id;
    }

    /**
     * 
     * @return boolean|unknown
     */
    public function getLabel()
    {
        try{
            $con_common = Yii::app()->db_common;
            $lable_id = $con_common->createCommand()
                    ->select('lable_id as id, lable as name')
                    ->from('lable')
                    ->where('app_id=:APP_ID')
                    ->bindParam(':APP_ID', $GLOBALS['__APPID'], PDO::PARAM_STR, 50)
                    ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $lable_id;
    }

    /**
     * 意见反馈
     *
     * @param  string $content
     * @param  string $contact
     *
     * @return boolean
     */
    public function feedback($content, $contact, $time, $user_id=0)
    {
        try{
            $con_common = Yii::app()->db_common;
            $ret = $con_common->createCommand()
                            ->insert('feedback',
                                array('content'=>$content,
                                      'contact'=>$contact,
                                      'create_ts'=>$time,
                                      'user_id'=>$user_id,
                          'ip'            => $GLOBALS['__IP'],
                          'version'       => $GLOBALS['__VERSION'],
                          'device_id'     => $GLOBALS['__DEVICEID'],
                          'platform'      => $GLOBALS['__PLATFORM'],
                          'channel'       => $GLOBALS['__CHANNEL'],
                          'app_version'   => $GLOBALS['__APPVERSION'],
                          'os_version'    => $GLOBALS['__OSVERSION'],
                          'app_id'        => $GLOBALS['__APPID']));
            
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $ret === false ? false : true;
    }

    /**
     * 获取最新版本
     *
     * @param int $platform
     */
    public function latest($platform)
    {
        try{
            $con_common = Yii::app()->db_common;
            $sql = "SELECT version,url,gotta as min_version,content FROM `app_version` 
		    				where platform=$platform AND app_id = ".$GLOBALS['__APPID']." 
		    				ORDER BY create_ts DESC";
		  $ret = $con_common->createCommand($sql)->queryRow();
            $ret['desc'] = $ret['content'];
            unset($ret['content']);
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $ret;
    }

    /**
     * 随机取数组
     * @param unknown $data
     * @return unknown
     */
    public function randData($data){
		if($data){
			$rand_keys = array_rand($data, 1);
			$data =  $data[$rand_keys];
		}
	    	return $data;
    }

    /**
     * 根据概率获取中奖号码
     */
    public function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }
    
    /**
     * 数组匹配去重复
     *
     * @param unknown $a
     * @param unknown $b
     */
    public function array_dif($a, $b, $c = 'photo_id'){
        
        foreach ($a as $k => $v){
        	foreach ($b as $v1){
        		if($v[$c]==$v1[$c]){
        			unset($a[$k]);
        		}
        	}
        }
        return $a;
    }
    
    /**
     * 获取七天登陆奖励
     */
    public function login_reward($user_id, $bag_id = 0){
        $res = array();
        try{
            $con_common = Yii::app()->db_common;
            //获取角色信息
            $characters_info = Characters::model()->getCharactersInfo($user_id);
            $ret = $con_common->createCommand()
            ->select('reward_id, days, type, number')
            ->from('login_reward')
            ->order('days ASC')
            ->queryAll();
            if($bag_id != 0){
                //获取
                if(date("Y-m-d", time()) > date("Y-m-d",strtotime($characters_info['update_ts']))){
                    //当前时间是否大于过领取时间
                    $days = $characters_info['login_days'] + 1;
                }elseif(date("Y-m-d", time()) == date("Y-m-d",strtotime($characters_info['update_ts']))){
                    //今天已经领取过
                    return -3;
                }else{
                    //时间错误不能领取
                    return -2;
                }
                if($days == 8){
                    $days = 1;
                }
                foreach ($ret as $k => $v){
                    if($days == (int)$v['days'] && $v['reward_id'] == $bag_id){
                        $res['id'] = (int)$v['reward_id'];
                        $res['gold'] = (int)$v['number'];
                        $res['day'] = (int)$v['days'];
                    }
                }
            }else{
                if(date("Y-m-d", time()) > date("Y-m-d",strtotime($characters_info['update_ts']))){
//                     echo $characters_info['login_days'];exit;
                    if($characters_info['login_days'] == 7){
                        $characters_info['login_days'] = 0;
                    }
                }
                foreach ($ret as $k => $v){
                    $res[$k]['id'] = (int)$v['reward_id'];
                    $res[$k]['gold'] = (int)$v['number'];
                    $res[$k]['day'] = (int)$v['days'];
                    
                    //状态：0 已领取，1 可领取，2不可领取
                    if($v['days'] < $characters_info['login_days']){
                            $res[$k]['status'] = 0;
                    }else{
                        if($v['days'] == 1 && $characters_info['login_days'] == 0){
                            $res[$k]['status'] = 1;
                        }else{
                            if($v['days'] <= $characters_info['login_days']){
                                $res[$k]['status'] = 0;
                            }else{
                                if(date("Y-m-d", time()) > date("Y-m-d",strtotime($characters_info['update_ts'])) && $v['days'] == ($characters_info['login_days']+1)){
                                    $res[$k]['status'] = 1;
                                }else{
                                    $res[$k]['status'] = 2;
                                }
                            }
                        }
                    }
                }
            }            
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        
        return $res;
    }
    
    /**
     * 随机取3个数不重复
     */
    public function randomNum(){
        if(!isset(Yii::app()->params['game_arr'][$GLOBALS['__APPID']])){
            $GLOBALS['__APPID'] = 10;
        }
        
        $prize_arr = array(1,2,3,4,5,6,7,8,9);
        $random_1 = array_rand($prize_arr,1);
        unset($prize_arr[$random_1]);
        $random_2 = array_rand($prize_arr,1);
        unset($prize_arr[$random_2]);
        $random_3 = array_rand($prize_arr,1);
        $temp = array($random_1,$random_2,$random_3);
        
        foreach ($temp as $k => $v){
            if($v <= 4){
                $pic_arr = Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['card'];
                $id = array_rand($pic_arr,1);
                $arr[$k] = array('type' => 1,'url'=>Yii::app()->params['img_url_base'].$pic_arr[$id],'thumb'=>Yii::app()->params['img_url_base'].$pic_arr[$id]);
            }
            if($v <= 7 && $v > 4){
                //无效牌
                $arr[$k] = array('type' => 0,'url'=>Yii::app()->params['img_url_base'].Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['wuxiao'],'thumb'=>Yii::app()->params['img_url_base'].Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['wuxiao_thumb']);
            }
            if($v <= 8 && $v > 7){
                //炸弹牌
                $arr[$k] = array('type' => 2,'url'=>Yii::app()->params['img_url_base'].Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['zhadan'],'thumb'=>Yii::app()->params['img_url_base'].Yii::app()->params['game_arr'][$GLOBALS['__APPID']]['zhadan_thumb']);
            }
        }
        return $arr;
    }
    
    /**
     * 获取充值礼包
     *
     */
    public function getPayPack()
    {
        try{
            $con_common = Yii::app()->db_common;
            
            if(strtolower($GLOBALS['__PLATFORM']) == 'ios' && $GLOBALS['__CHANNEL'] == '3003'){
                $ret = $con_common->createCommand()
                ->select('id,name,coin,price,discount,status,free,appstore_id')
                ->from('pay')
                ->where('type=1')
                ->queryAll();
            }else{
                $ret = $con_common->createCommand()
                ->select('id,name,coin,price,discount,status,free')
                ->from('pay')
                ->where('type=2')
                ->queryAll();
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    
        return $ret;
    }
    
    /**
     * 获取单个商品信息
     */
    public function getGoodsInfo($goods_id){
    	try{
    		$con_common = Yii::app()->db_common;
    		$ret = $con_common->createCommand()
    		->select('name,coin,price,discount,free')
    		->where('id=:ID', array(':ID'=>$goods_id))
    		->from('pay')
    		->queryRow();
    	}catch(Exception $e){
    		error_log($e);
    		return false;
    	}
    	return $ret;
    }
    
    


    /**
     * 软件下载
     * @return boolean|unknown
     */
    public function getSoftwareList($user_id,$software_id = 0){
        $return = array();
        try{
            $con_common = Yii::app()->db_common;
            $con_characters = Yii::app()->db_characters;
            if(strtolower($GLOBALS['__PLATFORM']) == 'ios'){
                $type = 1;
            }else{
                $type = 2;
            }
            $ret = $con_common->createCommand()
            ->select('id,name,content as desc,icon as image,duwn_url,duwn_num,version,gold,package_name')
            ->from('software_duwn')
            ->where('status = 0 AND type = '.$type)
            ->order('id DESC')
            ->queryAll();
            foreach ($ret as $k => $v){
                    $return[$k]['id'] = (int)$v['id'];
                    $return[$k]['name'] = $v['name'];
                    $return[$k]['desc'] = $v['desc'];
                    $return[$k]['image'] = $v['image'];
                    
                    $return[$k]['url'] = $v['duwn_url'];
                    $return[$k]['package_name'] = $v['package_name'];
                    
                //                 $return[$k]['down_num'] = (int)$v['duwn_num'];
                $return[$k]['version'] = $v['version'];
                $return[$k]['gold'] = (int)$v['gold'];
                $table_name = sprintf('software_%02s', dechex($user_id % 256));
                $software = $con_characters->createCommand()
                        ->select('status')
                        ->from($table_name)
                        ->where('software_id=:ID AND user_id =:UID')
                        ->bindParam(':ID', $v['id'], PDO::PARAM_INT, 11)
                        ->bindParam(':UID', $user_id, PDO::PARAM_INT, 11)
                        ->order('id DESC')
                        ->queryRow();
                if($software){
                    $return[$k]['status'] = (int)$software['status'];
                }else{
                    $return[$k]['status'] = 0;
                }
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        
        return $return;
        
    }
    
    /**
     * 软件下载
     * @return boolean|unknown
     */
    public function getSoftware($software_id = 0){
        $return = array();
        try{
            $con_common = Yii::app()->db_common;
                $ret = $con_common->createCommand()
                ->select('id,name,content as desc,icon as image,duwn_url,duwn_num,version,gold,package_name')
                ->from('software_duwn')
                ->where('id=:ID AND status = 0')
                ->bindParam(':ID', $software_id, PDO::PARAM_INT, 11)
                ->order('id DESC')
                ->queryAll();
            foreach ($ret as $k => $v){
                $return[$k]['id'] = (int)$v['id'];
                $return[$k]['name'] = $v['name'];
                $return[$k]['desc'] = $v['desc'];
                $return[$k]['image'] = $v['image'];
                $return[$k]['url'] = $v['duwn_url'];
                $return[$k]['package_name'] = $v['package_name'];
//                 $return[$k]['down_num'] = (int)$v['duwn_num'];
                $return[$k]['version'] = $v['version'];
                $return[$k]['gold'] = (int)$v['gold'];
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        
        return $return;
    }
    
    /**
     * 根据渠道号查询软件推荐开关
     */
    public function getSoftwareOnoff(){
        try{
            $type = 0;
            if(strtolower($GLOBALS['__PLATFORM']) == 'android'){
                $type = 2;
            }elseif(strtolower($GLOBALS['__PLATFORM']) == 'ios'){
                $type = 1;
            }elseif(strtolower($GLOBALS['__PLATFORM']) == 'windows'){
                $type = 3;
            }
            $con_common = Yii::app()->db_common;
            $ret = $con_common->createCommand()
                ->select('onoff')
                ->from('channel_item')
                ->where('item_id = :item_id AND type = :TYPE', array(':item_id' => $GLOBALS['__CHANNEL'],':TYPE' => $type))
                ->order('id DESC')
                ->queryRow();
            if($ret && $ret['onoff'] == 0){
                return -1;
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }        
        return $ret;
    }
    
    
}