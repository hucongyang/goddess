<?php
/*********************************************************
 * 接口公用方法
 *
 * @author  Lujia
 * @version 1.0 by Lujia @ 2013.12.23 创建
 ***********************************************************/
class ApiPublicController extends Controller
{
    public $runtime='';
    public $controller = '';
    /**
     * 构造函数
     *
     */
    public function __construct($id, $module=null)
    {
        Yii::import('application.extensions.*');
        require_once('runtime.php');
        
        $this->runtime = new runtime;
        $this->runtime->start();

        parent::__construct($id, $module);
		
        if($id=='pay' && isset($_REQUEST['trade_no']) && isset($_REQUEST['out_trade_no'])){ 
        	//支付宝回调api, 不需要公共参数检查, 也不需要sign验证
        }elseif($id == 'upload'){
            //前端上传图片不需要公共参数
        }else{
        	if(!isset($_REQUEST['version'])
        	|| !isset($_REQUEST['device_id'])
        	|| !isset($_REQUEST['platform'])
        	|| !isset($_REQUEST['channel'])
        	|| !isset($_REQUEST['app_version'])
        	|| !isset($_REQUEST['os_version'])
        	|| !isset($_REQUEST['app_id']))
        	{
        		$this->_return('MSG_ERR_LESS_PARAM');
        	}
        	 
        	//验证sign
        	if(!$this->validateSign($this->getSignKey()))
        	{
        		$this->_return('MSG_ERR_FAIL_SIGN');
        	}
        }
        
        //if(!$this->validateSign()) $this->_return('MSG_ERR_FAIL_SIGN');
        $GLOBALS['__IP']			= $this->getClientIP();
        $GLOBALS['__VERSION']		= trim(Yii::app()->request->getParam('version'));
        $GLOBALS['__DEVICEID']		= trim(Yii::app()->request->getParam('device_id'));
        $GLOBALS['__PLATFORM']		= trim(Yii::app()->request->getParam('platform'));
        $GLOBALS['__CHANNEL']		= trim(Yii::app()->request->getParam('channel'));
        $GLOBALS['__APPVERSION']	= trim(Yii::app()->request->getParam('app_version'));
        $GLOBALS['__APPID']   		= Yii::app()->request->getParam('app_id')? trim(Yii::app()->request->getParam('app_id')): 10;
        $GLOBALS['__OSVERSION']		= mb_substr(trim(Yii::app()->request->getParam('os_version')), 0, 30, 'utf-8');
        
    }
    
    /**
     * 析构函数，关闭所有的数据库链接
     */
    public function __destruct(){
        foreach (Yii::app()->components as $k => $v){
            $c = substr_count($k,'db_');
            if($c > 0){
                Yii::app()->$k->active = false;
            }
        }
    }

	/*******************************************************
	 * API调用返回对应JSON数据包
	 * @author Lujia
	 * @create 2013/12/20
	 * @modify 2013/12/30   修改为通用返回接口
	 *******************************************************/
    public function _return($error_code, $data=NULL)
	{
		require 'ErrorCode.php';
		
		
		$this->runtime ->stop();
		if(isset($GLOBALS['_SERVER']['REQUEST_URI'])){
                $temp_arr = explode('?', $GLOBALS['_SERVER']['REQUEST_URI']); 
                if(isset($temp_arr[0])){		
                $temp1_arr = explode('index.php', $temp_arr[0]);
                    if(isset($temp1_arr[1])){
                        $action_arr = explode('/', $temp1_arr[1]);
                        $user_id = trim(Yii::app()->request->getParam('user_id'));
                        if(isset($action_arr[1]) && isset($action_arr[2])){
                            Log::model()->_time_log($action_arr[1],$action_arr[2],$this->runtime->spent(),$user_id);
                        }
                    }
                }
		}
		//
		if(!isset($GLOBALS['__APPID']) || $GLOBALS['__APPID'] == ''){
		    $GLOBALS['__APPID'] = 10;
		}
		if(!$data)
		{
			$dstr = json_encode(array('error' => $_error_code[$GLOBALS['__APPID']][$error_code][1],
							   		  'success' => $_error_code[$GLOBALS['__APPID']][$error_code][0]
								));
			exit(str_replace('\/','/',$dstr));
			/*	5.3.* 的版本不支持JSON_UNESCAPED_SLASHES参数
			exit(json_encode(array('error' => $_error_code[$error_code][1],
							   'success' => $_error_code[$error_code][0]), JSON_UNESCAPED_SLASHES));
			*/
		}
		else
		{
			$dstr = json_encode(array('error' => $_error_code[$GLOBALS['__APPID']][$error_code][1],
							          'success' => $_error_code[$GLOBALS['__APPID']][$error_code][0],
							          'result' => $data
							   ));
			exit(str_replace('\/','/',$dstr));
			/*	5.3.* 的版本不支持JSON_UNESCAPED_SLASHES参数
			exit(json_encode(array('error' => $_error_code[$error_code][1],
							   'success' => $_error_code[$error_code][0],
							   'result' => $data), JSON_UNESCAPED_SLASHES));
							   */
		}
    }

