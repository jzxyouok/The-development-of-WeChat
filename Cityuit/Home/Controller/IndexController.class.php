<?php
namespace Home\Controller;
use Think\Controller;
use \Home\Model\WeChatApi;
class IndexController extends Controller {
    public function index(){
       if(isset($_GET["signature"])){   //默认为微信服务器发送的请求
            $weChat = new WeChatApi();
            $weChat->valid();  //每次请求都验证,可以省去
            $type = $weChat->getRev()->getRevType();
            switch($type) {
                case WeChatApi::MSGTYPE_TEXT:
                    A('Text')->dealText($weChat);
                    //判断当前操作
                    exit;
                    break;
                case WeChatApi::MSGTYPE_IMAGE:
                    $weChat->text("图片不错哦！")->reply();
                    exit;
                    break;
                case WeChatApi::MSGTYPE_VOICE:
                    /* $content = $weChat->getRevContent(); */
                    /* $weChat->text($content)->reply(); */
                    A('Text')->dealText($weChat, 1);
                    exit;
                    break;
                case WeChatApi::MSGTYPE_VIDEO:
                    $weChat->text("视频不错哦！")->reply();
                    exit;
                    break;
                case WeChatApi::MSGTYPE_LOCATION:
                    $location = $weChat->getRevGeo();
                    $Location_Y = $location[y];  //经度
                    $Location_X = $location[x];  //纬度
                    $content = '你目前所在经度：'.$Location_Y.'，纬度：'.$Location_X.'。';
                    $weChat->text($content)->reply();
                    exit;
                    break;
                case WeChatApi::MSGTYPE_LINK:
                    $weChat->text("我不会随便打开的！")->reply();
                    exit;
                    break;
                case WeChatApi::MSGTYPE_EVENT:
                    $event = $weChat->getRevEvent();
                    if($event == WeChatApi::EVENT_SUBSCRIBE){
                        //新用户关注
                        $weChat->text("欢迎关注！")->reply();
                    }else if($event == WeChatApi::EVENT_MENU_CLICK){
                        //自定义菜单按钮
                        $eventKey = $weChat->getRevEventKey();
                        switch ($eventKey) {
                            case "library":
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());
                                $weChat->text($id)->reply();
                                break;
                            case "exam":
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());
                                $weChat->text($id)->reply();
                                break;
                            case "score":
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());
                                $weChat->text($id)->reply();
                                break;
                            case "tomorrow":
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());
                                A('Students')->dealSchedule($weChat,1,$id);
                                break;
                            case "today":
                                /* $weChat->text("我是课表")->reply(); */
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());  //实例化Login控制器调用方法,如果存在返回学号值，不存在方法已将提醒绑定发送，并停止程序
                                A('Students')->dealSchedule($weChat,0,$id);
                                break;
                            /* case "wall": */  //直接跳转
                            /*     $weChat->text("表白墙")->reply(); */
                            /*     break; */
                            case "cet":
                                $weChat->text("四六级")->reply();
                                break;
                            case "express":
                                A('Campus')->dealExpress($weChat);
                                break;
                            case "classroom":
                                $weChat->text("自习教室")->reply();
                                break;
                            case "canteen":
                                $weChat->text("食堂菜单")->reply();
                                break;
                            case "loss":
                                $weChat->text("饭卡挂失")->reply();
                                break;
                            case "change":
                                /* $weChat->text("信息更新")->reply(); */
                                $id = A('Login')->hasBind($weChat, $weChat->getRevFrom(), true);  //实例化Login控制器调用方法,如果存在返回学号值，不存在
                                A('Help')->updateInfo($weChat, $id);    //方法通过$id确认是否有绑定过
                                break;
                            case "box":
                                $weChat->text("城院盒子")->reply();
                                break;
                            case "about":
                                $weChat->text("关于我们")->reply();
                                break;
                            case "bind":
                                A('Login')->dealBind($weChat);
                                break;
                            default:
                                $weChat->text("你说什么")->reply();
                                break;
                        }
                    }
                    exit;
                    break;
                default:
                    $weChat->text("我不懂你！")->reply();
                    exit;
                    break;
            }
        }else{
            /* echo $_SERVER['HTTP_HOST']; */
            //确认不是微信发送的请求
            $this->display();   //输出关于我们主页
        }
    }
}
?>
