<?php
namespace Home\Controller;
use Think\Controller;
use \Home\Model\WeChatApi;
class SettingController extends Controller {
    public function index(){
        //$this->display();
    }

    public function testM(){
        // 缓存数据300秒
        $value = "nihao";
        S('name',$value,30);
    }
    public function testQ(){
        // 缓存数据300秒
        $value = S('name');
        echo $value;
    }

    public function getCustomize(){
        $weChat = new WeChatApi();
        $Cus = $weChat->getMenu($newmenu);
        if($Cus){
            echo '<h1>按钮获取成功</h1>';
            dump($Cus);
        }else{
            echo '<h1>按钮获取失败</h1><h4>错误编号：'.$weChat->errCode.'；错误信息：'.$weChat->errMsg.'</h4>';
        }
    }
    
    //设置自定义菜单
    public function setCustomize(){
        $weChat = new WeChatApi();
        //设置菜单
        $newmenu =  array(
            "button"=> array (
                0 => array(
                    "name" => "个人查询",
                    "sub_button" => array (
                        0 => array (
                            "type" => "click",
                            "name" => "图书馆",
                            "key" => "library"
                        ),
                        1 => array (
                            "type" => "click",
                            "name" => "考试时间",
                            "key" => "exam"
                        ),
                        2 => array (
                            "type" => "click",
                            "name" => "期末成绩",
                            "key" => "score"
                        ),
                        3 => array (
                            "type" => "click",
                            "name" => "明日课表",
                            "key" => "tomorrow"
                        ),
                        4 => array (
                            "type" => "click",
                            "name" => "今日课表",
                            "key" => "today"
                        ),
                    ),
                ),
                1 => array(
                    "name" => "校园服务",
                    "sub_button" => array (
                        0 => array (
                            "type" => "click",
                            "name" => "表白墙",
                            "key" => "wall"
                        ),
                        1 => array (
                            "type" => "click",
                            "name" => "寻物平台",
                            "key" => "lost"
                        ),
                        2 => array (
                            "type" => "click",
                            "name" => "快递查询",
                            "key" => "express"
                        ),
                        3 => array (
                            "type" => "click",
                            "name" => "自习教室",
                            "key" => "classroom"
                        ),
                        4 => array (
                            "type" => "click",
                            "name" => "食堂菜单",
                            "key" => "canteen"
                        ),
                    ),
                ),
                2 => array(
                    "name" => "贴心帮助",
                    "sub_button" => array (
                        0 => array (
                            "type" => "click",
                            "name" => "饭卡挂失",
                            "key" => "loss"
                        ),
                        1 => array (
                            "type" => "click",
                            "name" => "更改密码",
                            "key" => "change"
                        ),
                        2 => array (
                            "type" => "click",
                            "name" => "城院盒子",
                            "key" => "box"
                        ),
                        3 => array (
                            "type" => "click",
                            "name" => "关于我们",
                            "key" => "about"
                        ),
                        4 => array (
                            "type" => "click",
                            "name" => "解除绑定",
                            "key" => "unbind"
                        ),
                    ),
                ),
            ),
        );
        if($weChat->createMenu($newmenu)){
            echo '<h1>按钮设置成功</h1>';
        }else{
            echo '<h1>按钮设置失败</h1><h4>错误编号：'.$weChat->errCode.'；错误信息：'.$weChat->errMsg.'</h4>';
        }
    }
}
