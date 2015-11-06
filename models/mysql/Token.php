<?php
class Token extends CActiveRecord
{
    public $expire_time = 315360000;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 插入用户系统中的token值
     *
     * @param  int $user_id
     *
     * @return  string $token
     */
    public function insertUserToken($user_id, $app_id)
    {
        $time = date("Y-m-d H:i:s");
        // 创建用户token
        $token = md5($user_id.$time.microtime().'mokunShanghai');
        $expire_ts = date("Y-m-d H:i:s", time()+$this->expire_time);
        try{
            $con_token = Yii::app()->db_token;
            $table_name = sprintf('token_%02s', dechex($user_id % 256));
            $con_token->createCommand()->insert($table_name,
                    array('user_id'         => $user_id,
                          'app_id'          => $app_id,
                          'device_id'       => 0,
                          'token'           => $token,
                          'last_login_ts'   => $time,
                          'expire_ts'       => $expire_ts
                          ));
//             $id = Yii::app()->db_token->getLastInsertID();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $token;
    }

    /**
     * 获取用户系统中的token值
     *
     * @param  int    $user_id
     * @param  int    $app_id
     * @return string $token
     */
    public function getUserToken($user_id, $app_id)
    {
        try{
            $con_token = Yii::app()->db_token;
            $table_name = sprintf('token_%02s', dechex($user_id % 256));
            $token = $con_token->createCommand()
                    ->select('token')
                    ->from($table_name)
                    ->where('user_id=:UserId AND app_id=:AppId', array(':UserId' => $user_id, ':AppId' => $app_id))
                    ->queryScalar();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        return $token;
    }

    /**
     * 更新用户系统中的token值
     *
     * @param  int $user_id
     *
     * @return string $token
     */
    public function updateUserToken($user_id, $app_id)
    {
        $time = date("Y-m-d H:i:s");
        $token = md5($user_id.$time.microtime().'mokunShanghai');
        $expire_ts = date("Y-m-d H:i:s", time()+$this->expire_time);

        try{
            $con_token = Yii::app()->db_token;
            $table_name = sprintf('token_%02s', dechex($user_id % 256));
            $con_token->createCommand()->update($table_name,
                    array('last_login_ts' => $time, 'token' => $token, 'expire_ts' => $expire_ts),
                    'user_id=:UserId AND app_id=:AppId', array(':UserId' => $user_id, ':AppId' => $app_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $token;
    }

    /**
     * 指定token过期
     * @param $user_id
     * @param $app_id
     * @return bool
     */
    public function expireToken($user_id, $app_id)
    {
        $yesterday = date("Y-m-d H:i:s", mktime(time()-86400));
        $time      = date("Y-m-d H:i:s");

        try{
            $con_token = Yii::app()->db_token;
            $table_name = sprintf('token_%02s', dechex($user_id % 256));
            $ret = $con_token->createCommand()->update($table_name,
                    array('last_login_ts' => $time, 'expire_ts' => $yesterday),
                    'user_id=:UserId AND app_id=:AppId', array(':UserId' => $user_id, ':AppId' => $app_id));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return $ret;
    }

    /**
     * 验证token
     *
     * @param  int    $user_id
     * @param  string $token
     *
     * @return boolean
     */
    public function verifyToken($user_id, $token, $app_id)
    {
        try{
            $con_token = Yii::app()->db_token;
            $table_name = sprintf('token_%02s', dechex($user_id % 256));
            $result = $con_token->createCommand()
                    ->select('token, expire_ts')
                    ->from($table_name)
                    ->where('user_id = :UserId  AND app_id=:AppId', array(':UserId' => $user_id, ':AppId' => $app_id))
                    ->queryRow();
        }catch(Exception $e){
            error_log($e);
            return false;
        }

        if(is_array($result)){
            if(($result['token'] == (string) $token) && strtotime($result['expire_ts']) >= time() ){
                return true;
            }
        }

        return false;
    }
}