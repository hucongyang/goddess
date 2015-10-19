<?php
class PlayerController extends ApiPublicController
{
    /**
     * 获取用户的状态值
     *
     * @param string $user_id
     * @param string $token
     */
    public function actionUserStatus()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){

            $characters_info = Characters::model()->getCharactersInfo($user_id);
            if(is_array($characters_info) && !empty($characters_info)){
                $data = array();
                //获取等级
                $lv = Level::model()->exp2Level($characters_info['exp']);
                if(!empty($lv)){
                    $data['point']         = (int)$characters_info['point'];
                    
                    $data['exp']           = (int)$characters_info['exp'];
                    $data['vit']           = (int)$characters_info['vit'];
                    $data['vit_time']       = $characters_info['vit_time'];
                    $data['level']         = (int)$lv;
                    $data['gold']           = (int)$characters_info['gold'];
                    $data['flowers']           = (int)$characters_info['flowers'];
                    
                    
                    $this->_return('MSG_SUCCESS', $data);
                }
            }
                $this->_return('MSG_ERR_UNKOWN');
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }

        $this->_return('MSG_ERR_UNKOWN');
    }

    /**
     * 给女神送礼物
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $goddess_id
     * @param int    $gift_id
     *
     */
    public function actionGiftGiving()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || 
            !isset($_REQUEST['gift_id']) || !isset($_REQUEST['gift_num'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $goddess_id   = trim(Yii::app()->request->getParam('goddess_id'));
        $gift_id      = trim(Yii::app()->request->getParam('gift_id'));
        $number       = trim(Yii::app()->request->getParam('gift_num'));

        //判断女神id 和 礼品id 数据类型
        if(!is_numeric($goddess_id) || !is_numeric($gift_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        if($number <= 0){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //送礼物
            $res = Gift::model()->giveGift($user_id,$goddess_id,$gift_id,$number);
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_UNKOWN');
            	case -2 : $this->_return('MSG_ERR_NO_GIFT');
            	case -3 : $this->_return('MSG_ERR_NO_FOLLOW');
            	case -4 : $this->_return('MSG_ERR_NO_GOLD');
            	default : break;
            }
            if(isset($res['log']['gold_after']) && isset($res['log']['gold'])){
                //道具购买
                Log::model()->_gold_log($user_id, $res['log']['gold'], $res['log']['gold_after'], 'GOLD_BUY_ITEM', date('Y-m-d H:i:s'), '');
            }
            //送礼物日志
            Log::model()->_gift_log($user_id, $goddess_id, $gift_id, $number, $res['log']['gold'], 'DS_TRIBUTE_GIFT', date('Y-m-d H:i:s'), '');
            //成功
            $this->_return('MSG_SUCCESS', $res['result']);
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 每日登录增加经验值
     *
     * @param int    $user_id
     * @param string $token
     * @param string $action loginPerDay
     */
    /* public function actionDailyLife(){
        // 参数检查
        if(!isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $action       = trim(Yii::app()->request->getParam('action'));
        $now          = date('Y-m-d H:i:s');

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        if(strcmp($action, 'loginPerDay') != 0)
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN'); //#token 错误
        }

        //获取用户游戏角色信息
        $characters_info = Characters::model()->getCharactersInfo($user_id);
        $today = date('ymd');
        if(isset($characters_info['charge_exp_ts']) && isset($characters_info['exp']))
        {
            $updated = date('ymd', $characters_info['charge_exp_ts']);
            if(strcmp($today, $updated) == 0){
                //已经送过经验值
                $this->_return('MSG_ERR_FAIL_DAILY');
            }
            //经验获取等级
            if($lv = Level::model()->exp2Level($characters_info['exp'])){
                $level_info = Level::model()->getLevelRow($lv);
                //拿到等级信息
                $param = array();
                $param['exp'] = $characters_info['exp'] + $level_info['exp_per_day'];

                //更新等级
                $lv = Level::model()->exp2Level($param['exp']);
                if(!empty($lv) && strcmp($lv, $characters_info['level']) != 0){
                    $param['level'] = $lv;
                }
                $param['charge_exp_ts'] = time();
                $characters_transaction = Yii::app()->db_characters->beginTransaction();
                $common_transaction     = Yii::app()->db_common->beginTransaction();
                $result = array();
                try{
                    //增加经验
                    Characters::model()->updateCharacters($user_id, $param);
                    //获取增加后的用户信息
                    $characters_info = Characters::model()->getCharactersInfo($user_id);

                    $result['point'] = $characters_info['point'];
                    $result['exp']   = $characters_info['exp'];
                    $result['vit']   = $characters_info['vit'];
                    $result['level'] = $lv;

                    $characters_transaction->commit();
                    $common_transaction->commit();
                }catch(Exception $e){
                    error_log($e);
                    $characters_transaction->rollback();
                    $common_transaction->rollback();
                    //未知错误
                    $this->_return('MSG_ERR_UNKOWN');
                }
                // 更新角色信息表  日志 增加经验
                //成功
                $this->_return('MSG_SUCCESS', $result);
            }
        }
        //未知错误
        $this->_return('MSG_ERR_UNKOWN');
    } */

    /**
     * 赠送好友体力值
     *
     * @param int $user_id
     * @param int $friend_id
     * @param string $token
     */
    public function actionGiveVit(){
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['friend_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id         = trim(Yii::app()->request->getParam('user_id'));
        $token           = trim(Yii::app()->request->getParam('token'));
        $friend_user_id  = trim(Yii::app()->request->getParam('friend_id'));
        $now          = date('Y-m-d H:i:s');

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //好友不存在
        if($friend_user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN'); //#token 错误
        }
        //是否已经为好友
        $is_exist = UserFriend::model()->isFriend($user_id, $friend_user_id);
        if($is_exist != 1)
        {
            //对方未确认为好友
            $this->_return('MSG_ERR_NO_FRIEND');
        }

        //获取最近一次赠送此好友的时间
        $vit_time = UserFriend::model()->getVitTime($user_id, $friend_user_id);
        if($vit_time > 0){
			
            //查询今天是否已经赠送好友的次数超过上线
            $VitNum = UserFriend::model()->getGiveVitNum($user_id);
            if($VitNum >=  Yii::app()->params['every_give_vit_upper_limit']){
                $this->_return('MSG_ERR_GIVE_VIT_COUNT'); //#
            }
            
            if(strcmp(date('Ymd'), date('Ymd', intval($vit_time))) == 0){
                $list = FriendVit::model()->getUserVit($user_id, $friend_user_id);
                if($list){
                    //今天已经送过了
                    $this->_return('MSG_ERR_FAIL_GIVINGVIT');
                }
            }
            //获取个人信息拿到经验值
            $characters_info = Characters::model()->getCharactersInfo($user_id);
            if(isset($characters_info['exp'])){
                if($lv = Level::model()->exp2Level($characters_info['exp'])){
                    $level_info = Level::model()->getLevelRow($lv);
                    $giving = $level_info['vit_per_giving'];
                    $param = array(
                        'user_id' => $friend_user_id,
                        'friend_user_id' => $user_id,
                        'vit'  => $giving,
                        'status' => 0,
                        'giving_time' => date('Ymd'),
                        'create_ts' => time(),
                        );

                    $friend_transaction = Yii::app()->db_friend->beginTransaction();
                    $vit_transaction     = Yii::app()->db_friend_vit->beginTransaction();
                    try{
                        //更新最新的赠送时间
                        UserFriend::model()->updateFriendRow($user_id, $friend_user_id, array('last_give_vit_ts'=>time()));
                        //更新体力消息
                        FriendVit::model()->insertFriendRow($friend_user_id, $param);

                        $friend_transaction->commit();
                        $vit_transaction->commit();
                    }catch(Exception $e){
                        error_log($e);
                        $friend_transaction->rollback();
                        $vit_transaction->rollback();

                        $this->_return('MSG_ERR_UNKOWN');
                    }
                    //体力日志
                    //送体力成功
                    $this->_return('MSG_SUCCESS');
                }
            }
        }

        $this->_return('MSG_ERR_UNKOWN');
    }

    /*
     * 好友送体力消息列表
     * @param int $user_id
     * @param string $token
     *
     */
    public function actionVitList()
    {
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id         = trim(Yii::app()->request->getParam('user_id'));
        $token           = trim(Yii::app()->request->getParam('token'));

        $now          = date('Y-m-d H:i:s');

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN'); //#token 错误
        }

        //获取好友赠送体力列表
        $info = FriendVit::model()->vitList($user_id);

        $result = array();
        if(is_array($info)){
            foreach ($info as $k => $v) {
                $key = array();
                $user = User::model()->getUserInfo($v['friend_user_id']);
                $username = User::model()->getUsername($v['friend_user_id']);
                if($user === false || $username === false){
                    $this->_return('MSG_ERR_UNKOWN');
                }
                if(!empty($user)){
                    $key['id']        = (int)$v['id'];
                    $key['nickname']  = $user['nickname'];
                    $key['face_url']  = $user['avatar'];
                    $key['vit']       = (int)$v['vit'];
                    $key['username']  = $username;
                    $key['timestamp'] = date('Y-m-d H:i:s', $v['create_ts']);
                    $result[] = $key;
                    unset($username);
                }
            }
        }
        //好友送体力日志
        $this->_return('MSG_SUCCESS', $result);
    }

    /**
     * API: 2.1.3.16 API_I_016
     * 收体力
     * @param int    $user_id
     * @param string $token
     * @param string $message_id
     */
    public function actionReceipt(){
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['message_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $message_id   = trim(Yii::app()->request->getParam('message_id'));
        $now          = date('Y-m-d H:i:s');
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        if(!FriendVit::model()->getUserMess($user_id,$message_id))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN'); //#token 错误
        }
        
        //查询今天收体力的数量是否已经超过上线
        $num = FriendVit::model()->getEveryAcceptVitNum($user_id);
        
        if($num >=  Yii::app()->params['every_accept_vit_upper_limit']){
            $this->_return('MSG_ERR_EVERY_ACCEPT_VIT'); //#
        }
        

        
        //获取用户游戏角色信息
        $player = Characters::model()->getCharactersInfo($user_id);
        if(!is_array($player) || !isset($player['vit'])){
            //未找到用户游戏角色信息
            $this->_return('MSG_ERR_NO_FOUND_P_INFO');
        }
        $level    = Level::model()->exp2Level($player['exp']);
        $level_info    = Level::model()->getLevelRow($level);
        if(isset($level_info['vit_per_giving'])){
            $update_vit = $level_info['vit_per_giving'];
        }
        //最后插入的体力值 增加的+原有的
        $vit = $update_vit + $player['vit'];
        if($vit > $level_info['max_vit']){
            $vit = $level_info['max_vit'];
        }
        if($player['vit'] == $level_info['max_vit']){
            //体力已满不能收体力
            $this->_return('MSG_ERR_FULL_VIT');
        }
        $characters_transaction = Yii::app()->db_characters->beginTransaction();
        $vit_transaction        = Yii::app()->db_friend_vit->beginTransaction();
        $result = array();
        
        try{
            //更新体力值
            Characters::model()->updatePlayerInfo($user_id, array('vit'=>$vit));
            FriendVit::model()->updateVitStatus($user_id, $message_id);
            //返回参数
            $result['point'] = $player['point'];
            $result['exp']   = $player['exp'];
            $result['vit']   = $vit;
            $result['vit_time']   = $player['vit_time'];
            $result['level'] = $level;
            $result['gold']   = $player['gold'];
            $result['flowers']   = $player['flowers'];

            $characters_transaction->commit();
            $vit_transaction->commit();
        }catch(Exception $e){
            error_log($e);
            $characters_transaction->rollback();
            $vit_transaction->rollback();
        }
        //收体力日志
        
        $this->_return('MSG_SUCCESS', $result);
    }

    /**
     * 用户已解锁的照片
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $type //0：所有图片1：相册图片2：剧情图片
     * @param int    $timestamp
     * @param int    $limit
     */
    public function actionUnlockPhotos(){
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['type'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $type         = trim(Yii::app()->request->getParam('type'));
        $timestamp    = trim(Yii::app()->request->getParam('timestamp'));
        $limit        = (int)trim(Yii::app()->request->getParam('limit'));

        $now          = date('Y-m-d H:i:s');

        if(!empty($timestamp)){
            $timestamp = strtotime($timestamp);

            if($timestamp == false){
                $this->_return('MSG_ERR_FAIL_PARAM');
            }
        }

        if(empty($limit)){
            $limit = 10;
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if($type != 0 && $type != 1 && $type != 2){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN'); //#token 错误
        }

        $ret = Photo::model()->unlockList($user_id, $timestamp, $limit);

        if($ret === false){
            $this->_return('MSG_ERR_UNKOWN');
        }

        $result = array();
        if(is_array($ret)){
            foreach ($ret as $pto) {
                $p = Photo::model()->photoInfo($pto['photo_id']);
                $url = Yii::app()->params['img_url_base'];
                if(is_array($p)){
                    $tmp = array();
                    $tmp['id']         = (int)$pto['photo_id'];
                    $tmp['url']        = stripslashes($p['url']);
                    $tmp['thumb']      = stripslashes($p['thumb']);
                    $tmp['level']      = (int)$p['level'];
                    $tmp['praised']    = (int)$pto['status'];
                    $tmp['goddess_id'] = (int)$p['heroine_id'];
                    $tmp['timestamp']  = date('Y-m-d H:i:s', $pto['timestamp']);
                    $result[] = $tmp;
                }
            }
        }
        $this->_return('MSG_SUCCESS', $result);
    }
}