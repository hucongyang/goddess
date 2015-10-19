<?php
/**
 * 用户信息接口
 */
class UserController extends ApiPublicController
{

    /**
     * 用户注册接口 actionRegister
     *
     * @version 1.0
     *
     * @param string        $GLOBALS['__IP']           // IP
     * @param string        $GLOBALS['__VERSION']      // 接口版本号
     * @param string        $GLOBALS['__DEVICEID']     // 设备ID (open-udid)
     * @param string        $GLOBALS['__PLATFORM']     // 平台（IOS|Android…）平台控制使用
     * @param string        $GLOBALS['__APPVERSION']   // App版本号
     * @param int           $GLOBALS['__CHANNEL']      // 渠道 (Google|baidu|91|appstore…)渠道控制使用
     * @param string        $GLOBALS['__OSVERSION']    // 操作系统版本号
     *
     * @param username      //用户名
     * @param password      //密码
     * @param email         //邮箱
     *
     * @return object user  //用户信息
     *
     */
    public function actionRegister()
    {
        // 参数检查
        if(    !isset($_REQUEST['username'])
            || !isset($_REQUEST['password'])
            || !isset($_REQUEST['email'])
            || empty($_REQUEST['username'])
            || empty($_REQUEST['password'])
            || empty($_REQUEST['email']))
        {
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
        //版本大于1.0 时增加用户昵称参数
        if($GLOBALS['__VERSION'] > '1.0'){
            if(empty($_REQUEST['nickName'])){
                $this->_return('MSG_ERR_LESS_PARAM');
            }
        }

        $username = trim(Yii::app()->request->getParam('username'));
        $password = trim(Yii::app()->request->getParam('password'));
        $email    = trim(Yii::app()->request->getParam('email'));
        $nickname  = trim(Yii::app()->request->getParam('nickName'));
        $mobile    = null;
        
        //版本大于1.0 判定用户昵称位数
        if($GLOBALS['__VERSION'] > '1.0'){
            if(strlen($nickname) > 32 && strlen($nickname) < 6){
                $this->_return('MSG_ERR_NIKENAME_LENGTH');
            }
            $nickname   = mb_substr($nickname, 0, 32, 'utf-8');
        }
        //密码格式不正确
        if(!$this->isPasswordValid($password))
        {
            $this->_return('MSG_ERR_PASSWORD_INPIT');
        }
        //邮箱格式不正确
        if(!$this->isEmail($email)){
            $this->_return('MSG_ERR_EMAIL_INPUT');
        }
        //用户名包含敏感词，不能注册。
        if($this->isExistShieldWord($username)){
            $this->_return('MSG_ERR_USERNAME_WORD');
        }
        //用户名长度错误，只能6到14位字符
        if(!$this->isUsernameValid($username)){
            $this->_return('MSG_ERR_USERNAME_LENGTH');
        }
        //email 不能大于50
        if(strlen($email) > 50){
            $this->_return('MSG_ERR_EMAIL_INPUT');
        }

        //获取id  #如果用户已存在 返回错误
        $user_id = User::model()->getUserId($username);
        if($user_id > 0) $this->_return('MSG_ERR_USERNAME_EXIST'); #20003

        if(strcmp($username, $password) == 0)
        {
            $this->_return('MSG_ERR_SET_SAME_PASSWORD');
        }

        $user_id = User::model()->getUserId(false, $email);
        if($user_id > 0) $this->_return('MSG_ERR_EMAIL_EXIST');

        // 创建用户并获取$user_id
        $user_transaction  = Yii::app()->db_user->beginTransaction();
        $characters_transaction = Yii::app()->db_characters->beginTransaction();
        $token_transaction = Yii::app()->db_token->beginTransaction();

        $now = date("Y-m-d H:i:s");
        try
        {

            //用户表    goddess_user.user AND goddess_user.user%02s 插入
            //加密
            $password = $this->_encrypt($password, $this->getCryptkey());
            $user_id = User::model()->insertUser($username, $password, $email, $mobile, $now);

            //用户信息  goddess_user.user_info 插入
            UserInfo::model()->insertUserInfo($user_id, 0, null, null, 0, $nickname);

            //硬件信息  goddess_user.user_machine_info 插入
            UserMachineInfo::model()->insertUserMachineInfo($user_id, $GLOBALS['__IP'],
                                          $GLOBALS['__DEVICEID'], $GLOBALS['__PLATFORM'],
                                          $GLOBALS['__APPVERSION'], $GLOBALS['__CHANNEL'],
                                           $GLOBALS['__OSVERSION'] );

            // 创建游戏内帐号
            Consumer::model()->createGameCharacters($user_id, $now);
            /* switch($GLOBALS['__APPID'])
            {
                // goddess 女神零距离
                case 10 : Consumer::model()->createGameCharacters($user_id, $now); break;
                default : Consumer::model()->createGameCharacters($user_id, $now); break;
            } */

            //创建token
            Token::model()->insertUserToken($user_id, $GLOBALS['__APPID']);

            //每日玫瑰发放
            Characters::model()->everyRose($user_id);
            
            // 提交事务
            $user_transaction->commit();
            $characters_transaction->commit();
            $token_transaction->commit();
        }
        catch(Exception $e)
        {
            error_log($e);
            $user_transaction->rollback();
            $characters_transaction->rollback();
            $token_transaction->rollback();
            //日志
            // Log::model()->_user_log($user_id, 'REGISTER_FAIL', $now, '数据库写入失败');
            $this->_return('MSG_ERR_UNKOWN');
        }

        if($user_id <= 0)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }

        $token = Token::model()->getUserToken($user_id, $GLOBALS['__APPID']);
		
        // 发送返回值
        $data = array();
        $data['username'] = $username;
        $data['token'] = $token;
        $data['user_id'] = (int) $user_id;
        // 注册成功 写入日志
        Log::model()->_user_log($user_id, 'USER_ACTIVE', date("Y-m-d H:i:s"), '');
        
        $this->_return('MSG_SUCCESS', $data);
    }

