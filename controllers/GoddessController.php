<?php
class GoddessController extends ApiPublicController
{


    /**
     * 获取女神信息
     *
     * @param string user_id
     * @param string token
     * @param int    goddess_id     女神id
     */
    public function actionGetGoddessInfo()
    {
        // 参数检查
        if(!isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now          = date("Y-m-d H:i:s");
        $goddess_id   = trim(Yii::app()->request->getParam('goddess_id'));

        //判断女神id 和 礼品id 数据类型
        if(!is_numeric($goddess_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            //不存在此女神
            $this->_return("MSG_ERR_NO_EXIST_GODDESS");
        }

        //女神的详细信息
        $data = Goddess::model()->getGoddessInfo($goddess_id);
//         if($data == false){
//             $this->_return('MSG_ERR_UNKOWN');
//         }

        if(    isset($_REQUEST['token'])
            && isset($_REQUEST['user_id'])
            && !empty($_REQUEST['user_id'])
        ){
            $user_id      = trim(Yii::app()->request->getParam('user_id'));
            $token        = trim(Yii::app()->request->getParam('token'));

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

            // 是否关注女神
            $follow = Follow::model()->getFollowRow($user_id, $goddess_id);
        }

        $result = array();
        if($data){
            $result['goddess_id']      = (int)$data['heroine_id'];
            $result['nickname']        = $data['nickname'];
            $result['sex']             = (int)$data['sex'];
            $result['birthplace']      = $data['birthplace'];
            $result['birthday']        = $data['birthday'];
            $result['signature']       = $data['signature'];
            $result['face_url']        = $data['faceurl'];
            $result['cover']           = $data['cover'];
            $result['height']          = (int)$data['height'];
            $result['weight']          = (int)$data['weight'];
            $result['hobby']           = $data['hobby'];
            $result['lable']           = $data['lable'];
            $result['glamorous']       = (int)$data['glamorous'];
            $result['follower_count']  = (int)$data['follower_count'];
            $result['picture_count']   = (int)$data['picture_count'];
            $result['vitalstatistics'] = $data['vitalstatistics'];
            $result['job']             = $data['job'];
            $result['name']            = $data['name'];
            $result['blood_type']      = (int)$data['blood_type'];
            $result['animal_sign']     = (int)$data['animal_sign'];
            $result['character']       = $data['character'];
            $result['constellations']  = (int)$data['constellations'];
            $result['caption']          = $data['caption'];
            if(isset($follow) && is_array($follow)){
                $result['followed']  = (int)$follow['followed'];
                $result['liking']    = (int)$follow['liking'];
            }else{
                $result['followed']  = 0;
                $result['liking']    = 0;
            }
        }
        
        //更新成功
        $this->_return('MSG_SUCCESS', $result);

    }

    /**
     * 关注女神
     *
     * @param string $user_id
     * @param string $token
     * @param int    $goddess_id
     * @param int    $follow            1：关注  0：取消关注  2:用户取消关注
     *
     *
     */
    public function actionFollowGoddess()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token'])
        	|| !isset($_REQUEST['goddess_id']) || !isset($_REQUEST['follow'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));
        $follow      = trim(Yii::app()->request->getParam('follow'));

        if( !is_numeric($goddess_id) || !is_numeric($follow)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if($follow != 0 && $follow != 1 && $follow != 2){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

        //查询是否有此女神
        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            $this->_return('MSG_ERR_NO_EXIST_GODDESS');
        }
        
        //如果已经相同 则成功
        $rw = Follow::model()->getFollowRow($user_id, $goddess_id);
        if(isset($rw['followed']) && $follow == $rw['followed']){
            $this->_return('MSG_SUCCESS');
        }

        //用户关注赞+1，女神照片赞+1，女神赞总数+1
        $characters_transaction = Yii::app()->db_characters->beginTransaction();
        $heroine_transaction = Yii::app()->db_heroine->beginTransaction();
        try{
            //关注女神
            Characters::model()->followGoddess($user_id, $goddess_id, $follow);
            $followerCount = Goddess::model()->getFollowerCount($goddess_id);
            
            if($follow == 1){
                $followerCount = $followerCount + 1;
                $arr = array('follower_count' => $followerCount);
                if($rw['followed'] != 2){
					
                    //关注魅力值+10
                    $glamorousCount = Goddess::model()->getGlamorousCount($goddess_id);
                    $glamorousCount = $glamorousCount + Yii::app()->params['follow_glamorous'];
                    $arr = array('follower_count' => $followerCount,'glamorous' => $glamorousCount);
                    //关注加好感值
                    $liking = $rw['liking'] + (int)Yii::app()->params['follow_liking'];
                    Follow::model()->updateFollowRow($user_id, $goddess_id, array('liking' => $liking));
                    //关注加经验
                    //获取用户基本信息
                    $player = Characters::model()->getCharactersInfo($user_id);
                    //获取等级信息
                    $liking = Liking::model()->getLikingRow($player['level']);
                    //加经验值
                    $params = array(
                            'exp'=>$player['exp']+(int)Yii::app()->params['follow_exp']
                    );
                    //加经验更新等级
                    $lv = Level::model()->exp2Level($params['exp']);
                    if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                        $param['level'] = $lv;
                    }
                    Characters::model()->updateCharacters($user_id,$params);
                }
            }
            if($follow == 0){
                $followerCount = max(($followerCount - 1), 0);
                $arr = array('follower_count' => $followerCount);
            }
            //增加关注度 
            Goddess::model()->updateHeroineInfo($goddess_id, $arr);
            $characters_transaction->commit();
            $heroine_transaction->commit();
            //关注女神
            Log::model()->_goddess_log($user_id, $goddess_id, 'DS_FOLLOW', date('Y-m-d H:i:s'), '');
            
        }catch(Exception $e){
            error_log($e);
            $characters_transaction->rollback();
            $heroine_transaction->rollback();
            $this->_return('MSG_ERR_UNKOWN');
        }
        $this->_return('MSG_SUCCESS');
    }

    /**
     * 赞女神照片
     * 
     * 
     * @param string $user_id
     * @param string $token
     * @param int    $goddess_id
     * @param int    $image_id
     * @param int    $rating       //预留
     * @param int    $status       //1-赞 0-取消赞
     */
    public function actionPraised()
    {
        
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || 
            !isset($_REQUEST['image_id']) || !isset($_REQUEST['status'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));
        $image_id    = trim(Yii::app()->request->getParam('image_id'));
        $status      = trim(Yii::app()->request->getParam('status'));

        //参数判断
        if(!is_numeric($goddess_id) || !is_numeric($image_id) || !is_numeric($status))
        {
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if($status != 0 && $status != 1){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //角色是否有关注此女神
            if (!Follow::model()->isExitsFollow($user_id, $goddess_id)) {
                $this->_return('MSG_ERR_NO_FOLLOW');
            }
            //是否有此照片
            $photo_info = Photo::model()->photoInfo($image_id);
            if (empty($photo_info) && !is_array($photo_info)) {
                $this->_return('MSG_ERR_NO_FOUND_IMG');
            }

            //判断照片是不是这个女神的
            if(strcmp($photo_info['heroine_id'], $goddess_id) != 0)
            {
                $this->_return('MSG_ERR_FAIL_PARAM');
            }

            $follow_info = Photo::model()->selectPhoto($user_id, $goddess_id, $image_id);

            if(empty($follow_info) || !is_array($follow_info)){
                //照片没有解锁
                $this->_return('MSG_ERR_NO_UNLOCK_IMG');
            }else{
                //已经赞过 或者 没有赞过
                if($follow_info['status'] == $status)
                {
                    $this->_return('MSG_ERR_FAIL_PRAISED');
                }

                
                //用户关注赞+1，女神照片赞+1，女神赞总数+1
                $characters_transaction = Yii::app()->db_characters->beginTransaction();
                $heroine_transaction = Yii::app()->db_heroine->beginTransaction();
                try{
                    //用户关注照片
                    Photo::model()->updatePhoto($user_id, $goddess_id, $image_id, $status);
                    $photo_info = Photo::model()->photoInfo($image_id);
                    
                    
                    if($status == 0){
                        $val = -1;
                        //更新  赞女神 总数
                        Goddess::model()->addPraisedCounts($goddess_id, $val);
                    }else{
                        $info = Goddess::model()->getGoddessInfo($goddess_id);
                        $val = $info['praised_counts']+1;
                        //赞照片， 更新魅力值 魅力值+1
                        $glamorousCount = $info['glamorous'] + 1;
                        $arr = array('praised_counts'=>$val, 'glamorous' => $glamorousCount);
                        //增加关注度
                        Goddess::model()->updateHeroineInfo($goddess_id, $arr);
                        $count = $photo_info['praised_count'] + 1;
                        //更新 赞照片 总数
                        Photo::model()->updateHeroinePhoto($image_id, array('praised_counts' => $count));
                        //赞女神照片
                        Log::model()->_photo_log($user_id, $goddess_id, $image_id, 'GODDESS_PHOTO_PRAISED', $now, '');
                    }
                    $characters_transaction->commit();
                    $heroine_transaction->commit();
                    $this->_return('MSG_SUCCESS', '');
                }
                catch(Exception $e)
                {
                    error_log($e);
                    $characters_transaction->rollback();
                    $heroine_transaction->rollback();
                    //更新失败
                    $this->_return('MSG_ERR_UNKOWN');
                }

            }

        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 我的女神
     * 
     * 接口待开发
     * 
     * @param string $user_id
     * @param string $token
     * @param string $page          页号
     * @param string $page_size
     * @param string $timestamp*
     */
    public function actionMyGoddess()
    {
        // 参数检查
        if( !isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) ){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));

        $timestamp = 0;
        $page = 0;
        $page_size = 0;

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }

        //获取好感等级
        $liking = Liking::model()->wholeLikingAround();

        $result = array();
        if($timestamp > 0)
        {
            $data = Follow::model()->popMyGoddess($user_id, $timestamp, $page_size);
        }else{
            //获取角色关注女神列表
            $data = Follow::model()->myGoddess($user_id, $page, $page_size);
        }

        // SQL UNKNOW FAIL
        if($data === false)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }

        if(is_array($data)){
            foreach ($data as $follow) {
                $key = array();
                $info = Goddess::model()->getGoddessInfo($follow['heroine_id']);
                $key['goddess_id']    = (int)$info['heroine_id'];
                $key['nickname']      = $info['nickname'];
                $key['signature']     = $info['signature'];
                $key['face_url']      = $info['faceurl'];
                $key['cover']         = $info['cover'];
                $key['lable']         = $info['lable'];
                $key['picture_count'] = (int)$info['picture_count'];
                $key['status']        = (int)$info['status'];
                $key['lock_num']      = (int)$follow['unlock_counts'];
                $key['timestamp']     = $follow['create_ts'];
                $key['followed']      = (int)$follow['followed'];
                
                //praised
                $key['liking'] = (int)$follow['liking'];
                //解锁照片数

                //增加好感等级
                foreach ($liking as $liking_level) {
                    if($liking_level['max'] >= $key['liking']){
                        $key['liking_level'] = (int)$liking_level['level'];
                        break;
                    }
                }
                $result[] = $key;
            }
            unset($key);
        }
        $this->_return('MSG_SUCCESS', $result);
    }

