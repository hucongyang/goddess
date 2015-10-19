<?php
class Upload extends CActiveRecord
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
    public function insertUpload($param)
    {
        try{
            $con_game = Yii::app()->db_upload;
            $con_game->createCommand()->insert('upload',$param);
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        
        return true;
    }

    /**
     * 创建
     * @param unknown $user_id
     * @param unknown $param
     * @return boolean
     */
    public function insertUserGoddess($param)
    {
        try{
            $con_game = Yii::app()->db_upload;
            $user_goddess_ret = $con_game->createCommand()
                ->select('user_goddess_id')
                ->from('user_goddess')
                ->where('qq=:QQ',array(':QQ' => $param['qq']))
                ->queryRow();
            if(!($user_goddess_ret)){
                $con_game->createCommand()->insert('user_goddess',$param);
                $user_goddess_id =  Yii::app()->db_upload->getLastInsertID();
            }else{
                $user_goddess_id = $user_goddess_ret['user_goddess_id'];
            }
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $user_goddess_id;
    }
    
    
}