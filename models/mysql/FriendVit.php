<?php
class FriendVit extends CActiveRecord
{
    public $table = 'vit';

    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 送体力值
     */
    public function insertFriendRow($friend_user_id, $param){

        try{
            $con_vit = Yii::app()->db_friend_vit;
    
            $vit_table = sprintf( $this->table.'_%02s', dechex($friend_user_id % 256));
            $con_vit->createCommand()
                        ->insert($vit_table,
                        $param);
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return true;
    }

    /**
     * 获取好友赠送体力列表
     */
    public function vitList($user_id){

        $info = array();
        try{
            $con_vit = Yii::app()->db_friend_vit;
            $vit_table = sprintf( $this->table.'_%02s', dechex($user_id % 256));
            $info = $con_vit->createCommand()
                        ->select('id, friend_user_id, vit, create_ts')
                        ->from($vit_table)
                        ->where('user_id=:user_id AND status=0')
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $info;
    }
    
    /**
     * 获取好友是否赠送过体力
     */
    public function getUserVit($user_id,$friend_id){
    
        $info = array();
        try{
            $con_vit = Yii::app()->db_friend_vit;
            $vit_table = sprintf( $this->table.'_%02s', dechex($friend_id % 256));
            $info = $con_vit->createCommand()
            ->select('id, friend_user_id, vit, create_ts')
            ->from($vit_table)
            ->where('user_id=:user_id AND friend_user_id=:friend_id AND status=0')
            ->bindParam(':user_id', $friend_id, PDO::PARAM_INT, 11)
            ->bindParam(':friend_id', $user_id, PDO::PARAM_INT, 11)
            ->queryAll();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $info;
    }

    /**
     *更新好友赠送体力值为已经领取
     */
    public function updateVitStatus($user_id, $param){
        try{
            $con_vit = Yii::app()->db_friend_vit;
            $vit_table = sprintf( $this->table.'_%02s', dechex($user_id % 256));
            $con_vit->createCommand()
                        ->update($vit_table,
                             array('status'=>'1'),
                             "user_id=:user_id AND id IN(".$param.")",
                             array(':user_id'=>$user_id)
                         );
        }catch(Exception $e){
            error_log($e);
            return false;
        }
    }
    
    /**
     * 查询每日收体力数量
     * @param unknown $user_id
     */
    public function getEveryAcceptVitNum($user_id){
        $now = date('Ymd');
        $con_vit = Yii::app()->db_friend_vit;
        $vit_table = sprintf( $this->table.'_%02s', dechex($user_id % 256));
        
        $info = $con_vit->createCommand()
                        ->select('count(*) as c')
                        ->from($vit_table)
                        ->where('user_id=:user_id AND status=1 AND giving_time =:GIVING_TIME ')
                        ->bindParam(':user_id', $user_id, PDO::PARAM_INT, 11)
                        ->bindParam(':GIVING_TIME', $now, PDO::PARAM_INT, 11)
                        ->queryRow();
        return $info['c'];
    }
    
    /**
     * 查询用户是否有这条消息
     */
    public function getUserMess($user_id, $message_id){
    
        $info = array();
        try{
            $con_vit = Yii::app()->db_friend_vit;
            $vit_table = sprintf( $this->table.'_%02s', dechex($user_id % 256));
            $info = $con_vit->createCommand()
            ->select('id')
            ->from($vit_table)
            ->where('id=:ID AND status=0')
            ->bindParam(':ID', $message_id, PDO::PARAM_INT, 11)
            ->queryRow();
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $info;
    }
}