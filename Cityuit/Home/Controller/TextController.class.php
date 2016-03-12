<?php
namespace Home\Controller;
use Think\Controller;
/*
 * 默认用来处理文本的接口类，同时处理语音（转换为文本）
 */
class TextController extends Controller {

    public function index(){
        /* $this->display(); */
    }

    public $route_list=array(
        "kuaidi"=>"A('Campus')->resetExpress",
        "exit"=>"A('Help')->exitHelp",
        "kb"=>"A('Students')->dealSchedule",
        "week"=>"A('Students')->showWeekSchedule",
        "bd"=>"A('Login')->dealBind",
        "jc"=>"A('Login')->dealUnBind",
        "wall"=>"A('Campus')->loveWall",
        "citybox"=>"A('Help')->cityBox",
        "update"=>"A('Help')->updateInfo",
        "help"=>"A('Help')->dealHelp",
        "cj"=>"A('Students')->dealScore",
        "st"=>"A('Campus')->askShiting",
        "ks"=>"A('Students')->dealTeam",
        "fj"=>"A('Help')->showHoliday",
        "game"=>"A('Help')->dealGame",
        "weather"=>"A('Campus')->dealWeather",
        "test"=>"assistor_test",
        "tscx"=>"assistor_lib",
        "mxp"=>"assistor_gift",
        "news"=>"assistor_news",
        "notice"=>"assistor_notice",
        "cet"=>"assistor_cet",
        "joke"=>"assistor_joke",
        "qbst"=>"assistor_canteen_all",
        "teacherbd"=>"assistor_teacher_bind",
        "general_course"=>"assistor_general_course",
        "vod"=>"assistor_vod",
        "statistic_st"=>"assistor_count_st",
        "order_food"=>"assistor_order_food",
        "random_food"=>"assistor_random_food",
    );

    /*
     *文本语音控制接入接口
     *@param $type 0 => 文本  1 => 语音
     */
    public function dealText($weChat, $type = 0){
        if($type == 0){
            $mesText = $weChat->getRevContent();
        }else{
            $mesText = preg_replace('/[！]/', '', $weChat->getRevContent());  //每次语音转换会加上！，所以。
        }
        
        if(strtolower($mesText)=="exit" || strtolower($mesText)=="q" || $mesText=="退出"){
            A('Help')->exitHelp($weChat);               //如果exit判断在后面，查图书的时候将无法退出操作。
        }
        if(S($weChat->getRevFrom().'_do') == 'library') {   //为什么图书查询的动作要提前，避免和功能同名的图书没办法查询。
            A('Campus')->checkLibrary($weChat, $mesText);
        }

        $operate=$this->reduce_route($mesText);
        $funcname = $operate[0];
        $params = $operate[1];
        
        //先判断有没有直接功能需要实现
        if(isset($this->route_list[$funcname])){
            if(count($operate) == 1){   //数组一位时代表没有参数
                eval($this->route_list[$funcname].'($weChat);');   //eval将字符串转换为效的代码执行，必须以;结尾
            }
            else{  //有参数，查课表
                eval($this->route_list[$funcname].'($weChat,$params);');   //eval将字符串转换为效的代码执行，必须以;结尾
            }
        }else{
            //查看缓存中是否有操作
            switch ( S($weChat->getRevFrom().'_do') ) {
            case 'shitang':
                A('Campus')->dealShitang($weChat, $mesText);
                break;
            case 'caidan':
                A('Campus')->dealCaidan($weChat, $mesText);
                break;
            case 'score':
                A('students')->getScore($weChat, $mesText);
                break;
            case 'express':
                if(isExpress($mesText)){     //单号简单验证
                    A('Campus')->checkExpress($weChat, $mesText);
                    S($weChat->getRevFrom().'_spress',$mesText,'86400');   //将单号存入缓存，默认一天时间
                }else{
                    $weChat->text("单号无法识别，重新输入。\n回复【exit】退出操作。")->reply();
                }
                break;
            case 'updateinfo':
                if($mesText == '确认'){
                    A('Help')->dealUpdate($weChat);
                }else{
                    $weChat->text("有疑问，请输入【帮助】查询。")->reply();
                }
                break;
            case 'unbind':
                if($mesText == '确认'){
                    A('Login')->doLogout($weChat);  //调用解绑方法
                }else{
                    $weChat->text("有疑问，请输入【帮助】查询。")->reply();
                }
                break;
            default:
                $weChat->text("有疑问，请输入【帮助】查询。")->reply();
            }
        }
    }


