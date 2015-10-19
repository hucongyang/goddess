<?php
class UserInfo extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 用户详情表新增一条用户信息
     *
     * @param  string $username
     * @param  string $sex
     * @param  string $age
     * @param  string $avatar
     *
     * @return int   $uid
     */
    public function insertUserInfo($user_id, $sex=0, $age=null, $avatar=null, $constellation=0, $nickname=null, $signature=null, $birthday=null)
    {
        try{
            $con_user = Yii::app()->db_user;
            $table_name = sprintf('user_info_%02s', dechex($user_id % 256));
            $con_user->createCommand()->insert($table_name,
                    array('user_id'        => $user_id,
                          'sex'           => $sex,
                          'age'           => $age,
                          'constellation' => $constellation,
                          'nickname'      => $nickname,
                          'signature'     => $signature,
                          'birthday'      => $birthday
                          ));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return true;
    }

}