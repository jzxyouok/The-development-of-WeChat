<?php
namespace Home\Controller;
use Think\Controller;
use \Home\Model\WeChatApi;
/*
 *设置按钮，更新全院课表数据
 */
class SettingController extends Controller {
    public function index(){
        //$this->display();
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
                            "name" => "自习室",
                            "key" => "classroom"
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
                            "type" => "view",
                            "name" => "表白墙",
                            "url" => "http://csxywxq.sinaapp.com/w/"
                        ),
                        1 => array (
                            "type" => "click",
                            "name" => "四六级",
                            "key" => "cet"
                        ),
                        2 => array (
                            "type" => "click",
                            "name" => "图书馆",
                            "key" => "library"
                        ),
                        3 => array (
                            "type" => "click",
                            "name" => "快递查询",
                            "key" => "express"
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
                            "name" => "帮助",
                            "key" => "help"
                        ),
                        1 => array (
                            "type" => "click",
                            "name" => "信息更新",
                            "key" => "change"
                        ),
                        2 => array (
                            "type" => "view",
                            "name" => "城院盒子",
                            "url" => "http://fir.im/citybox"
                        ),
                        3 => array (
                            "type" => "view",
                            "name" => "关于我们",
                            "url" => "http://2.cityuit.applinzi.com"
                        ),
                        4 => array (
                            "type" => "click",
                            "name" => "解绑操作",
                            "key" => "bind"
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

    //获取服务器IP群 array
    public function getServerIp(){
        $weChat = new WeChatApi();
        $Ip = $weChat->getServerIp();
        if($Ip){
            echo '<h1>IP获取成功</h1>';
            dump($Ip);
        }else{
            echo '<h1>IP获取失败</h1><h4>错误编号：'.$weChat->errCode.'；错误信息：'.$weChat->errMsg.'</h4>';
        }
    }

    /*
     *先将Public/Html/course.html文件内容改为最新的信息。调用此方法将课程信息存入数据库，供查用。
     */
    public function setCourse(){
        //通过正则将页面中的信息提取
        header("content-type:text/html;charset=utf-8");    
        $conn=file_get_contents('./Public/Html/course.html');     //和index.php同一级目录
        $preg = '/选课限制说明.*(.*?)<\/table>.*<\/form>/is';      //尽量匹配到所有课程的那部分即可，table中的第一栏行不需要就干脆排除在外
        preg_match($preg, $conn, $out); 
        $pregTable = $out[0];

        $preg = '/<tr>.*?<\/tr>/is';
        preg_match_all($preg, $pregTable, $out);   //全局匹配<tr>标签
        $pregTr = $out[0];

        $courseArr = Array();
        for($i=0 ; $i<count($pregTr) ; $i++){
            preg_match_all ("|<[^>]+>(.*)</[^>]+>|U", $pregTr[$i], $out, PREG_PATTERN_ORDER);
            $courseArr[] = str_replace('&nbsp;','', $out[1][8]);    //将&nbsp;删除
        }
        dump($courseArr);    //$courseArr数组保存了所以教室用时

        //提前完成后开始存储操作 
        //先将所有教室存入数据库
        $roomArr = Array();
        for($i=0 ; $i<count($courseArr) ; $i++){
            $room = explode(" ", $courseArr[$i])[3];
            if($room && !in_array($room, $roomArr)){     //存在并且数组不包含该教室
                $roomArr[] = $room;
            }
        }
        /* dump($roomArr); */
        /* $Classroom = M('Classroom'); */
        /* if($Classroom->where(array("key"=>'room'))->count()){    //有就更新 */
        /*     $data['value'] = json_encode($roomArr, JSON_UNESCAPED_UNICODE); */
        /*     $Classroom->where(array("key"=>'room'))->save($data); // 根据条件更新记录 */
        /* }else{ */
        /*     $where['key'] = 'room'; */
        /*     $where['value'] = json_encode($roomArr, JSON_UNESCAPED_UNICODE); */
        /*     $Classroom->data($where)->add(); */ 
        /* } */

        //开始存取各时间段存在的课程
    }

}