    /**
     * 体力解锁照片
     *
     * @param int $user_id
     * @param int $goddess_id
     * @param int $token
     * @param int $image_id
     *
     */
/*    public function actionUnlockPhoto()
    {
         
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || !isset($_REQUEST['image_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));
        $image_id    = trim(Yii::app()->request->getParam('image_id'));
        $now         = date("Y-m-d H:i:s");

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //获取图片的信息
            $imageInfo = Photo::model()->photoInfo($image_id);
            //如果不存在 返回没有这张图片信息
            if(empty($imageInfo)){
                $this->_return('MSG_ERR_NO_FOUND_IMG');
            }

            //是否已经解锁
            $is_exits = Photo::model()->selectPhoto($user_id, $goddess_id, $image_id);
            if(!empty($is_exits))
            {
                $this->_return('MSG_ERR_FAIL_UNLOCKED');
            }

            //获取用户基本信息
            $player = Characters::model()->getCharactersInfo($user_id);

            //消耗体力规则 玩家体力值大于等于 (照片等级*5+10)/5
            $minusVit = ( $imageInfo['level'] * 5 + 10 ) / 5;
            $add_exp  = ( $imageInfo['level'] * 5 + 10 ) / 5;
            //$minusVit = $imageInfo['devit'];
            if(( $player['vit'] ) >= $minusVit){

                //消耗体力 extra_vit 附加体力
                // if($player['extra_vit'] >= $minusVit){
                //     $param['extra_vit'] = $player['extra_vit'] - $minusVit;
                //     $param['vit'] = $player['vit'];
                // }else{
                //     $param['extra_vit'] = 0;
                //     $param['vit'] = $player['vit'] + $player['extra_vit'] - $minusVit;
                // }
                # 附加体力值去掉
                $param['vit'] = $player['vit'] - $minusVit;

                //更新经验 游戏人物等级字段废除 用经验值获取等级
                $param['exp'] = $player['exp']+$add_exp;

                //更新等级
                $lv = Level::model()->exp2Level($param['exp']);
                if(!empty($lv) && strcmp($lv, $player['level']) != 0){
                    $param['level'] = $lv;
                }

                //如果满体力消耗 更新体力时间
                $lInfo = Level::model()->getLevelRow($lv);
                if($player['vit'] == $lInfo['max_vit']){
                    $param['charge_vit_ts'] = $now;
                }

                $photoParams = array('user_id'=>$user_id,
                                'heroine_id'=>$goddess_id,
                                'photo_id'=>$image_id,
                                'unlock_type'=>0,
                                'status'=>0,
                                'timestamp'=>time(),
                                'type'=>0);

                $characters_transaction = Yii::app()->db_characters->beginTransaction();
                $liking = 0;
                try{
                    //更新体力值 等级 体力时间
                    Characters::model()->updatePlayerInfo($user_id, $param);
                    //插入解锁照片
                    Photo::model()->insertPhoto($user_id, $photoParams);
                    $follow = Follow::model()->getFollowRow($user_id, $photoParams['heroine_id']);
                    //好感度
                    if(isset($follow['liking']))
                    {
                        $liking = $follow['liking'];
                    }
                    $characters_transaction->commit();
				//解锁照片加入日志
				$memo  = $image_id;
                    Log::model()->_goddess_log($user_id, $goddess_id, 'DS_UNLOCK_IMG', date('Y-m-d H:i:s'), $memo);
                }
                catch(Exception $e)
                {
                    $characters_transaction->rollback();
                    //更新失败
                    $this->_return('MSG_ERR_UNKOWN');
                }
                
                // 返回的信息
                $result = array();
                //暂时没有记录
                $param['point'] = 0;
                $result['point']      = (int)$param['point'];
                $result['exp']        = (int)$param['exp'];
                $result['vit']        = (int)($param['vit']);
                $result['goddess_id'] = (int)$goddess_id;
                $result['level'] = (int)$lv;
                $result['liking']     = (int)$liking;
                
                
                $this->_return('MSG_SUCCESS',$result);
            }else{
                //体力值不足
                $this->_return('MSG_ERR_CHAKRA_DEFICIENCY');
            }
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        } 
    }
*/
    