    /**
     * 系统注册账号
     */
    public function actionSysRegister(){
        $user_transaction  = Yii::app()->db_user->beginTransaction();
        $characters_transaction = Yii::app()->db_characters->beginTransaction();
        $token_transaction = Yii::app()->db_token->beginTransaction();
        $now = date("Y-m-d H:i:s");
        $is_register       = trim(Yii::app()->request->getParam('is_register'));
        try
        {
            $password = null;
            if($is_register != 1){
                $lastAccountData = User::model()->getLastAccount();
                if($lastAccountData){
                    if($lastAccountData['password'] != null){
                        //修改过密码，请登录
                        $this->_return('MSG_ERR_EDIT_PASSWORD_LOGIN');
                    }
                    $user_id = $lastAccountData['user_id'];
                    $token = Token::model()->getUserToken($user_id, $GLOBALS['__APPID']);
                    if(!isset($token)){
                        //创建token
                        $token = Token::model()->insertUserToken($user_id, $GLOBALS['__APPID']);
                    }else{
                        //返回的token 值
                        $token = Token::model()->updateUserToken($user_id, $GLOBALS['__APPID']);
                    }
                    $data = array();
                    $data['username'] = $lastAccountData['user_name'];
                    $data['token'] = $token;
                    $data['user_id'] = (int) $user_id;
                    $user_transaction->commit();
                    $characters_transaction->commit();
                    $token_transaction->commit();
                    $this->_return('MSG_SUCCESS', $data);
    
                }
            }
            //用户表    goddess_user.user AND goddess_user.user%02s 插入
            $res = User::model()->SysInsertUser($password);
            $user_id = $res['user_id'];
            //用户信息  goddess_user.user_info 插入
            UserInfo::model()->insertUserInfo($user_id, 0, '', '', 0, '游客');
             
            //硬件信息  goddess_user.user_machine_info 插入
            UserMachineInfo::model()->insertUserMachineInfo($user_id, $GLOBALS['__IP'],
            $GLOBALS['__DEVICEID'], $GLOBALS['__PLATFORM'],
            $GLOBALS['__APPVERSION'], $GLOBALS['__CHANNEL'],
            $GLOBALS['__OSVERSION'] );
    
            
            // 创建游戏内帐号
            Consumer::model()->createGameCharacters($user_id, $now);
            /* switch($GLOBALS['__APPID'])
            {
                // goddess 女神零距离
            	case 10 : Consumer::model()->createGameCharacters($user_id, $now); break;
            	default : 
            	    Consumer::model()->createGameCharacters($user_id, $now);
            	    break;
            } */
    
            //创建token
            $token = Token::model()->insertUserToken($user_id, $GLOBALS['__APPID']);
            //每日玫瑰发放
            Characters::model()->everyRose($user_id);
    
            // 提交事务
            $user_transaction->commit();
            $characters_transaction->commit();
            $token_transaction->commit();
            // 注册成功 写入日志
            Log::model()->_user_log($user_id, 'SYS_USER_ACTIVE', date('Y-m-d H:i:s'), '');
        }
        catch(Exception $e)
        {
            error_log($e);
            $user_transaction->rollback();
            $characters_transaction->rollback();
            $token_transaction->rollback();
            $this->_return('MSG_ERR_UNKOWN');
        }
    
        if($user_id <= 0)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }
    
