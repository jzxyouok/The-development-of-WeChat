<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller {
    public function index(){
        $this->display();
    }
    public function doLogin(){
        $studentno = I('post.studentno',''); 
        $password = I('post.password','');

        $sql = M('idPass');
        $where['studentno'] = $studentno;
        $where['password'] = $password;
        $count = $sql-> where($where)->count();
        if($count>0){
            echo 'success';
        }else{
            echo 'error';
        }
    }

    /*
     *绑定验证，如果已绑定返回学号，否则发送绑定提醒并exit
     */
    public function hasBind($weChat, $openId='0'){
        $sql = M('openId');
        $where['openid'] = ':openid';   //参数绑定
        $oId = $sql->where($where)->bind(':openid',$openId)->find();
        if($oId){
            return $oId['studentno'];
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
