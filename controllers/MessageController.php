<?php
class MessageController extends ApiPublicController
{

	/**
     * 获取未读消息列表
     *
     * @param string $user_id
     * @param string $token
     * @param string $device_id
     */
    public function actionPushMessage()
    {

        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['device_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
		
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $device_id   = trim(Yii::app()->request->getParam('device_id'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){

            $message = Message::model()->newMessageRow($user_id);
            if(!empty($message)){
                $this->_return('MSG_SUCCESS', $message);
            }else{
                $this->_return('MSG_SUCCESS', '');
            }
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 推送客户端注册极光ID registration_id
     * 
     * @param string $user_id
     * @param string $token
     * @param string $registration_id
     * 
     */
    public function actionPushReg()
    {

	    	// 参数检查
	    	if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['registration_id'])){
	    		$this->_return('MSG_ERR_LESS_PARAM');
	    	}

	    	$user_id     = trim(Yii::app()->request->getParam('user_id'));
	    	$token       = trim(Yii::app()->request->getParam('token'));
	    	$registration_id   = trim(Yii::app()->request->getParam('registration_id'));
    		if(!is_numeric($user_id)){
            	$this->_return('MSG_ERR_FAIL_PARAM');
       	 }
        // if($GLOBALS['__APPID'] != 10){
            // $this->_return('MSG_SUCCESS','');
        // }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
        	  $info = User::model()->pushRegister($user_id,$registration_id,$GLOBALS['__PLATFORM']);
            if(!empty($info)){
            	$this->_return('MSG_SUCCESS', '');
            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

    }


    /**
     * 获取会话列表
     *
     * @param string $user_id
     * @param string $token
     * @param string $time // 可选
     */
    public function actionMessList()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $time        = trim(Yii::app()->request->getParam('time'));
		
        /* $timestamp   = empty($time) ? 0 : (int)$time;
        //获取用户id
        if($timestamp > 0){
            $time = date("Y-m-d H:i:s", $timestamp);
        }else{ */
            $time = null;
        /* } */

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
		
            // 获取用户所有未读消息
            $info = Message::model()->noReadMessage($user_id, $time);
            //重构用户女神消息体
            $result = Message::model()->newMessageList($info,$user_id);

            $this->_return('MSG_SUCCESS', $result);
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }


    /**
     * 消息详细
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $message_id
     *
     */
    public function actionMessInfo()
    {
	    	// 参数检查
	    	if(!isset($_REQUEST['user_id'])
	    	|| !isset($_REQUEST['token'])
	    	|| !isset($_REQUEST['message_id'])){
	    		$this->_return('MSG_ERR_LESS_PARAM');
	    	}

	    	$user_id     = trim(Yii::app()->request->getParam('user_id'));
	    	$token       = trim(Yii::app()->request->getParam('token'));
	    	$message_id  = trim(Yii::app()->request->getParam('message_id'));

	    	//过滤message_id
	    	if(!is_numeric($message_id) || $message_id < 1){
	    		$this->_return('MSG_ERR_FAIL_PARAM');
	    	}

	    	if(!is_numeric($user_id)){
	    		$this->_return('MSG_ERR_FAIL_PARAM');
	    	}

	    	$now = time();

	    	//用户不存在 返回错误
	    	if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	    	//验证token
	    	if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	    		//查询消息内容
	    		$result = Message::model()->getUserMessInfo($user_id, $message_id);
	    		if(!empty($result)){
	    			$this->_return('MSG_SUCCESS', $result);
	    		}else{
	    			$this->_return('MSG_ERR_UNKOWN');
	    		}
	    	}else{
	    		//token 错误
	    		$this->_return('MSG_ERR_TOKEN');
	    	}
    }



    /**
     * 删除会话
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $goddess_id
     */
    public function actionDelGoddessMess()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));

        //过滤女神id
        if(!is_numeric($goddess_id) && $goddess_id < 1){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            if(Message::model()->delGoddessMess($user_id, $goddess_id)){
				//清空女神信息
				$memo = '';
				Log::model()->_goddess_log($user_id, $goddess_id, 'DEL_DS_MESSAGE', date('Y-m-d H:i:s'), $memo);
                $this->_return('MSG_SUCCESS', '');
            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 阅读消息
     * 
     * 增加图片 解锁逻辑
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $message_id
     */
    public function actionReadMess()
    {
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['message_id']))
        {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $message_id   = trim(Yii::app()->request->getParam('message_id'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //读 解锁照片 
            $message = Message::model()->readMess($user_id, $message_id);
            //更新为已读接口
            Message::model()->updateMessType($user_id, $message_id, 1);
            
            if(empty($message))
            {
                $this->_return('MSG_ERR_FAIL_MESS');
            }

            $this->_return('MSG_SUCCESS');
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 删除消息
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $message_id
     * @param int    $status        //1:已读：0未读  2删除
     *
     */
    public function actionDelMess()
    {
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['message_id'])
            || !isset($_REQUEST['status'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $message_id  = trim(Yii::app()->request->getParam('message_id'));
        $status      = trim(Yii::app()->request->getParam('status'));

        //过滤message_id
        if(!is_numeric($message_id) || $message_id < 1){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($status)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if($status != 0 && $status != 1 && $status != 2){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //前段送0未读状态过来，直接判定为删除。直接删除
        if($status == 0){
            $status = 2;
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){

            if(Message::model()->updateMessType($user_id, $message_id, $status)){
				//读消息
				$memo = $message_id.'|2';
				Log::model()->_goddess_log($user_id, 0, 'READ_MESSAGE', date('Y-m-d H:i:s'), $memo);
                $this->_return('MSG_SUCCESS', '');
            }else{
                
                $this->_return('MSG_ERR_UNKOWN');
            }
        }else{
            
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }
}