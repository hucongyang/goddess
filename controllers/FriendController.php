<?php
/**
 * 用户信息接口
 */
class FriendController extends ApiPublicController
{
    /**
     * 获取加好友请求信息列表
     *
     * @param string $user_id
     * @param string $token
     *
     */
    public function actionNewFriendList()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            $this->_return('MSG_ERR_TOKEN');
        }

        //获取用户列表 状态status 0, mess_read = 1,未读
        $ids = UserFriend::model()->newFriendMess($user_id);
        if(!is_array($ids)){
            $this->_return('MSG_ERR_UNKOWN');
        }

        $result = array();
        foreach ($ids as $id) {
            $data = array();
            $tem = array();
            $tem = User::model()->getUserInfo($id['friend_user_id']);

            if(is_array($tem)){
                $data['friend_id'] = $id['friend_user_id'];
                $data['nickname']  = $tem['nickname'];
                $data['face_url']  = $tem['avatar'];
                $data['timestamp'] = $id['update_ts'];
                $result[] = $data;
            }
        }
        $this->_return('MSG_SUCCESS', $result);

    }

    /**
     * 拒绝/接受 好友邀请
     *
     * @param string $user_id
     * @param string $token
     * @param string $friend_id
     * @param string $status   1-已确认, 2-拒绝, 3-取消
     */
    public function actionInviting()
    {
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['friend_id'])
            || !isset($_REQUEST['status'])
        ){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id        = trim(Yii::app()->request->getParam('user_id'));
        $token          = trim(Yii::app()->request->getParam('token'));
        $status         = trim(Yii::app()->request->getParam('status'));
        $friend_id      = trim(Yii::app()->request->getParam('friend_id'));

        if($status != 1 && $status != 2 && $status != 3)
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id) || !is_numeric($friend_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //用户不存在 返回错误
        if($friend_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            $this->_return('MSG_ERR_TOKEN');
        }

        $ids = array();
        $ids = UserFriend::model()->newFriendMess($user_id);
        $friend = array();
        foreach ($ids as $id) {
            $friend[] = $id['friend_user_id'];
        }
        if(!in_array($friend_id, $friend))
        {
            $this->_return('MSG_ERR_NO_FRIEND_REQ');
        }

        $friend_transaction  = Yii::app()->db_friend->beginTransaction();
        try{
            UserFriend::model()->updateFriend($user_id, $friend_id, $status);
            $friend_transaction->commit();
            //log 日志
		$memo = $user_id.'|'.$friend_id.'|'.$status;
		Log::model()->_user_log($user_id, 'REPLY_INVITATION', date("Y-m-d H:i:s"), $memo);
        }catch(Exception $e){

            error_log($e);
            $friend_transaction->rollback();
            $this->_return('MSG_ERR_UNKOWN');
        }

        
        $this->_return('MSG_SUCCESS');

    }
}