    /*******************************************************
	 * API调用返回对应JSON数据包(用于WEB上跨域问题的解决)
	 * @author Lujia
	 * @create 2013/12/20
	 * @modify 2013/12/30   修改为通用返回接口
	 *******************************************************/
    public function _web_return($callback, $error_code, $data=NULL)
	{
		require 'ErrorCode.php';
		$ret_data = NULL;
		if(!$data)
		{
			$ret_data = json_encode(array('msg' => $_error_code[$error_code][1],
							   'result' => $_error_code[$error_code][0]));
		}
		else
		{
			$ret_data = json_encode(array('msg' => $_error_code[$error_code][1],
							   'result' => $_error_code[$error_code][0],
							   'data' => $data));
		}
		exit("$callback($ret_data)");
    }

	/*******************************************************
	 * 检查是否为合法的IP地址
	 * @author Lujia
	 * @create 2013/12/23
	 *******************************************************/
    public function isIPAddress($ip)
	{
		// TODO：这个IP地址不能完全的进行匹配
		$ip_rules = "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/";
		if (preg_match($ip_rules, $ip))
		{
            return true;
        }
		else
		{
            return false;
        }
    }

    /*******************************************************
	 * 检查是否为合法的手机号码
	 * @author Lujia
	 * @create 2013/12/23
	 *******************************************************/
    public function isMobile($mobile)
	{
        if (preg_match("/^1[3|5|8]\d{9}$/", $mobile))
		{
            return true;
        }
		else
		{
            return false;
        }
    }

	/*******************************************************
	 * 检查是否为合法的邮箱地址
	 * @author Lujia
	 * @create 2013/12/24
	 *******************************************************/
    public function isEmail($email)
	{
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        if (preg_match($pattern, $email))
		{
            return true;
        }
		else
		{
            return false;
        }
    }

