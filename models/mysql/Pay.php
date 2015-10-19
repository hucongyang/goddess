<?php
 /*********************************************************
 * This is the model class for SheepHappens Pay
 * 
 * @package Pay
 * @author  Lujia
 *
 * @version 1.0 by Lujia @ 2014.03.07 创建类以及相关的操作
 ***********************************************************/

class Pay extends CActiveRecord 
{
	/*******************************************************
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return User the static model class
     *******************************************************/
    public static function model($className = __CLASS__) 
	{
        return parent::model($className);
    }
    
    /*******************************************************
     * 创建订单
    * @param $user_id               // 用户ID
    * @param $money				 // 充值金额 单位分
    * @param $game_id               //游戏ID
    * @param $type                  //充值 支付类型 
    * @param $channel_id            //渠道ID
    * 
    * $status                      // 状态 （0-待付款，1-成功，2-失败)
    *
    * @return $result			// 成功 0 失败 -1
    *******************************************************/
    public function createOrder($user_id,$pay_data,$game_id,$pay_type = 101, $channel_id, $os_type)
    {
    	
        $coin = 0;
        $discount = 0;
        $free = 0;
        $pay_transaction = Yii::app()->db_pay->beginTransaction();
        try
        {      
        	if($os_type == "Android"){
        		$os_type = 1;
        	}else{
        		$os_type = 2;
        	}
        	
            // 插入数据
            Yii::app()->db_pay->createCommand()->insert('orders',
                    array('uid'     => $user_id,
                            'price'     => $pay_data['price'],
                            'coin'  =>  $pay_data['coin'],
                            'free'		=> $pay_data['free'],
                            'status'	=> 0,
                            'create_ts' => date('Y-m-d H:i:s'),
                            'channel_id'   => $channel_id,
                    		'os_type' => $os_type,
                    		'pay_type' => $pay_type,
                    		'app_id' => $game_id,
                            ));
            
            $order_id = Yii::app()->db_pay->getLastInsertID();
            
            $newNumber = substr(strval($order_id+10000000),1,7);
            $mokun_trade_no = time().$newNumber;
            
            Yii::app()->db_pay->createCommand()->update('orders',
                    array('trade_no'	=> $mokun_trade_no),
                    'id=:Order_id ', array(':Order_id' => $order_id));
            $pay_transaction->commit();
        }
        catch(Exception $e)
        {
            $pay_transaction->rollback();
            error_log($e);
            return -1;
        }
        
        return $mokun_trade_no;
    }
    
