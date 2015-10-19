<?php
class CommonController extends ApiPublicController
{

    /**	
     * 获取礼物信息
     * @param int    $type          // 礼物类型
     */
    public function actionGetGiftInfo()
    {
        // 参数检查
        if(!isset($_REQUEST['type'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $type        = trim(Yii::app()->request->getParam('type'));
        $now         = date("Y-m-d H:i:s");

        if(!is_numeric($type)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $data = Gift::model()->schedule($type);
        $result = array();
        if(is_array($data) && !empty($data)){
            foreach ($data as $k => $v) {
                $temp = array();
                $temp['id']     = (int) $v['gift_id'];
                $temp['url']    = Yii::app()->params['img_url_base'].$v['url'];
                $temp['name']   = $v['name'];
                $temp['type']   = (int) $v['type'];
                $temp['liking'] = (int) $v['add_liking'];
                $temp['exp']    = (int) $v['add_exp'];
                $temp['point']  = (int) $v['add_point'];
                $temp['vit']    = (int) $v['minus_vit'];
                $temp['level']  = (int) $v['lv'];
                $temp['gold']  = (int) $v['gold'];
                $result[] = $temp;
            }
        }

        $this->_return('MSG_SUCCESS', $result);

    }

    /**
     * 防打扰时间段
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $status          //1:打开，2：关闭
     * @param string $start_time
     * @param string $end_time
     */
    public function actionDoNotDisturb()
    {

        // 参数检查
        if(!isset($_REQUEST['user_id'])
            || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id      = trim(Yii::app()->request->getParam('user_id'));
        $token        = trim(Yii::app()->request->getParam('token'));
        $start_time   = trim(Yii::app()->request->getParam('start_time'));
        $end_time     = trim(Yii::app()->request->getParam('end_time'));
        $status         = trim(Yii::app()->request->getParam('status'));
        $now          = date("Y-m-d H:i:s");

        //用户不存在 返回错误
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

        $param = array();
        if(is_numeric($start_time) && $start_time < 24){
            $param['push_start'] = $start_time;
        }

        if(is_numeric($end_time) && $end_time < 24){
            $param['push_end'] = $end_time;
        }


        if(isset($param['push_start']) && isset($param['push_end']) && $param['push_start'] == $param['push_end'])
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if($status === 0 || $status === 1 || $status === '0' || $status === '1'){
            $param['push_enabled'] = $status;
        }

        // 如果由更新参数 那么更新
        if(!empty($param)){
            try{
                Characters::model()->updateCharacters($user_id, $param);
                // 更新角色信息表  日志 防打扰
                Log::model()->_goddess_log($user_id, '','DO_NOT_DISTURB', date("Y-m-d H:i:s"), '');
            }catch(Exception $e){
                error_log($e);
                $this->_return('MSG_ERR_UNKOWN');
            }
        }

        //获取用户app设置
        $player = Characters::model()->getAppSetting($user_id);
        if($player === false)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }

        $result = array();
        $result['status']     = (int)$player['push_enabled'];
        $result['start_time'] = (int)$player['push_start'];
        $result['end_time']   = (int)$player['push_end'];

        $this->_return('MSG_SUCCESS', $result);
    }

    /**
     * 意见反馈
     *
     * @param string $content  //意见内容
     * @param string $contact  //联系方式,可选
     */
    public function actionFeedback()
    {
        // 参数检查
        if(isset($_REQUEST['user_id']) && isset($_REQUEST['token'])){

            $user_id    = trim(Yii::app()->request->getParam('user_id'));
            $token      = trim(Yii::app()->request->getParam('token'));

            if(!is_numeric($user_id)){
                $this->_return('MSG_ERR_FAIL_PARAM');
            }
            //用户不存在 返回错误
            if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
            if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
                $this->_return('MSG_ERR_TOKEN');
            }
        }

        // 参数检查
        if(!isset($_REQUEST['content']) || empty($_REQUEST['content'])){ // || !isset($_REQUEST['contact'])
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $content  = trim(Yii::app()->request->getParam('content'));
        $contact  = isset($_REQUEST['contact']) ? trim(Yii::app()->request->getParam('contact')) : 0;

        if(mb_strlen($content, 'utf-8') < 10){
            $this->_return('MSG_ERR_CONTENT_ERR');

        }
        //截掉
        $content  = mb_substr($content, 0, 500, 'utf-8');
        $contact  = mb_substr($contact, 0, 50, 'utf-8');

        $time = date('Y-m-d H:i:s');
        if(isset($user_id) && is_numeric($user_id)){
            $pid = $user_id;
        }else{
            $pid = 0;
        }
        if(Common::model()->feedback($content, $contact, $time, $pid)){

            $this->_return('MSG_SUCCESS', '');
        }else{
            $this->_return('MSG_ERR_UNKOWN');
        }

    }

    /**
     * 用户等级信息
     *
     */
    public function actionLevelInfo()
    {
        $info = Level::model()->wholeLevelAround();

        if(!is_array($info))
        {
            $this->_return('MSG_ERR_UNKOWN');
        }
        
        $this->_return('MSG_SUCCESS', $info);
    }

    /**
     * 好感等级信息
     *
     */
    public function actionLevelLiking()
    {
        $info = Liking::model()->wholeLikingAround();

        if(!is_array($info))
        {
            $this->_return('MSG_ERR_UNKOWN');
        }
        
        $this->_return('MSG_SUCCESS', $info);
    }

    /**
     * 版本升级
     *
     * @param string $GLOBALS['__PLATFORM'] //系统平台
     *
     */
    public function actionUpgrade()
    {
        $platform = strtoupper(trim($GLOBALS['__PLATFORM']));
        if($platform == 'ANDROID'){
            $platform = 0;
        }elseif($platform == 'IOS'){
            $platform = 1;
        }elseif($platform == 'WINDOWSPHONE'){
            $platform = 2;
        }else{
            return $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $result = Common::model()->latest($platform);
        if($result === false)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }

        $data = array();
        if(isset($result['version'])){
//             if(strcmp($_REQUEST['app_version'], $result['version']) != 0){
                $data = $result;
//             }
        }

        if(empty($data)){
            $this->_return('MSG_ERR_FAIL_UPGRADE');
        }else{
            $this->_return('MSG_SUCCESS', $data);
        }
    }

    /**
     * 获取所有标签
     *
     */
    public function actionTag()
    {
        $tag = Common::model()->getLabel();

        if($tag === false){
            $this->_return('MSG_ERR_UNKOWN');
        }

        if(is_array($tag)){
            foreach ($tag as $key => $value) {
                $tag[$key]['id'] = (int) $value['id'];
            }
        }
        $this->_return('MSG_SUCCESS', $tag);
    }

    /**
     * 统计
     *
     * @param string $action
     * @param string $view
     * @param string $timestamp   //2014-03-10 12:12:12
     * @param int    $user_id*
     * @param string $target_id*
     * @param string $result*
     */
    public function actionStatistics()
    {

        // 参数检查
        if(    empty($_REQUEST['action'])
            || empty($_REQUEST['view'])
            || empty($_REQUEST['timestamp'])
        ){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $action     = trim(Yii::app()->request->getParam('action'));
        $view       = trim(Yii::app()->request->getParam('view'));
        $timestamp  = trim(Yii::app()->request->getParam('timestamp'));
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $target_id  = trim(Yii::app()->request->getParam('target_id'));
        $result     = trim(Yii::app()->request->getParam('result'));

        $time = strtotime($timestamp);
        //传入时间不能转换城时间戳返回参数错误
        if($time === false){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            $user_id = 0;
        }

        $year  = date('Y', $time);
        $month = date('m', $time);
        $day   = date('d', $time);

        $param = array(
                        'action'        =>  $action,
                        'view'          =>  $view,
                        'timestamp'     =>  $timestamp,
                        'user_id'       =>  $user_id,
                        'target_id'     =>  $target_id,
                        'result'        =>  $result,
                        'year'          =>  $year,
                        'month'         =>  $month,
                        'day'           =>  $day,
                        'version'       =>  $GLOBALS['__VERSION'],
                        'device_id'     =>  $GLOBALS['__DEVICEID'],
                        'platform'      =>  $GLOBALS['__PLATFORM'],
                        'channel'       =>  $GLOBALS['__CHANNEL'],
                        'app_version'   =>  $GLOBALS['__APPVERSION'],
                        'os_version'    =>  $GLOBALS['__OSVERSION'],
                        'app_id'        =>  $GLOBALS['__APPID'],
                        'ip'            =>  $GLOBALS['__IP']);

        try{
            Statistics::model()->push($time, $param);
        }catch(Exception $e){
            error_log($e);
            $this->_return('MSG_ERR_UNKOWN');
        }

        return $this->_return('MSG_SUCCESS');
    }
    
    /*******************************************************
     * 充值礼包 actionPayPack
    *
    * @param $user_id               // 用户id
    * @param $token			      // 用户token
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：充值礼包
    *******************************************************/
    public function actionPayPack(){
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
            //充值礼包
            $res = Common::model()->getPayPack();
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res);
    }
    
    /*******************************************************
     * 软件推荐 actionSoftware
    *
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：软件列表
    *******************************************************/
    public function actionSoftware(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $token      = trim(Yii::app()->request->getParam('token'));
        //
        $res = Common::model()->getSoftwareList($user_id);
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res);
    }
    
    /*******************************************************
     * 推送开关 actionOnOff
    *
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：推送开关
    *******************************************************/
    public function actionOnOff(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['open'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
         
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $token      = trim(Yii::app()->request->getParam('token'));
        $status     = trim(Yii::app()->request->getParam('open'));
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
         
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
         
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            $param['push_enabled'] = $status;
            // 如果由更新参数 那么更新
            if(!empty($param)){
                try{
                    Characters::model()->updateCharacters($user_id, $param);
                    // 更新角色信息表  日志 防打扰
                    Log::model()->_goddess_log($user_id, '','DO_NOT_DISTURB', date("Y-m-d H:i:s"), '');
                    // 发送返回值
                    $this->_return('MSG_SUCCESS');
                }catch(Exception $e){
                    error_log($e);
                    $this->_return('MSG_ERR_UNKOWN');
                }
            }
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
    }
    
    
}