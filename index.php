<?php
	header("Content-Type:text/html; charset=utf-8");
	require_once'IndexModel.class.php';
		//获得参数 signature nonce token timestamp echostr
		$nonce     = $_GET['nonce'];
		$token     = '123';
		$timestamp = $_GET['timestamp'];
		$echostr   = $_GET['echostr'];
		$signature = $_GET['signature'];
		//形成数组，然后按字典序排序
		$array = array();
		$array = array($nonce, $timestamp, $token);
		sort($array);
		//拼接成字符串,sha1加密 ，然后与signature进行校验
		$str = sha1( implode( $array ) );
		if( $str  == $signature && $echostr ){
			//第一次接入weixin api接口的时候
			echo  $echostr;
			exit;
			}else{
				//$this->definedItem();
				responseMsg();
			}
		// 接收事件推送并回复
	function responseMsg()
	{
		//1.获取到微信推送过来post数据（xml格式）
		$postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
		//2.处理消息类型，并设置回复类型和内容
		$postObj = simplexml_load_string( $postArr );

		//判断该数据包是否是订阅的事件推送
	if( strtolower( $postObj->MsgType) == 'event')
		{
		//如果是关注 subscribe 事件
			if( strtolower($postObj->Event == 'subscribe') )
			{
				$action = new action;
				
			//$content  = '发送方账号'.$postObj->FromUserName.'开发者微信公号原始ID'.$postObj->ToUserName;
			$contents = "";
			$jsoninfo = $action ->getUserInfo($postObj);
			$contents = "您好，".$jsoninfo["nickname"]."\n"."您的openID是：".$jsoninfo['openid']."\n"."性别：".(($jsoninfo["sex"]==1)?"男":(($jsoninfo["sex"]==2)?"女":"未知"))."\n"."地区：".$jsoninfo["country"]." ".$jsoninfo["province"]." ".$jsoninfo["city"]."\n"."语言：".(($jsoninfo["language"] == "zh_CN")?"简体中文":"非简体中文")."\n"."关注：".date('Y年m月d日',$jsoninfo["subscribe_time"]);
			$indexModel = new IndexModel;
			$indexModel->responseTxt($postObj,$contents);//发送纯文本	
			}//subscribe end
		}//end event 事件
	}//end responseMsg
	
	/*
	*
	*处理类，curl，getuserinfo,getWxAccessToken等
	*
	*/
class action{
	/*
	*获取用户信息
	*
	*/
	public function getUserInfo($postObj)
	{
		$access_token = $this->getWxAccessToken();
		//$openid = "oaGl9wJIq5Gz67X504HRNvdJQBBA";
		$openid = $postObj->FromUserName;////获取发送对象账号
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
		$res =$this->http_curl($url,'get','json');
		return $res;
	}//end getUserInfo
	/*
	*获取微信accesstoken
	*
	*/
	public function getWxAccessToken()
	{
		// 由于access_token有过期时间，所以将access_token存放到session/cookie中。
		if ($_SESSION['access_token'] && $_SESSION['expire_time']>time()) {
			//如果access_token没有过期
			return $_SESSION['access_token'];
		} else {
			//如果access_token已经过期，需要重新获取access_token
			$appid 		= 'wx5104563ab1ceb261';//测试账号的Id和secret
		 	$appsecret	= '136cd1efbf8b537425e2b82819881e7b';//chansonpro2的密钥，闲听松风
			$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$appsecret;
			$res = $this->http_curl($url,'get','json');
			$access_token = $res['access_token'];
			// 将获取之后的access_token存放到session
			$_SESSION['access_token'] = $access_token;
			$_SESSION['expire_time']  = time()+7000;

			return $access_token;
		}
	}//end getWxAccessToken
	
	
	/*优化curl函数，使其更加完备
	*$curl   接口URL	  string
	*$type  请求类型   post/get
	*$res   返回数据类型 string
	*$arr   post请求参数 String
	*/
	public function http_curl($url,$type ='get', $res = 'json',$arr= '')
	{//curl 获取工具
		// 1.初始化curl
		$ch = curl_init();
		// 2.设置curl的参数

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		//curl_setopt($ch,CURLOPT_HEADER,0);
		if ($type =='post') {
			//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			//curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
			//curl_setopt($ch,CURLOPT_URL,1);
			curl_setopt($ch,CURLOPT_POST,1);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$arr);
		}
		// 3.采集
		$output = curl_exec($ch);
		
		// 4.关闭
		curl_close($ch);
		if($res == 'json'){
			// var_dump($output);
			//echo curl_errno($ch);
			if (curl_errno($ch)) {
				//请求失败，返回错误信息
				return curl_error($ch);
			} else {
				// 请求成功，返回json数组
				//echo "json";
				return json_decode($output,true);
			}
		}
	}//end curl
}//end class