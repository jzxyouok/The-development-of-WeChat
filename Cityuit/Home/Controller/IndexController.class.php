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
                            case "classroom":
                                $weChat->text("自习教室")->reply();
                                break;
                            case "exam":
                                A('Students')->dealTeam($weChat);
                                break;
                            case "score":
                                A('Students')->dealTeam($weChat);
                                break;
                            case "tomorrow":
                                A('Students')->dealSchedule($weChat,1);
                                break;
                            case "today":
                                A('Students')->dealSchedule($weChat,0);
                                break;
                            case "cet":
                                $weChat->text("四六级")->reply();
                                break;
                            case "library":
                                A('Campus')->askLibrary($weChat);
                                break;
                            case "express":
                                A('Campus')->dealExpress($weChat);
                                break;
                            case "canteen":
                                $weChat->text("食堂菜单")->reply();
                                break;
                            case "help":
                                A('Help')->dealHelp($weChat);    //方法通过$id确认是否有绑定过
                                break;
                            case "change":
                                A('Help')->updateInfo($weChat);    //方法通过$id确认是否有绑定过
                                break;
                            case "bind":
                                A('Login')->dealUnBind($weChat);
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
