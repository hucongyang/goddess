<?php
class consumer extends CActiveRecord
{
    public static function model($className = __CLASS__)
	{
        return parent::model($className);
    }

    /**
     * 创建用户游戏角色 createGameCharacters
     *
     * @param  int $user_id
     * @param  int $level
     *
     * @return boolean
     *
     */
    public function createGameCharacters($user_id, $now, $level=1)
    {

        //初始化体力
        $vit = 60;
        //初始化附加体力
        $extra_vit = 0;
        //初始化积分
        $point = 0;
        //初始化经验
        $exp = 0;
        //初始化体力恢复时间
        $charge_vit_ts = $now;
        //初始金币
        $gold = 2000;
        //初始化关注女神总数
        $follow_counts = 0; //$level_info['follow_toplimit'];

        if(Characters::model()->createGoddessCharacters($user_id, $vit, $extra_vit, $point, $exp, $level, $charge_vit_ts, $follow_counts, $now, $gold))
            return true;
        else
            return false;
    }

    /**
     * 获取用户信息 getUserAll
     *
     * @param int $user_id
     *
     * @return array $info
     */
    public function getUserAll($user_id)
    {
        $data = array();

        //获取用户信息
        $user_info = User::model()->getUserInfo($user_id);
        //获取用户安全信息
        $user_safe_info = User::model()->getUserSafeInfo($user_id);
        //获取用户游戏角色信息
        $characters_info = Characters::model()->getCharactersInfo($user_id);

        $data['username']  = $user_safe_info['user_name'];
        $data['email']     = $user_safe_info['email'];
        $data['avatar']    = $user_info['avatar'];
        $data['nickname']  = $user_info['nickname'];
        $data['birthday']  = $user_info['birthday'];
        $data['point']     = $characters_info['point'];
        $data['birthplace']= $user_info['birthplace'];
        $data['sex']       = $user_info['sex'];
        $data['signature'] = $user_info['signature'];
        

        $data['password']      = $user_safe_info['password'];
        $data['mobile']        = $user_safe_info['mobile'];
        $data['status']        = $user_safe_info['status'];
        $data['from_type']     = $user_safe_info['from_type'];
        $data['from_user_id']  = $user_safe_info['from_user_id'];
        $data['isChangePW']    = $user_safe_info['password'] == '' ? 0:1;
        
        $data['constellation'] = $user_info['constellation'];

        
        $data['vit']           = $characters_info['vit'];
        $data['extra_vit']     = $characters_info['extra_vit'];
        $data['exp']           = $characters_info['exp'];
        $data['level']         = $characters_info['level'];
        $data['charge_vit_ts'] = $characters_info['charge_vit_ts'];
        $data['follow_counts'] = $characters_info['follow_counts'];

        return $data;
    }

    /**
     * 获取用户信息 getUserInfo
     *
     * @param int $user_id
     *
     * @return array $info
     */
    public function getUserInfo($user_id)
    {
        $data = array();

        //获取用户信息
        $user_info = User::model()->getUserInfo($user_id);
        //获取用户安全信息
        $user_safe_info = User::model()->getUserSafeInfo($user_id);
        //获取用户游戏角色信息
        // $characters_info = Characters::model()->getCharactersInfo($user_id);
        if($user_info ==false
        	|| $user_safe_info == false)
        	// || $characters_info == false)
    	{
    		return false;
    	}

        $data = array();

        $data['user_id']     = (int)$user_id;
        $data['username']    = $user_safe_info['user_name'];
        $data['email']       = $user_safe_info['email'];
        $data['isChangePW']    = $user_safe_info['password'] == '' ? 0:1;
        $data['face_url']    = $user_info['avatar'];
        $data['nickname']    = $user_info['nickname'];
        $data['birthday']    = $user_info['birthday'];
        // $data['point']         = $characters_info['point'];
        $data['birthplace']  = $user_info['birthplace'];
        $data['sex']         = (int)$user_info['sex'];
        $data['signature']   = $user_info['signature'];
        // $data['vit']           = $characters_info['vit'];
        // $data['extra_vit']     = $characters_info['extra_vit'];
        // $data['exp']           = $characters_info['exp'];

        return $data;
    }
}