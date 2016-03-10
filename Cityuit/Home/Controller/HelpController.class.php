<?php
namespace Home\Controller;
use Think\Controller;
/*
 *第三排按钮帮助功能等接口，除了解除绑定
 */
class HelpController extends Controller {
    public function index(){
        /* $this->display(); */
    }

    /*
     *退出操作成功提示
     */
    public function exitHelp($weChat){
        S($weChat->getRevFrom().'_do',null);   //删除操作缓存
        $weChat->text("成功退出当前操作。有疑问，请输入【帮助】查询。")->reply();
    }

    /*
     *帮助提示
     */
    public function dealHelp($weChat){
        $weChat->text("帮助详情")->reply();
    }
    /*
     *接收更新信息控制，提示
     */
    public function updateInfo($weChat){
        $id = A('Login')->hasBind($weChat, $weChat->getRevFrom(), true);  //实例化Login控制器调用方法,如果存在返回学号值，不存在
        if(!$id){
            $weChat->text("你没有绑定个人学号信息，绑定后才可以个人信息查询，欢迎绑定。")->reply();
        }else{   
            $weChat->text("你确定要信息更新吗？将会删除所有个人信息，建议在确认校网没有问题的情况下操作。\n\n回复【确认】信息更新\n回复【exit】退出操作")->reply();
            S($weChat->getRevFrom().'_do','updateinfo','120');
        }
    }

    /*
     *处理更新信息，删除课表，成绩，帐号密码等所有信息，外加备份。。。
     */
    public function dealUpdate($weChat){
        //获取学号
        $studentno = A('Login')->hasBind($weChat, $weChat->getRevFrom(), true);  //实例化Login控制器调用方法,如果存在返回学号值，不存在方法已将提醒绑定发送，并停止程序
        //删除tp_open_id表  必须得按照学号
        $Openid = M("openId"); // 实例化User对象
        $where['studentno'] = ':studentno';   //参数绑定
        $Openid->where($where)->bind(':studentno',$studentno)->delete();

        //获得密码
        $Idpass = M('idPass');  //先从本地数据库查询有没有之前绑定存在的帐号信息
        $where['studentno'] = ':studentno';
        $student = $Idpass->where($where)->bind(':studentno', $studentno)->find();
        $password = $student['password'];
        //删除tp_id_pass表 
        $Idpass->where($where)->bind(':studentno', $studentno)->delete();
        //备份信息
        $idpassVal = array(
            'studentno' => $studentno,
            'password' => $password
        );
        $Idback = M('idBack');
        $Idback->data($idpassVal)->add();

        //删除tp_schedule表
        $Schedule = M('Schedule');
        $where['studentno']=':studentno';
        $Schedule->where($where)->bind(':studentno',$studentno)->delete();

        //删除tp_score表
        $Score = M('Score');
        $where['studentno']=':studentno';
        $Score->where($where)->bind(':studentno',$studentno)->delete();

        //提醒
        $weChat->text("信息更新完成，请重新绑定查询！")->reply();
    }

    /*
     *小助手盒子
     */
    public function cityBox($weChat)
    {
        $box = array(
            "0"=>array(
                'Title' => '城院盒子',
                'Description'=>"安卓手机打开后请点击右上角在浏览器中打开，否者可能无法下载。\n苹果手机请在APP Store中搜索“城院小助手”下载。",
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/box.png',
                'Url'=> 'http://fir.im/citybox'
            ),
         );
        $weChat->news($box)->reply();
        exit;
    }

    /*
     *查询放假处理
     */
    public function showHoliday($weChat){
        
        $hol = array(
            "0"=>array(
                'Title'=>' 2016放假安排时间表',
            ),
            "1"=>array(
                'Title'=>'元旦：1月1日至3日放假调休，共3天。1月4日（星期一）上班',
            ),
            "2"=>array(
                'Title'=>'春节：2月7日至13日放假调休，共7天。2月6日（星期六）、2月14日（星期日）上班',
            ),
            "3"=>array(
                'Title'=>'清明节：4月2日至4日放假，共3天。',
            ),
            "4"=>array(
                'Title'=>'劳动节：4月30日至5月2日放假，共3天。',
            ),
            "5"=>array(
                'Title'=>'端午节：6月9日至11日放假，共3天。6月12日（星期日）上班',
            ),
            "6"=>array(
                'Title'=>'中秋节：9月15日至17日放假，共3天。9月18日（星期日）上班',
            ),
            "7"=>array(
                'Title'=>'国庆节：10月1日至7日放假，共7天。10月8日（星期六）、10月9日（星期日）上班',
            ),
         );
        $weChat->news($hol)->reply();
    }

    /*
     *小游戏处理
     */
    public function dealGame($weChat){
        $game = array(
            "0"=>array(
                'Title' => '朋友圈小游戏',
                'Description' => '快来挑战吧~',
                'PicUrl' => 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/game.png',
                'Url' => $_SERVER['HTTP_HOST'].U("Help/showGame"),
            ),
        );
        $weChat->news($game)->reply();
    }

    /*
     *进入游戏
     */
    public function showGame(){
        $this->display();
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
            if($now > 17){   //晚上就发送晚上的图片
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
}
