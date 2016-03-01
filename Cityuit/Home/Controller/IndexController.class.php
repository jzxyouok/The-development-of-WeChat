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
                    $content = $weChat->getRevContent();
                    $weChat->text($content)->reply();
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
                                $weChat->text($id)->reply();
                                break;
                            case "today":
                                /* $weChat->text("我是课表")->reply(); */
                                /* $id = A('Login')->hasBind($weChat, $weChat->getRevFrom());  //实例化Login控制器调用方法 */
                                $class = array(
                                    "0"=>array(
                                        'Title'=>'今日课表(02月29日 周一)',
                                    ),
                                    "1"=>array(
                                        'Title'=>'今日没有课呢~快出去看看吧',
                                    ),
                                    "3"=>array(
                                        'Title'=>'点此chuo进一周课表 ^_^|||',
                                    ),
                                 );
                                $weChat->news($class)->reply();
                                break;
                            case "wall":
                                $weChat->text("表白墙")->reply();
                                break;
                            case "lost":
                                $weChat->text("寻物平台")->reply();
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
                                $weChat->text("信息更新")->reply();
                                break;
                            case "box":
                                $weChat->text("城院盒子")->reply();
                                break;
                            case "about":
                                $weChat->text("关于我们")->reply();
                                break;
                            case "unbind":
                                $isBind = A('Login')->hasBind($weChat, $weChat->getRevFrom(), true);
                                if($isBind){
                                    S($weChat->getRevFrom().'_do','unbind','120');
                                    $weChat->text("你确定要解除绑定吗，抛弃我么/可怜？欢迎提意见。\n\n回复【确认】取消绑定")->reply();
                                }else{
                                    A('Login')->showBind($weChat, $weChat->getRevFrom());
                                }
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
            echo 'Hello World';
            $weChat1 = new WeChatApi();
        }
    }
}
?>
