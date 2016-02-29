<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller {
    
    public function index(){
        $this->display();
    }
    public function doLogin(){
        //校网模拟登录验证接口
        //git@github.com:wuxiwei/csxylink.git
        //curl -d 'username=学号&password=密码' http://server:port/api/login
        $studentno = I('post.studentno',''); 
        $password = I('post.password','');

        if ( $this->hasBindByStu($studentno) ) {  //已经绑定
            return 'al';
        }else{
            $idPass = M('idPass');  //先从本地数据库查询有没有之前绑定存在的帐号信息
            $where['studentno'] = $studentno;
            $where['password'] = $password;
            $count = $idPass-> where($where)->count();
            if($count>0){
                echo 'success';   //如果存在直接登录成功
            }else{
                echo 'error';  //如果不存在则
            }
        }
    }

    public function text(){
        echo C('LOGIN_LINK');
    }

    /*
     *绑定验证，如果已绑定返回学号，否则发送绑定提醒并exit
     *@param $studentno 根据学号值判断是否以绑定
     */
    public function hasBindByStu($studentno='0'){
        $sql = M('openId');
        $where['studentno'] = ':studentno';   //参数绑定
        $oId = $sql->where($where)->bind(':studentno',$studentno)->find();
        if($oId){
            return true;
        }else{
            return false;
        }
    }

    /*
     *绑定验证，如果已绑定返回学号，否则发送绑定提醒并exit
     *@param $openId 根据openId值判断是否以绑定
     *@param $return 默认false不返回数据 直接微信回复绑定提醒。反之true则返回false
     */
    public function hasBind($weChat, $openId='0', $return=false){
        $sql = M('openId');
        $where['openid'] = ':openid';   //参数绑定
        $oId = $sql->where($where)->bind(':openid',$openId)->find();
        if($oId){
            return $oId['studentno'];
        }else{
            if($return){
                return false;
            }else{
                $bind = array(
                    "0"=>array(
                        'Title'=>'戳我绑定登录~',
                        'Description'=>"新学期新气象\n新的绑定方式！\n最新数据查询方式！\n首次绑定可能较慢，稍作等待哦。",
                        'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/bind.png',
                        'Url'=> $_SERVER['HTTP_HOST'].U('Login/index')
                    ),
                 );
                $weChat->news($bind)->reply();
                exit;
            }
        }
    }
    public function add(){
        $sql = M('openId');
        $date=array(
            "openid" => "oX0iPwnBTFbL6FPLNSewkNyc22k0",
            "studentno" => "201312050",
            "date" => Date("Y-m-d"),
        );
        /* $a = $sql->add($date); */
        /* echo $a; */
        dump($sql->find());
        /* dump($sql->getField('studentno,password')); */
        /* $data1 = array('studentno'=>'201412052','password'=>'201412052'); */
        /* $sql-> where('id=2')->setField($data1); */
    }

}