    /*******************************************************
     * 支付宝快捷支付异步通知 
    * @param $data			// 
    *
    * @return $result			// 成功 success 失败 fail
    *******************************************************/
    public function paySuccess($data)
    {   	
        $con_pay = Yii::app()->db_pay;
        $con_characters = Yii::app()->db_characters;
        $now = date('Y-m-d H:i:s');
        $result = 'fail';
        $pay_transaction = Yii::app()->db_pay->beginTransaction();
        $trans_characters = $con_characters->beginTransaction();
        try
        {
        	$con_pay->createCommand()->insert('pay_notify_alipay',
        	array('notify_time'         => $data['notify_time'],
        			'notify_type'       => $data['notify_type'],
        			'notify_id'         => $data['notify_id'],
        			'sign'		        => $data['sign'],
        			'out_trade_no'	    => $data['out_trade_no'],
        			'subject'           => $data['subject'],
        			'payment_type'      => $data['payment_type'],
       				'trade_no'          => $data['trade_no'],
      				'trade_status'      => $data['trade_status'],
        			'seller_id'         => $data['seller_id'],
        			'seller_email'      => $data['seller_email'],
        			'buyer_id'          => $data['buyer_id'],
        			'buyer_email'       => $data['buyer_email'],
        			'total_fee'         => $data['total_fee'],
        			'quantity'          => $data['quantity'],
        			'body'              => $data['body'],
        			'gmt_create'        => $data['gmt_create'],
        			'gmt_payment'       => $data['gmt_payment'],
        			'create_ts'         => $now,
        	));
        	
            $order_no =  $data['out_trade_no'];
            $res = $con_pay->createCommand()
            	->select('id, price,coin, free, uid, status')
            	->from('orders')
            	->where('trade_no=:Order_no', array(':Order_no' => $order_no))
            	->queryRow();

            if($res){
                if($res['status'] == 0){     			
                    //可以支付
                    if($data['trade_status'] == 'TRADE_FINISHED'){
                        $return_money = $data['total_fee'] * 100;
                        //验证支付金额
                        if($return_money != $res['price']){
                        	
                        	$con_pay->createCommand()->update('orders',array('notify_ts'=>$now), 'id=:Order_id', 
                        			array(':Order_id' => $res['id']));
                        	
                            // 记录Log
                            Log::model()->_pay_log($res['uid'], 'ORDER_PAY_RETURN_MONY_ERR', $now, $order_no, $data['trade_no']);
                            $result = 'fail';
                        }else{
                        	$con_pay->createCommand()->update('orders',
                        			array('status'	=> 1,'charge_ts' => $now, 'notify_ts' => $now),    //订单成功,可以付款给玩家了
                        			'id=:Order_id ', array(':Order_id' => $res['id']));
                        	
                        	//给玩家金币
                        	$add_coin = $res['coin'] + $res['free'];
                        	$p_info = Characters::model()->getCharactersInfo($res['uid']);
                        	Characters::model()->updateCharacters($res['uid'], array('gold'=>$p_info['gold']+$add_coin));
                        	
                        	//充值加金币日志
                        	$gold_params = array(
                        	        'user_id'   =>$res['uid'],
                        	        'type'      =>6,
                        	        'value'     =>$add_coin,
                        	        'gold'      =>$p_info['gold']+$add_coin,
                        	        'create_ts' =>$now
                        	);
                        	Gold::model()->createGold($res['uid'],$gold_params);
                        	
                        	//添加金币log
                        	Log::model()->_gold_log($res['uid'], $add_coin, $p_info['gold']+$add_coin, 'PAY_BUY_GOLD', $now);
                        	// 交易成功Log
                        	Log::model()->_pay_log($res['uid'], 'ORDER_PAY_RETURN_OK', $now, $order_no, $data['trade_no']);
                        	
                        	$result = 'success';
                        }	
                    }
                }else{
                	Log::model()->_pay_log($res['uid'], 'ORDER_PAY_RETURN_REPEAT', $now, $order_no, $data['trade_no']);
                	$result = 'success';
                }              
            }else{
                // 记录Log
                Log::model()->_pay_log($res['user_id'], 'ORDER_PAY_RETURN_SELORDER_ERR', $now, $order_no, $data['trade_no']);
                $result = 'fail';
            }
            $pay_transaction->commit();
            $trans_characters->commit();
        }catch(Exception $e){
            error_log($e);
            $pay_transaction->rollback();
            $trans_characters->rollback();
            //数据库错误返回错误。
            $result = 'fail';
        }
        return $result;
    }
    
    /*******************************************************
     * IOS IAP支付成功处理
    * @param $data			//
    *
    * @return $result			// 成功 success 失败 fail
    *******************************************************/
    public function iosPaySuccess($uid, $trade_no, $add_coin)
    {
    	$con_pay = Yii::app()->db_pay;
    	$now = date('Y-m-d H:i:s');
    	
    	$con_pay->createCommand()->update('orders',
    			array('status'	=> 1,'charge_ts' => $now, 'notify_ts' => $now),    //订单成功,可以付款给玩家了
    			'trade_no=:Trade_no', array(':Trade_no' => $trade_no));
    		
    	//给玩家金币
    	$p_info = Characters::model()->getCharactersInfo($uid);
    	Characters::model()->updateCharacters($uid, array('gold'=>$p_info['gold']+$add_coin));
    	//添加金币log
    	Log::model()->_gold_log($uid, $add_coin, $p_info['gold']+$add_coin, 'PAY_BUY_GOLD', $now);
    }
    
    /**
     * 记录IAP支付信息
     * @param unknown $param
     */
    public function recordIOSIAPInfo($param){
    	$now = date('Y-m-d H:i:s');
    	$param['create_ts'] = $now;
    	$con_pay = Yii::app()->db_pay;
    	$con_pay->createCommand()->insert('ios_iap_info',$param);
    }
    
    public function getTradeInfo($trade_no){
    	return Yii::app()->db_pay->createCommand()
    	->select('id, price,coin, free, uid, status')
    	->from('orders')
    	->where('trade_no=:Trade_no', array(':Trade_no' => $trade_no))
    	->queryRow();
    }
    
}