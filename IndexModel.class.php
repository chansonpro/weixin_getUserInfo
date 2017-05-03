<?php
	class IndexModel{
		//回复多图文的函数封装
		public function responseMsg($postObj,$arr){
				$toUser = $postObj->FromUserName;
				$fromUser = $postObj->ToUserName;
				
				//$time   = time();
				$template = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<ArticleCount>".count($arr)."</ArticleCount>
						<Articles>";
			foreach($arr as $k=>$v){
				$template .="<item>
							<Title><![CDATA[".$v['title']."]]></Title> 
							<Description><![CDATA[".$v['description']."]]></Description>
							<PicUrl><![CDATA[".$v['picUrl']."]]></PicUrl>
							<Url><![CDATA[".$v['url']."]]></Url>
							</item>";
			}
			$template .="</Articles>
						</xml> ";
				//注意模板中的中括号 不能少 也不能多
			echo sprintf($template, $toUser, $fromUser, time(), 'news');
		}//responseMsg end
		//回复单文本的封装函数,也可以作为回复关注事件推送的函数
		public function responseTxt($postObj,$contents){
					$template = "<xml>
								<ToUserName><![CDATA[%s]]></ToUserName>
								<FromUserName><![CDATA[%s]]></FromUserName>
								<CreateTime>%s</CreateTime>
								<MsgType><![CDATA[%s]]></MsgType>
								<Content><![CDATA[%s]]></Content>
								</xml>";
					$fromUser = $postObj->ToUserName;//ToUserName开发者微信号
					$toUser   = $postObj->FromUserName;//FromUserName	发送方帐号（一个OpenID）
					$time     = time();
					$msgType  =  'text';
					//$content  = "<a href='http://www.baidu.com'>百度</a>";
					$info     = sprintf($template,$toUser,$fromUser,$time,$msgType,$contents);
					echo $info;
		}//responseTxt end
		//回复微信用户的关注事件
		public function responseSubscribe($postObj,$arr){
			$this->responseMsg($postObj,$arr);
		}//responseSubscribe end
		//关于新闻而专门写的类
		public function responseNews($postObj,$arrres){
				$toUser = $postObj->FromUserName;
				$fromUser = $postObj->ToUserName;
				
				//$time   = time();
				$template = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<ArticleCount>5</ArticleCount>
						<Articles>";
			for($i=0;$i<8;$i++)
			{
				$template .="<item>
							<Title><![CDATA[".$arrres['result']['data'][$i]['title']."]]></Title> 
							<Description><![CDATA[".$arrres['result']['data'][$i]['date']."]]></Description>
							<PicUrl><![CDATA[".$arrres['result']['data'][$i]['thumbnail_pic_s']."]]></PicUrl>
							<Url><![CDATA[".$arrres['result']['data'][$i]['url']."]]></Url>
							</item>";
			}
			$template .="</Articles>
						</xml> ";
				//注意模板中的中括号 不能少 也不能多
			echo sprintf($template, $toUser, $fromUser, time(), 'news');
		}//responseNews end
		public function getNewsFromApi($postObj,$QueryType = 'top'){
				$host = "http://toutiao-ali.juheapi.com";
				$path = "/toutiao/index";
				$method = "GET";
				$appcode = "5ab3c25815ee460783a50fb539c7fe49";
				$headers = array();
				array_push($headers, "Authorization:APPCODE " . $appcode);
				//$QueryType =$postObj->Content;
				$querys = "type=".$QueryType;
				//$querys = "type=caijing";
				//类型,,top(头条，默认),shehui(社会),guonei(国内),guoji(国际),yule(娱乐),tiyu(体育)junshi(军事),keji(科技),caijing(财经),shishang(时尚)
				$bodys = "";
				$url = $host . $path . "?" . $querys;

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
				curl_setopt($curl, CURLOPT_URL, $url);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_FAILONERROR, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_HEADER, false);
				if (1 == strpos("$".$host, "https://"))
				{
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				}
				$res = curl_exec($curl);
				$arrres = json_decode($res,true);
				$this->responseNews($postObj,$arrres);
		} 
		public function getNewsFromTianQi($postObj){
			//接口基本信息配置
				$appkey = 'ec1df8d77368cd447c455e236a719f24'; //您申请的天气查询appkey
				$weather = new weather($appkey);
				//$cityname = urldecode($postObj->Content);
				$cityname = '杭州';
				//$contents = $cityname;
				$cityWeatherResult = $weather->getWeather($cityname);
				//if($cityWeatherResult['error_code'] == 0){    //以下可根据实际业务需求，自行改写
				//////////////////////////////////////////////////////////////////////
				$data = $cityWeatherResult['result'];
				$content_dangqian ="===[".$cityname."]天气实况==="."\n";
				 $content_data= "温度：".$data['sk']['temp']."℃\n"."风向：".$data['sk']['wind_direction']."    （".$data['sk']['wind_strength']."）"."\n"."湿度：".$data['sk']['humidity']."\n";
			 
				$content_tips =  "===未来几天天气预报==="."\n";
				 foreach($data['future'] as $wkey =>$f){
					$content_date .=  "日期:".$f['date']." ".$f['week']." ".$f['weather']." ".$f['temperature']."\n";
				 }
				$content_tianqi =  "===相关天气指数==="."\n";
				$content_tianqi2 = "穿衣指数：".$data['today']['dressing_index']." , ".$data['today']['dressing_advice']."\n"."紫外线强度：".$data['today']['uv_index']."\n"."洗车指数：".$data['today']['wash_index'];
				$contents = $content_dangqian.$content_data.$content_tips.$content_date.$content_tianqi.$content_tianqi2 ; 
				//实例化responseTxt模板	
				$indexModel = new IndexModel;
				$indexModel->responseTxt($postObj,$contents);
		}
	}//class end