    /**
     * 女神分类筛选
     *
     * @param string $type          0-最新，1-最热 默认0
     * @param string $tag_id        关键字，标签
     * @param string $page          页号
     * @param string $page_size     页大小 需传递，服务端可仅支持几种固定pageSize
     *
     */
    public function actionFilter()
    {
        // 参数检查
        if(!isset($_REQUEST['page']) || !isset($_REQUEST['page_size'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now         = date("Y-m-d H:i:s");
        $type        = trim(Yii::app()->request->getParam('type'));
        $page        = trim(Yii::app()->request->getParam('page'));
        $page_size   = trim(Yii::app()->request->getParam('page_size'));
        $tag_id    = trim(Yii::app()->request->getParam('tag_id'));

        if(!is_numeric($tag_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        $tag = Common::model()->getLabel();
        $lable = array();
        foreach ($tag as $key => $v) {
            $lable[] = $v['id'];
        }

        if(!in_array($tag_id, $lable)){
            //没有这个标签
            $this->_return('MSG_ERR_NO_FOUND_TAG');
        }

        $type = empty($type) ? 0 : $type;

        if($type != 1 && $type != 0) {
        	$this->_return('MSG_ERR_FAIL_PARAM');
        }

        $data = Goddess::model()->filterGoddessList($tag_id, $type, $page, $page_size);

        if($data === false){
            $this->_return('MSG_ERR_UNKOWN');
        }

        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));

        $followed = array();
        if(isset($user_id) && !empty($user_id)){

            if(!is_numeric($user_id)){
                $this->_return('MSG_ERR_FAIL_PARAM');
            }
            //用户不存在 返回错误
            if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
            //验证token
            if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
                $followed = Follow::model()->followedList($user_id);
            }
        }

        foreach ($data as $k=>$r) {
            if(is_array($r) && !empty($r)){
                //增加图片
                $rand = mt_rand(1, 100);
                if($rand == 1){
                    $picLv = 10;
                }elseif($rand == 2 || $rand == 3){
                    $picLv = 9;
                }elseif($rand > 2 && $rand < 6){
                    $picLv = 8;
                }elseif($rand > 5 && $rand < 10){
                    $picLv = 7;
                }elseif($rand > 9 && $rand < 15){
                    $picLv = 6;
                }elseif($rand > 14 && $rand < 21){
                    $picLv = 5;
                }elseif($rand > 20 && $rand < 28){
                    $picLv = 4;
                }elseif($rand > 27 && $rand < 36){
                    $picLv = 3;
                }elseif($rand > 35 && $rand < 45){
                    $picLv = 2;
                }else{
                    $picLv = 1;
                }
                $t = Photo::model()->heroinePhotosIds($r['goddess_id'], $picLv);
                $p = array();
                if(!empty($t)){
                    $tKey = array_rand($t, 1);
                    $p = Photo::model()->photoInfo($t[$tKey]['photo_id']);
                }
                unset($p['heroine_id']);
                unset($p['devit']);
                $p = array();
                if(empty($p)){
                    $p = Photo::model()->single($r['goddess_id']);
                }
                if($p === false) $p = null;
                $data[$k]['photo'] = $p;

                if(in_array($r['goddess_id'], $followed)){
                    $data[$k]['followed'] =  1;
                }else{
                    $data[$k]['followed'] =  0;
                }
            }
        }

        $this->_return('MSG_SUCCESS', $data);

    }

    /**
     * 女神相册 actionGoddessPhoto
     *
     * @param int $user_id
     * @param int $goddess_id
     * @param int $token
     *
     */
    public function actionGoddessPhoto()
    {
        // 参数检查
        if(!isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));

        //判断女神id
        if(!is_numeric($goddess_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }

        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            //不存在此女神
            $this->_return("MSG_ERR_NO_EXIST_GODDESS");
        }

        if(isset($_REQUEST['user_id'])){
            if(!is_numeric($user_id)){
                $this->_return('MSG_ERR_FAIL_PARAM');
            }
            //用户不存在 返回错误
            if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
            //验证token
            if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
                //token 错误
                $this->_return('MSG_ERR_TOKEN');
            }

            $photos = Goddess::model()->getGoddessPhoto($user_id, $goddess_id);
        }else{
            $photos = Photo::model()->heroinePhotos($goddess_id);
            $imgUrl = Yii::app()->params['img_url_base'];
            foreach ($photos as $k => $v) {
                $photos[$k]['id']         = (int)$v['photo_id'];
                $photos[$k]['goddess_id'] = (int)$v['heroine_id'];
                $photos[$k]['lock']       = 0;
                $photos[$k]['praised']    = 0;
                $photos[$k]['url']        = $imgUrl.$v['url'];
                $photos[$k]['thumb']      = $imgUrl.$v['thumb'];
                $liking = Liking::model()->getLikingLvRow($v['level']);
                $photos[$k]['gold']       = (int)$liking['gold'];
                $photos[$k]['praisednum'] = (int)$v['praised_counts'];
                unset($photos[$k]['photo_id'],
                      $photos[$k]['heroine_id'],
                      $photos[$k]['praised_counts'],
                      $photos[$k]['create_ts'],
                      $photos[$k]['devit']);
            }
        }

// var_dump($photos);exit;
        $result = array();
        $info   = array();

        if(is_array($photos)){
            foreach ($photos as $key=>$p) {
                switch ($p['level']) {
                    case '1':
                        $info['1'][] = $p;
                        break;
                    case '2':
                        $info['2'][] = $p;
                        break;
                    case '3':
                        $info['3'][] = $p;
                        break;
                    case '4':
                        $info['4'][] = $p;
                        break;
                    case '5':
                        $info['5'][] = $p;
                        break;
                    case '6':
                        $info['6'][] = $p;
                        break;
                    case '7':
                        $info['7'][] = $p;
                        break;
                    case '8':
                        $info['8'][] = $p;
                        break;
                    case '9':
                        $info['9'][] = $p;
                        break;
                    case '10':
                        $info['10'][] = $p;
                        break;
                }
            }
        }

        foreach ($info as $k_lv => $v_arr) {
            $tmp = array();
            $num = 0;
            $tmp['level'] = $k_lv;
            foreach ($v_arr as $pt) {
                $num = $pt['lock']==1 ? $num+1 : $num;
            }
            $tmp['lock_num'] = $num;
            $tmp['count_num'] = count($v_arr);
            $tmp['images'] = $v_arr;
            $result[] = $tmp;
        }

        //排序
        if(!empty($result)){
            $sorting = array();
            foreach ($result as $k=>$v) {
                $sorting[$k] = $v['level'];
            }
            array_multisort($result, SORT_NUMERIC, $sorting);
        }
        $this->_return('MSG_SUCCESS', $result);
    }

