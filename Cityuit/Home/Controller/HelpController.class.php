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
        exit;
    }

    /*
     *帮助提示
     *@param $time=0 表示直接返回帮助，$tiem=1 表示关注返回帮助
     */
    public function dealHelp($weChat, $time = 0){
        $mess = " 回复【bd】绑定学号\n 回复【jc】解除绑定\n 回复【信息更新】信息更新\n 回复【成绩】查询成绩\n 回复【课表】查询今日课表\n 回复【明天】查询明天课表\n 回复【后天】查询后天课表\n 回复【周课表】查询周课表\n 回复【自习】查询当前空教室\n 回复【查自习】查询空教室\n 回复【cet】查询四六级\n 回复【图书】查询图书馆图书\n 回复【考试】查询考试时间\n 回复【天气】查询天气预报\n 回复【快递】查询快递信息\n 回复【微信墙】进入微信墙\n 回复【游戏】玩小游戏\n 回复【盒子】查看城院盒子\n ";
        if($time == 1){
            $backMessage = "欢迎关注城院小助手！[愉快]\n\n".$mess;
        }else{
            $backMessage = "你是不是想说\n\n".$mess;
        }
        $weChat->text($backMessage)->reply();
        exit;
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

}
