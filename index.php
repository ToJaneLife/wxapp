<?php
/*
    yuhanle
    CopyRight 2015 All Rights Reserved
*/

define("TOKEN", "weixin");

$wechatObj = new wechatCallbackapiTest();
if (!isset($_GET['echostr'])) {
    $wechatObj->responseMsg();
}else{
    $wechatObj->valid();
}

class wechatCallbackapiTest
{
    //验证签名
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if($tmpStr == $signature){
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        if (!empty($postStr)){
            $this->logger("R ".$postStr);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $RX_TYPE = trim($postObj->MsgType);

			$result = "";
            switch ($RX_TYPE)
            {
                case "event":
                    $result = $this->receiveEvent($postObj);
                    break;
                case "text":
                    $result = $this->receiveText($postObj);
                    break;
                case "image":
                    $result = $this->receiveText($postObj);
                    break;
                case "voice":
                    $result = $this->receiveText($postObj);
                    break;
                case "video":
                    $result = $this->receiveText($postObj);
                    break;
                case "shortvideo":
                    $result = $this->receiveText($postObj);
                    break;
                case "location":
                    $result = $this->receiveText($postObj);
                    break;
                case "link":
                    $result = $this->receiveText($postObj);
                    break;
            }
            $this->logger("T ".$result);
            echo $result;
        }else {
            echo "";
            exit;
        }
    }

    private function receiveEvent($object)
    {
        
        switch ($object->Event)
        {
            case "subscribe":
                $content = "Hi！我是至简生活，为您提供最新最全的天气信息！\n\n① 点击输入框 \n② 输入城市名（如：上海）\n③ 发送等回复";
                break;
            case "unsubscribe":
            	$content = "再见！再也不见";
            	break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        
        $url = "http://api.map.baidu.com/telematics/v3/weather?output=json&ak=XohzuurALBUGvfFA6sGaLGjp&location=".urlencode($keyword);

        $output = file_get_contents($url);
        $content = json_decode($output, true);
        
        $result = $this->transmitNews($object, $content);
        return $result;
    }

    private function transmitText($object, $content)
    {
		if (!isset($content) || empty($content)){
			return "";
		}
        $textTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    private function transmitNews($object, $newsArray)
    {
        if(!is_array($newsArray)){
            return "";
        }
        
        $keyword = trim($object->Content);
        
        $date = $newsArray[date];
        
        $error = $newsArray[error];
        
        $status = $newsArray[status];
        
        if($error != 0) {
            
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        
        $item_str = "";
            
        $item_str .= sprintf($itemTpl, 'Hi！我是至简生活，我把地球翻个遍儿也没找到 '.$keyword, '请输入正常的城市名',
                                     'https://mmbiz.qlogo.cn/mmbiz/icdZ5NYAvaOvKVyZQIEuoYia5ptreV6Wnhe8zT0lYzeEInJEHSLqYQzf4Cw9LBqrCeWH84lfYHEqobYKjtKjC8rw/0?wx_fmt=jpeg', 
                                     'http://mp.weixin.qq.com/s?__biz=MzA5MjE1NjI1OA==&mid=210526708&idx=1&sn=9b46af73e86fc1789d4ce393054034d7#rd');      
        $newsTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[]]></Content>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count(1));
        return $result;
        }
        
        
        $currentCity = $newsArray[results][0][currentCity];
        
        $pm25 = $newsArray[results][0][pm25];
        
        $weather_data = $newsArray[results][0][weather_data];
        
        $itemTpl = "    <item>
        <Title><![CDATA[%s]]></Title>
        <Description><![CDATA[%s]]></Description>
        <PicUrl><![CDATA[%s]]></PicUrl>
        <Url><![CDATA[%s]]></Url>
    </item>
";
        $item_str = "";
        
        
        for ($i = 0; $i < count($weather_data); $i++) { 
            
            $item = $weather_data[$i];
            
            if($i == 0){
                $item_str .= sprintf($itemTpl, $currentCity.' '.$item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                     $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'],
                                     'https://mmbiz.qlogo.cn/mmbiz/icdZ5NYAvaOvKVyZQIEuoYia5ptreV6Wnhe8zT0lYzeEInJEHSLqYQzf4Cw9LBqrCeWH84lfYHEqobYKjtKjC8rw/0?wx_fmt=jpeg', 
                                     'http://mp.weixin.qq.com/s?__biz=MzA5MjE1NjI1OA==&mid=210526708&idx=1&sn=9b46af73e86fc1789d4ce393054034d7#rd');
            }else {
            	$item_str .= sprintf($itemTpl, $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                     $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                	 $item['dayPictureUrl'], 
                                	 'http://mp.weixin.qq.com/s?__biz=MzA5MjE1NjI1OA==&mid=210526708&idx=1&sn=9b46af73e86fc1789d4ce393054034d7#rd');
            }

        } 
                
        $newsTpl = "<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[news]]></MsgType>
<Content><![CDATA[]]></Content>
<ArticleCount>%s</ArticleCount>
<Articles>
$item_str</Articles>
</xml>";

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($weather_data));
        return $result;
    }   
    
    private function logger($log_content)
    {
      
    }
}
?>