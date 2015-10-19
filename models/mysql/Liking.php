<?php
class Liking extends CActiveRecord
{
    public $table = 'liking';

    public static function db()
    {
        return Yii::app()->db_common;
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 好感值表信息
     *
     */
    public function wholeLikingAround()
    {
    	
    	$liking_redis = new Liking_redis();
    	$data = $liking_redis->getLiking();
    	if(!empty($data)){
    		return $data;
    	}else{
        $info = array();
        try{
            //exp_once, vit_per_lock, point_per_lock,exp_per_sms, exp_per_calling, gold, rate,
            $info = $this->db()->createCommand()
                    ->select('level, min, max, name, gold_exp as exp_per_goldlock,
                              guess_exp as exp_per_guess, guess_vit as vit_per_guess, wipe_exp as exp_per_erase, 
                            wipe_vit as vit_per_erase ')
                    ->from($this->table)
                    ->order('level')
                    ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
		
        //int 类型
        if(!empty($info)){
        	foreach ($info as $key => $value) {
//         		$info[$key] = array();
        		$info[$key]['level']           = (int) $value['level'];
        		$info[$key]['min']             = (int) $value['min'];
        		$info[$key]['max']             = (int) $value['max'];
//         		$info[$key]['exp_once']        = (int) $value['exp_once'];
//         		$info[$key]['liking_once']     = (int) $value['liking_once'];
//         		$info[$key]['exp_per_lock']       = (int) $value['exp_once'];
//         		$info[$key]['point_once']      = (int) $value['point_once'];
//         		$info[$key]['vit_per_lock']       = (int) $value['vit_per_lock'];
//         		$info[$key]['point_per_lock']     = (int) $value['point_per_lock'];
//         		$info[$key]['exp_per_sms']        = (int) $value['exp_per_sms'];
//         		$info[$key]['exp_per_calling']    = (int) $value['exp_per_calling'];
//         		$info[$key]['gold']               = (int) $value['gold'];
//         		$info[$key]['rate']               = (int) $value['rate'];
        		$info[$key]['name']               = $value['name'];
        		$info[$key]['exp_per_goldlock']   = (int) $value['exp_per_goldlock'];
        		$info[$key]['exp_per_guess']      = (int) $value['exp_per_guess'];
        		$info[$key]['vit_per_guess']       = (int) $value['vit_per_guess'];
        		$info[$key]['exp_per_erase']           = (int) $value['exp_per_erase'];
        		$info[$key]['vit_per_erase']           = (int) $value['vit_per_erase'];
        		$info[$key]['liking_per_image']           =(int)Yii::app()->params['follow_liking'];
//         		$info[] = $tmp;
        	}
        }
        $liking_redis->addLiking(json_encode($info));
        
        return $info;
    	}
    }
    
    /**
     * 根据好感值取等级信息
     * @param unknown $lv
     * @return unknown
     */
    public function getLikingRow($level)
    {
        $info = array();
        try{
            $info = $this->db()->createCommand()
            ->select('level, min, max, exp_once, vit_per_lock, point_per_lock,
                              exp_per_sms, exp_per_calling, gold, rate, name, gold_exp,
                              guess_exp, guess_vit, wipe_exp, wipe_vit, guess_vit')
                            ->from($this->table)
                            ->where('max>=:level_begin AND min<=:level_end')
                            ->bindParam(':level_begin', $level, PDO::PARAM_INT, 11)
                            ->bindParam(':level_end', $level, PDO::PARAM_INT, 11)
                            ->queryRow();
            //查询为空， 查询好感值是否大于最大值， 最大值返回最高。
        }catch(Exception $e){
            error_log($e);
        }
    
        return $info;
    }
    
    /**
     * 根据好感等级 取好感信息
     * @param unknown $lv
     * @return unknown
     */
    public function getLikingLvRow($lv)
    {
        $info = array();
        try{
            $info = $this->db()->createCommand()
            ->select('level, min, max, exp_once, vit_per_lock, point_per_lock,
                              exp_per_sms, exp_per_calling, gold, rate, name, gold_exp,
                              guess_exp, guess_vit, wipe_exp, wipe_vit, guess_vit')
                                  ->from($this->table)
                                  ->where('level=:level')
                                  ->bindParam(':level', $lv, PDO::PARAM_INT, 11)
                                  ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }
    
        return $info;
    }
    
    /**
     *  取最大好感信息
     * @param unknown $lv
     * @return unknown
     */
    public function getMaxLikingLvRow()
    {
        $info = array();
        try{
            $info = $this->db()->createCommand()
            ->select('level, min, max, exp_once, vit_per_lock, point_per_lock,
                              exp_per_sms, exp_per_calling, gold, rate, name, gold_exp,
                              guess_exp, guess_vit, wipe_exp, wipe_vit, guess_vit')
                                      ->from($this->table)
                                      ->order('level DESC')
                                      ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }
        
        return $info;
    }
}