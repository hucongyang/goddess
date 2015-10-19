<?php
class UserMachineInfo extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * 用户详情表新增一条用户信息
     *
     * @param  string $username
     * @param  string $password
     * @param  string $email
     * @param  string $mobile
     *
     * @return int   $user_id
     */
    public function insertUserMachineInfo($user_id, $ip, $deviceId, $platform, $appversion, $channel, $osversion)
    {
        $user_id = intval($user_id);

        try{
            // 获取连接
            $con_user = Yii::app()->db_user;
            // 记录用户设备信息
            $table_name = sprintf('user_machine_info_%02s', dechex($user_id % 256));
            $con_user->createCommand()->insert($table_name,
                        array('user_id'                 => $user_id,
                              'reg_ip'                  => $ip,
                              //'version'             => $version,
                              'device_id'               => $deviceId,
                              'platform'                => $platform,
                              'appversion'              => $appversion,
                              'channel'                 => $channel,
                              'os_version'              => $osversion));
        }catch(Exception $e){
            error_log($e);
            return false;
        }
        return true;

    }

}