	/*******************************************************
	 * 检查密码规则
	 * @author Lujia
	 * @create 2013/12/25
	 *******************************************************/
    public function isPasswordValid($pass)
	{
        // $pattern = "/^[\w~!@#$%^&*]{6,20}$/";
        // if (preg_match($pattern, $pass))
        // {
        //     return true;
        // }
        // else
        // {
        //     return false;
        // }

        #新密码规则
        $pattern = "/^[a-zA-Z0-9_]{6,14}$/";

        if(preg_match($pattern, $pass)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 用户名验证
     *
     * @param  string  $username
     * @return boolean
     */
    public function isUsernameValid($username)
    {
        $pattern = "/^[a-zA-Z0-9_]{6,14}$/";

        if(!preg_match('/^\d+$/', $username) && preg_match($pattern, $username)){
            return true;
        }else{
            return false;
        }
    }

	/*******************************************************
	 * 检查是否含有屏蔽词
	 * @author Lujia
	 * @create 2013/12/30
	 *******************************************************/
    public function isExistShieldWord($source)
	{
		$words = Yii::app()->params['shield_word'];
		// 遍历检测
		for($i = 0, $k = count($words); $i < $k; $i++)
		{
			// 如果此数组元素为空则跳过此次循环
			if($words[$i]=='')
			{
				continue;
			}

			// 如果检测到关键字，则返回匹配的关键字,并终止运行
			if(strpos($source, trim($words[$i])) !== false)
			{
				return true;
			}
		}
		return false;
    }

	/*******************************************************
	 * 加密算法
	 * @author Lujia
	 * @create 2013/12/24
	 *******************************************************/
	public function _encrypt($data, $key)
	{
		$key = md5($key);
		$x  = 0;
		$len = strlen($data);
		$l  = strlen($key);
		$char = '';
		$str = '';
		for ($i = 0; $i < $len; $i++)
		{
			if ($x == $l)
			{
				$x = 0;
			}
			$char .= $key{$x};
			$x++;
		}
		for ($i = 0; $i < $len; $i++)
		{
			$str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
		}
		return base64_encode($str);
	}

	/*******************************************************
	 * 解密算法
	 * @author Lujia
	 * @create 2013/12/24
	 *******************************************************/
	public function _decrypt($data, $key)
	{
		$key = md5($key);
		$x = 0;
		$data = base64_decode($data);
		$len = strlen($data);
		$l = strlen($key);
		$char = '';
		$str = '';
		for ($i = 0; $i < $len; $i++)
		{
			if ($x == $l)
			{
				$x = 0;
			}
			$char .= substr($key, $x, 1);
			$x++;
		}
		for ($i = 0; $i < $len; $i++)
		{
			if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1)))
			{
				$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
			}
			else
			{
				$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
			}
		}
		return $str;
	}

	/*******************************************************
	 * 获取密码加解密密钥
	 * @author Lujia
	 * @create 2013/12/24
	 *******************************************************/
	public function getCryptKey()
	{
		return 'mokun';
	}

	/*******************************************************
	 * 获取数据验证密钥
	 * @author Lujia
	 * @create 2014/01/23
	 *******************************************************/
	public function getSignKey()
	{
		return yii::app()->params['token']['key'];
	}

	/*******************************************************
	 * 获取连接IP
	 * @author Lujia
	 * @create 2013/12/26
	 *******************************************************/
	public function getClientIP()
	{
		if (getenv("HTTP_CLIENT_IP"))
		{
			$ip = getenv("HTTP_CLIENT_IP");
		}
		else if(getenv("HTTP_X_FORWARDED_FOR"))
		{
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}
		else if(getenv("REMOTE_ADDR"))
		{
			$ip = getenv("REMOTE_ADDR");
		}
		else
		{
			$ip = "Unknow";
		}
		return $ip;
	}

	/*******************************************************
	 * http post请求
	 * @author Lujia
	 * @create 2011/01/08
	 *******************************************************/
	function actionCurl($remote_server, $post_string)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_URL, $remote_server);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		//  curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
// 		var_dump($post_string);exit;
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
		$result = curl_exec($ch);
		return $result;
	}

	/*******************************************************
	 * 版本号转换
	 * @author Lujia
	 * @create 2011/01/15
	 *******************************************************/
	function convertVersion($version)
	{
		$ver_num = explode(".", $version);
		return $ver_num[0] * 10000 + $ver_num[1] * 100 + $ver_num[2];
	}

    /**
     *  记录日志     未完成
     * @param array $arr
     */
    public function _writeLog($arr) {
        $fileDir = Yii::app()->basePath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
        if (!is_dir($fileDir)) {
            mkdir($fileDir, 0755, true);
        }
        $fileName = $fileDir . 'data.log';
        file_put_contents($fileName, $arr, FILE_APPEND);
    }

    /**
     *  计算字符串长度   未完成
     * @param string $str
     * @return int num
     */
    public function strlen_str($str) {
        $len = strlen($str);
        $i = 0;
        while ($i < $len) {
            if (preg_match("/^[" . chr(0xa1) . "-" . chr(0xff) . "]+$/", $str[$i])) {
                $i+=2;
            } else {
                $i+=1;
            }
        }
        return $i;
    }

    /**
     * 校验token
     * @param  string $key token密钥
     * @return boolean
     */
    public function validateSign($key=null) {
        $key = isset($key) ? $key : yii::app()->params['token']['key'];

        if( is_array($_REQUEST) && isset($_REQUEST[yii::app()->params['token']['sign']]) ){
            $request = $_REQUEST;
            $sign = $request[yii::app()->params['token']['sign']];
            $exclude = yii::app()->params['token']['exclude'];
            if(!empty($exclude) && is_array($exclude)){
                foreach ($exclude as $rVal) {
                    unset($request[$rVal]);
                }
            }
            unset( $request[yii::app()->params['token']['sign']] );
            ksort($request);
//             var_dump($request);exit;
            if( md5($key.implode('', $request)) == $sign ) return true;
        }else{
            //
            return false;
        }
    }


}