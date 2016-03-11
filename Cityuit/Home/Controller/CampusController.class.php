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
     *发送提醒输入图书接口
     */
    public function askLibrary($weChat){
        $weChat->text("请输入书名（也可以输入关键字）。\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','library','120');      //提醒输入图书时间预设2分钟 
    }

    /*
     *发送快递查询接口
     */
    public function checkLibrary(){
/* $query_sql = "SELECT * FROM books where upper(book_name) like upper('%".$book_name."%') or upper(author_name) like upper('%".$book_name."%') LIMIT  $offset , $pagesize"; */
        $Library=M('Library');
        $where['booktitle']=array('like',(String)$bookKey.'%');
        $where['booktitle']=array('like','%'.(String)$bookKey);
        $where['booktitle']=array('like','%'.(String)$bookKey.'%');
        $Library->where($where)->select();
        echo $Library->getLastSql();
        /* $weChat->text($Library->getLastSql())->reply(); */
        exit;
        /* $url = "http://m.kuaidi100.com/index_all.html?postid=".$expressid; */
        /* $title = '快递单号：'.$expressid; */
        /* $ex = array( */
        /*     "0"=>array( */
        /*         'Title'=> $title, */
        /*         'Description'=>"为了方便查询，自动记录单号。\n\n查询其它快递单号，回复【快递】", */
        /*         'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/kuaidi.png', */
        /*         'Url'=> $url, */
        /*     ), */
        /*  ); */
        /* $weChat->news($ex)->reply(); */
        /* S($weChat->getRevFrom().'_do',null);   //删除操作缓存 */
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
     *处理食堂查询
     */
    public function askShiting($weChat){
        $weChat->text("请输入查询几食堂\n\n或输入【exit】退出操作。")->reply();
        S($weChat->getRevFrom().'_do','shitang','120');      //缓存查几食堂 
    }

    /*
     *返回食堂信息
     */
    public function dealShitang($weChat, $num){
        $Shitang = M('Shitang');
        if(strpos($num, "2")!==false || strpos($num, "二")!==false){   //查询二食堂
            $where['location']="二食堂";
            $num = "二食堂";
        }else if(strpos($num, "3")!==false || strpos($num, "三")!==false){
            $where['location']="三食堂";
            $num = "三食堂";
        }else{
            $weChat->text("食堂不存在，请重新输入\n例：'2'或者'二'再或者'二食堂'\n\n或输入【exit】退出操作。")->reply();
            exit;
        }
        $shitangArr = $Shitang->where($where)->select();
        $this->showShitang($weChat, $shitangArr, $num);
    }

    /*
     *发送食堂档口信息格式化
     */
    public function showShitang($weChat, $shitangArr, $num){
        $shitangstring = $num."档口：\n";
        $arr = array();
        for($i=0 ; $i<count($shitangArr) ; $i++){
                $shitangstring .= $i.". ".$shitangArr[$i]['name']."\n".$shitangArr[$i]['telephone']."\n";
                $arr[$i]=$shitangArr[$i]['id'];    //存储档口编号
        }

        $shitangstring .= "\n回复对应编号查看菜单\n回复【exit】退出操作";

        S($weChat->getRevFrom().'_do','caidan','120');   //菜单操作缓存
        S($weChat->getRevFrom().'_date', json_encode($arr),'120');   //编号数据缓存
        $weChat->text($shitangstring)->reply();
    }

    /*
     *处理菜单查询
     */
    public function dealCaidan($weChat, $num){
        
        $arr = S($weChat->getRevFrom().'_date');   //编号数据缓存
        $arr = json_decode($arr);
        $id = $arr[$num];   //获取编号对应的数据库中编号
        if($id != ""){
            $Caidan = M('Caidan');
            $where['id']=$id;
            $caidanArr = $Caidan->where($where)->select();
            $this->showCaidan($weChat, $caidanArr);
        }else{
            $weChat->text("编号错误，重新输入\n\n或输入【exit】退出操作。")->reply();
        }
    }

    /*
     *发送档口菜单信息格式化
     */
    public function showCaidan($weChat, $caidanArr){
        $caidanstring = "菜单：";
        $arr = array();
        for($i=0 ; $i<count($caidanArr) ; $i++){
            $caidanstring .= "\n".$caidanArr[$i]['name']." ￥".$caidanArr[$i]['price'];
        }

        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
        $weChat->text($caidanstring)->reply();
    }


    /*
     *小助手微信墙
     */
    public function loveWall($weChat)
    {
        $wall = array(
            "0"=>array(
                'Title' => '小助手微信墙',
                'Description'=>'表白，吐槽，心愿~',
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/wall.jpg',
                'Url'=> 'http://csxywxq.sinaapp.com/w/'
            ),
         );
        $weChat->news($wall)->reply();
        exit;
    }

    /*
     *天气预报处理
     */
    public function dealWeather($weChat){
        $res = $this->getWeather();
        $weaArr = json_decode($res, true);
        $todayWea = $weaArr['retData']['today']['type'];   //今日天气
        if($todayWea){   //如果数据存在或者获取正确返回天气
            $weather = array();
            $top = array(
                'Title' => '大连天气预报',
            );
            $weather[] = $top;   //添加头

            $todayArr = $weaArr['retData']['today'];  //今天天气所有信息
            if(strpos($todayWea, '转') !== false){ //如果有转则根据查询时间显示图片
                $weaDay = $this->changeWeather(explode("转", $todayWea)[0]);    //分割提取白天的天气并转为拼音
                $weaNight = $this->changeWeather(explode("转", $todayWea)[1]);   //晚上
            }else{
                $weaDay = $weaNight = $this->changeWeather($todayWea);    //天气并转为拼音
            }
            $now = date('H', time());
            if($now > 17 || $now < 6){   //晚上就发送晚上的图片
                $PicUrl = 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/weather/night/'.$weaNight.'.png';
            }else{
                $PicUrl = 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/weather/day/'.$weaDay.'.png';
            }
            $today = array(
                //天气字符串连接
                'Title'=>substr($todayArr['date'], 5).' '.$todayArr['week']."\n".$todayArr['type'].' '.$todayArr['fengxiang'].$todayArr['fengli'].' '.$todayArr['hightemp'].'~'.$todayArr['lowtemp'],
                'PicUrl'=>$PicUrl,  
            );
            $weather[] = $today;    //添加今日的天气

            $forecastWea = $weaArr['retData']['forecast'];
            /* for($i=0 ; $i<count($forecastWea) ; $i++){ */    //默认四天
            for($i=0 ; $i<2 ; $i++){   
                //后面天气直接转拼音，图片带有“转”的天气
                $PicUrl = 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/weather/day/'.$this->changeWeather($forecastWea[$i]['type']).'.png';
                $forecast = array(
                    'Title'=>substr($forecastWea[$i]['date'], 5).' '.$forecastWea[$i]['week']."\n".$forecastWea[$i]['type'].' '.$forecastWea[$i]['fengxiang'].$forecastWea[$i]['fengli'].' '.$forecastWea[$i]['hightemp'].'~'.$forecastWea[$i]['lowtemp'],
                    'PicUrl'=>$PicUrl,  
                );
                $weather[] = $forecast;    //添加今日的天气
            }

            $weChat->news($weather)->reply();
        }else{
            $weChat->text("天气服务暂停使用，谢谢支持。\n回复【帮助】获取更多帮助")->reply();
        }
    }

    /*
     *获取天气接口
     */
    public function getWeather(){
        $ch = curl_init();
        $url = 'http://apis.baidu.com/apistore/weatherservice/recentweathers?cityname=大连&cityid=101070201';
        $header = array(
            'apikey:'.C('BAIDUAPI_KEY'),
        );
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        return $res;
    }

    /*
     *根据中文字天气转拼音
     */
    public function changeWeather($zhongwen){
        switch ( $zhongwen ) {
            case '暴雪':
                return 'baoxue';
                break;
            case '暴雨':
                return 'baoyu';
                break;
            case '暴雨转大暴雨':
                return 'baoyuzhuandabaoyu';
                break;
            case '大暴雨':
                return 'dabaoyu';
                break;
            case '大暴雨转特大暴雨':
                return 'dabaoyuzhuantedabaoyu';
                break;
            case '大雪':
                return 'daxue';
                break;
            case '大雪转暴雪':
                return 'daxuezhuanbaoxue';
                break;
            case '大雨':
                return 'dayu';
                break;
            case '大雨转暴雨':
                return 'dayuzhuanbaoyu';
                break;
            case '冻雨':
                return 'dongyu';
                break;
            case '多云':
                return 'duoyun';
                break;
            case '浮尘':
                return 'fuchen';
                break;
            case '雷阵雨':
                return 'leizhenyu';
                break;
            case '雷阵雨伴有冰雹':
                return 'leizhenyubanyoubingbao';
                break;
            case '霾':
                return 'mai';
                break;
            case '强沙尘暴':
                return 'qiangshachenbao';
                break;
            case '晴':
                return 'qing';
                break;
            case '沙尘暴':
                return 'shachenbao';
                break;
            case '特大暴雨':
                return 'tedabaoyu';
                break;
            case '雾':
                return 'wu';
                break;
            case '小雪':
                return 'xiaoxue';
                break;
            case '小雪转中雪':
                return 'xiaoxuezhuanzhongxue';
                break;
            case '小雨':
                return 'xiaoyu';
                break;
            case '小雨转中雨':
                return 'xiaoyuzhuanzhongyu';
                break;
            case '扬沙':
                return 'yangsha';
                break;
            case '阴':
                return 'yin';
                break;
            case '雨夹雪':
                return 'yujiaxue';
                break;
            case '阵雪':
                return 'zhenxue';
                break;
            case '阵雨':
                return 'zhenyu';
                break;
            case '中雪':
                return 'zhongxue';
                break;
            case '中雪转大雪':
                return 'zhongxuezhuandaxue';
                break;
            case '中雨':
                return 'zhongyu';
                break;
            case '中雨转大雨':
                return 'zhongyuzhuandayu';
                break;
        }
    }

    /*
     *处理四六级查询接口
     */
    public function dealCet(){
    }

    /*
     *查询四级
     */
    public function queryCet(){
        $this->display();
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
