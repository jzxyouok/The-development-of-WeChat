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
}