    /**
     * 女神的消息
     *
     * @param int $user_id
     * @param int $goddess_id
     * @param int $token
     */
    public function actionGoddessMess()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));

        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');

        $result = array();

        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
        	$message = Message::model()->noReadGoddessMessList($user_id, $goddess_id);
        	foreach ($message as $v) {
                //女神的详细信息
                $heroineInfo = Goddess::model()->getGoddessInfo($v['heroine_id']);
                
                $tem['id']        = $v['m_id'];
                $tem['type']      = (int)$v['msg_type'];
                $tem['text']      = $v['msg_text'];
                $tem['image']       = $v['msg_image'];
                $tem['url']       = $v['msg_url'];
                $tem['time']      = $v['create_ts'];
                $tem['goddess_id']     = $v['heroine_id'];                   #女神id
                $tem['goddess_name']   = $heroineInfo['nickname'];           #女神名字
                $tem['goddess_face']   = $heroineInfo['faceurl'];            #头像地址
                $result[] = $tem;
                //将消息内的图片，信息关联
                Message::model()->readMess($user_id, $v['m_id']);
                //更新消息状态为已读
                Message::model()->updateMessType($user_id, $v['m_id'], 1);                       		
                //读消息
                $memo = $v['m_id'].'|'.$v['msg_type'];
                Log::model()->_goddess_log($user_id, $goddess_id, 'READ_MESSAGE', date('Y-m-d H:i:s'), $memo);
            }
            $this->_return('MSG_SUCCESS', $result);

        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 桃花运 actionPeach
     *
     * @param string $user_id
     * @param string $token
     *
     */
    public function actionPeach(){
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $followed = array();
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            $result = Goddess::model()->goddessPeach($user_id);
            // 成功
            $this->_return('MSG_SUCCESS', $result);
        }else{
            //token 错误
            $this->_return('MSG_ERR_TOKEN');
        }
    }

    /**
     * 女神粉丝排行榜
     *
     * @param int    $user_id
     * @param string $token
     * @param int    $goddess_id
     */
    public function actionFans(){

        // 参数检查
        if(!isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }

        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));

        if(isset($_REQUEST['user_id']))
        {
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
        }else{
            $user_id = 0;
        }


        if(!is_numeric($goddess_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //女神不存在 返回错误
        if($goddess_id < 1) $this->_return('MSG_ERR_FAIL_PARAM');

        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            //不存在此女神
            $this->_return("MSG_ERR_NO_EXIST_GODDESS");
        }
        $result = Goddess::model()->getfans($goddess_id, $user_id);
        // 成功
        $this->_return('MSG_SUCCESS', $result);
        
    }

    /**
     * 女神广场
     * @param int     $user_id*
     * @param string  $token*
     * @param int     $tag_id*
     * @param int     $limit*
     */
    public function actionSquare()
    {
        $now         = date("Y-m-d H:i:s");
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $limit       = trim(Yii::app()->request->getParam('limit'));
        $tag_id      = trim(Yii::app()->request->getParam('tag_id'));

        $limit = 9;

        $max = Photo::model()->getMaxPhotoId();
        //数据库操作错误 返回错误
        if($max === false)
        {
            $this->_return('MSG_SUCCESS');
        }

        $cards  = array();
        $photos = array();

        while(count($cards) < $limit){
            $info = array();
            $rand = mt_rand(1, $max);
            if(!in_array($rand, $photos))
            {
                $photos[] = $rand;
                $info = Photo::model()->photoInfo($rand);
                if(!empty($info)){
                    $cards[] = $info;
                }
            }
            if(count($photos) > 40){
                break;
            }
        }
        foreach ($cards as $key => $value) {
            $cards[$key]['id']         = (int)$value['photo_id'];
            $cards[$key]['goddess_id'] = (int)$value['heroine_id'];
            unset($cards[$key]['photo_id'], $cards[$key]['heroine_id'], $cards[$key]['devit'], $cards[$key]['praised_count']);
            $cards[$key]['lock']    = 0;
            $cards[$key]['praised'] = 0;
        }

        // if(isset($_REQUEST['user_id']))
        // {
        //     if(!is_numeric($user_id)){
        //         $this->_return('MSG_ERR_FAIL_PARAM');
        //     }
        //     //用户不存在 返回错误
        //     if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        //     //验证token
        //     if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        //     {
        //         //token 错误
        //         $this->_return('MSG_ERR_TOKEN');
        //     }
        // }
        // echo '<pre>';
        // print_r($cards);exit;
        // 成功
        $this->_return('MSG_SUCCESS', $cards);
    }
    
    /**
     *  获取用户得到的所有照片
     *  
     */
    public function actionGetUserAllPhoto(){
        // 参数检查
        if( !isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['type']) ){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
        $user_id    = trim(Yii::app()->request->getParam('user_id'));
        $token      = trim(Yii::app()->request->getParam('token'));
        $type       = trim(Yii::app()->request->getParam('type'));
        $timestamp  = trim(Yii::app()->request->getParam('timestamp'));
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //验证token
        if(!Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID']))
        {
            $this->_return('MSG_ERR_TOKEN');
        }
        
        $timestamp = strtotime($timestamp);
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        
        $image = Photo::model()->getUserAllPhoto($user_id, $type, $timestamp);
        
        $this->_return('MSG_SUCCESS', $image);
    }

    /*******************************************************
	 * 女神照片猜图 actionGetGuessImage
	*
	* @param $user_id			// 用户id
	* @param $token			// 用户token
	* @param $goddess_id		// 女神ID
	*
	* @return $error			// 成功 or 失败
	* @return $result			// 调用返回结果
	* @return $success			// 调用返回结果说明
	*
	* 说明：猜图结果
	*******************************************************/
    public function actionGetGuessImage(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
       
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $token = trim(Yii::app()->request->getParam('token'));
        $goddess_id = trim(Yii::app()->request->getParam('goddess_id'));
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        
        //查询是否有此女神
        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            $this->_return('MSG_ERR_NO_EXIST_GODDESS');
        }
        
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //获取翻开一张牌
            $res = Goddess::model()->get_guess_image($user_id,$goddess_id);
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_CHAKRA_DEFICIENCY');
            	case -2 : $this->_return('MSG_ERR_NO_FOLLOW');
            	case -3 : $this->_return('MSG_ERR_LIKING_NO_GUESS');
            	case -6 : $this->_return('MSG_ERR_NO_FOUND_IMG');
            	default :
            	    //进入擦图模式
                    if(isset($res['err']) && ( $res['err'] == -4 || $res['err'] == -5)){
                        Log::model()->_game_log($user_id, $goddess_id, $res['log']['guess_id'],
                        0, 201, 'GUESS_WIPE_IMAGE', date('Y-m-d H:i:s'), '');
                        $this->_return('MSG_SUCCESS',$res['result']);
                        
                    }
            	    break;
            }
            //记录日志
            if(isset($res['log'])){
                switch ($res['log']['type']){
                	case 1: $log_code = 'GUESS_IMAGE_PHOTO'; break;
                	case 2: $log_code = 'GUESS_IMAGE_NULL'; break;
                	case 3: $log_code = 'GUESS_IMAGE_VIT'; break;
                	case 4: $log_code = 'GUESS_IMAGE_LIKING'; break;
                	case 5: $log_code = 'GUESS_IMAGE_PLUS_GOLD'; break;
                	case 6: $log_code = 'GUESS_IMAGE_LOWER_GOLD'; break;
                	case 7: $log_code = 'GUESS_IMAGE_FLOWERS'; break;
                }
                //消耗体力日志
                Log::model()->_vit_log($user_id, $res['log']['vit'],
                $res['log']['vit_after'], 'GUESS_LESSEN_VIT', date('Y-m-d H:i:s'), $goddess_id);
                //游戏日志
                $memo = '';
                Log::model()->_game_log($user_id, $goddess_id, $res['log']['guess_id'],
                                 $res['log']['val'], 101, $log_code, date('Y-m-d H:i:s'), $memo);
            }
            
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
    }
    
    
    /*******************************************************
     * 女神照片猜图结果提交  actionGuessImage
    *
    * @param $user_id               // 用户id
    * @param $token			      // 用户token
    * @param $goddess_id		      // 女神ID
    * @param $image_id		      // 照片ID
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：猜图结果
    *******************************************************/
    public function actionGuessImage(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || !isset($_REQUEST['image_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
    
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $token = trim(Yii::app()->request->getParam('token'));
        $goddess_id = trim(Yii::app()->request->getParam('goddess_id'));
        $image_id = trim(Yii::app()->request->getParam('image_id'));
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
    
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
    
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //猜图结果提交
            $res = Goddess::model()->guess_image_result($user_id,$goddess_id,$image_id);
            
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_GUESS_EXIST');
            	case -2 : $this->_return('MSG_ERR_NO_FOLLOW');
            	case -3 : $this->_return('MSG_ERR_FAIL_SEARCH');
            	case -4 : $this->_return('MSG_ERR_NO_GET_LOGIN_REWARD');
            	default : break;
            }
            //记录日志
            if(isset($res['log'])){
                $memo = '';
                Log::model()->_game_log($user_id, $goddess_id,  $res['log']['guess_id'], 
                                $res['log']['val'], 102, 'GUESS_IMAGE_REWARD', date('Y-m-d H:i:s'), $memo);
                //记录日志 猜图图解锁照片
                if($res['log']['type'] == 1){
                    Log::model()->_photo_log($user_id, $goddess_id, $image_id, 'GUESS_UNLOCK_IMG', date('Y-m-d H:i:s'), '');
                //3 加体力
                }elseif($res['log']['type'] == 3){
                    Log::model()->_vit_log($user_id, $res['log']['val'],
                        $res['log']['vit_after'], 'GUESS_PLUS_VIT_COUNT', date('Y-m-d H:i:s'), $goddess_id);
                //4加好感
                }elseif($res['log']['type'] == 4){
                    Log::model()->_goddess_log($user_id, $goddess_id, 'DS_LIKING', date('Y-m-d H:i:s'), $res['log']['val']);
                // 5 加金币
                }elseif($res['log']['type'] == 5){
                    Log::model()->_gold_log($user_id, $res['log']['val'], $res['log']['gold_after'], 'GUESS_IMAGE_PLUS_GOLD', date('Y-m-d H:i:s'), '');
                // 6 减金币
                }elseif($res['log']['type'] == 6){
                    Log::model()->_gold_log($user_id, $res['log']['val'], $res['log']['gold_after'], 'GUESS_IMAGE_LOWER_GOLD', date('Y-m-d H:i:s'), '');
                // 7 加鲜花
                }elseif($res['log']['type'] == 7){    
                    Log::model()->_gift_log($user_id, $goddess_id, 1, $res['log']['val'], 0, 'GUESS_FLOWERS_COUNT', date('Y-m-d H:i:s'), '');
                }
            }
            
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
    }

    /*******************************************************
     * 女神照片擦图结果提交  actionGuessImage
    *
    * @param $user_id               // 用户id
    * @param $token			      // 用户token
    * @param $goddess_id		      // 女神ID
    * @param $image_id		      // 照片ID
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：猜图结果
    *******************************************************/
    public function actionWipeImageResult(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || !isset($_REQUEST['image_id']) 
            || !isset($_REQUEST['status'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $token = trim(Yii::app()->request->getParam('token'));
        $goddess_id = trim(Yii::app()->request->getParam('goddess_id'));
        $image_id = trim(Yii::app()->request->getParam('image_id'));
        $status = trim(Yii::app()->request->getParam('status'));
        
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //擦图结果解锁照片
            $res = Goddess::model()->wipe_image_result($user_id,$goddess_id,$image_id,$status);
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_UNKOWN');
            	case -2 : $this->_return('MSG_ERR_GUESS_NO_WIPE');
            	case -3 : $this->_return('MSG_ERR_CHAKRA_DEFICIENCY');
            	default : break;
            }
            if($status == 1){
                //擦图 扣体力
//                 Log::model()->_vit_log($user_id, $res['log']['vit'],
//                 $res['log']['vit_after'], 'WIPE_IMAGE_VIT', date('Y-m-d H:i:s'), $goddess_id);
                //擦图游戏成功纪录
                Log::model()->_game_log($user_id, $goddess_id,  0, $image_id, 202, 'WIPE_IMAGE_OK_REWARD', date('Y-m-d H:i:s'), '');
                //记录日志 擦图解锁照片
                Log::model()->_photo_log($user_id, $goddess_id, $image_id, 'WIPE_IMAGE_UNLOCK_IMG', date('Y-m-d H:i:s'), '');
            }else{
                //擦图游戏失败纪录
                Log::model()->_game_log($user_id, $goddess_id,  0, $image_id, 202, 'WIPE_IMAGE_ERR_REWARD', date('Y-m-d H:i:s'), '');
            }
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
    }
    
    /*******************************************************
     * 金币/女神 解锁女神 actionTermsUnlockGoddess
    *
    * @param $user_id               // 用户id
    * @param $token			      // 用户token
    * @param $goddess_id		      // 女神ID
    *
    * @return $error			// 成功 or 失败
    * @return $result			// 调用返回结果
    * @return $success			// 调用返回结果说明
    *
    * 说明：猜图结果
    *******************************************************/
    public function actionTermsUnlockGoddess(){
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        
        $user_id = trim(Yii::app()->request->getParam('user_id'));
        $token = trim(Yii::app()->request->getParam('token'));
        $goddess_id = trim(Yii::app()->request->getParam('goddess_id'));
        
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
        
        //查询是否有此女神
        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            $this->_return('MSG_ERR_NO_EXIST_GODDESS');
        }
        
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //条件解锁女神
            $res = Goddess::model()->terms_unlock_photo($user_id,$goddess_id);
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_UNKOWN');
            	case -2 : $this->_return('MSG_ERR_HAVE_TERMS');
            	case -3 : $this->_return('MSG_ERR_UNLOCK_GODDESS_GOLD');
            	case -4 : $this->_return('MSG_ERR_UNLOCK_GODDESS_TERMS');
            	case -5 : $this->_return('MSG_ERR_NO_UNLOCK_GODDESS');
            	default : break;
            }
            //记录日志 解锁女神
            Log::model()->_goddess_log($user_id, $goddess_id, 'TERMS_UNLOCK_GODDESS', date('Y-m-d H:i:s'), '');
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
    }
    
    /**
     * 金币解锁照片
     *
     * @param int $user_id
     * @param int $goddess_id
     * @param int $token
     * @param int $image_id
     *
     */
    public function actionGoldUnlockPhoto()
    {
        // 参数检查
        if(!isset($_REQUEST['user_id']) || !isset($_REQUEST['token']) || !isset($_REQUEST['goddess_id']) || !isset($_REQUEST['image_id'])){
            $this->_return('MSG_ERR_LESS_PARAM');
        }
    
        $user_id     = trim(Yii::app()->request->getParam('user_id'));
        $token       = trim(Yii::app()->request->getParam('token'));
        $goddess_id  = trim(Yii::app()->request->getParam('goddess_id'));
        $image_id    = trim(Yii::app()->request->getParam('image_id'));
        $now         = date("Y-m-d H:i:s");
    
        if(!is_numeric($user_id)){
            $this->_return('MSG_ERR_FAIL_PARAM');
        }
        //用户不存在 返回错误
        if($user_id < 1) $this->_return('MSG_ERR_NO_USER');
    
        if(!Goddess::model()->isExitsGoddess($goddess_id))
        {
            //不存在此女神
            $this->_return("MSG_ERR_NO_EXIST_GODDESS");
        }
        
        //验证token
        if(Token::model()->verifyToken($user_id, $token, $GLOBALS['__APPID'])){
            //金币解锁照片
            $res = Goddess::model()->GoldUnlockPhoto($user_id,$goddess_id,$image_id);
            switch($res)
            {
            	case -1 : $this->_return('MSG_ERR_NO_FOUND_IMG');
            	case -2 : $this->_return('MSG_ERR_FAIL_UNLOCKED');
            	case -3 : $this->_return('MSG_ERR_NO_GOLD');
            	default : break;
            }
            $memo = $res['log']['gold'];
            //记录日志 金币解锁照片
            Log::model()->_photo_log($user_id, $goddess_id, $image_id, 'GOLD_UNLOCK_IMG', date('Y-m-d H:i:s'), $memo);
            
        }else{
            $this->_return('MSG_ERR_TOKEN');
        }
        // 发送返回值
        $this->_return('MSG_SUCCESS',$res['result']);
    }
}