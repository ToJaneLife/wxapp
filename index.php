<?php
/*
    yuhanle
    CopyRight 2015 All Rights Reserved
*/

include("./Utils/dateUtils.php");

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
                    $result = $this->receiveImage($postObj);
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
                $content = "Hi！我是致简生活，为您提供最新最全的天气信息！\n\n① 点击输入框 \n② 输入城市名（如：上海）\n③ 发送等回复";
                break;
            case "unsubscribe":
            	$content = "再见！再也不见";
            	break;
        }
        $result = $this->transmitText($object, $content);
        return $result;
    }

    private function checkstr($str, $substr){
        $needle = $substr;
        $tmparray = explode($needle,$str);
        if(count($tmparray) > 1){
            return true;
        } else{
            return false;
        }
    }

    private function receiveText($object)
    {
        $keyword = trim($object->Content);
        
        $result = '';

        if (strpos($keyword, '失业') !== false) {
            $result = $this->transmitWork($object);
        } elseif (strpos($keyword, '工作') !== false) {
            $result = $this->transmitWork($object);
        } else {
            $result = $this->transmitWeather($object);
        }

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

    private function transmitWork($object)
    {
        $itemTpl = "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>";
                    
        $item_str = "";
                        
        $item_str .= sprintf($itemTpl, 
            '你可以从这里找到好工作', 
            '不客气，祝你好运', 
            'http://omiz2siz5.bkt.clouddn.com/zj-weixin/coffee-2425275_640.jpg',  
            'https://luna.58.com/list.shtml?plat=m&city=sh&cate=job&-15=20&utm_source=link&spm=u-Lt2pHoBa1luDubj.mzp_qzzp_qb_hshportal_zgz01');      
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

    private function transmitWeather($object)
    {
        $keyword = trim($object->Content);
        
        $url = "http://api.map.baidu.com/telematics/v3/weather?output=json&ak=XohzuurALBUGvfFA6sGaLGjp&location=".urlencode($keyword);

        $output = file_get_contents($url);
        $newsArray = json_decode($output, true);

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
                
            $item_str .= sprintf($itemTpl, 'Hi！我是致简生活，我把地球翻个遍儿也没找到 '.$keyword, '请输入正常的城市名',
                                         'https://mmbiz.qlogo.cn/mmbiz/icdZ5NYAvaOvKVyZQIEuoYia5ptreV6Wnhe8zT0lYzeEInJEHSLqYQzf4Cw9LBqrCeWH84lfYHEqobYKjtKjC8rw/0?wx_fmt=jpeg', 
                                         'https://weidian.com/s/1202372938?wfr=c&ifr=shopdetail');      
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
        
        $index_data = $newsArray[results][0][index];

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
                // http://life.html5.qq.com/
                $item_str .= sprintf($itemTpl, $currentCity.' '.$item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                     $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'],
                                     'https://mmbiz.qlogo.cn/mmbiz/icdZ5NYAvaOvKVyZQIEuoYia5ptreV6Wnhe8zT0lYzeEInJEHSLqYQzf4Cw9LBqrCeWH84lfYHEqobYKjtKjC8rw/0?wx_fmt=jpeg', 
                                     'https://weidian.com/s/1202372938?wfr=c&ifr=shopdetail');
            }else {
                $item_str .= sprintf($itemTpl, $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                 $item['date'].' '.$item['temperature'].' '.$item['weather'].' '.$item['wind'], 
                                 $this->dayOrNight() ? $item['dayPictureUrl'] : $item['nightPictureUrl'], 
                                 'https://weather.html5.qq.com/');
            }

        } 

        for ($i = 0; $i < count($index_data); $i++) { 
            $item = $index_data[$i];

            if ($i == 0) {
                // 穿衣
                $item_str .= sprintf($itemTpl, $item['title'].' '.$item['des'], 
                                 $item['title'].' '.$item['zs'].' '.$item['des'], 
                                 '', 
                                 'http://m.mogujie.com/');
            } elseif ($i == 1) {
                // 洗车
                $item_str .= sprintf($itemTpl, $item['title'].' '.$item['des'], 
                                 $item['title'].' '.$item['zs'].' '.$item['des'], 
                                 '', 
                                 'http://m.autohome.com.cn/');
            } elseif ($i == 2) {
                // 旅游
                
            } elseif ($i == 3) {
                // 感冒
                
            } elseif ($i == 4) {
                // 运动
                
            } elseif ($i == 5) {
                // 紫外线强度

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

        $result = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($weather_data)+2);
        return $result;
    }   

    private function receiveImage($object)
    {
        $itemTpl = "<item>
                    <Title><![CDATA[%s]]></Title>
                    <Description><![CDATA[%s]]></Description>
                    <PicUrl><![CDATA[%s]]></PicUrl>
                    <Url><![CDATA[%s]]></Url>
                    </item>";
                    
        $item_str = "";
                        
        $item_str .= sprintf($itemTpl, '我是致简生活，给我发图片是什么意思', '请输入正常的城市名',
                                                 'http://7xqhcq.com1.z0.glb.clouddn.com/zjlife/CilEmlbI4_GAXd0TAADmniNS4gA215.jpg', 
                                                 'https://weidian.com/s/1202372938?wfr=c&ifr=shopdetail');      
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
    
    private function logger($log_content)
    {
      
    }

    private function dayOrNight() 
    {
        $d = date("H:i:sa");

        $result = false;
        if (strcmp($d, "06:00:00") > 0 & strcmp($d, "18:00:00") < 0) {
            # code...
            echo "白天" . "\n";
            $result = true;
        }else {
            echo "黑夜" . "\n";
            $result = false;
        }

        return $result;
    }
}
?>