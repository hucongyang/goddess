<?php
class Log extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    
    //用户log
    private $log_code = array(
            // log 对应编号
            'USER_ACTIVE'                       => 1001,     		// 用户手动注册帐号 		 
            'USER_LOGIN'                        => 1002,     		// token验证自动登入			修改TOKEN
            'USER_HAND_LOGIN'                   => 1003,     		// 用户手动登入记录		
            'CHANGE_USERINFO'                   => 1004,     		// 修改用户个人信息	$sex|$nickname|$birthday|$birthplace|$signature|$avatar
            'CHANGE_PASSWORD'                   => 1006,     		// 修改用户密码
            'CHANGE_AVATAR'                     => 1007,     		// 修改头像			$avatar
            
            'ADD_FRIEND'                        => 1015,     		// 添加好友		     memo  $user_id.'|'.$friend_user_id.'|'.$status
            'REPLY_INVITATION'                  => 1026,     		// 回复邀请好友  	     memo  $user_id.'|'.$friend_user_id
            'SYS_USER_ACTIVE'                   => 1029,     		// 系统帐号注册成功	memo: ''
            'CHANGE_USNAME_AND_EMAIL'           => 1030,              // 修改用户名与绑定邮箱  	memo: $username.'|'.$email
        
    );
    // 女神
    private $goddess_code = array(
            'DS_PRAISED'                        => 2001,        // 赞女神				memo $goddess_id
            'DS_FOLLOW'                         => 2002,        // 关注女神			     memo $goddess_id
            'DS_LIKING'                         => 2003,        // 女神对用户的好感值		memo $goddess_id
            'DEL_DS_MESSAGE'                    => 2004,        // 删除女神会话			memo $goddess_id
            'READ_MESSAGE'                      => 2005,        // 阅读一条信息			memo $mess_id.'|'.$type 	0-已读 1-未读 2-删除
            'DO_NOT_DISTURB'                    => 2006,        // 防打扰				memo  '';
            'TERMS_UNLOCK_GODDESS'              => 2007,        // 金币/女神解锁女神-
    );
    
    //照片
    private $photo_code = array(
            'DS_UNLOCK_IMG'                     => 3001,        // 解锁照片普通解锁		memo  vit
            'GODDESS_PHOTO_PRAISED'             => 3002,	    // 赞女神照片		     memo  
            'GUESS_UNLOCK_IMG'                  => 3003,        // 猜牌解锁照片                       memo  
            'WIPE_IMAGE_UNLOCK_IMG'             => 3004,        // 擦图解锁照片                       memo 
            'GOLD_UNLOCK_IMG'                   => 3005,        // 金币解锁照片 -        memo gold    
    );    
    
    //金币日志
    private $gold_code = array(
            'GOLD_DAY_REWARD'                    => 4001,            //每日领取金币	-- 金币     memo 
            'GOLD_EARN_CRSR_REWARD'              => 4002,            //体力翻牌赚金币奖励--       
            'GOLD_ADD_VIT'                       => 4003,            //购买体力减少金币
            'GOLD_BUY_ITEM'                      => 4004,            //金币买道具 --
            'GOLD_SOFTWARE_TO_GOLD'              => 4005,            //软件兑换金币 memo 金币数 --  
            'PAY_BUY_GOLD'                       => 4006,            //充值金币
            'GUESS_IMAGE_PLUS_GOLD'              => 4007,            //猜图 加 金币
            'GUESS_IMAGE_LOWER_GOLD'             => 4008,            //猜图 减 金币
            'GOLD_ADMIN_ADD'                     => 4009,            //管理员 加 减 金币
    );
    
    //游戏日志
    private $game_code = array(
            'GUESS_IMAGE_PHOTO'                 => 5001,            //猜图    $memo type 1女神照片  value-
            'GUESS_IMAGE_NULL'                  => 5002,            //猜图    $memo type 2空牌 | value-
            'GUESS_IMAGE_VIT'                   => 5003,            //猜图    $memo type 3加体力 | value-
            'GUESS_IMAGE_LIKING'                => 5004,            //猜图    $memo type 4加好感 | value-
            'GUESS_IMAGE_PLUS_GOLD'             => 5005,            //猜图    $memo type 5加金币 | value-
            'GUESS_IMAGE_LOWER_GOLD'            => 5006,            //猜图    $memo type 6减金币 | value-
            'GUESS_IMAGE_FLOWERS'               => 5007,            //猜图    $memo type 7加鲜花 | value-
            'GUESS_IMAGE_REWARD'                => 5008,            //猜图    $memo type 1女神照片2空牌3加体力4加好感5加金币6减金币7加鲜花 | value-
            'WIPE_IMAGE_OK_REWARD'              => 5009,            //擦图成功
            'WIPE_IMAGE_ERR_REWARD'             => 5010,            //擦图失败
            'LESSEN_VIT_PLUS_GOLD'              => 5011,            //翻牌赚金币
            'LESSEN_VIT_PLUS_GOLD_REWARD'       => 5012,            //翻牌赚金币提交结果
            'GUESS_WIPE_IMAGE'                  => 5013,            //进入擦图
    );
    
    //体力日志
    private $vit_code = array(
            'GUESS_PLUS_VIT_COUNT'              => 6001,            // 猜牌加体力
            'GUESS_LESSEN_VIT'                  => 6002,            // 猜图消耗体力                memo goddess_id
            'WIPE_IMAGE_VIT'                    => 6003,            // 擦图消耗体力                memo goddess_id
            'GOLD_LESSEN_VIT'                   => 6004,            // 赚金币翻牌消耗体力
            
    );
    
    //礼物日志
    private $gift_code = array(
            'GUESS_FLOWERS_COUNT'               => 7001,            // 猜牌加鲜花数更新
            'DS_TRIBUTE_GIFT'                   => 7002,            // 送女神礼物			memo $gift_id
    );
    
    private $pay_log_code = array(
    		// pay log 对应编号
    		'CREATE_ORDER'		                  => 8001,			// 订单创建
    		'ORDER_PAY_POST'	                  => 8002,			// 订单支付返回POST
    		'ORDER_PAY_RETURN_OK'	             => 8003,			// 订单返回确认付款
    		'ORDER_PAY_RETURN_REPEAT'	        => 8004,			// 订单返回重复确认
    		'ORDER_PAY_SIGN_ERR'                  => 8005,              // 订单返回验签错误
    		'ORDER_PAY_RETURN_SELORDER_ERR'       => 8006,             // 订单返回查询订单错误
    		'ORDER_PAY_RETURN_MONY_ERR'           => 8007,             // 支付金额和返回金额不符
    		'ORDER_IOS_IAP_VERIFY_WRONG'          => 8008,             // 苹果返回错误
    		'ORDER_IOS_IAP_VERIFY_FAIL'           => 8009,             // 苹果返回失败
    		'ORDER_IOS_IAP_VERIFY_OK'             => 8010,             // 苹果返回成功
    );
    
    /*******************************************************
     * 用户Log接口
     *
     * @param $user_id     // 用户ID
     * @param $log_type    // 用户操作类型
     * @param $time        // 用户操作时间
     * @param $memo        // 备注
     *
     * 说明：用户操作对应的log记录
     *******************************************************/
    public function _user_log($user_id, $log_type, $time, $memo = NULL)
    {
        $table_name = sprintf('user_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                array(        'user_id'       => $user_id,
                              'log_type'      => $this->log_code[$log_type],
                              'time'          => $time,
                              'memo'          => $memo,
                              'version'       => $GLOBALS['__VERSION'],
                              'device_id'     => $GLOBALS['__DEVICEID'],
                              'platform'      => $GLOBALS['__PLATFORM'],
                              'channel'       => $GLOBALS['__CHANNEL'],
                              'app_version'   => $GLOBALS['__APPVERSION'],
                              'os_version'    => $GLOBALS['__OSVERSION'],
                              'app_id'        => $GLOBALS['__APPID'],
                              'ip'            => $GLOBALS['__IP']
            					));
        }
        catch(Exception $e)
        {
          error_log($e);
        }
    }

    /*******************************************************
     * 女神相关Log接口
     *
     * @param $user_id     // 用户ID
     * @param $goddess_id    // 女神ID
     * @param $log_type    // 用户操作类型
     * @param $time        // 用户操作时间
     * @param $memo        // 用户操作备注
     *
     * 说明：用户操作对应的log记录
     *******************************************************/
    public function _goddess_log($user_id, $goddess_id, $log_type, $time, $memo = NULL)
    {
        $table_name = sprintf('goddess_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                array(      
                		   'user_id'       => $user_id,
                            'goddess_id'    => $goddess_id,
                            'log_type'      => $this->goddess_code[$log_type],
                            'time'          => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }

    /*******************************************************
     * 照片相关Log接口
    *
    * @param $user_id       // 用户ID
    * @param $goddess_id    // 女神ID
    * @param $photo_id      // 照片ID
    * @param $log_type      // 用户操作类型
    * @param $time          // 用户操作时间
    * @param $memo          // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _photo_log($user_id, $goddess_id, $photo_id, $log_type, $time, $memo = NULL)
    {
        $table_name = sprintf('photo_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'user_id'       => $user_id,
                            'goddess_id'    => $goddess_id,
                            'photo_id'      => $photo_id,
                            'log_type'      => $this->photo_code[$log_type],
                            'create_ts'     => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 金币相关Log接口
    *
    * @param $user_id     // 用户ID
    * @param $gold        // 金币数
    * @param $gold_after  // 剩余金币数
    * @param $log_type    // 用户操作类型
    * @param $time        // 用户操作时间
    * @param $memo        // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _gold_log($user_id, $gold, $gold_after, $log_type, $time, $memo = NULL)
    {
        $table_name = sprintf('gold_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'user_id'       => $user_id,
                            'gold'          => $gold,
                            'gold_after'    => $gold_after,
                            'log_type'      => $this->gold_code[$log_type],
                            'create_ts'     => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 游戏相关Log接口
    *
    * @param $user_id     // 用户ID
    * @param $goddess_id  // 女神ID
    * @param $game_id     // 游戏ID
    * @param $value       // 数值
    * @param $log_type    // 用户操作类型
    * @param $time        // 用户操作时间
    * @param $memo        // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _game_log($user_id, $goddess_id, $game_id, $value, $game_type, $log_type,  $time, $memo = NULL)
    {
        $table_name = sprintf('game_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'user_id'       => $user_id,
                            'goddess_id'       => $goddess_id,
                            'log_type'      => $this->game_code[$log_type],
                            'game_id'       => $game_id,
                            'value'         => $value,
                            'game_type'     => $game_type,
                            'create_ts'     => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 礼物相关Log接口
    *
    * @param $user_id     // 用户ID
    * @param $goddess_id    // 女神ID
    * @param $log_type    // 用户操作类型
    * @param $time        // 用户操作时间
    * @param $memo        // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _gift_log($user_id, $goddess_id, $gift_id, $count, $gold, $log_type,  $time, $memo = NULL)
    {
        $table_name = sprintf('gift_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'user_id'       => $user_id,
                            'goddess_id'    => $goddess_id,
                            'gift_id'       => $gift_id,
                            'count'         => $count,
                            'gold'         => $gold,
                            'log_type'      => $this->gift_code[$log_type],
                            'create_ts'     => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 体力相关Log接口
    *
    * @param $user_id     // 用户ID
    * @param $vit         // 消耗体力值
    * @param $log_type    // 用户操作类型
    * @param $time        // 用户操作时间
    * @param $memo        // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _vit_log($user_id, $vit, $vit_after, $log_type,  $time, $memo = NULL)
    {
        $table_name = sprintf('vit_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'user_id'       => $user_id,
                            'vit'           => $vit,
                            'vit_after'     => $vit_after,
                            'log_type'      => $this->vit_code[$log_type],
                            'create_ts'     => $time,
                            'memo'          => $memo,
                            'version'       => $GLOBALS['__VERSION'],
                            'device_id'     => $GLOBALS['__DEVICEID'],
                            'platform'      => $GLOBALS['__PLATFORM'],
                            'channel'       => $GLOBALS['__CHANNEL'],
                            'app_version'   => $GLOBALS['__APPVERSION'],
                            'os_version'    => $GLOBALS['__OSVERSION'],
                            'app_id'        => $GLOBALS['__APPID'],
                            'ip'            => $GLOBALS['__IP']));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 接口时间统计 log
    *
    * @param $user_id     // 用户ID
    * @param $log_type    // 用户操作类型
    * @param $time        // 用户操作时间
    * @param $memo        // 用户操作备注
    *
    * 说明：用户操作对应的log记录
    *******************************************************/
    public function _time_log($controller, $action, $time, $user_id, $memo = NULL)
    {
        $table_name = sprintf('goddess_time_log_%s', date('Ym'));
        $con_log = Yii::app()->db_log;
        try
        {
            $con_log->createCommand()->insert($table_name,
                    array(
                            'controller'    => $controller,
                            'action'        => $action,
                            'time'          => $time,
                            'user_id'       => $user_id,
                            'add_time'      => date('Y-m-d H:i:s',time()),
                            'memo'          => $memo));
        }
        catch(Exception $e)
        {
            error_log($e);
        }
    }
    
    /*******************************************************
     * 支付 Log接口
    *
    * @param $user_id		// 用户ID
    * @param $log_type		// 用户操作类型
    * @param $time			// 用户操作时间
    *
    * 说明：游戏内操作对应的log记录
    *******************************************************/
    public function _pay_log($user_id, $log_type, $time, $mokun_trade_no='-1', $memo = NULL)
    {
    	$table_name = sprintf('pay_log_%s', date('Ym'));
    	$con_user = Yii::app()->db_log;
    	try
    	{
    		$data = array('user_id'			=> $user_id,
    				'log_type'		=> $this->pay_log_code[$log_type],
    				'create_ts'			=> $time,
    				'mokun_trade_no' => $mokun_trade_no,
    				'memo'			=> $memo,
    				'version'       => $GLOBALS['__VERSION'],
    				'device_id'     => $GLOBALS['__DEVICEID'],
    				'platform'      => $GLOBALS['__PLATFORM'],
    				'channel'       => $GLOBALS['__CHANNEL'],
    				'app_version'   => $GLOBALS['__APPVERSION'],
    				'os_version'    => $GLOBALS['__OSVERSION'],
    				'app_id'        => $GLOBALS['__APPID'],
    				'ip'            => $GLOBALS['__IP']
    		
    		);
    		$con_user->createCommand()->insert($table_name, $data);
    	}
    	catch(Exception $e)
    	{
    		error_log($e);
    	}
    }
    
    
}