<?php
class Gold extends CActiveRecord
{
    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 创建金币使用记录
     * @param unknown $user_id
     * @param unknown $param type 金币类型 1每日领取 2猜牌获得 3 猜牌盗走 4解锁女神 5解锁照片 6充值 7购买道具 8翻牌赚金币 9金币买体力 10软件兑换金币
     * @return boolean
     */
    public function createGold($user_id, $param)
    {
        $p = array(
                'user_id'=>'',
                'type'=>'',
                'value'=>'',
                'gold'=>'',
                'create_ts'=>date("Y-m-d H:i:s")
          );
        $con_characters = Yii::app()->db_characters;
        $param = array_intersect_key($param, $p);
        $table_name = sprintf('gold_%02s', dechex($user_id % 256));
        $con_characters->createCommand()->insert($table_name,
                            $param);
    }

}