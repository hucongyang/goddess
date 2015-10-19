 <?php
 /*********************************************************
 * 支付接口
 * 
 * @package PayController
 * @author  Lujia
 * @version 1.0 by jinhui @ 2014.06.11 创建类以及相关的操作
 ***********************************************************/

class PayController extends ApiPublicController 
{
    /*******************************************************
     * 生成充值订单
     *
     * @param $uid			  // 用户id
     * @param $token		  // 用户token
     * @param $money            // 用户充值金额（单位：分）
     * 
     * @return $result		  // 调用返回结果
     * @return $msg			  // 调用返回结果说明
     *
     * 说明：
     *******************************************************/
    public function actionChargeOrder(){
        // 检查参数      
        if(!isset($_REQUEST['uid']) || !isset($_REQUEST['pay_type'])
            || !isset($_REQUEST['token']) || !isset($_REQUEST['goods_id']) || !isset($_REQUEST['pay_type']) || !isset($_REQUEST['payment_type']) )
        {
            $this->_return('MSG_ERR_LESS_PARAM');
        }
        $uid = Yii::app()->request->getParam('uid');
        $token = trim(Yii::app()->request->getParam('token'));
        $pay_type = trim(Yii::app()->request->getParam('pay_type'));
        $app_id = trim(Yii::app()->request->getParam('app_id'));
        $payment_type = trim(Yii::app()->request->getParam('payment_type',1));
        $goods_id =  Yii::app()->request->getParam('goods_id');

        if(!Token::model()->verifyToken($uid, $token, $GLOBALS['__APPID'])){
        	$this->_return('MSG_ERR_TOKEN');
        }
        
        if(!User::model()->isSetPassword($uid)){
        	$this->_return('MSG_ERR_UNSET_PASSWORD');
        }
        
        $goods_info = Common::model()->getGoodsInfo($goods_id);
        if(!is_array($goods_info)){
        	$this->_return('MSG_ERR_FAIL_PARAM');
        }
        
        // 支付订单创建
        $out_trade_no = Pay::model()->createOrder($uid,$goods_info,$GLOBALS['__APPID'],$pay_type,$GLOBALS['__CHANNEL'], $GLOBALS['__PLATFORM']);
        if($out_trade_no < 0)
        {
            $this->_return('MSG_ERR_UNKOWN');
        }
        // 记录Log
        Log::model()->_pay_log($uid, 'CREATE_ORDER', date('Y-m-d H:i:s'), $out_trade_no);
        
        
        $data['out_trade_no'] = $out_trade_no;    
        
        $config_arr = Yii::app()->params['alipay_config'];
        //支付宝充值返回支付宝前段签名
        Yii::import('application.extensions.alipay.*');
        require_once('lib/alipay_notify.class.php');
        $alipayNotify = new AlipayNotify($config_arr);

        $price = $goods_info['price']/100;
        $subject = $goods_info['name'];
        $body = $goods_info['name'] . ", 你值得拥有";
        
        $price= '"'.$price.'"';
        $subject = '"'.$subject.'"';
        $body = '"'.$body.'"';
        
        if($app_id){
            $app_id = '"'.$app_id.'"';
        }
        /*
        if($extern_token){
            $extern_token = '"'.$extern_token.'"';
        }
        */
        /*
        if($appenv){
            $appenv = '"'.$appenv.'"';
        }
        */
        if($payment_type){
            $payment_type = '"'.$payment_type.'"';
        }
        /*
        if($it_b_pay){
            $it_b_pay = '"'.$it_b_pay.'"';
        }
        */
        /*
        if($show_url){
            $show_url = '"'.urlencode($show_url).'"';
        }
        */
       
        $service = '"mobile.securitypay.pay"';
        $partner = '"'.Yii::app()->params['partner'].'"';
        $charset = '"utf-8"';
        $notify_url = '"'.urlencode(Yii::app()->params['notifyUrl']).'"';
        $out_trade_no = '"'.$out_trade_no.'"';
        $seller_id = '"'.Yii::app()->params['partner'].'"';
        
        $sign_date = array(
                //'it_b_pay'=>$it_b_pay,
                //'show_url'=>$show_url,
                //'extern_token'=>$extern_token,
                'seller_id'=>$seller_id,
                'total_fee'=>$price,
                'service'=> $service,
                'partner'=> $partner,
                '_input_charset'=> $charset,
                'notify_url'=>$notify_url,
                'out_trade_no'=>$out_trade_no,
                'subject'=>$subject,
                'app_id'=>$app_id,
                //'appenv'=>$appenv,
                'payment_type'=>$payment_type,
                'body'=>$body
        );
        $sign_date = paraFilter($sign_date);
        $sign_date = createLinkstring($sign_date);
        $private_key_url = '../extensions/alipay/'.$alipayNotify->alipay_config['private_key_path'];
        $data['sign'] = rsaSign($sign_date, $private_key_url);       
        $data['url'] = $sign_date;
        $data['notifyUrl'] = Yii::app()->params['notifyUrl'];
      	
        // 发送返回值
        $this->_return('MSG_SUCCESS',$data);
    }
    /*******************************************************
    *
    *   
    *   
    * @param $uid			// 用户id
    * @param $token		     // 用户token
    *
    * @return $result		// 调用返回结果
    * @return $msg			// 调用返回结果说明
    *
    * 说明：
    *******************************************************/
    public function actionNotify()
    {   
        // 记录Log
        //Log::model()->_payreturn_log('ORDER_PAY_POST', date('Y-m-d H:i:s'), json_encode($_POST));
        
        $config_arr = Yii::app()->params['alipay_config'];
        
        //计算得出通知验证结果
        Yii::import('application.extensions.alipay.*');
        require_once('lib/alipay_notify.class.php');
    
        $alipayNotify = new AlipayNotify($config_arr);
        $verify_result = $alipayNotify->verifyNotify();
        
        if($verify_result) {//验证成功
            //通知时间
            $data['notify_time'] = trim(Yii::app()->request->getParam('notify_time'));
            //通知类型
            $data['notify_type'] = trim(Yii::app()->request->getParam('notify_type'));
            //通知效验ID
            $data['notify_id'] = trim(Yii::app()->request->getParam('notify_id'));
            //签名
            $data['sign'] = trim(Yii::app()->request->getParam('sign'));
            //商户订单号
            $data['out_trade_no'] = trim(Yii::app()->request->getParam('out_trade_no'));
            //商品名称
            $data['subject'] = trim(Yii::app()->request->getParam('subject'));
            //支付类型
            $data['payment_type'] = trim(Yii::app()->request->getParam('payment_type'));
            //支付宝交易号
            $data['trade_no'] = trim(Yii::app()->request->getParam('trade_no'));
            //交易状态
            $data['trade_status'] = trim(Yii::app()->request->getParam('trade_status'));
            //卖家支付宝用户名
            $data['seller_id'] = trim(Yii::app()->request->getParam('seller_id'));
            //卖家支付宝账号
            $data['seller_email'] = trim(Yii::app()->request->getParam('seller_email'));
            //买家支付宝用户名
            $data['buyer_id'] = trim(Yii::app()->request->getParam('buyer_id'));
            //买家支付宝账号
            $data['buyer_email'] = trim(Yii::app()->request->getParam('buyer_email'));
            //交易金额
            $data['total_fee'] = trim(Yii::app()->request->getParam('total_fee'));
            //购买数量
            $data['quantity'] = trim(Yii::app()->request->getParam('quantity'));
            //商品描述
            $data['body'] = trim(Yii::app()->request->getParam('body'));
            //交易创建时间
            $data['gmt_create'] = trim(Yii::app()->request->getParam('gmt_create'));
            //交易付款时间
            $data['gmt_payment'] = trim(Yii::app()->request->getParam('gmt_payment'));
            
            $return = Pay::model()->paySuccess($data);
            echo $return;
        }else{
            // 发送返回值
            // 记录Log
        	Log::model()->_pay_log(0, 'ORDER_PAY_SIGN_ERR', date('Y-m-d H:i:s'));
        	
            //Log::model()->_payreturn_log('ORDER_PAY_SIGN_ERR', date('Y-m-d H:i:s'), json_encode($_POST));
            //验证失败
            echo "fail";
    
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }
  	
    /**
     * IOS IAP获取服务器订单号
     */
    public function actionGenerateIapTradeno(){
    	
    	if(!isset($_REQUEST['token']) || !isset($_REQUEST['uid']) || !isset($_REQUEST['item_id'])){
    		$this->_return('MSG_ERR_LESS_PARAM');
    	}
    	$uid = trim(Yii::app()->request->getParam('uid'));
    	$token = trim(Yii::app()->request->getParam('token'));
    	$goods_id = Yii::app()->request->getParam('item_id');
    	$pay_type = 102; //IOS IAP
    	if(!Token::model()->verifyToken($uid, $token, $GLOBALS['__APPID']))
    	{
    		$this->_return('MSG_ERR_TOKEN'); //#token 错误
    	}
    	
    	if(!User::model()->isSetPassword($uid)){
    		$this->_return('MSG_ERR_UNSET_PASSWORD');
    	}
    	
    	$goods_info = Common::model()->getGoodsInfo($goods_id);
    	if(!is_array($goods_info)){
    		$this->_return('MSG_ERR_FAIL_PARAM');
    	}
    	
    	// 支付订单创建
    	$out_trade_no = Pay::model()->createOrder($uid,$goods_info,$GLOBALS['__APPID'],$pay_type,$GLOBALS['__CHANNEL'], $GLOBALS['__PLATFORM']);
    	if($out_trade_no < 0)
    	{
    		$this->_return('MSG_ERR_UNKOWN');
    	}
    	Log::model()->_pay_log($uid, 'CREATE_ORDER', date('Y-m-d H:i:s'), $out_trade_no);
    	$this->_return('MSG_SUCCESS', array('trade_no'=>$out_trade_no));
    }
    
    /**
     * IOS IAP 支付验证
     * $uid
     * $token
     * $item_id
     * $receipt
     */
    public function actionIosVerify(){ 	
    	
    	// error_log(json_encode($_POST));
    	
    	if(!isset($_REQUEST['receipt']) || !isset($_REQUEST['uid']) || !isset($_REQUEST['token']) || !isset($_REQUEST['trade_no'])){
    		$this->_return('MSG_ERR_LESS_PARAM');
    	}
    	 
    	/*沙盒测试开关,正式发布时,需置为false**********************************/
    	$isSandbox = true;
    	/********************************************************************/
    	
    	$receipt = Yii::app()->request->getParam('receipt');
    	$uid = trim(Yii::app()->request->getParam('uid'));
    	$token = trim(Yii::app()->request->getParam('token'));
    	$trade_no = Yii::app()->request->getParam('trade_no');

    	$now = date('Y-m-d H:i:s');
    	 
    	if(!Token::model()->verifyToken($uid, $token, $GLOBALS['__APPID']))
    	{
    		$this->_return('MSG_ERR_TOKEN'); //#token 错误
    	}    	
    	$param = array('uid'=>$uid, 'receipt'=>$receipt, 'create_ts'=>$now, 'trade_no'=>$trade_no);

    	$trade_info = Pay::model()->getTradeInfo($trade_no);

    	if($trade_info['uid'] != $uid || $trade_info['status'] != 0){
    		Pay::model()->recordIOSIAPInfo($param);
    		Log::model()->_pay_log($uid,'ORDER_IOS_IAP_VERIFY_WRONG', $now, $trade_no, "无效或重复的订单");
    		$this->_return('MSG_ISO_PAY_WRONG');
    	}
    	
    	if ($isSandbox) {
    		$endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
    	}
    	else {
    		$endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
    	}
    	$postData = json_encode(
    			array('receipt-data' => $receipt)
    	);
    	 
    	$ch = curl_init($endpoint);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_POST, true);
    	//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    	//curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
    	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	 
    	$response = curl_exec($ch);
    	$errno    = curl_errno($ch);
    	$errmsg   = curl_error($ch);
    	curl_close($ch);
    	
    	if ($errno != 0) {
    		Log::model()->_pay_log($uid,'ORDER_IOS_IAP_VERIFY_WRONG', $now, trade_no, $errno.">".$errmsg);
    		Pay::model()->recordIOSIAPInfo($param);
    		$this->_return('MSG_ISO_PAY_WRONG');
    	}
    	$param['verify_data'] = $response;
    	
    	$data = json_decode($response);
    	 
    	if (!is_object($data)) {
    		Log::model()->_pay_log($uid,'ORDER_IOS_IAP_VERIFY_WRONG', $now, $trade_no, '不能解析返回数据>'.$response);
    		Pay::model()->recordIOSIAPInfo($param);
    		$this->_return('MSG_ISO_PAY_WRONG');
    		//throw new Exception('Invalid response data');
    	}
    	 
    	if (!isset($data->status) || $data->status != 0) {
    		Log::model()->_pay_log($uid,'ORDER_IOS_IAP_VERIFY_FAIL', $now, $trade_no);
    		Pay::model()->recordIOSIAPInfo($param);
    		$this->_return('MSG_ISO_PAY_FAIL');
    	}
    	
    	$add_coin = $trade_info['coin'] + $trade_info['free'];
    	Pay::model()->iosPaySuccess($uid, $trade_no, $add_coin);
    	Pay::model()->recordIOSIAPInfo($param);
    	Log::model()->_pay_log($uid,'ORDER_IOS_IAP_VERIFY_OK', $now, $trade_no);
    	
    	$res = array(
    		'gold' =>$add_coin,
    	);
    	 
    	$this->_return('MSG_SUCCESS', $res);
    }    
}