<?php
//defined('ADMIN_HOST') or define('ADMIN_HOST', 'http://122.227.43.176/admin');
//defined('API_HOST') or define('API_HOST', 'http://122.227.43.176/index.php/');

// this contains the application parameters that can be maintained via GUI
return array(
        
    // 默认头像地址
    'default_head_image' => '/avatar/goddess/dface.png',
    // 默认女生头像地址
    'default_woman_head_image' => '/avatar/goddess/dwface.png',
	//关注加魅力
    'follow_glamorous' => 100,
    //关注加好感、 解锁照片加好感
    'follow_liking' => 10,
    //关注加经验
    'follow_exp' => 10,
    //翻牌赚金币加经验
    'earn_gold_exp' => 2,
    //每日登录加经验
    'every_login_exp' => 5,
    //好友上限 50
    'friend_upper_limit' => 50,
    //每日赠送好友体力上限为15
    'every_give_vit_upper_limit' => 15,
    //接受好友赠送上限为15
    'every_accept_vit_upper_limit' => 15,
    //女神 10
    'goddess_jpush'=>array(
            //极光推送 秘钥
            'master_secret' =>'b4bc4d539cb355e864cbe0ba',
            //appkey
            'app_key' => 'c1bafcd5f7e484944a5b3315',
    ),
    // 下限少女 11
    'girl_jpush'=>array(
            //极光推送 秘钥
            'master_secret' =>'c191c0f4c92c33f441c3ad52',
            //appkey
            'app_key' => 'bc7c0303741cda3a4b1382ff',
    ),
    // 二次元私密 12
    'secret_jpush'=>array(
            //极光推送 秘钥
            'master_secret' =>'fb5075ca75f4382277a5ea37',
            //appkey
            'app_key' => '46b0c678ad32daed16d2828d',
    ),
    'game_arr' => array(
          10 => array(
                    //默认的抽卡图片数组，随机取一张图片出来
                    'card' =>array(
                            'icons/goddess_2/01.jpg',
                            'icons/goddess_2/02.jpg',
                            'icons/goddess_2/03.jpg',
                            'icons/goddess_2/04.jpg',
                            'icons/goddess_2/05.jpg',
                            'icons/goddess_2/06.jpg',
                            'icons/goddess_2/07.jpg',
                            'icons/goddess_2/08.jpg',
                    ),
                    //九宫格 游戏  炸弹
                    'zhadan' => 'icons/gold/bomb.png',
                    //九宫格 游戏  炸弹 缩微图
                    'zhadan_thumb' => 'icons/gold/bomb.png',
                    //九宫格 游戏  无效
                    'wuxiao' => 'icons/gold/invalidation2.png',
                    //九宫格 游戏  无效 缩微图
                    'wuxiao_thumb' => 'icons/gold/invalidation2.png',
                    //九宫格 奖励 金币基数
                    'gold' => 100,
                    //九宫格 奖励 金币倍数 3个
                    'gold1' => 1,
                    'gold2' => 1.5,
                    'gold3' => 5,
                    //女神猜图奖励牌 效果地址
                    'guess_card_url' => array(
                            '2' => 'icons/guess/invalidation2.png',
                            '3' => 'icons/guess/vit.png',
                            '4' => 'icons/guess/liking.png',
                            '5' => 'icons/guess/gold.png',
                            '6' => 'icons/guess/thief.png',
                            '7' => 'icons/guess/rose.png',
                    ),
                    //效果牌奖励 数值 3体力 4好感 5获得金币 6盗走金币 7获得玫瑰花
                    'status_card' => array(
                            2 => 'icons/guess/invalidation2.png',
                            3=> 10,
                            4=> 10,
                            5=> 100,
                            6=> -100,
                            7=> 10,
                    ),
                    'max_flowers' => 10,
            
            ),
            //下限少女 11
            11 => array(
                    //默认的抽卡图片数组，随机取一张图片出来
                    'card' =>array(
                            'icons/girls/01.jpg',
                            'icons/girls/02.jpg',
                            'icons/girls/03.jpg',
                            'icons/girls/04.jpg',
                            'icons/girls/05.jpg',
                            'icons/girls/06.jpg',
                            'icons/girls/07.jpg',
                            'icons/girls/08.jpg',
                    ),
                    //九宫格 游戏  炸弹
                    'zhadan' => 'icons/gold/bomb.png',
                    //九宫格 游戏  炸弹 缩微图
                    'zhadan_thumb' => 'icons/gold/bomb.png',
                    //九宫格 游戏  无效
                    'wuxiao' => 'icons/gold/invalidation2.png',
                    //九宫格 游戏  无效 缩微图
                    'wuxiao_thumb' => 'icons/gold/invalidation2.png',
                    //九宫格 奖励 金币基数
                    'gold' => 100,
                    //九宫格 奖励 金币倍数 3个
                    'gold1' => 1,
                    'gold2' => 1.5,
                    'gold3' => 5,
                    //女神猜图奖励牌 效果地址
                    'guess_card_url' => array(
                            '2' => 'icons/guess/invalidation2.png',
                            '3' => 'icons/guess/vit.png',
                            '4' => 'icons/guess/liking.png',
                            '5' => 'icons/guess/gold.png',
                            '6' => 'icons/guess/thief.png',
                            '7' => 'icons/guess/rose.png',
                    ),
                    //效果牌奖励 数值 3体力 4好感 5获得金币 6盗走金币 7获得玫瑰花
                    'status_card' => array(
                            2 => 'icons/guess/invalidation2.png',
                            3=> 10,
                            4=> 10,
                            5=> 100,
                            6=> -100,
                            7=> 10,
                    ),
                    'max_flowers' => 10,
            
            ),
            //二次元私密 12
            12 => array(
                    
                    //默认的抽卡图片数组，随机取一张图片出来
                    'card' =>array(
                            'icons/girls/01.jpg',
                            'icons/girls/02.jpg',
                            'icons/girls/03.jpg',
                            'icons/girls/04.jpg',
                            'icons/girls/05.jpg',
                            'icons/girls/06.jpg',
                            'icons/girls/07.jpg',
                            'icons/girls/08.jpg',
                    ),
                    //九宫格 游戏  炸弹
                    'zhadan' => 'icons/gold/bomb.png',
                    //九宫格 游戏  炸弹 缩微图
                    'zhadan_thumb' => 'icons/gold/bomb.png',
                    //九宫格 游戏  无效
                    'wuxiao' => 'icons/gold/invalidation2.png',
                    //九宫格 游戏  无效 缩微图
                    'wuxiao_thumb' => 'icons/gold/invalidation2.png',
                    //九宫格 奖励 金币基数
                    'gold' => 100,
                    //九宫格 奖励 金币倍数 3个
                    'gold1' => 1,
                    'gold2' => 1.5,
                    'gold3' => 5,
                    //女神猜图奖励牌 效果地址
                    'guess_card_url' => array(
                            '2' => 'icons/guess/invalidation2.png',
                            '3' => 'icons/guess/vit.png',
                            '4' => 'icons/guess/liking.png',
                            '5' => 'icons/guess/gold.png',
                            '6' => 'icons/guess/thief.png',
                            '7' => 'icons/guess/rose.png',
                    ),
                    //效果牌奖励 数值 3体力 4好感 5获得金币 6盗走金币 7获得玫瑰花
                    'status_card' => array(
                            2 => 'icons/guess/invalidation2.png',
                            3=> 10,
                            4=> 10,
                            5=> 100,
                            6=> -100,
                            7=> 10,
                    ),
                    'max_flowers' => 10,
            
            )
    ),
);
