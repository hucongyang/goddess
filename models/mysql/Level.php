<?php
class Level extends CActiveRecord
{

	public $table = 'level';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function db()
    {
    	return Yii::app()->db_common;
    }

    /**
     * 等级表信息
     * @return array
     */
    public function wholeLevelAround()
    {
	    	$level_redis = new Level_redis();
	    	$data = $level_redis->getLevel();
	    	if(!empty($data)){
	    		return $data;
	    	}else{
	    	   $info = array();
	        try{
	            $info = $this->db()->createCommand()
	                    ->select('level, flowers, max_vit, vit_per_hour as vit_per_time, min_exp, max_exp, max_info_num,
	                        max_follower, vit_per_giving, exp_per_day, gold_vit as vit_lottery_gold,  buy_vit as vit_recharge_gold')
	                    ->from($this->table)
	                    ->queryAll();
	        }catch(Exception $e){
	            error_log($e);
	        }
	
	        if(!empty($info)){
	        	foreach ($info as $key => $value) {
	        		$info[$key]['level']          = (int) $value['level'];
	        		$info[$key]['flowers']       = (int) $value['flowers'];
	        		$info[$key]['max_vit']        = (int) $value['max_vit'];
	        		$info[$key]['vit_per_time']   = (int) $value['vit_per_time'];
	        		$info[$key]['min_exp']        = (int) $value['min_exp'];
	        		$info[$key]['max_exp']        = (int) $value['max_exp'];
	        		$info[$key]['max_info_num']   = (int) $value['max_info_num'];
	        		$info[$key]['max_follower']   = (int) $value['max_follower'];
	        		$info[$key]['vit_per_giving'] = (int) $value['vit_per_giving'];
	        		$info[$key]['exp_per_day']    = (int) $value['exp_per_day'];
	        		$info[$key]['vit_lottery_gold']   = (int) $value['vit_lottery_gold'];
	        		$info[$key]['vit_recharge_gold']  = (int) $value['vit_recharge_gold'];
	        		
	        	}
	        }
	        $level_redis->addLevel(json_encode($info));
	        return $info;
		  }
    }

    public function getLevelRow($lv)
    {
        $info = array();
        try{
            $info = $this->db()->createCommand()
                    ->select('level, min_exp, max_exp, max_vit, vit_per_hour, max_info_num,
                        max_follower, vit_per_giving, exp_per_day, gold_vit, flowers, buy_vit')
                    ->from($this->table)
                    ->where('level=:level')
                    ->bindParam(':level', $lv, PDO::PARAM_STR, 25)
                    ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }

        return $info;
    }

    /**
     * 通过经验判断等级
     *
     */
    public function exp2Level($exp)
    {

    	$lv = null;
        try{
            $lv = $this->db()->createCommand()
                    ->select('level')
                    ->from($this->table)
                    ->where('max_exp >=:exp')
                    ->order('max_exp')
                    ->limit('1', '0')
                    ->bindParam(':exp', $exp, PDO::PARAM_INT, 11)
                    ->queryScalar();
            //根据经验值查询对应的等级，如果返回为空，查询经验值最大的等级返回。
        }catch(Exception $e){
            error_log($e);
        }

        return $lv;
    }
    
    /**
     * 查询最大等级
     * @return unknown
     */
    public function getMaxLevelRow()
    {
        $info = array();
        try{
            $info = $this->db()->createCommand()
            ->select('level, min_exp, max_exp, max_vit, vit_per_hour, max_info_num,
                        max_follower, vit_per_giving, exp_per_day, gold_vit, flowers, buy_vit')
                            ->from($this->table)
                            ->order('level DESC')
                            ->queryRow();
        }catch(Exception $e){
            error_log($e);
        }
    
        return $info;
    }
}
