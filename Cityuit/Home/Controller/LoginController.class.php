<?php
namespace Home\Controller;
use Think\Controller;
/*
 *所有关于个人登录等信息的控制
 */
class LoginController extends Controller {
    
    public function index(){
        $authGet = I('get.auth','hihao');  
        $auth = replaceStr($authGet, false);  //将替换的字符换回来
        $openidVal = authcode($auth,'DECODE');   //只有带openidVal请求才是有效的，并且openidVal是有时效的加密
        if($openidVal){
            $this->assign('openid',$openidVal);
            $this->display();
        }else{
            $this->assign('error','链接超时失效，如果你已经绑定就不用担心了，如果没绑定，请重新获取。');
            $this->display('linkError');
        } 
    }

    public function doLogin(){
        //校网模拟登录验证接口
        //git@github.com:wuxiwei/csxylink.git
        //curl -d 'username=学号&password=密码' http://server:port/api/login
        $studentno = I('post.studentno',''); 
        $password = I('post.password','');
        $openidValue = I('post.openid','');

        if ( $this->hasBind('', $openidValue, true) ) {  //已经绑定(意味着密码错误也提示已经绑定了,所以一定做好解除绑定时的删除工作)
            echo 'al';
        }else{
            $Idpass = M('idPass');  //先从本地数据库查询有没有之前绑定存在的帐号信息
            $where['studentno'] = $studentno;
            $where['password'] = $password;
            $count = $Idpass->where($where)->count();
            if($count > 0){
                $Openid = M('openId');
                $openidVal = array(
                    "openid" => $openidValue,
                    "studentno" => $studentno,
                    "date" => Date("Y-m-d"),
                );
                $result = $Openid->data($openidVal)->add();
                if(!$result){
                    echo 'internal error';
                }else{   //如果存在直接登录成功，但是需要将openid重新添加到数据库
                    /* $this->showSuccessBind($openidValue);  //根据openid我们可以推送绑定成功提醒 */
                    echo 'success';
                }
            }else{
                $student = array(     
                    'username'=>$studentno,
                    'password'=>$password
                );      
                $resultJson = http_post(C('CITY_LINK').'login',$student);
                $resultArr = json_decode($resultJson, true);
                switch ( $resultArr['status'] ) {
                    case 'ok':    //登录成功
                        $idpassVal = $where;
                        $openidVal = array(
                            "openid" => $openidValue,
                            "studentno" => $studentno,
                            "date" => Date("Y-m-d"),
                        );
                        $result = $this->successBind($idpassVal, $openidVal);
                        if($result){
                            /* $this->showSuccessBind($openidValue);  //根据openid我们可以推送绑定成功提醒 */
                            echo 'success';
                        }else{
                            echo 'internal error';
                        }
                        break;
                    case 'login failed':   //登录失败
                        echo 'error';
                        break;
                    case 'School network connection failure':  //校网问题
                        echo 'school';
                        break;
                    default:
                        echo 'internal error';  //内部错误
                }
            }
        }
    }

    /*
     *绑定成功调用
     *@param array $idpassVal 保存到tp_id_pass的值
     *@param array $openidVal 保存到tp_open_id的值
     */
    public function successBind($idpassVal = array(), $openidVal = array()){
        //tp_id_pass表先操作
        if($idpassVal){
            $Idpass = M('idPass');
            $result = $Idpass->data($idpassVal)->add();
            if(!$result){
                return false;
            }
        }else{
            return false;
        }
        if($openidVal){
            $Openid = M('openId');
            $result = $Openid->data($openidVal)->add();
            if(!$result){
                return false;
            }
        }else{
            return false;
        } 
        return true;
    }

    /*
     *发送绑定成功提醒
     *需要直接发送至微信服务器，所以没办法实现，需要微信高级接口，需要认证
     */
    public function showSuccessBind($openidValue){
        $weChat = new WeChatApi();

        $count = 1;
        $newsData = array(
            "0"=>array(
                'Title'=>'学号绑定成功提醒',
            ),
        );
		$msg = array(
			'ToUserName' => $openidValue,
			'FromUserName'=> '原始ID',
			'MsgType'=>WeChatApi::MSGTYPE_NEWS,
			'CreateTime'=>time(),
			'ArticleCount'=>$count,
			'Articles'=>$newsData,
		);
        $weChat->reply($msg);
    }

