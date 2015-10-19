<?php
class EarnGold extends CActiveRecord
{
    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 创建
     * @param unknown $user_id
     * @param unknown $param 
     * @return boolean
     */
    public function insertGuess($user_id, $param)
    {
        try{
            $con_game = Yii::app()->db_game;
            $table_name = sprintf('earn_gold_%02s', dechex($user_id % 256));
            $con_game->createCommand()->insert($table_name,$param);
            $set_id =  Yii::app()->db_game->getLastInsertID();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $set_id;
        
    }

    /**
     * 更新
     * @param unknown $user_id
     * @param unknown $id
     * @param unknown $param
     * @return boolean
     */
    public function updateGuess($user_id, $id, $param)
    {
        try{
            $con_game = Yii::app()->db_game;
            $table_name = sprintf('earn_gold_%02s', dechex($user_id % 256));
            $con_game->createCommand()->update($table_name,
                    $param,
                    'id=:ID', array(':ID' => $id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }
    
}