<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第二排按钮，校园服务控制实现
 */
class CampusController extends Controller {
    public function index(){
        /* $this->display(); */
    }

    /*
     *如果是文本收到快递，默认删除缓存单号
     */
    public function resetExpress($weChat){
        S($weChat->getRevFrom().'_spress',null);
        $this->askExpress($weChat);
    }

    /*
     *快递消息处理
     */
    public function dealExpress($weChat){
        if($expressid = S($weChat->getRevFrom().'_spress')){
            //如果缓存里有快递单号，直接返回
            $this->checkExpress($weChat, $expressid);
        }else{
            $this->askExpress($weChat);
        }
    }

    /*
     *发送提醒输入快递单号接口
     */
    public function askExpress($weChat){
        $weChat->text("请输入快递单号（不用输入快递公司）。\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','express','300');     
    }

    /*
     *发送快递查询接口
     */
    public function checkExpress($weChat, $expressid){
        $url = "http://m.kuaidi100.com/index_all.html?postid=".$expressid;
        $title = '快递单号：'.$expressid;
        $ex = array(
            "0"=>array(
                'Title'=> $title,
                'Description'=>"为了方便查询，自动记录单号。\n\n查询其它快递单号，回复【快递】",
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/kuaidi.png',
                'Url'=> $url,
            ),
         );
        $weChat->news($ex)->reply();
        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
    }

    /*
     *小助手微信墙
     */
    public function assistor_wall()
    {
        $title = '小助手微信墙';
        $description = '表白，吐槽，心愿~';
        $picUrl = 'https://csxyxzs.sinaapp.com/img/wall.jpg';
        $url = "http://csxywxq.sinaapp.com/w/";

        assistor_echo_news($title, $description, $picUrl, $url);
        exit;
    }

    /*
     *处理四六级查询接口
     */
    public function daelCet(){
    }


    public function httpGtCet(){
        $ch = curl_init();
        $url = "http://www.chsi.com.cn/cet/query?zkzh=211150152201126&xm=张坤";
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:120.27.53.164', 'CLIENT-IP:120.27.53.164'));

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11"); 
        curl_setopt($ch, CURLOPT_REFERER, "http://www.chsi.com.cn/cet/");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
        curl_setopt($ch, CURLOPT_PROXY, "58.222.254.11"); //代理服务器地址
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128); //代理服务器端口
        /* //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式 */
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if(intval($aStatus["http_code"])==200){
            //开启匹配正则取出内容
            echo $sContent;
        }else{
            /* return false; */
            /* echo 'nihao'.$aStatus["http_code"]; */
            echo $sContent;
        }
    }
    function cet(){
        $name = '张坤';
        $id = '211150152201126';
            $name = urlencode(mb_convert_encoding($name, 'gb2312', 'utf-8'));
                $post = 'id=' . $id . '&name=' . $name;
                $url = "http://cet.99sushe.com/find";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_REFERER, "http://cet.99sushe.com/");
                        curl_setopt($ch, CURLOPT_POST, 1);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                                curl_setopt($ch, CURLOPT_NOBODY, false);
                                $str = curl_exec($ch);
                                    curl_close($ch);
                                    $str = iconv("GB2312", "UTF-8", $str);
                                    if (strlen($str) < 10) {
                                           echo 'nihao' ;        
                                                return false;
                                    }
                                        echo explode(',', $str);

    }


    /*
     *四六级爬虫接口
     */
    public function httpGetCet(){
        header("content-Type: text/html; charset=utf-8");
        ignore_user_abort();

        $url='http://www.chsi.com.cn/cet/';

        $header[]='Host:www.chsi.com.cn';
        $header[]='Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8';
        $header[]='Referer:http://www.chsi.com.cn/cet/';
        $header[]='User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36';
        //$header[]='X-FORWARDED-FOR:120.27.53.164';

        //$header[]='X-Requested-With:XMLHttpRequest';
        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER,true);
        curl_setopt($ch, CURLOPT_NOBODY,true);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headerc);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        $html=curl_exec($ch);
        curl_close($ch);
        preg_match('#Set\-Cookie: (.*?);#i',$html,$matchs);

        print_r($matchs);

        $url='http://www.chsi.com.cn/cet/query?zkzh=211150152201126&xm=张坤';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HEADER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        //curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
        //curl_setopt($ch, CURLOPT_PROXY, "115.148.71.4:9000"); //代理服务器地址
        curl_setopt($ch,CURLOPT_PROXY,'180.106.229.241:80');
        /* //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式 */
        //curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
        curl_setopt($ch, CURLOPT_COOKIE,$matchs[1]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,false);
        curl_exec($ch);
        curl_close($ch);
    }

}