    /*
     *获取openid加密值，做测试
     */
    public function getAuth(){
        $openidVal = $_GET['openid'] ? $_GET['openid'] : "oX0iPwnBTFbL6FPLNSewkNyc22k";
        echo replaceStr(authcode($openidVal,'ENCODE'));
  
    }


    /*
     *绑定验证，如果已绑定返回学号，否则发送绑定提醒并exit
     *@param $openidVal 根据openidVal值判断是否以绑定
     *@param $return 默认false不返回数据 直接微信回复绑定提醒。反之true则返回false
     */
    public function hasBind($weChat = null, $openidVal='0', $return=false){
        $Openid = M('openId');
        $where['openid'] = ':openid';   //参数绑定
        $oId = $Openid->where($where)->bind(':openid',$openidVal)->find();
        if($oId){
            return $oId['studentno'];
        }else{
            if($return){
                return false;
            }else{
                $this->showBind($weChat, $openidVal);
                exit;
            }
        }
    }

    /*
     *发送绑定提醒
     */
    public function showBind($weChat, $openidVal){
        //公共函数1.加密2.字符替换
        $auth = replaceStr(authcode($openidVal,'ENCODE'));
        $bind = array(
            "0"=>array(
                'Title'=>'戳我绑定登录~',
                'Description'=>"新学期新气象\n新的绑定方式！\n最新数据查询方式！\n首次绑定可能较慢，稍作等待哦。",
                'PicUrl'=> 'http://'.$_SERVER['HTTP_HOST'].'/Public/Image/bind.png',
                'Url'=> $_SERVER['HTTP_HOST'].U("Login/index?auth=$auth")
            ),
         );
        $weChat->news($bind)->reply();
    }

    /*
     *根据学号获取密码
     *@param string $studentno 学号
     */
    public function getPassword($studentno = ""){
        $Idpass = M('idPass');  //先从本地数据库查询有没有之前绑定存在的帐号信息
        $where['studentno']=':studentno';
        $idpassVal = $Idpass->where($where)->bind(':studentno',$studentno)->find();
        if($idpassVal){
            return $idpassVal['password'];  //存在就返回密码
        }else{
            return false;
        }
    }

    /*
     *解除绑定调用接口，只删除tp_open_id表
     */
    public function doLogout($weChat){
        $Openid = M("openId"); // 实例化User对象
        $where['openid'] = ':openid';   //参数绑定
        $Openid->where($where)->bind(':openid',$weChat->getRevFrom())->delete(); // 删除id为5的用户数据

        $weChat->text("取消绑定成功。\n期待你下次绑定，我会做的更好。")->reply();
        S($weChat->getRevFrom().'_do',null);   //删除缓存
    }

    /*
     *单击取消绑定处理
     */
    public function dealBind($weChat){
        $isBind = $this->hasBind($weChat, $weChat->getRevFrom(), true);
        if($isBind){
            $weChat->text("你确定要解除绑定吗，抛弃我么/可怜？欢迎提意见。\n\n回复【确认】取消绑定\n回复【exit】退出操作")->reply();
            S($weChat->getRevFrom().'_do','unbind','120');
        }else{
            $this->showBind($weChat, $weChat->getRevFrom());
        }
    }

    public function add(){
        /* $sql = M('openId'); */
        /* $date=array( */
        /*     "openid" => "oX0iPwnBTFbL6FPLNSewkNyc22k0", */
        /*     "studentno" => "201312050", */
        /*     "date" => Date("Y-m-d"), */
        /* ); */
        /* $a = $sql->add($date); */
        $studentno= I('in',''); 
        /* $studentno = I('studentno',''); */ 
        /* $password = I('password',''); */
        /*     $idpass = M('idPass');  //先从本地数据库查询有没有之前绑定存在的帐号信息 */
        /*     $where['studentno'] = $studentno; */
        /*     $where['password'] = $password; */
        /*     $find = $idpass->where($where)->select(); */
        /*     dump($find); */
        $Class = M('Class');
        $where['studentno']=':studentno';
        $classVal = $Class->where($where)->bind(':studentno',$studentno)->find();
        echo $classVal;
        /* dump($sql->find()); */
        /* dump($sql->getField('studentno,password')); */
        /* $data1 = array('studentno'=>'201412052','password'=>'201412052'); */
        /* $sql-> where('id=2')->setField($data1); */
    }

}