    public function reduce_route($form_Content){
        if( $form_Content=="快递" || $form_Content=="快递查询" || strtolower($form_Content)=="kd" || strtolower($form_Content)=="kuaidi" ){
            return array("kuaidi");
        }
        if($form_Content=="课表" || $form_Content=="今天课表" || $form_Content=="今天" || strtolower($form_Content)=="jtkb" || strtolower($form_Content)=="kb" || strtolower($form_Content)=="jt"){
            return array("kb", 0);
        }
        if($form_Content=="明天" || $form_Content=="明天课表" || strtolower($form_Content)=="mt"){
            return array("kb", 1);
        }
        if($form_Content=="后天" || $form_Content=="后天课表" || strtolower($form_Content)=="ht"){
            return array("kb", 2);
        }
        if( $form_Content=="周课表" || strtolower($form_Content)=="week" ){
            return array("week");
        }
        if(strtolower($form_Content)=="help" || $form_Content=="帮助" || strtolower($form_Content)=="bz"){
            return array("help");
        }
        if(strtolower($form_Content)=="exit" || strtolower($form_Content)=="q" || $form_Content=="退出"){
            return array("exit");
        }
        if( $form_Content=="绑定" || strtolower($form_Content)=="bd" || $form_Content=="绑定帐号" ){
            return array("bd");
        }
        if( $form_Content=="解除" || $form_Content=="取消绑定" || $form_Content=="解除绑定" || strtolower($form_Content)=="jc" ){
            return array("jc");
        }
        if( stristr($form_Content,"表白") || stristr($form_Content,"吐槽") || stristr($form_Content,"心愿") || stristr($form_Content,"墙")){
            return array("wall");
        }
        if( $form_Content=="城院盒子" || $form_Content=="盒子" || strtolower($form_Content)=="citybox" ){
            return array("citybox");
        }
        if( $form_Content=="信息更新" || $form_Content=="更新" || strtolower($form_Content)=="gx" ){
            return array("update");
        }
        if($form_Content=="成绩" || stristr($form_Content,"成绩") || strtolower($form_Content)=="cj"){
            return array("cj", 1);
        }
        if(strtolower($form_Content)=="st" || $form_Content=="食堂" || $form_Content=="食堂菜单" || $form_Content=="菜单"){
            return array("st");
        }
        if( $form_Content=="考试时间" || $form_Content=="考试" || strtolower($form_Content)=="ks" || strtolower($form_Content)=="exam" ){
            return array("ks");
        }
        if($form_Content=="放假" || $form_Content=="放假时间" || strtolower($form_Content)=="fjxx" || strtolower($form_Content)=="fj" ){
            return array("fj");
        }
        if( $form_Content=="game" || $form_Content=="游戏" || $form_Content=="小游戏"){
            return array("game");
        }
        if($form_Content=="天气" || $form_Content=="天气预报"|| $form_Content=="大连天气" || $form_Content=="明天天气" || strtolower($form_Content)=="tq" || strtolower($form_Content)=="weather" ){
            return array("weather");
        }
        if(strtolower($form_Content)=="test"){
            return array("test");
        }
        if( $form_Content=="图书" || strtolower($form_Content)=="tscx" || $form_Content=="图书馆" || $form_Content=="书籍查询" || $form_Content=="图书查询"){
            return array("tscx");
        }
        if(strtolower($form_Content)=="cet" || $form_Content=="四六级" || $form_Content=="四六级成绩"|| $form_Content=="四级" || $form_Content=="六级"){
            return array("cet");
        }
        /* if( $form_Content=="通识课" || $form_Content=="选修课" || $form_Content=="选修" ){ */
        /*     return array("general_course"); */
        /* } */
    }
}