        // 发送返回值
        $data = array();
        $data['username'] = $res['user_name'];
        $data['token'] = $token;
        $data['user_id'] = (int) $user_id;
        $this->_return('MSG_SUCCESS', $data);
    }
    
    /**
     * 用户登陆接口 actionLogin
     *
     * @param username      //用户名
     * @param password      //密码
     *
     *
     */
    public function actionLogin()
    {
        // 参数检查
        if(!isset($_REQUEST['username']) || !isset($_REQUEST['password'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $username = trim(Yii::app()->request->getParam('username'));
        $password = trim(Yii::app()->request->getParam('password'));

        $user_id = User::model()->getUserId($username);

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //获取用户信息
        $user_info = User::model()->getUserSafeInfo($user_id);
        if($username == $user_info['user_name']){
            if($password == ''){
                $this->_return('MSG_ERR_PASSWORD_ERR');
            }
        }
        
        $now = date("Y-m-d H:i:s");
        $password = $this->_encrypt($password, $this->getCryptkey());
        
        if(strcmp($password, $user_info['password']) == 0){

            //返回的token 值
		  $return_token = Token::model()->updateUserToken($user_id, $GLOBALS['__APPID']);
            if($return_token){
            	 //登录日志 修改TOKEN
            	 Log::model()->_user_log($user_id, 'USER_HAND_LOGIN', date("Y-m-d H:i:s"));
                $data = Consumer::model()->getUserInfo($user_id);
				
                if($data){
                    $data['token']   = $return_token;
                    //每日玫瑰发放
                    Characters::model()->everyRose($user_id);
                    $this->_return('MSG_SUCCESS', $data);
                }else{
                    $this->_return('MSG_ERR_UNKOWN');
                }

            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }
        }else{
            //密码错误
            $this->_return('MSG_ERR_PASSWORD_WRONG');
        }
    }

    /**
     * 用户注销接口 actionLogout	
     *
     * @param string $user_id      //用户名
     * @param string $token
     *
     */
    public function actionLogout()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
	   }

        $now       = date("Y-m-d H:i:s");
        $user_id   = trim(Yii::app()->request->getParam('user_id'));
        $token     = trim(Yii::app()->request->getParam('token'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //token 过期
            if(Token::model()->expireToken($user_id, $GLOBALS['__APPID'])){
                //退出不写LOG
                $this->_return('MSG_SUCCESS');
            }
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 获取用户个人信息  actionGetUserInfo
     *
     * @param string $user_id
     * @param string $token
     *
     */
    public function actionGetUserInfo()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now      = date("Y-m-d H:i:s");
        $user_id  = trim(Yii::app()->request->getParam('user_id'));
        $token    = trim(Yii::app()->request->getParam('token'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){

        //获取用户信息
	   $data = Consumer::model()->getUserInfo($user_id);
        if($data){
                $data['token'] = $token;
                $this->_return('MSG_SUCCESS', $data);
            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }

        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 用户修改个人信息  actionChangeUserInfo
     *
     * @param string $user_id
     * @param string $token
     * @param int    $sex
     * @param string nickname
     * @param string birthday
     * @param string birthplace
     * @param string signature
     *
     * @return
     */
    public function actionChangeUserInfo()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now         = date("Y-m-d H:i:s");
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));

        $sex         = intval(trim(Yii::app()->request->getParam('sex')));

        $nickname    = trim(Yii::app()->request->getParam('nickname'));
        $birthday    = trim(Yii::app()->request->getParam('birthday'));
        $birthplace  = trim(Yii::app()->request->getParam('birthplace'));
        $signature   = trim(Yii::app()->request->getParam('signature'));

        if($sex != 0 && $sex != 1 && $sex != 2)
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }else{
            $param['sex'] = $sex;
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证$birthday 合法性
        if(!strtotime($birthday.' 00:00:00'))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $nickname   = mb_substr($nickname, 0, 32, 'utf-8');
        //出生地32字节
        $birthplace = mb_substr($birthplace, 0, 32, 'utf-8');
        $signature  = mb_substr($signature, 0, 250, 'utf-8');
        
        if(!empty($nickname)){
            $param['nickname'] = $nickname;
        }
        if(!empty($birthday)){
            $param['birthday'] = $birthday;
        }
        if(!empty($birthplace)){
            $param['birthplace'] = $birthplace;
        }
        if(!empty($signature)){
            $param['signature'] = $signature;
        }

        if(isset($_REQUEST['signature']) && trim($_REQUEST['signature']) == '' ){
            $param['signature'] = '';
        }
        if(isset($_REQUEST['birthplace']) && trim($_REQUEST['birthplace']) == '' ){
            $param['birthplace'] = '';
        }
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //更新用户数据
            $ret = User::model()->changeUserInfo($user_id, $param);
            if($ret === false){
                //更新失败
                $this->_return('MSG_ERR_UNKOWN');
            }
            //写入日志 更新用户信息 
            Log::model()->_user_log($user_id, 'CHANGE_USERINFO', date('Y-m-d H:i:s'), '');
			
            //获取用户信息
            $data = Consumer::model()->getUserInfo($user_id);
            
            if($data){
                $this->_return('MSG_SUCCESS', $data);
            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }

        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

    }

    /**
     * 修改用户头像  actionChangeHeadImg
     *
     * @param string $user_id
     * @param string $token
     * @param string $face
     * @param string $type   //图片类型  jpg png
     *
     */
    public function actionChangeHeadImg()
    {
        set_time_limit(0);
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['face']))
        {
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $face        = trim(Yii::app()->request->getParam('face'));
        $type        = trim(yii::app()->request->getParam('type'));
        if($type == 'png'){
            $type = '.png';
        }else if($type == 'gif'){
            $type = '.gif';
        }else{
            $type = '.jpg';
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
        
        $image = isset($face) ? trim($face) : file_get_contents("php://input");
        $im = false;
        /* try
        {
            $im = imagecreatefromstring(base64_decode($image));//base64编码的一个大字符串。。。
        }
        catch(Exception $e)
        {
            $this->_return('MSG_ERR_UPLOAD_IMG_ERR');
        }
        
        if ($im === false) {
            $this->_return('MSG_ERR_UPLOAD_IMG_ERR');
        }  */
        // 上传大小限制
        if(strlen(base64_decode($image)) > 1024*1024*2){
            $this->_return('MSG_ERR_UPLOAD_HEAD_IMG');
        }
        $url = null;
        $ret_code = $this->actionCurl(Yii::app()->params['img_server_url'],
                            'type='.urlencode($type).'&image='.urlencode($image));
        if(!$ret_code)
        {
            $this->_return('MSG_ERR_UPLOAD_HEAD_IMG');
        }
        $ret_json = json_decode($ret_code);

        if($ret_json->{'result'} != '10000')
        {
            $this->_return('MSG_ERR_UPLOAD_HEAD_IMG');
        }

        $url = $ret_json->{'data'}->{'url'};

        // 更新用户头像记录
        if(User::model()->changeUserInfo($user_id, array('avatar'=>$url))){
            //成功
            //写入日志
            Log::model()->_user_log($user_id, 'CHANGE_AVATAR', date('Y-m-d H:i:s'), '');
		
            // 发送返回值
            $data = array();
            $data['face_url'] = Yii::app()->params['img_url_base'].$url;
            $data['face_url'] = stripslashes($data['face_url']);
            $this->_return('MSG_SUCCESS', $data);
        }else{
            //更新失败
            $this->_return('MSG_ERR_UNKOWN');
        }
        //todo:log ?

    }

    /**
     * 用户修改/设定密码  actionChangePassword
     *
     * @param string $user_id
     * @param string $password
     * @param string $new_password
     * @param token  $token
     *
     */
    public function actionChangePassword()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['password'])
        	|| !isset($_REQUEST['new_password']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now          = date("Y-m-d H:i:s");
        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $password     = trim(Yii::app()->request->getParam('password'));
        $new_password = trim(Yii::app()->request->getParam('new_password'));
        $token        = trim(Yii::app()->request->getParam('token'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

        // 获取用户安全信息
        $safe_info = User::model()->getUserSafeInfo($user_id);

        // 密码是否相同
        if(strcmp($password, $new_password) == 0) {
        	$this->_return('MSG_ERR_NEW_PASSWORD');
        }

        //验证密码的合法性
        if(!$this->isPasswordValid($new_password))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(strcmp($new_password, $safe_info['user_name']) == 0)
        {
            $this->_return('MSG_ERR_SET_SAME_PASSWORD');
        }
        $password = $this->_encrypt($password, $this->getCryptkey());
        // 密码是否正确
        if(strcmp($password, $safe_info['password']) == 0){

            $transaction = Yii::app()->db_user->beginTransaction();
            try
            {
            	$new_password = $this->_encrypt($new_password, $this->getCryptkey());
                User::model()->updateUserPassword($user_id, $new_password);
                // 提交事务
                $transaction->commit();
            }
            catch(Exception $e)
            {
                error_log($e);
                $transaction->rollback();

                //更新失败
                $this->_return('MSG_ERR_UNKOWN');
            }
            //修改密码日志
            Log::model()->_user_log($user_id, 'CHANGE_PASSWORD', date('Y-m-d H:i:s'), '');
            //更新成功
            $this->_return('MSG_SUCCESS');
        }else{
            //用户密码错误
            $this->_return('MSG_ERR_PASSWORD_WRONG');
        }

    }

    /**
     * 登陆验证
     *
     * @param string $user_id
     * @param string $token
     */
    public function actionVerifyToken()
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
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])) {
            $data = Consumer::model()->getUserInfo($user_id);
            if($data){
                //每日玫瑰发放
                Characters::model()->everyRose($user_id);
                $data['token'] = $token;
                //写入日志 更新用户信息
                Log::model()->_user_log($user_id, 'USER_LOGIN', date('Y-m-d H:i:s'), '');
                $this->_return('MSG_SUCCESS', $data);
            }else{
                $this->_return('MSG_ERR_UNKOWN');
            }
        }
        else
        {

            $this->_return('MSG_ERR_TOKEN');
        }
    }


    /**
     * 用户找回密码 actionFindPassword
     *
     * @param  string $username
     * @param  string $email
     * @param  string $new_password
     * @return
     */
    public function actionFindPassword()
    {
        // 参数检查
        if(    !isset($_REQUEST['username'])
            || !isset($_REQUEST['email'])
            || !isset($_REQUEST['new_password'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $username     = trim(Yii::app()->request->getParam('username'));
        $email        = trim(Yii::app()->request->getParam('email'));
        $new_password = trim(Yii::app()->request->getParam('new_password'));
        $now              = date("Y-m-d H:i:s");

        //验证密码的合法性
        if(!$this->isPasswordValid($new_password))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!$this->isEmail($email)){
            $this->_return('MSG_ERR_EMAIL_INPUT');
        }

        //获取用户id
        $userIdX = User::model()->getUserId($username);

        //用户id 不存在 返回错误
        if(empty($userIdX)) $this->_return('MSG_ERR_NO_USER');

        $userIdY = User::model()->getUserId(false, $email);

        //用户id 不存在 返回错误
        if(empty($userIdX)) $this->_return('MSG_ERR_EMAIL_FAIL');

        if(strcmp($userIdX, $userIdY) != 0)
        {
            //邮箱验证错误
            $this->_return('MSG_ERR_EMAIL_FAIL');
        }

        if(strcmp($username, $new_password) == 0)
        {
            //用户名密码不能一致
            $this->_return('MSG_ERR_SET_SAME_PASSWORD');
        }

        $transaction = Yii::app()->db_user->beginTransaction();
        try
        {
            $new_password = $this->_encrypt($new_password, $this->getCryptkey());
            User::model()->updateUserPassword($userIdX, $new_password);
            // 提交事务
            $transaction->commit();
        }
        catch(Exception $e)
        {
            error_log($e);
            $transaction->rollback();

            //更新失败
            $this->_return('MSG_ERR_UNKOWN');
        }

        $data = Consumer::model()->getUserInfo($userIdX);
        //修改密码日志
        Log::model()->_user_log($userIdX, 'CHANGE_PASSWORD', date('Y-m-d H:i:s'), '');
        if($data){
            $this->_return('MSG_SUCCESS', $data);
        }else{
            $this->_return('MSG_ERR_UNKOWN');
        }

    }

    /**
     * 验证用户名 actionVerifyUsername
     *
     * @parma string $username
     *
     */
    public function actionVerifyUsername()
    {
        // 参数检查
        if(!isset($_REQUEST['username'])) $this->_return('MSG_ERR_LESS_PARAM');

        $username = trim(Yii::app()->request->getParam('username'));

        //获取用户id
        $user_id = User::model()->getUserId($username);
        if($user_id > 0)
            $this->_return('MSG_ERR_USERNAME_EXIST');
        else
            //用户不存在
            $this->_return('MSG_SUCCESS');
    }



    /**
     * 查找好友
     *
     * @param string $user_id
     * @param string $token
     * @param string $find_username
     * @param int    $page
     * @param int    $page_size
     *
     */
    public function actionFindFriend()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['find_username'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now              = date("Y-m-d H:i:s");
        $user_id          = trim(Yii::app()->request->getParam('user_id'));
        $token            = trim(Yii::app()->request->getParam('token'));
        $find_username    = trim(Yii::app()->request->getParam('find_username'));
        $page             = trim(Yii::app()->request->getParam('page'));
        $page_size        = (int)trim(Yii::app()->request->getParam('page_size'));

        if(empty($page)) $page = 1;
        if(empty($page_size)) $page_size = 10;

        if($page_size > 20){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $start = ($page-1)*$page_size;
        //$page 大小范围 $page_size 大小范围

        if(empty($find_username))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $find_username   = mb_substr($find_username, 0, 20, 'utf-8');

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //LOG
            $this->_return('MSG_ERR_TOKEN');
        }
        $info = array();
        //获取用户列表
        $info[] = User::model()->findFriend($user_id, $find_username);
        ############################### 模糊
        if(empty($info[0])){
            $info = array();
            $data = array();
            //查看是否有此人
            $ids = User::model()->fuzzy($find_username, $start, $page_size);
//             echo '<pre>';
//             print_r($ids);exit;

            foreach ($ids as $id) {
                $uinfo = Consumer::model()->getUserAll($id);
                if(!empty($uinfo)){
                    //查看是否已经建立了好友关系
                    $is_exist = UserFriend::model()->isFriend($user_id, $id);
                    if($is_exist)
                        $data['status'] = 1;
                    else
                        $data['status'] = 0;
                    $data['friend_id']= $id;
                    $data['face_url'] = $uinfo['avatar'];
                    $data['nickname'] = $uinfo['nickname'];
                    $data['username'] = $uinfo['username'];
                    $data['exp']      = $uinfo['point'];
                    $data['level']    = $uinfo['level'];
                    $info[] = $data;
                }
            }
        }
        ###############################
        $this->_return('MSG_SUCCESS', $info);
    }

    /**
     * 添加好友
     *
     * @param string $user_id
     * @param string $token
     * @param string $friend_id
     *
     */
    public function actionAddFriend()
    {
        // 参数检查
        if(    !isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])
            || !isset($_REQUEST['friend_id']) ){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now           = date("Y-m-d H:i:s");
        $user_id       = trim(Yii::app()->request->getParam('user_id'));
        $token         = trim(Yii::app()->request->getParam('token'));
        $friend_id     = trim(Yii::app()->request->getParam('friend_id'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN');
        }
        //不能添加自己为好友
        if(strcmp($user_id, $friend_id) == 0)
        {
            $this->_return('MSG_ERR_ADD_SELF_FRIEND');
        }

        //好友用户不存在 返回错误
        if(!User::model()->getUserInfo($friend_id)){
            $this->_return('MSG_ERR_NO_USER');
        }

        //是否已经为好友
        //$is_exist = UserFriend::model()->isFriend($user_id, $friend_id);
        //查询用户好友数量 
        
        $friend_num = UserFriend::model()->getUerFriendNum($user_id);
        if($friend_num >=  Yii::app()->params['friend_upper_limit']){
            $this->_return('MSG_ERR_ADD_FRIEND_NUM'); //#
        }
        
        //获取好友关系
        $relation = UserFriend::model()->getFriendRelation($user_id, $friend_id);
        $now = date("Y-m-d H:i:s");

        switch ($relation){
        	case 0: //没有关系,插入请求
        		$transaction = Yii::app()->db_friend->beginTransaction();
        		try{
        		
        			UserFriend::model()->insertFriend($user_id, $friend_id, $now);
        			// 提交事务
        			$transaction->commit();
        			//log 添加好友日志
        			$memo = $user_id.'|'.$friend_id;
        			Log::model()->_user_log($user_id, 'ADD_FRIEND', date("Y-m-d H:i:s"), $memo);
        		}
        		catch(Exception $e)
        		{
        			error_log($e);
        			$transaction->rollback();
        			$this->_return('MSG_ERR_UNKOWN');
        		}
        		$this->_return('MSG_SUCCESS');
        		break;
        	case 1: //已经请求过了,就不用再请求了吧,浪费时间
        		$this->_return('MSG_SUCCESS');
        		break;
        	case 2: //对方已经请求过你了, 直接设置为好友
        		$transaction = Yii::app()->db_friend->beginTransaction();
        		try{
        			$param1 = array(
        					'status' => '1',
        					'mess_read'   => '1',
        					'update_ts' => $now
        			);
        			$param2 = array(
        					'status' => '1',
        					'mess_read'   => '1',
        					'update_ts' => $now
        			);
        			
        			UserFriend::model()->updateFriend2($user_id, $friend_id, $param1, $param2);
        		
        			// 提交事务
        			$transaction->commit();
        		}
        		catch(Exception $e)
        		{
        			error_log($e);
        			$transaction->rollback();
        			//TODO::LOG
        			$this->_return('MSG_ERR_UNKOWN');
        		}
        		
        		$this->_return('MSG_SUCCESS');
        		break;
        	case 3: //已经是好友了
        		$this->_return('MSG_ERR_ALEADY_FRIEND');
        		break;
        	case 4: //取消或拒绝,可以再次请求
        		$transaction = Yii::app()->db_friend->beginTransaction();
	            try{
	
	                $param1 = array(
	                        'status' => '0',
	                        'mess_read'   => '1',
	                        'update_ts' => $now
	                    );
	                $param2 = array(
	                        'status' => '0',
	                        'mess_read'   => '0',
	                        'update_ts' => $now
	                    );
	                UserFriend::model()->updateFriend2($user_id, $friend_id, $param1, $param2);
	                // 提交事务
	                $transaction->commit();
	            }
	            catch(Exception $e)
	            {
	                error_log($e);
	                $transaction->rollback();
	                //TODO::LOG
	                $this->_return('MSG_ERR_UNKOWN');
	            }
	
	            $this->_return('MSG_SUCCESS');
        		break;
			default:
				$this->_return('MSG_ERR_UNKOWN');
				break;
        }
    }

    /**
     * 好友列表
     *
     * @param string $user_id
     * @param string $token
     */
    public function actionFriendList()
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
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){

            //获取用户列表
            $info = User::model()->friendList($user_id);
            $this->_return('MSG_SUCCESS', $info);
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }

        $this->_return('MSG_ERR_UNKOWN');
    }

	
	    
	
	/*******************************************************
	 * 系统修改用户名与绑定邮箱  actionChangeUserName
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	* @param $email			// 找回密码使用 唯一邮箱
	* @param $username			// 新用户名
	*
	* @return $error			// 成功 or 失败 
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：单机模式下，无尽游戏结束后通知服务器进行数据同步
	*******************************************************/
	public function actionChangeUserName(){
	    	// 参数检查
	    	if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) 
	    	  || !isset($_REQUEST['email']) || !isset($_REQUEST['username'])){
	    	    $this->_return('MSG_ERR_LESS_PARAM');
	    	}
	    	//版本大于1.0 时增加 密码 参数
	    	if($GLOBALS['__VERSION'] != '1.0'){
	    	    if(!isset($_REQUEST['new_password'])){
	    	        $this->_return('MSG_ERR_LESS_PARAM');
	    	    }
	    	}
	    	
	    	$user_id = trim(Yii::app()->request->getParam('user_id'));
	    	$token = trim(Yii::app()->request->getParam('token'));
	    	$email = trim(Yii::app()->request->getParam('email'));
	    	$username = trim(Yii::app()->request->getParam('username'));
	    	$password = trim(Yii::app()->request->getParam('new_password'));
	    	
	    	//版本大于1.0 时验证 密码 参数是否正确
	    	if($GLOBALS['__VERSION'] > '1.0'){
	    	    //验证参数是否正确
	    	    if(!$this->isPasswordValid($password))
	    	    {
	    	        $this->_return('MSG_ERR_PASSWORD_INPIT');
	    	    }
	    	}
	    	
	    	//用户名不合法
	    	if(!$this->isUsernameValid($username))
	    	{
	    	    $this->_return('MSG_ERR_USERNAME_LENGTH');
	    	}
	    	//用户名包含非法字符
	    	if($this->isExistShieldWord($username)){
	    		$this->_return('MSG_ERR_USERNAME_WORD');
	    	}
	    	//邮箱不和法
	    	if(!$this->isEmail($email)){
	    		$this->_return('MSG_ERR_EMAIL_INPUT');
	    	}
	    	
	    	if(!is_numeric($user_id)){
	    	    $this->_return('MSG_ERR_FAIL_PARAM');
	    	}
	    	
	    	//email 不能大于50
	    	if(strlen($email) > 50){
	    	    $this->_return('MSG_ERR_EMAIL_INPUT');
	    	}
	    	
	    	//用户不存在 返回错误
	    	if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

	    	//验证token
	    	if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
                if($password != ''){
                    $new_password = $this->_encrypt($password, $this->getCryptkey());
                }else{
                    $new_password = NULL;
                }
                //修改系统注册用户信息
                $res = User::model()->SysChangeUserName($user_id,$email,$username,$new_password);
                switch($res)
                {
                	case -1 : $this->_return('MSG_ERR_UNKOWN');
                	case -2 : $this->_return('MSG_ERR_USERNAME_EXIST');
                	case -3 : $this->_return('MSG_ERR_EMAIL_EXIST');
                	case -4 : $this->_return('MSG_ERR_USERNAME_NO');
                	default : break;
                }
                // 注册成功 写入日志
                $memo = $username.'|'.$email;
                Log::model()->_user_log($user_id, 'CHANGE_USNAME_AND_EMAIL', date("Y-m-d H:i:s"), $memo);
                $res['token'] = $token;
	    	}else{
	    	    $this->_return('MSG_ERR_TOKEN');
	    	}
	    	// 发送返回值
	    	$this->_return('MSG_SUCCESS',$res);
	}
	
	/*******************************************************
	 * 获取每日登陆奖励 actionEveyLoginReward
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：获得七天登陆奖励列表
	*******************************************************/
	public function actionEveyLoginReward(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	    
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	    
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	    
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	    
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //获取七天登陆奖励
	        $res = Common::model()->login_reward($user_id);
	    }else{
	        $this->_return('MSG_ERR_TOKEN');
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS',$res);
	}
	
	/*******************************************************
	 * 每日登陆奖励领取 actionLoginRewardResult
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	* @param $token			// bag_id 礼包ID
	* 
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：获得七天登陆奖励
	*******************************************************/
	public function actionLoginRewardResult(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['bag_id'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	     
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	    $bag_id = trim(Yii::app()->request->getParam('bag_id'));
	     
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	     
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	     
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //获取七天登陆奖励
	        $res = User::model()->login_reward_result($user_id,$bag_id);
	        switch($res)
	        {
	        	case -1 : $this->_return('MSG_ERR_UNKOWN');
	        	case -2 : $this->_return('MSG_ERR_NO_GET_LOGIN_REWARD');
	        	case -3 : $this->_return('MSG_ERR_LOGIN_REWARD_OK');
	        	
	        	default : break;
	        }
	        //每日奖励领取
	        Log::model()->_gold_log($user_id, $res['log']['gold'], $res['log']['gold_after'], 'GOLD_DAY_REWARD', date('Y-m-d H:i:s'), '');
	    }else{
	        $this->_return('MSG_ERR_TOKEN');
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS');
	}
	
	/*******************************************************
	 * 体力 10  获取翻牌赚金币 三张卡牌中翻一张 actionEarnGoldList  100 300 500
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：获得三张卡牌结果 扣体力
	*******************************************************/
	public function actionEarnGoldList(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	     
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	     
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	     
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	     
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //获取三张
	        $res = User::model()->earn_gold_list($user_id);
	        switch($res)
	        {
	        	case -1 : $this->_return('MSG_ERR_CHAKRA_DEFICIENCY');
	        	default : break;
	        }
	        
	        //翻牌 扣体力
	        Log::model()->_vit_log($user_id, $res['log']['vit'],
	           $res['log']['vit_after'], 'GOLD_LESSEN_VIT', date('Y-m-d H:i:s'), '');
	        
	        //游戏日志
	        Log::model()->_game_log($user_id, 0,  $res['log']['id'],
	           $res['log']['gold'], 301, 'LESSEN_VIT_PLUS_GOLD', date('Y-m-d H:i:s'), '');
	        
	    }else{
	        $this->_return('MSG_ERR_TOKEN');
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS',$res['result']);
	}
	
	/*******************************************************
	 * 翻牌赚金币提交 获得金币 actionEarnGoldResult
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	* @param $set_id			// set_id 盘数ID
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：三张卡牌结果提交 加金币
	*******************************************************/
	public function actionEarnGoldResult(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['set_id'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	    
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	    $set_id = trim(Yii::app()->request->getParam('set_id'));
	    
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	    
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //领取奖励
	        $res = User::model()->earn_gold_result($user_id,$set_id);
	        switch($res)
	        {
	        	case -1 : $this->_return('MSG_ERR_NO_SEARCH_EARN_GOLD');
	        	case -2 : $this->_return('MSG_ERR_UNKOWN');
	        	case -3 : $this->_return('MSG_ERR_EARN_GAME_REWARD');
	        	default : break;
	        }
	        //奖励领取
	        Log::model()->_gold_log($user_id, $res['log']['gold'], $res['log']['gold_after'], 
	                               'GOLD_EARN_CRSR_REWARD', date('Y-m-d H:i:s'), $res['set_id']);
	        //游戏日志
	        Log::model()->_game_log($user_id, 0,   $res['set_id'],
	           $res['log']['gold'], 302, 'LESSEN_VIT_PLUS_GOLD_REWARD', date('Y-m-d H:i:s'), '');
	    }else{
	        $this->_return('MSG_ERR_TOKEN');
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS',$res['result']);
	}
	
	/*******************************************************
	 * 金币买体力 acitonGoldVit
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	* @param $set_id			// set_id 盘数ID
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：金币 购买体力
	*******************************************************/
	public function actionGoldVit(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	     
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	     
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	     
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //金币买体力
	        $res = User::model()->gold_buy_vit($user_id);
	        switch($res)
	        {
	          //系统繁忙，请稍后再试
	        	case -1 : $this->_return('MSG_ERR_UNKOWN');
	        	//金币不足
	        	case -2 : $this->_return('MSG_ERR_NO_GOLD');
	        	//体力已满，不需要购买。
	        	case -3 : $this->_return('MSG_ERR_VIT_FULL');
	        	default : break;
	        }
	        $memo = '';
	        //金币购买体力
	        Log::model()->_gold_log($user_id, $res['log']['gold'], $res['log']['gold_after'], 'GOLD_ADD_VIT', date('Y-m-d H:i:s'), $memo);
	    }else{
	        $this->_return('MSG_ERR_TOKEN');
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS',$res['result']);
	}
    
	/*******************************************************
	 * 软件推荐兑换金币 actionSoftwareToGold
	*
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：软件列表
	*******************************************************/
	public function actionSoftwareToGold(){
	    // 参数检查
	    if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['software_id'])){
	        $this->_return('MSG_ERR_LESS_PARAM');
	    }
	
	    $user_id = trim(Yii::app()->request->getParam('user_id'));
	    $token = trim(Yii::app()->request->getParam('token'));
	    $software_id = trim(Yii::app()->request->getParam('software_id'));
	    if(!is_numeric($user_id)){
	        $this->_return('MSG_ERR_FAIL_PARAM');
	    }
	    //用户不存在 返回错误
	    if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
	    
	    //验证token
	    if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
	        //
	        $res = User::model()->software_to_gold($user_id,$software_id);
	        switch($res)
	        {
	            //系统繁忙，请稍后再试
	        	case -1 : $this->_return('MSG_ERR_UNKOWN');
	        	//软件金币已经领取，不能重复领取
	        	case -2 : $this->_return('MSG_ERR_SOFTWARE_TO_GOLD');
	        	case -3 : $this->_return('MSG_ERR_FAIL_SEARCH');
	        	default : break;
	        }
	        $memo = '';
	        //软件兑换金币
	        Log::model()->_gold_log($user_id, $res['log']['gold'], $res['log']['gold_after'], 'GOLD_SOFTWARE_TO_GOLD', date('Y-m-d H:i:s'), $memo);
	    }
	   // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
	}
	
	/*******************************************************
	 * 软件推荐开关 actionSoftwareOnOff
	*
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：软件推荐开关
	*******************************************************/
	public function actionSoftwareOnOff(){
	    $res = Common::model()->getSoftwareOnoff();
	    switch($res)
	    {
	        //系统繁忙，请稍后再试
	    	case -1 : $this->_return('MSG_ERR_SOFTWARE_OFF');
	    	default : break;
	    }
	    // 发送返回值
	    $this->_return('MSG_SUCCESS');
	}
}